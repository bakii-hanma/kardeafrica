<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\BambooReportingService;
use App\Services\OrderDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Administration fournisseur Bamboo.
 *
 * - Solde des comptes + alerte de seuil bas (feature 3)
 * - Transactions / marge sur une période (feature 4)
 * - Taux de change officiels (feature 5)
 * - Réconciliation : historique des commandes Bamboo vs commandes locales
 *   orphelines, et relance de récupération (feature 7)
 *
 * Tous les chiffres viennent du proxy afrikard (BambooReportingService) —
 * l'app n'est pas whitelistée côté Bamboo.
 */
class BambooAdminController extends Controller
{
    public function __construct(private BambooReportingService $bamboo)
    {
    }

    public function index(Request $request)
    {
        $tz        = 'Africa/Libreville';
        $today     = Carbon::now($tz);
        $startDate = $request->query('start_date')
            ? Carbon::parse($request->query('start_date'), $tz)->format('Y-m-d')
            : $today->copy()->subDays(29)->format('Y-m-d');
        $endDate   = $request->query('end_date')
            ? Carbon::parse($request->query('end_date'), $tz)->format('Y-m-d')
            : $today->format('Y-m-d');

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $accounts    = $this->bamboo->accounts(fresh: $request->boolean('refresh'));
        $transactions = $this->bamboo->transactions($startDate, $endDate, $request->boolean('refresh'));
        $rates       = $this->bamboo->exchangeRates($request->boolean('refresh'));

        // Seuil d'alerte sur le solde (feature 3) : compte EUR (le compte de
        // travail) et équivalents USD convertis grossièrement.
        $threshold = (float) config('services.bamboo.accounts_alert_threshold_eur');
        $low = [];
        foreach ($accounts['accounts'] as $acc) {
            $bal = (float) ($acc['balance'] ?? 0);
            $cur = strtoupper((string) ($acc['currency'] ?? ''));
            if ($cur === 'EUR' && $bal < $threshold) {
                $low[] = $acc;
            }
        }

        return view('admin.bamboo.index', compact(
            'accounts', 'transactions', 'rates', 'startDate', 'endDate', 'threshold', 'low'
        ));
    }

    /**
     * Réconciliation : liste les commandes Bamboo de la période et les rapproche
     * des commandes locales (par requestId/orderId). Relance la récupération des
     * commandes locales orphelines (payées, sans cartes, avec reference fournisseur).
     */
    public function reconcile(Request $request)
    {
        $tz        = 'Africa/Libreville';
        $today     = Carbon::now($tz);
        $startDate = $request->query('start_date')
            ? Carbon::parse($request->query('start_date'), $tz)->format('Y-m-d')
            : $today->copy()->subDays(6)->format('Y-m-d');
        $endDate   = $request->query('end_date')
            ? Carbon::parse($request->query('end_date'), $tz)->format('Y-m-d')
            : $today->format('Y-m-d');

        $report = $this->bamboo->orderReport($startDate, $endDate, $request->boolean('refresh'));

        // Commandes locales éligibles à la récupération sur la même période.
        $locales = Order::with(['user', 'orderItems'])
            ->where('payment_status', Order::PAYMENT_STATUS_COMPLETED)
            ->whereNotIn('status', [Order::STATUS_COMPLETED, Order::STATUS_REFUNDED, Order::STATUS_CANCELLED])
            ->whereBetween('created_at', [Carbon::parse($startDate, $tz)->startOfDay()->utc(), Carbon::parse($endDate, $tz)->endOfDay()->utc()])
            ->latest()
            ->get();

        $recovered    = 0;
        $details      = [];
        $delivery     = app(OrderDeliveryService::class);

        if ($request->boolean('run') && $report['ok']) {
            // Une commande Bamboo est « trouvée » localement si
            // billing_details->checkout_request_id == clientReferenceNumber.
            $bambooRequestIds = collect($report['orders'])
                ->pluck('clientReferenceNumber')
                ->filter()
                ->flip();

            foreach ($locales as $order) {
                $bd = (array) $order->billing_details;
                $req = $bd['checkout_request_id'] ?? null;
                $oid = $bd['checkout_order_id'] ?? null;

                // 1) Commande présente dans l'historique Bamboo mais pas
                //    complétée localement → relance la récupération.
                if ($req !== null && $bambooRequestIds->has($req)) {
                    try {
                        $res = $delivery->recover($order);
                        if ($res['type'] === 'delivered') {
                            $recovered++;
                            $details[] = ['order' => $order, 'result' => $res];
                        }
                    } catch (\Throwable $e) {
                        // Un échec réseau sur UNE commande ne bloque pas le lot.
                    }
                    continue;
                }

                // 2) Orpheline stricte : livraison demandée, jamais retrouvée dans
                //    l'historique Bamboo de la période → signalement pour l'admin.
                if ($order->delivery_requested_at !== null && $req !== null && $oid !== null) {
                    $details[] = ['order' => $order, 'result' => ['type' => 'orphan_debited', 'message' => 'Commande référencée Bamboo sans trace dans l\'historique fournisseur.']];
                }
            }
        }

        return view('admin.bamboo.reconcile', [
            'report'      => $report,
            'startDate'   => $startDate,
            'endDate'     => $endDate,
            'locales'     => $locales,
            'recovered'   => $recovered,
            'details'     => $details,
        ]);
    }
}