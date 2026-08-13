<?php

namespace App\Services;

use App\Jobs\SendWhatsAppMessage;
use App\Models\WhatsAppMessage;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

/**
 * WhatsAppNotifier — API applicative pour ENFILER un message WhatsApp.
 *
 * C'est le point d'entrée que le reste de l'app doit utiliser (jamais
 * WhapiService directement) : il journalise le message, applique le
 * dédoublonnage (dedup_key), respecte le consentement (category=marketing),
 * puis délègue l'envoi réel au job asynchrone SendWhatsAppMessage — immédiat
 * ou programmé (scheduled_at).
 *
 * Options communes ($opts) :
 *   - category    : transactional|support|marketing|otp|ops (def. transactional)
 *   - dedup_key   : string — empêche un doublon (ex. "order-ready-123")
 *   - context     : [type, id] — rattachement métier
 *   - at          : Carbon|null — envoi programmé (sinon immédiat)
 *   - preview     : string — aperçu texte pour l'UI admin (déf. dérivé)
 */
class WhatsAppNotifier
{
    /** Message texte. */
    public function text(string $phone, string $body, array $opts = []): ?WhatsAppMessage
    {
        return $this->enqueue('text', $phone, ['body' => $body], $opts + ['preview' => $body]);
    }

    /** Image depuis une URL publique. */
    public function image(string $phone, string $mediaUrl, ?string $caption = null, array $opts = []): ?WhatsAppMessage
    {
        $payload = ['media' => $mediaUrl] + ($caption ? ['caption' => $caption] : []);
        return $this->enqueue('image', $phone, $payload, $opts + ['preview' => $caption ?? '[image]']);
    }

    /** Document depuis une URL publique. */
    public function document(string $phone, string $mediaUrl, ?string $filename = null, ?string $caption = null, array $opts = []): ?WhatsAppMessage
    {
        $payload = ['media' => $mediaUrl]
            + ($filename ? ['filename' => $filename] : [])
            + ($caption ? ['caption' => $caption] : []);
        return $this->enqueue('document', $phone, $payload, $opts + ['preview' => $caption ?? ($filename ?? '[document]')]);
    }

    /**
     * Boutons de réponse rapide.
     * @param array<int,array{id:string,title:string}> $buttons
     */
    public function buttons(string $phone, string $body, array $buttons, array $opts = []): ?WhatsAppMessage
    {
        $action = array_map(
            fn ($b) => ['type' => 'quick_reply', 'title' => mb_substr($b['title'], 0, 20), 'id' => $b['id']],
            array_slice(array_values($buttons), 0, 3),
        );
        $payload = ['type' => 'button', 'body' => ['text' => $body], 'action' => ['buttons' => $action]];
        return $this->enqueue('interactive', $phone, $payload, $opts + ['preview' => $body]);
    }

    /**
     * Poste un texte sur le CHANNEL WhatsApp (newsletter) de diffusion. Les
     * abonnés ont opté par leur abonnement → pas de contrôle opt-in par numéro.
     * Renvoie null si aucun channel n'est configuré.
     */
    public function channelText(string $text, array $opts = []): ?WhatsAppMessage
    {
        $channel = config('services.whapi.channel_id');
        if (empty($channel)) return null;
        return $this->enqueue('text', $channel, ['body' => $text],
            $opts + ['category' => WhatsAppMessage::CAT_MARKETING, 'skip_optin' => true, 'preview' => $text]);
    }

    /** Poste une image (URL) sur le channel de diffusion. */
    public function channelImage(string $mediaUrl, ?string $caption = null, array $opts = []): ?WhatsAppMessage
    {
        $channel = config('services.whapi.channel_id');
        if (empty($channel)) return null;
        $payload = ['media' => $mediaUrl] + ($caption ? ['caption' => $caption] : []);
        return $this->enqueue('image', $channel, $payload,
            $opts + ['category' => WhatsAppMessage::CAT_MARKETING, 'skip_optin' => true, 'preview' => $caption ?? '[image]']);
    }

    // ------------------------------------------------------------------
    // Cœur
    // ------------------------------------------------------------------

    /**
     * Journalise + enfile un message. Retourne le modèle créé, ou null si le
     * message a été ignoré (doublon, ou marketing sans consentement).
     */
    public function enqueue(string $type, string $phone, array $payload, array $opts = []): ?WhatsAppMessage
    {
        $phone    = WhapiService::normalize($phone);
        $category = $opts['category'] ?? WhatsAppMessage::CAT_TRANSACTIONAL;
        $dedupKey = $opts['dedup_key'] ?? null;

        if ($phone === '') {
            return null;
        }

        // Consentement : les messages marketing exigent un opt-in explicite.
        // Exception : les posts de channel (skip_optin) — l'abonnement vaut opt-in.
        if ($category === WhatsAppMessage::CAT_MARKETING
            && empty($opts['skip_optin'])
            && !$this->isMarketingAllowed($phone)) {
            Log::info('WhatsAppNotifier: marketing sans opt-in — ignoré', ['to' => $phone]);
            return null;
        }

        // Dédoublonnage : si un message porte déjà cette clé, on ne renvoie pas.
        if ($dedupKey !== null) {
            $existing = WhatsAppMessage::where('dedup_key', $dedupKey)->first();
            if ($existing) {
                return $existing;
            }
        }

        [$ctxType, $ctxId] = $opts['context'] ?? [null, null];
        $at = $opts['at'] ?? null;

        $message = WhatsAppMessage::create([
            'direction'    => WhatsAppMessage::DIR_OUT,
            'phone'        => $phone,
            'type'         => $type,
            'category'     => $category,
            'body'         => isset($opts['preview']) ? mb_substr((string) $opts['preview'], 0, 500) : null,
            'payload'      => $payload,
            'status'       => WhatsAppMessage::STATUS_QUEUED,
            'context_type' => $ctxType,
            'context_id'   => $ctxId !== null ? (string) $ctxId : null,
            'dedup_key'    => $dedupKey,
            'scheduled_at' => $at instanceof CarbonInterface ? $at : null,
        ]);

        $job = SendWhatsAppMessage::dispatch($message->id)->onQueue('default');
        if ($at instanceof CarbonInterface && $at->isFuture()) {
            $job->delay($at);
        }

        return $message;
    }

    /**
     * Le contact accepte-t-il le marketing ? Placeholder Phase 0 : on refuse par
     * défaut (les envois marketing arriveront en Phase 4 avec un vrai registre
     * d'opt-in). La blacklist WHAPI reste gérée côté provider.
     */
    private function isMarketingAllowed(string $phone): bool
    {
        return false;
    }
}
