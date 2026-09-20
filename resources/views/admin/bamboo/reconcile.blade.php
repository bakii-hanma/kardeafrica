@extends('admin.layouts.admin')

@section('title', 'Bamboo — Historique & réconciliation')
@section('page-title', 'Bamboo · Réconciliation')

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 2, ',', ' ');
@endphp

<div class="lst">

    @if (session('success'))
        <div class="lst-flash">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="lst-flash lst-flash--err" role="alert">{{ $errors->first() }}</div>
    @endif

    {{-- ============ PERIODE + ACTION ============ --}}
    <form method="GET" action="{{ route('admin.bamboo.reconcile') }}" class="bmb-filter">
        <label>Du <input type="date" name="start_date" value="{{ $startDate }}"></label>
        <label>au <input type="date" name="end_date" value="{{ $endDate }}"></label>
        <button type="submit" class="lst-apply">Filtrer</button>
        <button type="submit" name="refresh" value="1" class="lst-apply lst-apply--ghost">↻ Rafraîchir</button>
        <button type="submit" name="run" value="1" class="lst-apply lst-apply--warn">Récupérer les orphelines</button>
    </form>

    @if ($recovered > 0)
        <div class="lst-flash">{{ $recovered }} commande(s) récupérée(s) et livrée(s) grâce à l'historique Bamboo.</div>
    @endif

    {{-- ============ HISTORIQUE BAMBOO ============ --}}
    <x-ui.card variant="inset" class="bmb-card">
        <div class="bmb-head">
            <div>
                <div class="bmb-title">Commandes Bamboo sur la période</div>
                <p class="bmb-meta">
                    {{ count($report['orders'] ?? []) }} commande(s)
                    @if (! $report['ok']) <span class="bmb-err">— {{ $report['error'] }}</span>
                    @else — l'identifiant « Réf. » sert au rapprochement avec les commandes locales. @endif
                </p>
            </div>
        </div>

        @if ($report['ok'] && ! empty($report['orders']))
            <div class="lst-wrap">
                <table class="lst-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Numéro</th>
                            <th>Réf. client</th>
                            <th>Produit</th>
                            <th class="r">Qté</th>
                            <th class="r">Total</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['orders'] as $o)
                            <tr>
                                <td>{{ \Illuminate\Support\Carbon::parse($o['orderDate'] ?? null)->setTimezone('Africa/Libreville')->format('d/m H:i') }}</td>
                                <td><span class="bmb-order">#{{ $o['orderNumber'] ?? '—' }}</span></td>
                                <td>
                                    <span class="bmb-ref-sub">{{ $o['clientReferenceNumber'] ?? '—' }}</span>
                                    {{-- Requête locale correspondante (clientReferenceNumber == requestId) --}}
                                    @php
                                        $local = $locales->first(fn ($l) => ($l->billing_details['checkout_request_id'] ?? null) === ($o['clientReferenceNumber'] ?? null));
                                    @endphp
                                    @if ($local)
                                        @if ($local->userCards()->exists())
                                            <x-ui.pill status="completed">livrée</x-ui.pill>
                                        @else
                                            <x-ui.pill status="pending">locale</x-ui.pill>
                                        @endif
                                    @endif
                                </td>
                                <td>{{ $o['productName'] ?? '—' }}</td>
                                <td class="r">{{ (int) ($o['quantity'] ?? 0) }}</td>
                                <td class="r"><x-admin.cell-amount :value="(float) ($o['total'] ?? 0)" :unit="$o['accountCurrency'] ?? ''" /></td>
                                <td>{{ $o['status'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif ($report['ok'])
            <x-ui.empty-state label="Aucune commande Bamboo sur cette période." />
        @endif
    </x-ui.card>

    {{-- ============ COMMANDES LOCALES SUR LA PÉRIODE ============ --}}
    <x-ui.card variant="inset" class="bmb-card">
        <div class="bmb-head">
            <div>
                <div class="bmb-title">Commandes locales en attente de livraison</div>
                <p class="bmb-meta">{{ $locales->count() }} commande(s) payée(s), sans cartes livrées, sur la période.</p>
            </div>
        </div>

        @if ($locales->isEmpty())
            <x-ui.empty-state label="Rien à réconcilier sur cette période." />
        @else
            <div class="lst-wrap">
                <table class="lst-table">
                    <thead>
                        <tr>
                            <th>Commande</th>
                            <th>Client</th>
                            <th class="r">Montant</th>
                            <th>Réf. fournisseur</th>
                            <th>Résultat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($locales as $order)
                            @php
                                $bd     = (array) $order->billing_details;
                                $req    = $bd['checkout_request_id'] ?? null;
                                $detail = collect($details)->first(fn ($d) => $d['order']->id === $order->id);
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="lst-cell-link">
                                        <span class="bmb-order">#{{ $order->id }}</span>
                                    </a>
                                    <span class="bmb-ref-sub">{{ $order->created_at->setTimezone('Africa/Libreville')->format('d/m H:i') }}</span>
                                </td>
                                <td>{{ $order->user?->name ?? '—' }}</td>
                                <td class="r"><x-admin.cell-amount :value="$order->total_amount" unit="FCFA" /></td>
                                <td><span class="bmb-ref-sub">{{ $req ? mb_substr($req, 0, 12) . '…' : '—' }}</span></td>
                                <td>
                                    @if ($detail)
                                        @if ($detail['result']['type'] === 'delivered')
                                            <x-ui.pill status="completed">livrée</x-ui.pill>
                                        @else
                                            <x-ui.pill status="pending">{{ $detail['result']['type'] }}</x-ui.pill>
                                        @endif
                                    @else
                                        <span class="bmb-meta">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

</div>
@endsection