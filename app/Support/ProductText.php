<?php

namespace App\Support;

/**
 * Nettoyage des textes produit venant de l'API afrikard (audit SEO :
 * descriptions FR + EN concaténées, ex. « Achetez des jeux… Buy games,
 * add-on content… »). Heuristique conservatrice : on coupe à la première
 * phrase anglaise détectée, seulement s'il reste du texte avant (sinon on
 * garde tel quel — mieux vaut de l'anglais que rien).
 */
class ProductText
{
    /** Débuts de phrase anglais fréquents dans le catalogue afrikard. */
    private const EN_STARTS = [
        'Buy ', 'Use ', 'Get ', 'Redeem ', 'Shop ', 'Enjoy ', 'Purchase ',
        'Play ', 'Give ', 'Discover ', 'Choose ', 'With this ', 'This card',
        'This gift', 'The perfect', 'Top up ', 'Add funds', 'Treat ',
    ];

    /** Garde la partie française d'une description bilingue concaténée. */
    public static function frenchOnly(?string $text): string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }

        $cutAt = null;
        foreach (self::EN_STARTS as $marker) {
            $pos = mb_strpos($text, $marker);
            // > 20 : ne jamais couper une description 100 % anglaise (le marqueur
            // serait au tout début) ; on ne coupe que si du FR précède.
            if ($pos !== false && $pos > 20 && ($cutAt === null || $pos < $cutAt)) {
                $cutAt = $pos;
            }
        }

        return $cutAt !== null ? rtrim(mb_substr($text, 0, $cutAt), " \t\n·-–") : $text;
    }
}
