<?php

namespace Database\Seeders;

use App\Models\MerchantCard;
use App\Models\Reseller;
use App\Support\MerchantSlug;
use Illuminate\Database\Seeder;

/**
 * MerchantCardSeeder
 * ===
 * Données de démo pour le vendor KA-V-G8H6 (ESSONO Florent) — Phase 2.
 *
 * 1. Complète l'identité marchand (business_name, business_type, KYC approuvé)
 *    pour que les cartes apparaissent comme un VRAI marchand sur Kardafrica.
 * 2. Crée 3 cartes-cadeau d'exemple (1 active, 1 en attente, 1 brouillon sans
 *    visuel) — couvre les trois états du badge dans la liste vendor.
 *
 * Idempotent : updateOrCreate sur (reseller_id, name).
 */
class MerchantCardSeeder extends Seeder
{
    public function run(): void
    {
        $reseller = Reseller::where('vendor_code', 'KA-V-G8H6')->first();

        if (!$reseller) {
            $this->command->warn('Reseller KA-V-G8H6 introuvable — seeder ignoré.');
            return;
        }

        // 1) Identité marchand : on ne touche pas aux champs déjà saisis
        $reseller->fill([
            'business_name'   => $reseller->business_name   ?? 'ESSONO Boutique & Services',
            'business_type'   => $reseller->business_type   ?? 'mode',
            'description'     => $reseller->description     ?? 'Mode, beauté et accessoires haut de gamme à Libreville. Carte-cadeau valable en boutique pour toute la gamme.',
            'address'         => $reseller->address         ?? 'Boulevard du Bord de Mer',
            'city'            => $reseller->city            ?? 'Libreville',
            'province'        => $reseller->province        ?? 'Estuaire',
            'whatsapp_number' => $reseller->whatsapp_number ?? '+24100000000',
            'kyc_status'      => 'approved',
            'kyc_approved_at' => $reseller->kyc_approved_at ?? now(),
        ]);

        if (empty($reseller->slug)) {
            $reseller->slug = MerchantSlug::generate(
                $reseller->business_name ?? $reseller->name,
                $reseller->id
            );
        }

        $reseller->save();

        $this->command->info("→ Reseller {$reseller->vendor_code} configuré comme marchand approuvé (slug: {$reseller->slug}).");

        // 2) Cartes-cadeau d'exemple
        $cards = [
            [
                'name'                => 'Carte Mode & Élégance',
                'category'            => 'mode',
                'description'         => 'Carte-cadeau utilisable sur toute la collection mode (vêtements, chaussures, accessoires).',
                'denominations'       => [10000, 25000, 50000, 100000],
                'allow_custom_amount' => true,
                'min_amount'          => 2000,
                'max_amount'          => 500000,
                'validity_months'     => 12,
                'terms_conditions'    => "• Utilisable uniquement en boutique\n• Non remboursable, non échangeable contre de l'espèce\n• Valable 12 mois à compter de la date d'achat\n• Plusieurs utilisations possibles jusqu'à épuisement du solde",
                'is_active'           => true,
                'activated_at'        => now()->subDays(7),
                'total_sold'          => 0,
                'total_revenue'       => 0,
            ],
            [
                'name'                => 'Carte Beauté & Bien-être',
                'category'            => 'beaute',
                'description'         => 'Offre une expérience beauté complète : soins du visage, manucure, coiffure.',
                'denominations'       => [15000, 25000, 50000],
                'allow_custom_amount' => false,
                'min_amount'          => null,
                'max_amount'          => null,
                'validity_months'     => 6,
                'terms_conditions'    => 'Sur rendez-vous uniquement. Valable 6 mois.',
                'is_active'           => false,
                'activated_at'        => null,
                'total_sold'          => 0,
                'total_revenue'       => 0,
            ],
            [
                'name'                => 'Bon-cadeau VIP',
                'category'            => 'autre',
                'description'         => 'Bon-cadeau personnalisé pour les grandes occasions.',
                'denominations'       => [50000, 100000, 250000],
                'allow_custom_amount' => true,
                'min_amount'          => 50000,
                'max_amount'          => 1000000,
                'validity_months'     => 24,
                'terms_conditions'    => null,
                'is_active'           => false,
                'activated_at'        => null,
                'total_sold'          => 0,
                'total_revenue'       => 0,
            ],
        ];

        foreach ($cards as $data) {
            MerchantCard::updateOrCreate(
                ['reseller_id' => $reseller->id, 'name' => $data['name']],
                $data + ['reseller_id' => $reseller->id, 'currency' => 'XAF']
            );
            $this->command->info("  ✓ {$data['name']}");
        }
    }
}
