@extends('admin.layouts.admin')

@section('title', 'Commandes')
@section('page-title', 'Commandes')

@section('content')
@php
    use App\Models\Order;
    use App\Support\AdminStatus;

    /* Onglets calqués sur les statuts RÉELS du modèle Order — aucun inventé.
       « Toutes » d'abord, puis le cycle de vie dans son ordre naturel. */
    $statuts = [
        Order::STATUS_PENDING,
        Order::STATUS_PROCESSING,
        Order::STATUS_COMPLETED,
        Order::STATUS_CANCELLED,
        Order::STATUS_REFUNDED,
    ];

    $courant = request('status', '');
    $base    = request()->except(['status', 'page']);

    $onglets = collect([['key' => '', 'label' => 'Toutes', 'count' => $statusCounts->sum()]])
        ->concat(collect($statuts)->map(fn ($s) => [
            'key'   => $s,
            'label' => AdminStatus::label($s),
            'count' => (int) ($statusCounts[$s] ?? 0),
        ]))
        ->map(fn ($t) => $t + [
            'url'    => route('admin.orders.index', $t['key'] === '' ? $base : $base + ['status' => $t['key']]),
            'active' => $courant === $t['key'],
        ])->all();

    $filtre = request()->hasAny(['status', 'search', 'date_from', 'date_to']);
@endphp

<x-admin.list-screen
    title="Commandes"
    :total="$orders->total()"
    :tabs="$onglets"
    :search="request('search')"
    placeholder="N° de commande, client, e-mail…"
    :filtered="$filtre"
    :reset-url="route('admin.orders.index')">

    @if ($orders->isEmpty())
        {{-- Libellé contextuel : dire « aucune commande » quand l'utilisateur
             vient de filtrer sur « Annulée » n'aide personne. --}}
        <x-ui.empty-state :label="request('search')
            ? 'Aucun résultat pour « ' . request('search') . ' ».'
            : ($courant !== ''
                ? 'Aucune commande « ' . AdminStatus::label($courant) .' » sur ces critères.'
                : 'Aucune commande pour le moment.')" />
    @else
        <div class="lst-wrap">
            <table class="lst-table">
                <thead>
                    <tr>
                        <th>Commande</th>
                        <th>Client</th>
                        <th class="c">Canal</th>
                        <th class="r">Montant</th>
                        <th class="c">Paiement</th>
                        <th class="c">Statut</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr class="is-clickable" onclick="window.location='{{ route('admin.orders.show', $order) }}'">
                            <td>
                                <span class="lst-ref">#{{ $order->order_number }}</span>
                                @if ($order->order_items_count > 0)
                                    <span class="lst-ref-sub">{{ $order->order_items_count }} article{{ $order->order_items_count > 1 ? 's' : '' }}</span>
                                @endif
                            </td>
                            <td><x-admin.cell-user :name="$order->user?->name" :sub="$order->user?->email" /></td>
                            <td class="c"><x-admin.cell-channel :method="$order->payment_method" /></td>
                            <td class="r"><x-admin.cell-amount :value="$order->total_amount" /></td>
                            <td class="c"><x-ui.pill :status="$order->payment_status">{{ AdminStatus::label($order->payment_status) }}</x-ui.pill></td>
                            <td class="c"><x-ui.pill :status="$order->status">{{ AdminStatus::label($order->status) }}</x-ui.pill></td>
                            <td><x-admin.cell-date :value="$order->created_at" /></td>
                            <td class="r">
                                <a href="{{ route('admin.orders.show', $order) }}" class="lst-action" onclick="event.stopPropagation();">Voir</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="lst-pagination">{{ $orders->links() }}</div>
    @endif
</x-admin.list-screen>
@endsection
