<?php

namespace App\Jobs;

use App\Models\WhatsAppMessage;
use App\Services\AdminCommandHandler;
use App\Services\SupportBot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Traite un message WhatsApp ENTRANT (table whatsapp_messages, direction=in).
 *
 * Routage :
 *  - numéro admin whitelisté → pilotage du catalogue (AdminCommandHandler) ;
 *  - sinon → bot de support IA (SupportBot : rapprochement compte, statut
 *    commande, recherche catalogue, escalade humaine).
 */
class ProcessInboundWhatsApp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 60;

    public function __construct(public int $messageId) {}

    public function handle(SupportBot $bot, AdminCommandHandler $admin): void
    {
        $m = WhatsAppMessage::find($this->messageId);
        if (!$m || $m->direction !== WhatsAppMessage::DIR_IN) {
            return;
        }

        if (AdminCommandHandler::isAdmin($m->phone)) {
            $admin->handle($m);
            return;
        }

        $bot->handle($m);
    }
}
