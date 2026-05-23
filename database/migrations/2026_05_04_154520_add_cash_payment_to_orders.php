<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Paiement physique chez un vendeur Kardafrica :
            // - cash_reseller_id : le vendeur qui doit encaisser le client
            // - cash_lock_expires_at : deadline avant libération automatique du lock
            // - cash_confirmation_code : code à 6 chiffres que le client donne au vendeur
            $table->foreignId('cash_reseller_id')
                ->nullable()
                ->after('payment_method')
                ->constrained('resellers')
                ->nullOnDelete();
            $table->timestamp('cash_lock_expires_at')->nullable()->after('cash_reseller_id');
            $table->string('cash_confirmation_code', 6)->nullable()->after('cash_lock_expires_at');

            $table->index(['cash_reseller_id', 'payment_status']);
            $table->index('cash_lock_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['cash_reseller_id', 'payment_status']);
            $table->dropIndex(['cash_lock_expires_at']);
            $table->dropConstrainedForeignId('cash_reseller_id');
            $table->dropColumn(['cash_lock_expires_at', 'cash_confirmation_code']);
        });
    }
};
