<?php

namespace App\Services;

use App\Models\MerchantCard;
use App\Models\WhatsAppMessage;

/**
 * Pilotage du catalogue « Carte Gabon » depuis WhatsApp, réservé aux numéros
 * admin whitelistés (config services.whapi.admin_numbers). Permet de modérer les
 * cartes marchand sans ouvrir le back-office.
 *
 * Commandes (insensibles à la casse) :
 *   aide                      → liste des commandes
 *   attente                   → cartes en attente de validation
 *   publier <id>              → approuve + publie la carte
 *   refuser <id> <motif>      → refuse avec motif (≥ 5 caractères)
 *
 * Sécurité : le routage vers ce handler (vs le SupportBot) est décidé en amont
 * (ProcessInboundWhatsApp) sur la base de la whitelist — un non-admin n'atteint
 * jamais ces actions.
 */
class AdminCommandHandler
{
    public function __construct(private WhatsAppNotifier $notifier) {}

    /** Numéros admin autorisés (E.164 normalisés). */
    public static function adminNumbers(): array
    {
        $list = (string) config('services.whapi.admin_numbers', '');
        $numbers = array_filter(array_map('trim', explode(',', $list)));
        if (empty($numbers) && config('services.whapi.admin_number')) {
            $numbers = [(string) config('services.whapi.admin_number')];
        }
        return array_values(array_unique(array_map([WhapiService::class, 'normalize'], $numbers)));
    }

    public static function isAdmin(string $phone): bool
    {
        return in_array(WhapiService::normalize($phone), self::adminNumbers(), true);
    }

    public function handle(WhatsAppMessage $inbound): void
    {
        $phone = $inbound->phone;
        $text  = trim((string) $inbound->body);
        $parts = preg_split('/\s+/', $text, 3) ?: [];
        $cmd   = strtolower($parts[0] ?? '');

        match ($cmd) {
            'aide', 'help', 'menu' => $this->reply($phone, $this->helpText()),
            'attente', 'pending'   => $this->listPending($phone),
            'publier', 'approuver' => $this->approve($phone, $parts[1] ?? null),
            'refuser', 'rejeter'   => $this->reject($phone, $parts[1] ?? null, $parts[2] ?? null),
            default                => $this->reply($phone, "Commande inconnue. Envoyez *aide* pour la liste."),
        };
    }

    private function helpText(): string
    {
        return "🛠️ *Admin KardAfrica — Carte Gabon*\n\n"
            . "• *attente* — cartes à valider\n"
            . "• *publier <id>* — approuver & publier\n"
            . "• *refuser <id> <motif>* — refuser avec motif\n"
            . "• *aide* — ce menu";
    }

    private function listPending(string $phone): void
    {
        $pending = MerchantCard::where('is_active', false)
            ->whereNull('rejection_reason')
            ->with('owner')
            ->latest()
            ->limit(15)
            ->get();

        if ($pending->isEmpty()) {
            $this->reply($phone, "✅ Aucune carte en attente de validation.");
            return;
        }

        $lines = $pending->map(function (MerchantCard $c) {
            $owner = $c->owner?->business_name ?? '—';
            return "#{$c->id} — {$c->name} ({$owner})";
        })->implode("\n");

        $this->reply($phone, "🕓 *Cartes en attente* :\n{$lines}\n\nPour publier : *publier <id>*");
    }

    private function approve(string $phone, ?string $id): void
    {
        $card = $this->findCard($phone, $id);
        if (!$card) return;

        $card->update(['is_active' => true, 'activated_at' => now(), 'rejection_reason' => null]);
        $this->notifyOwner($card,
            "🎉 KardAfrica : votre carte « {$card->name} » est approuvée et publiée ! "
            . "Elle est en vente sur " . route('gabon.card', $card));

        $this->reply($phone, "✅ Carte #{$card->id} « {$card->name} » publiée.");
    }

    private function reject(string $phone, ?string $id, ?string $motif): void
    {
        $card = $this->findCard($phone, $id);
        if (!$card) return;

        $motif = trim((string) $motif);
        if (mb_strlen($motif) < 5) {
            $this->reply($phone, "❌ Motif requis (≥ 5 caractères). Ex : *refuser {$card->id} visuel illisible*");
            return;
        }

        $card->update(['is_active' => false, 'activated_at' => null, 'rejection_reason' => $motif]);
        $this->notifyOwner($card,
            "KardAfrica : votre carte « {$card->name} » n'a pas été publiée.\nMotif : {$motif}\n"
            . "Vous pouvez la modifier et la re-soumettre depuis votre espace propriétaire.");

        $this->reply($phone, "🚫 Carte #{$card->id} refusée. Le marchand a été notifié.");
    }

    private function findCard(string $phone, ?string $id): ?MerchantCard
    {
        $id = (int) $id;
        if ($id <= 0) {
            $this->reply($phone, "Précisez l'ID de la carte. Ex : *publier 12*");
            return null;
        }
        $card = MerchantCard::find($id);
        if (!$card) {
            $this->reply($phone, "Carte #{$id} introuvable.");
            return null;
        }
        return $card;
    }

    private function notifyOwner(MerchantCard $card, string $message): void
    {
        $number = $card->owner?->whatsapp_number ?: $card->owner?->phone;
        if ($number) {
            $this->notifier->text($number, $message, [
                'category' => WhatsAppMessage::CAT_TRANSACTIONAL,
                'context'  => ['merchant_card', $card->id],
            ]);
        }
    }

    private function reply(string $phone, string $text): void
    {
        $this->notifier->text($phone, $text, ['category' => WhatsAppMessage::CAT_OPS]);
    }
}
