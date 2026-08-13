<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInboundWhatsApp;
use App\Models\WhatsAppMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Réception des webhooks WHAPI (messages entrants + statuts de livraison).
 *
 * Sécurité : endpoint public → protégé par un secret partagé (query `?secret=`
 * ou header X-Webhook-Secret) comparé à config('services.whapi.webhook_secret').
 * Fail-closed : sans secret configuré ou en cas de non-correspondance → 403.
 *
 * On répond 200 rapidement ; le traitement lourd (bot IA) est délégué à un job.
 */
class WhapiWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $expected = (string) config('services.whapi.webhook_secret');
        $provided = (string) ($request->query('secret') ?? $request->header('X-Webhook-Secret', ''));

        if ($expected === '' || !hash_equals($expected, $provided)) {
            Log::warning('WHAPI webhook: secret invalide', ['ip' => $request->ip()]);
            return response()->json(['ok' => false], 403);
        }

        $payload = $request->all();

        // 1) Statuts de livraison des messages SORTANTS (sent/delivered/read/failed).
        foreach (($payload['statuses'] ?? []) as $status) {
            $this->applyStatus($status);
        }

        // 2) Messages ENTRANTS → journalisés puis remis au job de traitement.
        foreach (($payload['messages'] ?? []) as $msg) {
            // Ignorer nos propres messages (echo) et les non-messages.
            if (($msg['from_me'] ?? false) === true) {
                continue;
            }
            $this->storeInbound($msg);
        }

        return response()->json(['ok' => true]);
    }

    /** Met à jour le statut d'un message sortant d'après son id WHAPI. */
    private function applyStatus(array $status): void
    {
        $providerId = $status['id'] ?? null;
        $state      = $status['status'] ?? null;   // sent|delivered|read|failed
        if (!$providerId || !$state) {
            return;
        }

        $message = WhatsAppMessage::where('provider_message_id', $providerId)->first();
        if (!$message) {
            return;
        }

        $map = [
            'sent'      => WhatsAppMessage::STATUS_SENT,
            'delivered' => WhatsAppMessage::STATUS_DELIVERED,
            'read'      => WhatsAppMessage::STATUS_READ,
            'failed'    => WhatsAppMessage::STATUS_FAILED,
        ];
        $mapped = $map[$state] ?? null;
        if (!$mapped) {
            return;
        }

        $updates = ['status' => $mapped];
        if ($state === 'delivered' && !$message->delivered_at) {
            $updates['delivered_at'] = now();
        }
        if ($state === 'read' && !$message->read_at) {
            $updates['read_at'] = now();
        }
        $message->update($updates);
    }

    /** Journalise un message entrant et déclenche son traitement asynchrone. */
    private function storeInbound(array $msg): void
    {
        $from = $msg['from'] ?? ($msg['chat_id'] ?? null);
        if (!$from) {
            return;
        }
        $from = \App\Services\WhapiService::normalize((string) $from);

        // Extraction du texte selon le type WHAPI (text.body, ou fallback).
        $type = $msg['type'] ?? 'text';
        $body = $msg['text']['body']
            ?? $msg['button']['text']
            ?? $msg['interactive']['reply']['title']
            ?? null;

        $record = WhatsAppMessage::create([
            'direction'           => WhatsAppMessage::DIR_IN,
            'phone'               => $from,
            'type'                => $type,
            'category'            => WhatsAppMessage::CAT_SUPPORT,
            'body'                => $body ? mb_substr($body, 0, 2000) : null,
            'payload'             => $msg,
            'status'              => WhatsAppMessage::STATUS_RECEIVED,
            'provider_message_id' => $msg['id'] ?? null,
        ]);

        ProcessInboundWhatsApp::dispatch($record->id)->onQueue('default');
    }
}
