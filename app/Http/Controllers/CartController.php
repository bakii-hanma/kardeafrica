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

        // C1 — DÉTECTION (Palier 1, sans blocage) : compare le prix envoyé par le
        // client au prix serveur faisant autorité (Money::toFcfa). On ne rejette
        // rien encore ; on journalise les écarts pour valider avant enforcement.
        try {
            $authPrice = app(\App\Services\ProductApiService::class)
                ->authoritativePriceFcfa($request->product_id);
            if ($authPrice !== null && (int) round((float) $request->price) !== $authPrice) {
                \Illuminate\Support\Facades\Log::warning('C1 price mismatch (cart:add)', [
                    'product_id'    => $request->product_id,
                    'client_price'  => $request->price,
                    'authoritative' => $authPrice,
                    'user_id'       => Auth::id(),
                ]);
            }
        } catch (\Throwable $e) {
            // détection strictement non bloquante — ne jamais interrompre l'ajout
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
