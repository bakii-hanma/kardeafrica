<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Vendor\SaleController as VendorSaleController;
use App\Models\Reseller;
use App\Models\ResellerCard;
use App\Models\ResellerOrder;
use App\Services\PaymentRefundService;
use App\Support\RefundPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Gestion admin des commandes vendeur (ResellerOrder) et de leurs cartes.
 *
 * Permet à un admin/gérant de :
 * - voir le détail d'une commande qui a échoué côté livraison
 * - relancer la livraison via afrikard
 * - rembourser le vendeur (et le client par ricochet pour les ventes E-Billing)
 * - INJECTER MANUELLEMENT des codes carte (pour le cas où afrikard est
 *   définitivement HS et qu'on a un stock interne à utiliser)
 */
class VendorOrderController extends Controller
{
    public function show(Reseller $reseller, ResellerOrder $order)
    {
        if ($order->reseller_id !== $reseller->id) abort(404);
        $order->load('items', 'cards');

        return view('admin.resellers.orders.show', [
            'reseller' => $reseller,
            'order'    => $order,
        ]);
    }

    /**
     * Relance la livraison via afrikard (réutilise tryDeliver du vendor controller).
     */
    public function retryDelivery(Reseller $reseller, ResellerOrder $order, VendorSaleController $vendorCtrl)
    {
        if ($order->reseller_id !== $reseller->id) abort(404);

        if ($order->payment_status !== ResellerOrder::PAYMENT_COMPLETED) {
            return back()->with('error', 'Commande non payée.');
        }
        if ($order->cards()->count() > 0) {
            return back()->with('error', 'Cartes déjà livrées.');
        }

        $order->load('items');
        $vendorCtrl->tryDeliver($order);
        $order->refresh();

        if ($order->cards()->count() > 0) {
            return back()->with('success', 'Livraison réussie — ' . $order->cards()->count() . ' carte(s) générée(s).');
        }
        return back()->with('error', $order->notes ?: 'Livraison échouée.');
    }

    /**
     * Remboursement admin de la commande : selon le mode de paiement, appelle
     * E-Billing transfer (cash → wallet vendeur restauré + cash_to_remit ajusté).
     */
    public function refund(Request $request, Reseller $reseller, ResellerOrder $order, PaymentRefundService $refundSvc)
    {
        if ($order->reseller_id !== $reseller->id) abort(404);

        if ($order->payment_status !== ResellerOrder::PAYMENT_COMPLETED) {
            return back()->with('error', 'Commande non payée.');
        }
        if ($order->cards()->count() > 0) {
            return back()->with('error', 'Cartes déjà livrées — impossible de rembourser automatiquement.');
        }
        if (in_array($order->status, [ResellerOrder::STATUS_REFUNDING, ResellerOrder::STATUS_REFUNDED], true)) {
            return back()->with('error', 'Cette commande est déjà remboursée ou un remboursement est en cours.');
        }

        // Numéro de destination du remboursement : numéro du client de la vente
        // par défaut, ou « autre numéro » saisi par l'admin. Lecture seule
        // avant le verrou (le numéro ne change pas pendant le virement).
        $refundPhone = null;
        if ($order->payment_method === 'ebilling') {
            $refundPhone = RefundPhone::resolveReseller($order, $request);
            if ($refundPhone['msisdn'] === null) {
                return back()->with('error', 'Aucun numéro de remboursement valide. Renseigne le numéro du compte du client ou un autre numéro Mobile Money.');
            }
        }

        // Machine à états anti double-virement (H5) :
        // 1. transaction {verrou + re-test des gardes + tombstone 'refunding'}
        // 2. virement E-Billing HORS transaction (réf. déterministe → PSP déduplique)
        // 3. transaction {verrou + crédits wallet + statut REFUNDED}

        $previousStatus = null;
        try {
            $claimed = DB::transaction(function () use ($order, &$previousStatus) {
                $locked = ResellerOrder::where('id', $order->id)->lockForUpdate()->first();

                if ($locked->payment_status !== ResellerOrder::PAYMENT_COMPLETED
                    || in_array($locked->status, [ResellerOrder::STATUS_REFUNDING, ResellerOrder::STATUS_REFUNDED], true)
                    || $locked->cards()->count() > 0) {
                    return false;
                }

                $previousStatus = $locked->status;
                $locked->update(['status' => ResellerOrder::STATUS_REFUNDING]);
                return true;
            });
        } catch (\Throwable $e) {
            Log::error('Admin vendor refund claim exception', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }

        if (!$claimed) {
            return back()->with('error', 'Cette commande est déjà remboursée ou un remboursement est en cours.');
        }

        // E-Billing : virement HORS transaction. La référence de transfert
        // est déterministe (REFUND_<ref d'origine>), le PSP déduplique
        // un éventuel double appel.
        if ($order->payment_method === 'ebilling') {
            $result = $refundSvc->refund(
                originalReference: $order->external_reference,
                amountFcfa: (int) round($order->total_amount),
                reason: "Refund admin {$order->order_number}",
                extras: [
                    'msisdn' => $refundPhone['msisdn'],
                    'name'   => $order->customer_name,
                ],
            );
            if (!$result['ok']) {
                // Échec du virement → retour à l'état antérieur pour permettre un retry
                ResellerOrder::where('id', $order->id)->update(['status' => $previousStatus]);
                return back()->with('error', 'Remboursement E-Billing refusé : ' . $result['message']);
            }
        }

        // Transaction 2 : crédits wallet + clôture, sous verrou
        try {
            DB::transaction(function () use ($reseller, $order) {
                $locked = ResellerOrder::where('id', $order->id)->lockForUpdate()->first();

                $subtotal   = (float) $locked->subtotal;
                $commission = (float) $locked->commission_earned;

                // Restaure le wallet (les cartes n'ont pas été livrées)
                // refundCredit : une restitution ne doit pas buter sur le plafond wallet.
                $reseller->refundCredit($subtotal, "Remboursement admin #{$locked->order_number}", $locked->order_number);
                if ($commission > 0) {
                    $reseller->commission(-$commission, "Annulation commission admin #{$locked->order_number}", $locked->order_number);
                }
                // Si vente cash, le vendeur va devoir rendre le cash → décrémente cash_to_remit
                if ($locked->payment_method === 'cash') {
                    $fresh = Reseller::lockForUpdate()->find($reseller->id);
                    $fresh->cash_to_remit = max(0, (float) $fresh->cash_to_remit - $subtotal);
                    $fresh->save();
                }

                $locked->update([
                    'status'         => ResellerOrder::STATUS_REFUNDED,
                    'payment_status' => ResellerOrder::PAYMENT_REFUNDED,
                    'notes'          => 'Remboursée par admin (' . $locked->payment_method . ')',
                ]);
            });

            return back()->with('success', 'Commande remboursée — wallet vendeur restauré.');
        } catch (\Throwable $e) {
            Log::error('Admin vendor refund exception', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            // Retour à l'état antérieur : le retry est sûr même côté E-Billing
            // grâce à la référence de transfert déterministe (déduplication PSP).
            ResellerOrder::where('id', $order->id)->update([
                'status' => $previousStatus,
                'notes'  => 'Échec finalisation remboursement admin — à réessayer : ' . $e->getMessage(),
            ]);
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Injection manuelle de codes carte. Quand afrikard est HS pour de bon,
     * l'admin peut entrer les codes d'un stock interne ou récupérer manuellement
     * la commande pour le client.
     *
     * Format attendu : un bloc cartes par item, chaque carte = card_code + pin.
     */
    public function injectCards(Request $request, Reseller $reseller, ResellerOrder $order)
    {
        if ($order->reseller_id !== $reseller->id) abort(404);

        if ($order->cards()->count() > 0) {
            return back()->with('error', 'Cette commande a déjà des cartes — utilise « Voir » pour les consulter.');
        }
        if ($order->payment_status !== ResellerOrder::PAYMENT_COMPLETED) {
            return back()->with('error', 'Commande non payée.');
        }

        $request->validate([
            'cards'                       => 'required|array|min:1',
            'cards.*.reseller_order_item_id' => 'required|integer|exists:reseller_order_items,id',
            'cards.*.card_code'           => 'required|string|max:255',
            'cards.*.pin'                 => 'nullable|string|max:64',
            'cards.*.expiration_date'     => 'nullable|date',
            'cards.*.face_value'          => 'nullable|numeric|min:0',
        ]);

        $order->load('items');
        $itemMap = $order->items->keyBy('id');

        try {
            DB::transaction(function () use ($order, $request, $itemMap) {
                foreach ($request->input('cards') as $cardData) {
                    $item = $itemMap->get($cardData['reseller_order_item_id']);
                    if (!$item || $item->reseller_order_id !== $order->id) continue;

                    ResellerCard::create([
                        'reseller_order_id'      => $order->id,
                        'reseller_order_item_id' => $item->id,
                        'product_id'             => $item->product_id,
                        'name'                   => $item->name,
                        'brand'                  => $item->brand,
                        'card_code'              => trim($cardData['card_code']),
                        'pin'                    => isset($cardData['pin']) ? trim($cardData['pin']) : null,
                        'expiration_date'        => $cardData['expiration_date'] ?? null,
                        'face_value'             => $cardData['face_value'] ?? $item->unit_price,
                        'currency'               => 'XAF',
                        'status'                 => ResellerCard::STATUS_ACTIVE,
                        'image_url'              => $item->image_url,
                        'metadata'                => [
                            'source'              => 'manual_admin_injection',
                            'injected_by_admin'   => auth()->id(),
                            'injected_at'         => now()->toIso8601String(),
                        ],
                    ]);
                }

                $order->update([
                    'status'       => ResellerOrder::STATUS_COMPLETED,
                    'completed_at' => now(),
                    'notes'        => 'Cartes injectées manuellement par l\'admin (afrikard inutilisable)',
                ]);
            });

            return back()->with('success', 'Cartes injectées manuellement — la commande est livrée. Le client peut récupérer ses cartes via le QR code.');
        } catch (\Throwable $e) {
            Log::error('Admin manual card injection', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }
}
