<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * H4 — Double livraison afrikard sur retry (volet cartes)
 * ===
 * `checkout_card_id` est l'identifiant UNIQUE de la carte côté afrikard.
 * Un rejeu de saveCards() (retry de job après crash post-appel) recréait la
 * même carte en double dans user_cards / reseller_cards.
 *
 * 1. Dédoublonnage : par checkout_card_id non-null, on garde la ligne au plus
 *    petit id (les doublons sont des copies exactes du même code de carte).
 * 2. Contrainte UNIQUE — colonne nullable : les NULL multiples restent
 *    autorisés (cartes marchand locales, anciennes lignes sans id afrikard).
 *
 * Uniquement DB::table ici (règle M14).
 */
return new class extends Migration
{
    /**
     * Dédoublonne une table sur checkout_card_id (garde le plus petit id).
     */
    private function dedupe(string $tableName): void
    {
        $dupes = DB::table($tableName)
            ->select('checkout_card_id')
            ->whereNotNull('checkout_card_id')
            ->groupBy('checkout_card_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('checkout_card_id');

        foreach ($dupes as $checkoutCardId) {
            $ids = DB::table($tableName)
                ->where('checkout_card_id', $checkoutCardId)
                ->orderBy('id')
                ->pluck('id');

            $deleteIds = $ids->slice(1)->values()->all();
            DB::table($tableName)->whereIn('id', $deleteIds)->delete();

            Log::warning("H4 dedup: doublons {$tableName} supprimés", [
                'checkout_card_id' => $checkoutCardId,
                'kept_id'          => $ids->first(),
                'deleted_ids'      => $deleteIds,
            ]);
        }
    }

    public function up(): void
    {
        // 1. user_cards
        $this->dedupe('user_cards');
        Schema::table('user_cards', function (Blueprint $table) {
            $table->unique('checkout_card_id');
        });

        // 2. reseller_cards (même colonne, même défaut dans Vendor/SaleController::saveCards)
        if (Schema::hasColumn('reseller_cards', 'checkout_card_id')) {
            $this->dedupe('reseller_cards');
            Schema::table('reseller_cards', function (Blueprint $table) {
                $table->unique('checkout_card_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('user_cards', function (Blueprint $table) {
            $table->dropUnique(['checkout_card_id']);
        });
        if (Schema::hasColumn('reseller_cards', 'checkout_card_id')) {
            Schema::table('reseller_cards', function (Blueprint $table) {
                $table->dropUnique(['checkout_card_id']);
            });
        }
        // Les lignes dédoublonnées ne sont pas restaurées (comme tout dedup).
    }
};
