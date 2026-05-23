<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->cascadeOnDelete();
            $table->string('order_number')->unique();
            $table->uuid('claim_token')->unique();          // Token pour le QR code public
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->decimal('subtotal',         10, 2)->default(0);
            $table->decimal('commission_earned',10, 2)->default(0);
            $table->decimal('total_amount',     10, 2)->default(0);
            $table->string('currency', 5)->default('XAF');
            $table->string('status', 20)->default('pending');         // pending | processing | completed | cancelled | failed
            $table->string('payment_status', 20)->default('pending'); // pending | completed | failed
            $table->string('payment_method', 30)->nullable();         // wallet | ebilling | cash
            $table->string('external_reference')->nullable();         // E-Billing ref
            $table->timestamp('claimed_at')->nullable();              // Quand le client a scanné
            $table->timestamp('expires_at')->nullable();              // Optionnel : 30 jours par défaut
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['reseller_id', 'status']);
            $table->index('claim_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_orders');
    }
};
