<?php

namespace App\Support;

/**
 * Conversion des soldes/transactions Bamboo vers FCFA (XAF).
 *
 * Le taux officiel EUR→XAF est FIXE (parité BCEAO : 1 EUR = 655,957 XAF).
 * Les taux Bamboo (`/exchange-rates`, base USD) sont convertis en cote
 * devise→XAF via `rates[cur] / rates[XAF]` : EUR donne exactement 655,96 XAF,
 * ce qui confirme la parité officielle. Si les taux Bamboo font défaut on
 * retombe sur les taux d'affichage `Money` (configurés en admin).
 */
class BambooRates
{
    /**
     * Transforme la réponse `/exchange-rates` de Bamboo en cotes devise→XAF.
     *
     * @param  array<int, array{currencyCode?: string, value?: float}>  $bambooRates
     * @return array<string, float>  ex. ['EUR' => 655.96, 'USD' => 571.4, ...]
     */
    public static function toXafRates(array $bambooRates): array
    {
        $byCur = [];
        foreach ($bambooRates as $r) {
            $code = strtoupper((string) ($r['currencyCode'] ?? ''));
            $val  = (float) ($r['value'] ?? 0);
            if ($code !== '' && $val > 0) {
                $byCur[$code] = $val;
            }
        }

        $xaf = $byCur['XAF'] ?? 0;
        if ($xaf <= 0) {
            return [];
        }

        $out = [];
        foreach ($byCur as $cur => $v) {
            $out[$cur] = $v / $xaf;
        }

        return $out;
    }

    /**
     * Convertit un montant d'une devise vers FCFA.
     * XAF/XOF sont déjà du FCFA ; les devises sans cote Bamboo passent par
     * les taux d'affichage `Money`. null = devise inconnue (ne pas afficher).
     */
    public static function convert(float $amount, ?string $currency, array $xafRates): ?float
    {
        $cur = strtoupper((string) ($currency ?: 'XAF'));

        if ($cur === 'XAF' || $cur === 'XOF') {
            return $amount;
        }

        $rate = $xafRates[$cur] ?? null;
        if ($rate === null) {
            $fallback = Money::EXCHANGE_RATES[$cur] ?? null;
            if ($fallback === null) {
                return null;
            }
            $rate = (float) $fallback;
        }

        return $amount * $rate;
    }

    /**
     * Total FCFA d'une liste de comptes Bamboo (`balance` + `currency`).
     * Les devises inconnues sont ignorées plutôt que comptabilisées à 0.
     */
    public static function totalXaf(array $accounts, array $xafRates): float
    {
        $total = 0.0;
        foreach ($accounts as $acc) {
            $val = self::convert(
                (float) ($acc['balance'] ?? 0),
                (string) ($acc['currency'] ?? ''),
                $xafRates,
            );
            if ($val !== null) {
                $total += $val;
            }
        }
        return $total;
    }
}