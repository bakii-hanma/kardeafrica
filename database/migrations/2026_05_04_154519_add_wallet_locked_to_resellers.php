<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            // Montant temporairement réservé sur le wallet de vente — pour les commandes
            // cash en attente de confirmation. wallet_balance reste tel quel ; le solde
            // dispo réel = wallet_balance - wallet_locked.
            $table->decimal('wallet_locked', 12, 2)->default(0)->after('commission_balance');
        });
    }

    public function down(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            $table->dropColumn('wallet_locked');
        });
    }
};
