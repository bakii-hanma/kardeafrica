<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Models\UserProfile;

class ProfileController extends Controller
{
    public function show()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Stats compactes pour l'en-tête + le résumé
        $stats = [
            'total_cards'    => $user->userCards()->count(),
            'active_cards'   => $user->userCards()->where('status', 'active')->count(),
            'total_orders'   => $user->orders()->count(),
            'completed_orders' => $user->orders()->where('status', 'completed')->count(),
            'total_spent'    => (float) $user->orders()
                ->where('payment_status', 'completed')
                ->sum('total_amount'),
            'days_since'     => (int) $user->created_at->diffInDays(now()),
        ];

        // Activité récente (3 dernières commandes + 3 dernières cartes)
        $recentOrders = $user->orders()
            ->with('orderItems')
            ->latest()
            ->take(3)
            ->get();

        $recentCards = $user->userCards()
            ->with('orderItem')
            ->latest()
            ->take(3)
            ->get();

        return view('profile.show', compact('user', 'stats', 'recentOrders', 'recentCards'));
    }

    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return redirect()->route('profile.show')->with('success', 'Profil mis à jour avec succès.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Mot de passe modifié avec succès.');
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $fullUrl = asset('storage/' . $path);

            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => explode(' ', $user->name)[0],
                    'last_name' => explode(' ', $user->name)[1] ?? '',
                    'avatar' => $fullUrl
                ]
            );

            return back()->with('success', 'Photo de profil mise à jour avec succès.');
        }

        return back()->with('error', 'Aucune image sélectionnée.');
    }
}
