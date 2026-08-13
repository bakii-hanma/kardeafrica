<?php

namespace App\Console\Commands;

use App\Models\DaywatchProduct;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Synchronise les formules Daywatch depuis api.daywatch.online.
 *
 * Daywatch est le seul produit LOCAL du catalogue : il ne vient pas d'afrikard
 * et n'a donc aucune raison de suivre son cycle de cache. Cette commande le
 * rapproche par `plan_id`, la seule clé stable — se fier au nom créerait un
 * doublon au premier renommage de formule.
 *
 * Une formule retirée de l'API est DÉSACTIVÉE, jamais supprimée : des cartes
 * vendues y sont rattachées.
 */
class SyncDaywatchCatalog extends Command
{
    protected $signature = 'daywatch:sync {--dry-run : Affiche les changements sans rien écrire}';

    protected $description = 'Importe les formules Daywatch depuis leur API';

    public function handle(): int
    {
        $url = config('services.daywatch.catalog_url');
        $sec = $this->option('dry-run');

        $this->info(($sec ? '[simulation] ' : '') . 'Lecture de ' . $url);

        try {
            $reponse = Http::timeout(20)->acceptJson()->get($url);
        } catch (\Throwable $e) {
            $this->error('API injoignable : ' . $e->getMessage());

            return self::FAILURE;
        }

        if (! $reponse->successful() || ! ($reponse->json('success') ?? false)) {
            $this->error('Réponse invalide (HTTP ' . $reponse->status() . ')');

            return self::FAILURE;
        }

        $formules = $reponse->json('data') ?? [];

        if ($formules === []) {
            // Un catalogue vide désactiverait TOUT : on refuse plutôt que de
            // vider la boutique sur un incident côté Daywatch.
            $this->error('Catalogue vide — aucune modification appliquée.');

            return self::FAILURE;
        }

        $lignes = [];
        $vus    = [];

        foreach ($formules as $i => $f) {
            $planId = (int) ($f['planId'] ?? 0);

            if ($planId === 0) {
                $this->warn('Formule sans planId ignorée : ' . ($f['planName'] ?? '?'));
                continue;
            }

            $vus[] = $planId;
            $donnees = $this->mapper($f, $i);
            $existant = DaywatchProduct::where('plan_id', $planId)->first();

            $lignes[] = [
                $donnees['name'],
                $donnees['duration_days'] . ' j',
                number_format($donnees['price_xaf'], 0, ',', ' '),
                $donnees['original_price_xaf'] > $donnees['price_xaf']
                    ? '−' . round((1 - $donnees['price_xaf'] / $donnees['original_price_xaf']) * 100) . ' %'
                    : '—',
                $existant ? 'mise à jour' : 'CRÉATION',
            ];

            if (! $sec) {
                DaywatchProduct::updateOrCreate(['plan_id' => $planId], $donnees);
            }
        }

        // Retrait de l'API = désactivation, pas suppression.
        $obsoletes = DaywatchProduct::whereNotNull('plan_id')
            ->whereNotIn('plan_id', $vus)
            ->where('is_active', true);

        $nbObsoletes = $obsoletes->count();

        if ($nbObsoletes > 0 && ! $sec) {
            $obsoletes->update(['is_active' => false]);
            Log::info('daywatch:sync — formules désactivées', ['n' => $nbObsoletes]);
        }

        $this->table(['Formule', 'Durée', 'Prix FCFA', 'Remise', 'Action'], $lignes);

        if ($nbObsoletes > 0) {
            $this->warn($nbObsoletes . ' formule(s) absente(s) de l\'API ' . ($sec ? 'seraient désactivées' : 'désactivées'));
        }

        $this->info($sec
            ? 'Simulation terminée — rien n\'a été écrit.'
            : count($lignes) . ' formule(s) synchronisée(s).');

        return self::SUCCESS;
    }

    /** @return array<string, mixed> */
    private function mapper(array $f, int $rang): array
    {
        $prix     = (int) ($f['priceXaf'] ?? 0);
        $original = (int) ($f['originalPriceXaf'] ?? 0) ?: $prix;

        return [
            'name'               => $f['planName'] ?? 'Daywatch',
            'slug'               => Str::slug($f['kind'] ?? ($f['planName'] ?? 'daywatch')),
            'subtitle'           => $f['durationLabel'] ?? null,
            'description'        => $f['description'] ?? null,
            'duration_days'      => (int) ($f['duration'] ?? 0),
            'price_xaf'          => $prix,
            'original_price_xaf' => $original,
            'currency'           => 'XAF',
            'max_profiles'       => $f['maxProfiles'] ?? null,
            'max_devices'        => $f['maxDevices'] ?? null,
            'features'           => array_values(array_filter([
                ($f['maxProfiles'] ?? null) ? $f['maxProfiles'] . ' profil' . ($f['maxProfiles'] > 1 ? 's' : '') : null,
                ($f['maxDevices'] ?? null) ? $f['maxDevices'] . ' appareil' . ($f['maxDevices'] > 1 ? 's' : '') : null,
                ($f['discountPct'] ?? 0) > 0 ? 'Économise ' . ($f['savingsLabel'] ?? '') : null,
            ])),
            // L'API sert ses visuels en http:// alors qu'elle répond en https.
            // Laissés tels quels, les navigateurs les bloqueraient en contenu
            // mixte sur kardafrica.com.
            'image_url'      => $this->https($f['designFrontUrl'] ?? null),
            'image_back_url' => $this->https($f['designBackUrl'] ?? null),
            'is_active'      => true,
            'sort_order'     => $rang,
            'synced_at'      => now(),
        ];
    }

    private function https(?string $url): ?string
    {
        return $url ? preg_replace('#^http://#i', 'https://', $url) : null;
    }
}
