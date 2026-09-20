<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Server-side Bamboo reporting (via le proxy afrikard).
 *
 * L'app Laravel n'est PAS whitelistée chez Bamboo : tous les appels de
 * lecture (soldes, transactions, taux, historique) passent par le proxy
 * afrikard (`/accounts`, `/transactions`, `/exchange-rates`, `/orders/report`).
 *
 * Mise en cache courte (60 s) : les écrans admin ne martèlent pas Bamboo.
 * Swallow plus jamais : toute erreur réseau retourne un état dégradé lisible
 * plutôt qu'une exception 500 du dashboard.
 */
class BambooReportingService
{
    private const CACHE_TTL = 60;

    private function proxyBase(): string
    {
        return rtrim((string) config('services.bamboo.admin_base_url'), '/');
    }

    /**
     * Solde des comptes fournisseur (proxy afrikard → Bamboo /accounts).
     *
     * @return array{ok:bool, accounts:array, fetched_at:?string, error:?string}
     */
    public function accounts(?bool $fresh = false): array
    {
        $cacheKey = 'bamboo_accounts_v1';

        if ($fresh) {
            cache()->forget($cacheKey);
        }

        return cache()->remember($cacheKey, self::CACHE_TTL, function () {
            try {
                $res = Http::timeout(12)->get($this->proxyBase() . '/accounts');
                if (! $res->successful()) {
                    return ['ok' => false, 'accounts' => [], 'fetched_at' => null, 'error' => "HTTP {$res->status()}"];
                }
                $data = $res->json();

                return [
                    'ok'         => true,
                    'accounts'   => $data['accounts'] ?? [],
                    'fetched_at' => now()->toDateTimeString(),
                    'error'      => null,
                ];
            } catch (\Throwable $e) {
                Log::warning('BambooReporting.accounts: erreur', ['error' => $e->getMessage()]);
                return ['ok' => false, 'accounts' => [], 'fetched_at' => null, 'error' => 'afrikard/Bamboo injoignable'];
            }
        });
    }

    /**
     * Transactions Bamboo sur une période (proxy afrikard → /transactions).
     *
     * @return array{ok:bool, clients:array, fetched_at:?string, error:?string}
     */
    public function transactions(string $startDate, string $endDate, ?bool $fresh = false): array
    {
        $cacheKey = 'bamboo_tx_v1_' . $startDate . '_' . $endDate;

        if ($fresh) {
            cache()->forget($cacheKey);
        }

        return cache()->remember($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate) {
            try {
                $res = Http::timeout(20)->get($this->proxyBase() . '/transactions', [
                    'startDate' => $startDate,
                    'endDate'   => $endDate,
                ]);
                if (! $res->successful()) {
                    return ['ok' => false, 'clients' => [], 'fetched_at' => null, 'error' => "HTTP {$res->status()}"];
                }
                $data = $res->json();

                return [
                    'ok'         => true,
                    'clients'    => $data['clients'] ?? [],
                    'fetched_at' => now()->toDateTimeString(),
                    'error'      => null,
                ];
            } catch (\Throwable $e) {
                Log::warning('BambooReporting.transactions: erreur', ['error' => $e->getMessage()]);
                return ['ok' => false, 'clients' => [], 'fetched_at' => null, 'error' => 'afrikard/Bamboo injoignable'];
            }
        });
    }

    /**
     * Taux de change officiels Bamboo (proxy afrikard → /exchange-rates).
     *
     * @return array{ok:bool, base:?string, rates:array, error:?string}
     */
    public function exchangeRates(?bool $fresh = false): array
    {
        if ($fresh) {
            cache()->forget('bamboo_rates_v1');
        }

        return cache()->remember('bamboo_rates_v1', 600, function () {
            try {
                $res = Http::timeout(12)->get($this->proxyBase() . '/exchange-rates');
                if (! $res->successful()) {
                    return ['ok' => false, 'base' => null, 'rates' => [], 'error' => "HTTP {$res->status()}"];
                }
                $data = $res->json();

                return [
                    'ok'    => true,
                    'base'  => $data['baseCurrencyCode'] ?? null,
                    'rates' => $data['rates'] ?? [],
                    'error' => null,
                ];
            } catch (\Throwable $e) {
                Log::warning('BambooReporting.exchangeRates: erreur', ['error' => $e->getMessage()]);
                return ['ok' => false, 'base' => null, 'rates' => [], 'error' => 'afrikard/Bamboo injoignable'];
            }
        });
    }

    /**
     * Historique des commandes Bamboo sur la période (réconciliation).
     *
     * @return array{ok:bool, orders:array, error:?string}
     */
    public function orderReport(string $startDate, string $endDate, ?bool $fresh = false): array
    {
        if ($fresh) {
            cache()->forget('bamboo_orders_v1_' . $startDate . '_' . $endDate);
        }

        return cache()->remember('bamboo_orders_v1_' . $startDate . '_' . $endDate, self::CACHE_TTL, function () use ($startDate, $endDate) {
            try {
                $res = Http::timeout(20)->get($this->proxyBase() . '/orders/report', [
                    'startDate' => $startDate,
                    'endDate'   => $endDate,
                ]);
                if (! $res->successful()) {
                    return ['ok' => false, 'orders' => [], 'error' => "HTTP {$res->status()}"];
                }
                return [
                    'ok'     => true,
                    'orders' => $res->json() ?? [],
                    'error'  => null,
                ];
            } catch (\Throwable $e) {
                Log::warning('BambooReporting.orderReport: erreur', ['error' => $e->getMessage()]);
                return ['ok' => false, 'orders' => [], 'error' => 'afrikard/Bamboo injoignable'];
            }
        });
    }
}