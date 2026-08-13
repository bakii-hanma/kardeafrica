<?php

namespace App\Console\Commands;

use App\Models\CardOwner;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppNotifier;
use Illuminate\Console\Command;

/**
 * Relance WhatsApp des pros bloqués dans l'onboarding :
 *  - `otp_verified`   : numéro vérifié mais dossier KYC non soumis ;
 *  - `docs_requested` : l'admin a réclamé des pièces complémentaires.
 *
 * Fenêtre [min_age_hours, max_age_days] (config/whatsapp.php), une relance par
 * (pro, statut) via dedup_key kyc-reminder-{id}-{status}. Message de service
 * (catégorie transactional) — il concerne le compte du pro.
 *
 * À schedule une fois par jour (routes/console.php).
 */
class SendKycReminders extends Command
{
    protected $signature = 'whatsapp:kyc-reminders';
    protected $description = 'Relance WhatsApp les pros dont le dossier KYC est incomplet ou en attente de pièces.';

    public function handle(WhatsAppNotifier $notifier): int
    {
        if (!config('whatsapp.reminders.kyc.enabled', true)) {
            $this->info('Relances KYC désactivées.');
            return self::SUCCESS;
        }

        $minAgeH = (int) config('whatsapp.reminders.kyc.min_age_hours', 24);
        $maxAgeD = (int) config('whatsapp.reminders.kyc.max_age_days', 14);

        $notBefore = now()->subDays($maxAgeD);
        $notAfter  = now()->subHours($minAgeH);

        $owners = CardOwner::whereIn('status', [
                CardOwner::STATUS_OTP_VERIFIED,
                CardOwner::STATUS_DOCS_REQUESTED,
            ])
            ->whereBetween('updated_at', [$notBefore, $notAfter])
            ->limit(500)
            ->get();

        $link = route('pro.kyc.show');
        $sent = 0;

        foreach ($owners as $owner) {
            $phone = $owner->whatsapp_number ?: $owner->phone;
            if (empty($phone)) {
                continue;
            }

            $body = $owner->status === CardOwner::STATUS_DOCS_REQUESTED
                ? "Bonjour {$owner->contact_name} 👋\n\nIl nous manque des pièces pour valider votre compte pro KardAfrica. Ajoutez-les ici :\n{$link}"
                : "Bonjour {$owner->contact_name} 👋\n\nVotre inscription pro KardAfrica est presque terminée ! Complétez votre dossier pour créer vos cartes :\n{$link}";

            $msg = $notifier->text($phone, $body, [
                'category'  => WhatsAppMessage::CAT_TRANSACTIONAL,
                'dedup_key' => "kyc-reminder-{$owner->id}-{$owner->status}",
                'context'   => ['kyc', $owner->id],
            ]);

            if ($msg) {
                $sent++;
            }
        }

        $this->info("Relances KYC : {$sent} envoi(s) enfilé(s) sur {$owners->count()} candidat(s).");
        return self::SUCCESS;
    }
}
