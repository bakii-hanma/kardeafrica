<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\UserCard;
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function index(Request $request)
    {
        $query = UserCard::with(['user', 'order', 'orderItem'])->latest();

        // Filtre par utilisateur (depuis le bouton "Ses cartes" sur une carte)
        $filteredUser = null;
        if ($request->filled('user_id')) {
            $filteredUser = User::find($request->user_id);
            if ($filteredUser) {
                $query->where('user_id', $filteredUser->id);
            }
        }

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtre par marque
        if ($request->filled('brand')) {
            $query->where('brand', 'like', '%' . $request->brand . '%');
        }

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filtre par date
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $cards = $query->paginate(20)->withQueryString();

        // Pré-calcul du nombre de cartes par user pour les badges "Ses cartes (N)"
        // (évite le N+1 dans la boucle de la vue)
        $userCardCounts = UserCard::whereIn('user_id', $cards->pluck('user_id')->filter()->unique())
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        // Statistiques rapides
        $totalCards    = UserCard::count();
        $activeCards   = UserCard::where('status', 'active')->count();
        $usedCards     = UserCard::where('status', 'used')->count();
        $expiredCards  = UserCard::where('status', 'expired')->count();
        // Total dépensé en FCFA (basé sur le orderItem.unit_price = prix payé)
        $totalRevenue  = UserCard::with('orderItem')->get()
            ->sum(fn($c) => (float) ($c->orderItem?->unit_price ?? 0));

        // Commandes payees mais sans cartes (afrikard a echoue) - pour bouton retry admin
        $stuckOrders = Order::where('payment_status', Order::PAYMENT_STATUS_COMPLETED)
            ->whereDoesntHave('userCards')
            ->with(['user', 'orderItems'])
            ->latest()
            ->get();

        return view('admin.cards.index', compact(
            'cards', 'totalCards', 'activeCards', 'usedCards', 'expiredCards', 'totalRevenue', 'stuckOrders',
            'filteredUser', 'userCardCounts'
        ));
    }
}
