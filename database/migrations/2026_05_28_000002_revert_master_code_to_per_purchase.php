<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Retour au modèle correct : le code/PIN/expiration sont générés À LA LIVRAISON
 * (un code unique par achat dans merchant_card_purchases), PAS sur la carte
 * template. Plusieurs clients peuvent acheter la même carte → chacun son code.
 *
 * 1. Drop unique_code / pin_code / expires_at de merchant_cards (master inutile)
 * 2. Ré-applique UNIQUE sur merchant_card_purchases.unique_code (après dédup
 *    éventuelle des codes partagés créés par la version précédente)
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop les colonnes master de merchant_cards
        Schema::table('merchant_cards', function (Blueprint $table) {
            if (Schema::hasColumn('merchant_cards', 'unique_code')) {
                $table->dropUnique('merchant_cards_unique_code_unique');
                $table->dropColumn('unique_code');
            }
            if (Schema::hasColumn('merchant_cards', 'pin_code')) {
                $table->dropColumn('pin_code');
            }
            if (Schema::hasColumn('merchant_cards', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
        });

        // 2. Dédup des unique_code partagés sur merchant_card_purchases avant
        //    de ré-appliquer la contrainte UNIQUE.
        $dupes = DB::table('merchant_card_purchases')
            ->select('unique_code')
            ->groupBy('unique_code')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('unique_code');

        foreach ($dupes as $code) {
            // Garde la 1ère purchase, régénère un code unique pour les autres
            $rows = DB::table('merchant_card_purchases')
                ->where('unique_code', $code)
                ->orderBy('id')
                ->pluck('id');

            foreach ($rows->slice(1) as $id) {
                do {
                    $new = (string) random_int(10000000, 99999999);
                } while (DB::table('merchant_card_purchases')->where('unique_code', $new)->exists());

                DB::table('merchant_card_purchases')->where('id', $id)->update(['unique_code' => $new]);
            }
        }

        // 3. Ré-applique UNIQUE (drop l'index simple posé par la migration précédente d'abord)
        Schema::table('merchant_card_purchases', function (Blueprint $table) {
            try { $table->dropIndex(['unique_code']); } catch (\Throwable $e) {}
            $table->unique('unique_code');
        });
    }

    public function down(): void
    {
        Schema::table('merchant_card_purchases', function (Blueprint $table) {
            $table->dropUnique('merchant_card_purchases_unique_code_unique');
            $table->index('unique_code');
        });

        Schema::table('merchant_cards', function (Blueprint $table) {
            $table->string('unique_code', 8)->nullable()->unique()->after('visual_url');
            $table->string('pin_code', 4)->nullable()->after('unique_code');
            $table->date('expires_at')->nullable()->after('pin_code');
        });
    }
};
