<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Lecture d'un champ téléphone saisi avec le composant `<x-phone-input>`.
 *
 * Le composant envoie deux champs — `{nom}_country` et `{nom}_national` — plutôt
 * qu'un numéro déjà assemblé : la recomposition se fait côté serveur, donc le
 * formulaire reste juste même sans JavaScript.
 *
 * Le repli sur un champ unique est conservé : l'API et les anciens formulaires
 * envoient encore un numéro brut, et rien ne justifie de les casser.
 */
class PhoneInput
{
    /** Numéro international, ou null si la saisie ne donne rien d'exploitable. */
    public static function fromRequest(Request $request, string $name = 'phone'): ?string
    {
        $national = $request->input($name . '_national');

        if (filled($national)) {
            return DialCodes::compose($request->input($name . '_country'), $national);
        }

        return Phone::normalize($request->input($name));
    }

    /**
     * Numéro utilisable comme clé de compte client, ou null si la saisie reste
     * ambiguë. Un null doit faire redemander le numéro, jamais créer un compte
     * sur une supposition.
     */
    public static function accountKeyFromRequest(Request $request, string $name = 'phone'): ?string
    {
        $national = $request->input($name . '_national');

        if (filled($national)) {
            // Le pays est explicite : le numéro composé est fiable par construction.
            $compose = DialCodes::compose($request->input($name . '_country'), $national);

            return $compose !== null && strlen($compose) >= 9 ? $compose : null;
        }

        return Phone::accountKey($request->input($name));
    }

    /**
     * La saisie dépasse-t-elle la longueur du pays choisi ?
     * Attrape le cas courant : sélecteur resté sur Gabon, numéro étranger tapé.
     */
    public static function tooLongForCountry(Request $request, string $name = 'phone'): bool
    {
        $national = $request->input($name . '_national');

        return filled($national)
            && DialCodes::tooLongForCountry($request->input($name . '_country'), $national);
    }

    /** La longueur saisie correspond-elle au pays choisi ? */
    public static function lengthLooksRight(Request $request, string $name = 'phone'): bool
    {
        $national = $request->input($name . '_national');

        return blank($national)
            || DialCodes::lengthLooksRight($request->input($name . '_country'), $national);
    }
}
