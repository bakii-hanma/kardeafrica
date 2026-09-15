<?php

namespace App\Support;

/**
 * PhoneOperator
 * =============
 * Reconnaissance de l'opérateur Mobile Money gabonais à partir du numéro.
 *
 * Règles fournies par le client (KardAfrica) :
 *   - Airtel Money : 077, 074, 076
 *   - Moov Money   : 066, 062, 0065 (comprend 062 00 65 xx xx)
 *
 * Le préfixe se lit sur le numéro local (0XX…) ; la forme internationale
 * (241XX…) est traduite avant lecture.
 */
class PhoneOperator
{
    public const AIRTEL = 'airtel';
    public const MOOV  = 'moov';

    /** Préfixes locaux qui désignent Airtel Money. */
    public const AIRTEL_PREFIXES = ['077', '074', '076'];

    /** Préfixes locaux qui désignent Moov Money. */
    public const MOOV_PREFIXES = ['066', '062', '065', '0065'];

    /**
     * Opérateur Mobile Money reconnu, ou null si inconnu.
     *
     * Accepte toutes les écritures (077123456, +24177123456, 241987654X…)
     * en s'appuyant sur Phone::normalize.
     */
    public static function detect(?string $phone): ?string
    {
        $normalize = Phone::normalize($phone);

        if ($normalize === null) {
            return null;
        }

        // Forme internationale 241XXXXXXXX → suite 8 chiffres.
        if (str_starts_with($normalize, Phone::COUNTRY_CODE)) {
            $normalize = substr($normalize, strlen(Phone::COUNTRY_CODE));
        }

        $local = '0' . ltrim($normalize, '0');

        if (self::startsWithAny($local, self::AIRTEL_PREFIXES)) {
            return self::AIRTEL;
        }

        if (self::startsWithAny($local, self::MOOV_PREFIXES)) {
            return self::MOOV;
        }

        return null;
    }

    /** Libellé convivial, ex. « Airtel Money ». */
    public static function label(?string $phone): ?string
    {
        return match (self::detect($phone)) {
            self::AIRTEL => 'Airtel Money',
            self::MOOV   => 'Moov Money',
            default      => null,
        };
    }

    private static function startsWithAny(string $subject, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($subject, $prefix)) {
                return true;
            }
        }

        return false;
    }
}