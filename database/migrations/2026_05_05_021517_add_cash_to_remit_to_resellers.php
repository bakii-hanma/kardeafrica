<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            // Cumul du cash physique encaissé par le vendeur sur des ventes "cash"
            // que le vendeur doit reverser à KardAfrica (via E-Billing). Ce cash
            // ne lui appartient PAS — c'est le revenu des cartes vendues.
            // À chaque ventecashConfirm() : cash_to_remit += subtotal
            // À chaque remise validée par E-Billing : cash_to_remit -= montant
            //   ET wallet_balance += montant (le float est reconstitué)
            $table->decimal('cash_to_remit', 12, 2)->default(0)->after('wallet_locked');
        });
    }

    public function down(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            $table->dropColumn('cash_to_remit');
        });
    }
};
