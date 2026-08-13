@extends('admin.layouts.admin')

@section('title', 'Paiements')
@section('page-title', 'Paiements')

@section('content')
@php
    use App\Models\Payment;
    use App\Support\AdminStatus;

    /* Onglets sur les statuts RÉELS de Payment, dans l'ordre du cycle de vie. */
    $statuts = ['pending', 'processing', 'completed', 'failed', 'refunded'];

    $courant = request('status', '');
    $base    = request()->except(['status', 'page']);

    $onglets = collect([['key' => '', 'label' => 'Tous', 'count' => (int) $statusCounts->sum()]])
        ->concat(collect($statuts)->map(fn ($s) => [
            'key'   => $s,
            'label' => AdminStatus::label($s),
            'count' => (int) ($statusCounts[$s] ?? 0),
        ]))
        ->map(fn ($t) => $t + [
            'url'    => route('admin.payments.index', $t['key'] === '' ? $base : $base + ['status' => $t['key']]),
            'active' => $courant === $t['key'],
        ])->all();

    $filtre = request()->hasAny(['status', 'provider', 'search', 'date_from', 'date_to']);
@endphp

<x-admin.list-screen
    title="Paiements"
    :total="$payments->total()"
    :tabs="$onglets"
    :search="request('search')"
    placeholder="N° de transaction, client…"
    :filtered="$filtre"
    :reset-url="route('admin.payments.index')">

    @if ($payments->isEmpty())
        <x-ui.empty-state :label="request('search')
            ? 'Aucun résultat pour « ' . request('search') . ' ».'
            : ($courant !== ''
                ? 'Aucun paiement « ' . AdminStatus::label($courant) . ' » sur ces critères.'
                : 'Aucun paiement enregistré.')" />
    @else
        <div class="lst-wrap" x-data="{ copie: null }">
            <table class="lst-table">
                <thead>
                    <tr>
                        <th>Transaction</th>
                        <th>Client</th>
                        <th>Commande</th>
                        <th class="c">Canal</th>
                        <th class="r">Montant</th>
                        <th class="c">Statut</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payments as $payment)
                        <tr>
                            <td>
                                {{-- Copie au clic : le motif existe déjà dans
                                     l'admin (fiche revendeur), on le reprend. --}}
                                <button type="button" class="lst-mono lst-copy"
                                        @click="navigator.clipboard.writeText('{{ $payment->transaction_id }}'); copie = {{ $payment->id }}; setTimeout(() => copie = null, 1600)"
                                        :title="copie === {{ $payment->id }} ? 'Copié' : 'Copier la référence'">
                                    <span x-show="copie !== {{ $payment->id }}">{{ $payment->transaction_id }}</span>
                                    <span x-show="copie === {{ $payment->id }}" x-cloak class="lst-copied">Copié ✓</span>
                                </button>
                            </td>
                            <td><x-admin.cell-user :name="$payment->user?->name" :sub="$payment->user?->email" /></td>
                            <td>
                                @if ($payment->order)
                                    <a href="{{ route('admin.orders.show', $payment->order) }}" class="lst-link">#{{ $payment->order->order_number }}</a>
                                @else
                                    <span class="lst-ref-sub">—</span>
                                @endif
                            </td>
                            <td class="c"><x-admin.cell-channel :method="$payment->payment_method ?: $payment->provider" /></td>
                            <td class="r"><x-admin.cell-amount :value="$payment->amount" /></td>
                            <td class="c"><x-ui.pill :status="$payment->status">{{ AdminStatus::label($payment->status) }}</x-ui.pill></td>
                            <td><x-admin.cell-date :value="$payment->created_at" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="lst-pagination">{{ $payments->links() }}</div>
    @endif
</x-admin.list-screen>
@endsection
