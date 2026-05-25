<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Vendor\MerchantCardController;
use App\Models\MerchantCard;
use App\Models\Reseller;
use Illuminate\Http\Request;

/**
 * GabonController
 * ===
 * Marketplace public des cartes-cadeau MARCHAND (= cartes créées par les
 * vendors approuvés KYC dans /vendor/cartes-cadeau).
 *
 * URLs :
 *   /gabon                          → landing
 *   /gabon/categorie/{slug}         → filtre par catégorie
 *   /gabon/marchand/{slug}          → profil d'un marchand + ses cartes
 *   /gabon/carte/{merchantCard}     → détail d'une carte + bouton acheter
 *
 * Toutes ces pages sont publiques (pas d'auth). Le bouton "Acheter" mène à
 * Phase 4 (futursowax) — pour l'instant route placeholder.
 */
class GabonController extends Controller
{
    /** Landing /gabon — hero + marchands en vedette + grille de cartes */
    public function index(Request $request)
    {
        $query = MerchantCard::active()
            ->with('reseller:id,name,business_name,slug,city,logo_url')
            ->latest('activated_at');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('reseller', fn ($r) => $r->where('business_name', 'like', "%{$search}%"));
            });
        }

        if ($city = trim((string) $request->query('city', ''))) {
            $query->whereHas('reseller', fn ($r) => $r->where('city', $city));
        }

        $cards = $query->paginate(24)->withQueryString();

        $featuredMerchants = Reseller::where('kyc_status', 'approved')
            ->where('is_active', true)
            ->whereHas('merchantCards', fn ($q) => $q->where('is_active', true))
            ->withCount(['merchantCards' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('merchant_cards_count', 'desc')
            ->take(6)
            ->get();

        $cities = Reseller::where('kyc_status', 'approved')
            ->whereNotNull('city')
            ->whereHas('merchantCards', fn ($q) => $q->where('is_active', true))
            ->select('city')->distinct()->orderBy('city')->pluck('city');

        return view('gabon.index', [
            'cards'             => $cards,
            'featuredMerchants' => $featuredMerchants,
            'cities'            => $cities,
            'categories'        => MerchantCardController::CATEGORIES,
            'currentSearch'     => $search,
            'currentCity'       => $city,
        ]);
    }

    /** /gabon/categorie/{slug} — toutes les cartes d'une catégorie */
    public function category(Request $request, string $slug)
    {
        abort_unless(array_key_exists($slug, MerchantCardController::CATEGORIES), 404);

        // Filtre via la relation reseller.business_type (= "catégorie du marchand")
        // mais aussi via merchant_card.category (= catégorie spécifique à la carte)
        $cards = MerchantCard::active()
            ->where(function ($q) use ($slug) {
                $q->where('category', $slug)
                  ->orWhereHas('reseller', fn ($r) => $r->where('business_type', $slug));
            })
            ->with('reseller:id,name,business_name,slug,city,logo_url')
            ->latest('activated_at')
            ->paginate(24)
            ->withQueryString();

        return view('gabon.category', [
            'cards'        => $cards,
            'categorySlug' => $slug,
            'categoryName' => MerchantCardController::CATEGORIES[$slug],
            'categories'   => MerchantCardController::CATEGORIES,
        ]);
    }

    /** /gabon/marchand/{slug} — profil + cartes du marchand */
    public function merchant(string $slug)
    {
        $merchant = Reseller::where('slug', $slug)
            ->where('kyc_status', 'approved')
            ->where('is_active', true)
            ->firstOrFail();

        $cards = $merchant->merchantCards()
            ->where('is_active', true)
            ->latest('activated_at')
            ->get();

        return view('gabon.merchant', [
            'merchant'   => $merchant,
            'cards'      => $cards,
            'categories' => MerchantCardController::CATEGORIES,
        ]);
    }

    /** /gabon/carte/{merchantCard} — détail + bouton acheter */
    public function card(MerchantCard $merchantCard)
    {
        // Seules les cartes actives d'un marchand approuvé sont publiquement consultables
        abort_unless(
            $merchantCard->is_active
            && $merchantCard->reseller
            && $merchantCard->reseller->kyc_status === 'approved'
            && $merchantCard->reseller->is_active,
            404
        );

        $merchantCard->load('reseller');

        // Suggestions : autres cartes du même marchand
        $otherCards = $merchantCard->reseller->merchantCards()
            ->where('is_active', true)
            ->where('id', '!=', $merchantCard->id)
            ->take(4)
            ->get();

        return view('gabon.card', [
            'card'       => $merchantCard,
            'merchant'   => $merchantCard->reseller,
            'otherCards' => $otherCards,
            'categories' => MerchantCardController::CATEGORIES,
        ]);
    }
}
