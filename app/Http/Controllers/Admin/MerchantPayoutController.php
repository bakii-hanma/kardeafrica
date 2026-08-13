<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MerchantSettlement;
use App\Support\CsvExport;
use App\Support\OwnerEarnings;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Récapitulatif hebdomadaire des versements aux commerçants.
 *
 * Les Cartes Gabon sont réglées une fois par semaine, le lundi suivant l'achat.
 * Sans cet écran, il fallait ouvrir la fiche de chaque commerçant pour savoir
 * quoi lui virer — le lundi matin, avec le risque d'en oublier un.
 *
 * L'écran ne verse rien : les virements Mobile Money se font hors application.
 * Il dit qui payer, combien, et enregistre ce qui a été fait.
 */
class MerchantPayoutController extends Controller
{
    public function index(Request $request)
    {
        // Une seule lecture de la base, filtrée ensuite en mémoire : la liste
        // des commerçants tient largement en RAM, et recalculer les agrégats
        // par onglet coûterait trois passes de sous-requêtes jointes.
        $toutes = OwnerEarnings::payoutRun(onlyDue: false);

        $onglet = $request->query('onglet', 'du');
        if (! in_array($onglet, ['du', 'soldes', 'tous'], true)) {
            $onglet = 'du';
        }

        $recherche = trim((string) $request->query('search', ''));

        $compteurs = [
            'du'     => $toutes->filter(fn ($l) => (float) $l->solde > 0)->count(),
            // « Soldé » = ce qui était dû a été versé. Un commerçant sans aucune
            // vente n'est pas soldé, il est simplement inactif.
            'soldes' => $toutes->filter(fn ($l) => (float) $l->solde <= 0 && (float) $l->verse > 0)->count(),
            'tous'   => $toutes->count(),
        ];

        $filtrees = match ($onglet) {
            'soldes' => $toutes->filter(fn ($l) => (float) $l->solde <= 0 && (float) $l->verse > 0),
            'tous'   => $toutes,
            default  => $toutes->filter(fn ($l) => (float) $l->solde > 0),
        };

        $lignes = $filtrees->values();

        if ($recherche !== '') {
            $lignes = $lignes->filter(fn ($l) => str_contains(
                mb_strtolower($l->business_name . ' ' . $l->contact_name . ' ' . $l->phone),
                mb_strtolower($recherche),
            ))->values();
        }

        return view('admin.versements.index', [
            'lignes'        => $lignes,
            'compteurs'     => $compteurs,
            'onglet'        => $onglet,
            'recherche'     => $recherche,
            // Synthèse calculée sur TOUT, pas sur l'onglet : « total à payer »
            // doit rester le même quel que soit le filtre affiché.
            'total'         => $toutes->sum(fn ($l) => max(0, (float) $l->solde)),
            'totalVerse'    => $toutes->sum(fn ($l) => (float) $l->verse),
            'aVenir'        => $toutes->sum(fn ($l) => (float) $l->a_venir),
            'prochainLundi' => today()->next(\Illuminate\Support\Carbon::MONDAY),
        ]);
    }

    /** Enregistre le versement d'un commerçant depuis la liste du lundi. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'card_owner_id' => ['required', 'integer', 'exists:card_owners,id'],
            'amount'        => ['required', 'numeric', 'min:1'],
            'method'        => ['required', 'string', Rule::in(array_keys(MerchantSettlement::METHODS))],
            'reference'     => ['nullable', 'string', 'max:120'],
        ]);

        MerchantSettlement::create($data + [
            'recorded_by' => Auth::id(),
            'settled_at'  => now(),
        ]);

        Log::info('Admin: versement commerçant enregistré depuis le récapitulatif', [
            'card_owner_id' => $data['card_owner_id'],
            'montant'       => (float) $data['amount'],
            'par'           => Auth::id(),
        ]);

        return back()->with('success', 'Versement enregistré.');
    }

    /**
     * Export du lot à virer.
     *
     * C'est le vrai besoin du lundi matin : un fichier à côté de l'interface
     * Mobile Money, pas un écran à recopier ligne à ligne.
     */
    public function export(Request $request)
    {
        $lignes = OwnerEarnings::payoutRun(onlyDue: $request->query('onglet', 'du') === 'du');

        return CsvExport::stream(
            CsvExport::filename('kardafrica-versements-commercants'),
            ['Commerçant', 'Contact', 'Téléphone', 'Ville', 'À payer (FCFA)',
             'Exigible (FCFA)', 'Déjà versé (FCFA)', 'Semaine en cours (FCFA)', 'Dernier versement'],
            $lignes->map(fn ($l) => [
                $l->business_name,
                $l->contact_name,
                // Forme canonique : elle se colle telle quelle dans une interface
                // Mobile Money, sans reprise à la main.
                Phone::normalize($l->phone),
                $l->city,
                (float) $l->solde,
                (float) $l->exigible,
                (float) $l->verse,
                (float) $l->a_venir,
                $l->dernier_versement ? date('d/m/Y', strtotime($l->dernier_versement)) : '',
            ]),
        );
    }
}
