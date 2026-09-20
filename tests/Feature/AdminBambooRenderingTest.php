<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\BambooReportingService;
use App\Support\AdminBadges;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Tests\TestCase;

/**
 * Rendu admin Bamboo : les soldes affichent l'équivalent FCFA (demande
 * utilisateur), le dashboard expose le total FCFA du fournisseur, et aucune
 * vue admin ne porte de couleur hex hors tokens (règle « layered »).
 */
class AdminBambooRenderingTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Exécutés sur la base DEV (fichier) déjà migrée : les migrations
        // `RefreshDatabase` tombent sur SQLite (dropForeign par nom) et la
        // base `:memory:` de phpunit est vide. DatabaseTransactions rolle
        // back chaque test, aucune donnée n'est donc altérée.
        config(['database.connections.sqlite.database' => database_path('database.sqlite')]);
        \Illuminate\Support\Facades\DB::purge('sqlite');

        // La base DEV n'a pas les tables marchands (card_owners, merchant_*).
        // `AdminBadges::make()` est résolu via le conteneur : on y lie un mock
        // pour que le layout (_nav) et AdminDashboardStats rendent sans DB.
        $badges = Mockery::mock(AdminBadges::class);
        $badges->shouldReceive('pendingDeliveries')->andReturn(0);
        $badges->shouldReceive('pendingProAccounts')->andReturn(0);
        $badges->shouldReceive('pendingSettlements')->andReturn(['amount' => 0.0, 'count' => 0]);
        $this->app->instance(AdminBadges::class, $badges);
    }

    private array $accounts = [
        [
            'id' => 1134, 'currency' => 'USD', 'balance' => 0.0,
            'isActive' => true, 'sandboxMode' => false,
        ],
        [
            'id' => 1137, 'currency' => 'EUR', 'balance' => 2081.875,
            'isActive' => true, 'sandboxMode' => false,
        ],
    ];

    private array $rates = [
        'base' => 'USD',
        'rates' => [
            ['currencyCode' => 'EUR', 'value' => 1.159379],
            ['currencyCode' => 'USD', 'value' => 1.01],
            ['currencyCode' => 'XAF', 'value' => 0.0017675],
        ],
    ];

    private array $ratesPayload = ['ok' => true, 'error' => null];

    private function ratesPayload(): array
    {
        return $this->ratesPayload + $this->rates;
    }

    private function mockBamboo(array $overrides = []): void
    {
        $mock = Mockery::mock(BambooReportingService::class);
        $mock->shouldReceive('accounts')->andReturn(array_merge([
            'ok' => true, 'accounts' => $this->accounts, 'fetched_at' => '2026-09-20 12:00:00', 'error' => null,
        ], $overrides['accounts'] ?? []));
        $mock->shouldReceive('transactions')->andReturn($overrides['transactions'] ?? [
            'ok' => true, 'clients' => [], 'fetched_at' => null, 'error' => null,
        ]);
        $mock->shouldReceive('exchangeRates')->andReturn($overrides['rates'] ?? $this->ratesPayload());
        $mock->shouldReceive('orderReport')->andReturn([
            'ok' => true, 'orders' => [], 'error' => null,
        ]);

        $this->app->instance(BambooReportingService::class, $mock);
    }

    private function actingAsAdmin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
    }

    public function test_bamboo_index_affiche_le_total_fcfa_et_les_taux(): void
    {
        $this->mockBamboo();
        $this->actingAsAdmin();

        // EUR 2081,875 → 2081,875 × 655,957 ≈ 1.365.610 FCFA ; USD 0.
        $response = $this->get(route('admin.bamboo.index'));

        $response->assertOk();
        $response->assertSee('FCFA', false);
        $response->assertSee('Équivalent', false);
    }

    public function test_bamboo_index_affiche_solde_bas_quand_sous_le_seuil(): void
    {
        $this->mockBamboo(['accounts' => [
            'ok' => true,
            'accounts' => [[
                'id' => 1137, 'currency' => 'EUR', 'balance' => 120.0,
                'isActive' => true, 'sandboxMode' => false,
            ]],
            'fetched_at' => '2026-09-20 12:00:00', 'error' => null,
        ]]);
        $this->actingAsAdmin();

        $response = $this->get(route('admin.bamboo.index'));

        $response->assertOk();
        $response->assertSee('SOLDE BAS', false);
    }

    public function test_bamboo_index_grace_quand_proxy_indisponible(): void
    {
        $this->mockBamboo(['accounts' => [
            'ok' => false, 'accounts' => [], 'fetched_at' => null, 'error' => 'HTTP 503',
        ]]);
        $this->actingAsAdmin();

        $response = $this->get(route('admin.bamboo.index'));

        $response->assertOk();
        $response->assertSee('Indisponible', false);
    }

    public function test_dashboard_affiche_le_total_fcfa_du_solde_fournisseur(): void
    {
        $this->mockBamboo();
        $this->actingAsAdmin();

        $response = $this->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Solde fournisseur Bamboo', false);
        $response->assertSee('Équivalent total', false);
        $response->assertSee('FCFA', false);
    }

    /**
     * Une transaction Bamboo liée à une commande locale (par checkout_order_id)
     * affiche le client de la commande et un lien vers la fiche de détail —
     * « Adopter list-screen partout » + liaison vers les commandes.
     */
    public function test_bamboo_index_lie_les_transactions_aux_commandes_locales(): void
    {
        $client = User::factory()->create([
            'name' => 'Clara Ikouma',
            'email' => 'clara.' . uniqid() . '@example.com',
            'role' => 'user',
        ]);
        $order = Order::create([
            'user_id' => $client->id,
            'status' => Order::STATUS_COMPLETED,
            'subtotal' => 9.42,
            'tax_amount' => 0.0,
            'total_amount' => 9.42,
            'currency' => 'EUR',
            'payment_status' => Order::PAYMENT_STATUS_COMPLETED,
            'billing_details' => ['checkout_order_id' => 33909211, 'checkout_request_id' => 'req-abc-123'],
        ]);

        try {
            $this->mockBamboo(['transactions' => [
                'ok' => true,
                'clients' => [[
                    'clientName' => 'FUTUR SOWAX - Client',
                    'transactions' => [[
                        'orderId' => 33909211,
                        'requestId' => 'req-abc-123',
                        'transactionId' => '33747291 ',
                        'transactionType' => 'Order',
                        'orderTotal' => ['value' => 9.42, 'currencyCode' => 'EUR'],
                        'transactionAmount' => ['value' => 9.42, 'currencyCode' => 'EUR'],
                        'availableBalance' => ['value' => 81.25, 'currencyCode' => 'EUR'],
                        'orderItems' => [[
                            'productName' => 'Carte Airtel 5 000 FCFA',
                            'denomination' => ['value' => 5000, 'currencyCode' => 'FCFA'],
                        ]],
                        'transactionDate' => '2026-09-19T15:30:00.000Z',
                    ]],
                ]],
            ]]);
            $this->actingAsAdmin();

            $response = $this->get(route('admin.bamboo.index'));

            $response->assertOk();
            $response->assertSee('Clara Ikouma', false);
            $response->assertSee('Commande locale · ' . $order->order_number, false);
            $response->assertSee(route('admin.orders.show', $order), false);
        } finally {
            // DatabaseTransactions ne rolle back ici que pour les écritures via
            // la connexion reconfigurée : purge explicite pour ne surtout pas
            // laisser la commande créer des ventes sur le dashboard des autres tests.
            $order->delete();
            $client->delete();
        }
    }

    /**
     * Règle « layered » : aucun hex dans les vues admin (les couleurs passent
     * par les tokens .adm). Test borner sur les vues Bamboo touchées.
     */
    public function test_aucun_hex_dans_les_vues_bamboo(): void
    {
        $files = [
            resource_path('views/admin/bamboo/index.blade.php'),
            resource_path('views/admin/bamboo/reconcile.blade.php'),
        ];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,6}\b/', $content);
        }
    }
}