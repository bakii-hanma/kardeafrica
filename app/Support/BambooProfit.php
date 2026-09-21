<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;

/**
 * Bénéfice des ventes Kardafrica à partir des transactions Bamboo.
 *
 * Le coût (vrai prix) d'une carte, c'est ce que le compte FUTUR SOWAX paie à
 * Bamboo : le `transactionAmount` de la transaction (ou `clientPrice` par
 * article). Le prix Kardafrica, c'est ce que le client paie (`total_amount`
 * local). Le bénéfice = prix Kardafrica − coût, tout en FCFA.
 *
 * Deux sources, jamais mélangées au hasard :
 *  - Bamboo (fiable) : transaction appariée par checkout_order_id /
 *    checkout_request_id → `transactionAmount` converti.
 *  - Repli local (estimé) : somme des `native_value` × taux, sans arrondi de
 *    vente — l'ordre de grandeur de la carte, pas un prix de vente.
 *
 * Les méthodes sont statiques et purs : un contrôleur décide quoi passer.
 */
class BambooProfit
{
    /**
     * Transaction Bamboo qui correspond à une commande locale, ou null.
     *
     * @param  Collection<int, array<string, mixed>>  $transactions
     * @return array<string, mixed>|null
     */
    public static function matchTransaction(Order $order, Collection $transactions): ?array
    {
        $bd  = (array) $order->billing_details;
        $oid = $bd['checkout_order_id'] ?? null;
        $rid = $bd['checkout_request_id'] ?? null;

        if ($oid === null && $rid === null) {
            return null;
        }

        return $transactions->first(function ($t) use ($oid, $rid) {
            return ((string) $oid !== '' && (string) ($t['orderId'] ?? '') === (string) $oid)
                || ((string) $rid !== '' && (string) ($t['requestId'] ?? '') === (string) $rid);
        });
    }

    /**
     * Coût FCFA d'une transaction Bamboo (montant réellement débité).
     * null si la devise est inconnue.
     *
     * @param  array<string, mixed>  $tx
     * @return float|null
     */
    public static function transactionCostFcfa(array $tx, array $xafRates): ?float
    {
        $amt = $tx['transactionAmount'] ?? null;
        if (! is_array($amt)) {
            return null;
        }

        return BambooRates::convert(
            (float) ($amt['value'] ?? 0),
            (string) ($amt['currencyCode'] ?? ''),
            $xafRates,
        );
    }

    /**
     * Coût FCFA d'origine d'un article local : `native_value` converti sans
     * arrondi, ou null si le champ est vide (commande trop ancienne / simulée).
     */
    public static function itemNativeCostFcfa(OrderItem $item, array $xafRates): ?float
    {
        $value = (float) ($item->native_value ?? 0);
        $cur   = (string) ($item->native_currency ?? '');
        if ($value <= 0 || $cur === '') {
            return null;
        }

        return BambooRates::convert($value * (float) $item->quantity, $cur, $xafRates);
    }

    /**
     * Bénéfice d'une commande : prix Kardafrica − coût réel.
     *
     * @param  array<string, mixed>|null  $matchedTx  transaction Bamboo appariée, ou null
     * @return array{
     *   matched: bool, source: string, card_total_fcfa: float,
     *   cost_fcfa: ?float, profit_fcfa: ?float, margin_percent: ?float, note: ?string
     * }
     */
    public static function forOrder(Order $order, ?array $matchedTx, array $xafRates): array
    {
        $cardTotal = (float) $order->total_amount;

        if ($matchedTx !== null) {
            $cost = self::transactionCostFcfa($matchedTx, $xafRates);
            if ($cost !== null) {
                return self::build($cardTotal, $cost, true, 'bamboo',
                    'Coût issu de la transaction Bamboo (montant débité FUTUR SOWAX).');
            }
        }

        // Repli : somme des native_value, « coût estimé » si pas de transaction.
        $cost = 0.0;
        foreach ($order->orderItems as $item) {
            $native = self::itemNativeCostFcfa($item, $xafRates);
            if ($native !== null) {
                $cost += $native;
            }
        }

        if ($cost > 0) {
            return self::build($cardTotal, $cost, false, 'fallback',
                $matchedTx ? 'Détail transaction Bamboo indisponible : coût estimé du prix facial des cartes.' : 'Aucune transaction Bamboo reliée : coût estimé du prix facial des cartes.');
        }

        return [
            'matched' => false, 'source' => 'none',
            'card_total_fcfa' => $cardTotal,
            'cost_fcfa' => null, 'profit_fcfa' => null, 'margin_percent' => null,
            'note' => 'Coût indisponible : pas de transaction Bamboo ni de valeur faciale enregistrée.',
        ];
    }

    /**
     * Répartit le coût d'une transaction sur les articles locaux, par
     * correspondance product_id (meilleur-effort : coût null sinon).
     *
     * @param  array<string, mixed>  $tx
     * @return array<int, array{item: OrderItem, cost_fcfa: ?float}>
     */
    public static function splitByItem(Order $order, array $tx, array $xafRates): array
    {
        $txAmount = $tx['transactionAmount'] ?? null;
        $txCur    = (string) (is_array($txAmount) ? ($txAmount['currencyCode'] ?? '') : '');

        $out = [];
        foreach ($order->orderItems as $item) {
            $cost = null;

            foreach (($tx['orderItems'] ?? []) as $oi) {
                $pid = (string) ($oi['productId'] ?? '');
                if ($pid !== '' && $pid === (string) $item->product_id) {
                    $client = $oi['clientPrice'] ?? null;
                    $value  = is_array($client) ? (float) ($client['value'] ?? 0) : null;
                    $cur    = is_array($client) ? (string) ($client['currencyCode'] ?? $txCur) : $txCur;
                    if ($value !== null && $value > 0) {
                        $value *= (float) ($oi['quantity'] ?? 1);
                        $cost = BambooRates::convert($value, $cur ?: null, $xafRates);
                    }
                    break;
                }
            }

            $out[] = ['item' => $item, 'cost_fcfa' => $cost];
        }

        return $out;
    }

    /**
     * Statistiques de bénéfice sur une période, en rapprochant les ventes
     * locales PAYÉES des transactions Bamboo de la même fenêtre.
     *
     * @param  Collection<int, Order>  $orders    ventes locales de la période
     * @param  Collection<int, array<string, mixed>>  $transactions  transactions Bamboo
     * @return array{
     *   sale_total_fcfa: float, cost_total_fcfa: ?float, profit_fcfa: ?float,
     *   margin_percent: ?float, cards_sold: int, matched_orders: int,
     *   estimated_orders: int, note: ?string
     * }
     */
    public static function periodStats(Collection $orders, Collection $transactions, array $xafRates): array
    {
        $byOrderId = $transactions->keyBy(fn ($t) => 'order:' . (string) ($t['orderId'] ?? ''));
        $byReqId   = $transactions->keyBy(fn ($t) => 'req:' . (string) ($t['requestId'] ?? ''));

        $saleTotal  = 0.0;
        $costTotal  = 0.0;
        $cards      = 0;
        $matched    = 0;
        $estimated  = 0;
        $missing    = 0;

        foreach ($orders as $order) {
            $bd  = (array) $order->billing_details;
            $oid = $bd['checkout_order_id'] ?? null;
            $rid = $bd['checkout_request_id'] ?? null;

            $tx = ($oid !== null ? ($byOrderId['order:' . $oid] ?? null) : null)
                ?? ($rid !== null ? ($byReqId['req:' . $rid] ?? null) : null);

            $saleTotal += (float) $order->total_amount;

            foreach ($order->orderItems as $item) {
                $cards += (int) $item->quantity;
            }

            if ($tx !== null) {
                $cost = self::transactionCostFcfa($tx, $xafRates);
                if ($cost !== null) {
                    $costTotal += $cost;
                    $matched++;
                    continue;
                }
            }

            $est = 0.0;
            foreach ($order->orderItems as $item) {
                $native = self::itemNativeCostFcfa($item, $xafRates);
                if ($native !== null) {
                    $est += $native;
                }
            }

            if ($est > 0) {
                $costTotal += $est;
                $estimated++;
            } else {
                $missing++;
            }
        }

        $profit = $costTotal > 0 ? round($saleTotal - $costTotal, 2) : null;

        return [
            'sale_total_fcfa' => round($saleTotal, 2),
            'cost_total_fcfa' => $costTotal > 0 ? round($costTotal, 2) : null,
            'profit_fcfa'     => $profit,
            'margin_percent'  => $profit !== null && $saleTotal > 0 ? round($profit / $saleTotal * 100, 1) : null,
            'cards_sold'      => $cards,
            'matched_orders'  => $matched,
            'estimated_orders'=> $estimated,
            'note'            => $missing > 0
                ? "{$missing} commande(s) sans coût (ni transaction Bamboo ni valeur faciale)."
                : null,
        ];
    }

    /**
     * @return array{
     *   matched: bool, source: string, card_total_fcfa: float,
     *   cost_fcfa: float, profit_fcfa: float, margin_percent: ?float, note: ?string
     * }
     */
    private static function build(float $cardTotal, float $cost, bool $matched, string $source, string $note): array
    {
        $profit = round($cardTotal - $cost, 2);

        return [
            'matched' => $matched,
            'source'  => $source,
            'card_total_fcfa' => round($cardTotal, 2),
            'cost_fcfa'   => round($cost, 2),
            'profit_fcfa' => $profit,
            'margin_percent' => $cardTotal > 0 ? round($profit / $cardTotal * 100, 1) : null,
            'note' => $note,
        ];
    }
}