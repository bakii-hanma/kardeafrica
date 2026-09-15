<?php

namespace App\Http\Controllers;

use App\Models\MerchantCardPurchase;
use App\Models\Order;
use App\Models\Reseller;
use App\Models\UserCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\OrderDeliveryService;
use App\Services\PaymentRefundService;
use App\Support\RefundPhone;

class OrderController extends Controller
{
    /**
     * Display a listing of the user's orders.
     */
    public function index(Request $request)
    {
        $orders = Order::where('user_id', Auth::id())
            ->with(['orderItems', 'userCards'])
            ->latest()
            ->paginate(10);

        // If API request, return JSON
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $orders,
            ]);
        }

        return view('orders.index', compact('orders'));
    }

    /**
     * Display the specified order with items and cards.
     */
    public function show(Request $request, Order $order)
    {
        // Ensure the user owns the order
        if ($order->user_id !== Auth::id()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Non autorise'], 403);
            }
            abort(403);
        }

        $order->load('orderItems');

        // ============================================================
        // Self-heal : si la commande est payée et contient des items carte
        // locale (Carte Gabon) sans MerchantCardPurchase, on les génère ici
        // (boucle sur chaque item marchand, idempotent). Couvre aussi le cas
        // mixte afrikard + local. Évite que le client doive cliquer 'Relancer'.
        // ============================================================
        if ($order->payment_status === Order::PAYMENT_STATUS_COMPLETED) {
            foreach ($order->orderItems as $item) {
                if (\App\Support\MerchantCardCode::isMerchantOrderItem($item)) {
                    try {
                        \App\Support\MerchantCardCode::createPurchaseForOrderItem($order, $item);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error('OrderController show: self-heal MerchantCardPurchase échoué', [
                            'order_id'      => $order->id,
                            'order_item_id' => $item->id,
                            'error'         => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        // Load associated user cards (afrikard catalog items + miroirs carte locale)
        $userCards = UserCard::where('order_id', $order->id)
            ->where('user_id', Auth::id())
            ->with('orderItem')
            ->get();

        // Load merchant card purchases (Carte Gabon items)
        $merchantPurchases = MerchantCardPurchase::where('order_id', $order->id)
            ->with('merchantCard')
            ->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'order' => $order,
                'cards' => $userCards->makeVisible(['card_code', 'pin']),
                'merchant_purchases' => $merchantPurchases->map(fn ($p) => [
                    'id'           => $p->id,
                    'unique_code'  => $p->unique_code,
                    'amount'       => $p->amount,
                    'merchant'     => 'KardAfrica',
                    'expires_at'   => $p->expires_at,
                    'status'       => $p->status,
                ]),
            ]);
        }

        return view('orders.show', compact('order', 'userCards', 'merchantPurchases'));
    }

    /**
     * Re-essaye la livraison des cartes pour une commande payee mais sans cartes.
     * (cas typique : afrikard a echoue lors du paiement initial)
     */
    public function retryCheckout(Request $request, Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        if ($order->payment_status !== Order::PAYMENT_STATUS_COMPLETED) {
            return back()->with('error', 'Cette commande n\'a pas encore ete payee.');
        }

        $order->load('orderItems');

        // ============================================================
        // 0. Items marchand (Carte Gabon) : création LOCALE en synchrone.
        //    Pas d'appel API, pas de queue worker requis — le job database
        //    n'a aucun worker sur shared hosting.
        // ============================================================
        $merchantItems = $order->orderItems->filter(
            fn ($i) => \App\Support\MerchantCardCode::isMerchantOrderItem($i)
        );
        $afrikardItems = $order->orderItems->reject(
            fn ($i) => \App\Support\MerchantCardCode::isMerchantOrderItem($i)
        );

        foreach ($merchantItems as $item) {
            try {
                \App\Support\MerchantCardCode::createPurchaseForOrderItem($order, $item);
            } catch (\Throwable $e) {
                Log::error('Retry checkout: échec MerchantCardPurchase', [
                    'order_id'      => $order->id,
                    'order_item_id' => $item->id,
                    'product_id'    => $item->product_id,
                    'error'         => $e->getMessage(),
                ]);
            }
        }

        // Si aucun item afrikard, on complète la commande et on renvoie.
        if ($afrikardItems->isEmpty()) {
            $order->update([
                'status'       => Order::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
            return redirect()->route('orders.show', $order)
                ->with('success', 'Cartes marchand livrées !');
        }

        // Au-delà, on poursuit le flow historique pour les items afrikard,
        // via le service commun (garde H4 + récupération asynchrone Bamboo).
        if ($order->userCards()->exists()) {
            return back()->with('error', 'Cette commande a deja ete livree.');
        }

        $result = app(OrderDeliveryService::class)->attempt($order);

        return match ($result['type']) {
            'already_delivered' => back()->with('error', 'Cette commande a deja ete livree.'),
            'not_paid' => back()->with('error', 'Cette commande n\'a pas encore ete payee.'),
            'refunded' => back()->with('error', 'Cette commande est remboursée ou annulée.'),
            'missing' => back()->with('warning',
                'Catalogue fournisseur incomplet (produits ' . implode(', ', $result['missing']) . ' manquants). '
                . 'On a programmé un nouvel essai automatique — patiente quelques minutes puis recharge la page.'),
            'no_afrikard_items' => back()->with('error', 'Aucun item fournisseur à livrer sur cette commande.'),
            'orphan_debited' => back()->with('error', $result['message']),
            'pending' => back()->with('warning',
                ($result['saved'] > 0 ? "{$result['saved']} carte(s) livrée(s). " : '')
                . 'Les codes restants sont en cours de génération côté fournisseur (asynchrone). '
                . 'Recharge la page dans quelques minutes : la récupération est automatique.'),
            'delivered' => redirect()->route('orders.show', $order)->with('success',
                ($result['saved'] > 0 ? "{$result['saved']} cartes livrées avec succès !" : 'Cartes livrées avec succès !')),
            'rejected' => back()->with('error', "Nos serveurs n'ont pas pu finaliser la livraison. Patientez quelques minutes puis réessayez."),
            default => back()->with('error', $result['message'] ?? "Connexion temporairement indisponible. Vos données sont en sécurité — réessayez dans un instant."),
        };
    }

    /**
     * Demande de remboursement par le client. Disponible si :
     * - paiement OK + pas de cartes livrées (échec afrikard)
     * - statut order != deja remboursée
     *
     * Selon payment_method :
     * - 'ebilling'           → appel API transfer.php (rembourse Mobile Money/carte)
     * - 'cash_at_reseller'   → demande au vendeur de rendre le cash physiquement
     *                           (le vendeur valide ensuite via /vendor/cash/{order})
     * - 'simulated'          → annulation locale uniquement
     */
    public function refund(Request $request, Order $order, PaymentRefundService $refundSvc)
    {
        if ($order->user_id !== Auth::id()) abort(403);

        // Gardes rapides (messages précis) — re-testées ensuite DANS le verrou,
        // seules les gardes verrouillées font foi contre un double submit.
        if ($order->userCards()->exists()) {
            return back()->with('error', 'Cette commande a déjà été livrée — impossible de rembourser automatiquement.');
        }
        if ($order->payment_status !== Order::PAYMENT_STATUS_COMPLETED) {
            return back()->with('error', 'Cette commande n\'a pas été payée.');
        }
        if (in_array($order->status, [Order::STATUS_REFUNDED, Order::STATUS_REFUNDING], true)) {
            return back()->with('error', 'Cette commande est déjà remboursée ou un remboursement est en cours.');
        }

        // Numéro de destination du remboursement : numéro du compte par défaut,
        // ou « autre numéro » saisi (nom + détection opérateur Mobile Money).
        $refundPhone = null;
        if ($order->payment_method === 'ebilling') {
            $refundPhone = RefundPhone::resolve($order, $request);
            if ($refundPhone['msisdn'] === null) {
                return back()->with('error', 'Aucun numéro de remboursement valide. Choisis « numéro du compte » ou renseigne un autre numéro Mobile Money.');
            }
        }

        // Cash chez vendeur : on ne peut pas rembourser via API, le vendeur doit
        // rendre l'argent en physique. On informe le client. Tout est local →
        // une seule transaction verrouillée suffit (pas d'appel PSP).
        if ($order->payment_method === Order::PAYMENT_METHOD_CASH_RESELLER) {
            try {
                $already = DB::transaction(function () use ($order) {
                    // Verrou de la commande + re-test des gardes DANS le verrou
                    $locked = Order::where('id', $order->id)->lockForUpdate()->first();
                    if ($locked->payment_status !== Order::PAYMENT_STATUS_COMPLETED
                        || in_array($locked->status, [Order::STATUS_REFUNDED, Order::STATUS_REFUNDING], true)
                        || $locked->userCards()->exists()) {
                        return true;
                    }

                    // Restitue le wallet du vendeur (les cartes n'ont pas été livrées)
                    $reseller = Reseller::lockForUpdate()->find($locked->cash_reseller_id);
                    if ($reseller) {
                        $reseller->refundCredit((float) $locked->total_amount, "Remboursement commande #{$locked->order_number}", $locked->order_number);
                    }
                    $locked->update([
                        'status'         => Order::STATUS_REFUNDED,
                        'payment_status' => Order::PAYMENT_STATUS_REFUNDED,
                        'notes'          => 'Remboursée — le vendeur doit rendre le cash au client',
                    ]);
                    return false;
                });

                if ($already) {
                    return back()->with('error', 'Cette commande est déjà remboursée ou un remboursement est en cours.');
                }
                return back()->with('success', 'Remboursement enregistré. Va voir le vendeur Kardafrica pour récupérer ton argent en cash.');
            } catch (\Throwable $e) {
                return back()->with('error', 'Erreur : ' . $e->getMessage());
            }
        }

        // ============================================================
        // Machine à états anti double-virement (H5) :
        // 1. transaction {verrou + re-test des gardes + état 'refunding'}
        // 2. virement E-Billing HORS transaction
        // 3. transaction {statut REFUNDED}
        // ============================================================

        // Transaction 1 : réserve le remboursement. Un double submit concurrent
        // trouve la commande en 'refunding' (ou 'refunded') et sort proprement.
        $previousStatus = null;
        try {
            $claimed = DB::transaction(function () use ($order, &$previousStatus) {
                $locked = Order::where('id', $order->id)->lockForUpdate()->first();

                if ($locked->payment_status !== Order::PAYMENT_STATUS_COMPLETED
                    || in_array($locked->status, [Order::STATUS_REFUNDED, Order::STATUS_REFUNDING], true)
                    || $locked->userCards()->exists()) {
                    return false;
                }

                $previousStatus = $locked->status;
                $locked->update(['status' => Order::STATUS_REFUNDING]);
                return true;
            });
        } catch (\Throwable $e) {
            Log::error('Customer refund claim exception', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }

        if (!$claimed) {
            return back()->with('error', 'Cette commande est déjà remboursée ou un remboursement est en cours.');
        }

        // E-Billing : appel transfer — HORS transaction (appel HTTP externe).
        // La référence de transfert est déterministe (REFUND_<ref d'origine>),
        // le PSP peut donc dédupliquer un éventuel double appel.
        if ($order->payment_method === 'ebilling') {
            $result = $refundSvc->refund(
                originalReference: $order->external_reference,
                amountFcfa: (int) round($order->total_amount),
                reason: "Remboursement commande {$order->order_number}",
                extras: [
                    'msisdn' => $refundPhone['msisdn'],
                    'name'   => data_get($order->billing_details, 'name') ?? optional($order->user)->name,
                    'email'  => data_get($order->billing_details, 'email') ?? optional($order->user)->email,
                ],
            );
            if (!$result['ok']) {
                // Échec du virement → retour à l'état antérieur pour permettre un retry
                Order::where('id', $order->id)->update(['status' => $previousStatus]);
                return back()->with('error', 'Remboursement E-Billing refusé : ' . $result['message']);
            }
        }

        // Transaction 2 : clôture du remboursement
        try {
            DB::transaction(function () use ($order) {
                $order->update([
                    'status'         => Order::STATUS_REFUNDED,
                    'payment_status' => Order::PAYMENT_STATUS_REFUNDED,
                    'notes'          => 'Remboursée via E-Billing transfer',
                ]);
            });
            return back()->with('success', 'Remboursement effectué — l\'argent te sera renvoyé sur ton moyen de paiement.');
        } catch (\Throwable $e) {
            Log::error('Customer refund exception', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            // Retour à l'état antérieur : le retry est sûr même côté E-Billing
            // grâce à la référence de transfert déterministe (déduplication PSP).
            Order::where('id', $order->id)->update(['status' => $previousStatus]);
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }
}
