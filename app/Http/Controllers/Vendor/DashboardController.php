<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\ResellerOrder;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();

        $stats = [
            'orders_total'     => $reseller->orders()->count(),
            'orders_today'     => $reseller->orders()->whereDate('created_at', today())->count(),
            'orders_pending'   => $reseller->orders()->where('status', ResellerOrder::STATUS_PROCESSING)->count(),
            'volume_total'     => (float) $reseller->orders()->where('status', ResellerOrder::STATUS_COMPLETED)->sum('total_amount'),
            'volume_today'     => (float) $reseller->orders()->whereDate('created_at', today())->where('status', ResellerOrder::STATUS_COMPLETED)->sum('total_amount'),
            'commission_today' => (float) $reseller->orders()->whereDate('created_at', today())->sum('commission_earned'),
        ];

        $recentOrders = $reseller->orders()->with('items')->latest()->take(5)->get();

        return view('vendor.dashboard', compact('reseller', 'stats', 'recentOrders'));
    }
}
