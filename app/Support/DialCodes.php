<?php

namespace App\Support;

/**
 * DialCodes
 * =========
 * Indicatifs proposés à la saisie d'un numéro de téléphone.
 *
 * Exister est le point : tant que le pays reste implicite, un numéro saisi sans
 * indicatif est indevinable — « 0612345678 » peut être une ligne française
 * comme une saisie locale mal formée. Le sélecteur supprime l'ambiguïté à la
 * source, ce qu'aucune heuristique ne peut faire après coup (cf. `Phone`).
 *
 * L'ordre n'est pas alphabétique : le Gabon d'abord, puis la CEMAC et les pays
 * d'où viennent réellement les expatriés et la diaspora. Un vendeur au comptoir
 * ne doit pas faire défiler 200 lignes pour trouver le cas courant.
 */
class DialCodes
{
    public const DEFAULT = 'GA';

    /**
     * @var array<string, array{name:string, code:string, flag:string, length:int|null}>
     *      `length` = nombre de chiffres d'abonné attendus, null si variable.
     */
    public const COUNTRIES = [
        // ---- Gabon, cas par défaut ----
        'GA' => ['name' => 'Gabon',                   'code' => '241', 'flag' => '🇬🇦', 'length' => 8],

        // ---- CEMAC et voisins immédiats ----
        'CM' => ['name' => 'Cameroun',                'code' => '237', 'flag' => '🇨🇲', 'length' => 9],
        'CG' => ['name' => 'Congo-Brazzaville',       'code' => '242', 'flag' => '🇨🇬', 'length' => 9],
        'CD' => ['name' => 'RD Congo',                'code' => '243', 'flag' => '🇨🇩', 'length' => 9],
        'GQ' => ['name' => 'Guinée équatoriale',      'code' => '240', 'flag' => '🇬🇶', 'length' => 9],
        'TD' => ['name' => 'Tchad',                   'code' => '235', 'flag' => '🇹🇩', 'length' => 8],
        'CF' => ['name' => 'Centrafrique',            'code' => '236', 'flag' => '🇨🇫', 'length' => 8],
        'ST' => ['name' => 'Sao Tomé-et-Principe',    'code' => '239', 'flag' => '🇸🇹', 'length' => 7],

        // ---- Afrique de l'Ouest francophone ----
        'CI' => ['name' => "Côte d'Ivoire",           'code' => '225', 'flag' => '🇨🇮', 'length' => 10],
        'SN' => ['name' => 'Sénégal',                 'code' => '221', 'flag' => '🇸🇳', 'length' => 9],
        'BJ' => ['name' => 'Bénin',                   'code' => '229', 'flag' => '🇧🇯', 'length' => 8],
        'TG' => ['name' => 'Togo',                    'code' => '228', 'flag' => '🇹🇬', 'length' => 8],
        'BF' => ['name' => 'Burkina Faso',            'code' => '226', 'flag' => '🇧🇫', 'length' => 8],
        'ML' => ['name' => 'Mali',                    'code' => '223', 'flag' => '🇲🇱', 'length' => 8],
        'NE' => ['name' => 'Niger',                   'code' => '227', 'flag' => '🇳🇪', 'length' => 8],
        'GN' => ['name' => 'Guinée',                  'code' => '224', 'flag' => '🇬🇳', 'length' => 9],

        // ---- Expatriés et diaspora ----
        'FR' => ['name' => 'France',                  'code' => '33',  'flag' => '🇫🇷', 'length' => 9],
        'BE' => ['name' => 'Belgique',                'code' => '32',  'flag' => '🇧🇪', 'length' => 9],
        'CH' => ['name' => 'Suisse',                  'code' => '41',  'flag' => '🇨🇭', 'length' => 9],
        'CA' => ['name' => 'Canada',                  'code' => '1',   'flag' => '🇨🇦', 'length' => 10],
        'US' => ['name' => 'États-Unis',              'code' => '1',   'flag' => '🇺🇸', 'length' => 10],
        'GB' => ['name' => 'Royaume-Uni',             'code' => '44',  'flag' => '🇬🇧', 'length' => 10],
        'PT' => ['name' => 'Portugal',                'code' => '351', 'flag' => '🇵🇹', 'length' => 9],
        'ES' => ['name' => 'Espagne',                 'code' => '34',  'flag' => '🇪🇸', 'length' => 9],
        'IT' => ['name' => 'Italie',                  'code' => '39',  'flag' => '🇮🇹', 'length' => 10],
        'DE' => ['name' => 'Allemagne',               'code' => '49',  'flag' => '🇩🇪', 'length' => null],
        'MA' => ['name' => 'Maroc',                   'code' => '212', 'flag' => '🇲🇦', 'length' => 9],
        'TN' => ['name' => 'Tunisie',                 'code' => '216', 'flag' => '🇹🇳', 'length' => 8],
        'DZ' => ['name' => 'Algérie',                 'code' => '213', 'flag' => '🇩🇿', 'length' => 9],
        'CN' => ['name' => 'Chine',                   'code' => '86',  'flag' => '🇨🇳', 'length' => 11],
        'IN' => ['name' => 'Inde',                    'code' => '91',  'flag' => '🇮🇳', 'length' => 10],
        'LB' => ['name' => 'Liban',                   'code' => '961', 'flag' => '🇱🇧', 'length' => null],
        'TR' => ['name' => 'Turquie',                 'code' => '90',  'flag' => '🇹🇷', 'length' => 10],
        'ZA' => ['name' => 'Afrique du Sud',          'code' => '27',  'flag' => '🇿🇦', 'length' => 9],
        'NG' => ['name' => 'Nigeria',                 'code' => '234', 'flag' => '🇳🇬', 'length' => 10],
        'GH' => ['name' => 'Ghana',                   'code' => '233', 'flag' => '🇬🇭', 'length' => 9],
    ];

    /** Indicatif d'un pays, Gabon si le code est inconnu. */
    public static function code(?string $iso): string
    {
        return self::COUNTRIES[strtoupper((string) $iso)]['code']
            ?? self::COUNTRIES[self::DEFAULT]['code'];
    }

    public static function isKnown(?string $iso): bool
    {
        return array_key_exists(strtoupper((string) $iso), self::COUNTRIES);
    }

    /**
     * Assemble un numéro international à partir du pays choisi et du national
     * saisi. Le 0 de départ national est retiré : il ne s'utilise qu'en
     * composition locale et fausserait le numéro international.
     */
    public static function compose(?string $iso, ?string $national): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $national);

        if ($digits === '') {
            return null;
        }

        $digits = ltrim($digits, '0');

        if ($digits === '') {
            return null;
        }

        return self::code($iso) . $digits;
    }

    /**
     * Le national saisi a-t-il la longueur attendue pour ce pays ?
     * Sert à alerter à la saisie plutôt qu'à découvrir l'erreur à l'envoi.
     */
    public static function lengthLooksRight(?string $iso, ?string $national): bool
    {
        $attendue = self::COUNTRIES[strtoupper((string) $iso)]['length'] ?? null;

        if ($attendue === null) {
            return true;   // longueur variable : rien à vérifier
        }

        return strlen(ltrim(preg_replace('/\D/', '', (string) $national), '0')) === $attendue;
    }

    /**
     * Le national saisi est-il TROP LONG pour le pays choisi ?
     *
     * Signale le cas courant : le sélecteur resté sur Gabon alors que le numéro
     * est étranger. On ne rejette que le dépassement, jamais un numéro plus
     * court — l'ancienne numérotation gabonaise en fait partie et doit passer.
     */
    public static function tooLongForCountry(?string $iso, ?string $national): bool
    {
        $attendue = self::COUNTRIES[strtoupper((string) $iso)]['length'] ?? null;

        if ($attendue === null) {
            return false;   // longueur variable : rien à vérifier
        }

        return strlen(ltrim(preg_replace('/\D/', '', (string) $national), '0')) > $attendue;
    }

    /**
     * Le numéro commence-t-il par un indicatif connu ?
     * Sert à reconnaître un numéro déjà international quand le « + » a été perdu
     * en chemin — typiquement celui composé par le sélecteur de pays.
     */
    public static function startsWithKnownCode(?string $international): bool
    {
        $digits = preg_replace('/\D/', '', (string) $international);

        if ($digits === '') {
            return false;
        }

        foreach (self::COUNTRIES as $info) {
            if (str_starts_with($digits, $info['code'])
                && strlen($digits) > strlen($info['code'])) {
                return true;
            }
        }

        return false;
    }

    /** Pays le plus probable pour un numéro international déjà normalisé. */
    public static function guessIso(?string $international): string
    {
        $digits = preg_replace('/\D/', '', (string) $international);

        if ($digits === '') {
            return self::DEFAULT;
        }

        // Indicatifs les plus longs d'abord : « 1 » ne doit pas gagner sur « 212 ».
        $pays = self::COUNTRIES;
        uasort($pays, fn ($a, $b) => strlen($b['code']) <=> strlen($a['code']));

        foreach ($pays as $iso => $info) {
            if (str_starts_with($digits, $info['code'])) {
                return $iso;
            }
        }

        return self::DEFAULT;
    }
}
