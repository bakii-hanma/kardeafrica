<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reversements aux commerçants.
 *
 * `owner_net_amount` s'accumulait depuis toujours sans qu'aucune trace ne dise
 * ce qui avait été réellement versé. Le commerçant voyait un cumul et ne pouvait
 * pas répondre à « combien KardAfrica me doit aujourd'hui ? ». Les revendeurs
 * ont `reseller_cash_remittances` depuis le début ; les commerçants n'avaient
 * rien.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_owner_id')->constrained('card_owners')->cascadeOnDelete();

            $table->decimal('amount', 14, 2);
            $table->string('method', 20)->default('mobile_money');   // mobile_money | especes | virement
            $table->string('reference', 120)->nullable();            // n° de transaction Airtel/Moov
            $table->text('notes')->nullable();

            // Qui a enregistré le versement — un mouvement d'argent sans auteur
            // n'est pas auditable.
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('settled_at');
            $table->timestamps();

            $table->index(['card_owner_id', 'settled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_settlements');
    }
};
