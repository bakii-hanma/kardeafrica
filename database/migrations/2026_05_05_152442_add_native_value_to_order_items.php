<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stocke la valeur native (EUR, USD, etc.) au moment de la création de
        // la commande, pour qu'on n'ait PLUS besoin de relookup le catalogue
        // afrikard au moment de la livraison.
        Schema::table('reseller_order_items', function (Blueprint $table) {
            $table->decimal('native_value', 12, 4)->nullable()->after('unit_price');
            $table->string('native_currency', 8)->nullable()->after('native_value');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('native_value', 12, 4)->nullable()->after('unit_price');
            $table->string('native_currency', 8)->nullable()->after('native_value');
        });
    }

    public function down(): void
    {
        Schema::table('reseller_order_items', function (Blueprint $table) {
            $table->dropColumn(['native_value', 'native_currency']);
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['native_value', 'native_currency']);
        });
    }
};
