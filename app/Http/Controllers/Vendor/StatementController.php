<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\ResellerOrder;
use App\Models\ResellerWalletTransaction;
use App\Support\CsvExport;
use App\Support\VendorSalesFeed;
use Illuminate\Http\Request;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Relevés du vendeur : historique complet des mouvements + exports comptables.
 *
 * Deux manques de l'audit sont traités ici :
 *  — aucun export n'existait nulle part dans l'espace vendeur ; un revendeur
 *    ne pouvait sortir aucun relevé pour sa comptabilité ;
 *  — tous les historiques étaient tronqués en dur (8 mouvements au profil,
 *    20 remises, 20 recharges) sans page complète ni pagination.
 */
class StatementController extends Controller
{
    /** Libellés des types de mouvement, pour l'écran et pour l'export. */
    public const TYPE_LABELS = [
        'credit'       => 'Recharge',
        'debit'        => 'Achat de carte',
        'refund'       => 'Remboursement restitué',
        'commission'   => 'Commission',
        'adjustment'   => 'Ajustement',
        'lock'         => 'Fonds réservés',
        'unlock'       => 'Fonds libérés',
        'transfer_in'  => 'Transfert reçu',
        'transfer_out' => 'Transfert envoyé',
        'cash_collected'  => 'Cash encaissé',
        'cash_remittance' => 'Cash reversé',
    ];

    /** Historique complet des mouvements, paginé et filtrable. */
    public function transactions(Request $request)
    {
        /** @var \App\Models\Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();

        $query = $this->transactionsQuery($reseller, $request);

        return view('vendor.statement.transactions', [
            'reseller'     => $reseller,
            'transactions' => $query->paginate(30)->withQueryString(),
            'filters'      => $this->filters($request),
            'typeLabels'   => self::TYPE_LABELS,
        ]);
    }

    /** Export CSV des mouvements de portefeuille (mêmes filtres que l'écran). */
    public function exportTransactions(Request $request)
    {
        /** @var \App\Models\Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();

        $rows = CsvExport::lazy($this->transactionsQuery($reseller, $request)->reorder('id'))
            ->map(fn (ResellerWalletTransaction $t) => [
                $t->created_at?->format('d/m/Y H:i'),
                $t->wallet === 'commission' ? 'Commissions' : 'Vente',
                self::TYPE_LABELS[$t->type] ?? $t->type,
                (float) $t->amount,
                (float) $t->balance_before,
                (float) $t->balance_after,
                $t->description,
                $t->reference,
            ]);

        return CsvExport::stream(
            CsvExport::filename('kardafrica-mouvements-' . strtolower($reseller->vendor_code)),
            ['Date', 'Portefeuille', 'Type', 'Montant (FCFA)', 'Solde avant', 'Solde après', 'Libellé', 'Référence'],
            $rows
        );
    }

    /** Export CSV des ventes, avec les mêmes filtres que « Mes ventes ». */
    public function exportOrders(Request $request)
    {
        /** @var \App\Models\Reseller $reseller */
        $reseller = Auth::guard('vendor')->user();

        // Même flux que l'écran « Mes ventes » : l'export reprend exactement ce
        // que le vendeur a sous les yeux, filtre de nature compris. Deux exports
        // séparés obligeaient à recoller les fichiers pour boucler une caisse.
        $rows = LazyCollection::make(function () use ($request, $reseller) {
            foreach (app(VendorSalesFeed::class)->lazyRows($request, $reseller) as $r) {
                yield [
                    $r['date']?->format('d/m/Y H:i'),
                    $r['type_label'],
                    $r['reference'],
                    $r['customer'] ?: 'Client',
                    $r['phone'],
                    $r['items'],
                    $r['amount'],
                    // Commission laissée à 0 tant que la vente n'est pas acquise :
                    // sinon la comptabilité reprendrait des montants jamais gagnés.
                    $r['commission'] ?? 0.0,
                    $r['commission_kind'] === 'cash' ? 'Espèces gardées' : 'Portefeuille',
                    $r['status_label'],
                    $r['payment'],
                ];
            }
        });

        return CsvExport::stream(
            CsvExport::filename('kardafrica-ventes-' . strtolower($reseller->vendor_code)),
            ['Date', 'Nature', 'Référence', 'Client', 'Téléphone', 'Détail', 'Montant (FCFA)',
             'Commission acquise (FCFA)', 'Nature commission', 'Statut', 'Paiement'],
            $rows
        );
    }

    // ------------------------------------------------------------------

    private function transactionsQuery(\App\Models\Reseller $reseller, Request $request)
    {
        $query = ResellerWalletTransaction::where('reseller_id', $reseller->id)->latest('id');

        if ($request->filled('wallet') && in_array($request->string('wallet')->toString(), ['sales', 'commission'], true)) {
            $query->where('wallet', $request->string('wallet'));
        }
        if ($request->filled('type') && isset(self::TYPE_LABELS[$request->string('type')->toString()])) {
            $query->where('type', $request->string('type'));
        }
        $this->applyDateRange($query, $request, 'created_at');

        return $query;
    }

    /** Applique une période [du, au] si elle est fournie et valide. */
    private function applyDateRange($query, Request $request, string $column): void
    {
        foreach ([['from', '>='], ['to', '<=']] as [$key, $operator]) {
            if (!$request->filled($key)) continue;
            try {
                $date = Carbon::parse($request->string($key));
            } catch (\Throwable) {
                continue;   // date illisible : on ignore plutôt que de planter
            }
            $query->where($column, $operator, $key === 'from' ? $date->startOfDay() : $date->endOfDay());
        }
    }

    /** @return array{from:?string,to:?string,wallet:?string,type:?string} */
    private function filters(Request $request): array
    {
        return [
            'from'   => $request->string('from')->toString() ?: null,
            'to'     => $request->string('to')->toString() ?: null,
            'wallet' => $request->string('wallet')->toString() ?: null,
            'type'   => $request->string('type')->toString() ?: null,
        ];
    }
}
