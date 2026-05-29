<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les cartes locales sont désormais un catalogue admin global (reseller_id null).
 * Quand un client achète, la MerchantCardPurchase hérite de reseller_id = null,
 * mais la colonne était NOT NULL → l'insertion échouait silencieusement
 * (try/catch) et la carte n'était jamais livrée.
 *
 * On rend reseller_id nullable sur merchant_card_purchases ET
 * merchant_card_redemptions (cohérence pour le futur scan au comptoir).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_card_purchases', function (Blueprint $table) {
            $table->dropForeign(['reseller_id']);
            $table->unsignedBigInteger('reseller_id')->nullable()->change();
            $table->foreign('reseller_id')->references('id')->on('resellers')->nullOnDelete();
        });

        Schema::table('merchant_card_redemptions', function (Blueprint $table) {
            $table->dropForeign(['reseller_id']);
            $table->unsignedBigInteger('reseller_id')->nullable()->change();
            $table->foreign('reseller_id')->references('id')->on('resellers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('merchant_card_purchases', function (Blueprint $table) {
            $table->dropForeign(['reseller_id']);
            $table->unsignedBigInteger('reseller_id')->nullable(false)->change();
            $table->foreign('reseller_id')->references('id')->on('resellers')->cascadeOnDelete();
        });

        Schema::table('merchant_card_redemptions', function (Blueprint $table) {
            $table->dropForeign(['reseller_id']);
            $table->unsignedBigInteger('reseller_id')->nullable(false)->change();
            $table->foreign('reseller_id')->references('id')->on('resellers')->cascadeOnDelete();
        });
    }
};
