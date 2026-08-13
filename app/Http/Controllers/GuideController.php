<?php

namespace App\Http\Controllers;

use App\Services\ProductApiService;
use App\Support\Money;
use Illuminate\Support\Facades\Cache;

/**
 * Guides éditoriaux SEO/GEO (audit 06/08) — ciblent les questions PAA observées
 * sur Google (« Comment acheter Netflix au Gabon ? », « carte PSN gabon mobile
 * money »…), celles-là mêmes que posent les moteurs IA.
 *
 * Principes :
 *  - Prix en FCFA TIRÉS DU CATALOGUE LIVE (jamais inventés), datés du jour —
 *    le « tableau de prix vérifié » reste vrai en permanence.
 *  - FAQ par guide → affichée ET exposée en JSON-LD FAQPage.
 *  - Chaque guide = Article + FAQPage + BreadcrumbList schema, canonical auto.
 */
class GuideController extends Controller
{
    public function __construct(private ProductApiService $catalog) {}

    /**
     * Registre des guides. `updated` = date de dernière révision éditoriale
     * (les PRIX, eux, sont live). `price_queries` alimente les tableaux.
     */
    public static function guides(): array
    {
        return [
            'acheter-carte-netflix-gabon-airtel-money' => [
                'title'   => 'Comment acheter une carte Netflix au Gabon avec Airtel Money ? (Guide 2026)',
                'h1'      => 'Acheter une carte Netflix au Gabon avec Airtel Money',
                'meta'    => 'Payer Netflix au Gabon sans carte bancaire : achetez une carte cadeau Netflix par Airtel Money ou Moov Money, code reçu en 30 secondes. Étapes détaillées + prix réels en FCFA.',
                'excerpt' => 'Pas de carte bancaire ? Voici comment payer Netflix par Airtel Money ou Moov Money, étape par étape, avec les prix réels en FCFA.',
                'updated' => '2026-08-10',
                'price_queries' => ['Netflix' => 'Cartes Netflix disponibles'],
                'faq' => [
                    ['Comment payer Netflix par Airtel Money au Gabon ?', 'Achetez une carte cadeau Netflix sur KardAfrica : choisissez le montant, payez par Airtel Money (validation sur votre téléphone), puis entrez le code reçu sur netflix.com/redeem. Aucune carte bancaire n\'est nécessaire.'],
                    ['Quel est le prix d\'un abonnement Netflix en FCFA ?', 'Netflix ne facture pas directement en FCFA. Au Gabon, la solution est la carte cadeau : une carte Netflix de 25 € coûte environ le montant indiqué dans notre tableau de prix (mis à jour en continu) et crédite votre compte, qui décompte ensuite votre formule (Essentiel, Standard ou Premium).'],
                    ['Netflix fonctionne-t-il au Gabon ?', 'Oui, Netflix est disponible au Gabon. Les cartes cadeaux Netflix sont liées à une région (ex. Europe/France) : elles fonctionnent sur les comptes Netflix de la région indiquée sur la carte.'],
                    ['Peut-on payer Netflix sans carte bancaire ?', 'Oui : c\'est exactement l\'usage de la carte cadeau. Vous payez en FCFA par Airtel Money ou Moov Money, et votre compte Netflix est crédité avec le code reçu.'],
                    ['Combien de temps pour recevoir le code Netflix ?', 'Environ 30 secondes après la confirmation du paiement. Le code apparaît dans votre espace client « Mes cartes » sur KardAfrica. Code invalide ou non reçu = remboursé sous 24 h.'],
                ],
            ],

            'carte-psn-gabon-mobile-money' => [
                'title'   => 'Carte PSN au Gabon : acheter par Mobile Money (Airtel & Moov) — Guide 2026',
                'h1'      => 'Acheter une carte PSN au Gabon par Mobile Money',
                'meta'    => 'Créditez votre porte-monnaie PlayStation Store depuis le Gabon : carte PSN payée par Airtel Money ou Moov Money, code en 30 secondes. Guide pas à pas + prix réels en FCFA.',
                'excerpt' => 'Créditez votre porte-monnaie PlayStation Store en FCFA : étapes, choix de la région du compte, et prix réels des cartes PSN.',
                'updated' => '2026-08-10',
                'price_queries' => ['Playstation' => 'Cartes PSN disponibles'],
                'faq' => [
                    ['Où acheter une carte PSN au Gabon ?', 'Sur KardAfrica : choisissez une carte PSN (ex. PSN France 10 €, 20 €, 50 €…), payez par Airtel Money, Moov Money ou carte bancaire, et recevez le code en environ 30 secondes dans votre espace client.'],
                    ['Comment créditer son porte-monnaie PSN ?', 'Sur votre console ou sur store.playstation.com : connectez-vous, ouvrez « Utiliser un code », saisissez le code à 12 caractères de votre carte PSN. Le montant est crédité immédiatement sur votre porte-monnaie.'],
                    ['Quelle région de carte PSN choisir ?', 'La carte doit correspondre à la région de votre compte PlayStation : un compte PSN France utilise des cartes PSN France. Vérifiez la région de votre compte avant d\'acheter — c\'est la cause n°1 des codes « refusés ».'],
                    ['Peut-on payer une carte PSN par Airtel Money ?', 'Oui. Le paiement passe par Airtel Money ou Moov Money (validation sur votre téléphone) ou par carte bancaire. Le prix affiché en FCFA est le prix final.'],
                    ['Que faire si le code PSN ne fonctionne pas ?', 'Vérifiez d\'abord la région de votre compte. Sinon, contactez le support depuis « Mes commandes » : un code invalide ou non reçu est remboursé sous 24 h.'],
                ],
            ],

            'prix-cartes-cadeaux-fcfa' => [
                'title'   => 'Prix des cartes cadeaux en FCFA au Gabon — tableaux vérifiés (2026)',
                'h1'      => 'Prix des cartes cadeaux en FCFA au Gabon',
                'meta'    => 'Combien coûte une carte Netflix, PSN ou Xbox en FCFA au Gabon ? Tableaux de prix réels, mis à jour en continu, payables par Airtel Money et Moov Money.',
                'excerpt' => 'Netflix, PSN, Xbox : les prix réels en FCFA, expliqués (valeur faciale vs prix payé), mis à jour en continu depuis notre catalogue.',
                'updated' => '2026-08-10',
                'price_queries' => [
                    'Netflix'     => 'Cartes Netflix — prix en FCFA',
                    'Playstation' => 'Cartes PSN — prix en FCFA',
                    'Xbox'        => 'Cartes Xbox — prix en FCFA',
                ],
                'faq' => [
                    ['Pourquoi le prix en FCFA diffère-t-il de la valeur en euros ?', 'Le prix en FCFA correspond à la valeur de la carte convertie au taux du jour, arrondie au palier supérieur. La valeur faciale (ex. 25 €) est ce qui est crédité sur votre compte ; le prix FCFA est ce que vous payez — les deux sont affichés sur chaque carte.'],
                    ['Y a-t-il des frais cachés ?', 'Non. Le prix affiché en FCFA est le prix final débité, que vous payiez par Airtel Money, Moov Money ou carte bancaire.'],
                    ['Les prix changent-ils souvent ?', 'Ils suivent le taux de change et les tarifs des éditeurs. Les tableaux de cette page sont générés depuis notre catalogue en temps réel — le prix indiqué est celui que vous paierez maintenant.'],
                    ['Comment payer en FCFA ?', 'Par Airtel Money ou Moov Money (validation sur votre téléphone) ou par carte bancaire. Le code arrive en environ 30 secondes dans votre espace client.'],
                ],
            ],

            'xbox-game-pass-gabon' => [
                'title'   => 'Xbox Game Pass au Gabon : payer par Mobile Money — Guide 2026',
                'h1'      => 'Xbox Game Pass au Gabon, payé par Mobile Money',
                'meta'    => 'Abonnez-vous au Xbox Game Pass depuis le Gabon sans carte bancaire : carte Game Pass payée par Airtel Money ou Moov Money, code en 30 secondes. Formules Essential, Premium, Ultimate + prix réels en FCFA.',
                'excerpt' => 'Essential, Premium ou Ultimate : activez votre Game Pass en FCFA, sans carte bancaire, code livré en 30 secondes.',
                'updated' => '2026-08-11',
                'price_queries' => ['Game Pass' => 'Cartes Xbox Game Pass disponibles'],
                'faq' => [
                    ['Comment s\'abonner au Xbox Game Pass depuis le Gabon ?', 'Achetez une carte Xbox Game Pass sur KardAfrica (Essential, Premium ou Ultimate, 1 à 6 mois), payez par Airtel Money ou Moov Money, puis activez le code sur votre console ou sur xbox.com/redeemcode. L\'abonnement démarre immédiatement.'],
                    ['Quelle est la différence entre Essential, Premium et Ultimate ?', 'Essential donne accès au catalogue de base sur console, Premium ajoute plus de jeux et fonctionnalités, Ultimate inclut console + PC + cloud gaming et le multijoueur en ligne. Le tableau de cette page affiche nos prix réels pour chaque formule.'],
                    ['Quelle région de carte Game Pass choisir ?', 'La carte doit correspondre à la région de votre compte Microsoft : un compte enregistré en Europe utilise les cartes EU. Vérifiez la région de votre compte avant d\'acheter.'],
                    ['Le Game Pass fonctionne-t-il au Gabon ?', 'Oui : une fois le code activé sur votre compte, vous téléchargez et jouez normalement depuis le Gabon. Une bonne connexion est recommandée pour le cloud gaming (Ultimate).'],
                    ['Combien de temps pour recevoir le code ?', 'Environ 30 secondes après la confirmation du paiement, dans votre espace client « Mes cartes ». Code invalide ou non reçu = remboursé sous 24 h.'],
                ],
            ],

            'carte-google-play-gabon' => [
                'title'   => 'Carte Google Play au Gabon : acheter par Airtel Money — Guide 2026',
                'h1'      => 'Acheter une carte Google Play au Gabon',
                'meta'    => 'Créditez votre compte Google Play depuis le Gabon : applications, jeux, abonnements payés par Airtel Money ou Moov Money via une carte cadeau. Étapes + prix réels en FCFA.',
                'excerpt' => 'Applis, jeux, diamants Free Fire, abonnements : créditez Google Play en FCFA par Mobile Money, sans carte bancaire.',
                'updated' => '2026-08-11',
                'price_queries' => ['Google Play' => 'Cartes Google Play disponibles'],
                'faq' => [
                    ['Comment créditer son compte Google Play au Gabon ?', 'Achetez une carte Google Play sur KardAfrica, payez par Airtel Money ou Moov Money, puis entrez le code dans l\'application Play Store (Menu → Paiements et abonnements → Utiliser un code) ou sur play.google.com/redeem.'],
                    ['Que peut-on acheter avec une carte Google Play ?', 'Applications et jeux payants, achats intégrés (diamants Free Fire, UC PUBG, gemmes…), films, livres et abonnements facturés via Google Play. Le solde est débité automatiquement à chaque achat.'],
                    ['Quelle région de carte Google Play choisir ?', 'Le code doit correspondre au pays de votre profil de paiement Google. Vérifiez le pays de votre compte Google Play avant d\'acheter — c\'est la principale cause de codes refusés.'],
                    ['Peut-on payer Free Fire ou PUBG Mobile avec une carte Google Play ?', 'Oui : créditez votre compte Google Play avec la carte, puis effectuez l\'achat intégré dans le jeu — il sera débité de votre solde Google Play, sans carte bancaire.'],
                    ['Combien de temps pour recevoir le code ?', 'Environ 30 secondes après la confirmation du paiement, dans votre espace client « Mes cartes ». Code invalide ou non reçu = remboursé sous 24 h.'],
                ],
            ],

            'payer-musique-streaming-gabon' => [
                'title'   => 'Payer Spotify ou Deezer au Gabon sans carte bancaire — Guide 2026',
                'h1'      => 'Payer sa musique en streaming au Gabon (Spotify, Deezer)',
                'meta'    => 'Comment payer Spotify ou Deezer depuis le Gabon sans carte bancaire ? La carte cadeau payée par Airtel Money ou Moov Money. Solutions disponibles + prix réels en FCFA.',
                'excerpt' => 'Spotify, Deezer : comment payer son abonnement musique en FCFA par Mobile Money — et ce qui est disponible dès maintenant.',
                'updated' => '2026-08-11',
                'price_queries' => ['Deezer' => 'Cartes Deezer disponibles (immédiatement)'],
                'faq' => [
                    ['Comment payer Spotify au Gabon sans carte bancaire ?', 'Spotify Premium s\'active par carte cadeau Spotify (sur spotify.com/redeem). Ces cartes ne sont pas disponibles sur KardAfrica pour le moment — dès qu\'elles le seront, elles apparaîtront dans le catalogue. L\'alternative disponible immédiatement en Mobile Money est Deezer (voir tableau).'],
                    ['Deezer fonctionne-t-il au Gabon ?', 'Oui. Les cartes Deezer (Premium, Family, 1 à 12 mois) s\'activent sur deezer.com/gift : le compte est crédité et l\'abonnement démarre immédiatement, sans carte bancaire.'],
                    ['Quelle est la différence entre Deezer Premium et Deezer Family ?', 'Premium couvre un seul compte sans publicité avec écoute hors ligne ; Family couvre jusqu\'à 6 profils sur le même foyer. Les deux existent en cartes 1, 3, 6 ou 12 mois.'],
                    ['Comment activer une carte Deezer ?', 'Achetez la carte sur KardAfrica (paiement Airtel Money ou Moov Money), recevez le code en ~30 secondes dans « Mes cartes », puis saisissez-le sur deezer.com/gift en étant connecté à votre compte.'],
                    ['Peut-on payer YouTube Music ou Apple Music de la même façon ?', 'Apple Music peut être payé via une carte cadeau Apple (créditez votre compte Apple, puis l\'abonnement y est prélevé) — disponible sur KardAfrica. YouTube Music se finance via une carte Google Play, également disponible.'],
                ],
            ],

            'payer-mobile-money-airtel-moov' => [
                'title'   => 'Payer par Airtel Money ou Moov Money sur Internet au Gabon — Guide 2026',
                'h1'      => 'Payer en ligne par Airtel Money ou Moov Money au Gabon',
                'meta'    => 'Comment payer un achat en ligne par Airtel Money ou Moov Money au Gabon : étapes, sécurité, que faire si le paiement échoue. Exemple concret avec les cartes cadeaux KardAfrica.',
                'excerpt' => 'Le Mobile Money remplace la carte bancaire pour les achats en ligne : voici comment ça marche, en pratique et en sécurité.',
                'updated' => '2026-08-10',
                'price_queries' => [],
                'faq' => [
                    ['Comment payer sur Internet avec Airtel Money ?', 'Au moment de payer, choisissez « Airtel Money », saisissez votre numéro, puis validez la demande de paiement reçue sur votre téléphone avec votre code secret. Le marchand est notifié automatiquement — sur KardAfrica, votre code de carte arrive ensuite en ~30 secondes.'],
                    ['Le paiement Mobile Money est-il sécurisé ?', 'Oui : vous validez chaque paiement vous-même sur votre téléphone avec votre code secret, qui n\'est jamais communiqué au site. Sur KardAfrica, les paiements passent par E-Billing, agrégateur de paiement agréé au Gabon. Ne partagez jamais votre code secret Mobile Money.'],
                    ['Moov Money fonctionne-t-il aussi ?', 'Oui, Moov Money est accepté au même titre qu\'Airtel Money, avec le même déroulé : saisie du numéro, validation sur le téléphone, confirmation immédiate.'],
                    ['Que faire si le paiement échoue ?', 'Vérifiez votre solde et recommencez — aucune somme n\'est débitée sans validation de votre part. Si un paiement est débité sans livraison, le remboursement est automatique ; sinon le support répond sous 24 h.'],
                    ['Faut-il une carte bancaire pour acheter en ligne au Gabon ?', 'Non. Le Mobile Money suffit pour tous les achats sur KardAfrica : cartes Netflix, PSN, Xbox, Google Play, et les cartes cadeaux de commerçants gabonais.'],
                ],
            ],
        ];
    }

    public function index()
    {
        return view('guides.index', ['guides' => self::guides()]);
    }

    public function show(string $slug)
    {
        $guides = self::guides();
        abort_unless(isset($guides[$slug]), 404);
        $guide = $guides[$slug];

        // Tableaux de prix LIVE (jamais inventés) — cache court, tolérant aux pannes.
        $priceTables = [];
        foreach ($guide['price_queries'] as $query => $label) {
            $rows = $this->prices($query);
            if (!empty($rows)) {
                $priceTables[$label] = $rows;
            }
        }

        return view('guides.show', [
            'slug'        => $slug,
            'guide'       => $guide,
            'priceTables' => $priceTables,
            'allGuides'   => $guides,
        ]);
    }

    /**
     * Prix réels du catalogue pour une marque : filtre FR/EU (marché cible),
     * tri par prix, dédoublonné par valeur faciale. Vide si l'API est down —
     * la vue masque alors le tableau (on n'affiche jamais de faux prix).
     */
    private function prices(string $query): array
    {
        return Cache::remember("guide_prices_" . md5($query), 21600, function () use ($query) {
            try {
                return collect($this->catalog->searchProductsViaApi($query, 40))
                    ->filter(fn ($p) => in_array(strtoupper($p['cardType']['countryCode'] ?? ''), ['FR', 'EU'], true))
                    ->filter(fn ($p) => ($p['price']['min'] ?? 0) > 0)
                    ->sortBy(fn ($p) => Money::toFcfa($p['price']['min'], $p['price']['currencyCode'] ?? 'XAF'))
                    ->unique(fn ($p) => ($p['minFaceValue'] ?? 0) . ($p['price']['currencyCode'] ?? ''))
                    ->take(8)
                    ->map(fn ($p) => [
                        'name'  => $p['name'] ?? '',
                        'face'  => Money::formatOriginal($p['minFaceValue'] ?? ($p['price']['min'] ?? 0), $p['price']['currencyCode'] ?? 'XAF'),
                        'fcfa'  => Money::formatFcfa($p['price']['min'], $p['price']['currencyCode'] ?? 'XAF'),
                        'url'   => ($id = $p['cardType']['internalId'] ?? $p['cardType']['id'] ?? null)
                            ? route('card-type.show', $id) : route('boutique'),
                    ])
                    ->values()
                    ->all();
            } catch (\Throwable $e) {
                return [];
            }
        });
    }
}
