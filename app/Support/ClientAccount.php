<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ClientAccount
 * =============
 * Compte client identifié par son numéro WhatsApp.
 *
 * Un client qui achète une Carte Gabon au comptoir n'a ni compte, ni e-mail, ni
 * envie d'en créer un — mais il lui faut un endroit durable où retrouver son
 * code. Ce compte est donc créé pour lui, à partir du seul élément qu'il a déjà
 * donné : son numéro.
 *
 * TROIS RÈGLES QUI TIENNENT L'ENSEMBLE
 * ------------------------------------
 *  1. **Aucune création sur un numéro ambigu.** `Phone::accountKey()` refuse ce
 *     qu'il ne peut pas identifier de façon sûre ; on redemande plutôt que de
 *     rattacher la carte d'un client au compte d'un autre.
 *  2. **La recherche couvre toutes les écritures.** Les lignes antérieures à la
 *     normalisation portent encore `0XXXXXXXX` ou `+241…` : chercher la seule
 *     forme canonique créerait un doublon à chaque vente.
 *  3. **Le mot de passe est aléatoire et jamais communiqué.** Le compte ne
 *     s'ouvre que par un lien WhatsApp ou un OTP — il n'y a pas de secret à
 *     deviner, et aucun chemin d'authentification n'a à gérer un mot de passe nul.
 *
 * Le numéro n'est PAS considéré comme vérifié à la création : c'est l'ouverture
 * du lien reçu sur WhatsApp qui prouve la possession de la ligne.
 */
class ClientAccount
{
    public const VIA_COUNTER = 'comptoir';
    public const VIA_ONLINE  = 'en_ligne';

    /**
     * Compte existant correspondant à ce numéro, sous n'importe quelle écriture.
     */
    public static function find(?string $phone): ?User
    {
        $formes = Phone::candidates($phone);

        if ($formes === []) {
            return null;
        }

        return User::whereIn('phone', $formes)->orderBy('id')->first();
    }

    /**
     * Compte du client, créé si besoin.
     *
     * @return User|null  null si le numéro est trop ambigu pour servir de clé —
     *                    l'appelant doit alors redemander le numéro.
     */
    public static function findOrCreate(?string $phone, ?string $name = null, string $via = self::VIA_COUNTER): ?User
    {
        $cle = Phone::accountKey($phone);

        if ($cle === null) {
            return null;
        }

        return DB::transaction(function () use ($cle, $phone, $name, $via) {
            // Verrou sur la lecture : deux ventes simultanées au même client ne
            // doivent pas produire deux comptes.
            $formes = Phone::candidates($phone);

            $existant = User::whereIn('phone', $formes)->orderBy('id')->lockForUpdate()->first();

            if ($existant) {
                // Un nom donné au comptoir vaut mieux que « Client KardAfrica »,
                // mais ne doit jamais écraser un nom choisi par le client.
                if ($name && self::isAutoCreated($existant) && self::looksPlaceholder($existant->name)) {
                    $existant->forceFill(['name' => $name])->save();
                }

                return $existant;
            }

            $user = new User();
            $user->forceFill([
                'name'         => $name ?: 'Client KardAfrica',
                'email'        => null,
                // Aléatoire et jamais communiqué : le compte s'ouvre par lien
                // WhatsApp ou OTP, il n'y a pas de secret à deviner.
                'password'     => bcrypt(Str::random(64)),
                'phone'        => $cle,
                'role'         => 'user',
                'is_active'    => true,
                'created_via'  => $via,
            ])->save();

            Log::info('ClientAccount: compte créé sur numéro WhatsApp', [
                'user_id' => $user->id,
                'via'     => $via,
            ]);

            return $user;
        });
    }

    /** Le compte a-t-il été ouvert par KardAfrica plutôt que par le client ? */
    public static function isAutoCreated(User $user): bool
    {
        return $user->created_via !== null;
    }

    /**
     * Le client a-t-il pris possession de son compte ?
     * Un compte auto-créé dont le numéro n'est pas vérifié n'a encore jamais
     * été ouvert par son titulaire.
     */
    public static function isClaimed(User $user): bool
    {
        return $user->phone_verified_at !== null;
    }

    /**
     * Marque la possession du numéro : l'ouverture d'un lien reçu sur WhatsApp
     * prouve que le titulaire de la ligne est bien là.
     */
    public static function markClaimed(User $user): void
    {
        if ($user->phone_verified_at === null) {
            $user->forceFill(['phone_verified_at' => now()])->save();
        }
    }

    private static function looksPlaceholder(?string $name): bool
    {
        return $name === null
            || trim($name) === ''
            || in_array(trim($name), ['Client KardAfrica', 'Client comptoir', 'Client'], true);
    }
}
