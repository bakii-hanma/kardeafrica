<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 — Parité UX avec les cartes afrikard
 * ===
 * Les cartes afrikard ont un code + un PIN. Les cartes marchand n'avaient qu'un
 * code 8 chiffres. On ajoute un PIN 4 chiffres pour :
 *  - améliorer la sécurité au comptoir (le code seul peut être deviné par
 *    brute force, le PIN ajoute 10 000 combinaisons supplémentaires)
 *  - homogénéiser l'UI avec /cards (qui affiche code + PIN)
 *
 * Nullable pour les achats existants ; les nouveaux en généreront un.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_card_purchases', function (Blueprint $table) {
            $table->string('pin_code', 4)->nullable()->after('unique_code');
        });
    }

    public function down(): void
    {
        Schema::table('merchant_card_purchases', function (Blueprint $table) {
            $table->dropColumn('pin_code');
        });
    }
};
