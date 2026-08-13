<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Champs apportés par le catalogue Daywatch (api.daywatch.online).
 *
 * Additif et réversible : aucune colonne existante n'est touchée. `plan_id` est
 * la clé de rapprochement avec l'API — sans elle, une resynchronisation
 * créerait des doublons à chaque changement de nom de formule.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daywatch_products', function (Blueprint $table) {
            $table->unsignedInteger('plan_id')->nullable()->unique()->after('id');
            // Prix barré : sans lui, la remise annoncée par Daywatch (jusqu'à
            // 30 %) serait invisible en boutique.
            $table->unsignedInteger('original_price_xaf')->nullable()->after('price_xaf');
            $table->unsignedTinyInteger('max_profiles')->nullable()->after('features');
            $table->unsignedTinyInteger('max_devices')->nullable()->after('max_profiles');
            $table->string('image_back_url')->nullable()->after('image_url');
            $table->timestamp('synced_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('daywatch_products', function (Blueprint $table) {
            $table->dropUnique(['plan_id']);
            $table->dropColumn(['plan_id', 'original_price_xaf', 'max_profiles',
                                'max_devices', 'image_back_url', 'synced_at']);
        });
    }
};
