<?php

namespace App\Console\Commands;

use App\Models\MerchantCard;
use App\Services\WhapiService;
use App\Support\PopularHighlights;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Pousse le catalogue KardAfrica vers WhatsApp Business (WHAPI /business/products) :
 *
 *  1. les CARTES POPULAIRES du catalogue afrikard (celles de la rotation
 *     d'accueil : ChatGPT, Netflix, Steam, PSN, Xbox, Deezer, Apple,
 *     Google Play, Roblox, Binance) — prix d'appel réel, visuel OG, lien fiche ;
 *  2. les cartes « Carte Gabon » actives (marchands locaux).
 *
 * `retailer_id` rend l'opération idempotente : relancer la commande met à jour
 * les produits existants au lieu d'en créer des doublons.
 *
 * ⚠️ DÉSACTIVÉ par défaut (services.whapi.catalog_sync) : nécessite un compte
 * WhatsApp Business lié au canal WHAPI. Tant qu'il n'existe pas, la commande
 * ne fait rien. La diffusion channel (whatsapp:announce-new-cards) et les fiches
 * à la demande (bot support) fonctionnent, elles, sans compte Business.
 */
class SyncWhatsAppCatalog extends Command
{
    protected $signature = 'whatsapp:catalog-sync
                            {--only= : popular|gabon — ne synchroniser qu\'une source}
                            {--dry-run : affiche ce qui serait poussé sans appeler WHAPI}';

    protected $description = 'Synchronise les cartes populaires + les cartes Gabon vers le catalogue WhatsApp Business.';

    public function handle(WhapiService $whapi): int
    {
        $dry  = (bool) $this->option('dry-run');
        $only = $this->option('only');

        if (!$dry && !config('services.whapi.catalog_sync')) {
            $this->warn('Sync catalogue désactivée (WHAPI_CATALOG_SYNC_ENABLED=false). '
                . 'Activez-la une fois un compte WhatsApp Business lié au canal WHAPI.');
            return self::SUCCESS;
        }

        if (!$dry && !$whapi->isConfigured()) {
            $this->error('WHAPI_TOKEN absent : rien à faire.');
            return self::FAILURE;
        }

        $products = [];
        if ($only !== 'gabon')   $products = array_merge($products, $this->popularProducts());
        if ($only !== 'popular') $products = array_merge($products, $this->gabonProducts());

        if (empty($products)) {
            $this->warn('Aucun produit à synchroniser (catalogue afrikard froid ?).');
            return self::SUCCESS;
        }

        if ($dry) {
            $this->table(
                ['retailer_id', 'nom', 'prix', 'lien'],
                array_map(fn ($p) => [
                    $p['retailer_id'],
                    $p['name'],
                    number_format($p['price'], 0, ',', ' ') . ' ' . $p['currency'],
                    $p['url'] ?? '—',
                ], $products)
            );
            $this->info(count($products) . ' produit(s) seraient poussés (aucun appel WHAPI).');
            return self::SUCCESS;
        }

        $ok = 0;
        foreach ($products as $product) {
            $res = $whapi->createProduct($product);
            if ($res['ok']) {
                $ok++;
            } else {
                Log::warning('catalog-sync: échec produit', [
                    'retailer_id' => $product['retailer_id'],
                    'error'       => $res['error'] ?? '?',
                ]);
                $this->line("  ✗ {$product['name']} : " . ($res['error'] ?? 'erreur'));
            }
        }

        $this->info("Sync catalogue : {$ok}/" . count($products) . ' produit(s) poussé(s).');
        return self::SUCCESS;
    }

    /**
     * Cartes populaires du catalogue afrikard — même source que le bandeau
     * d'accueil, donc mêmes ids et mêmes prix réels (jamais de prix écrit en dur).
     */
    private function popularProducts(): array
    {
        $out = [];
        foreach (PopularHighlights::resolved() as $card) {
            $out[] = [
                'name'        => $card['brand'],
                'description' => $card['tagline'],
                'price'       => (int) $card['price_fcfa'],
                'currency'    => 'XAF',
                'image_url'   => route('og.card', $card['card_type_id']),
                'url'         => route('card-type.show', $card['card_type_id']),
                'retailer_id' => 'kard-' . $card['key'],
            ];
        }
        return $out;
    }

    /** Cartes cadeaux locales actives (Carte Gabon). */
    private function gabonProducts(): array
    {
        return MerchantCard::where('is_active', true)->get()->map(function (MerchantCard $card) {
            $min = collect($card->denominations ?? [])->filter(fn ($d) => (float) $d > 0)->min();
            return [
                'name'        => $card->name,
                'description' => (string) $card->description,
                'price'       => (int) ($min ?? 0),
                'currency'    => 'XAF',
                'image_url'   => route('og.gabon', $card),
                'url'         => route('gabon.card', $card),
                'retailer_id' => 'gabon-' . $card->id,
            ];
        })->all();
    }
}
