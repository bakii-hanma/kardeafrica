<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_cash_remittances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('external_reference')->unique(); // référence E-Billing
            $table->string('status', 16)->default('pending'); // pending | completed | failed | cancelled
            $table->string('bill_id')->nullable(); // bill_id retourné par futursowax
            $table->string('portal_url', 500)->nullable();
            $table->string('payment_method', 32)->nullable(); // ebilling | mobile_money | etc.
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['reseller_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_cash_remittances');
    }
};
