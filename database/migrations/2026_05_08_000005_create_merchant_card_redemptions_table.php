<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Module « Carte Gabon »
 * ===
 * Table merchant_card_redemptions : journal d'utilisation des cartes.
 * Chaque entrée = un débit (total ou partiel) sur le solde d'une carte achetée.
 *
 * IDEMPOTENCE & ANTI-DOUBLE-DEBIT (spec §SÉCURITÉ #2) :
 * Au moment du redeem, le controller fait DB::transaction + select(...)->lockForUpdate()
 * sur le purchase pour empêcher deux encaissements concurrents (deux scans
 * simultanés du même QR par deux caissiers, par exemple) de doubler le débit.
 *
 * balance_before/balance_after sont snapshotés à l'insertion pour avoir un audit
 * trail clair (ne pas dépendre uniquement du purchase.remaining_balance courant).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_card_redemptions', function (Blueprint $table) {
            $table->id();

            // FKs
            $table->foreignId('merchant_card_purchase_id')
                  ->constrained('merchant_card_purchases')
                  ->cascadeOnDelete();
            $table->foreignId('reseller_id')->constrained('resellers')->cascadeOnDelete();
            // Quel employé a fait le scan (nullable si owner direct)
            $table->foreignId('merchant_user_id')
                  ->nullable()
                  ->constrained('merchant_users')
                  ->nullOnDelete();

            // Montant débité de la carte (≤ balance courant)
            $table->decimal('amount_used',    12, 2);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after',  12, 2);

            // Métadonnées
            $table->string('scan_method', 10);                 // 'qr' | 'code'
            $table->string('location', 150)->nullable();       // libre, ex: "Bar du lobby"
            $table->text('notes')->nullable();

            $table->timestamp('redeemed_at')->useCurrent();
            $table->timestamps();

            // ===== Index =====
            // - Liste des utilisations d'une carte : par purchase_id
            // - Stats marchand : ses redemptions du jour/mois
            $table->index('merchant_card_purchase_id', 'mcr_purchase_idx');
            $table->index(['reseller_id', 'redeemed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_card_redemptions');
    }
};
