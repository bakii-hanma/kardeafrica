<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chiffrement des secrets de cartes dans `user_cards`.
 *
 * `card_code` et `pin` étaient stockés en clair. Une lecture de la base — sauvegarde
 * égarée, accès en lecture seule, injection SQL — suffisait à récupérer et dépenser
 * toutes les cartes livrées. Même exigence que `merchant_card_purchases.pin_code`,
 * chiffré depuis le 12 août : la clé vit dans l'environnement, jamais en base.
 *
 * Pas de condensat ici : contrairement à la Carte Gabon, personne ne « vérifie » ces
 * codes chez nous — ils sont consommés chez l'émetteur (Netflix, Apple…). Le seul
 * usage est l'affichage à leur propriétaire, qui exige un chiffrement réversible.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Un chiffré dépasse largement les colonnes d'origine.
        Schema::table('user_cards', function (Blueprint $table) {
            $table->text('card_code')->nullable()->change();
            $table->text('pin')->nullable()->change();
        });

        DB::table('user_cards')
            ->orderBy('id')
            ->select('id', 'card_code', 'pin')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $maj = [];

                    // Idempotence : une valeur déjà chiffrée ne doit pas l'être deux
                    // fois si la migration est rejouée sur une base partiellement
                    // traitée.
                    foreach (['card_code', 'pin'] as $col) {
                        $valeur = $row->{$col};

                        if ($valeur === null || $valeur === '' || self::dejaChiffre($valeur)) {
                            continue;
                        }

                        $maj[$col] = Crypt::encryptString($valeur);
                    }

                    if ($maj !== []) {
                        DB::table('user_cards')->where('id', $row->id)->update($maj);
                    }
                }
            });
    }

    public function down(): void
    {
        // Déchiffrement, sinon les colonnes deviendraient illisibles après retrait
        // du cast sur le modèle.
        DB::table('user_cards')
            ->orderBy('id')
            ->select('id', 'card_code', 'pin')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    $maj = [];

                    foreach (['card_code', 'pin'] as $col) {
                        if (self::dejaChiffre($row->{$col})) {
                            $maj[$col] = Crypt::decryptString($row->{$col});
                        }
                    }

                    if ($maj !== []) {
                        DB::table('user_cards')->where('id', $row->id)->update($maj);
                    }
                }
            });
    }

    private static function dejaChiffre(?string $valeur): bool
    {
        if ($valeur === null || $valeur === '') {
            return false;
        }

        $decode = json_decode(base64_decode($valeur, true) ?: '', true);

        return is_array($decode) && isset($decode['iv'], $decode['value'], $decode['mac']);
    }
};
