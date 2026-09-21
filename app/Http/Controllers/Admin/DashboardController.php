<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BambooReportingService;
use App\Support\AdminDashboardStats;
use App\Support\BambooRates;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Tableau de bord admin.
     *
     * Tous les chiffres viennent d'`AdminDashboardStats`, instanciée UNE fois :
     * chaque widget lui demande ses valeurs, elle mémoïse. Auparavant la méthode
     * empilait douze requêtes indépendantes, dont plusieurs recalculaient la
     * même chose, et aucune ne respectait la période choisie dans la topbar.
     */
    public function index(Request $request, BambooReportingService $bamboo)
    {
        $threshold = (float) config('services.bamboo.accounts_alert_threshold_eur');

        $stats    = AdminDashboardStats::fromRequest($request);

        // Équivalent FCFA du solde fournisseur : taux officiels Bamboo
        // convertis vers XAF (fallback Money), pour un total lisible dans
        // la monnaie locale de l'admin.
        $accounts  = $bamboo->accounts();
        $rates     = $bamboo->exchangeRates();
        $xafRates  = BambooRates::toXafRates($rates['rates'] ?? []);
        $totalXaf  = $accounts['ok'] ? BambooRates::totalXaf($accounts['accounts'] ?? [], $xafRates) : null;

        // Transactions Bamboo sur la même fenêtre que la topbar : le bénéfice
        // (prix Kardafrica − coût réel) est rapproché des ventes payées.
        $txPeriod = $bamboo->transactions(
            $stats->from()->format('Y-m-d'),
            $stats->to()->format('Y-m-d'),
        );
        $transactions = collect($txPeriod['clients'] ?? [])
            ->flatMap(fn ($c) => collect($c['transactions'] ?? [])
                ->map(fn ($t) => $t + ['bambooClient' => $c['clientName'] ?? null]));
        $profit = $stats->profit($transactions, $xafRates);

        return view('admin.dashboard', [
            'stats'    => $stats,
            'bamboo'   => $accounts,
            'bambooThreshold' => $threshold,
            'bambooTotalXaf'  => $totalXaf,
            'profit'   => $profit,
        ]);
    }
}
