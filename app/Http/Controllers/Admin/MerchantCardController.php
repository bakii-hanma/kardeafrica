<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MerchantCard;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Admin\MerchantCardController
 * ===
 * Gestion centralisée des cartes-cadeau Carte Gabon. C'est l'admin qui crée
 * les cartes (les boutiques ne font que les vendre).
 *
 * Workflow :
 *  - Admin crée → is_active=false (brouillon)
 *  - Admin active → is_active=true → carte publique sur /gabon
 *  - Admin refuse une carte créée par un vendor (legacy) → rejection_reason
 */
class MerchantCardController extends Controller
{
    /** Liste avec stats + filtres */
    public function index(Request $request)
    {
        $status = $request->query('status', '');
        $search = trim((string) $request->query('search', ''));

        $query = MerchantCard::withCount('purchases');

        if ($status === 'pending') {
            $query->where('is_active', false)->whereNull('rejection_reason');
        } elseif ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'rejected') {
            $query->whereNotNull('rejection_reason')->where('is_active', false);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $cards = $query->latest('created_at')->paginate(24)->withQueryString();

        $stats = [
            'total'    => MerchantCard::count(),
            'pending'  => MerchantCard::where('is_active', false)->whereNull('rejection_reason')->count(),
            'active'   => MerchantCard::where('is_active', true)->count(),
            'rejected' => MerchantCard::whereNotNull('rejection_reason')->where('is_active', false)->count(),
        ];

        return view('admin.merchant-cards.index', [
            'cards'      => $cards,
            'stats'      => $stats,
            'categories' => MerchantCard::CATEGORIES,
            'status'     => $status,
            'search'     => $search,
        ]);
    }

    /** Formulaire création — par défaut la carte est active immédiatement */
    public function create()
    {
        return view('admin.merchant-cards.create', [
            'card'       => new MerchantCard([
                'currency'        => 'XAF',
                'validity_months' => 12,
                'denominations'   => [5000, 10000, 25000, 50000],
                'is_active'       => true,
            ]),
            'categories' => MerchantCard::CATEGORIES,
        ]);
    }

    /** Crée la carte (catalogue admin global, pas de marchand attaché) */
    public function store(Request $request)
    {
        $data = $this->validated($request);

        $card = new MerchantCard($data);
        $card->is_active     = (bool) ($data['is_active'] ?? false);
        $card->activated_at  = $card->is_active ? now() : null;
        $card->total_sold    = 0;
        $card->total_revenue = 0;

        // Pas de code/PIN sur le template : ils sont générés UNIQUEMENT à l'achat
        // (un code distinct par client dans merchant_card_purchases).

        if ($request->hasFile('visual')) {
            $card->visual_url = $this->storeVisual($request->file('visual'));
        }

        $card->save();

        return redirect()
            ->route('admin.merchant-cards.show', $card)
            ->with('success', 'Carte créée' . ($card->is_active ? ' et publiée immédiatement.' : ' (brouillon — à activer ensuite).'));
    }

    /** Détail + form approve/reject */
    public function show(MerchantCard $merchantCard)
    {
        $merchantCard->load('purchases');

        return view('admin.merchant-cards.show', [
            'card'       => $merchantCard,
            'categories' => MerchantCard::CATEGORIES,
        ]);
    }

    /** Formulaire édition */
    public function edit(MerchantCard $merchantCard)
    {
        return view('admin.merchant-cards.edit', [
            'card'       => $merchantCard,
            'categories' => MerchantCard::CATEGORIES,
        ]);
    }

    /** Met à jour la carte */
    public function update(Request $request, MerchantCard $merchantCard)
    {
        $data = $this->validated($request);

        if ($request->hasFile('visual')) {
            $this->deleteVisual($merchantCard->visual_url);
            $data['visual_url'] = $this->storeVisual($request->file('visual'));
        }

        $data['is_active']    = (bool) ($data['is_active'] ?? false);
        $data['activated_at'] = $data['is_active']
            ? ($merchantCard->activated_at ?? now())
            : null;

        $merchantCard->update($data);

        return redirect()
            ->route('admin.merchant-cards.show', $merchantCard)
            ->with('success', 'Carte mise à jour.');
    }

    /** Approuve : publie publiquement sur /gabon */
    public function approve(MerchantCard $merchantCard)
    {
        $merchantCard->update([
            'is_active'        => true,
            'activated_at'     => now(),
            'rejection_reason' => null,
        ]);

        return back()->with('success', "« {$merchantCard->name} » approuvée et publiée sur Kardafrica.");
    }

    /** Refuse avec motif (vendor verra la raison sur sa carte) */
    public function reject(Request $request, MerchantCard $merchantCard)
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $merchantCard->update([
            'is_active'        => false,
            'activated_at'     => null,
            'rejection_reason' => $data['rejection_reason'],
        ]);

        return redirect()
            ->route('admin.merchant-cards.index', ['status' => 'rejected'])
            ->with('success', "« {$merchantCard->name} » refusée. Le marchand recevra le motif.");
    }

    /** Supprime la carte (soft si purchases existantes) */
    public function destroy(MerchantCard $merchantCard)
    {
        if ($merchantCard->purchases()->exists()) {
            $merchantCard->update(['is_active' => false]);
            return redirect()
                ->route('admin.merchant-cards.index')
                ->with('success', 'Carte désactivée (impossible de supprimer : des achats existent).');
        }

        $this->deleteVisual($merchantCard->visual_url);
        $merchantCard->delete();

        return redirect()
            ->route('admin.merchant-cards.index')
            ->with('success', 'Carte supprimée.');
    }

    // ============================================================
    // Helpers privés
    // ============================================================

    /** Validation partagée create + update */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:120'],
            'description'         => ['nullable', 'string', 'max:2000'],
            'category'            => ['required', 'string', Rule::in(array_keys(MerchantCard::CATEGORIES))],
            'visual'              => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'denominations'       => ['required', 'array', 'min:1', 'max:10'],
            'denominations.*'     => ['required', 'integer', 'min:500', 'max:1000000'],
            'allow_custom_amount' => ['nullable', 'boolean'],
            'min_amount'          => ['nullable', 'required_if:allow_custom_amount,1', 'numeric', 'min:500'],
            'max_amount'          => ['nullable', 'required_if:allow_custom_amount,1', 'numeric', 'gte:min_amount', 'max:1000000'],
            'validity_months'     => ['required', 'integer', 'min:1', 'max:60'],
            'terms_conditions'    => ['nullable', 'string', 'max:5000'],
            'is_active'           => ['nullable', 'boolean'],
        ]);

        $denoms = array_values(array_unique(array_map('intval', $validated['denominations'])));
        sort($denoms);
        $validated['denominations'] = $denoms;

        $validated['allow_custom_amount'] = (bool) ($validated['allow_custom_amount'] ?? false);
        if (!$validated['allow_custom_amount']) {
            $validated['min_amount'] = null;
            $validated['max_amount'] = null;
        }

        $validated['currency'] = 'XAF';
        unset($validated['visual']);

        return $validated;
    }

    /**
     * Sauve le visuel directement à la racine du doc-root nginx (= base_path()).
     * Sur SiteGround, public_html/ EST la racine servie par nginx ; public_path()
     * pointe vers public_html/public/ qui n'est PAS exposé. On écrit donc dans
     * public_html/merchant-cards/admin/ pour que l'image soit accessible via
     * https://kardafrica.com/merchant-cards/admin/<file>.
     */
    private function storeVisual(UploadedFile $file): string
    {
        $relDir = 'merchant-cards/admin';
        $absDir = base_path($relDir);
        if (!is_dir($absDir)) {
            @mkdir($absDir, 0755, true);
        }
        $filename = Str::random(40).'.'.strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $file->move($absDir, $filename);
        return $relDir.'/'.$filename;
    }

    /** Supprime un fichier visuel (à la racine doc-root) */
    private function deleteVisual(?string $relPath): void
    {
        if (!$relPath) return;
        $abs = base_path($relPath);
        if (is_file($abs)) {
            @unlink($abs);
        }
    }
}
