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

    // Taux utiles (cotes devise→XAF déjà préparées par le contrôleur).
    $rate = fn (string $c) => $xafRates[$c] ?? null;

    // Dates de période lisibles pour le titre ("Du 22/08/2026 au 20/09/2026").
    $dmy = fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('d/m/Y');
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

    {{-- ============ 3. TAUX DE CHANGE ============ --}}
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

    {{-- ============ 4. TRANSACTIONS / MARGE ============ --}}
    <x-ui.card variant="inset" class="bmb-card">
        <div class="lst-head">
            <h1 class="lst-title">Transactions Bamboo</h1>
            <x-ui.pill variant="count" class="lst-total">{{ number_format($tx->count(), 0, ',', ' ') }}</x-ui.pill>
        </div>
        <p class="bmb-meta">
            Du {{ $dmy($startDate) }} au {{ $dmy($endDate) }}
            @if (! $transactions['ok']) <span class="bmb-err">— {{ $transactions['error'] }}</span> @endif
        </p>

        <form method="GET" action="{{ route('admin.bamboo.index') }}" class="lst-toolbar lst-filters" data-no-loader>
            <label class="lst-date"><span>Du</span><input type="date" name="start_date" value="{{ $startDate }}"></label>
            <label class="lst-date"><span>Au</span><input type="date" name="end_date" value="{{ $endDate }}"></label>
            <button type="submit" class="lst-apply">Filtrer</button>
            <button type="submit" name="refresh" value="1" class="lst-apply lst-apply--ghost">↻ Rafraîchir</button>
            <a class="lst-action" href="{{ route('admin.bamboo.reconcile', ['start_date' => $startDate, 'end_date' => $endDate]) }}">Historique & réconciliation</a>
        </form>

        @if ($tx->isEmpty())
            <x-ui.empty-state :label="$transactions['ok'] ? 'Aucune transaction sur cette période.' : 'Transactions indisponibles.'" />
        @else
            <div class="lst-wrap">
                <table class="lst-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Commande</th>
                            <th>Client</th>
                            <th>Produit</th>
                            <th class="r">Montant</th>
                            <th class="r">Équivalent FCFA</th>
                            <th class="r">Solde après</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tx->take(60) as $t)
                            @php
                                $tAmt   = (float) ($t['transactionAmount']['value'] ?? 0);
                                $tCur   = (string) ($t['transactionAmount']['currencyCode'] ?? '');
                                $tXaf   = \App\Support\BambooRates::convert($tAmt, $tCur, $xafRates);
                                $bal    = (float) ($t['availableBalance']['value'] ?? 0);
                                $balCur = (string) ($t['availableBalance']['currencyCode'] ?? $tCur);
                                $local  = $localByBamboo['order:' . ($t['orderId'] ?? '')]
                                    ?? $localByBamboo['req:' . ($t['requestId'] ?? '')] ?? null;
                                $clientName = $local?->user?->name ?: ($t['bambooClient'] ?? null);
                                $clientSub  = $local?->user?->email ?: ($local ? '#' . $local->order_number : null);
                            @endphp
                            <tr class="{{ $local ? 'is-clickable' : '' }}" @if ($local) onclick="window.location='{{ route('admin.orders.show', $local) }}'" @endif>
                                <td><x-admin.cell-date :value="$t['transactionDate'] ?? null" /></td>
                                <td>
                                    @if ($local)
                                        <a class="lst-link" href="{{ route('admin.orders.show', $local) }}">#{{ $t['orderId'] ?? '—' }}</a>
                                        <span class="lst-ref-sub">Commande locale · {{ $local->order_number }}</span>
                                    @else
                                        <span class="lst-ref">#{{ $t['orderId'] ?? '—' }}</span>
                                        @if ($ref = $t['requestId'] ?? null)
                                            <span class="lst-ref-sub">{{ mb_substr($ref, 0, 8) }}…</span>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    <x-admin.cell-user :name="$clientName" :sub="$clientSub" tone="{{ $local ? 'navy' : 'teal' }}" />
                                </td>
                                <td>
                                    @php $oi = ($t['orderItems'][0] ?? null); @endphp
                                    {{ $oi['productName'] ?? '—' }}
                                    @if (($oi['denomination']['value'] ?? null) !== null)
                                        <span class="lst-ref-sub">{{ $oi['denomination']['value'] }} {{ $oi['denomination']['currencyCode'] ?? '' }}</span>
                                    @endif
                                </td>
                                <td class="r">
                                    <span class="cll-amount">{{ $fmt($tAmt) }}@if ($tCur)<small>{{ $tCur }}</small>@endif</span>
                                </td>
                                <td class="r">
                                    @if ($tXaf !== null)
                                        <span class="cll-amount">{{ $fmt0($tXaf) }}<small>FCFA</small></span>
                                    @else
                                        <span class="lst-soon">—</span>
                                    @endif
                                </td>
                                <td class="r">
                                    <span class="cll-amount">{{ $fmt($bal) }}@if ($balCur)<small>{{ $balCur }}</small>@endif</span>
                                </td>
                                <td class="r">
                                    @if ($local)
                                        <a href="{{ route('admin.orders.show', $local) }}" class="lst-action" onclick="event.stopPropagation();">Voir</a>
                                    @else
                                        <span class="lst-soon">Ext.</span>
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