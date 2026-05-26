<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Vendor\MerchantCardController as VendorMerchantCardController;
use App\Models\MerchantCard;
use Illuminate\Http\Request;

/**
 * Admin\MerchantCardController
 * ===
 * Modération des cartes-cadeau créées par les marchands (Phase 6).
 *
 * Workflow :
 *  - Vendor crée → is_active=false (visible uniquement dans son espace)
 *  - Admin valide → is_active=true, activated_at=now() → carte publique sur /gabon
 *  - Admin refuse → is_active=false, rejection_reason="..." (vendor voit le motif)
 */
class MerchantCardController extends Controller
{
    /** Liste avec stats + filtres */
    public function index(Request $request)
    {
        $status = $request->query('status', ''); // '', 'pending', 'active', 'rejected'
        $search = trim((string) $request->query('search', ''));

        $query = MerchantCard::with('reseller:id,name,business_name,slug,city,vendor_code')
            ->withCount('purchases');

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
                  ->orWhereHas('reseller', function ($r) use ($search) {
                      $r->where('business_name', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('vendor_code', 'like', "%{$search}%");
                  });
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
            'categories' => VendorMerchantCardController::CATEGORIES,
            'status'     => $status,
            'search'     => $search,
        ]);
    }

    /** Détail + form approve/reject */
    public function show(MerchantCard $merchantCard)
    {
        $merchantCard->load('reseller', 'purchases');

        return view('admin.merchant-cards.show', [
            'card'       => $merchantCard,
            'categories' => VendorMerchantCardController::CATEGORIES,
        ]);
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
}
