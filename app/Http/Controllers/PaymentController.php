<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\UserCard;
use App\Models\ShoppingCart;
use App\Jobs\ProcessCheckoutJob;

class PaymentController extends Controller
{
    /**
     * Formater le numero de telephone (format Gabon +241)
     */
    private function formatPhoneNumber($phone)
    {
        if (empty($phone)) return '24174000000';

        $cleaned = preg_replace('/\D/', '', $phone);

        if (str_starts_with($cleaned, '00')) {
            $cleaned = substr($cleaned, 2);
        }

        if (str_starts_with($cleaned, '241')) {
            return $cleaned;
        }

        if (str_starts_with($cleaned, '0')) {
            return '241' . substr($cleaned, 1);
        }

        return '241' . $cleaned;
    }

    /**
     * Format NATIONAL attendu par init.php (portail) : 0[67]XXXXXXX.
     * Ex. "+241 77 04 78 61" / "24177047861" → "077047861".
     */
    private function formatPhoneNational($phone)
    {
        $c = preg_replace('/\D/', '', (string) $phone);
        if (str_starts_with($c, '00'))  $c = substr($c, 2);
        if (str_starts_with($c, '241')) $c = substr($c, 3);
        if (!str_starts_with($c, '0'))  $c = '0' . $c;
        return $c;
    }

    /**
     * Initialiser un paiement via futursowax/portal.php pour une commande existante.
     *
     * Body attendu :
     *   - order_id (required) : id de la commande pending
     *   - phone   (required) : numero du payeur
     *   - email   (optional) : sinon user->email
     *   - name    (optional) : sinon user->name
     */
    public function init(Request $request)
    {
        try {
            $validated = $request->validate([
                'order_id' => 'required|integer|exists:orders,id',
                'phone'    => 'required|string|max:20',
                'email'    => 'nullable|email|max:255',
                'name'     => 'nullable|string|max:255',
            ]);

            $order = Order::findOrFail($validated['order_id']);
            $user  = $request->user();

            if ($user && $order->user_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Commande non autorisee.'], 403);
            }

            if ($order->payment_status === Order::PAYMENT_STATUS_COMPLETED) {
                return response()->json(['success' => false, 'message' => 'Commande deja payee.'], 409);
            }

            // C3 : référence NON prédictible (l'ancien rand(1000,9999) laissait
            // 9 000 valeurs par seconde d'horodatage, énumérables).
            $externalRef = $order->external_reference
                ?: ('KARD_' . time() . '_' . strtoupper(bin2hex(random_bytes(6))));
            if (!$order->external_reference) {
                $order->update(['external_reference' => $externalRef]);
            }

            // Numéro FIXE d'initialisation du paiement (le client paie ensuite sur
            // le portail Mobile Money redirigé). Configurable via
            // PAYMENT_BACKEND_FIXED_MSISDN, défaut 076527007.
            $payPhone = (string) config('services.payment_backend.fixed_msisdn', '076527007');

            $payload = [
                'amount'            => (int) round($order->total_amount),
                'short_description' => 'Commande ' . $order->order_number,
                'reference'         => $externalRef,
                // portal.php lit `reference` ; init.php lit `external_reference`
                // (les autres champs sont ignorés et il génère une réf LAMAP_).
                // On envoie les deux = notre réf → init.php enregistre sous la
                // BONNE référence, donc check_status.php la retrouve.
                'external_reference' => $externalRef,
                'order_id'          => $externalRef,
                'email'             => $validated['email'] ?? $user?->email ?? 'noreply@kardafrica.com',
                // portal.php attend `msisdn` (format 241…) ; init.php attend `phone`
                // (format national 0[67]XXXXXXX). Sans `phone`, init.php répondait
                // 400 « Phone number is required » → transaction jamais enregistrée
                // sur le portail → un 2e e-bill était créé au paiement.
                'msisdn'            => $this->formatPhoneNumber($payPhone),
                'phone'             => $this->formatPhoneNational($payPhone),
                'name'              => $validated['name'] ?? $user?->name ?? 'Client KardAfrica',
                'callback_url'      => config('services.payment_backend.callback_url')
                    ?? url('/payment/return?ref=' . $externalRef),
                'format'            => 'json',
            ];

            // En-têtes navigateur OBLIGATOIRES (contournement anti-bot sgcaptcha) :
            // sans eux, futursowax renvoie une page HTML au lieu du JSON.
            $appUrl = rtrim((string) config('app.url', 'https://kardafrica.com'), '/');
            $browserHeaders = [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
                'Origin'     => $appUrl,
                'Referer'    => $appUrl . '/',
            ];

            // 1) init.php — ENREGISTRE la transaction dans le MySQL du portail.
            //    Indispensable : sinon le portail crée un AUTRE bill au paiement
            //    (le nôtre reste "expired") et check_status.php ne retrouve
            //    jamais le paiement sous notre référence. Best-effort : on log
            //    mais on ne bloque pas la création de facture si init.php échoue.
            try {
                $reg = Http::timeout(20)->withHeaders($browserHeaders)->acceptJson()->asForm()
                    ->post(config('services.payment_backend.register_url'), $payload);
                Log::info('Futursowax init.php (register) response', ['status' => $reg->status()]);
            } catch (\Throwable $e) {
                Log::warning('Futursowax init.php exception', ['error' => $e->getMessage()]);
            }

            // 2) portal.php — crée la facture E-Billing (bill_id + portal_url).
            $response = Http::timeout(20)
                ->withHeaders($browserHeaders)
                ->acceptJson()
                ->asForm()
                ->post(config('services.payment_backend.init_url'), $payload);

            Log::info('Futursowax portal.php response', [
                'status' => $response->status(),
                'body'   => $response->json() ?? $response->body(),
            ]);

            if (!$response->successful()) {
                $body = $response->json();
                return response()->json([
                    'success' => false,
                    'message' => $body['error'] ?? 'Erreur lors de l\'initialisation du paiement.',
                    'code'    => $body['code'] ?? null,
                ], 502);
            }

            $body = $response->json();
            $data = $body['data'] ?? [];
            $portalUrl = $data['portal_url'] ?? null;
            $billId    = $data['bill_id'] ?? null;

            if (!$portalUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reponse portal.php incomplete (portal_url manquant).',
                ], 502);
            }

            // Trace une intention de paiement (pending), une seule par external_reference
            Payment::firstOrCreate(
                ['transaction_id' => $externalRef],
                [
                    'order_id'                => $order->id,
                    'user_id'                 => $order->user_id,
                    'payment_method'          => 'ebilling',
                    'provider'                => 'futursowax',
                    'amount'                  => $order->total_amount,
                    'currency'                => $order->currency ?? 'XAF',
                    'status'                  => Payment::STATUS_PENDING,
                    'external_transaction_id' => $billId,
                ]
            );

            $order->update(['payment_status' => Order::PAYMENT_STATUS_PROCESSING]);

            return response()->json([
                'success'            => true,
                'portal_url'         => $portalUrl,
                'bill_id'            => $billId,
                'external_reference' => $externalRef,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Donnees invalides',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Payment init exception', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'initialisation du paiement.',
            ], 500);
        }
    }

    /**
     * C6 — Crée un e-bill (billing-easy) CÔTÉ SERVEUR pour le flux mobile.
     * La clé marchand reste dans le .env (plus jamais dans l'APK), et le montant
     * est recalculé depuis le catalogue (jamais le montant envoyé par le client).
     */
    public function createEbill(Request $request)
    {
        $validated = $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required',
            'items.*.quantity'   => 'required|integer|min:1',
            'external_reference' => 'required|string|max:100',
            'phone'              => 'required|string|max:30',
            'email'              => 'nullable|email|max:255',
            'name'               => 'nullable|string|max:255',
            'description'        => 'nullable|string|max:120',
        ]);

        $auth = config('services.ebilling.auth');
        if (empty($auth)) {
            return response()->json(['success' => false, 'message' => 'Paiement momentanément indisponible.'], 503);
        }

        $user = $request->user();

        $svc = app(\App\Services\ProductApiService::class);
        $amount = 0;
        foreach ($validated['items'] as $it) {
            $amount += $svc->authoritativeUnitPrice($it['product_id'], 0) * (int) $it['quantity'];
        }
        if ($amount <= 0) {
            return response()->json(['success' => false, 'message' => 'Montant invalide.'], 422);
        }

        $payload = [
            'payer_email'        => $validated['email'] ?? $user?->email ?? 'noreply@kardafrica.com',
            'payer_msisdn'       => $this->formatPhoneNumber($validated['phone']),
            'amount'             => (int) round($amount),
            'short_description'  => substr($validated['description'] ?? 'Commande KardAfrica', 0, 100),
            'external_reference' => $validated['external_reference'],
            'payer_name'         => $validated['name'] ?? $user?->name ?? 'Client KardAfrica',
            'expiry_period'      => 60,
            'currency'           => 'XAF',
        ];

        try {
            $response = Http::timeout(20)
                ->withHeaders(['Authorization' => $auth, 'Accept' => 'application/json'])
                ->post(config('services.ebilling.url'), $payload);
        } catch (\Throwable $e) {
            Log::warning('createEbill: exception réseau', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Erreur réseau paiement.'], 502);
        }

        if (!$response->successful()) {
            Log::warning('createEbill: billing-easy non OK', ['status' => $response->status()]);
            return response()->json(['success' => false, 'message' => 'Erreur lors de la création de la facture.'], 502);
        }

        $data   = $response->json();
        $billId = $data['e_bill']['bill_id'] ?? $data['bill_id'] ?? null;

        return response()->json([
            'success'    => true,
            'bill_id'    => $billId,
            'portal_url' => $billId ? (config('services.ebilling.portal_base') . $billId) : null,
            'amount'     => (int) round($amount),
            'e_bill'     => $data['e_bill'] ?? $data,
        ]);
    }

    /**
     * Verifier le statut d'un paiement via futursowax/check_status.php
     * Format de reponse aligne sur la doc : { is_completed, is_failed, status }
     */
    public function checkStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'external_reference' => 'required|string|max:100',
            ]);

            $response = Http::timeout(10)->withHeaders($this->browserHeaders())->get(
                config('services.payment_backend.check_url'),
                ['external_reference' => $validated['external_reference']]
            );

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la verification du statut.',
                ], 502);
            }

            $body = $response->json();
            $data = $body['data'] ?? $body;

            // Source de vérité : BillingEasy /e_bills/{bill_id}. Le portail
            // (check_status.php) peut répondre « not found » alors que le client
            // a payé ; BillingEasy fait foi.
            $billId = Payment::where('transaction_id', $validated['external_reference'])->value('external_transaction_id');
            $ebill  = $this->ebillPaid($billId, 0, $validated['external_reference']);
            $isCompleted = (bool) ($data['is_completed'] ?? false) || $ebill['paid'];

            // SÉCURITÉ (C6) : endpoint PUBLIC (polling WebView). On ne renvoie QUE
            // le statut — jamais le payload brut du fournisseur qui contient des
            // données personnelles du payeur (msisdn, email, montant). Sinon,
            // couplé à des références devinables, c'était un outil d'énumération
            // des paiements d'autrui.
            return response()->json([
                'success'      => true,
                'status'       => $isCompleted ? 'completed' : ($data['status'] ?? $ebill['state'] ?? 'unknown'),
                'is_completed' => $isCompleted,
                'is_failed'    => (bool) ($data['is_failed'] ?? false),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Donnees invalides',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Payment check exception', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la verification.',
            ], 500);
        }
    }

    /**
     * Source de vérité du paiement : BillingEasy GET /e_bills/{bill_id}.
     * Payé si state === "paid" OU amount_paid >= montant attendu (cf. doc Sowax).
     *
     * @return array{paid:bool, state:?string, amount_paid:float}
     */
    /**
     * En-têtes de navigateur exigés par futursowax (anti-bot sgcaptcha) : sans
     * eux, on reçoit du HTML au lieu du JSON sur init/portal/check_status.
     */
    private function browserHeaders(): array
    {
        $appUrl = rtrim((string) config('app.url', 'https://kardafrica.com'), '/');
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            'Origin'     => $appUrl,
            'Referer'    => $appUrl . '/',
        ];
    }

    private function ebillPaid(?string $billId, int $expectedAmount = 0, ?string $externalRef = null): array
    {
        // 1. Chemin rapide : le bill suivi en base.
        $res = $this->ebillGetPaid($billId, $expectedAmount);
        if ($res['paid']) {
            return $res + ['bill_id' => $billId];
        }

        // 2. Multi-bills : le portail crée parfois PLUSIEURS e-bills pour la même
        //    référence (un expire, le client en paie un autre). On scanne la liste
        //    BillingEasy récente pour retrouver un bill PAYÉ de cette référence.
        if ($externalRef) {
            try {
                $base = rtrim((string) config('services.ebilling.url'), '/');
                $r = Http::withHeaders(['Authorization' => (string) config('services.ebilling.auth')])
                    ->acceptJson()->timeout(15)->get($base, ['per_page' => 50]);
                if ($r->successful()) {
                    foreach (($r->json()['entries'] ?? []) as $e) {
                        if (($e['external_reference'] ?? null) !== $externalRef) {
                            continue;
                        }
                        $state = strtolower((string) ($e['state'] ?? ''));
                        $ap    = (float) ($e['amount_paid'] ?? 0);
                        if ($state === 'paid' || ($expectedAmount > 0 && $ap >= $expectedAmount)) {
                            return [
                                'paid' => true, 'state' => 'paid', 'amount_paid' => $ap,
                                'bill_id' => $e['bill_id'] ?? $billId,
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('ebillPaid: scan liste BillingEasy échoué', ['error' => $e->getMessage()]);
            }
        }

        return $res + ['bill_id' => $billId];
    }

    /** Vérifie un seul e-bill BillingEasy par son id (source de vérité). */
    private function ebillGetPaid(?string $billId, int $expectedAmount = 0): array
    {
        if (empty($billId)) {
            return ['paid' => false, 'state' => null, 'amount_paid' => 0.0];
        }
        try {
            $url = rtrim((string) config('services.ebilling.url'), '/') . '/' . $billId;
            $r = Http::withHeaders(['Authorization' => (string) config('services.ebilling.auth')])
                ->acceptJson()->timeout(12)->get($url);
            if (!$r->successful()) {
                return ['paid' => false, 'state' => null, 'amount_paid' => 0.0];
            }
            $j = $r->json();
            $d = is_array($j) ? ($j['e_bill'] ?? $j['data'] ?? $j) : [];
            $state      = strtolower((string) ($d['state'] ?? ''));
            $amountPaid = (float) ($d['amount_paid'] ?? 0);
            $paid = $state === 'paid' || ($expectedAmount > 0 && $amountPaid >= $expectedAmount);
            return ['paid' => $paid, 'state' => $state ?: null, 'amount_paid' => $amountPaid];
        } catch (\Throwable $e) {
            Log::warning('ebillGetPaid: exception BillingEasy', ['error' => $e->getMessage()]);
            return ['paid' => false, 'state' => null, 'amount_paid' => 0.0];
        }
    }

    /**
     * Retour apres paiement (Redirect from E-Billing)
     */
    public function handleReturn(Request $request)
    {
        $externalRef = $request->query('ref');

        if (!$externalRef) {
            return redirect()->route('cart.index')->with('error', 'Reference de paiement manquante.');
        }

        // Récupère la commande pour alimenter l'événement Meta Pixel "Purchase"
        // (tiré côté client une fois le paiement confirmé — voir payment/verify).
        $order = \App\Models\Order::where('external_reference', $externalRef)->first();

        return view('payment.verify', [
            'ref'              => $externalRef,
            'purchaseValue'    => $order ? (int) round($order->total_amount) : null,
            'purchaseCurrency' => 'XAF',
            'purchaseOrderId'  => $order?->id,
        ]);
    }

    /**
     * Finaliser le paiement d'une commande EXISTANTE (créée par CheckoutController::start
     * côté web, ou POST /api/orders + /api/payment/init côté mobile).
     *
     * H0 : cette méthode ne crée JAMAIS de commande ni de Payment — l'ancienne
     * version recréait les deux et violait l'index unique payments.transaction_id
     * (le Payment existait déjà depuis start/init) → rollback → client débité
     * sans commande finalisée, sur le chemin nominal.
     *
     * Idempotente : rappeler finalize sur une commande déjà payée renvoie le même
     * résultat (la page verify et le mobile pollent cet endpoint en boucle).
     */
    public function finalize(Request $request)
    {
        $externalRef = $request->input('ref') ?? $request->input('external_reference');

        if (!$externalRef) {
            return response()->json(['success' => false, 'message' => 'Reference manquante.'], 400);
        }

        // SÉCURITÉ (C0) : la référence est réutilisée dans des requêtes SQL et
        // identifie une commande. On impose un format strict — aucun métacaractère
        // LIKE (% _) possible. Format réel des refs : KARD_<timestamp>_<rand>.
        if (!preg_match('/^[A-Za-z0-9_\-]{1,64}$/', (string) $externalRef)) {
            return response()->json(['success' => false, 'message' => 'Référence invalide.'], 422);
        }

        $userId = Auth::id();

        // SÉCURITÉ (C0 + C3) : la référence doit appartenir à une commande de
        // l'utilisateur authentifié. Une référence payée par un autre client ne
        // permet plus de finaliser quoi que ce soit à son profit — on répond 404
        // générique sans révéler l'existence de la référence. Le `orWhere notes`
        // couvre les commandes historiques du flux legacy (qui stockait la
        // référence dans notes au lieu d'external_reference).
        $order = Order::where('user_id', $userId)
            ->where(function ($q) use ($externalRef) {
                $q->where('external_reference', $externalRef)
                  ->orWhere('notes', 'Ref: ' . $externalRef);
            })
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Commande introuvable pour cette référence.',
            ], 404);
        }

        // Idempotence : déjà finalisée → même réponse que le premier succès.
        if ($order->payment_status === Order::PAYMENT_STATUS_COMPLETED) {
            return $this->finalizedResponse($order);
        }

        try {
            // 1. Vérifier le statut chez le PSP (hors transaction — appel réseau).
            //    Normalisation d'enveloppe : certains endpoints renvoient le statut
            //    à la racine, d'autres sous `data` (cf. checkStatus / Api checkout).
            $status = 'pending';
            $isCompleted = false;

            // SOURCE DE VÉRITÉ (doc) : BillingEasy /e_bills/{bill_id}. C'est le
            // seul endroit fiable : futursowax check_status lit le MySQL du
            // portail, qui ne connaît pas la transaction si init.php n'a pas
            // tourné → « not found » alors que le client a bien payé.
            $billId = Payment::where('transaction_id', $externalRef)->value('external_transaction_id');
            $ebill  = $this->ebillPaid($billId, (int) round((float) $order->total_amount), $externalRef);
            if ($ebill['paid']) {
                $isCompleted = true;
                $status = 'paid';
                // Multi-bills : si le client a payé un AUTRE bill de la même
                // référence, on corrige le bill suivi (traçabilité + réconciliation).
                if (!empty($ebill['bill_id']) && $ebill['bill_id'] !== $billId) {
                    Payment::where('transaction_id', $externalRef)
                        ->update(['external_transaction_id' => $ebill['bill_id']]);
                }
            } else {
                // Repli : check_status futursowax (MySQL portail).
                $responseCheck = Http::timeout(10)->withHeaders($this->browserHeaders())->get(config('services.payment_backend.check_url'), [
                    'external_reference' => $externalRef,
                ]);
                if ($responseCheck->successful()) {
                    $body = $responseCheck->json();
                    $data = is_array($body) ? ($body['data'] ?? $body) : [];
                    $status = $data['status'] ?? ($ebill['state'] ?? 'pending');
                    $isCompleted = in_array($status, ['completed', 'success'], true)
                        || (bool) ($data['is_completed'] ?? false);
                } else {
                    $status = $ebill['state'] ?? 'pending';
                }
            }

            if (!$isCompleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le paiement n\'a pas ete confirme (Statut: ' . $status . '). Veuillez reessayer.'
                ]);
            }

            // 2. C3 — réconciliation : la facture E-Billing a été créée à l'init
            //    avec le montant serveur de la commande (Payment.amount). Si la
            //    commande dépasse ce montant facturé, on refuse la livraison.
            $invoiced = Payment::where('transaction_id', $externalRef)->value('amount');
            if ($invoiced !== null
                && (int) round((float) $order->total_amount) > (int) round((float) $invoiced)) {
                Log::warning('C3 total commande supérieur au montant facturé — livraison refusée', [
                    'ref'      => $externalRef,
                    'total'    => $order->total_amount,
                    'invoiced' => $invoiced,
                    'user_id'  => $userId,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Montant de la commande incohérent avec le paiement. Contacte le support avec ta référence.',
                ], 422);
            }

            // 3. Transaction courte : verrou + re-test du statut À L'INTÉRIEUR
            //    (C9 : la page verify polle en AJAX — deux réponses simultanées
            //    ne doivent produire qu'une seule finalisation/livraison).
            $didFinalize = DB::transaction(function () use ($order, $externalRef) {
                $fresh = Order::whereKey($order->id)->lockForUpdate()->first();
                if ($fresh->payment_status === Order::PAYMENT_STATUS_COMPLETED) {
                    return false; // une requête concurrente a déjà finalisé
                }

                $fresh->update([
                    'payment_status' => Order::PAYMENT_STATUS_COMPLETED,
                    'status'         => Order::STATUS_PROCESSING,
                ]);

                $payment = Payment::where('transaction_id', $externalRef)->lockForUpdate()->first();
                if ($payment) {
                    $payment->update([
                        'order_id'     => $fresh->id,
                        'status'       => Payment::STATUS_COMPLETED,
                        'processed_at' => now(),
                    ]);
                } else {
                    // Cas limite (commande legacy sans intention de paiement tracée)
                    Payment::create([
                        'transaction_id'          => $externalRef,
                        'order_id'                => $fresh->id,
                        'user_id'                 => $fresh->user_id,
                        'payment_method'          => 'ebilling',
                        'provider'                => 'futursowax',
                        'amount'                  => $fresh->total_amount,
                        'currency'                => $fresh->currency ?? 'XAF',
                        'status'                  => Payment::STATUS_COMPLETED,
                        'external_transaction_id' => $externalRef,
                        'processed_at'            => now(),
                    ]);
                }

                return true;
            });

            if (!$didFinalize) {
                // Course perdue proprement : l'autre requête a livré, on renvoie l'état.
                return $this->finalizedResponse($order->fresh());
            }

            $order = $order->fresh()->load('orderItems');

            // 4a. Items marchand (Carte Gabon) : génération LOCALE synchrone pour
            //     ne pas dépendre du queue worker. createPurchaseForOrderItem est
            //     idempotent par order_item_id.
            $merchantItems = $order->orderItems->filter(
                fn ($i) => \App\Support\MerchantCardCode::isMerchantOrderItem($i)
            );
            foreach ($merchantItems as $item) {
                try {
                    \App\Support\MerchantCardCode::createPurchaseForOrderItem($order, $item);
                } catch (\Throwable $e) {
                    Log::error('PaymentController finalize: échec MerchantCardPurchase', [
                        'order_id'      => $order->id,
                        'order_item_id' => $item->id,
                        'product_id'    => $item->product_id,
                        'error'         => $e->getMessage(),
                    ]);
                }
            }

            // 4b. Items afrikard : dispatch du job async (une seule fois — garanti
            //     par le flip payment_status sous verrou ci-dessus).
            $afrikardItems = $order->orderItems->reject(
                fn ($i) => \App\Support\MerchantCardCode::isMerchantOrderItem($i)
            );
            if ($afrikardItems->isNotEmpty()) {
                ProcessCheckoutJob::dispatch($order);
                Log::info('Checkout job dispatched (afrikard items present)', ['order_id' => $order->id]);
            } else {
                $order->update([
                    'status'       => Order::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);
                Log::info('Order 100% marchand complétée inline', ['order_id' => $order->id]);
            }

            // 5. Vider le panier serveur (le flux web start() ne le vide pas à
            //    l'init pour permettre de réessayer un paiement abandonné).
            ShoppingCart::where('user_id', $userId)->delete();

            return $this->finalizedResponse($order->fresh());

        } catch (\Exception $e) {
            Log::error('Payment Finalize Error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la finalisation du paiement.'
            ], 500);
        }
    }

    /**
     * Réponse standard (et idempotente) d'une commande finalisée : commande + cartes.
     * makeVisible est légitime ici : la commande appartient à l'utilisateur
     * authentifié (contrôle de propriété fait en amont dans finalize).
     */
    private function finalizedResponse(Order $order)
    {
        $order->load('orderItems');
        $userCards = UserCard::where('order_id', $order->id)->get();

        return response()->json([
            'success'      => true,
            'message'      => 'Commande finalisée. Vos cartes seront disponibles dans quelques instants.',
            'redirect_url' => route('orders.show', $order),
            'order_id'     => $order->id,
            'order'        => $order,
            'cards'        => $userCards->makeVisible(['card_code', 'pin']),
        ]);
    }

    /**
     * Webhook Callback (Server to Server)
     */
    public function handleCallback(Request $request)
    {
        // SÉCURITÉ (C4) : endpoint public NON AUTHENTIFIÉ. Il est volontairement
        // INERTE — il ne modifie AUCUN état (statut de paiement, commande, carte).
        // ⚠️ NE JAMAIS lui faire confiance sans vérifier la signature HMAC du
        // fournisseur au préalable (avec idempotence sur external_reference).
        // La source de vérité du paiement reste la vérification serveur active
        // (check_status) dans finalize(). On ne journalise pas le corps brut.
        Log::info('Payment Webhook reçu (inerte)', [
            'ip'  => $request->ip(),
            'ref' => $request->input('external_reference') ?? $request->input('ref'),
        ]);
        return response()->json(['success' => true]);
    }
}
