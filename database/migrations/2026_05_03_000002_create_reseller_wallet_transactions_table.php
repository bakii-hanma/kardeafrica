<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20);                      // credit | debit | commission | adjustment
            $table->decimal('amount',         10, 2);
            $table->decimal('balance_before', 10, 2);
            $table->decimal('balance_after',  10, 2);
            $table->string('description')->nullable();
            $table->string('reference')->nullable()->index(); // ex: numéro commande
            $table->timestamps();

            $table->index(['reseller_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_wallet_transactions');
    }
};
