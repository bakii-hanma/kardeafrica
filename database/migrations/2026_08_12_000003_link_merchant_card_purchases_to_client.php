<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rattache une Carte Gabon au compte de son acheteur.
 *
 * Le miroir `UserCard` — utilisé pour les achats en ligne — ne convient pas ici :
 * sa colonne `order_id` est NOT NULL et une vente au comptoir n'a pas de
 * commande. Le lien direct évite en outre de recopier le secret dans
 * `user_cards.pin`, qui est stocké en clair.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_card_purchases', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('reseller_id')
                ->constrained('users')->nullOnDelete();
        });

        // Reprise : les achats issus d'une commande en ligne appartiennent déjà
        // à un compte, il suffit de matérialiser le lien.
        DB::statement('
            UPDATE merchant_card_purchases p
            JOIN orders o ON o.id = p.order_id
            SET p.user_id = o.user_id
            WHERE p.order_id IS NOT NULL AND p.user_id IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('merchant_card_purchases', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
