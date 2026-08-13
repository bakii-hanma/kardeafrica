<?php

namespace Database\Seeders;

use App\Models\CardOwner;
use App\Models\MerchantCard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Compte commerçant de démonstration, pour visualiser l'espace propriétaire.
 *
 * Rattache aussi les cartes orphelines : le jeu de démonstration en contenait
 * six sans `card_owner_id`, publiées au catalogue et déjà vendues. Une carte
 * sans propriétaire ne peut être débitée par PERSONNE — `Owner\ScanController`
 * exige que la carte appartienne au commerçant qui scanne. Le client paie, et
 * ne peut jamais rien consommer.
 */
class CardOwnerDemoSeeder extends Seeder
{
    public const EMAIL    = 'demo@commercant.ga';
    public const PASSWORD = 'demo1234';

    public function run(): void
    {
        $owner = CardOwner::updateOrCreate(
            ['email' => self::EMAIL],
            [
                'business_name' => 'Restaurant Le Phare',
                'contact_name'  => 'Sylvie Ndong',
                'slug'          => Str::slug('Restaurant Le Phare Demo'),
                'phone'         => '24177445566',
                'password'      => Hash::make(self::PASSWORD),
                'city'          => 'Libreville',
                'is_active'     => true,
            ],
        );

        // `status` gouverne l'accès (machine à états de l'onboarding) et n'est
        // pas assignable en masse : sans lui, le middleware renvoie le
        // commerçant vers son parcours d'inscription au lieu du tableau de bord.
        $owner->forceFill([
            'status'    => CardOwner::STATUS_ACTIVE,
            'is_active' => true,
        ])->save();

        // Adoption des cartes orphelines : sans propriétaire, elles sont
        // invendables au sens propre — encaissées mais jamais consommables.
        $orphelines = MerchantCard::whereNull('card_owner_id')->get();

        foreach ($orphelines as $carte) {
            $carte->forceFill(['card_owner_id' => $owner->id])->save();
        }

        $this->command?->info(sprintf(
            'Commerçant démo : %s / %s — %d carte(s) rattachée(s), %d au total.',
            self::EMAIL,
            self::PASSWORD,
            $orphelines->count(),
            $owner->cards()->count(),
        ));
    }
}
