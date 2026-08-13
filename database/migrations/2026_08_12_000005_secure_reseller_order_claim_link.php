<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sécurisation du lien de récupération des cartes digitales.
 *
 * Faille jumelle de celle des Cartes Gabon, sur des montants six fois
 * supérieurs : `/claim/{token}` était **permanent et réutilisable**, et le
 * revendeur avait le QR sous les yeux sur son écran de commande. Rien ne
 * l'empêchait de le scanner lui-même, avant ou après son client.
 *
 * DIFFÉRENCE DE FOND AVEC LA CARTE GABON
 * --------------------------------------
 * Le lien d'une Carte Gabon part sur le WhatsApp du client : l'ouvrir prouve la
 * possession de la ligne. Un QR affiché sur l'écran du revendeur ne prouve
 * strictement rien. La remise passe donc, elle aussi, par WhatsApp — et le lien
 * devient à usage unique, expirant, avec seul son condensat en base.
 *
 * Les jetons existants sont effacés : ce sont précisément les liens éternels que
 * ce chantier supprime.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reseller_orders', function (Blueprint $table) {
            $table->string('claim_token_hash')->nullable()->after('claim_token');
            $table->timestamp('claim_expires_at')->nullable()->after('claim_token_hash');
            $table->timestamp('claim_sent_at')->nullable()->after('claim_expires_at');
            $table->string('claim_sent_to', 30)->nullable()->after('claim_sent_at');
            $table->unsignedTinyInteger('claim_sends')->default(0)->after('claim_sent_to');
            $table->string('claim_channel', 20)->nullable()->after('claim_sends');
            $table->string('claimed_ip', 45)->nullable()->after('claim_channel');
            $table->foreignId('user_id')->nullable()->after('reseller_id')
                ->constrained('users')->nullOnDelete();
        });

        // L'ordre compte : `claim_token` est NOT NULL à l'origine. Vider les
        // valeurs avant d'assouplir la colonne échoue dès qu'une commande
        // existe — donc partout sauf sur une base vide.
        Schema::table('reseller_orders', function (Blueprint $table) {
            $table->string('claim_token', 36)->nullable()->change();
        });

        // Les liens permanents déjà distribués cessent d'ouvrir quoi que ce soit.
        DB::table('reseller_orders')->update(['claim_token' => null]);
    }

    public function down(): void
    {
        Schema::table('reseller_orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn([
                'claim_token_hash', 'claim_expires_at', 'claim_sent_at', 'claim_sent_to',
                'claim_sends', 'claim_channel', 'claimed_ip', 'user_id',
            ]);
        });
    }
};
