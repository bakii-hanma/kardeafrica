<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 — vendor CRUD
 * ===
 * Quand "allow_custom_amount = false", min_amount et max_amount n'ont pas de
 * sens : on les set à NULL dans le controller. La table doit donc autoriser
 * NULL pour ces colonnes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_cards', function (Blueprint $table) {
            $table->decimal('min_amount', 12, 2)->nullable()->default(null)->change();
            $table->decimal('max_amount', 12, 2)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('merchant_cards', function (Blueprint $table) {
            $table->decimal('min_amount', 12, 2)->nullable(false)->default(2000)->change();
            $table->decimal('max_amount', 12, 2)->nullable(false)->default(500000)->change();
        });
    }
};
