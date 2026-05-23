<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Card;
use App\Models\User;
use Carbon\Carbon;

class TestCardsSeeder extends Seeder
{
    public function run()
    {
        $user = User::first();

        if (!$user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@kardafrica.com',
                'password' => bcrypt('password'),
            ]);
        }

        $cards = [
            [
                'name' => 'Netflix Premium',
                'brand' => 'Netflix',
                'type' => 'streaming',
                'value' => 15000,
                'balance' => 15000,
                'currency' => 'XOF',
                'code' => 'NFLX-8923-4829-1039',
                'status' => 'active',
                'description' => 'Abonnement Netflix Premium 1 mois',
                'expiry_date' => Carbon::now()->addMonths(12),
            ],
            [
                'name' => 'Spotify Premium',
                'brand' => 'Spotify',
                'type' => 'music',
                'value' => 5000,
                'balance' => 5000,
                'currency' => 'XOF',
                'code' => 'SPOT-9922-1122-3344',
                'status' => 'active',
                'description' => 'Abonnement Spotify Premium Personnel',
                'expiry_date' => Carbon::now()->addMonths(6),
            ],
            [
                'name' => 'PlayStation Store',
                'brand' => 'PlayStation',
                'type' => 'gaming',
                'value' => 25000,
                'balance' => 25000,
                'currency' => 'XOF',
                'code' => 'PSN-7777-8888-9999',
                'status' => 'active',
                'description' => 'Carte cadeau PlayStation Store',
                'expiry_date' => Carbon::now()->addYears(1),
            ],
            [
                'name' => 'Apple Gift Card',
                'brand' => 'Apple',
                'type' => 'app_store',
                'value' => 50000,
                'balance' => 50000,
                'currency' => 'XOF',
                'code' => 'APPL-1234-5678-9012',
                'status' => 'active',
                'description' => 'Carte cadeau Apple Store',
                'expiry_date' => Carbon::now()->addYears(2),
            ]
        ];

        foreach ($cards as $cardData) {
            Card::create(array_merge($cardData, ['user_id' => $user->id]));
        }
    }
}
