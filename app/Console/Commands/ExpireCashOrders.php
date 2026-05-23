<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Reseller;
use App\Models\ResellerOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Libère le solde bloqué des commandes cash dont le délai est expiré.
 *
 * À schedule toutes les 5 minutes (routes/console.php).
 */
class ExpireCashOrders extends Command
{
    protected $signature = 'cash:expire-orders';

    protected $description = 'Annule les commandes cash dont le verrou de fonds est expiré et libère les soldes bloqués des vendeurs.';

    public function handle(): int
    {
        // 1. Cash boutique (Order avec cash_reseller_id) — délai 2h
        $expiredCustomer = Order::where('payment_method', Order::PAYMENT_METHOD_CASH_RESELLER)
            ->where('payment_status', Order::PAYMENT_STATUS_PENDING)
            ->whereNotNull('cash_lock_expires_at')
            ->where('cash_lock_expires_at', '<', now())
            ->whereNotNull('cash_reseller_id')
            ->get();

        $countCustomer = 0;
        foreach ($expiredCustomer as $order) {
            try {
                DB::transaction(function () use ($order) {
                    $reseller = Reseller::lockForUpdate()->find($order->cash_reseller_id);
                    if ($reseller) {
                        $reseller->releaseFunds((float) $order->subtotal, $order->order_number);
                    }
                    $order->update([
                        'payment_status' => Order::PAYMENT_STATUS_CANCELLED,
                        'status'         => Order::STATUS_CANCELLED,
                        'notes'          => 'Expirée automatiquement (délai dépassé)',
                    ]);
                });
                $countCustomer++;
            } catch (\Throwable $e) {
                Log::error('ExpireCashOrders: échec libération customer order', [
                    'order_id' => $order->id, 'error' => $e->getMessage(),
                ]);
            }
        }

        // 2. Cash vente directe (ResellerOrder avec payment_method='cash') — délai 30 min
        $expiredVendor = ResellerOrder::where('payment_method', 'cash')
            ->where('payment_status', ResellerOrder::PAYMENT_PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $countVendor = 0;
        foreach ($expiredVendor as $order) {
            try {
                DB::transaction(function () use ($order) {
                    $reseller = Reseller::lockForUpdate()->find($order->reseller_id);
                    if ($reseller) {
                        $reseller->releaseFunds((float) $order->subtotal, $order->order_number);
                    }
                    $order->update([
                        'status'         => ResellerOrder::STATUS_CANCELLED,
                        'payment_status' => ResellerOrder::PAYMENT_FAILED,
                        'notes'          => 'Expirée automatiquement (30 min dépassées) — fonds libérés',
                    ]);
                });
                $countVendor++;
            } catch (\Throwable $e) {
                Log::error('ExpireCashOrders: échec libération vendor cash', [
                    'order_id' => $order->id, 'error' => $e->getMessage(),
                ]);
            }
        }

        $total = $countCustomer + $countVendor;
        if ($total === 0) {
            $this->info('Aucune commande cash à expirer.');
        } else {
            $this->info("Expiré : {$countCustomer} commande(s) boutique + {$countVendor} vente(s) vendeur cash.");
        }
        return self::SUCCESS;
    }
}
