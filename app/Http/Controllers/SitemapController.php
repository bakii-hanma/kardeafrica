<?php

namespace App\Http\Controllers;

use App\Models\MerchantCard;
use App\Services\ProductApiService;
use Illuminate\Support\Facades\Cache;

/**
 * Sitemap XML dynamique (audit SEO 06/08) : pages statiques + fiches card-type
 * du catalogue afrikard + cartes Gabon actives. Mis en cache 12 h — le
 * catalogue bouge peu et Google ne crawle pas plus souvent.
 */
class SitemapController extends Controller
{
    public function index(ProductApiService $catalog)
    {
        $xml = Cache::remember('sitemap_xml_v1', 43200, function () use ($catalog) {
            $urls = [];

            // Pages statiques (priorité décroissante)
            $urls[] = ['loc' => route('home'),        'priority' => '1.0', 'changefreq' => 'daily'];
            $urls[] = ['loc' => route('boutique'),    'priority' => '0.9', 'changefreq' => 'daily'];
            $urls[] = ['loc' => route('gabon.index'), 'priority' => '0.9', 'changefreq' => 'daily'];
            $urls[] = ['loc' => url('/telecharger'),  'priority' => '0.6', 'changefreq' => 'monthly'];
            $urls[] = ['loc' => route('about'),       'priority' => '0.5', 'changefreq' => 'monthly'];
            $urls[] = ['loc' => route('contact'),     'priority' => '0.5', 'changefreq' => 'monthly'];
            $urls[] = ['loc' => route('support'),     'priority' => '0.5', 'changefreq' => 'monthly'];
            $urls[] = ['loc' => route('pro.landing'), 'priority' => '0.6', 'changefreq' => 'monthly'];
            $urls[] = ['loc' => route('how-it-works'), 'priority' => '0.7', 'changefreq' => 'monthly'];

            // Guides éditoriaux (PAA) — fort enjeu SEO hors marque
            $urls[] = ['loc' => route('guides.index'), 'priority' => '0.8', 'changefreq' => 'weekly'];
            foreach (array_keys(\App\Http\Controllers\GuideController::guides()) as $slug) {
                $urls[] = ['loc' => route('guides.show', $slug), 'priority' => '0.8', 'changefreq' => 'weekly'];
            }

            // Fiches card-type : un URL par MARQUE (cardType) unique du catalogue.
            try {
                $seen = [];
                foreach ($catalog->getAllProducts(0, 99999) as $p) {
                    $ctId = $p['cardType']['internalId'] ?? $p['cardType']['id'] ?? null;
                    if ($ctId && !isset($seen[$ctId])) {
                        $seen[$ctId] = true;
                        $urls[] = [
                            'loc'        => route('card-type.show', $ctId),
                            'priority'   => '0.7',
                            'changefreq' => 'weekly',
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // Catalogue indisponible : sitemap partiel plutôt que 500.
            }

            // Cartes Gabon publiées
            foreach (MerchantCard::active()->get(['id']) as $card) {
                $urls[] = [
                    'loc'        => route('gabon.card', $card->id),
                    'priority'   => '0.7',
                    'changefreq' => 'weekly',
                ];
            }

            $items = collect($urls)->map(fn ($u) =>
                "  <url>\n"
                . '    <loc>' . e($u['loc']) . "</loc>\n"
                . '    <changefreq>' . $u['changefreq'] . "</changefreq>\n"
                . '    <priority>' . $u['priority'] . "</priority>\n"
                . "  </url>"
            )->implode("\n");

            return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
                . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
                . $items . "\n</urlset>";
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
