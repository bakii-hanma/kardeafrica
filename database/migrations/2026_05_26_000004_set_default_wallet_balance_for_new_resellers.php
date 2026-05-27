<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cagnotte initiale : 20 000 FCFA pour tout nouveau compte marchand
 * ===
 * Avant : wallet_balance default = 0 → tout nouveau compte démarrait sans aucun
 * fonds, devait attendre un transfert admin pour pouvoir vendre.
 *
 * Maintenant : 20 000 FCFA crédités à la création (= bootstrap permettant de
 * vendre quelques cartes Carte Gabon dès l'activation). Le marchand peut
 * recharger via Airtel Money depuis son espace.
 *
 * Backfill : on crédite les comptes ayant wallet_balance=0 ET commission_balance=0
 * (= comptes jamais utilisés). On NE touche PAS les comptes ayant déjà eu de
 * l'activité (commission_balance > 0 ou wallet_balance > 0).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Change le default DB pour les nouveaux INSERT
        DB::statement('ALTER TABLE resellers MODIFY wallet_balance DECIMAL(10,2) NOT NULL DEFAULT 20000');

        // Backfill : comptes vierges → cagnotte initiale
        DB::table('resellers')
            ->where('wallet_balance', 0)
            ->where('commission_balance', 0)
            ->update(['wallet_balance' => 20000]);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE resellers MODIFY wallet_balance DECIMAL(10,2) NOT NULL DEFAULT 0');
        // Pas de rollback du crédit : on ne sait pas distinguer les comptes
        // bootstrap des comptes ayant gagné 20k naturellement.
    }
};
