<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * MistralService — appel bas-niveau de l'API Mistral (chat completions), avec
 * support du function-calling. La clé vit uniquement côté serveur
 * (config/services.mistral). Tolérant aux pannes : renvoie un résultat
 * normalisé, ne lève jamais d'exception.
 */
class MistralService
{
    private ?string $key;
    private string $model;
    private string $baseUrl;

    public function __construct()
    {
        $this->key     = config('services.mistral.key');
        $this->model   = (string) config('services.mistral.model', 'mistral-small-latest');
        $this->baseUrl = rtrim((string) config('services.mistral.base_url', 'https://api.mistral.ai'), '/');
    }

    public function isConfigured(): bool
    {
        return !empty($this->key);
    }

    /**
     * Un tour de chat. $messages au format OpenAI/Mistral ; $tools = définitions
     * de fonctions. Renvoie ['ok'=>bool, 'message'=>?array, 'error'=>?string]
     * où message est l'objet assistant (content + éventuels tool_calls).
     *
     * @return array{ok:bool,message:?array,error:?string}
     */
    public function chat(array $messages, array $tools = [], array $opts = []): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'message' => null, 'error' => 'not_configured'];
        }

        $body = [
            'model'       => $opts['model'] ?? $this->model,
            'messages'    => $messages,
            'temperature' => $opts['temperature'] ?? 0.2,
            'max_tokens'  => $opts['max_tokens'] ?? 700,
        ];
        if (!empty($tools)) {
            $body['tools']       = $tools;
            $body['tool_choice'] = $opts['tool_choice'] ?? 'auto';
        }

        try {
            $response = Http::timeout($opts['timeout'] ?? 30)
                ->withToken($this->key)
                ->acceptJson()
                ->post($this->baseUrl . '/v1/chat/completions', $body);

            if (!$response->successful()) {
                $json  = is_array($response->json()) ? $response->json() : [];
                $error = $json['message'] ?? $json['error']['message'] ?? ('http_' . $response->status());
                Log::error('Mistral chat échoué', ['status' => $response->status(), 'error' => is_string($error) ? $error : 'error']);
                return ['ok' => false, 'message' => null, 'error' => is_string($error) ? $error : 'error'];
            }

            $message = $response->json()['choices'][0]['message'] ?? null;
            if (!is_array($message)) {
                return ['ok' => false, 'message' => null, 'error' => 'empty_response'];
            }
            return ['ok' => true, 'message' => $message, 'error' => null];
        } catch (\Throwable $e) {
            Log::error('Mistral exception réseau', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => null, 'error' => 'network'];
        }
    }
}
