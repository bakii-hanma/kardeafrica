<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Card;

class CardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer les catégories
        $streamingCategory = \App\Models\Category::where('slug', 'streaming')->first();
        $gamingCategory = \App\Models\Category::where('slug', 'gaming')->first();
        $shoppingCategory = \App\Models\Category::where('slug', 'shopping')->first();
        $mobileCategory = \App\Models\Category::where('slug', 'mobile-recharge')->first();
        $digitalCategory = \App\Models\Category::where('slug', 'digital-services')->first();
        $voyageCategory = \App\Models\Category::where('slug', 'voyage-transport')->first();

        $cards = [
            [
                'name' => 'Carte Cadeau Netflix',
                'category_id' => $streamingCategory ? $streamingCategory->id : 1,
                'price' => 15000, // 15 EUR en centimes
                'stock' => 100,
                'currency' => 'FCFA',
                'description' => 'Profitez de millions de films et séries TV en streaming avec cette carte cadeau Netflix',
                'image' => 'assets/banner/Banner-Netflix---Kardafrica.jpg',
                'brand' => 'Netflix',
                'expires_at' => now()->addYear(),
                'is_active' => true,
                'sort_order' => 1,
                'usage_instructions' => json_encode([
                    '1. Connectez-vous à votre compte Netflix',
                    '2. Allez dans "Compte" > "Recharger le compte"',
                    '3. Saisissez le code de la carte',
                    '4. Confirmez la transaction'
                ])
            ],
            [
                'name' => 'Carte Cadeau Spotify Premium',
                'category_id' => $streamingCategory ? $streamingCategory->id : 1,
                'price' => 9990,
                'stock' => 150,
                'currency' => 'FCFA',
                'description' => 'Écoutez vos musiques préférées sans publicité avec Spotify Premium',
                'image' => 'assets/banner/Banner-Spotify---Kardafrica.jpg',
                'brand' => 'Spotify',
                'expires_at' => now()->addYear(),
                'is_active' => true,
                'sort_order' => 2,
                'usage_instructions' => json_encode([
                    '1. Connectez-vous à votre compte Spotify',
                    '2. Allez dans "Paramètres" > "Abonnement"',
                    '3. Sélectionnez "Carte cadeau"',
                    '4. Entrez le code et validez'
                ])
            ],
            [
                'name' => 'Carte Cadeau Apple Store',
                'category_id' => $digitalCategory ? $digitalCategory->id : 1,
                'price' => 25000,
                'stock' => 80,
                'currency' => 'FCFA',
                'description' => 'Utilisez cette carte pour acheter des apps, des jeux, de la musique et plus encore',
                'image' => 'assets/banner/Banner-Apple---Kardafrica.jpg',
                'brand' => 'Apple',
                'expires_at' => now()->addYear(),
                'is_active' => true,
                'sort_order' => 3,
                'usage_instructions' => json_encode([
                    '1. Ouvrez l\'App Store sur votre appareil Apple',
                    '2. Faites défiler vers le bas et appuyez sur votre Apple ID',
                    '3. Appuyez sur "Redeem Gift Card or Code"',
                    '4. Saisissez le code de la carte'
                ])
            ],
            [
                'name' => 'Crédit Uber',
                'category_id' => $voyageCategory ? $voyageCategory->id : 1,
                'price' => 20000,
                'stock' => 200,
                'currency' => 'FCFA',
                'description' => 'Voyagez en toute simplicité avec ce crédit Uber pour vos trajets et livraisons',
                'image' => 'assets/banner/Banner-Uber--Kardafrica.jpg',
                'brand' => 'Uber',
                'expires_at' => now()->addYear(),
                'is_active' => true,
                'sort_order' => 4,
                'usage_instructions' => json_encode([
                    '1. Ouvrez l\'application Uber',
                    '2. Allez dans "Paramètres" > "Paiements"',
                    '3. Sélectionnez "Ajouter un mode de paiement"',
                    '4. Choisissez "Carte cadeau" et saisissez le code'
                ])
            ],
            [
                'name' => 'Carte Cadeau Google Play',
                'category_id' => $digitalCategory ? $digitalCategory->id : 1,
                'price' => 10000,
                'stock' => 120,
                'currency' => 'FCFA',
                'description' => 'Achetez des apps, des jeux, des films et plus encore sur Google Play',
                'image' => 'assets/banner/COVER-KARDAFRICA-2.jpg',
                'brand' => 'Google',
                'expires_at' => now()->addYear(),
                'is_active' => true,
                'sort_order' => 5,
                'usage_instructions' => json_encode([
                    '1. Ouvrez Google Play Store',
                    '2. Appuyez sur le menu > "Compte"',
                    '3. Sélectionnez "Redeem"',
                    '4. Saisissez le code de la carte'
                ])
            ],
            [
                'name' => 'Carte Cadeau Amazon',
                'category_id' => $shoppingCategory ? $shoppingCategory->id : 1,
                'price' => 50000,
                'stock' => 60,
                'currency' => 'FCFA',
                'description' => 'Achetez tout ce que vous voulez sur Amazon avec cette carte cadeau',
                'image' => 'assets/banner/COVER-KARDAFRICA-2.jpg',
                'brand' => 'Amazon',
                'expires_at' => now()->addYear(),
                'is_active' => true,
                'sort_order' => 6,
                'usage_instructions' => json_encode([
                    '1. Connectez-vous à votre compte Amazon',
                    '2. Allez dans "Compte et listes" > "Carte cadeau"',
                    '3. Cliquez sur "Redeem a Gift Card"',
                    '4. Entrez le code et cliquez sur "Apply to Your Balance"'
                ])
            ],
            [
                'name' => 'Carte Steam Wallet',
                'category_id' => $gamingCategory ? $gamingCategory->id : 1,
                'price' => 30000,
                'stock' => 90,
                'currency' => 'FCFA',
                'description' => 'Achetez des jeux et du contenu sur Steam avec cette carte',
                'image' => 'assets/banner/COVER-KARDAFRICA-2.jpg',
                'brand' => 'Steam',
                'expires_at' => now()->addYear(),
                'is_active' => true,
                'sort_order' => 7,
                'usage_instructions' => json_encode([
                    '1. Ouvrez Steam et connectez-vous',
                    '2. Allez dans "Steam" > "Activer un produit Steam"',
                    '3. Sélectionnez "Je suis d\'accord"',
                    '4. Saisissez le code de la carte Steam'
                ])
            ],
            [
                'name' => 'Carte PlayStation Store',
                'category_id' => $gamingCategory ? $gamingCategory->id : 1,
                'price' => 40000,
                'stock' => 70,
                'currency' => 'FCFA',
                'description' => 'Achetez des jeux et du contenu sur PlayStation Store',
                'image' => 'assets/banner/COVER-KARDAFRICA-2.jpg',
                'brand' => 'PlayStation',
                'expires_at' => now()->addYear(),
                'is_active' => true,
                'sort_order' => 8,
                'usage_instructions' => json_encode([
                    '1. Connectez-vous à votre compte PlayStation',
                    '2. Allez dans PlayStation Store',
                    '3. Sélectionnez "Redeem Codes"',
                    '4. Saisissez le code de la carte'
                ])
            ],
            [
                'name' => 'Carte Cadeau Microsoft',
                'category_id' => $digitalCategory ? $digitalCategory->id : 1,
                'price' => 35000,
                'stock' => 85,
                'currency' => 'FCFA',
                'description' => 'Utilisez sur Microsoft Store, Xbox, Office et plus encore',
                'image' => 'assets/banner/COVER-KARDAFRICA-2.jpg',
                'brand' => 'Microsoft',
                'expires_at' => now()->addYear(),
                'is_active' => true,
                'sort_order' => 9,
                'usage_instructions' => json_encode([
                    '1. Connectez-vous à votre compte Microsoft',
                    '2. Allez dans "Microsoft Store"',
                    '3. Sélectionnez "Redeem a code"',
                    '4. Saisissez le code de la carte'
                ])
            ],
            [
                'name' => 'Recharge Mobile Orange',
                'category_id' => $mobileCategory ? $mobileCategory->id : 1,
                'price' => 15000,
                'stock' => 300,
                'currency' => 'FCFA',
                'description' => 'Rechargez votre crédit Orange instantanément',
                'image' => 'assets/banner/COVER-KARDAFRICA-2.jpg',
                'brand' => 'Orange',
                'expires_at' => now()->addYear(),
                'is_active' => true,
                'sort_order' => 10,
                'usage_instructions' => json_encode([
                    '1. Composez *144# sur votre téléphone Orange',
                    '2. Sélectionnez "Recharger"',
                    '3. Entrez le code de recharge',
                    '4. Confirmez la transaction'
                ])
            ]
        ];

        foreach ($cards as $cardData) {
            Card::firstOrCreate(
                ['name' => $cardData['name']],
                $cardData
            );
        }
    }
}
