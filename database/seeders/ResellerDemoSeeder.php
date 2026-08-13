<?php

namespace Database\Seeders;

use App\Models\MerchantCard;
use App\Models\MerchantCardPurchase;
use App\Models\Reseller;
use App\Models\ResellerOrder;
use App\Models\ResellerOrderItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Jeu de démonstration de l'espace vendeur — il n'en existait aucun, ce qui
 * rendait le dashboard impossible à visualiser en local.
 *
 *   php artisan db:seed --class=ResellerDemoSeeder
 *
 * Idempotent (updateOrCreate sur le vendor_code, commandes recréées à neuf).
 * Volontairement REPRÉSENTATIF des cas réels, pas seulement du chemin heureux :
 * des commandes livrées, une en cours, une abandonnée, une remboursée, une
 * échouée, du cash en attente d'encaissement et du cash à reverser. C'est ce
 * mélange qui fait apparaître les statistiques fausses du dashboard.
 */
class ResellerDemoSeeder extends Seeder
{
    public const DEMO_CODE     = 'KA-V-G8H6';
    public const DEMO_PASSWORD = 'demo1234';

    public function run(): void
    {
        $reseller = Reseller::updateOrCreate(
            ['vendor_code' => self::DEMO_CODE],
            [
                'name'                    => 'Jean-Pierre ESSONO',
                'business_name'           => 'ESSONO Boutique & Services',
                'phone'                   => '24100000001',
                'email'                   => 'vendeur.demo@kardafrica.com',
                'password'                => self::DEMO_PASSWORD,   // hashé par le cast
                'wallet_balance'          => 82_000,
                'wallet_locked'           => 15_000,   // une commande cash en attente
                'commission_balance'      => 24_350,
                'cash_to_remit'           => 45_000,
                'max_wallet'              => 150_000,
                'commission_rate'         => 4.50,
                'total_commission_earned' => 24_350,
                'total_volume'            => 541_000,
                'is_active'               => true,
                'city'                    => 'Libreville',
                'whatsapp_number'         => '24100000001',
            ]
        );

        // Repart d'une base propre pour rester idempotent.
        ResellerOrder::where('reseller_id', $reseller->id)->delete();

        // [jours, statut, paiement, méthode, sous-total, libellé produit]
        $scenarios = [
            [0,  ResellerOrder::STATUS_COMPLETED,  ResellerOrder::PAYMENT_COMPLETED, 'ebilling', 18_300, 'Netflix EU 25 €'],
            [0,  ResellerOrder::STATUS_COMPLETED,  ResellerOrder::PAYMENT_COMPLETED, 'cash',      7_100, 'Playstation France 10 €'],
            [0,  ResellerOrder::STATUS_PENDING,    ResellerOrder::PAYMENT_PENDING,   'ebilling', 12_000, 'Steam Games EU 15 €'],
            [0,  ResellerOrder::STATUS_REFUNDED,   ResellerOrder::PAYMENT_REFUNDED,  'ebilling',  9_500, 'Xbox France 12 €'],
            [1,  ResellerOrder::STATUS_COMPLETED,  ResellerOrder::PAYMENT_COMPLETED, 'ebilling', 36_400, 'Binance (USDT) 50 $'],
            [1,  ResellerOrder::STATUS_PROCESSING, ResellerOrder::PAYMENT_COMPLETED, 'ebilling', 21_800, 'Rewarble ChatGPT 30 $'],
            [2,  ResellerOrder::STATUS_FAILED,     ResellerOrder::PAYMENT_COMPLETED, 'ebilling',  6_300, 'Deezer Premium France'],
            [3,  ResellerOrder::STATUS_COMPLETED,  ResellerOrder::PAYMENT_COMPLETED, 'cash',     75_000, 'Apple France 100 €'],
            [5,  ResellerOrder::STATUS_COMPLETED,  ResellerOrder::PAYMENT_COMPLETED, 'ebilling', 18_800, 'Apple France 25 €'],
            [8,  ResellerOrder::STATUS_COMPLETED,  ResellerOrder::PAYMENT_COMPLETED, 'ebilling',  7_000, 'ROBLOX France 10 €'],
            [12, ResellerOrder::STATUS_COMPLETED,  ResellerOrder::PAYMENT_COMPLETED, 'cash',     22_000, 'Netflix EU 30 €'],
            [18, ResellerOrder::STATUS_CANCELLED,  ResellerOrder::PAYMENT_FAILED,    'ebilling', 11_300, 'Apple France 15 €'],
            [25, ResellerOrder::STATUS_COMPLETED,  ResellerOrder::PAYMENT_COMPLETED, 'ebilling', 37_500, 'Apple France 50 €'],
        ];

        foreach ($scenarios as $i => [$daysAgo, $status, $payStatus, $method, $subtotal, $label]) {
            $at         = now()->subDays($daysAgo)->subHours($i);
            $commission = round($subtotal * (float) $reseller->commission_rate / 100, 2);

            $order = ResellerOrder::create([
                'reseller_id'        => $reseller->id,
                'customer_name'      => ['Aïssa M.', 'Yannick O.', 'Nadia B.', 'Serge N.', 'Prisca L.'][$i % 5],
                'customer_phone'     => '2410000001' . ($i % 10),
                'subtotal'           => $subtotal,
                'commission_earned'  => $commission,
                'total_amount'       => $subtotal,
                'currency'           => 'XAF',
                'status'             => $status,
                'payment_status'     => $payStatus,
                'payment_method'     => $method,
                'external_reference' => 'DEMO_' . strtoupper(Str::random(8)),
                'completed_at'       => $status === ResellerOrder::STATUS_COMPLETED ? $at : null,
                'notes'              => $status === ResellerOrder::STATUS_FAILED
                    ? 'Livraison afrikard impossible : produit introuvable au catalogue.'
                    : null,
            ]);
            // created_at n'est pas fillable : on le force pour étaler l'historique.
            $order->forceFill(['created_at' => $at, 'updated_at' => $at])->save();

            ResellerOrderItem::create([
                'reseller_order_id' => $order->id,
                'product_id'        => (string) (100000 + $i),
                'name'              => $label,
                'brand'             => explode(' ', $label)[0],
                'unit_price'        => $subtotal,
                'quantity'          => 1,
                'total_price'       => $subtotal,
            ]);
        }

        // Ventes de Cartes Gabon au comptoir — absentes du dashboard aujourd'hui,
        // ce qui est précisément l'un des manques à corriger.
        $card = MerchantCard::active()->first();
        if ($card) {
            MerchantCardPurchase::where('reseller_id', $reseller->id)->delete();
            foreach ([[0, 10_000], [2, 5_000], [6, 15_000]] as $j => [$daysAgo, $amount]) {
                $commission = round($amount * 4.5 / 100, 2);
                $purchase = MerchantCardPurchase::create([
                    'merchant_card_id'         => $card->id,
                    'reseller_id'              => $reseller->id,
                    // unique_code est un varchar(8) : code court, comme en production.
                    'unique_code'              => $code = 'DM' . strtoupper(Str::random(6)),
                    'pin_code'                 => (string) random_int(1000, 9999),
                    'qr_payload'               => $code,
                    'remaining_balance'        => $amount,
                    'currency'                 => 'XAF',
                    'buyer_name'               => ['Client comptoir', 'Mme Ondo', 'M. Bekale'][$j],
                    'buyer_phone'              => '2410000002' . $j,
                    'buyer_email'              => 'client' . $j . '@example.test',
                    'amount'                   => $amount,
                    'vendor_commission_amount' => $commission,
                    'status'                   => 'active',
                    'payment_status'           => 'paid',
                    'sold_by_reseller_at'      => now()->subDays($daysAgo),
                    'paid_at'                  => now()->subDays($daysAgo),
                    'expires_at'               => now()->addMonths(12),
                ]);
                $purchase->forceFill([
                    'created_at' => now()->subDays($daysAgo),
                    'updated_at' => now()->subDays($daysAgo),
                ])->save();
            }
        }

        // Quelques mouvements de portefeuille, sinon le relevé est vide et
        // l'écran ne se laisse pas juger.
        \App\Models\ResellerWalletTransaction::where('reseller_id', $reseller->id)->delete();
        $mouvements = [
            [12, 'sales', 'credit',      50_000, 'Recharge Airtel Money'],
            [10, 'sales', 'debit',       18_300, 'Achat Netflix EU 25 €'],
            [10, 'commission', 'commission',  824, 'Commission vente #KV-DEMO-1'],
            [7,  'sales', 'lock',        15_000, 'Fonds réservés (vente en espèces en attente)'],
            [5,  'sales', 'debit',        7_100, 'Achat Playstation France 10 €'],
            [5,  'commission', 'commission', 320, 'Commission vente #KV-DEMO-2'],
            [3,  'sales', 'refund',       9_500, 'Remboursement vente #KV-DEMO-3'],
            [2,  'sales', 'cash_collected', 45_000, 'Cash encaissé au comptoir'],
            [1,  'sales', 'credit',      20_000, 'Recharge Moov Money'],
        ];
        $solde = 82_000.0;
        foreach ($mouvements as $i => [$daysAgo, $wallet, $type, $montant, $libelle]) {
            $tx = \App\Models\ResellerWalletTransaction::create([
                'reseller_id'    => $reseller->id,
                'wallet'         => $wallet,
                'type'           => $type,
                'amount'         => $montant,
                'balance_before' => $solde,
                'balance_after'  => $solde,
                'description'    => $libelle,
                'reference'      => 'DEMO-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
            ]);
            $at = now()->subDays($daysAgo);
            $tx->forceFill(['created_at' => $at, 'updated_at' => $at])->save();
        }

        $this->command->info('Vendeur de démonstration prêt.');
        $this->command->line('  URL       : ' . url('/vendor/login'));
        $this->command->line('  Code      : ' . self::DEMO_CODE);
        $this->command->line('  Mot de passe : ' . self::DEMO_PASSWORD);
    }
}
