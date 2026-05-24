<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Module « Carte Gabon »
 * ===
 * Table merchant_cards : templates de cartes-cadeaux propriétaires créées par
 * les marchands (≠ ResellerCard qui sont des cartes du catalogue afrikard
 * revendues par le vendor).
 *
 * Un MerchantCard est un MODÈLE de carte (ex: "Carte Hôtel Le Méridien
 * Libreville", denominations 5 000 / 10 000 / 25 000 / 50 000 FCFA, visuel,
 * validité 12 mois). Quand un client l'achète, ça crée une MerchantCardPurchase
 * (table suivante).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_cards', function (Blueprint $table) {
            $table->id();

            // FK vers le marchand (= reseller approuvé en mode "merchant")
            $table->foreignId('reseller_id')->constrained('resellers')->cascadeOnDelete();

            // Identité de la carte
            $table->string('name', 150);                       // "Carte Restaurant Le Phare"
            $table->text('description')->nullable();
            $table->string('category', 40)->nullable();        // optionnel, ex: "menu_diner"
            $table->string('visual_url', 500);                 // URL Supabase/S3 du visuel uploadé

            // Denominations : JSON array d'entiers en FCFA
            // ex: [5000, 10000, 25000, 50000, 100000]
            $table->json('denominations');
            $table->boolean('allow_custom_amount')->default(true);
            $table->decimal('min_amount', 12, 2)->default(2000);
            $table->decimal('max_amount', 12, 2)->default(500000);
            $table->string('currency', 3)->default('XAF');

            // Validité de la carte achetée (en mois, à partir de la date d'achat)
            $table->unsignedSmallInteger('validity_months')->default(12);
            $table->text('terms_conditions')->nullable();

            // Workflow d'activation (le marchand crée → admin valide → is_active=true)
            $table->boolean('is_active')->default(false);
            $table->timestamp('activated_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Stats matérialisées (mises à jour par observer/transaction)
            $table->unsignedInteger('total_sold')->default(0);
            $table->decimal('total_revenue', 14, 2)->default(0);

            $table->timestamps();

            // Index — un marchand a typiquement 1-5 cartes actives, donc index simple
            $table->index(['reseller_id', 'is_active']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_cards');
    }
};
