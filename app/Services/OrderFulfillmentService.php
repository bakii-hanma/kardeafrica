<?php

namespace App\Services;

use App\Jobs\ProcessCheckoutJob;
use App\Models\Order;
use App\Models\Payment;
use App\Support\MerchantCardCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bascule une commande PAYÉE vers son état de traitement et déclenche la
 * livraison de ses items.
 *
 * Extrait de PaymentController::finalize pour être réutilisé par la
 * réconciliation des commandes restées en `processing`
 * (orders:reconcile-payments) : la finalisation web/mobile et la
 * réconciliation doivent produire EXACTEMENT le même résultat.
 */
class OrderFulfillmentService
{
    /**
     * Marque la commande PAYÉE et son Payment associé.
     *
     * Idempotent et concurrent-safe : le statut est relu sous verrou ; si une
     * requête parallèle a déjà finalisé, on renvoie false sans rien changer.
     *
     * @return bool true si la transition a été effectuée par CET appel.
     */
    public function markPaid(Order $order, string $externalRef): bool
    {
        return DB::transaction(function () use ($order, $externalRef) {
            $fresh = Order::whereKey($order->id)->lockForUpdate()->first();
            if ($fresh->payment_status === Order::PAYMENT_STATUS_COMPLETED) {
                return false;
            }

            $fresh->update([
                'payment_status' => Order::PAYMENT_STATUS_COMPLETED,
                'status'         => Order::STATUS_PROCESSING,
            ]);

            $payment = Payment::where('transaction_id', $externalRef)->lockForUpdate()->first();
            if ($payment) {
                $payment->update([
                    'order_id'     => $fresh->id,
                    'status'       => Payment::STATUS_COMPLETED,
                    'processed_at' => now(),
                ]);
            } else {
                // Cas limite : commande legacy sans intention de paiement tracée.
                Payment::create([
                    'transaction_id'          => $externalRef,
                    'order_id'                => $fresh->id,
                    'user_id'                 => $fresh->user_id,
                    'payment_method'          => 'ebilling',
                    'provider'                => 'futursowax',
                    'amount'                  => $fresh->total_amount,
                    'currency'                => $fresh->currency ?? 'XAF',
                    'status'                  => Payment::STATUS_COMPLETED,
                    'external_transaction_id' => $externalRef,
                    'processed_at'            => now(),
                ]);
            }

            return true;
        });
    }

    /**
     * Déclenche la livraison des items d'une commande PAYÉE :
     *  - items marchand (Carte Gabon) : génération LOCALE synchrone (idempotente),
     *  - items afrikard/daywatch : dispatch de ProcessCheckoutJob (anti double
     *    débit garanti par delivery_requested_at),
     *  - commande 100 % marchand : complétée inline.
     *
     * Un échec marchand ne bloque pas les items afrikard (et inversement).
     */
    public function deliver(Order $order): void
    {
        $order->load('orderItems');

        $merchantItems = $order->orderItems->filter(
            fn ($i) => MerchantCardCode::isMerchantOrderItem($i)
        );
        foreach ($merchantItems as $item) {
            try {
                MerchantCardCode::createPurchaseForOrderItem($order, $item);
            } catch (\Throwable $e) {
                Log::error('OrderFulfillment: échec MerchantCardPurchase', [
                    'order_id'      => $order->id,
                    'order_item_id' => $item->id,
                    'product_id'    => $item->product_id,
                    'error'         => $e->getMessage(),
                ]);
            }
        }

        $afrikardItems = $order->orderItems->reject(
            fn ($i) => MerchantCardCode::isMerchantOrderItem($i)
        );
        if ($afrikardItems->isNotEmpty()) {
            ProcessCheckoutJob::dispatch($order);
            Log::info('OrderFulfillment: checkout job dispatched (items afrikard)', ['order_id' => $order->id]);
        } else {
            $order->update([
                'status'       => Order::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
            Log::info('OrderFulfillment: commande 100% marchand complétée inline', ['order_id' => $order->id]);
        }
    }
}
