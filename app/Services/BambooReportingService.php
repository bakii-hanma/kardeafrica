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
 * Mise en cache courte (60 s) des SEULS succès : les écrans admin ne
 * martèlent pas Bamboo, et un échec transitoire (429, réseau) n'est jamais
 * figé pendant une minute. Swallow plus jamais : toute erreur réseau retourne
 * un état dégradé lisible plutôt qu'une exception 500 du dashboard.
 */
class BambooReportingService
{
    private const CACHE_TTL = 60;

    private function proxyBase(): string
    {
        return rtrim((string) config('services.bamboo.admin_base_url'), '/');
    }

    /**
     * GET via le proxy, avec retries sur le rate-limit Bamboo (429).
     *
     * Un 429 est transitoire : on attend `Retry-After` (plafonné à quelques
     * secondes) puis on réessaie une poignée de fois avant d'abandonner, ce qui
     * évite d'afficher « 0 transaction » juste parce que la page a été
     * rafraîchie quelques secondes trop tôt.
     *
     * @return array{status:?int, json:?array, error:?string}
     */
    private function proxyGet(string $path, array $query = [], int $timeout = 12): array
    {
        $maxAttempts = 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $res = Http::timeout($timeout)->get($this->proxyBase() . $path, $query);

                if ($res->successful()) {
                    return ['status' => $res->status(), 'json' => $res->json(), 'error' => null];
                }

                if ($res->status() === 429 && $attempt < $maxAttempts) {
                    $wait = min((int) ($res->header('Retry-After') ?: $attempt * 2), 5);
                    usleep($wait * 1_000_000);
                    continue;
                }

                if ($res->status() === 429) {
                    return ['status' => 429, 'json' => null, 'error' => 'API Bamboo saturée (429) — réessayez dans quelques instants'];
                }

                return ['status' => $res->status(), 'json' => null, 'error' => "HTTP {$res->status()}"];
            } catch (\Throwable $e) {
                Log::warning("BambooReporting.{$path}: erreur", ['error' => $e->getMessage()]);
                return ['status' => null, 'json' => null, 'error' => 'afrikard/Bamboo injoignable'];
            }
        }

        return ['status' => null, 'json' => null, 'error' => 'afrikard/Bamboo injoignable'];
    }

    /**
     * Cache un appel : seul un résultat `ok` est persisté (TTL court). Un échec
     * est retourné tel quel et pourra être re-tenté à la prochaine visite.
     */
    private function rememberOk(string $key, int $ttl, callable $fetch): array
    {
        if (($cached = cache()->get($key)) !== null) {
            return $cached;
        }

        $result = $fetch();

        if (($result['ok'] ?? false) === true) {
            cache()->put($key, $result, $ttl);
        }

        return $result;
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

        return $this->rememberOk($cacheKey, self::CACHE_TTL, function () {
            $data = $this->proxyGet('/accounts');

            if ($data['error'] !== null) {
                return ['ok' => false, 'accounts' => [], 'fetched_at' => null, 'error' => $data['error']];
            }

            return [
                'ok'         => true,
                'accounts'   => $data['json']['accounts'] ?? [],
                'fetched_at' => now()->toDateTimeString(),
                'error'      => null,
            ];
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

        return $this->rememberOk($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate) {
            $data = $this->proxyGet('/transactions', [
                'startDate' => $startDate,
                'endDate'   => $endDate,
            ], 20);

            if ($data['error'] !== null) {
                return ['ok' => false, 'clients' => [], 'fetched_at' => null, 'error' => $data['error']];
            }

            return [
                'ok'         => true,
                'clients'    => $data['json']['clients'] ?? [],
                'fetched_at' => now()->toDateTimeString(),
                'error'      => null,
            ];
        });
    }

    /**
     * Taux de change officiels Bamboo (proxy afrikard → /exchange-rates).
     *
     * @return array{ok:bool, base:?string, rates:array, error:?string}
     */
    public function exchangeRates(?bool $fresh = false): array
    {
        $cacheKey = 'bamboo_rates_v1';

        if ($fresh) {
            cache()->forget($cacheKey);
        }

        return $this->rememberOk($cacheKey, 600, function () {
            $data = $this->proxyGet('/exchange-rates');

            if ($data['error'] !== null) {
                return ['ok' => false, 'base' => null, 'rates' => [], 'error' => $data['error']];
            }

            return [
                'ok'    => true,
                'base'  => $data['json']['baseCurrencyCode'] ?? null,
                'rates' => $data['json']['rates'] ?? [],
                'error' => null,
            ];
        });
    }

    /**
     * Historique des commandes Bamboo sur la période (réconciliation).
     *
     * @return array{ok:bool, orders:array, error:?string}
     */
    public function orderReport(string $startDate, string $endDate, ?bool $fresh = false): array
    {
        $cacheKey = 'bamboo_orders_v1_' . $startDate . '_' . $endDate;

        if ($fresh) {
            cache()->forget($cacheKey);
        }

        return $this->rememberOk($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate) {
            $data = $this->proxyGet('/orders/report', [
                'startDate' => $startDate,
                'endDate'   => $endDate,
            ], 20);

            if ($data['error'] !== null) {
                return ['ok' => false, 'orders' => [], 'error' => $data['error']];
            }

            return [
                'ok'     => true,
                'orders' => $data['json'] ?? [],
                'error'  => null,
            ];
        });
    }
}