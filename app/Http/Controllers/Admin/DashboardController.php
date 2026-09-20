<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BambooReportingService;
use App\Support\AdminDashboardStats;
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

        return view('admin.dashboard', [
            'stats'    => AdminDashboardStats::fromRequest($request),
            'bamboo'   => $bamboo->accounts(),
            'bambooThreshold' => $threshold,
        ]);
    }
}
