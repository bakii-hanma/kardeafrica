<?php

namespace Tests\Feature;

use App\Services\BambooReportingService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * BambooReportingService : les 429 (rate-limit Bamboo) sont réessayés avec
 * backoff et ne figent jamais un échec transitoire dans le cache.
 */
class BambooReportingServiceTest extends TestCase
{
    private function service(): BambooReportingService
    {
        return app(BambooReportingService::class);
    }

    public function test_transactions_retry_sur_429(): void
    {
        Http::fake([
            '*/transactions*' => Http::sequence()
                ->push(['error' => 'rate limit'], 429)
                ->push([
                    'clients' => [
                        ['clientId' => 1, 'transactions' => [['transactionId' => 'T1']]],
                    ],
                ], 200),
        ]);

        $result = $this->service()->transactions('2026-09-01', '2026-09-20');

        $this->assertTrue($result['ok']);
        $this->assertCount(1, $result['clients']);
        $this->assertNull($result['error']);
    }

    public function test_transactions_429_abandonne_apres_retries(): void
    {
        Http::fake([
            '*/transactions*' => Http::response(['error' => 'rate limit'], 429),
        ]);

        $result = $this->service()->transactions('2026-09-01', '2026-09-20');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('429', (string) $result['error']);
        $this->assertEmpty($result['clients']);
    }

    public function test_transactions_echec_non_cache_mais_succes_cache(): void
    {
        Http::fake([
            '*/transactions*' => Http::sequence()
                ->push(['error' => 'rate limit'], 429)
                ->push(['error' => 'rate limit'], 429)
                ->push(['error' => 'rate limit'], 429)
                ->push(['clients' => [['clientId' => 1, 'transactions' => []]]], 200),
        ]);

        $service = $this->service();

        // Premier appel : 3 réponses 429 → abandon, non cache (les retries
        // épuisent la séquence, l'appel suivant re-tentera immédiatement).
        $this->assertFalse($service->transactions('2026-09-01', '2026-09-20', true)['ok']);

        // Si le premier échec avait été cache, ce second appel rendrait encore
        // l'échec. Il re-tente donc le proxy.
        $this->assertTrue($service->transactions('2026-09-01', '2026-09-20')['ok']);

        // Le succès est bien persisté : plus d'appel réseau ensuite.
        Http::preventStrayRequests();
        $this->assertTrue($service->transactions('2026-09-01', '2026-09-20')['ok']);
    }
}