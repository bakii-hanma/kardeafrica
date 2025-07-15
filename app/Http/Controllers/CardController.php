<?php

namespace App\Http\Controllers;

use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class CardController extends Controller
{
    /**
     * Afficher la liste des cartes
     */
    public function index(): View
    {
        $cards = Card::with('user')
            ->latest()
            ->paginate(12);

        return view('cards.index', compact('cards'));
    }

    /**
     * Afficher le formulaire de création de carte
     */
    public function create(): View
    {
        return view('cards.create');
    }

    /**
     * Enregistrer une nouvelle carte
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'value' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'description' => 'nullable|string',
            'brand' => 'nullable|string|max:255',
            'expiry_date' => 'nullable|date|after:today',
            'image' => 'nullable|image|max:2048',
        ]);

        // Gestion de l'upload d'image
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('cards', 'public');
        }

        // Générer un code unique
        $validated['code'] = Card::generateUniqueCode();
        $validated['balance'] = $validated['value']; // Solde initial = valeur
        $validated['user_id'] = Auth::id();

        $card = Card::create($validated);

        return redirect()
            ->route('cards.show', $card)
            ->with('success', 'Carte créée avec succès !');
    }

    /**
     * Afficher une carte spécifique
     */
    public function show(Card $card): View
    {
        return view('cards.show', compact('card'));
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(Card $card): View
    {
        return view('cards.edit', compact('card'));
    }

    /**
     * Mettre à jour une carte
     */
    public function update(Request $request, Card $card): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'value' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'description' => 'nullable|string',
            'brand' => 'nullable|string|max:255',
            'expiry_date' => 'nullable|date|after:today',
            'image' => 'nullable|image|max:2048',
            'status' => 'required|in:active,expired,used',
        ]);

        // Gestion de l'upload d'image
        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image
            if ($card->image) {
                Storage::disk('public')->delete($card->image);
            }
            $validated['image'] = $request->file('image')->store('cards', 'public');
        }

        $card->update($validated);

        return redirect()
            ->route('cards.show', $card)
            ->with('success', 'Carte mise à jour avec succès !');
    }

    /**
     * Supprimer une carte
     */
    public function destroy(Card $card): RedirectResponse
    {
        // Supprimer l'image associée
        if ($card->image) {
            Storage::disk('public')->delete($card->image);
        }

        $card->delete();

        return redirect()
            ->route('cards.index')
            ->with('success', 'Carte supprimée avec succès !');
    }

    /**
     * Rechercher des cartes
     */
    public function search(Request $request): View
    {
        $query = $request->get('search');
        
        $cards = Card::where('name', 'like', "%{$query}%")
            ->orWhere('code', 'like', "%{$query}%")
            ->orWhere('brand', 'like', "%{$query}%")
            ->orWhere('type', 'like', "%{$query}%")
            ->with('user')
            ->latest()
            ->paginate(12)
            ->appends(['search' => $query]);

        return view('cards.index', compact('cards', 'query'));
    }

    /**
     * Afficher les cartes par type
     */
    public function byType(string $type): View
    {
        $cards = Card::where('type', $type)
            ->with('user')
            ->latest()
            ->paginate(12);

        return view('cards.index', compact('cards', 'type'));
    }

    /**
     * Activer/désactiver une carte
     */
    public function toggleStatus(Card $card): RedirectResponse
    {
        $card->status = $card->status === 'active' ? 'expired' : 'active';
        $card->save();

        return redirect()
            ->back()
            ->with('success', 'Statut de la carte mis à jour !');
    }
}
