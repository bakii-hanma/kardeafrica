<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vente de cartes locales (Carte Gabon) PAR LES REVENDEURS — activation gated.
 * ===
 * Un revendeur réserve une carte (status='inactive', payment='pending') puis la
 * « récupère » sur son dashboard : action atomique qui débite son wallet
 * (montant − commission revendeur), active le code et prouve la vente à
 * KardAfrica. Tant que non récupérée, le code est inerte (refusé au comptoir).
 *
 * `reseller_id` avait été supprimé (2026_05_29_000002) quand les cartes locales
 * sont devenues un catalogue admin global ; on le réintroduit NULLABLE :
 *  - NULL  = achat client en ligne (flux historique, carte née active)
 *  - non-NULL = vente au comptoir par ce revendeur (carte née inactive)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_card_purchases', function (Blueprint $table) {
            $table->foreignId('reseller_id')
                  ->nullable()
                  ->after('order_item_id')
                  ->constrained('resellers')
                  ->nullOnDelete();

            // Horodatage de la récupération (= preuve de vente + activation).
            $table->timestamp('sold_by_reseller_at')->nullable()->after('paid_at');

            $table->index(['reseller_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('merchant_card_purchases', function (Blueprint $table) {
            $table->dropForeign(['reseller_id']);
            $table->dropIndex(['reseller_id', 'status']);
            $table->dropColumn(['reseller_id', 'sold_by_reseller_at']);
        });
    }
};
