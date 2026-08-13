<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H4 — Double livraison afrikard sur retry
 * ===
 * `delivery_requested_at` = marqueur posé (sous verrou) juste AVANT l'appel
 * afrikard POST /orders/checkout. Si l'appel réussit côté fournisseur mais que
 * la suite échoue (timeout, crash worker), le retry du job retrouve ce marqueur
 * avec un status non complété → il NE rappelle PAS afrikard (sinon la carte
 * serait facturée deux fois) et exige une réconciliation manuelle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'delivery_requested_at')) {
                $table->timestamp('delivery_requested_at')->nullable()->after('completed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'delivery_requested_at')) {
                $table->dropColumn('delivery_requested_at');
            }
        });
    }
};
