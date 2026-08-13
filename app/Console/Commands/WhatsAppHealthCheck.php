<?php

namespace App\Console\Commands;

use App\Services\MistralService;
use App\Services\WhapiService;
use App\Support\PopularHighlights;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Diagnostic de la chaîne WhatsApp : dit en un coup d'œil ce qui est configuré,
 * ce qui répond, et ce qui manque encore. À lancer juste après avoir renseigné
 * les variables (Railway ou .env), AVANT d'ouvrir le support aux clients.
 *
 *   php artisan whatsapp:health
 *   php artisan whatsapp:health --send=24106XXXXXX   (envoi de test réel)
 *
 * Ne rend JAMAIS le token ni la clé Mistral : seulement leur présence.
 */
class WhatsAppHealthCheck extends Command
{
    protected $signature = 'whatsapp:health {--send= : numéro E.164 destinataire d\'un message de test}';
    protected $description = 'Vérifie la configuration WhatsApp (WHAPI + Mistral) et l\'accessibilité du webhook.';

    private int $blocking = 0;

    public function handle(WhapiService $whapi, MistralService $mistral): int
    {
        $this->line('');
        $this->info('── Configuration ──────────────────────────────');

        $token = (string) config('services.whapi.token');
        $this->row('WHAPI_TOKEN', $token !== '', $token !== ''
            ? 'présent (' . strlen($token) . ' caractères)'
            : 'MANQUANT — le support et les notifications sont inertes', true);

        $this->row('WHAPI_BASE_URL', true, (string) config('services.whapi.base_url'));

        $admin = (string) config('services.whapi.admin_number');
        $this->row('WHAPI_ADMIN_NUMBER', $admin !== '', $admin !== ''
            ? WhapiService::normalize($admin)
            : 'MANQUANT — personne ne reçoit les alertes admin', true);

        $agent = (string) config('services.whapi.agent_number');
        $this->row('WHAPI_AGENT_NUMBER', true, $agent !== ''
            ? WhapiService::normalize($agent)
            : 'vide → les escalades partent sur le numéro admin');

        $admins = (string) config('services.whapi.admin_numbers');
        $this->row('WHAPI_ADMIN_NUMBERS', true, $admins !== ''
            ? count(array_filter(explode(',', $admins))) . ' numéro(s) pilote(s) du catalogue'
            : 'vide → repli sur WHAPI_ADMIN_NUMBER');

        $secret = (string) config('services.whapi.webhook_secret');
        $this->row('WHAPI_WEBHOOK_SECRET', $secret !== '', $secret !== ''
            ? 'présent — webhook protégé'
            : 'MANQUANT — le webhook REFUSE tout (fail-closed) : aucun message entrant', true);

        $channel = (string) config('services.whapi.channel_id');
        $this->row('WHAPI_CHANNEL_ID', true, $channel !== ''
            ? $channel
            : 'vide → pas de diffusion channel (announce-new-cards inactif)');

        $this->row('WHAPI_CATALOG_SYNC_ENABLED', true, config('services.whapi.catalog_sync')
            ? 'activé'
            : 'désactivé → whatsapp:catalog-sync ne pousse rien (compte Business requis)');

        $key = (string) config('services.mistral.key');
        $this->row('MISTRAL_API_KEY', $key !== '', $key !== ''
            ? 'présente — assistante Kara active (' . config('services.mistral.model') . ')'
            : 'MANQUANTE — le bot ne répond pas, tout est escaladé à l\'agent', true);

        $this->line('');
        $this->info('── Webhook entrant ────────────────────────────');
        $url = url('/api/webhooks/whapi') . ($secret !== '' ? '?secret=' . str_repeat('•', 8) : '');
        $this->row('URL à déclarer chez WHAPI', true, $url);
        $this->line('  <fg=gray>Renseignez l\'URL réelle (avec le secret en clair) dans le');
        $this->line('  tableau de bord WHAPI → Channel → Webhooks, événements « messages ».</>');

        $this->line('');
        $this->info('── Connectivité ───────────────────────────────');

        if ($token === '') {
            $this->row('Appel WHAPI', false, 'ignoré (pas de token)');
        } else {
            $res = $this->pingWhapi();
            $this->row('Appel WHAPI /health', $res['ok'], $res['detail'], !$res['ok']);
        }

        if ($key === '') {
            $this->row('Appel Mistral', false, 'ignoré (pas de clé)');
        } else {
            $ok = $mistral->isConfigured();
            $this->row('Mistral configuré', $ok, $ok ? 'oui' : 'non', !$ok);
        }

        $this->line('');
        $this->info('── Contenu prêt à servir ──────────────────────');
        $highlights = PopularHighlights::resolved();
        $this->row('Cartes populaires résolues', count($highlights) > 0,
            count($highlights) . ' carte(s) : ' . implode(', ', array_column($highlights, 'key')),
            count($highlights) === 0);
        $this->row('Cartes Gabon actives', true,
            \App\Models\MerchantCard::where('is_active', true)->count() . ' carte(s)');

        // Envoi de test réel — la seule preuve qui compte.
        if ($to = $this->option('send')) {
            $this->line('');
            $this->info('── Message de test ────────────────────────────');
            if (!$whapi->isConfigured()) {
                $this->error('Impossible : WHAPI_TOKEN absent.');
            } else {
                $sent = $whapi->sendText($to, "✅ KardAfrica — test de configuration WhatsApp.\n"
                    . 'Si vous lisez ceci, l\'envoi sortant fonctionne.');
                $this->row('Envoi vers ' . WhapiService::normalize($to), $sent,
                    $sent ? 'reçu par WHAPI' : 'échec — voir laravel.log', !$sent);
            }
        }

        $this->line('');
        if ($this->blocking > 0) {
            $this->error("{$this->blocking} point(s) bloquant(s) : le support client n'est pas encore opérationnel.");
            return self::FAILURE;
        }
        $this->info('Tout est en place. Le support client WhatsApp peut être ouvert.');
        return self::SUCCESS;
    }

    /** Ping WHAPI : /health est le endpoint d'état du canal. */
    private function pingWhapi(): array
    {
        try {
            $res = Http::withToken((string) config('services.whapi.token'))
                ->timeout(10)
                ->get(rtrim((string) config('services.whapi.base_url'), '/') . '/health');

            if ($res->successful()) {
                $status = $res->json()['status']['text'] ?? $res->json()['status'] ?? 'OK';
                return ['ok' => true, 'detail' => 'canal joignable — statut : ' . (is_string($status) ? $status : json_encode($status))];
            }
            return ['ok' => false, 'detail' => 'HTTP ' . $res->status() . ' — token invalide ou canal inactif ?'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'detail' => 'injoignable : ' . $e->getMessage()];
        }
    }

    private function row(string $label, bool $ok, string $detail, bool $isBlocking = false): void
    {
        if ($isBlocking && !$ok) $this->blocking++;
        $mark = $ok ? '<fg=green>✓</>' : ($isBlocking ? '<fg=red>✗</>' : '<fg=yellow>–</>');
        $this->line(sprintf('  %s %-28s %s', $mark, $label, $detail));
    }
}
