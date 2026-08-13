<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['user', 'order'])->latest();

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtre par provider
        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }

        // Recherche par transaction_id
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhere('external_transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
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

        $payments = $query->paginate(20)->withQueryString();

        // Statistiques globales
        $stats = [
            'total'      => Payment::count(),
            'completed'  => Payment::where('status', Payment::STATUS_COMPLETED)->count(),
            'pending'    => Payment::whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PROCESSING])->count(),
            'failed'     => Payment::whereIn('status', [Payment::STATUS_FAILED, Payment::STATUS_CANCELLED])->count(),
            'revenue'    => (float) Payment::where('status', Payment::STATUS_COMPLETED)->sum('amount'),
        ];

        // Compteurs par statut RÉEL, une requête groupée. `$stats` regroupe
        // pending+processing et failed+cancelled : utile en synthèse, faux pour
        // des onglets qui doivent refléter exactement la liste filtrée.
        $statusCounts = Payment::query()
            ->select('status', \DB::raw('COUNT(*) as n'))
            ->groupBy('status')
            ->pluck('n', 'status');

        return view('admin.payments.index', compact('payments', 'stats', 'statusCounts'));
    }
}
