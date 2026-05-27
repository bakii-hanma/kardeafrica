<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * wallet_recharges — recharges du wallet marchand via Airtel Money / E-Billing
 * ===
 * Le marchand a une cagnotte (wallet_balance). Quand elle est trop basse pour
 * vendre, il peut la recharger en payant via Airtel Money / Moov Money / carte
 * via futursowax E-Billing.
 *
 * Flow :
 *  1. POST /vendor/wallet/recharge/init  → crée pending + récupère portal_url
 *  2. Redirection vers portal E-Billing  → user paie via Mobile Money
 *  3. GET  /vendor/wallet/recharge/return → page de poll
 *  4. POST /vendor/wallet/recharge/finalize → vérifie + Reseller::credit() atomique
 *
 * Status :
 *  - pending   : init E-Billing OK, en attente du paiement
 *  - completed : paiement confirmé + wallet crédité
 *  - failed    : E-Billing rejette ou timeout
 *  - cancelled : abandon utilisateur (futur)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_recharges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained('resellers')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('external_reference', 100)->unique();
            $table->string('payment_method', 20)->default('ebilling'); // 'ebilling' | 'airtel' | 'moov'
            $table->string('status', 20)->default('pending');
            $table->string('bill_id', 100)->nullable();
            $table->text('portal_url')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['reseller_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_recharges');
    }
};
