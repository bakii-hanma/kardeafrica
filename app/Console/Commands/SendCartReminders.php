<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\WhatsAppNotifier;
use App\Models\WhatsAppMessage;
use Illuminate\Console\Command;

/**
 * Relance WhatsApp des paniers abandonnés : commandes créées mais non payées,
 * dans la fenêtre [min_age, max_age] (config/whatsapp.php). Une seule relance
 * par commande (dedup_key cart-reminder-{id}).
 *
 * Message de SERVICE lié à la commande du client (catégorie transactional) — il
 * concerne son propre achat en cours, pas une promotion.
 *
 * À schedule toutes les heures (routes/console.php).
 */
class SendCartReminders extends Command
{
    protected $signature = 'whatsapp:cart-reminders';
    protected $description = 'Relance WhatsApp les paniers abandonnés (commandes non payées).';

    public function handle(WhatsAppNotifier $notifier): int
    {
        if (!config('whatsapp.reminders.cart.enabled', true)) {
            $this->info('Relances panier désactivées.');
            return self::SUCCESS;
        }

        $minAge = (int) config('whatsapp.reminders.cart.min_age_minutes', 120);
        $maxAge = (int) config('whatsapp.reminders.cart.max_age_hours', 48);

        $notBefore = now()->subHours($maxAge);   // borne basse (pas trop vieux)
        $notAfter  = now()->subMinutes($minAge);  // borne haute (assez mûr)

        $orders = Order::where('status', Order::STATUS_PENDING)
            ->where('payment_status', '!=', Order::PAYMENT_STATUS_COMPLETED)
            // On exclut le paiement cash chez un vendeur (flux hors ligne différent).
            ->where(fn ($q) => $q->whereNull('payment_method')
                ->orWhere('payment_method', '!=', Order::PAYMENT_METHOD_CASH_RESELLER))
            ->whereBetween('created_at', [$notBefore, $notAfter])
            ->with('user')
            ->limit(500)
            ->get();

        $sent = 0;
        foreach ($orders as $order) {
            $phone = data_get($order->billing_details, 'phone') ?: optional($order->user)->phone;
            if (empty($phone)) {
                continue;
            }

            // Lien : espace client si compte, sinon la boutique.
            $link = $order->user_id ? route('orders.show', $order) : route('boutique');

            $body = "Bonjour 👋\n\nVotre commande *{$order->order_number}* n'est pas encore finalisée. "
                  . "Reprenez votre achat en quelques secondes :\n{$link}\n\n"
                  . "Besoin d'aide ? Répondez à ce message. 💬";

            $msg = $notifier->text($phone, $body, [
                'category'  => WhatsAppMessage::CAT_TRANSACTIONAL,
                'dedup_key' => "cart-reminder-{$order->id}",
                'context'   => ['cart', $order->id],
            ]);

            if ($msg) {
                $sent++;
            }
        }

        $this->info("Relances panier : {$sent} envoi(s) enfilé(s) sur {$orders->count()} candidate(s).");
        return self::SUCCESS;
    }
}
