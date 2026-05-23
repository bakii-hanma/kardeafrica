<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessCheckoutJob;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Reseller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashOrderController extends Controller
{
    /**
     * Liste des commandes cash en attente d'encaissement pour le vendeur connecté.
     */
    public function index()
    {
        $reseller = Auth::guard('vendor')->user();

        $pending = Order::with(['orderItems', 'user'])
            ->where('cash_reseller_id', $reseller->id)
            ->where('payment_method', Order::PAYMENT_METHOD_CASH_RESELLER)
            ->where('payment_status', Order::PAYMENT_STATUS_PENDING)
            ->orderBy('cash_lock_expires_at')
            ->get();

        $recent = Order::with('orderItems')
            ->where('cash_reseller_id', $reseller->id)
            ->where('payment_method', Order::PAYMENT_METHOD_CASH_RESELLER)
            ->whereIn('payment_status', [
                Order::PAYMENT_STATUS_COMPLETED,
                Order::PAYMENT_STATUS_CANCELLED,
                Order::PAYMENT_STATUS_FAILED,
            ])
            ->latest()
            ->take(10)
            ->get();

        return view('vendor.cash.index', [
            'reseller' => $reseller,
            'pending'  => $pending,
            'recent'   => $recent,
        ]);
    }

    /**
     * Détail d'une commande cash (avant ou après encaissement).
     */
    public function show(Order $order)
    {
        $reseller = Auth::guard('vendor')->user();
        if ($order->cash_reseller_id !== $reseller->id) {
            abort(404);
        }

        $order->load(['orderItems', 'user']);

        return view('vendor.cash.show', [
            'reseller' => $reseller,
            'order'    => $order,
        ]);
    }

    /**
     * Confirme l'encaissement cash :
     * 1. Vérifie code de confirmation client
     * 2. Vérifie lock pas expiré
     * 3. Atomique : libère le lock, débite le wallet, crédite la commission
     * 4. Marque l'Order comme payée + crée Payment
     * 5. Dispatch ProcessCheckoutJob qui livrera les cartes (afrikard)
     */
    public function confirm(Request $request, Order $order)
    {
        $reseller = Auth::guard('vendor')->user();
        if ($order->cash_reseller_id !== $reseller->id) {
            abort(404);
        }

        if ($order->payment_status !== Order::PAYMENT_STATUS_PENDING) {
            return back()->with('error', 'Cette commande n\'est plus en attente.');
        }

        if ($order->cash_lock_expires_at && $order->cash_lock_expires_at->isPast()) {
            return back()->with('error', 'Le délai de cette commande est expiré. Le client doit en repasser une.');
        }

        $validated = $request->validate([
            'confirmation_code' => 'required|string|size:6',
        ]);

        if (trim($validated['confirmation_code']) !== (string) $order->cash_confirmation_code) {
            return back()->with('error', 'Code de confirmation invalide.');
        }

        try {
            DB::transaction(function () use ($order, $reseller) {
                $subtotal   = (float) $order->subtotal;
                $rate       = (float) $reseller->commission_rate;
                $commission = round($subtotal * ($rate / 100), 2);

                // Débite le wallet (et libère la portion bloquée correspondante)
                $reseller->debitLocked($subtotal, "Encaissement cash #{$order->order_number}", $order->order_number);
                // Verse la commission sur le portefeuille dédié
                $reseller->commission($commission, "Commission cash #{$order->order_number}", $order->order_number);
                // Cash physique reçu du client : à reverser à KardAfrica via E-Billing
                $reseller->recordCashCollection($subtotal, $order->order_number);
                $reseller->increment('total_volume', $subtotal);

                $order->update([
                    'payment_status' => Order::PAYMENT_STATUS_COMPLETED,
                    'status'         => Order::STATUS_PROCESSING,
                ]);

                Payment::firstOrCreate(
                    ['transaction_id' => $order->external_reference],
                    [
                        'order_id'                => $order->id,
                        'user_id'                 => $order->user_id,
                        'payment_method'          => 'cash_at_reseller',
                        'provider'                => 'reseller',
                        'amount'                  => $order->total_amount,
                        'currency'                => $order->currency,
                        'status'                  => Payment::STATUS_COMPLETED,
                        'external_transaction_id' => $reseller->vendor_code,
                        'processed_at'            => now(),
                    ]
                );
            });

            // Livraison cartes en async (mêmes appels afrikard que la voie E-Billing)
            ProcessCheckoutJob::dispatch($order->fresh());

            return redirect()
                ->route('vendor.cash.show', $order)
                ->with('success', 'Encaissement confirmé — les cartes sont en cours de livraison au client.');
        } catch (\Throwable $e) {
            Log::error('Cash order confirm failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Le vendeur peut refuser une commande cash (client absent, suspecte...).
     * Libère immédiatement le lock et marque la commande cancelled.
     */
    public function reject(Request $request, Order $order)
    {
        $reseller = Auth::guard('vendor')->user();
        if ($order->cash_reseller_id !== $reseller->id) {
            abort(404);
        }
        if ($order->payment_status !== Order::PAYMENT_STATUS_PENDING) {
            return back()->with('error', 'Cette commande n\'est plus en attente.');
        }

        try {
            DB::transaction(function () use ($order, $reseller) {
                $reseller->releaseFunds((float) $order->subtotal, $order->order_number);
                $order->update([
                    'payment_status' => Order::PAYMENT_STATUS_CANCELLED,
                    'status'         => Order::STATUS_CANCELLED,
                    'notes'          => 'Refusée par le vendeur',
                ]);
            });
            return redirect()->route('vendor.cash.index')->with('success', 'Commande refusée — fonds libérés.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }
}
