<?php

namespace App\Console\Commands;

use App\Models\MerchantCard;
use App\Models\MerchantCardPurchase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Détecte les cartes marchandes sans propriétaire.
 *
 * Une telle carte ne peut être débitée par personne : `Owner\ScanController`
 * exige que la carte appartienne au commerçant qui scanne. Le client paie,
 * reçoit son code, se présente au comptoir — et rien ne peut lui être servi.
 *
 * Le cas se produit sans qu'aucun humain ne fasse d'erreur : la contrainte est
 * `ON DELETE SET NULL`, donc supprimer un commerçant orpheline toutes ses cartes
 * d'un coup, silencieusement.
 */
class DetectOrphanMerchantCards extends Command
{
    protected $signature = 'cards:orphelines {--json : Sortie machine}';

    protected $description = 'Repère les Cartes Gabon sans propriétaire (invalidables au comptoir)';

    public function handle(): int
    {
        $orphelines = MerchantCard::orphan()->get(['id', 'name', 'is_active']);

        if ($orphelines->isEmpty()) {
            $this->info('Aucune carte orpheline.');

            return self::SUCCESS;
        }

        $encours = MerchantCardPurchase::whereIn('merchant_card_id', $orphelines->pluck('id'))
            ->where('payment_status', MerchantCardPurchase::PAYMENT_PAID)
            ->whereIn('status', [
                MerchantCardPurchase::STATUS_ACTIVE,
                MerchantCardPurchase::STATUS_PARTIALLY_USED,
            ]);

        $bloque = (float) $encours->sum('remaining_balance');
        $clients = $encours->count();

        if ($this->option('json')) {
            $this->line(json_encode([
                'cartes'            => $orphelines->count(),
                'clients_bloques'   => $clients,
                'montant_bloque'    => $bloque,
                'ids'               => $orphelines->pluck('id'),
            ], JSON_UNESCAPED_UNICODE));

            return self::FAILURE;
        }

        $this->error(sprintf(
            '%d carte(s) sans propriétaire — %d client(s) ne peuvent rien consommer, %s FCFA bloqués.',
            $orphelines->count(),
            $clients,
            number_format($bloque, 0, ',', ' '),
        ));

        $this->table(
            ['ID', 'Nom', 'Publiée'],
            $orphelines->map(fn ($c) => [$c->id, $c->name, $c->is_active ? 'oui' : 'non'])->all(),
        );

        $this->line('Réattribuer un propriétaire depuis l\'admin, ou désactiver la carte.');

        Log::critical('Cartes marchandes orphelines détectées', [
            'cartes'         => $orphelines->pluck('id'),
            'montant_bloque' => $bloque,
        ]);

        return self::FAILURE;
    }
}
