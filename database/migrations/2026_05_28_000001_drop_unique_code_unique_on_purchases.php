<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le code livré au client = le code MASTER de la carte (défini par l'admin),
 * comme l'API afrikard renvoie le code de la carte source. Plusieurs achats
 * d'une même carte partagent donc le même unique_code → on retire la contrainte
 * UNIQUE sur merchant_card_purchases.unique_code (on garde un index simple pour
 * la recherche au scan).
 *
 * qr_payload reste unique (chiffré avec l'ID de purchase, donc distinct par achat).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_card_purchases', function (Blueprint $table) {
            // Drop l'index unique (nom par défaut Laravel : table_column_unique)
            $table->dropUnique('merchant_card_purchases_unique_code_unique');
            // Index simple pour le lookup au comptoir
            $table->index('unique_code');
        });
    }

    public function down(): void
    {
        Schema::table('merchant_card_purchases', function (Blueprint $table) {
            $table->dropIndex(['unique_code']);
            $table->unique('unique_code');
        });
    }
};
