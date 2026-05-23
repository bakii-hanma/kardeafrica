<?php

namespace App\Support;

use App\Models\AppSetting;

class Money
{
    /**
     * Taux de change vers FCFA (XAF) — fallback HARDCODÉ utilisé seulement si
     * la BDD ne contient pas de taux pour la devise. Pour EUR/USD/AED, les
     * vrais taux sont configurés depuis l'admin (table `app_settings`,
     * clés `currency_rate_eur`, `currency_rate_usd`, `currency_rate_aed`).
     *
     * Aligné avec la table JS dans layouts/app.blade.php et mobile/utils/currency.ts.
     */
    public const EXCHANGE_RATES = [
        'XAF' => 1,
        'XOF' => 1,
        'EUR' => 655.957,
        'USD' => 620,
        'AED' => 170,
        'GBP' => 780,
        'CAD' => 450,
        'ARS' => 0.7,
        'AUD' => 405,
        'BRL' => 120,
        'TRY' => 20,
        'MXN' => 35,
        'INR' => 7.5,
        'ZAR' => 35,
        'NGN' => 0.4,
    ];

    /**
     * Devises éditables depuis l'admin (= ont un taux en BDD).
     * Les autres restent sur les valeurs hardcodées.
     */
    public const ADMIN_EDITABLE = ['EUR', 'USD', 'AED'];

    /**
     * Palier d'arrondi par défaut (FCFA) si aucun n'est configuré.
     */
    private const DEFAULT_ROUND_STEP = 100;

    /**
     * Memo par requête (évite des centaines d'appels Cache::get sur les
     * endpoints catalogue qui convertissent N produits × M devises).
     * Reset à chaque resetRequestMemo() ou démarrage de process queue.
     */
    private static ?array $memoRates = null;
    private static ?int   $memoStep  = null;

    /**
     * Convertit un montant + sa devise en FCFA, en appliquant :
     *  1. Le taux configuré (BDD prioritaire, fallback table en mémoire).
     *  2. Un arrondi VERS LE HAUT au prochain palier configuré (ex: 100).
     *     Exemple : 1016 → 1100, 6560 → 6600.
     *
     * Pour XAF/XOF, pas de conversion — juste l'arrondi.
     */
    public static function toFcfa(int|float $amount, ?string $currency = 'XAF'): int
    {
        $code = strtoupper($currency ?: 'XAF');

        // Fast path : devises 1:1 (XAF/XOF) — pas besoin du memo BDD
        if ($code === 'XAF' || $code === 'XOF') {
            return self::roundUpToStep((float) $amount);
        }

        $rate      = self::resolveRate($code);
        $converted = $amount * $rate;
        return self::roundUpToStep($converted);
    }

    /**
     * Renvoie une chaîne formatée "5 000 FCFA" depuis un prix + devise sources.
     */
    public static function formatFcfa(int|float $amount, ?string $currency = 'XAF'): string
    {
        $fcfa = self::toFcfa($amount, $currency);
        return number_format($fcfa, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Map [code => rate] courant — pour synchroniser le frontend (JS web et
     * mobile). Lit la BDD pour les devises admin-editable, fallback sinon.
     * Memo par process : ne ré-interroge la BDD qu'une fois.
     */
    public static function currentRates(): array
    {
        if (self::$memoRates !== null) return self::$memoRates;

        $rates = self::EXCHANGE_RATES;
        foreach (self::ADMIN_EDITABLE as $code) {
            $db = AppSetting::get('currency_rate_' . strtolower($code));
            if ($db !== null && (float) $db > 0) {
                $rates[$code] = (float) $db;
            }
        }
        return self::$memoRates = $rates;
    }

    /**
     * Palier d'arrondi FCFA actuel (lit la BDD une fois par process).
     */
    public static function roundStep(): int
    {
        if (self::$memoStep !== null) return self::$memoStep;

        $step = (int) AppSetting::get('currency_round_step', self::DEFAULT_ROUND_STEP);
        return self::$memoStep = ($step > 1 ? $step : 1);
    }

    /**
     * Reset le memo — à appeler après update admin pour forcer le re-fetch.
     * Sinon les nouveaux taux ne s'appliquent qu'à la prochaine requête HTTP.
     */
    public static function resetMemo(): void
    {
        self::$memoRates = null;
        self::$memoStep  = null;
    }

    /**
     * Résout le taux d'une devise : memo en mémoire (lui-même alimenté par BDD).
     */
    private static function resolveRate(string $code): float
    {
        $rates = self::currentRates();
        return (float) ($rates[$code] ?? 1);
    }

    /**
     * Arrondit AU PLUS HAUT multiple du palier configuré.
     * Ex: step=100 → 1016 → 1100 ; 6560 → 6600 ; 1100 → 1100 (déjà rond).
     */
    private static function roundUpToStep(float $amount): int
    {
        $step = self::roundStep();
        if ($step <= 1) return (int) ceil($amount);
        return (int) (ceil($amount / $step) * $step);
    }
}
