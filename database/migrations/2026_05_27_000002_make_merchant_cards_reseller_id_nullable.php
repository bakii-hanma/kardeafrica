<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot architectural : les cartes locales (merchant_cards) sont désormais créées
 * directement par l'admin et ne sont plus liées à un marchand spécifique. Elles
 * forment un catalogue global. reseller_id devient nullable.
 *
 * Note : reseller_id reste sur merchant_card_purchases pour tracer quelle
 * boutique a (le cas échéant) facilité la vente — mais c'est optionnel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_cards', function (Blueprint $table) {
            // Drop la FK avant de modifier (MySQL n'accepte pas modify avec FK)
            $table->dropForeign(['reseller_id']);
            $table->unsignedBigInteger('reseller_id')->nullable()->change();
            // Recrée la FK avec onDelete set null pour ne pas casser si un marchand est supprimé
            $table->foreign('reseller_id')->references('id')->on('resellers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('merchant_cards', function (Blueprint $table) {
            $table->dropForeign(['reseller_id']);
            $table->unsignedBigInteger('reseller_id')->nullable(false)->change();
            $table->foreign('reseller_id')->references('id')->on('resellers')->cascadeOnDelete();
        });
    }
};
