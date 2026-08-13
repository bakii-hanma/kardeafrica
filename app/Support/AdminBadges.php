<?php

namespace App\Support;

use App\Models\CardOwner;
use App\Models\Order;

/**
 * Compteurs des badges de la navigation admin.
 *
 * L'arborescence est rendue DEUX fois par page — une fois pour le volet de
 * bureau, une fois pour le tiroir mobile. Chaque compteur y était donc calculé
 * en double, et `payoutRun()` (trois sous-requêtes jointes) trois fois avec le
 * widget du tableau de bord.
 *
 * Résolue en singleton de conteneur plutôt qu'en propriétés statiques : le
 * conteneur est reconstruit à chaque requête ET entre deux tests, là où un
 * static garderait les valeurs d'un test à l'autre.
 */
class AdminBadges
{
    /** @var array<string, mixed> */
    private array $memo = [];

    public static function make(): self
    {
        return app(self::class);
    }

    /** Commandes payées dont les cartes ne sont pas encore livrées. */
    public function pendingDeliveries(): int
    {
        return $this->memo['deliveries'] ??= Order::where('payment_status', Order::PAYMENT_STATUS_COMPLETED)
            ->where('status', '!=', Order::STATUS_COMPLETED)
            ->whereDoesntHave('userCards')
            ->count();
    }

    /** Dossiers professionnels en attente d'une décision humaine. */
    public function pendingProAccounts(): int
    {
        return $this->memo['pro'] ??= CardOwner::whereIn('status', ['provisional', 'docs_requested'])->count();
    }

    /** @return array{amount:float, count:int} */
    public function pendingSettlements(): array
    {
        if (isset($this->memo['settlements'])) {
            return $this->memo['settlements'];
        }

        $lignes = OwnerEarnings::payoutRun();

        return $this->memo['settlements'] = [
            'amount' => (float) $lignes->sum(fn ($l) => max(0, (float) $l->solde)),
            'count'  => $lignes->count(),
        ];
    }
}
