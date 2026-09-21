<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Reseller;
use App\Services\BambooReportingService;
use App\Services\OrderDeliveryService;
use App\Services\PaymentRefundService;
use App\Services\ProductApiService;
use App\Support\BambooProfit;
use App\Support\BambooRates;
use App\Support\RefundPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Petit endpoint JSON pour tester la connectivité afrikard depuis l'UI admin.
     */
    public function pingAfrikard(ProductApiService $service)
    {
        return response()->json($service->ping());
    }

    /**
     * Liste les commandes payées sans cartes livrées (en attente du fournisseur).
     */
    public function pendingDelivery(Request $request)
    {
        $orders = Order::with(['user', 'orderItems'])
            ->where('payment_status', Order::PAYMENT_STATUS_COMPLETED)
            ->where('status', '!=', Order::STATUS_COMPLETED)
            ->whereDoesntHave('userCards')
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $service = app(ProductApiService::class);
        $apiStatus = $service->ping(5);

        return view('admin.orders.pending-delivery', compact('orders', 'apiStatus'));
    }

    /**
     * Retry en lot — relance /orders/checkout pour chaque commande sélectionnée.
     * S'arrête immédiatement si l'API est down (évite N appels qui timeout 30s chacun).
     */
    public function retryBulk(Request $request)
    {
        $request->validate([
            'order_ids'   => 'required|array|min:1|max:100',
            'order_ids.*' => 'integer|exists:orders,id',
        ]);

        $service = app(ProductApiService::class);
        $ping = $service->ping(5);
        if (!$ping['ok']) {
            return back()->with('error', "API afrikard indisponible — {$ping['message']}. Annulé sans aucun appel.");
        }

        $orders = Order::with('orderItems', 'userCards')
            ->whereIn('id', $request->order_ids)
            ->get();

        $delivery = app(OrderDeliveryService::class);
        $results = [
            'delivered' => 0, 'pending' => 0, 'skipped' => 0, 'failed' => 0,
            'errors' => [],
        ];

        foreach ($orders as $order) {
            $r = $delivery->attempt($order);

            switch ($r['type']) {
                case 'delivered':
                    $results['delivered']++;
                    break;
                case 'pending':
                case 'unavailable':
                    $results['pending']++;
                    $results['errors'][] = "#{$order->order_number} → " . ($r['message'] ?? 'codes en attente (async) — polling auto');
                    break;
                case 'already_delivered':
                case 'not_paid':
                case 'refunded':
                case 'no_afrikard_items':
                    $results['skipped']++;
                    break;
                case 'orphan_debited':
                    $results['failed']++;
                    $results['errors'][] = "#{$order->order_number} → " . $r['message'];
                    break;
                case 'rejected':
                case 'failed':
                    $results['failed']++;
                    $results['errors'][] = "#{$order->order_number} → fournisseur HTTP " . ($r['status'] ?? '?');
                    Log::warning('Bulk retry checkout : afrikard a echoue', [
                        'order_id' => $order->id, 'status' => $r['status'] ?? null,
                    ]);
                    break;
                default:
                    $results['failed']++;
                    $results['errors'][] = "#{$order->order_number} → erreur inattendue";
            }
        }

        $msg = "Retry terminé — {$results['delivered']} livrées, {$results['pending']} en attente, "
            . "{$results['failed']} échouées, {$results['skipped']} ignorées.";
        if (!empty($results['errors'])) {
            $msg .= ' Détails : ' . implode(' | ', array_slice($results['errors'], 0, 3));
        }

        return back()->with($results['delivered'] > 0 ? 'success' : 'error', $msg);
    }

    public function index(Request $request)
    {
        // `withCount` : la liste affiche le nombre d'articles par commande.
        // Le charger par ligne coûtait une requête chacune — 20 par page.
        $query = Order::with('user', 'userCards')->withCount('orderItems')->latest();

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtre par statut de paiement
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Recherche par numero de commande
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filtre par date
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->paginate(20)->withQueryString();

        // Compteurs d'onglets : une requête groupée, pas une par statut.
        // Ils comptent TOUTES les commandes, pas la page courante — un onglet
        // qui n'annoncerait que sa page ne servirait à rien.
        $statusCounts = Order::query()
            ->select('status', \DB::raw('COUNT(*) as n'))
            ->groupBy('status')
            ->pluck('n', 'status');

        return view('admin.orders.index', compact('orders', 'statusCounts'));
    }

    public function show(Order $order, BambooReportingService $bamboo)
    {
        $order->load(['user', 'orderItems', 'payments', 'userCards', 'cashReseller']);

        $canRetry = $order->status === Order::STATUS_PROCESSING
            && $order->userCards->isEmpty();

        // Bénéfice : coût réel (transaction Bamboo) ou valeur faciale en repli.
        $profit  = null;
        $split   = [];
        $rates   = $bamboo->exchangeRates();
        $xafRates = BambooRates::toXafRates($rates['rates'] ?? []);

        if (! empty($xafRates)) {
            $bd = (array) $order->billing_details;
            $hasLink = ($bd['checkout_order_id'] ?? null) !== null
                || ($bd['checkout_request_id'] ?? null) !== null;

            if ($hasLink && $order->created_at) {
                $tz = 'Africa/Libreville';
                $from = $order->created_at->setTimezone($tz)->subDays(2)->format('Y-m-d');
                $to   = $order->created_at->setTimezone($tz)->addDays(2)->format('Y-m-d');

                $tx = $bamboo->transactions($from, $to);
                $flat = collect($tx['clients'] ?? [])
                    ->flatMap(fn ($c) => collect($c['transactions'] ?? [])
                        ->map(fn ($t) => $t + ['bambooClient' => $c['clientName'] ?? null]));

                $matched = BambooProfit::matchTransaction($order, $flat);

                if ($matched !== null) {
                    $split = BambooProfit::splitByItem($order, $matched, $xafRates);
                }

                $profit = BambooProfit::forOrder($order, $matched, $xafRates);
            }

            if ($profit === null) {
                $profit = BambooProfit::forOrder($order, null, $xafRates);
            }
        }

        return view('admin.orders.show', compact('order', 'canRetry', 'profit', 'split', 'xafRates'));
    }

    /**
     * Relancer la livraison afrikard pour une commande payée mais sans cartes.
     * Conforme H4 : ne re-POSTe JAMAIS si un checkout a déjà été demandé —
     * dans ce cas, on poll GET /orders/{requestId} pour récupérer les cartes
     * (génération asynchrone côté Bamboo).
     */
    public function retryCheckout(Order $order)
    {
        $result = app(OrderDeliveryService::class)->attempt($order);

        return match ($result['type']) {
            'already_delivered' => back()->with('error', 'Cette commande a deja des cartes livrees.'),
            'not_paid' => back()->with('error', 'Le paiement de cette commande n\'est pas confirme.'),
            'refunded' => back()->with('error', 'Cette commande est remboursée ou annulée.'),
            'missing' => back()->with('error',
                'Catalogue fournisseur incomplet (produits ' . implode(', ', $result['missing']) . ' manquants). '
                . 'Essai automatique programmé — patiente quelques minutes puis réessaie.'),
            'no_afrikard_items' => back()->with('error', 'Aucun item fournisseur à livrer sur cette commande.'),
            'orphan_debited' => back()->with('error', $result['message']),
            'pending' => back()->with('warning',
                'Livraison demandée — les codes sont en cours de génération côté fournisseur (asynchrone). '
                . ($result['saved'] > 0 ? "{$result['saved']} carte(s) déjà enregistrée(s). " : '')
                . 'Réessaie dans quelques minutes ou laisse la récupération automatique faire.'),
            'delivered' => back()->with('success',
                'Cartes livrees avec succes pour #' . $order->order_number
                . ($result['saved'] > 0 ? " ({$result['saved']} cartes)" : '')),
            'rejected' => back()->with('error', "Le fournisseur n'a pas pu finaliser la livraison. Réessayez dans quelques minutes."),
            'unavailable' => back()->with('error', $result['message'] ?? "Connexion temporairement indisponible avec le fournisseur."),
            default => back()->with('error', 'Erreur inattendue lors de la livraison.'),
        };
    }

    /**
     * Remboursement initié par l'admin : restaure le wallet du vendeur si cash,
     * appelle E-Billing transfer si paiement en ligne, puis annule la commande.
     */
    public function refund(Request $request, Order $order, PaymentRefundService $refundSvc)
    {
        if ($order->userCards()->exists()) {
            return back()->with('error', 'Cette commande a déjà des cartes livrées.');
        }
        if ($order->payment_status !== Order::PAYMENT_STATUS_COMPLETED) {
            return back()->with('error', 'Le paiement n\'est pas confirmé.');
        }
        if (in_array($order->status, [Order::STATUS_REFUNDED, Order::STATUS_REFUNDING], true)) {
            return back()->with('error', 'Cette commande est déjà remboursée ou un remboursement est en cours.');
        }

        // Numéro de destination du remboursement : numéro du compte du client
        // par défaut, ou « autre numéro » saisi par l'admin. Résolu avant le
        // verrou (lecture seule) ; le numéro ne change pas pendant le virement.
        $refundPhone = null;
        if ($order->payment_method === 'ebilling') {
            $refundPhone = RefundPhone::resolve($order, $request);
            if ($refundPhone['msisdn'] === null) {
                return back()->with('error', 'Aucun numéro de remboursement valide. Renseigne le numéro du compte ou un autre numéro Mobile Money.');
            }
        }

        // ============================================================
        // Machine à états anti double-virement (H5) :
        // 1. verrou + tombstone 'refunding' (cash : clôturé ici sous verrou)
        // 2. virement E-Billing HORS transaction (réf. déterministe → PSP déduplique)
        // 3. clôture = statut REFUNDED
        // ============================================================
        $previousStatus = null;
        try {
            $claim = DB::transaction(function () use ($order, &$previousStatus) {
                $locked = Order::where('id', $order->id)->lockForUpdate()->first();

                if ($locked->payment_status !== Order::PAYMENT_STATUS_COMPLETED
                    || in_array($locked->status, [Order::STATUS_REFUNDED, Order::STATUS_REFUNDING], true)
                    || $locked->userCards()->exists()) {
                    return false;
                }

                $previousStatus = $locked->status;

                // Cash chez vendeur : pas d'appel API — restaure le wallet et
                // clôture immédiatement, le tout dans le verrou (anti double submit).
                if ($locked->payment_method === Order::PAYMENT_METHOD_CASH_RESELLER) {
                    $reseller = Reseller::lockForUpdate()->find($locked->cash_reseller_id);
                    if ($reseller) {
                        $reseller->refundCredit((float) $locked->total_amount, "Remboursement #{$locked->order_number}", $locked->order_number);
                    }
                    $locked->update([
                        'status'         => Order::STATUS_REFUNDED,
                        'payment_status' => Order::PAYMENT_STATUS_REFUNDED,
                        'notes'          => 'Remboursée par admin — le vendeur doit rendre le cash au client',
                    ]);
                    return 'cash_done';
                }

                $locked->update(['status' => Order::STATUS_REFUNDING]);
                return true;
            });
        } catch (\Throwable $e) {
            Log::error('Admin refund claim exception', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }

        if ($claim === false || $claim === null) {
            return back()->with('error', 'Cette commande est déjà remboursée ou un remboursement est en cours.');
        }

        if ($claim === 'cash_done') {
            return back()->with('success', 'Remboursement enregistré. Wallet vendeur restauré, le vendeur doit rendre le cash au client.');
        }

        // E-Billing : transfer HORS transaction. En cas d'échec, retour à l'état
        // antérieur pour autoriser un futur retry (le PSP déduplique grâce à la
        // référence de transfert déterministe REFUND_<originalReference>).
        if ($order->payment_method === 'ebilling') {
            $result = $refundSvc->refund(
                originalReference: $order->external_reference,
                amountFcfa: (int) round($order->total_amount),
                reason: "Remboursement #{$order->order_number}",
                extras: [
                    'msisdn' => $refundPhone['msisdn'],
                    'name'   => data_get($order->billing_details, 'name') ?? optional($order->user)->name,
                    'email'  => data_get($order->billing_details, 'email') ?? optional($order->user)->email,
                ],
            );
            if (!$result['ok']) {
                Order::where('id', $order->id)->update(['status' => $previousStatus]);
                return back()->with('error', 'Remboursement E-Billing refusé : ' . $result['message']);
            }
        }

        try {
            $order->update([
                'status'         => Order::STATUS_REFUNDED,
                'payment_status' => Order::PAYMENT_STATUS_REFUNDED,
                'notes'          => 'Remboursée par admin (' . $order->payment_method . ')',
            ]);
            return back()->with('success', 'Remboursement effectué avec succès.');
        } catch (\Throwable $e) {
            Log::error('Admin refund exception', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            Order::where('id', $order->id)->update(['status' => $previousStatus]);
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }
}
