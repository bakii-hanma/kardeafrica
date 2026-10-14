<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserCard;
use App\Services\CardImageService;
use App\Services\WahaService;
use Illuminate\Support\Facades\Log;

/**
 * Envoi d'une carte livrée au client sur WhatsApp (image custom KardAfrica +
 * message contenant le code/lien d'activation). Best-effort : n'échoue jamais
 * le flux de livraison si WhatsApp est indisponible ou le numéro absent.
 */
class WhatsAppCardNotifier
{
    /** Envoie une carte précise. Retourne true si envoyé. */
    public static function send(UserCard $card): bool
    {
        $cardId = $card->id ?? null;
        try {
            $user = $card->user ?: User::find($card->user_id);
            if (! $user || empty($user->phone)) {
                Log::info('WhatsAppCardNotifier: pas de numéro', ['card_id' => $cardId, 'user_id' => $card->user_id]);
                return false; // pas de numéro → rien à faire (bandeau « compléter » côté site)
            }
            $waha = app(WahaService::class);
            if (! $waha->isConfigured()) {
                Log::warning('WhatsAppCardNotifier: WAHA non configuré', ['card_id' => $cardId]);
                return false;
            }

            // Le numéro est-il un compte WhatsApp ? (évite une erreur « No LID » muette)
            if ($waha->numberExists($user->phone) === false) {
                Log::warning('WhatsAppCardNotifier: numéro PAS sur WhatsApp', [
                    'card_id' => $cardId, 'user_id' => $user->id, 'phone' => Phone::masked($user->phone),
                ]);
                return false;
            }

            $code  = (string) $card->card_code;
            $isUrl = str_starts_with($code, 'http');

            // Image custom (habillage = carte « Mes cartes » du site)
            $png = app(CardImageService::class)->render([
                'brand'     => $card->brand ?: $card->name,
                'name'      => $card->name,
                'face'      => $card->face_value ? rtrim(rtrim((string) $card->face_value, '0'), '.') : '',
                'currency'  => $card->currency ?: '',
                'code'      => $code,
                'pin'       => (string) $card->pin,
                'expires'   => $card->expiration_date ? $card->expiration_date->format('d/m/Y') : '',
                'logo_url'  => (string) ($card->image_url ?? ''),
                'price_xaf' => (float) ($card->price_paid_xaf ?? 0),
                'status'    => (string) $card->status,
            ]);

            // Message (le code/lien COMPLET est ici — jamais tronqué)
            $lines = ["🎁 *KardAfrica* — ta carte *{$card->name}* est prête !"];
            if ($isUrl) {
                $lines[] = "Active-la ici : {$code}";
            } else {
                $lines[] = "Code d'activation : *{$code}*";
                if (! empty($card->pin)) {
                    $lines[] = "PIN : *{$card->pin}*";
                }
            }
            $lines[] = "Retrouve-la aussi dans ton compte sur kardafrica.com.";
            $caption = implode("\n", $lines);

            $ok = $waha->sendImage($user->phone, $png, $caption, 'kardafrica-carte.png');
            if (! $ok) {
                $ok = $waha->sendText($user->phone, $caption);
            }
            if ($ok) {
                Log::info('WhatsAppCardNotifier: carte envoyée ✅', ['card_id' => $cardId, 'user_id' => $user->id]);
            } else {
                Log::warning('WhatsAppCardNotifier: échec envoi', [
                    'card_id' => $cardId, 'user_id' => $user->id, 'phone' => Phone::masked($user->phone),
                ]);
            }
            return $ok;
        } catch (\Throwable $e) {
            Log::warning('WhatsAppCardNotifier: exception', ['card_id' => $cardId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /** Message d'excuse + invitation à réessayer (commandes en attente). */
    public static function sendApology(User $user, ?string $retryUrl = null): bool
    {
        try {
            if (empty($user->phone)) {
                return false;
            }
            $waha = app(WahaService::class);
            if (! $waha->isConfigured()) {
                return false;
            }
            $msg  = "Bonjour 👋\n\n";
            $msg .= "Nous te présentons nos excuses : un incident technique a empêché la livraison de ta commande récente sur *KardAfrica*.\n\n";
            $msg .= "C'est désormais corrigé ✅. Tu peux relancer ton achat en quelques secondes";
            $msg .= $retryUrl ? " ici : {$retryUrl}" : " sur kardafrica.com.";
            $msg .= "\n\nMerci de ta confiance 🙏 — l'équipe KardAfrica.";
            return $waha->sendText($user->phone, $msg);
        } catch (\Throwable $e) {
            Log::warning('WhatsAppCardNotifier: apology échec', ['user_id' => $user->id ?? null, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
