<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 — vendor CRUD
 * ===
 * Le visuel est facultatif à la création (le marchand peut créer un brouillon,
 * uploader le visuel plus tard). L'admin l'exigera avant de valider, mais en
 * DB on autorise NULL pour ne pas bloquer la création initiale.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_cards', function (Blueprint $table) {
            $table->string('visual_url', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('merchant_cards', function (Blueprint $table) {
            $table->string('visual_url', 500)->nullable(false)->change();
        });
    }
};
