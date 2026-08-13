<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('shopping_cart', function (Blueprint $table) use ($isSqlite) {
            // MySQL/MariaDB refusent de dropper un index utilisé par une FK
            // (erreur 1553). Ici la FK user_id s'appuie sur l'index composite
            // (user_id, card_id) : on doit donc supprimer la FK user_id AVANT
            // ses index, puis la recréer plus bas sur le nouvel index
            // (user_id, product_id). L'ancien ordre cassait toute installation
            // fraîche. (Sur SQLite : pas de FK à gérer, seul le drop de colonne
            // compte.)
            if (!$isSqlite) {
                // La FK user_id s'appuie sur l'index composite (user_id, card_id) ;
                // la FK card_id sur son propre index — les deux doivent partir
                // avant de dropper les index / la colonne card_id.
                $table->dropForeign(['user_id']);
                $table->dropForeign(['card_id']);
            }

            $table->dropIndex(['user_id', 'card_id']);
            $table->dropIndex(['session_id', 'card_id']);

            $table->dropColumn('card_id');
        });

        Schema::table('shopping_cart', function (Blueprint $table) use ($isSqlite) {
            // Add fields for API products
            $table->string('product_id')->after('session_id');
            $table->string('name')->after('product_id');
            $table->decimal('price', 10, 2)->after('name');
            $table->string('image_url')->nullable()->after('price');

            $table->index(['user_id', 'product_id']);
            $table->index(['session_id', 'product_id']);

            // Recrée la FK user_id (l'index (user_id, product_id) ci-dessus la
            // soutient désormais).
            if (!$isSqlite) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shopping_cart', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'product_id']);
            $table->dropIndex(['session_id', 'product_id']);
            $table->dropColumn(['product_id', 'name', 'price', 'image_url']);
            $table->foreignId('card_id')->constrained()->onDelete('cascade');
            $table->index(['user_id', 'card_id']);
            $table->index(['session_id', 'card_id']);
        });
    }
};
