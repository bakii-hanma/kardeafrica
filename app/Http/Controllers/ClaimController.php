<?php

namespace App\Http\Controllers;

use App\Models\ResellerOrder;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    /**
     * Page publique de récupération des cartes via QR code (token UUID).
     */
    public function show(string $token)
    {
        $order = ResellerOrder::where('claim_token', $token)
            ->with(['items', 'cards', 'reseller:id,name,vendor_code'])
            ->firstOrFail();

        if ($order->expires_at && $order->expires_at->isPast()) {
            return view('claim.expired', ['order' => $order]);
        }

        // Marque la première récupération
        if (!$order->claimed_at) {
            $order->update(['claimed_at' => now()]);
        }

        return view('claim.show', compact('order'));
    }

    /**
     * Version "imprimable" / PDF — même contenu en plein écran sans navigation.
     */
    public function print(string $token)
    {
        $order = ResellerOrder::where('claim_token', $token)
            ->with(['items', 'cards'])
            ->firstOrFail();

        if ($order->expires_at && $order->expires_at->isPast()) {
            abort(410, 'Ce lien a expiré.');
        }

        return view('claim.print', compact('order'));
    }
}
