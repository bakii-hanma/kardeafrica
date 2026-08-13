<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\MerchantCard;
use App\Models\MerchantCardPurchase;
use App\Models\MerchantCardRedemption;
use App\Models\MerchantSettlement;
use App\Support\OwnerEarnings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Dashboard du propriétaire (Carte Gabon). Vue d'ensemble de ses cartes,
 * achats clients et historique de redemptions au comptoir.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $owner = Auth::guard('card_owner')->user();

        // Tous les chiffres viennent d'OwnerEarnings, qui sépare enfin trois
        // choses que l'écran confondait : ce que les CLIENTS ont payé, ce que
        // KardAfrica DOIT au commerçant (au prorata de ce qu'il a réellement
        // servi), et la marchandise qu'il doit ENCORE servir.
        $stats = OwnerEarnings::for($owner)->dashboard();

        $recentRedemptions = MerchantCardRedemption::whereHas('purchase', fn ($q) =>
                $q->whereIn('merchant_card_id', $owner->cards()->pluck('id')))
            ->with(['purchase.merchantCard:id,name'])
            ->latest('redeemed_at')
            ->take(6)
            ->get();

        $settlements = MerchantSettlement::where('card_owner_id', $owner->id)
            ->latest('settled_at')
            ->take(5)
            ->get();

        return view('owner.dashboard', [
            'owner'             => $owner,
            'stats'             => $stats,
            'recentRedemptions' => $recentRedemptions,
            'settlements'       => $settlements,
        ]);
    }

    public function cards(Request $request)
    {
        $owner  = Auth::guard('card_owner')->user();
        $status = (string) $request->query('status', '');

        $query = $owner->cards()->withCount(['purchases as purchases_paid_count' => function ($q) {
            $q->where('payment_status', MerchantCardPurchase::PAYMENT_PAID);
        }]);

        if ($status === 'active')   $query->where('is_active', true);
        if ($status === 'inactive') $query->where('is_active', false);

        $cards = $query->latest('created_at')->paginate(20)->withQueryString();

        return view('owner.cards.index', [
            'owner'  => $owner,
            'cards'  => $cards,
            'status' => $status,
        ]);
    }

    public function cardShow(MerchantCard $merchantCard)
    {
        $owner = Auth::guard('card_owner')->user();
        abort_unless($merchantCard->card_owner_id === $owner->id, 404);

        $purchases = $merchantCard->purchases()
            ->where('payment_status', MerchantCardPurchase::PAYMENT_PAID)
            ->latest('paid_at')
            ->paginate(20);

        return view('owner.cards.show', [
            'owner'     => $owner,
            'card'      => $merchantCard,
            'purchases' => $purchases,
        ]);
    }

    public function history(Request $request)
    {
        $owner   = Auth::guard('card_owner')->user();
        $cardIds = $owner->cards()->pluck('id');

        $redemptions = MerchantCardRedemption::whereHas('purchase', fn ($q) =>
                $q->whereIn('merchant_card_id', $cardIds))
            ->with(['purchase.merchantCard:id,name'])
            ->latest('redeemed_at')
            ->paginate(30);

        return view('owner.history', [
            'owner'       => $owner,
            'redemptions' => $redemptions,
        ]);
    }
}
