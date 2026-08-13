<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Sécurisation du secret d'une Carte Gabon.
 *
 * Avant : `pin_code` en clair en base, affiché en permanence au revendeur sur
 * `/vendor/local-cards/{id}`, et suffisant — avec le code — pour vider la carte
 * chez le commerçant. Un revendeur pouvait consommer la carte avant son client,
 * sans aucune trace permettant de le démontrer.
 *
 * Après :
 *  - `pin_hash` fait foi pour la vérification au comptoir du commerçant ;
 *  - `pin_code` ne sert plus qu'à la fenêtre de révélation, chiffré au repos et
 *    effacé dès que le client a vu son code ;
 *  - le code part chez le client par un lien WhatsApp à usage unique, dont seul
 *    le condensat est stocké — lire la base ne permet pas d'ouvrir le lien.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_card_purchases', function (Blueprint $table) {
            // `pin_code` était un varchar(4) taillé pour 4 chiffres en clair. Il
            // accueille désormais un chiffré : sans cet élargissement, la reprise
            // ci-dessous échoue (SQLSTATE 22001) dès qu'une carte non encore
            // remise existe en base.
            $table->string('pin_code', 255)->nullable()->change();

            $table->string('pin_hash')->nullable()->after('pin_code');

            // Lien de révélation envoyé au client.
            $table->string('reveal_token_hash')->nullable()->after('pin_hash');
            $table->timestamp('reveal_expires_at')->nullable()->after('reveal_token_hash');
            $table->timestamp('reveal_sent_at')->nullable()->after('reveal_expires_at');
            $table->string('reveal_sent_to', 30)->nullable()->after('reveal_sent_at');
            $table->unsignedTinyInteger('reveal_sends')->default(0)->after('reveal_sent_to');

            // Traçabilité de la remise : qui a vu le code, quand, par quel canal.
            $table->timestamp('revealed_at')->nullable()->after('reveal_sends');
            $table->string('revealed_ip', 45)->nullable()->after('revealed_at');
            $table->string('reveal_channel', 20)->nullable()->after('revealed_ip');
        });

        // Reprise de l'existant : condensat pour la vérification, puis chiffrement
        // du PIN en clair. Sans cette étape les cartes déjà vendues deviendraient
        // inutilisables chez le commerçant.
        DB::table('merchant_card_purchases')
            ->whereNotNull('pin_code')
            ->orderBy('id')
            ->select('id', 'pin_code', 'sold_by_reseller_at')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $clair = (string) $row->pin_code;
                    if ($clair === '') continue;

                    DB::table('merchant_card_purchases')->where('id', $row->id)->update([
                        'pin_hash' => Hash::make($clair),
                        // Les cartes déjà remises au client n'ont plus besoin du
                        // PIN en clair : il est effacé plutôt que chiffré.
                        'pin_code' => $row->sold_by_reseller_at ? null : Crypt::encryptString($clair),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Le PIN en clair n'est pas restaurable : `pin_code` reste chiffré ou nul.
        Schema::table('merchant_card_purchases', function (Blueprint $table) {
            $table->dropColumn([
                'pin_hash', 'reveal_token_hash', 'reveal_expires_at', 'reveal_sent_at',
                'reveal_sent_to', 'reveal_sends', 'revealed_at', 'revealed_ip', 'reveal_channel',
            ]);
        });
    }
};
