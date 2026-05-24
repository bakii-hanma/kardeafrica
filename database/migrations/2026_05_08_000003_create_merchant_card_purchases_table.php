<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Module « Carte Gabon »
 * ===
 * Table merchant_card_purchases : représente UN achat client d'une carte-cadeau
 * marchand. Chaque purchase est unique, identifié par :
 *  - `unique_code` : 8 chiffres pour saisie manuelle au comptoir
 *  - `qr_payload`  : JWT signé contenant {purchase_id, merchant_id, iat}
 *                    (NE contient JAMAIS le solde — toujours requêter la DB
 *                     au moment du redeem)
 *
 * Le `remaining_balance` permet l'utilisation partielle (ex: carte 50 000 utilisée
 * deux fois 20 000 + 30 000). Toutes les MAJ du balance passent par une
 * transaction Postgres/MySQL SELECT ... FOR UPDATE pour éviter le double-débit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_card_purchases', function (Blueprint $table) {
            $table->id();

            // FKs
            $table->foreignId('merchant_card_id')->constrained('merchant_cards')->cascadeOnDelete();
            $table->foreignId('reseller_id')->constrained('resellers')->cascadeOnDelete();

            // Identifiants uniques pour vérification (cf spec : QR + code 8 chiffres)
            $table->string('unique_code', 8)->unique();        // ex: "47291634"
            $table->string('qr_payload', 500)->unique();        // JWT signé

            // Acheteur (= celui qui paie)
            $table->string('buyer_name', 150);
            $table->string('buyer_phone', 30);
            $table->string('buyer_email', 150)->nullable();

            // Destinataire (optionnel = "offrir à un proche")
            $table->string('recipient_name', 150)->nullable();
            $table->string('recipient_phone', 30)->nullable();
            $table->text('recipient_message')->nullable();

            // Montants & solde
            $table->decimal('amount', 12, 2);                  // Montant total chargé sur la carte
            $table->decimal('remaining_balance', 12, 2);       // Solde encore utilisable
            $table->string('currency', 3)->default('XAF');

            // Paiement (réutilise futursowax = même flow que ResellerOrder)
            $table->string('payment_method', 20)->nullable();  // 'airtel' | 'moov' | 'ebilling'
            $table->string('payment_status', 20)->default('pending'); // pending|paid|failed|refunded
            $table->string('payment_ref', 100)->nullable();
            $table->string('ebilling_transaction_id', 100)->nullable();

            // Statut de la carte (cycle de vie)
            // inactive (= avant paiement) → active → partially_used → fully_used
            //                                       → expired | cancelled
            $table->string('status', 30)->default('inactive');

            // Canaux de livraison choisis par l'acheteur (lien wa.me + mail)
            $table->json('delivery_channel')->nullable(); // ["whatsapp","email"]
            $table->timestamp('delivered_at')->nullable();

            // Échéances
            $table->timestamp('expires_at');                   // = now() + validity_months
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            // ===== Index ciblés =====
            // - scan QR : recherche rapide par qr_payload (déjà unique)
            // - scan code : idem unique_code (déjà unique)
            // - vue marchand : liste ses cartes vendues
            $table->index(['reseller_id', 'status']);
            $table->index(['merchant_card_id', 'status']);
            $table->index('expires_at'); // pour le job qui expire les cartes
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_card_purchases');
    }
};
