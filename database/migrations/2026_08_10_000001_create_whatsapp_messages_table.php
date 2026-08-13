<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal des messages WhatsApp (WHAPI) — sortants ET entrants.
 *
 * Sert de socle unique à tout l'écosystème WhatsApp :
 *  - traçabilité (qui/quoi/quand + statut delivered/read),
 *  - idempotence via `dedup_key` (ex. une seule notif « code prêt » par commande),
 *  - envoi différé/programmé via `scheduled_at`,
 *  - respect du consentement via `category` (transactional/support/marketing…).
 *
 * ⚠️ Politique de sécurité : le corps d'un message SORTANT ne doit JAMAIS
 * contenir un code/PIN de carte (règle produit — on envoie un lien sécurisé).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();

            // 'out' = envoyé par KardAfrica ; 'in' = reçu d'un contact.
            $table->string('direction', 3)->default('out');

            // Numéro du contact (E.164 sans +, ex. 24106871309).
            $table->string('phone', 30);

            // text | image | document | video | audio | interactive | list | template | product
            $table->string('type', 20)->default('text');

            // Catégorie fonctionnelle (pilote l'opt-in + l'analytics).
            // transactional | support | marketing | otp | ops
            $table->string('category', 20)->default('transactional');

            // Corps texte (aperçu pour l'UI admin). JAMAIS de code/PIN pour un sortant.
            $table->text('body')->nullable();

            // Charge utile brute additionnelle (media url, boutons, sections…).
            $table->json('payload')->nullable();

            // queued | sent | delivered | read | failed | received
            $table->string('status', 12)->default('queued');

            // Identifiant du message renvoyé par WHAPI (pour rapprocher les statuts).
            $table->string('provider_message_id')->nullable();

            // Dernier message d'erreur (diagnostic, sans body brut du provider).
            $table->string('error')->nullable();

            // Rattachement métier optionnel (ex. 'order_status' + order id) — dédoublonnage.
            $table->string('context_type', 40)->nullable();
            $table->string('context_id', 64)->nullable();

            // Clé d'idempotence : empêche un doublon (ex. 'order-ready-123').
            $table->string('dedup_key')->nullable();

            // Programmation : NULL = envoi immédiat ; sinon envoi après cette date.
            $table->timestamp('scheduled_at')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index(['phone', 'direction']);
            $table->index('status');
            $table->index('scheduled_at');
            $table->index('provider_message_id');
            $table->index(['context_type', 'context_id']);
            // NB: MySQL/MariaDB/SQLite autorisent plusieurs NULL dans un index unique,
            // donc seuls les messages porteurs d'une dedup_key sont dédoublonnés.
            $table->unique('dedup_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
