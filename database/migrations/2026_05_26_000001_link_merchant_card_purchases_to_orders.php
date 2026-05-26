<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 — Paiement intégré au checkout existant
 * ===
 * Quand un client achète une carte-cadeau marchand depuis /gabon, le flow réutilise
 * le checkout kardafrica (Order + OrderItem + futursowax). À la fin de
 * ProcessCheckoutJob, on crée une MerchantCardPurchase LOCALEMENT (au lieu d'appeler
 * l'API afrikard). On la lie à l'Order pour pouvoir afficher les codes dans la
 * vue order detail + permettre les remboursements.
 *
 * Nullable car les achats existants ne viendront pas tous d'une Order (ex: achat
 * direct futur sans compte user).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_card_purchases', function (Blueprint $table) {
            $table->foreignId('order_id')
                  ->nullable()
                  ->after('reseller_id')
                  ->constrained('orders')
                  ->nullOnDelete();

            $table->foreignId('order_item_id')
                  ->nullable()
                  ->after('order_id')
                  ->constrained('order_items')
                  ->nullOnDelete();

            $table->index(['order_id']);
        });
    }

    public function down(): void
    {
        Schema::table('merchant_card_purchases', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['order_item_id']);
            $table->dropIndex(['order_id']);
            $table->dropColumn(['order_id', 'order_item_id']);
        });
    }
};
