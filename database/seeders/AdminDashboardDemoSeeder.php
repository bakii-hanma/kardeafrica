<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserCard;
use App\Support\AdminDashboardStats;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Jeu de démonstration du canal EN LIGNE, pour le tableau de bord admin.
 *
 * La base de démo n'avait aucune commande `orders` : tout l'écran s'affichait
 * en états vides, et le rendu peuplé était donc invérifiable. Ce seeder crée
 * des commandes réparties sur deux mois, avec plusieurs canaux de paiement, de
 * quoi voir vivre les deltas, la barre segmentée et les courbes.
 *
 * Idempotent : les commandes déjà créées par ce seeder (préfixe DEMO-) sont
 * supprimées avant régénération, pour ne pas empiler les jeux d'essai.
 */
class AdminDashboardDemoSeeder extends Seeder
{
    private const PREFIXE = 'DEMO-';

    public function run(): void
    {
        $tz = AdminDashboardStats::TZ;

        // Nettoyage du jeu précédent — jamais des vraies commandes.
        $anciennes = Order::where('order_number', 'like', self::PREFIXE . '%')->pluck('id');
        Payment::whereIn('order_id', $anciennes)->delete();
        UserCard::whereIn('order_id', $anciennes)->delete();
        OrderItem::whereIn('order_id', $anciennes)->delete();
        Order::whereIn('id', $anciennes)->delete();

        $clients = collect(['Aïssa Mbadinga', 'Franck Obame', 'Sylvie Ndong', 'Patrick Ondo', 'Nadia Boussougou'])
            ->map(fn ($nom, $i) => User::firstOrCreate(
                ['email' => 'client' . ($i + 1) . '@demo.kardafrica.ga'],
                ['name' => $nom, 'password' => Hash::make(Str::random(32))],
            ));

        $catalogue = [
            ['Netflix', 12_500], ['Spotify', 7_500], ['PlayStation', 25_000],
            ['Apple', 18_300], ['Xbox', 15_000], ['Binance', 36_400], ['Deezer', 6_800],
        ];

        // Méthodes réelles du marché : Mobile Money majoritaire, carte minoritaire.
        $methodes = ['airtel_money', 'airtel_money', 'moov_money', 'moov_money',
                     'mobile_money', 'visa_card', 'mastercard', 'paypal'];

        $now = Carbon::now($tz);
        $n = 0;

        // 60 jours d'historique : la période précédente est donc complète, et
        // les 6 barres mensuelles ont de la matière.
        for ($jour = 59; $jour >= 0; $jour--) {
            // Volume irrégulier, plus dense sur la période récente : une courbe
            // parfaitement plate ne dit rien du composant.
            $ventes = match (true) {
                $jour > 45 => $jour % 4 === 0 ? 1 : 0,
                $jour > 20 => $jour % 3 === 0 ? 2 : 1,
                default    => $jour % 2 === 0 ? 3 : 1,
            };

            for ($k = 0; $k < $ventes; $k++) {
                [$marque, $prix] = $catalogue[($jour + $k) % count($catalogue)];
                $client  = $clients[($jour + $k) % $clients->count()];
                $methode = $methodes[($jour * 3 + $k) % count($methodes)];
                $quand   = $now->copy()->subDays($jour)->setTime(8 + ($k * 4) % 12, ($jour * 7) % 60);

                // Une commande ne peut pas être passée dans le futur : sur le
                // jour courant, l'heure générée peut dépasser l'heure réelle et
                // la liste afficherait « dans 2 heures ».
                if ($quand->greaterThan($now)) {
                    $quand = $now->copy()->subMinutes(5 + $k * 17);
                }

                // Une commande sur douze échoue : les statuts doivent être variés
                // pour que les pills de statut se voient réellement.
                $paye = ($jour + $k) % 12 !== 0;

                $commande = Order::create([
                    'order_number'   => self::PREFIXE . $quand->format('ymd') . strtoupper(Str::random(4)),
                    'user_id'        => $client->id,
                    'subtotal'       => $prix,
                    'total_amount'   => $prix,
                    'billing_details' => ['name' => $client->name, 'phone' => '24106' . str_pad((string) $client->id, 6, '0', STR_PAD_LEFT)],
                    'status'         => $paye ? Order::STATUS_COMPLETED : Order::STATUS_CANCELLED,
                    'payment_status' => $paye ? Order::PAYMENT_STATUS_COMPLETED : Order::PAYMENT_STATUS_FAILED,
                    'payment_method' => $methode,
                ]);
                $commande->forceFill(['created_at' => $quand->utc(), 'updated_at' => $quand->utc()])->save();

                OrderItem::create([
                    'order_id' => $commande->id, 'quantity' => 1,
                    'unit_price' => $prix, 'total_price' => $prix,
                ]);

                Payment::create([
                    'transaction_id' => 'TX-' . strtoupper(Str::random(10)),
                    'order_id'       => $commande->id,
                    'user_id'        => $client->id,
                    'payment_method' => $methode,
                    'amount'         => $prix,
                    'currency'       => 'XAF',
                    'status'         => $paye ? 'completed' : 'failed',
                    'processed_at'   => $quand->utc(),
                ])->forceFill(['created_at' => $quand->utc()])->save();

                if ($paye) {
                    UserCard::create([
                        'user_id' => $client->id, 'order_id' => $commande->id,
                        'name' => $marque . ' Gift Card', 'brand' => $marque,
                        'card_code' => strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)),
                        'pin' => str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
                        'status' => UserCard::STATUS_ACTIVE,
                        'face_value' => $prix, 'currency' => 'XAF',
                    ]);
                }

                $n++;
            }
        }

        $this->command?->info("Jeu de démonstration admin : {$n} commandes sur 60 jours.");
    }
}
