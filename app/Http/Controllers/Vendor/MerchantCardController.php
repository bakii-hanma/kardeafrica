<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\MerchantCard;
use App\Support\MerchantSlug;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * MerchantCardController (vendor side)
 * ===
 * CRUD pour les cartes-cadeau créées par les marchands eux-mêmes (≠ ResellerCard
 * qui est le catalogue afrikard que le vendor revend).
 *
 * Workflow :
 *  1. Vendor crée → is_active=false  (en attente activation admin)
 *  2. Admin valide visuel + contenu  → is_active=true (Phase 6)
 *  3. Carte visible sur /gabon       (Phase 3)
 *
 * Le vendor ne peut PAS activer lui-même (sécurité + modération obligatoire).
 */
class MerchantCardController extends Controller
{
    /** Catégories proposées au vendor (modifiables — c'est juste pour le select) */
    public const CATEGORIES = [
        'restaurant'   => 'Restaurant / Café',
        'mode'         => 'Mode & Vêtements',
        'beaute'       => 'Beauté & Coiffure',
        'spa'          => 'Spa & Bien-être',
        'sport'        => 'Sport & Fitness',
        'supermarche'  => 'Supermarché / Alimentation',
        'electronique' => 'Électronique & High-tech',
        'maison'       => 'Maison & Déco',
        'loisirs'      => 'Loisirs & Divertissement',
        'sante'        => 'Santé & Pharmacie',
        'autre'        => 'Autre',
    ];

    public function index()
    {
        /** @var \App\Models\Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();

        $cards = $reseller->merchantCards()
            ->withCount('purchases')
            ->latest()
            ->get();

        return view('vendor.merchant-cards.index', [
            'reseller' => $reseller,
            'cards'    => $cards,
        ]);
    }

    public function create()
    {
        /** @var \App\Models\Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();

        return view('vendor.merchant-cards.create', [
            'reseller'   => $reseller,
            'categories' => self::CATEGORIES,
            'card'       => new MerchantCard([
                'currency'        => 'XAF',
                'validity_months' => 12,
                'denominations'   => [5000, 10000, 25000, 50000],
            ]),
        ]);
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();

        $data = $this->validated($request);

        // S'assurer qu'un slug marchand existe (le marketplace /gabon en a besoin)
        if (empty($reseller->slug) && !empty($reseller->business_name ?? $reseller->name)) {
            $reseller->slug = MerchantSlug::generate($reseller->business_name ?? $reseller->name, $reseller->id);
            $reseller->save();
        }

        $card = new MerchantCard($data);
        $card->reseller_id = $reseller->id;
        $card->is_active   = false; // toujours en attente admin
        $card->total_sold  = 0;
        $card->total_revenue = 0;

        if ($request->hasFile('visual')) {
            $card->visual_url = $request->file('visual')->store(
                'merchant-cards/'.$reseller->id,
                'public'
            );
        }

        $card->save();

        return redirect()
            ->route('vendor.merchant-cards.index')
            ->with('success', 'Carte créée. Elle sera visible publiquement après validation par l\'équipe KardAfrica.');
    }

    public function edit(MerchantCard $merchantCard)
    {
        $this->authorizeCard($merchantCard);

        return view('vendor.merchant-cards.edit', [
            'reseller'   => Auth::guard('vendor')->user(),
            'categories' => self::CATEGORIES,
            'card'       => $merchantCard,
        ]);
    }

    public function update(Request $request, MerchantCard $merchantCard)
    {
        $this->authorizeCard($merchantCard);

        $data = $this->validated($request);

        if ($request->hasFile('visual')) {
            // Supprimer l'ancien visuel pour libérer l'espace
            if ($merchantCard->visual_url) {
                Storage::disk('public')->delete($merchantCard->visual_url);
            }
            $data['visual_url'] = $request->file('visual')->store(
                'merchant-cards/'.$merchantCard->reseller_id,
                'public'
            );
        }

        // Toute modification d'une carte déjà active la repasse en attente
        // (sinon un vendor pourrait changer le contenu après validation admin)
        if ($merchantCard->is_active) {
            $data['is_active']     = false;
            $data['activated_at']  = null;
        }

        $merchantCard->update($data);

        return redirect()
            ->route('vendor.merchant-cards.index')
            ->with('success', 'Carte mise à jour. Elle repasse en attente de validation.');
    }

    public function destroy(MerchantCard $merchantCard)
    {
        $this->authorizeCard($merchantCard);

        // Pas de delete physique si des achats existent — on désactive juste,
        // sinon on casserait l'historique des MerchantCardPurchase liées
        if ($merchantCard->purchases()->exists()) {
            $merchantCard->update(['is_active' => false]);
            return redirect()
                ->route('vendor.merchant-cards.index')
                ->with('success', 'Carte désactivée (impossible de supprimer : des achats existent).');
        }

        if ($merchantCard->visual_url) {
            Storage::disk('public')->delete($merchantCard->visual_url);
        }
        $merchantCard->delete();

        return redirect()
            ->route('vendor.merchant-cards.index')
            ->with('success', 'Carte supprimée.');
    }

    // ============================================================
    // Helpers privés
    // ============================================================

    /** 403 si la carte n'appartient pas au vendor connecté */
    private function authorizeCard(MerchantCard $card): void
    {
        $reseller = Auth::guard('vendor')->user();
        abort_if($card->reseller_id !== $reseller->id, 403);
    }

    /**
     * Validation partagée create + update. Retourne les data prêtes à insert,
     * avec les dénominations déjà parsées en array d'entiers triés.
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:120'],
            'description'         => ['nullable', 'string', 'max:2000'],
            'category'            => ['required', 'string', Rule::in(array_keys(self::CATEGORIES))],
            'visual'              => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'], // 3MB
            'denominations'       => ['required', 'array', 'min:1', 'max:10'],
            'denominations.*'     => ['required', 'integer', 'min:500', 'max:1000000'],
            'allow_custom_amount' => ['nullable', 'boolean'],
            'min_amount'          => ['nullable', 'required_if:allow_custom_amount,1', 'numeric', 'min:500'],
            'max_amount'          => ['nullable', 'required_if:allow_custom_amount,1', 'numeric', 'gte:min_amount', 'max:1000000'],
            'validity_months'     => ['required', 'integer', 'min:1', 'max:60'],
            'terms_conditions'    => ['nullable', 'string', 'max:5000'],
        ]);

        // Nettoyer/dédupliquer/trier les dénominations
        $denoms = array_values(array_unique(array_map('intval', $validated['denominations'])));
        sort($denoms);
        $validated['denominations'] = $denoms;

        $validated['allow_custom_amount'] = (bool) ($validated['allow_custom_amount'] ?? false);
        if (!$validated['allow_custom_amount']) {
            $validated['min_amount'] = null;
            $validated['max_amount'] = null;
        }

        $validated['currency'] = 'XAF';

        // visual_url est géré séparément (file upload)
        unset($validated['visual']);

        return $validated;
    }
}
