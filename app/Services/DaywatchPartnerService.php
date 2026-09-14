<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DaywatchPartnerService — émission de VRAIS codes cadeaux Daywatch.
 *
 * KardAfrica est « partenaire » Daywatch : à l'achat d'un abonnement, on alloue
 * un vrai code `DW-XXXX-XXXX-XXXX` via l'API partenaire (authentifiée par une
 * clé `dwpk_live_...`), au lieu de fabriquer un code local qui ne s'active pas.
 *
 * Sécurité :
 *   - Clé stockée UNIQUEMENT côté serveur (config/services → .env).
 *   - On ne logge JAMAIS les codes émis (comme afrikard, cf. C7) : uniquement
 *     le statut HTTP et le nombre de codes.
 *   - Idempotence : `allocationRef` empêche la double-allocation sur retry HTTP.
 */
class DaywatchPartnerService
{
    private ?string $key;
    private string $base;

    public function __construct()
    {
        $this->key  = config('services.daywatch.partner_key');
        $this->base = rtrim((string) config('services.daywatch.base_url', 'https://api.daywatch.online/api'), '/');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->key);
    }

    /**
     * Alloue `quantity` codes pour un plan (DayBreak|DayTrip|DayDream|DayLight|DaySaga|AllDay).
     *
     * @return array{success:bool, codes:array<int,string>, plan:?array, designs:array, redemptionUrl:?string, error:?string}
     */
    public function allocate(string $planName, int $quantity, ?string $allocationRef = null): array
    {
        $fail = fn (string $error) => [
            'success' => false, 'codes' => [], 'plan' => null,
            'designs' => [], 'redemptionUrl' => null, 'error' => $error,
        ];

        if (! $this->isConfigured()) {
            Log::warning('Daywatch partenaire non configuré — allocation impossible');
            return $fail('not_configured');
        }

        $quantity = max(1, min(100, $quantity));

        $payload = ['planName' => $planName, 'quantity' => $quantity];
        if ($allocationRef !== null && $allocationRef !== '') {
            $payload['allocationRef'] = $allocationRef;
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders(['X-Api-Key' => $this->key])
                ->acceptJson()
                ->post($this->base . '/gift-cards/allocate', $payload);
        } catch (\Throwable $e) {
            Log::error('Daywatch allocate: exception réseau', [
                'plan' => $planName, 'error' => $e->getMessage(),
            ]);
            return $fail('network');
        }

        $json = is_array($response->json()) ? $response->json() : [];

        if (! $response->successful() || ! ($json['success'] ?? false)) {
            $error = $json['error'] ?? $json['message'] ?? ('http_' . $response->status());
            Log::error('Daywatch allocate: échec', [
                'plan'   => $planName,
                'qty'    => $quantity,
                'status' => $response->status(),
                'error'  => is_string($error) ? $error : json_encode($error),
            ]);
            return $fail(is_string($error) ? $error : 'error');
        }

        $codes = array_values(array_filter((array) ($json['codes'] ?? [])));

        // C7 : on ne logge pas les codes, seulement leur nombre.
        Log::info('Daywatch allocate: succès', [
            'plan'       => $planName,
            'codes_sent' => $quantity,
            'codes_got'  => count($codes),
        ]);

        return [
            'success'       => true,
            'codes'         => $codes,
            'plan'          => $json['plan'] ?? null,
            'designs'       => (array) ($json['designs'] ?? []),
            'redemptionUrl' => $json['redemptionUrl'] ?? null,
            'error'         => null,
        ];
    }

    /**
     * Stock disponible par plan autorisé.
     *
     * @return array<int,array{planId:int, planName:string, durationLabel:?string, availableStock:int}>
     */
    public function stock(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['X-Api-Key' => $this->key])
                ->acceptJson()
                ->get($this->base . '/gift-cards/stock');
        } catch (\Throwable $e) {
            Log::error('Daywatch stock: exception réseau', ['error' => $e->getMessage()]);
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        return (array) ($response->json()['data'] ?? []);
    }

    /**
     * Carte le design (front/back) par code, depuis le tableau `designs` d'une
     * réponse allocate.
     *
     * @return array{frontUrl:?string, backUrl:?string}
     */
    public static function designFor(array $designs, string $code): array
    {
        foreach ($designs as $d) {
            if (($d['code'] ?? null) === $code) {
                return [
                    'frontUrl' => self::https($d['frontUrl'] ?? null),
                    'backUrl'  => self::https($d['backUrl'] ?? null),
                ];
            }
        }
        return ['frontUrl' => null, 'backUrl' => null];
    }

    /** L'API sert ses visuels en http:// alors qu'elle répond en https. */
    private static function https(?string $url): ?string
    {
        return $url ? preg_replace('#^http://#i', 'https://', $url) : null;
    }
}
