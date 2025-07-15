<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('payment_method'); // mobile_money, bank_transfer, card, etc.
            $table->string('provider')->nullable(); // Orange Money, MTN Mobile Money, etc.
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('XOF');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded']);
            $table->string('external_transaction_id')->nullable();
            $table->json('provider_response')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
