<?php

namespace App\Console\Commands;

use App\Models\MerchantCard;
use App\Services\WhatsAppNotifier;
use Illuminate\Console\Command;

/**
 * Diffuse les nouvelles cartes « Carte Gabon » publiées sur le CHANNEL WhatsApp
 * (newsletter). Une annonce par carte (dedup channel-card-{id}) → sûr à rejouer.
 *
 * Nécessite un channel configuré (services.whapi.channel_id). À schedule
 * régulièrement (routes/console.php).
 */
class AnnounceNewCards extends Command
{
    protected $signature = 'whatsapp:announce-new-cards {--days=7 : Fenêtre max depuis l\'activation}';
    protected $description = 'Annonce les nouvelles cartes Gabon publiées sur le channel WhatsApp.';

    public function handle(WhatsAppNotifier $notifier): int
    {
        if (empty(config('services.whapi.channel_id'))) {
            $this->info('Aucun channel WhatsApp configuré (WHAPI_CHANNEL_ID) — diffusion ignorée.');
            return self::SUCCESS;
        }

        $days = (int) $this->option('days');
        $cards = MerchantCard::where('is_active', true)
            ->whereNotNull('activated_at')
            ->where('activated_at', '>=', now()->subDays($days))
            ->latest('activated_at')
            ->limit(50)
            ->get();

        $sent = 0;
        foreach ($cards as $card) {
            $min = collect($card->denominations ?? [])->filter(fn ($d) => (float) $d > 0)->min();
            $price = $min ? number_format((float) $min, 0, ',', ' ') . ' FCFA' : null;

            $caption = "🆕 Nouvelle carte sur KardAfrica : *{$card->name}*"
                . ($price ? "\nÀ partir de {$price}" : '')
                . "\n👉 " . route('gabon.card', $card);

            $msg = $notifier->channelImage(route('og.gabon', $card), $caption, [
                'dedup_key' => "channel-card-{$card->id}",
                'context'   => ['channel_card', $card->id],
            ]);

            if ($msg) {
                $sent++;
            }
        }

        $this->info("Diffusion channel : {$sent} nouvelle(s) carte(s) annoncée(s).");
        return self::SUCCESS;
    }
}
