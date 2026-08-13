<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppNotifier;

/**
 * Notifications WhatsApp liées au cycle de vie d'une commande (Phase 1).
 *
 * Se branche sur la transition `status → completed` (quel que soit le chemin :
 * ProcessCheckoutJob, checkout inline, retry, self-heal), ce qui garantit que
 * les codes ont déjà été générés au moment de l'envoi. Le dédoublonnage
 * (`order-ready-{id}`) assure UN seul message même si la commande est
 * re-sauvegardée plusieurs fois.
 *
 * ⚠️ Le message ne contient JAMAIS le code/PIN : il pointe vers l'espace client
 * (page protégée par authentification).
 */
class OrderObserver
{
    public function updated(Order $order): void
    {
        if (!$order->wasChanged('status') || $order->status !== Order::STATUS_COMPLETED) {
            return;
        }

        $this->notifyReady($order);
    }

    private function notifyReady(Order $order): void
    {
        $phone = data_get($order->billing_details, 'phone') ?: optional($order->user)->phone;
        if (empty($phone)) {
            return; // pas de numéro → pas de notification WhatsApp (silencieux)
        }

        $link = route('orders.show', $order);
        $body = "🎉 *KardAfrica* — votre commande {$order->order_number} est prête !\n\n"
              . "Vos codes vous attendent dans votre espace client (connexion requise) :\n{$link}\n\n"
              . "Une question ? Répondez simplement à ce message. 💬";

        app(WhatsAppNotifier::class)->text($phone, $body, [
            'category'  => WhatsAppMessage::CAT_TRANSACTIONAL,
            'dedup_key' => "order-ready-{$order->id}",
            'context'   => ['order', $order->id],
        ]);
    }
}
