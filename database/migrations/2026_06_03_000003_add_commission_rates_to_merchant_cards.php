<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Commissions Carte Gabon.
 *
 *  - Sur le template (merchant_cards) : pourcentages configurables par l'admin
 *    (admin = part KardAfrica, vendor = part boutique qui revend).
 *  - Sur chaque achat (merchant_card_purchases) : snapshot des montants en FCFA
 *    calculés au moment de la vente, plus le net qui revient au propriétaire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_cards', function (Blueprint $table) {
            $table->decimal('admin_commission_rate', 5, 2)->default(0)->after('total_revenue');
            $table->decimal('vendor_commission_rate', 5, 2)->default(0)->after('admin_commission_rate');
        });

        Schema::table('merchant_card_purchases', function (Blueprint $table) {
            $table->decimal('admin_commission_amount', 12, 2)->default(0)->after('amount');
            $table->decimal('vendor_commission_amount', 12, 2)->default(0)->after('admin_commission_amount');
            $table->decimal('owner_net_amount', 12, 2)->default(0)->after('vendor_commission_amount');
        });
    }

    public function down(): void
    {
        Schema::table('merchant_cards', function (Blueprint $table) {
            $table->dropColumn(['admin_commission_rate', 'vendor_commission_rate']);
        });
        Schema::table('merchant_card_purchases', function (Blueprint $table) {
            $table->dropColumn(['admin_commission_amount', 'vendor_commission_amount', 'owner_net_amount']);
        });
    }
};
