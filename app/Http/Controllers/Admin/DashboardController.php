<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserCard;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Stats globales
        $totalRevenue = Order::where('payment_status', Order::PAYMENT_STATUS_COMPLETED)->sum('total_amount');
        $totalOrders = Order::count();
        $todayOrders = Order::whereDate('created_at', today())->count();
        $totalUsers = User::count();
        $activeUsers = User::active()->count();
        $totalCards = UserCard::count();
        $activeCards = UserCard::where('status', 'active')->count();

        // Commandes qui necessitent attention
        $pendingOrders = Order::whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PROCESSING])->count();

        // Revenus du mois
        $monthlyRevenue = Order::where('payment_status', Order::PAYMENT_STATUS_COMPLETED)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');

        // 10 dernieres commandes
        $recentOrders = Order::with('user')
            ->latest()
            ->take(10)
            ->get();

        // 5 derniers paiements
        $recentPayments = Payment::with(['user', 'order'])
            ->latest()
            ->take(5)
            ->get();

        // 8 dernieres cartes livrees (pour la grille design carte)
        $recentCards = UserCard::with(['user', 'orderItem'])
            ->latest()
            ->take(8)
            ->get();

        return view('admin.dashboard', compact(
            'totalRevenue',
            'totalOrders',
            'todayOrders',
            'totalUsers',
            'activeUsers',
            'totalCards',
            'activeCards',
            'pendingOrders',
            'monthlyRevenue',
            'recentOrders',
            'recentPayments',
            'recentCards'
        ));
    }
}
