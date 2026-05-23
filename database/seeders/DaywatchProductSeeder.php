<?php

namespace Database\Seeders;

use App\Models\DaywatchProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DaywatchProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name'          => 'Daywatch Découverte',
                'subtitle'      => 'L\'essentiel pour démarrer',
                'description'   => 'Accès illimité au catalogue Daywatch (films, séries locales, info) en qualité standard pendant 1 jour.',
                'duration_days' => 1,
                'price_xaf'     => 500,
                'features'      => ['Catalogue complet', 'Qualité SD', '1 écran simultané', 'Sans publicité'],
                'color'         => '#0EA5E9',
                'sort_order'    => 1,
            ],
            [
                'name'          => 'Daywatch Hebdo',
                'subtitle'      => 'Une semaine de divertissement',
                'description'   => 'Accès complet à la plateforme pendant 7 jours. Idéal pour le week-end ou un voyage.',
                'duration_days' => 7,
                'price_xaf'     => 2500,
                'features'      => ['Catalogue complet', 'Qualité HD', '2 écrans simultanés', 'Téléchargement hors-ligne'],
                'color'         => '#3B82F6',
                'sort_order'    => 2,
                'is_featured'   => true,
            ],
            [
                'name'          => 'Daywatch Mensuel',
                'subtitle'      => 'L\'abonnement le plus populaire',
                'description'   => '30 jours d\'accès illimité, qualité Full HD, jusqu\'à 4 écrans. Parfait pour la famille.',
                'duration_days' => 30,
                'price_xaf'     => 7500,
                'features'      => ['Catalogue complet', 'Qualité Full HD', '4 écrans simultanés', 'Téléchargement hors-ligne', 'Profils enfants'],
                'color'         => '#44A08D',
                'sort_order'    => 3,
                'is_featured'   => true,
            ],
            [
                'name'          => 'Daywatch Trimestriel',
                'subtitle'      => '3 mois — économisez 15%',
                'description'   => 'Engagement de 3 mois avec une réduction de 15% sur le tarif mensuel.',
                'duration_days' => 90,
                'price_xaf'     => 19500,
                'features'      => ['Catalogue complet', 'Qualité Full HD', '4 écrans simultanés', 'Téléchargement hors-ligne', 'Profils enfants', 'Accès anticipé aux nouveautés'],
                'color'         => '#7C3AED',
                'sort_order'    => 4,
            ],
            [
                'name'          => 'Daywatch Annuel',
                'subtitle'      => 'L\'année complète, économies maximales',
                'description'   => '12 mois d\'accès Premium avec 25% d\'économie. Inclut le pack Premium 4K et 6 écrans.',
                'duration_days' => 365,
                'price_xaf'     => 67500,
                'features'      => ['Catalogue complet', 'Qualité 4K Ultra HD', '6 écrans simultanés', 'Téléchargement hors-ligne', 'Profils enfants', 'Accès anticipé', 'Audio Dolby Atmos'],
                'color'         => '#EA580C',
                'sort_order'    => 5,
                'is_featured'   => true,
            ],
            [
                'name'          => 'Daywatch Sport',
                'subtitle'      => 'Football, basket, tennis — 1 mois',
                'description'   => 'Accès à toutes les compétitions sportives africaines et internationales pendant 30 jours.',
                'duration_days' => 30,
                'price_xaf'     => 9000,
                'features'      => ['Toutes les compétitions sport', 'Replays', 'Multi-cam', 'Statistiques live', '2 écrans simultanés'],
                'color'         => '#DC2626',
                'sort_order'    => 6,
            ],
            [
                'name'          => 'Daywatch Kids',
                'subtitle'      => 'Pour les enfants — 1 mois',
                'description'   => 'Catalogue dédié aux 3-12 ans : dessins animés, séries éducatives, contes africains.',
                'duration_days' => 30,
                'price_xaf'     => 4500,
                'features'      => ['Catalogue 3-12 ans', 'Contrôle parental', 'Sans publicité', 'Téléchargement hors-ligne', '2 écrans'],
                'color'         => '#F59E0B',
                'sort_order'    => 7,
            ],
            [
                'name'          => 'Daywatch Cinéma',
                'subtitle'      => 'Films premium — 1 mois',
                'description'   => 'Les derniers blockbusters et le catalogue cinéma africain en exclusivité.',
                'duration_days' => 30,
                'price_xaf'     => 11000,
                'features'      => ['Sorties cinéma récentes', 'Cinéma africain exclusif', 'Qualité 4K', 'Audio Dolby Atmos', '4 écrans'],
                'color'         => '#1E293B',
                'sort_order'    => 8,
            ],
            [
                'name'          => 'Daywatch Musique',
                'subtitle'      => 'Streaming audio — 1 mois',
                'description'   => 'Découvrez les meilleurs artistes africains et internationaux en haute qualité.',
                'duration_days' => 30,
                'price_xaf'     => 3500,
                'features'      => ['Bibliothèque illimitée', 'Qualité Hi-Fi', 'Mode hors-ligne', 'Playlists personnalisées', 'Sans publicité'],
                'color'         => '#10B981',
                'sort_order'    => 9,
            ],
            [
                'name'          => 'Daywatch Famille',
                'subtitle'      => 'Tout-en-un — 6 mois',
                'description'   => 'Le pack complet : films, séries, sport, kids et musique pour 6 mois. Jusqu\'à 8 écrans.',
                'duration_days' => 180,
                'price_xaf'     => 49000,
                'features'      => ['Tous les contenus', 'Sport inclus', 'Kids inclus', 'Musique incluse', 'Qualité 4K', '8 écrans simultanés', 'Profils illimités'],
                'color'         => '#A855F7',
                'sort_order'    => 10,
                'is_featured'   => true,
            ],
        ];

        foreach ($products as $data) {
            $slug = Str::slug($data['name']);
            DaywatchProduct::updateOrCreate(
                ['slug' => $slug],
                array_merge($data, ['slug' => $slug, 'is_active' => true])
            );
        }
    }
}
