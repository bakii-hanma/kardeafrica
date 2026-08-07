<?php

namespace App\Http\Controllers;

use App\Models\ShoppingCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cartItems = $this->getCartItems();
        
        $total = $cartItems->sum(function($item) {
            return $item->price * $item->quantity;
        });

        return response()->json([
            'items' => $cartItems,
            'total' => $total,
            'count' => $cartItems->sum('quantity')
        ]);
    }

    /**
     * Add an item to the cart.
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required',
            'name' => 'required',
            'price' => 'required|numeric',
            'quantity' => 'nullable|integer|min:1'
        ]);

        // C1 — ENFORCEMENT (Palier 2) : le prix payé ne doit JAMAIS venir du
        // client. On le remplace par le prix serveur faisant autorité
        // (Money::toFcfa depuis le catalogue). Pour un produit résolvable, tout
        // prix client est écrasé (une commande légitime a déjà le bon prix : no-op).
        // Les produits non résolvables (merchant_*/daywatch_*/id suffixé) gardent
        // leur prix — traités à leur propre palier.
        try {
            $authPrice = app(\App\Services\ProductApiService::class)
                ->authoritativePriceFcfa($request->product_id);
            if ($authPrice !== null) {
                if ((int) round((float) $request->price) !== $authPrice) {
                    \Illuminate\Support\Facades\Log::warning('C1 price corrected (cart:add)', [
                        'product_id'    => $request->product_id,
                        'client_price'  => $request->price,
                        'authoritative' => $authPrice,
                        'user_id'       => Auth::id(),
                    ]);
                }
                $request->merge(['price' => $authPrice]);
            }
        } catch (\Throwable $e) {
            // ne jamais casser l'ajout au panier sur une erreur de résolution
        }

        // C2 — cartes marchand : le prix = le montant encodé (valeur stockée 1:1),
        // et le montant doit être ACHETABLE pour la carte (denomination ou plage
        // libre, carte active). On rejette un montant invalide et on force le prix
        // au montant validé (empêche "merchant_5_500000 à 1 FCFA").
        if (str_starts_with((string) $request->product_id, 'merchant_')) {
            $amount = \App\Support\MerchantCardCode::authoritativeAmount((string) $request->product_id);
            if ($amount === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Montant de carte invalide pour ce marchand.',
                ], 422);
            }
            if ((int) round((float) $request->price) !== $amount) {
                \Illuminate\Support\Facades\Log::warning('C2 merchant price corrected (cart:add)', [
                    'product_id'   => $request->product_id,
                    'client_price' => $request->price,
                    'amount'       => $amount,
                    'user_id'      => Auth::id(),
                ]);
            }
            $request->merge(['price' => $amount]);
        }

        $userId = Auth::id();
        $sessionId = Session::getId();
        
        $quantity = $request->input('quantity', 1);

        // Check if item already exists
        $query = ShoppingCart::where('product_id', $request->product_id);
        
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId);
        }

        $cartItem = $query->first();

        if ($cartItem) {
            $cartItem->quantity += $quantity;
            $cartItem->save();
        } else {
            ShoppingCart::create([
                'user_id' => $userId,
                'session_id' => $userId ? null : $sessionId,
                'product_id' => $request->product_id,
                'name' => $request->name,
                'price' => $request->price,
                'image_url' => $request->image_url,
                'quantity' => $quantity
            ]);
        }

        return $this->index();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $cartItem = ShoppingCart::find($id);

        if (!$cartItem) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        // Verify ownership
        if (Auth::check()) {
            if ($cartItem->user_id !== Auth::id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } else {
            if ($cartItem->session_id !== Session::getId()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $cartItem->quantity = $request->quantity;
        $cartItem->save();

        return $this->index();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function remove($id)
    {
        $cartItem = ShoppingCart::find($id);

        if (!$cartItem) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        // Verify ownership
        if (Auth::check()) {
            if ($cartItem->user_id !== Auth::id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        } else {
            if ($cartItem->session_id !== Session::getId()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $cartItem->delete();

        return $this->index();
    }

    /**
     * Clear the cart.
     */
    public function clear()
    {
        $userId = Auth::id();
        $sessionId = Session::getId();

        if ($userId) {
            ShoppingCart::where('user_id', $userId)->delete();
        } else {
            ShoppingCart::where('session_id', $sessionId)->delete();
        }

        return $this->index();
    }

    /**
     * Helper to get cart items based on auth status.
     */
    private function getCartItems()
    {
        if (Auth::check()) {
            return ShoppingCart::where('user_id', Auth::id())->get();
        } else {
            return ShoppingCart::where('session_id', Session::getId())->get();
        }
    }
}
