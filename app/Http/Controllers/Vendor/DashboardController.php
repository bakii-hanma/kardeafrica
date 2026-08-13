<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Support\VendorStats;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();

        // Tous les chiffres viennent de VendorStats : volume livré uniquement,
        // ventes de Cartes Gabon incluses, alertes actionnables. Voir la classe
        // pour les définitions retenues.
        $stats = VendorStats::for($reseller)->dashboard();

        $recentOrders = $reseller->orders()->with('items')->latest()->take(5)->get();

        return view('vendor.dashboard', compact('reseller', 'stats', 'recentOrders'));
    }
}
