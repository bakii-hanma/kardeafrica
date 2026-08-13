<?php

namespace App\Support;

/**
 * Phone
 * =====
 * Forme canonique d'un numéro de téléphone, et clé de compte client.
 *
 * La base porte plusieurs écritures de la même ligne — `24106871309`,
 * `06871309`, `077998877`. Tant que le numéro n'était qu'une donnée de contact,
 * la coexistence passait inaperçue. Il devient la **clé du compte client** :
 * sans unification, la même personne aurait deux comptes et ses cartes
 * éparpillées entre les deux.
 *
 * Canonique retenue : `241XXXXXXXX`, chiffres seuls, sans `+` — la forme
 * attendue par WHAPI, ce qui évite une conversion à chaque envoi.
 *
 * DEUX USAGES, DEUX EXIGENCES OPPOSÉES
 * ------------------------------------
 * `normalize()` sert à **conserver** : il ne détruit jamais une donnée de
 * contact, même illisible. Il rend au pire les chiffres tels quels.
 *
 * `accountKey()` sert à **identifier** : il refuse tout ce qui est ambigu et
 * rend `null`. Mieux vaut redemander le numéro que rattacher la carte d'un
 * client au compte d'un autre.
 *
 * DEUX PIÈGES RÉELS, DÉLIBÉRÉMENT NON DEVINÉS
 * -------------------------------------------
 *  1. Un étranger installé au Gabon garde son numéro WhatsApp d'origine. Saisi
 *     SANS indicatif (« 0612345678 » pour la France), il est indistinguable
 *     d'une saisie locale mal formée : `accountKey()` refuse plutôt que de le
 *     gabonisier. Dès qu'il porte un `+` ou un `00`, il est reconnu et respecté.
 *  2. L'ancienne numérotation gabonaise (06 87 65 43, 8 chiffres) reste parfois
 *     le numéro WhatsApp d'un client de longue date. Le passage à 9 chiffres
 *     (066 87 65 43) a inséré un chiffre qui dépend de l'opérateur et n'est pas
 *     déductible du numéro : elle est conservée comme contact, mais refusée
 *     comme clé.
 *
 * La bonne réponse à ces deux cas n'est pas une heuristique plus fine, c'est un
 * sélecteur d'indicatif à la saisie. `accountKey()` est ce qui force à le poser.
 */
class Phone
{
    public const COUNTRY_CODE = '241';

    /** Chiffres d'abonné d'un numéro gabonais actuel (indicatif et 0 exclus). */
    private const NATIONAL_LENGTH = 8;

    /** Longueur d'un national de l'ancienne numérotation, 0 de départ compris. */
    private const LEGACY_NATIONAL_LENGTH = 8;

    /** En deçà, aucun numéro international n'est complet. */
    private const MIN_INTERNATIONAL = 10;

    /**
     * Forme la plus canonique atteignable SANS deviner.
     * Ne rend `null` que si l'entrée ne contient aucun chiffre : une donnée de
     * contact douteuse reste préférable à une donnée effacée.
     */
    public static function normalize(?string $phone): ?string
    {
        $brut = (string) $phone;

        // Marqueur international explicite : le client a dit son pays, on le croit.
        $explicite = str_contains($brut, '+') || preg_match('/^\s*00\d/', $brut) === 1;

        $digits = preg_replace('/\D/', '', $brut);

        if ($digits === '') {
            return null;
        }

        if ($explicite) {
            $digits = preg_replace('/^00/', '', $digits);

            // Jamais réécrit en gabonais, même si la longueur y ressemble.
            return $digits;
        }

        // Déjà canonique.
        if (str_starts_with($digits, self::COUNTRY_CODE)
            && strlen($digits) === strlen(self::COUNTRY_CODE) + self::NATIONAL_LENGTH) {
            return $digits;
        }

        // Numérotation actuelle : 0 de départ suivi des 8 chiffres d'abonné
        // (066 87 65 43).
        if (str_starts_with($digits, '0') && strlen($digits) === self::NATIONAL_LENGTH + 1) {
            return self::COUNTRY_CODE . substr($digits, 1);
        }

        // Abonné seul, sans 0 de départ (66 87 65 43) : lecture gabonaise par
        // défaut, c'est la saisie de comptoir la plus courante.
        if (strlen($digits) === self::NATIONAL_LENGTH && !str_starts_with($digits, '0')) {
            return self::COUNTRY_CODE . $digits;
        }

        // ANCIENNE NUMÉROTATION : 0 de départ + 7 chiffres (06 87 65 43).
        // Ces lignes joignent toujours leur titulaire sur WhatsApp : les refuser
        // bloquerait une vente réelle. Elles sont donc canonicalisées telles
        // quelles — 241 + les chiffres, sans le 0 de composition locale.
        //
        // Ce qui n'est PAS fait ici : convertir vers la forme à 9 chiffres. Le
        // chiffre inséré lors du changement de plan n'est pas déductible du
        // numéro, et une conversion fausse rattacherait la carte d'un client au
        // compte d'un autre. `looksLegacyGabon()` signale ces numéros pour qu'un
        // rapprochement puisse être fait plus tard, sur une règle confirmée.
        if (self::looksLegacyGabonDigits($digits)) {
            return self::COUNTRY_CODE . ltrim($digits, '0');
        }

        // Étranger saisi sans indicatif, ou saisie tronquée : conservé tel quel,
        // et refusé comme clé de compte par `accountKey()`.
        return $digits;
    }

    /**
     * Clé d'identification d'un compte client, ou `null` si le numéro est
     * ambigu. Un `null` doit conduire à redemander le numéro, jamais à créer
     * un compte sur une supposition.
     */
    public static function accountKey(?string $phone): ?string
    {
        $n = self::normalize($phone);

        if ($n === null) {
            return null;
        }

        // Ligne gabonaise reconnue sans ambiguïté.
        if (self::isGabon($n)) {
            return $n;
        }

        // Ancienne numérotation : joignable sur WhatsApp, donc acceptée comme
        // clé sous sa propre forme canonique. À rapprocher de la forme à 9
        // chiffres le jour où la règle de conversion sera confirmée.
        if (self::looksLegacyGabon($phone)) {
            return $n;
        }

        // International explicite et complet : accepté tel quel.
        if (self::wasExplicitlyInternational($phone) && strlen($n) >= self::MIN_INTERNATIONAL) {
            return $n;
        }

        // Numéro déjà international, reconnaissable à son indicatif — le cas d'un
        // numéro composé par le sélecteur de pays, qui perd le « + » en route.
        // Aucun indicatif ne commence par 0 : une saisie nationale mal formée
        // reste donc écartée.
        if (strlen($n) >= self::MIN_INTERNATIONAL
            && !str_starts_with($n, '0')
            && DialCodes::startsWithKnownCode($n)) {
            return $n;
        }

        return null;
    }

    /** Le numéro peut-il servir de clé de compte ? */
    public static function isUsableAsKey(?string $phone): bool
    {
        return self::accountKey($phone) !== null;
    }

    /**
     * Le numéro relève-t-il de l'ancienne numérotation gabonaise ?
     * Sert à afficher un message utile plutôt qu'un « numéro invalide » sec :
     * ces numéros sont souvent encore le WhatsApp d'un client de longue date.
     */
    public static function looksLegacyGabon(?string $phone): bool
    {
        if (self::wasExplicitlyInternational($phone)) {
            return false;
        }

        return self::looksLegacyGabonDigits(preg_replace('/\D/', '', (string) $phone));
    }

    private static function looksLegacyGabonDigits(string $digits): bool
    {
        return strlen($digits) === self::LEGACY_NATIONAL_LENGTH && str_starts_with($digits, '0');
    }

    /** Le numéro désigne-t-il une ligne gabonaise actuelle ? */
    public static function isGabon(?string $phone): bool
    {
        $n = self::normalize($phone);

        return $n !== null
            && str_starts_with($n, self::COUNTRY_CODE)
            && strlen($n) === strlen(self::COUNTRY_CODE) + self::NATIONAL_LENGTH;
    }

    /** Deux numéros désignent-ils la même ligne, quelles que soient leurs formes ? */
    public static function same(?string $a, ?string $b): bool
    {
        $na = self::normalize($a);

        return $na !== null && $na === self::normalize($b);
    }

    /**
     * Comparaison PERMISSIVE, réservée aux gardes anti-fraude.
     *
     * `same()` exige une égalité canonique et rate donc le cas où la même ligne
     * est écrite sous l'ancienne et la nouvelle numérotation. Pour empêcher un
     * revendeur de s'envoyer à lui-même le code d'un client, on compare la
     * queue du numéro d'abonné.
     *
     * Le déséquilibre est assumé : refuser à tort le numéro d'un client qui
     * partage ses derniers chiffres avec le revendeur coûte une ressaisie ;
     * laisser passer un auto-envoi coûte une carte volée.
     */
    public static function sameLine(?string $a, ?string $b, int $chiffres = 7): bool
    {
        $queue = function (?string $p) use ($chiffres): ?string {
            $n = self::normalize($p);

            return $n !== null && strlen($n) >= $chiffres ? substr($n, -$chiffres) : null;
        };

        $qa = $queue($a);

        return $qa !== null && $qa === $queue($b);
    }

    /**
     * Toutes les écritures sous lesquelles ce numéro peut exister en base.
     * Indispensable tant que des lignes non migrées subsistent — et pour les
     * recherches saisies à la main par un vendeur.
     *
     * @return array<int, string>
     */
    public static function candidates(?string $phone): array
    {
        $n = self::normalize($phone);

        if ($n === null) {
            return [];
        }

        $formes = [$n, '+' . $n];

        if (str_starts_with($n, self::COUNTRY_CODE)) {
            $abonne = substr($n, strlen(self::COUNTRY_CODE));
            $formes[] = '0' . $abonne;
            $formes[] = $abonne;
        }

        return array_values(array_unique($formes));
    }

    /** Écriture lisible : +241 06 87 13 09. */
    public static function display(?string $phone): string
    {
        $n = self::normalize($phone);

        if ($n === null) {
            return '';
        }

        if (!self::isGabon($n)) {
            return '+' . $n;
        }

        $abonne = substr($n, strlen(self::COUNTRY_CODE));

        return '+' . self::COUNTRY_CODE . ' ' . trim(chunk_split($abonne, 2, ' '));
    }

    /** Forme masquée pour un affichage public : +24106 ** ** 09. */
    public static function masked(?string $phone): string
    {
        $n = self::normalize($phone);

        if ($n === null) {
            return '';
        }

        return '+' . substr($n, 0, 5) . ' ** ** ' . substr($n, -2);
    }

    private static function wasExplicitlyInternational(?string $phone): bool
    {
        $brut = (string) $phone;

        return str_contains($brut, '+') || preg_match('/^\s*00\d/', $brut) === 1;
    }
}
