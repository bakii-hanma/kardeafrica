<?php

namespace App\Jobs;

use App\Models\WhatsAppMessage;
use App\Services\WhapiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Envoie un message WhatsApp journalisé (table whatsapp_messages) via WHAPI.
 *
 * Idempotent : un message déjà envoyé (sent/delivered/read) n'est jamais rejoué.
 * Retries sur erreur réseau/HTTP transitoire ; sur « non configuré » on échoue
 * définitivement sans gaspiller les tentatives.
 */
class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 60, 300];
    public $timeout = 30;

    public function __construct(public int $messageId) {}

    public function handle(WhapiService $whapi): void
    {
        $m = WhatsAppMessage::find($this->messageId);
        if (!$m) {
            return;
        }

        // Idempotence : ne jamais renvoyer un message déjà parti.
        if (in_array($m->status, [
            WhatsAppMessage::STATUS_SENT,
            WhatsAppMessage::STATUS_DELIVERED,
            WhatsAppMessage::STATUS_READ,
        ], true)) {
            return;
        }

        $res = $whapi->send($m->type, $m->phone, $m->payload ?? []);

        if ($res['ok']) {
            $m->update([
                'status'              => WhatsAppMessage::STATUS_SENT,
                'provider_message_id' => $res['id'],
                'sent_at'             => now(),
                'error'               => null,
            ]);
            return;
        }

        // Sans configuration WHAPI, réessayer est inutile → échec définitif.
        if ($res['error'] === 'not_configured') {
            $m->update(['status' => WhatsAppMessage::STATUS_FAILED, 'error' => 'not_configured']);
            Log::warning('SendWhatsAppMessage: WHAPI non configuré', ['id' => $m->id]);
            return;
        }

        // Erreur transitoire : mémoriser puis relancer une exception pour retry.
        $m->update(['error' => $res['error']]);

        if ($this->attempts() >= $this->tries) {
            $m->update(['status' => WhatsAppMessage::STATUS_FAILED]);
            Log::error('SendWhatsAppMessage: échec définitif', ['id' => $m->id, 'error' => $res['error']]);
            return;
        }

        throw new \RuntimeException('WHAPI send failed: ' . ($res['error'] ?? 'unknown'));
    }

    public function failed(\Throwable $e): void
    {
        $m = WhatsAppMessage::find($this->messageId);
        if ($m && !in_array($m->status, [WhatsAppMessage::STATUS_SENT, WhatsAppMessage::STATUS_DELIVERED, WhatsAppMessage::STATUS_READ], true)) {
            $m->update(['status' => WhatsAppMessage::STATUS_FAILED, 'error' => mb_substr($e->getMessage(), 0, 200)]);
        }
    }
}
