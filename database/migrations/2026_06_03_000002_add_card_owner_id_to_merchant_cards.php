<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lien carte locale → propriétaire. Nullable pour préserver les cartes
 * existantes du catalogue admin global (legacy), mais le formulaire admin
 * exigera désormais un propriétaire pour toute nouvelle carte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_cards', function (Blueprint $table) {
            $table->foreignId('card_owner_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('card_owners')
                  ->nullOnDelete();

            $table->index(['card_owner_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('merchant_cards', function (Blueprint $table) {
            $table->dropForeign(['card_owner_id']);
            $table->dropIndex(['card_owner_id', 'is_active']);
            $table->dropColumn('card_owner_id');
        });
    }
};
