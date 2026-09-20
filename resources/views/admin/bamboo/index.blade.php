@extends('admin.layouts.admin')

@section('title', 'Bamboo — Finances fournisseur')
@section('page-title', 'Bamboo')

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 2, ',', ' ');

    $accByCur = collect($accounts['accounts'])->filter(fn ($a) => (bool) ($a['isActive'] ?? false))
        ->keyBy(fn ($a) => strtoupper((string) ($a['currency'] ?? '?')));

    $totalEur = (float) ($accByCur['EUR']['balance'] ?? 0)
        + (float) ($accByCur['USD']['balance'] ?? 0) * 0.92; // approximation USD→EUR

    // Transactions flat (tous les clients) pour un tableau lisible.
    $tx = collect($transactions['clients'] ?? [])
        ->flatMap(fn ($c) => $c['transactions'] ?? [])
        ->sortByDesc('transactionDate')
        ->values();

    // Taux utiles (les taux Bamboo sont donnés pour 1 unité : EUR→XAF etc.)
    $ratesByCur = collect($rates['rates'] ?? [])->keyBy('currencyCode');
@endphp

<div class="lst">

    @if (session('success'))
        <div class="lst-flash">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="lst-flash lst-flash--err" role="alert">{{ $errors->first() }}</div>
    @endif

    {{-- ============ 1. SOLDES DES COMPTES (feature 3) ============ --}}
    <x-ui.card variant="inset" class="lst-summary">
        <div class="bmb-head">
            <div>
                <div class="bmb-title">Soldes des comptes fournisseur</div>
                <p class="bmb-meta">
                    @if ($accounts['ok'])
                        Actualisé {{ $accounts['fetched_at'] }}
                    @else
                        <span class="bmb-err">Indisponible — {{ $accounts['error'] }}</span>
                    @endif
                </p>
            </div>
            <a class="bmb-refresh" href="{{ route('admin.bamboo.index', request()->except('refresh') + ['refresh' => 1]) }}">↻ Actualiser</a>
        </div>

        <div class="bmb-accounts">
            @forelse (($accounts['accounts'] ?? []) as $acc)
                @php
                    $cur   = strtoupper((string) ($acc['currency'] ?? ''));
                    $bal   = (float) ($acc['balance'] ?? 0);
                    $isLow = $cur === 'EUR' && $bal < $threshold;
                @endphp
                <div class="bmb-acc {{ $isLow ? 'bmb-acc--low' : '' }}">
                    <div class="bmb-acc-cur">{{ $cur }}</div>
                    <div class="bmb-acc-bal">{{ $fmt($bal) }} {{ $cur }}</div>
                    <div class="bmb-acc-meta">
                        Compte #{{ $acc['id'] ?? '—' }}
                        · {{ ($acc['sandboxMode'] ?? false) ? 'sandbox' : 'prod' }}
                        @if ($isLow)
                            · <strong class="bmb-low">SOLDE BAS</strong>
                        @endif
                    </div>
                </div>
            @empty
                <p class="bmb-meta">Aucun compte retourné par Bamboo.</p>
            @endforelse
        </div>

        @if (count($low) > 0)
            <div class="bmb-alert">
                ⚠ Alerte : le solde du compte {{ strtoupper((string) ($low[0]['currency'] ?? 'EUR')) }}
                ({{ $fmt($low[0]['balance'] ?? 0) }}) est passé sous le seuil de {{ $fmt($threshold) }}
                — pensez à créditer le compte fournisseur.
            </div>
        @elseif (count($accounts['accounts'] ?? []) > 0)
            @if ($totalEur > 0)
                <p class="bmb-meta">Solde total approximatif (EUR + USD ≈ EUR) : <strong>{{ $fmt($totalEur) }} EUR</strong></p>
            @endif
        @endif
    </x-ui.card>

    {{-- ============ 2. PÉRIODE ============ --}}
    <form method="GET" action="{{ route('admin.bamboo.index') }}" class="bmb-filter">
        <label>Du <input type="date" name="start_date" value="{{ $startDate }}"></label>
        <label>au <input type="date" name="end_date" value="{{ $endDate }}"></label>
        <button type="submit" class="lst-apply">Filtrer</button>
    </form>

    {{-- ============ 3. TAUX DE CHANGE (feature 5) ============ --}}
    <x-ui.card variant="inset" class="bmb-card">
        <div class="bmb-title">Taux de change officiels Bamboo</div>
        <p class="bmb-meta">Base : {{ $rates['base'] ?? '—' }} · 1 unité de devise cible ({{ $rates['ok'] ? '' : 'indisponible' }})</p>
        @if ($rates['ok'])
            <div class="bmb-rates">
                <span class="bmb-rate"><strong>EUR</strong> → XAF : {{ $fmt(($ratesByCur['XAF']['value'] ?? 0) !== 0 ? 655.957 * (($ratesByCur['EUR'] ?? [])['value'] ?? 1) / (($ratesByCur['USD'] ?? [])['value'] ?? 1) : 0) }}</span>
                @foreach (['USD', 'EUR', 'AED', 'GBP'] as $c)
                    @if ($r = $ratesByCur[$c] ?? null)
                        <span class="bmb-rate"><strong>{{ $c }}</strong> : {{ $fmt($r['value']) }}</span>
                    @endif
                @endforeach
            </div>
        @endif
    </x-ui.card>

    {{-- ============ 4. TRANSACTIONS / MARGE (feature 4) ============ --}}
    <x-ui.card variant="inset" class="bmb-card">
        <div class="bmb-head">
            <div>
                <div class="bmb-title">Transactions Bamboo · {{ $startDate }} → {{ $endDate }}</div>
                <p class="bmb-meta">
                    {{ $tx->count() }} transaction(s)
                    @if (! $transactions['ok']) <span class="bmb-err">— {{ $transactions['error'] }}</span> @endif
                </p>
            </div>
            <a class="bmb-refresh" href="{{ route('admin.bamboo.reconcile', ['start_date' => $startDate, 'end_date' => $endDate]) }}">Historique & réconciliation →</a>
        </div>

        @if ($tx->isEmpty())
            <x-ui.empty-state :label="$transactions['ok'] ? 'Aucune transaction sur cette période.' : 'Transactions indisponibles.'" />
        @else
            <div class="lst-wrap">
                <table class="lst-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Commande</th>
                            <th>Produit</th>
                            <th class="r">Montant</th>
                            <th>Solde après</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tx->take(60) as $t)
                            <tr>
                                <td>{{ \Illuminate\Support\Carbon::parse($t['transactionDate'] ?? null)->setTimezone('Africa/Libreville')->format('d/m H:i') }}</td>
                                <td>
                                    <span class="bmb-order">#{{ $t['orderId'] ?? '—' }}</span>
                                    @if ($ref = $t['requestId'] ?? null)
                                        <span class="bmb-ref-sub">{{ mb_substr($ref, 0, 8) }}…</span>
                                    @endif
                                </td>
                                <td>
                                    @php $oi = ($t['orderItems'][0] ?? null); @endphp
                                    {{ $oi['productName'] ?? '—' }}
                                    @if (($oi['denomination']['value'] ?? null) !== null)
                                        <span class="bmb-ref-sub">{{ $oi['denomination']['value'] }} {{ $oi['denomination']['currencyCode'] ?? '' }}</span>
                                    @endif
                                </td>
                                <td class="r">
                                    <x-admin.cell-amount :value="(float) ($t['transactionAmount']['value'] ?? 0)"
                                        :unit="$t['transactionAmount']['currencyCode'] ?? ''" />
                                </td>
                                <td class="r">
                                    <span class="lst-soon">{{ $fmt($t['availableBalance']['value'] ?? 0) }}</span>
                                </td>
                                <td><x-ui.pill status="{{ strtolower($t['transactionType'] ?? 'order') === 'order' ? 'completed' : 'pending' }}">{{ $t['transactionType'] ?? 'Order' }}</x-ui.pill></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>

</div>
@endsection