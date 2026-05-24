<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Module « Carte Gabon »
 * ===
 * Table merchant_users : employés autorisés à scanner/encaisser les cartes au
 * comptoir. Le PIN est hashé avec bcrypt (jamais en clair, cf spec).
 *
 * On crée AVANT merchant_card_redemptions parce que redemptions y fait
 * référence (merchant_user_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained('resellers')->cascadeOnDelete();

            $table->string('name', 100);
            $table->string('phone', 30)->nullable();

            // Rôles : owner = le marchand lui-même, manager = peut tout sauf changer KYC,
            // cashier = peut juste scanner/encaisser.
            $table->string('role', 20)->default('cashier'); // 'owner' | 'manager' | 'cashier'

            // PIN 4 chiffres hashé bcrypt (cost 10 par défaut Laravel)
            // Saisi à chaque encaissement pour confirmation
            $table->string('pin_code_hash');

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();

            $table->timestamps();

            $table->index(['reseller_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_users');
    }
};
