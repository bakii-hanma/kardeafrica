<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reseller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Throwable;

class ResellerController extends Controller
{
    public function index(Request $request)
    {
        $query = Reseller::query()->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('vendor_code', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $resellers = $query->paginate(20)->withQueryString();

        $stats = [
            'total'       => Reseller::count(),
            'active'      => Reseller::where('is_active', true)->count(),
            'wallet_sum'  => (float) Reseller::sum('wallet_balance'),
            'commission_sum' => (float) Reseller::sum('total_commission_earned'),
        ];

        return view('admin.resellers.index', compact('resellers', 'stats'));
    }

    public function create()
    {
        return view('admin.resellers.create', [
            'reseller'        => new Reseller(['commission_rate' => 3, 'max_wallet' => 150000, 'is_active' => true]),
            'suggestedCode'   => Reseller::generateVendorCode(),
            'suggestedPass'   => 'KA-' . strtoupper(\Str::random(6)),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:120',
            'phone'            => 'nullable|string|max:20',
            'email'            => 'nullable|email|max:120|unique:resellers,email',
            'vendor_code'      => 'required|string|max:20|unique:resellers,vendor_code',
            'password'         => 'required|string|min:6',
            'max_wallet'       => 'required|integer|min:1000|max:150000',
            'initial_credit'   => 'nullable|integer|min:0|max:150000|lte:max_wallet',
            'commission_rate'  => 'required|numeric|min:0|max:50',
            'is_active'        => 'sometimes|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $initialCredit = (int) ($data['initial_credit'] ?? 0);
        unset($data['initial_credit']);

        $reseller = Reseller::create($data);

        if ($initialCredit > 0) {
            try {
                $reseller->credit(
                    (float) $initialCredit,
                    Auth::id(),
                    'Crédit initial à la création',
                    'INIT-' . $reseller->vendor_code
                );
            } catch (Throwable $e) {
                return redirect()
                    ->route('admin.resellers.show', $reseller)
                    ->with('error', 'Vendeur créé, mais crédit initial échoué : ' . $e->getMessage());
            }
        }

        $msg = "Vendeur « {$reseller->name} » créé. Code : {$reseller->vendor_code}";
        if ($initialCredit > 0) {
            $msg .= ' · Solde initial : ' . number_format($initialCredit, 0, ',', ' ') . ' FCFA';
        }

        return redirect()
            ->route('admin.resellers.show', $reseller)
            ->with('success', $msg);
    }

    public function show(Reseller $reseller)
    {
        $reseller->load(['walletTransactions' => fn($q) => $q->take(50), 'walletTransactions.admin']);
        $recentOrders = $reseller->orders()->latest()->take(10)->get();

        $stats = [
            'orders_count'    => $reseller->orders()->count(),
            'orders_completed'=> $reseller->orders()->where('status', 'completed')->count(),
            'total_volume'    => (float) $reseller->total_volume,
        ];

        return view('admin.resellers.show', compact('reseller', 'recentOrders', 'stats'));
    }

    public function credit(Request $request, Reseller $reseller)
    {
        $request->validate([
            'amount'      => 'required|integer|min:1000|max:150000',
            'description' => 'nullable|string|max:200',
        ]);

        try {
            $reseller->credit(
                (float) $request->amount,
                Auth::id(),
                $request->description ?: 'Recharge admin'
            );
            return back()->with('success', 'Portefeuille crédité de ' . number_format($request->amount, 0, ',', ' ') . ' FCFA.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Met à jour les réglages financiers : commission_rate, max_wallet.
     */
    public function updateSettings(Request $request, Reseller $reseller)
    {
        $data = $request->validate([
            'commission_rate' => 'required|numeric|min:0|max:50',
            'max_wallet'      => 'required|integer|min:1000|max:150000',
        ]);

        if ((float) $data['max_wallet'] < (float) $reseller->wallet_balance) {
            return back()->with('error', "Plafond impossible : le solde actuel est de " . number_format($reseller->wallet_balance, 0, ',', ' ') . " FCFA.");
        }

        $reseller->update($data);

        return back()->with('success', "Réglages mis à jour : commission " . rtrim(rtrim(number_format($data['commission_rate'], 2), '0'), '.') . "% · plafond " . number_format($data['max_wallet'], 0, ',', ' ') . " FCFA.");
    }

    public function toggle(Reseller $reseller)
    {
        $reseller->update(['is_active' => !$reseller->is_active]);
        $status = $reseller->is_active ? 'activé' : 'désactivé';
        return back()->with('success', "Vendeur {$reseller->name} {$status}.");
    }

    /**
     * Réinitialise le mot de passe d'un vendeur (admin only).
     */
    public function resetPassword(Request $request, Reseller $reseller)
    {
        $request->validate(['password' => 'required|string|min:6']);
        $reseller->update(['password' => $request->password]); // cast 'hashed' = auto-hash
        return back()->with('success', "Mot de passe réinitialisé pour {$reseller->name}.");
    }
}
