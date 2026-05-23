<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Order;
use App\Models\UserCard;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class CardController extends Controller
{
    /**
     * Afficher la liste des cartes achetees par l'utilisateur (UserCard).
     * Filtres acceptes : search, status, sort.
     */
    public function index(Request $request): View
    {
        $query = UserCard::where('user_id', Auth::id())
            ->with(['orderItem', 'order']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        if (in_array($status = $request->get('status'), ['active', 'used', 'expired'])) {
            $query->where('status', $status);
        }

        switch ($request->get('sort', 'latest')) {
            case 'oldest':     $query->oldest(); break;
            case 'price_asc':  $query->orderBy('face_value', 'asc'); break;
            case 'price_desc': $query->orderBy('face_value', 'desc'); break;
            case 'latest':
            default:           $query->latest(); break;
        }

        $cards = $query->paginate(12)->withQueryString();

        // Stats globales (sans pagination) — utilise le vrai prix paye en FCFA via orderItem
        $allUserCards = UserCard::where('user_id', Auth::id())->with('orderItem')->get();
        $stats = [
            'total'     => $allUserCards->count(),
            'active'    => $allUserCards->where('status', 'active')->count(),
            'used'      => $allUserCards->where('status', 'used')->count(),
            'value_xaf' => $allUserCards->sum(fn($c) => (float) ($c->orderItem?->unit_price ?? 0)),
        ];

        // Commandes payees mais sans cartes (afrikard a echoue lors du paiement)
        $pendingOrders = Order::where('user_id', Auth::id())
            ->where('payment_status', Order::PAYMENT_STATUS_COMPLETED)
            ->whereDoesntHave('userCards')
            ->with('orderItems')
            ->latest()
            ->get();

        return view('cards.index', compact('cards', 'stats', 'pendingOrders'));
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
        if ($card->user_id !== Auth::id()) {
            abort(403);
        }
        return view('cards.show', compact('card'));
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit(Card $card): View
    {
        if ($card->user_id !== Auth::id()) {
            abort(403);
        }
        return view('cards.edit', compact('card'));
    }

    /**
     * Mettre à jour une carte
     */
    public function update(Request $request, Card $card): RedirectResponse
    {
        if ($card->user_id !== Auth::id()) {
            abort(403);
        }
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
        if ($card->user_id !== Auth::id()) {
            abort(403);
        }
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
        
        $cards = Card::where('user_id', Auth::id())
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%")
                  ->orWhere('brand', 'like', "%{$query}%")
                  ->orWhere('type', 'like', "%{$query}%");
            })
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
        $cards = Card::where('user_id', Auth::id())
            ->where('type', $type)
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
        if ($card->user_id !== Auth::id()) {
            abort(403);
        }
        $card->status = $card->status === 'active' ? 'expired' : 'active';
        $card->save();

        return redirect()
            ->back()
            ->with('success', 'Statut de la carte mis à jour !');
    }
}
