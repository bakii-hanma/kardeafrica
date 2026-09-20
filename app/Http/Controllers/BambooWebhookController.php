<?php

namespace App\Http\Controllers;

use App\Jobs\HandleBambooOrderCompleted;
use App\Jobs\HandleBambooProductUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Réception des webhooks Svix Bamboo : `ordercompleted.v1` et `productupdated.v1`.
 *
 * Sécurité : endpoint public → protégé par la signature Svix (HMAC-SHA256).
 * Fail-closed : pas de secret configuré, horodatage hors fenêtre (±5 min) ou
 * signature invalide → 400/403, sans traitement.
 *
 * On répond 200 rapidement ; le traitement lourd est délégué à un job (queue
 * Redis), comme pour le webhook WHAPI.
 */
class BambooWebhookController extends Controller
{
    /** Tolérance de rejeu Svix : ±5 minutes. */
    private const TIMESTAMP_TOLERANCE_SECONDS = 300;

    public function handle(Request $request)
    {
        $secret = (string) config('services.bamboo.webhook_secret');

        if ($secret === '') {
            Log::warning('Bamboo webhook: secret non configuré — rejeté.');
            return response()->json(['ok' => false], 403);
        }

        $payload = $request->getContent();

        if (! $this->verifySvix($secret, (string) $request->header('svix-id', ''), (string) $request->header('svix-timestamp', ''), (string) $request->header('svix-signature', ''), $payload)) {
            Log::warning('Bamboo webhook: signature Svix invalide', ['ip' => $request->ip()]);
            return response()->json(['ok' => false], 400);
        }

        $event = $request->input('eventType');

        match ($event) {
            'ordercompleted.v1'    => HandleBambooOrderCompleted::dispatch($request->all()),
            'productupdated.v1'    => HandleBambooProductUpdated::dispatch($request->input('products', [])),
            default                => Log::info('Bamboo webhook: événement ignoré', ['eventType' => $event]),
        };

        return response()->json(['ok' => true]);
    }

    /**
     * Vérifie la signature Svix sans dépendance externe.
     *
     * Procédure (docs Svix) :
     *  1. message = "{svix-id}.{svix-timestamp}.{payload}"
     *  2. clé     = base64_decode(secret sans le préfixe éventuel "whsec_")
     *  3. attendu = base64(hmac_sha256(message, clé))
     *  4. le header svix-signature liste "v1,<sig>[ vN,<sig>]" → l'une doit matcher
     *  5. |now - svix-timestamp| ≤ 5 min (anti-rejeu)
     */
    private function verifySvix(string $secret, string $id, string $timestamp, string $signatureHeader, string $payload): bool
    {
        if ($id === '' || $timestamp === '' || $signatureHeader === '') {
            return false;
        }

        // Anti-rejeu : l'horodatage doit être récent.
        if (! ctype_digit($timestamp)) {
            return false;
        }
        if (abs(time() - (int) $timestamp) > self::TIMESTAMP_TOLERANCE_SECONDS) {
            return false;
        }

        $key = str_starts_with($secret, 'whsec_') ? substr($secret, 6) : $secret;
        $key = base64_decode($key, true);
        if ($key === false) {
            return false;
        }

        $message = $id . '.' . $timestamp . '.' . $payload;
        $expected = base64_encode(hash_hmac('sha256', $message, $key, true));

        $provided = explode(' ', trim($signatureHeader));
        foreach ($provided as $candidate) {
            if (! str_starts_with($candidate, 'v1,')) {
                continue;
            }
            if (hash_equals($expected, substr($candidate, 3))) {
                return true;
            }
        }

        return false;
    }
}