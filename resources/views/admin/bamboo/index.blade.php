@extends('admin.layouts.admin')

@section('title', 'Bamboo — Finances fournisseur')
@section('page-title', 'Bamboo')

@section('content')
@php
    $fmt  = fn ($n) => number_format((float) $n, 2, ',', ' ');
    $fmt0 = fn ($n) => number_format((float) $n, 0, ',', ' ');

    $accs = collect($accounts['accounts'] ?? [])->filter(fn ($a) => (bool) ($a['isActive'] ?? false));
    $accByCur = $accs->keyBy(fn ($a) => strtoupper((string) ($a['currency'] ?? '?')));

    // Comptes EUR sous le seuil d'alerte, distinct du flag booléen $low des pillules.
    $lowAccounts = $accs->filter(fn ($a) => strtoupper((string) ($a['currency'] ?? '')) === 'EUR'
        && (float) ($a['balance'] ?? 0) < $threshold)->values();
    $low = $lowAccounts->isNotEmpty();

    $eur = $accByCur->get('EUR');
    $usd = $accByCur->get('USD');

    // Transactions flat (tous les clients) pour un tableau lisible.
    $tx = collect($transactions['clients'] ?? [])
        ->flatMap(fn ($c) => $c['transactions'] ?? [])
        ->sortByDesc('transactionDate')
        ->values();

    // Taux utiles (cotes devise→XAF déjà préparées par le contrôleur).
    $rate = fn (string $c) => $xafRates[$c] ?? null;
@endphp

<div class="lst">

    @if (session('success'))
        <div class="lst-flash">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="lst-flash lst-flash--err" role="alert">{{ $errors->first() }}</div>
    @endif

    {{-- ============ 1. SOLDE TOTAL (hero) ============ --}}
    <x-ui.card variant="highlight" class="bmb-hero">
        <div class="bmb-head bmb-head--navy">
            <div>
                <div class="ui-stat-label ui-card-accent">Solde fournisseur Bamboo</div>
                <p class="bmb-hero-meta">
                    @if ($accounts['ok'])
                        Actualisé {{ $accounts['fetched_at'] }}
                    @else
                        <span class="bmb-err">Indisponible — {{ $accounts['error'] }}</span>
                    @endif
                </p>
            </div>
            <div class="bmb-hero-actions">
                @if ($accounts['ok'] && $eur)
                    @if ($low)
                        <x-ui.pill status="pending">Sous {{ $fmt($threshold) }} EUR</x-ui.pill>
                    @else
                        <x-ui.pill status="completed">Seuil OK</x-ui.pill>
                    @endif
                @endif
                <a class="bmb-refresh" href="{{ route('admin.bamboo.index', request()->except('refresh') + ['refresh' => 1]) }}">↻ Actualiser</a>
            </div>
        </div>

        @if ($accounts['ok'] && $totalXaf !== null)
            <x-ui.stat-number :value="$totalXaf" label="Équivalent FCFA" />
            <p class="bmb-hero-sub">
                ≈ {{ $fmt($eur['balance'] ?? 0) }} EUR
                @if ($usd)
                    + {{ $fmt($usd['balance']) }} USD
                @endif
                · parité officielle {{ $fmt0($rate('EUR') ?? 655.957) }} FCFA / EUR
            </p>
        @else
            <x-ui.empty-state label="Solde Bamboo indisponible." class="bmb-empty-navy" />
        @endif
    </x-ui.card>

    {{-- ============ 2. COMPTES FOURNISSEUR ============ --}}
    <x-ui.card variant="inset" class="bmb-card">
        <div class="bmb-title">Comptes fournisseur</div>
        <p class="bmb-meta">Chaque compte avec son équivalent en monnaie locale.</p>

        <div class="bmb-accounts">
            @forelse ($accs as $acc)
                @php
                    $cur   = strtoupper((string) ($acc['currency'] ?? ''));
                    $bal   = (float) ($acc['balance'] ?? 0);
                    $isLow = $cur === 'EUR' && $bal < $threshold;
                @endphp
                <div class="bmb-acc {{ $isLow ? 'bmb-acc--low' : '' }}">
                    <div class="bmb-acc-top">
                        <div class="bmb-acc-cur">{{ $cur }}</div>
                        @if ($isLow)
                            <span class="bmb-low">SOLDE BAS</span>
                        @endif
                    </div>
                    <div class="bmb-acc-bal">{{ $fmt($bal) }} {{ $cur }}</div>
                    @if (array_key_exists($cur, $accountsXaf) && $accountsXaf[$cur] !== null)
                        <div class="bmb-acc-xaf">≈ {{ $fmt0($accountsXaf[$cur]) }} FCFA</div>
                    @endif
                    <div class="bmb-acc-meta">
                        Compte #{{ $acc['id'] ?? '—' }}
                        · {{ ($acc['sandboxMode'] ?? false) ? 'sandbox' : 'prod' }}
                    </div>
                </div>
            @empty
                <p class="bmb-meta">Aucun compte retourné par Bamboo.</p>
            @endforelse
        </div>

        @if ($lowAccounts->isNotEmpty())
            <div class="bmb-alert">
                ⚠ Alerte : le solde du compte {{ strtoupper((string) ($lowAccounts[0]['currency'] ?? 'EUR')) }}
                ({{ $fmt($lowAccounts[0]['balance'] ?? 0) }}) est passé sous le seuil de {{ $fmt($threshold) }}
                — pensez à créditer le compte fournisseur.
            </div>
        @endif
    </x-ui.card>

    {{-- ============ 3. PÉRIODE ============ --}}
    <form method="GET" action="{{ route('admin.bamboo.index') }}" class="bmb-filter">
        <label>Du <input type="date" name="start_date" value="{{ $startDate }}"></label>
        <label>au <input type="date" name="end_date" value="{{ $endDate }}"></label>
        <button type="submit" class="lst-apply">Filtrer</button>
        <button type="submit" name="refresh" value="1" class="lst-apply lst-apply--ghost">↻ Rafraîchir</button>
    </form>

    {{-- ============ 4. TAUX DE CHANGE ============ --}}
    <x-ui.card variant="inset" class="bmb-card">
        <div class="bmb-title">Taux de change officiels Bamboo</div>
        <p class="bmb-meta">{{ $rates['ok'] ? 'Base : ' . ($rates['base'] ?? '—') . ' — cotes converties vers FCFA.' : 'Taux indisponibles.' }}</p>
        @if ($rates['ok'])
            <div class="bmb-rates">
                @foreach (['EUR', 'USD', 'GBP', 'AED'] as $c)
                    @if (($r = $rate($c)) !== null)
                        <span class="bmb-rate"><strong>{{ $c }}</strong> → FCFA : {{ $fmt0($r) }}</span>
                    @endif
                @endforeach
            </div>
        @endif
    </x-ui.card>

    {{-- ============ 5. TRANSACTIONS / MARGE ============ --}}
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
                            <th>Équivalent FCFA</th>
                            <th>Solde après</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tx->take(60) as $t)
                            @php
                                $tAmt = (float) ($t['transactionAmount']['value'] ?? 0);
                                $tCur = (string) ($t['transactionAmount']['currencyCode'] ?? '');
                                $tXaf = \App\Support\BambooRates::convert($tAmt, $tCur, $xafRates);
                            @endphp
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
                                    <x-admin.cell-amount :value="$tAmt" :unit="$tCur" />
                                </td>
                                <td class="r">
                                    @if ($tXaf !== null)
                                        <span class="cll-amount">{{ $fmt0($tXaf) }}<small>FCFA</small></span>
                                    @else
                                        <span class="lst-soon">—</span>
                                    @endif
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