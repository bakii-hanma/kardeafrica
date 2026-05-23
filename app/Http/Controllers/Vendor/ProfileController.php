<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show()
    {
        /** @var \App\Models\Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();

        $stats = [
            'orders_total'      => $reseller->orders()->count(),
            'orders_completed'  => $reseller->orders()->where('status', 'completed')->count(),
            'volume_total'      => (float) $reseller->total_volume,
            'commission_total'  => (float) $reseller->total_commission_earned,
        ];

        $recentTransactions = $reseller->walletTransactions()->take(8)->get();

        return view('vendor.profile', compact('reseller', 'stats', 'recentTransactions'));
    }
}
