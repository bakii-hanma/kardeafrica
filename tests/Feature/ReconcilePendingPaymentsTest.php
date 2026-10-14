<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Services\OrderFulfillmentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

/**
 * Réconciliation des paiements : une commande PAYÉE mais restée en
 * `payment_status=processing` (le client n'est jamais revenu sur la page
 * /verify → finalize() jamais appelé) doit être détectée puis finalisée.
 * Une commande NON payée (ou payée d'un montant insuffisant) est laissée
 * intacte.
 *
 * La livraison réelle (ProcessCheckoutJob) est stubée : la base DEV de test
 * (fichier) a un schéma plus ancien que les migrations actuelles.
 */
class ReconcilePendingPaymentsTest extends TestCase
{
    use DatabaseTransactions;

    private ?Order $order = null;
    private ?User $user = null;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.sqlite.database' => database_path('database.sqlite')]);
        DB::purge('sqlite');
    }

    protected function tearDown(): void
    {
        Mockery::close();

        if ($this->order) {
            Payment::where('order_id', $this->order->id)->delete();
            OrderItem::where('order_id', $this->order->id)->delete();
            $this->order->delete();
        }
        if ($this->user) {
            $this->user->delete();
        }
        parent::tearDown();
    }

    private function makePendingOrder(string $ref, int $amount = 11000): Order
    {
        $this->user = User::factory()->create(['role' => 'user']);

        $this->order = Order::create([
            'order_number'       => 'KA' . now()->format('Ymd') . strtoupper(substr(md5($ref), 0, 4)),
            'external_reference' => $ref,
            'user_id'            => $this->user->id,
            'status'             => Order::STATUS_PENDING,
            'subtotal'           => $amount,
            'tax_amount'         => 0,
            'total_amount'       => $amount,
            'currency'           => 'XAF',
            'payment_status'     => Order::PAYMENT_STATUS_PROCESSING,
            'billing_details'    => ['name' => 'Test Client', 'email' => 'test@example.com', 'phone' => '+241061112233'],
        ]);

        OrderItem::create([
            'order_id'     => $this->order->id,
            'product_id'   => '12345',
            'name'         => 'Carte test',
            'quantity'     => 1,
            'unit_price'   => $amount,
            'native_value' => 15,
            'total_price'  => $amount,
        ]);

        Payment::create([
            'transaction_id'          => $ref,
            'order_id'                => $this->order->id,
            'user_id'                 => $this->user->id,
            'payment_method'          => 'ebilling',
            'provider'                => 'futursowax',
            'amount'                  => $amount,
            'currency'                => 'XAF',
            'status'                  => Payment::STATUS_PENDING,
            'external_transaction_id' => '555',
        ]);

        return $this->order;
    }

    /** @param array{bill_id:string,state:string,amount_paid:float} $bill */
    private function fakeEbilling(array $bill): void
    {
        Http::fake(['stg.billing-easy.com/*' => Http::response($bill, 200)]);
    }

    public function test_finalise_une_commande_payee_restee_en_processing(): void
    {
        $ref = 'KARD_' . time() . '_TESTPAID';
        $order = $this->makePendingOrder($ref, 11000);
        $this->fakeEbilling(['bill_id' => '555', 'state' => 'paid', 'amount_paid' => 11000]);

        $fulfillment = Mockery::mock(OrderFulfillmentService::class);
        $fulfillment->shouldReceive('markPaid')->once()->with(Mockery::type(Order::class), $ref)->andReturn(true);
        $fulfillment->shouldReceive('deliver')->once();
        $this->app->instance(OrderFulfillmentService::class, $fulfillment);

        $this->artisan('orders:reconcile-payments', ['--hours' => 24])->assertSuccessful();
    }

    public function test_laisse_une_commande_non_payee_intacte(): void
    {
        $ref = 'KARD_' . time() . '_TESTUNPAID';
        $this->makePendingOrder($ref, 11000);
        $this->fakeEbilling(['bill_id' => '555', 'state' => 'unpaid', 'amount_paid' => 0]);

        $fulfillment = Mockery::mock(OrderFulfillmentService::class);
        $fulfillment->shouldNotReceive('markPaid');
        $fulfillment->shouldNotReceive('deliver');
        $this->app->instance(OrderFulfillmentService::class, $fulfillment);

        $this->artisan('orders:reconcile-payments', ['--hours' => 24])->assertSuccessful();
    }

    public function test_refuse_un_montant_paye_inferieur_au_total(): void
    {
        $ref = 'KARD_' . time() . '_TESTPARTIAL';
        $this->makePendingOrder($ref, 11000);
        $this->fakeEbilling(['bill_id' => '555', 'state' => 'paid', 'amount_paid' => 500]);

        $fulfillment = Mockery::mock(OrderFulfillmentService::class);
        $fulfillment->shouldNotReceive('markPaid');
        $this->app->instance(OrderFulfillmentService::class, $fulfillment);

        $this->artisan('orders:reconcile-payments', ['--hours' => 24])->assertSuccessful();
    }

    public function test_mark_paid_bascule_la_commande_et_le_paiement(): void
    {
        $ref = 'KARD_' . time() . '_TESTMARK';
        $order = $this->makePendingOrder($ref, 11000);

        $done = app(OrderFulfillmentService::class)->markPaid($order, $ref);

        $this->assertTrue($done);
        $order->refresh();
        $this->assertSame(Order::PAYMENT_STATUS_COMPLETED, $order->payment_status);
        $this->assertSame(Order::STATUS_PROCESSING, $order->status);
        $this->assertSame(Payment::STATUS_COMPLETED, Payment::where('transaction_id', $ref)->value('status'));

        // Idempotence : un second appel ne re-finalise pas.
        $this->assertFalse(app(OrderFulfillmentService::class)->markPaid($order->fresh(), $ref));
    }
}
