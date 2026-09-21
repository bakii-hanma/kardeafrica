<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\BambooReportingService;
use App\Support\AdminBadges;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/**
 * Rendu du bénéfice : la fiche commande affiche « Bénéfice gagné » (coût réel
 * Bamboo, repli valeur faciale « coût estimé »), et le dashboard affiche le
 * widget bénéfice de la période.
 */
class OrderBenefitRenderingTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.sqlite.database' => database_path('database.sqlite')]);
        DB::purge('sqlite');

        $badges = Mockery::mock(AdminBadges::class);
        $badges->shouldReceive('pendingDeliveries')->andReturn(0);
        $badges->shouldReceive('pendingProAccounts')->andReturn(0);
        $badges->shouldReceive('pendingSettlements')->andReturn(['amount' => 0.0, 'count' => 0]);
        $this->app->instance(AdminBadges::class, $badges);
    }

    private function ratesPayload(): array
    {
        return [
            'ok' => true, 'error' => null, 'base' => 'USD',
            'rates' => [
                ['currencyCode' => 'EUR', 'value' => 1.159379],
                ['currencyCode' => 'USD', 'value' => 1.01],
                ['currencyCode' => 'XAF', 'value' => 0.0017675],
            ],
        ];
    }

    private function mockBamboo(array $overrides = []): void
    {
        $mock = Mockery::mock(BambooReportingService::class);
        $mock->shouldReceive('exchangeRates')->andReturn($overrides['rates'] ?? $this->ratesPayload());
        $mock->shouldReceive('transactions')->andReturn($overrides['transactions'] ?? [
            'ok' => true, 'clients' => [], 'fetched_at' => null, 'error' => null,
        ]);
        $this->app->instance(BambooReportingService::class, $mock);
    }

    private function makeOrderWithItem(array $itemOverrides = []): array
    {
        $client = User::factory()->create(['role' => 'user']);

        $order = Order::create([
            'user_id' => $client->id,
            'status' => Order::STATUS_COMPLETED,
            'subtotal' => 7000,
            'tax_amount' => 0.0,
            'total_amount' => 7000,
            'currency' => 'XAF',
            'payment_status' => Order::PAYMENT_STATUS_COMPLETED,
            'billing_details' => ['checkout_order_id' => 12345, 'checkout_request_id' => 'req-mock-123'],
        ]);

        OrderItem::create(array_merge([
            'order_id' => $order->id,
            'product_id' => '111',
            'name' => 'Steam 10 EUR',
            'quantity' => 1,
            'unit_price' => 7000,
            'native_value' => 10,
            'native_currency' => 'EUR',
            'total_price' => 7000,
        ], $itemOverrides));

        return [$order, $client];
    }

    public function test_fiche_commande_affiche_le_benefice_bamboo(): void
    {
        [$order, $client] = $this->makeOrderWithItem();

        try {
            $this->mockBamboo(['transactions' => [
                'ok' => true,
                'clients' => [[
                    'clientName' => 'FUTUR SOWAX - Client',
                    'transactions' => [[
                        'orderId' => 12345,
                        'requestId' => 'req-mock-123',
                        'transactionId' => 'T-1',
                        'transactionType' => 'Order',
                        'transactionAmount' => ['value' => 9.42, 'currencyCode' => 'EUR'],
                        'orderItems' => [[
                            'productId' => '111',
                            'denomination' => ['value' => 10, 'currencyCode' => 'EUR'],
                            'clientPrice' => ['value' => 9.42, 'currencyCode' => 'EUR'],
                        ]],
                        'transactionDate' => now()->toIso8601String(),
                    ]],
                ]],
            ]]);
            $this->actingAs(User::factory()->create(['role' => 'admin']));

            $response = $this->get(route('admin.orders.show', $order));

            $response->assertOk();
            $response->assertSee('Bénéfice gagné', false);
            $response->assertSee('Coût (vrai prix)', false);
            $response->assertSee('marge', false);
            $response->assertSee('transaction Bamboo', false);
        } finally {
            $order->delete();
            $client->delete();
        }
    }

    public function test_fiche_commande_affiche_le_cout_estime_sans_transaction(): void
    {
        [$order, $client] = $this->makeOrderWithItem();

        try {
            $this->mockBamboo(); // aucune transaction
            $this->actingAs(User::factory()->create(['role' => 'admin']));

            $response = $this->get(route('admin.orders.show', $order));

            $response->assertOk();
            $response->assertSee('Bénéfice gagné', false);
            $response->assertSee('coût estimé', false);
            $response->assertSee('Aucune transaction Bamboo reliée', false);
        } finally {
            $order->delete();
            $client->delete();
        }
    }

    public function test_fiche_commande_grace_sans_cout(): void
    {
        // Article sans valeur faciale ni lien Bamboo → le panneau ne doit ni
        // casser la page ni s'afficher.
        [$order, $client] = $this->makeOrderWithItem(['native_value' => null, 'native_currency' => null]);
        $order->update(['billing_details' => []]);

        try {
            $this->mockBamboo();
            $this->actingAs(User::factory()->create(['role' => 'admin']));

            $response = $this->get(route('admin.orders.show', $order));

            $response->assertOk();
            $response->assertDontSee('Coût (vrai prix)', false);
        } finally {
            $order->delete();
            $client->delete();
        }
    }
}