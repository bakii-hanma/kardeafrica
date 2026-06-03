<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\MerchantCard;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * CRUD des cartes locales côté propriétaire.
 *
 * Workflow : le propriétaire crée une carte en brouillon (is_active = false).
 * Un admin la valide ensuite via /admin/merchant-cards. Les taux de commission
 * (admin / vendeur) sont fixés exclusivement par l'admin — invisibles au form
 * propriétaire.
 */
class CardController extends Controller
{
    public function create()
    {
        return view('owner.cards.create', [
            'owner'      => Auth::guard('card_owner')->user(),
            'card'       => new MerchantCard([
                'currency'        => 'XAF',
                'validity_months' => 12,
                'denominations'   => [5000, 10000, 25000, 50000],
                'is_active'       => false, // toujours brouillon à la création par le propriétaire
            ]),
            'categories' => MerchantCard::CATEGORIES,
            'isEdit'     => false,
        ]);
    }

    public function store(Request $request)
    {
        $owner = Auth::guard('card_owner')->user();
        $data  = $this->validated($request);

        $card = new MerchantCard($data);
        $card->card_owner_id = $owner->id;
        $card->is_active     = false; // brouillon obligatoire à la création
        $card->activated_at  = null;
        $card->total_sold    = 0;
        $card->total_revenue = 0;

        if ($request->hasFile('visual')) {
            $card->visual_url = $this->storeVisual($request->file('visual'));
        }

        $card->save();

        return redirect()
            ->route('owner.card.show', $card)
            ->with('success', "« {$card->name} » créée. En attente de validation par l'admin avant publication sur Kardafrica.");
    }

    public function edit(MerchantCard $merchantCard)
    {
        $owner = Auth::guard('card_owner')->user();
        abort_unless($merchantCard->card_owner_id === $owner->id, 404);

        return view('owner.cards.edit', [
            'owner'      => $owner,
            'card'       => $merchantCard,
            'categories' => MerchantCard::CATEGORIES,
            'isEdit'     => true,
        ]);
    }

    public function update(Request $request, MerchantCard $merchantCard)
    {
        $owner = Auth::guard('card_owner')->user();
        abort_unless($merchantCard->card_owner_id === $owner->id, 404);

        $data = $this->validated($request);

        if ($request->hasFile('visual')) {
            $this->deleteVisual($merchantCard->visual_url);
            $data['visual_url'] = $this->storeVisual($request->file('visual'));
        }

        // Si la carte est déjà publiée, une édition la repasse en brouillon
        // (pour que l'admin re-valide après modification).
        $wasActive = $merchantCard->is_active;
        if ($wasActive) {
            $data['is_active']    = false;
            $data['activated_at'] = null;
        }

        $merchantCard->update($data);

        return redirect()
            ->route('owner.card.show', $merchantCard)
            ->with('success', $wasActive
                ? 'Carte mise à jour. Re-soumise pour validation à l\'admin.'
                : 'Carte mise à jour.');
    }

    public function destroy(MerchantCard $merchantCard)
    {
        $owner = Auth::guard('card_owner')->user();
        abort_unless($merchantCard->card_owner_id === $owner->id, 404);

        // Refuse la suppression si des achats existent (intégrité)
        if ($merchantCard->purchases()->exists()) {
            $merchantCard->update(['is_active' => false]);
            return redirect()
                ->route('owner.cards')
                ->with('success', 'Carte désactivée (impossible de supprimer : des achats existent).');
        }

        $this->deleteVisual($merchantCard->visual_url);
        $merchantCard->delete();

        return redirect()
            ->route('owner.cards')
            ->with('success', 'Carte supprimée.');
    }

    // ============================================================
    // Helpers
    // ============================================================

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

    private function storeVisual(UploadedFile $file): string
    {
        $relDir = 'merchant-cards/owners';
        $absDir = base_path($relDir);
        if (!is_dir($absDir)) {
            @mkdir($absDir, 0755, true);
        }
        $filename = Str::random(40).'.'.strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $file->move($absDir, $filename);
        return $relDir.'/'.$filename;
    }

    private function deleteVisual(?string $relPath): void
    {
        if (!$relPath) return;
        $abs = base_path($relPath);
        if (is_file($abs)) {
            @unlink($abs);
        }
    }
}
