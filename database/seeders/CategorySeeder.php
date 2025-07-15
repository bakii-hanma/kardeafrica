<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Gaming',
                'slug' => 'gaming',
                'description' => 'Cartes pour les jeux vidéo et plateformes gaming',
                'icon' => '🎮',
                'color' => '#8B5CF6',
                'sort_order' => 1,
            ],
            [
                'name' => 'Streaming',
                'slug' => 'streaming',
                'description' => 'Cartes pour les services de streaming vidéo et audio',
                'icon' => '🎬',
                'color' => '#EF4444',
                'sort_order' => 2,
            ],
            [
                'name' => 'Shopping',
                'slug' => 'shopping',
                'description' => 'Cartes cadeaux pour les achats en ligne',
                'icon' => '🛍️',
                'color' => '#F59E0B',
                'sort_order' => 3,
            ],
            [
                'name' => 'Mobile & Recharge',
                'slug' => 'mobile-recharge',
                'description' => 'Recharges téléphoniques et cartes mobiles',
                'icon' => '📱',
                'color' => '#10B981',
                'sort_order' => 4,
            ],
            [
                'name' => 'Voyage & Transport',
                'slug' => 'voyage-transport',
                'description' => 'Cartes pour les services de voyage et transport',
                'icon' => '✈️',
                'color' => '#3B82F6',
                'sort_order' => 5,
            ],
            [
                'name' => 'Digital Services',
                'slug' => 'digital-services',
                'description' => 'Services numériques et logiciels',
                'icon' => '💻',
                'color' => '#6366F1',
                'sort_order' => 6,
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
