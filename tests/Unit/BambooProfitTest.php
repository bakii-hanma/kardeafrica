<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\BambooProfit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Calcul du bénéfice (prix Kardafrica − coût réel Bamboo).
 *
 * Les modèles sont construits en mémoire (attributs + relations), sans base :
 * `forOrder`/`periodStats`/`splitByItem` ne lisent que ce que le contrôleur
 * leur donne. Les montants FCFA s'expriment avec les taux Money de repli.
 */
class BambooProfitTest extends TestCase
{
    private array $xafRates = [
        'EUR' => 655.957,
        'USD' => 620.0,
        'AED' => 170.0,
    ];

    private function order(array $billing = []): Order
    {
        $order = new Order([
            'id' => 1,
            'total_amount' => 7000,
            'payment_status' => Order::PAYMENT_STATUS_COMPLETED,
            'billing_details' => $billing,
            'created_at' => Carbon::parse('2026-09-15 10:00:00', 'UTC'),
        ]);
        $order->exists = true;

        $order->setRelation('orderItems', collect([
            new OrderItem([
                'id' => 1, 'product_id' => '111', 'name' => 'Steam 10 EUR',
                'quantity' => 1, 'unit_price' => 7000, 'total_price' => 7000,
                'native_value' => 10, 'native_currency' => 'EUR',
            ]),
        ]));

        return $order;
    }

    private function tx(array $overrides = []): array
    {
        return array_merge([
            'orderId' => 12345,
            'requestId' => 'req-abc',
            'transactionAmount' => ['value' => 9.42, 'currencyCode' => 'EUR'],
            'orderItems' => [
                ['productId' => '111', 'quantity' => 1, 'clientPrice' => ['value' => 6.5, 'currencyCode' => 'EUR']],
                ['productId' => '222', 'quantity' => 2, 'clientPrice' => ['value' => 1.46, 'currencyCode' => 'EUR']],
            ],
            'transactionDate' => '2026-09-15T10:05:00.000Z',
        ], $overrides);
    }

    public function test_match_transaction_par_checkout_order_id(): void
    {
        $order = $this->order(['checkout_order_id' => 12345, 'checkout_request_id' => 'req-abc']);
        $found = BambooProfit::matchTransaction($order, collect([$this->tx()]));

        $this->assertNotNull($found);
        $this->assertSame(12345, $found['orderId']);
    }

    public function test_match_transaction_par_request_id_quand_order_id_absent(): void
    {
        $order = $this->order(['checkout_request_id' => 'req-abc']);
        $tx    = $this->tx(['orderId' => null]);

        $this->assertNotNull(BambooProfit::matchTransaction($order, collect([$tx])));
    }

    public function test_match_revient_null_sans_aucun_lien(): void
    {
        $this->assertNull(BambooProfit::matchTransaction($this->order(), collect([$this->tx()])));
    }

    public function test_transaction_cost_fcfa_convertit_le_montant_debite(): void
    {
        $cost = BambooProfit::transactionCostFcfa($this->tx(), $this->xafRates);

        $this->assertNotNull($cost);
        $this->assertEqualsWithDelta(9.42 * 655.957, $cost, 0.01);
    }

    public function test_transaction_cost_null_sans_transaction_amount(): void
    {
        $this->assertNull(BambooProfit::transactionCostFcfa(['orderId' => 1], $this->xafRates));
    }

    public function test_for_order_source_bamboo_avec_transaction(): void
    {
        $r = BambooProfit::forOrder($this->order(['checkout_order_id' => 12345]), $this->tx(), $this->xafRates);

        $this->assertTrue($r['matched']);
        $this->assertSame('bamboo', $r['source']);
        $this->assertEqualsWithDelta(7000, $r['card_total_fcfa'], 0.01);
        $this->assertEqualsWithDelta(9.42 * 655.957, $r['cost_fcfa'], 0.01);
        $this->assertEqualsWithDelta(7000 - 9.42 * 655.957, $r['profit_fcfa'], 0.01);
        $this->assertNotNull($r['margin_percent']);
    }

    public function test_for_order_repli_native_value_sans_transaction(): void
    {
        $r = BambooProfit::forOrder($this->order(), null, $this->xafRates);

        $this->assertFalse($r['matched']);
        $this->assertSame('fallback', $r['source']);
        $this->assertEqualsWithDelta(10 * 655.957, $r['cost_fcfa'], 0.01);
    }

    public function test_for_order_cout_indisponible_sans_source(): void
    {
        $order = $this->order();
        $order->setRelation('orderItems', collect([new OrderItem([
            'id' => 9, 'product_id' => '000', 'name' => 'Vieille carte',
            'quantity' => 1, 'unit_price' => 7000, 'total_price' => 7000,
            'native_value' => null, 'native_currency' => null,
        ])]));

        $r = BambooProfit::forOrder($order, null, $this->xafRates);

        $this->assertSame('none', $r['source']);
        $this->assertNull($r['cost_fcfa']);
        $this->assertNull($r['profit_fcfa']);
    }

    public function test_split_by_item_repartit_le_cout_par_article(): void
    {
        $order = $this->order(['checkout_order_id' => 12345]);
        $split = BambooProfit::splitByItem($order, $this->tx(), $this->xafRates);

        $this->assertCount(1, $split);
        $this->assertSame('111', $split[0]['item']->product_id);
        $this->assertEqualsWithDelta(6.5 * 655.957, $split[0]['cost_fcfa'], 0.01);
    }

    public function test_split_by_item_null_quand_product_inconnu(): void
    {
        $order = $this->order(['checkout_order_id' => 12345]);
        $tx    = $this->tx(['orderItems' => [
            ['productId' => 'DOESNOTEXIST', 'quantity' => 1, 'clientPrice' => ['value' => 6.5, 'currencyCode' => 'EUR']],
        ]]);

        $split = BambooProfit::splitByItem($order, $tx, $this->xafRates);

        $this->assertNull($split[0]['cost_fcfa']);
    }

    public function test_period_stats_melange_bamboo_et_estimations(): void
    {
        $bamboo = $this->order(['checkout_order_id' => 12345]);
        $bamboo->total_amount = 7000;

        $estime = $this->order();
        $estime->total_amount = 1500;
        $estime->id = 2;
        $estime->setRelation('orderItems', collect([
            new OrderItem([
                'id' => 3, 'product_id' => '333', 'name' => 'COD 500 FCFA',
                'quantity' => 1, 'unit_price' => 1500, 'total_price' => 1500,
                'native_value' => 500, 'native_currency' => 'XAF',
            ]),
        ]));

        $sansCout = $this->order();
        $sansCout->total_amount = 999;
        $sansCout->id = 4;
        $sansCout->setRelation('orderItems', collect([
            new OrderItem([
                'id' => 4, 'product_id' => '444', 'name' => 'Ancienne',
                'quantity' => 1, 'unit_price' => 999, 'total_price' => 999,
                'native_value' => null, 'native_currency' => null,
            ]),
        ]));

        $stats = BambooProfit::periodStats(
            collect([$bamboo, $estime, $sansCout]),
            collect([$this->tx()]),
            $this->xafRates,
        );

        $this->assertEqualsWithDelta(7000 + 1500 + 999, $stats['sale_total_fcfa'], 0.01);
        $this->assertEqualsWithDelta(9.42 * 655.957 + 500, $stats['cost_total_fcfa'], 0.01);
        $this->assertSame(1, $stats['matched_orders']);
        $this->assertSame(1, $stats['estimated_orders']);
        $this->assertSame(3, $stats['cards_sold']);
        $this->assertNotNull($stats['note']);
        $this->assertStringContainsString('1 commande(s) sans coût', $stats['note']);
    }

    public function test_period_stats_vide_retourne_zero_sans_profit(): void
    {
        $stats = BambooProfit::periodStats(collect(), collect(), $this->xafRates);

        $this->assertEquals(0, $stats['sale_total_fcfa']);
        $this->assertNull($stats['cost_total_fcfa']);
        $this->assertNull($stats['profit_fcfa']);
        $this->assertSame(0, $stats['cards_sold']);
    }
}