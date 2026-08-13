<?php

namespace App\Console\Commands;

use App\Jobs\ProcessInboundWhatsApp;
use App\Models\WhatsAppMessage;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Simule un message WhatsApp ENTRANT en local, sans tunnel ni compte WHAPI.
 *
 * Fabrique la charge utile exacte que WHAPI enverrait, la passe par la VRAIE
 * route webhook (donc le contrôle du secret partagé est réellement exercé),
 * puis affiche ce que le bot a produit en réponse.
 *
 *   php artisan whatsapp:simulate-inbound "c'est quoi le prix de Netflix ?"
 *   php artisan whatsapp:simulate-inbound "bonjour" --from=241066000000
 *   php artisan whatsapp:simulate-inbound "je veux un humain" --now
 *
 * L'envoi de la RÉPONSE part par WHAPI : sans WHAPI_TOKEN elle échouera, mais
 * tout le reste de la chaîne (webhook → journal → job → Mistral → outils) est
 * bien vérifié. Le texte produit est affiché dans tous les cas.
 */
class SimulateInboundWhatsApp extends Command
{
    protected $signature = 'whatsapp:simulate-inbound
                            {message : le texte envoyé par le client}
                            {--from=241000000000 : numéro expéditeur (E.164 sans +)}
                            {--now : traiter tout de suite au lieu d\'enfiler le job}';

    protected $description = 'Simule un message WhatsApp entrant (test local du support, sans WHAPI).';

    public function handle(): int
    {
        $secret = (string) config('services.whapi.webhook_secret');
        if ($secret === '') {
            $this->error('WHAPI_WEBHOOK_SECRET est vide : le webhook refuse tout (fail-closed).');
            $this->line('  Ajoutez-le dans .env, puis relancez. Générer : <fg=cyan>openssl rand -hex 32</>');
            return self::FAILURE;
        }

        $from = (string) $this->option('from');
        $text = (string) $this->argument('message');

        // Charge utile identique à celle de WHAPI (événement « messages »).
        $payload = [
            'messages' => [[
                'id'        => 'SIM.' . Str::upper(Str::random(20)),
                'from_me'   => false,
                'type'      => 'text',
                'chat_id'   => $from . '@s.whatsapp.net',
                'from'      => $from,
                'from_name' => 'Test local',
                'timestamp' => now()->timestamp,
                'text'      => ['body' => $text],
            ]],
        ];

        $this->line('');
        $this->info("→ Entrant simulé  [{$from}]  « {$text} »");

        // Passage par la vraie route : middleware + vérification du secret inclus.
        $request = Request::create(
            '/api/webhooks/whapi?secret=' . urlencode($secret),
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode($payload)
        );

        $before   = WhatsAppMessage::max('id') ?? 0;
        $response = app()->handle($request);

        if ($response->getStatusCode() !== 200) {
            $this->error('Webhook refusé : HTTP ' . $response->getStatusCode() . ' — ' . $response->getContent());
            return self::FAILURE;
        }
        $this->line('  <fg=green>✓</> webhook accepté (200), message journalisé');

        $inbound = WhatsAppMessage::where('id', '>', $before)
            ->where('direction', WhatsAppMessage::DIR_IN)
            ->latest('id')
            ->first();

        if (!$inbound) {
            $this->error('Aucun message entrant enregistré — vérifiez la base.');
            return self::FAILURE;
        }

        // Traitement : soit immédiat, soit laissé à la file d'attente.
        if ($this->option('now')) {
            $this->line('  <fg=gray>traitement immédiat…</>');
            // handle() reçoit ses dépendances par injection du conteneur.
            app()->call([new ProcessInboundWhatsApp($inbound->id), 'handle']);
        } elseif (config('queue.default') === 'sync') {
            $this->line('  <fg=gray>file « sync » : le job a déjà tourné.</>');
        } else {
            $this->warn('  Job enfilé sur la file « ' . config('queue.default') . ' ».');
            $this->line('  <fg=gray>Lancez un worker dans un autre terminal :</> <fg=cyan>php artisan queue:work</>');
            $this->line('  <fg=gray>ou relancez avec</> <fg=cyan>--now</> <fg=gray>pour traiter sans worker.</>');
            return self::SUCCESS;
        }

        // Ce que le bot a répondu (journalisé même si l'envoi WHAPI a échoué).
        $replies = WhatsAppMessage::where('id', '>', $inbound->id)
            ->where('direction', WhatsAppMessage::DIR_OUT)
            ->orderBy('id')
            ->get();

        $this->line('');
        if ($replies->isEmpty()) {
            $this->warn('← Aucune réponse produite. Causes possibles :');
            $this->line('   • MISTRAL_API_KEY absente → le bot ne peut pas répondre');
            $this->line('   • message routé vers l\'admin (numéro dans WHAPI_ADMIN_NUMBERS)');
            $this->line('   • voir storage/logs/laravel.log');
            return self::SUCCESS;
        }

        foreach ($replies as $reply) {
            $this->info("← Réponse [{$reply->type}] vers {$reply->phone} — statut : {$reply->status}");
            $this->line('  ' . str_replace("\n", "\n  ", (string) $reply->body));
        }

        $this->line('');
        $this->line('<fg=gray>Statut « failed » = WHAPI n\'a pas pu envoyer (token absent en local) :');
        $this->line('c\'est attendu, le reste de la chaîne est bien validé.</>');

        return self::SUCCESS;
    }
}
