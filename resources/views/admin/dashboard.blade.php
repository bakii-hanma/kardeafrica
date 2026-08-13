@extends('admin.layouts.admin')

@section('title', 'Tableau de bord')
@section('page-title', 'Vue d\'ensemble')

@section('content')
@php
    use App\Support\AdminDashboardStats;

    $fmt = fn ($n) => number_format((float) $n, 0, ',', ' ');

    /* Montants compactés pour les axes : « 120 k » se lit, « 120 000 » déborde. */
    $compact = function ($n) {
        $n = (float) $n;
        if (abs($n) >= 1_000_000) return rtrim(rtrim(number_format($n / 1_000_000, 1, ',', ' '), '0'), ',') . ' M';
        if (abs($n) >= 1_000)     return rtrim(rtrim(number_format($n / 1_000, 1, ',', ' '), '0'), ',') . ' k';
        return $fmt($n);
    };

    $delta       = $stats->revenueDelta();
    $canaux      = $stats->paymentChannels();
    $serie       = $stats->salesSeries();
    $classement  = $stats->topSellers();
    $parMois     = $stats->ordersByMonth();
    $versements  = $stats->pendingSettlements();
    $meilleure   = $stats->bestSale();
    $commandes   = $stats->recentOrders();
    $cartes      = $stats->cards();
    $utilisateurs= $stats->users();

    /* Les onglets conservent une plage personnalisée nulle : basculer sur un
       preset doit effacer date_from/date_to, sinon la plage l'emporterait. */
    $onglets = collect(AdminDashboardStats::PRESETS)->map(fn ($libelle, $cle) => [
        'label'  => $libelle,
        'url'    => route('admin.dashboard', ['periode' => $cle]),
        'active' => ! $stats->isCustom() && $stats->preset() === $cle,
    ])->values()->all();

    $maxMois = max(1, max(array_column($parMois, 'count')));
@endphp

<div class="dsh">

    {{-- ============ 1. EN-TÊTE KPI ============ --}}
    <x-ui.card class="dsh-panel">

        <div class="dsh-head">
            <div>
                <div class="dsh-watermark">Vue d'ensemble</div>
                <div class="dsh-date">
                    {{ \Illuminate\Support\Carbon::now(AdminDashboardStats::TZ)->translatedFormat('d/m/Y') }}
                    · {{ $stats->label() }}
                </div>
            </div>
            <x-ui.segmented-tabs :tabs="$onglets" />
        </div>

        <div class="dsh-kpis">

            {{-- Revenu + comparaison à la période précédente de même durée --}}
            <div class="dsh-rev">
                <x-ui.stat-number :value="$stats->revenue()" label="Revenu · {{ $stats->label() }}" />
                <div class="dsh-rev-pills">
                    @if ($delta['kind'] === 'percent')
                        <x-ui.pill variant="delta" :down="! $delta['up']" title="{{ $delta['note'] ?? 'Comparé à la période précédente de même durée.' }}">
                            {{ $delta['up'] ? '+' : '' }}{{ number_format($delta['percent'], 1, ',', ' ') }} %
                        </x-ui.pill>
                        <x-ui.pill variant="delta" :down="! $delta['up']">
                            {{ $delta['up'] ? '+' : '−' }}{{ $fmt(abs($delta['amount'])) }} FCFA
                        </x-ui.pill>
                    @else
                        {{-- Référence nulle : jamais de pourcentage infini. --}}
                        <x-ui.pill status="pending" title="{{ $delta['note'] }}">1ʳᵉ période</x-ui.pill>
                    @endif
                </div>
            </div>

            {{-- Meilleure vente de la période --}}
            <x-ui.card variant="highlight" class="dsh-best">
                <span class="ui-stat-label ui-card-accent">Meilleure vente</span>
                @if ($meilleure)
                    <a href="{{ route('admin.orders.show', $meilleure) }}" class="dsh-best-link">
                        <span class="ui-stat">{{ $fmt($meilleure->total_amount) }}<small>FCFA</small></span>
                        <span class="dsh-best-meta">
                            {{ $meilleure->user?->name ?? 'Client' }} · #{{ $meilleure->order_number }}
                        </span>
                    </a>
                @else
                    <x-ui.empty-state label="Pas encore de vente sur cette période." class="dsh-empty-navy" />
                @endif
            </x-ui.card>

            {{-- 3 compteurs à chip sémantique --}}
            <a href="{{ route('admin.orders.index') }}" class="dsh-stat">
                <x-ui.icon-chip color="blue">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </x-ui.icon-chip>
                <div>
                    <x-ui.stat-number :value="$stats->ordersCount()" unit="" label="Commandes" class="dsh-stat-num" />
                    <span class="dsh-stat-meta">dont {{ $stats->ordersToday() }} aujourd'hui</span>
                </div>
            </a>

            <a href="{{ route('admin.cards.index') }}" class="dsh-stat">
                <x-ui.icon-chip color="teal">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </x-ui.icon-chip>
                <div>
                    <x-ui.stat-number :value="$cartes['active']" unit="" label="Cartes actives" class="dsh-stat-num" />
                    <span class="dsh-stat-meta">sur {{ $fmt($cartes['total']) }} au total</span>
                </div>
            </a>

            <a href="{{ route('admin.users.index') }}" class="dsh-stat">
                <x-ui.icon-chip color="violet">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </x-ui.icon-chip>
                <div>
                    <x-ui.stat-number :value="$utilisateurs['active']" unit="" label="Utilisateurs actifs" class="dsh-stat-num" />
                    <span class="dsh-stat-meta">sur {{ $fmt($utilisateurs['total']) }} inscrits</span>
                </div>
            </a>
        </div>

        {{-- ============ 2. RÉPARTITION DES PAIEMENTS ============ --}}
        <section class="dsh-chan" aria-labelledby="dsh-chan-t">
            <div class="dsh-chan-head">
                <h2 class="ui-stat-label" id="dsh-chan-t">Paiements par canal</h2>
                <span class="dsh-chan-total">{{ $fmt($canaux['total']) }} FCFA</span>
            </div>

            @if ($canaux['total'] <= 0)
                <div class="dsh-chan-bar dsh-chan-bar--empty" role="img" aria-label="Aucun paiement sur la période"></div>
            @else
                <div class="dsh-chan-bar" role="img"
                     aria-label="Répartition : @foreach($canaux['segments'] as $s){{ $s['label'] }} {{ $s['percent'] }} %. @endforeach">
                    @foreach ($canaux['segments'] as $s)
                        @if ($s['percent'] > 0)
                            <span class="dsh-seg dsh-seg--{{ $s['color'] }}" style="width:{{ $s['percent'] }}%;"
                                  title="{{ $s['label'] }} — {{ $fmt($s['amount']) }} FCFA · {{ $s['count'] }} paiement{{ $s['count'] > 1 ? 's' : '' }}"></span>
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- Légende complète même à un seul canal : l'absence est une information. --}}
            <ul class="dsh-chan-legend">
                @foreach ($canaux['segments'] as $s)
                    <li>
                        <span class="dsh-dot dsh-dot--{{ $s['color'] }}"></span>
                        <span class="dsh-chan-name">{{ $s['label'] }}</span>
                        <span class="dsh-chan-val">{{ $fmt($s['amount']) }} FCFA · {{ number_format($s['percent'], 1, ',', ' ') }} %</span>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- ============ 3. GRILLE DE WIDGETS ============ --}}
        <div class="dsh-grid">

            {{-- Dynamique des ventes --}}
            <x-ui.card variant="inset" class="dsh-w">
                <div class="dsh-w-head">
                    <h2 class="ui-stat-label">Dynamique des ventes</h2>
                    <span class="dsh-w-legend">
                        <span class="dsh-lg dsh-lg--now"></span> période
                        <span class="dsh-lg dsh-lg--prev"></span> précédente
                    </span>
                </div>

                @if ($serie['max'] <= 0)
                    <x-ui.empty-state label="Aucune vente à tracer sur cette période." />
                @else
                    @php
                        /* Courbe lisse : une Bézier cubique entre chaque paire de
                           points, tangentes horizontales — pas de dépendance à une
                           librairie pour deux polylignes. */
                        $n = count($serie['points']);
                        $w = 100; $h = 100;
                        $x = fn ($i) => $n > 1 ? round($i * $w / ($n - 1), 2) : 0;
                        $y = fn ($v) => round($h - ($serie['max'] > 0 ? $v / $serie['max'] * $h : 0), 2);

                        $chemin = function (string $cle) use ($serie, $n, $x, $y) {
                            $d = '';
                            foreach ($serie['points'] as $i => $p) {
                                $px = $x($i); $py = $y($p[$cle]);
                                if ($i === 0) { $d .= "M{$px},{$py}"; continue; }
                                $ax = $x($i - 1); $ay = $y($serie['points'][$i - 1][$cle]);
                                $c  = round(($px - $ax) / 2, 2);
                                $d .= " C" . ($ax + $c) . ",{$ay} " . ($px - $c) . ",{$py} {$px},{$py}";
                            }
                            return $d;
                        };
                    @endphp

                    <svg class="dsh-chart" viewBox="0 0 100 100" preserveAspectRatio="none" role="img"
                         aria-label="Ventes par {{ $serie['granularity'] }} : {{ $fmt($serie['total']) }} FCFA sur la période.">
                        {{-- Grille légère : 3 lignes, pas de graduations superflues. --}}
                        @foreach ([25, 50, 75] as $g)
                            <line x1="0" y1="{{ $g }}" x2="100" y2="{{ $g }}" class="dsh-grid-line" vector-effect="non-scaling-stroke"/>
                        @endforeach
                        <path d="{{ $chemin('previous') }}" class="dsh-line dsh-line--prev" vector-effect="non-scaling-stroke"/>
                        <path d="{{ $chemin('current') }}" class="dsh-line dsh-line--now" vector-effect="non-scaling-stroke"/>
                    </svg>

                    <div class="dsh-axis">
                        <span>{{ $serie['points'][0]['label'] ?? '' }}</span>
                        <span class="dsh-axis-max">max {{ $compact($serie['max']) }} FCFA</span>
                        <span>{{ $serie['points'][$n - 1]['label'] ?? '' }}</span>
                    </div>
                @endif
            </x-ui.card>

            {{-- Classement --}}
            <x-ui.card variant="inset" class="dsh-w">
                <div class="dsh-w-head">
                    <h2 class="ui-stat-label">{{ $classement['fallback'] ? 'Top cartes vendues' : 'Top vendeurs' }}</h2>
                    @unless ($classement['fallback'])
                        <a href="{{ route('admin.resellers.index') }}" class="dsh-w-link">Voir tout</a>
                    @endunless
                </div>

                @if ($classement['rows']->isEmpty())
                    <x-ui.empty-state label="Aucune vente sur cette période." />
                @else
                    <ol class="dsh-lead">
                        @foreach ($classement['rows'] as $i => $l)
                            <li class="dsh-lead-row">
                                <span class="dsh-lead-rank">{{ $i + 1 }}</span>
                                <span class="dsh-lead-ini" aria-hidden="true">{{ mb_strtoupper(mb_substr($l['name'], 0, 2)) }}</span>
                                <span class="dsh-lead-main">
                                    <span class="dsh-lead-name">
                                        @if ($l['url'])<a href="{{ $l['url'] }}">{{ $l['name'] }}</a>@else{{ $l['name'] }}@endif
                                    </span>
                                    <span class="dsh-lead-sub">{{ $l['count'] }} commande{{ $l['count'] > 1 ? 's' : '' }}{{ $l['sub'] ? ' · ' . $l['sub'] : '' }}</span>
                                </span>
                                @if ($i === 0)
                                    <x-ui.pill variant="delta">Top ventes</x-ui.pill>
                                @endif
                                <span class="dsh-lead-amount">{{ $fmt($l['amount']) }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-ui.card>

            {{-- Commandes par mois --}}
            <x-ui.card variant="inset" class="dsh-w">
                <div class="dsh-w-head">
                    <h2 class="ui-stat-label">Commandes par mois</h2>
                </div>

                @if ($maxMois <= 1 && array_sum(array_column($parMois, 'count')) === 0)
                    <x-ui.empty-state label="Aucune commande sur les 6 derniers mois." />
                @else
                    <div class="dsh-months">
                        @foreach ($parMois as $m)
                            <span class="dsh-month">
                                <span class="dsh-month-track">
                                    {{-- Le mois en cours est hachuré : il est incomplet,
                                         le comparer à plein aux autres ferait croire à une chute. --}}
                                    <span class="dsh-month-bar {{ $m['partial'] ? 'is-partial' : '' }}"
                                          style="height:{{ $m['count'] > 0 ? max(6, round($m['count'] / $maxMois * 100)) : 0 }}%;"
                                          title="{{ $m['label'] }} — {{ $m['count'] }} commande{{ $m['count'] > 1 ? 's' : '' }}{{ $m['partial'] ? ' (mois en cours, incomplet)' : '' }}"></span>
                                </span>
                                <span class="dsh-month-lbl">{{ $m['label'] }}</span>
                            </span>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>

            {{-- Versements en attente --}}
            <x-ui.card variant="inset" class="dsh-w dsh-w--pay">
                <div class="dsh-w-head">
                    <h2 class="ui-stat-label">Versements en attente</h2>
                </div>
                <x-ui.stat-number :value="$versements['amount']" />
                <p class="dsh-pay-meta">
                    {{ $versements['count'] }} commerçant{{ $versements['count'] > 1 ? 's' : '' }} à régler
                </p>
                <a href="{{ route('admin.versements.index') }}" class="dsh-w-link">Ouvrir les versements →</a>
            </x-ui.card>
        </div>

        {{-- ============ 4. COMMANDES RÉCENTES ============ --}}
        <section class="dsh-recent">
            <div class="dsh-w-head">
                <h2 class="ui-stat-label">Commandes récentes</h2>
                <a href="{{ route('admin.orders.index') }}" class="dsh-w-link">Voir tout</a>
            </div>

            @if ($commandes->isEmpty())
                <x-ui.empty-state label="Aucune commande pour le moment." />
            @else
                <div class="dsh-table-wrap">
                    <table class="dsh-table">
                        <thead>
                            <tr>
                                <th>Commande</th>
                                <th>Client</th>
                                <th>Paiement</th>
                                <th class="r">Montant</th>
                                <th class="c">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($commandes as $o)
                                @php
                                    $canal = AdminDashboardStats::channelBucket($o->payment_method);
                                    $teinte = ['mobile' => 'teal', 'card' => 'blue'][$canal] ?? 'violet';
                                @endphp
                                <tr onclick="window.location='{{ route('admin.orders.show', $o) }}'">
                                    <td>
                                        <span class="dsh-td-ref">#{{ $o->order_number }}</span>
                                        <span class="dsh-td-sub">{{ $o->created_at?->setTimezone(AdminDashboardStats::TZ)->format('d/m/Y H:i') }}</span>
                                    </td>
                                    <td>{{ $o->user?->name ?? 'Client' }}</td>
                                    <td>
                                        <x-ui.icon-chip :color="$teinte" class="dsh-td-chip">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                        </x-ui.icon-chip>
                                    </td>
                                    <td class="r"><span class="dsh-td-amount">{{ $fmt($o->total_amount) }}<small>FCFA</small></span></td>
                                    <td class="c"><x-ui.pill :status="$o->status">{{ ucfirst($o->status) }}</x-ui.pill></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </x-ui.card>
</div>
@endsection

@push('head')
<style>
    /* Toutes les couleurs viennent des tokens du P1 (admin-tokens.css). */
    .dsh-panel { box-shadow: var(--shadow-panel); padding: 24px; }
    @media (max-width: 640px) { .dsh-panel { padding: 16px; } }

    .dsh-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 20px; }
    .dsh-watermark { font-family: 'Space Grotesk','Inter',sans-serif; font-size: 28px; font-weight: 300; color: var(--text-faint); line-height: 1; }
    .dsh-date { font-size: 12px; color: var(--text-muted); margin-top: 6px; }

    /* ---- En-tête KPI ---- */
    .dsh-kpis { display: grid; grid-template-columns: 1fr; gap: 12px; margin-bottom: 22px; }
    @media (min-width: 720px)  { .dsh-kpis { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1180px) { .dsh-kpis { grid-template-columns: 1.3fr 1.2fr repeat(3, .9fr); } }

    .dsh-rev { display: flex; flex-direction: column; justify-content: center; gap: 10px; }
    .dsh-rev-pills { display: flex; gap: 6px; flex-wrap: wrap; }

    .dsh-best { display: flex; flex-direction: column; justify-content: center; gap: 6px; padding: 16px 18px; }
    .dsh-best-link { text-decoration: none; display: flex; flex-direction: column; gap: 4px; }
    .dsh-best-meta { font-size: 12px; color: rgb(255 255 255 / .70); }
    .dsh-empty-navy { padding: 8px 0; }
    .dsh-empty-navy .ui-empty-label { color: rgb(255 255 255 / .60); }
    .dsh-empty-navy svg { color: rgb(255 255 255 / .35); width: 26px; height: 26px; }

    .dsh-stat {
        display: flex; align-items: center; gap: 12px;
        background: var(--surface-inset); border-radius: var(--r-sub);
        padding: 14px 16px; text-decoration: none; color: inherit;
    }
    .dsh-stat:hover { box-shadow: var(--shadow-card); }
    .dsh-stat-num .ui-stat { font-size: 24px; }
    .dsh-stat-meta { display: block; font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }

    /* ---- Barre segmentée ---- */
    .dsh-chan { margin-bottom: 22px; }
    .dsh-chan-head { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; margin-bottom: 8px; }
    .dsh-chan-total { font-size: 14px; font-weight: 700; color: var(--text); }
    .dsh-chan-bar { display: flex; gap: 2px; height: 20px; border-radius: var(--r-pill); overflow: hidden; }
    .dsh-chan-bar--empty { background: var(--surface-inset); }
    .dsh-seg { height: 100%; transition: width .3s ease; }
    .dsh-seg--teal   { background: var(--teal); }
    .dsh-seg--blue   { background: var(--chip-blue); }
    .dsh-seg--violet { background: var(--chip-violet); }
    .dsh-chan-legend { list-style: none; margin: 10px 0 0; padding: 0; display: flex; gap: 18px; flex-wrap: wrap; }
    .dsh-chan-legend li { display: flex; align-items: center; gap: 6px; font-size: 12px; }
    .dsh-dot { width: 8px; height: 8px; border-radius: var(--r-pill); flex: none; }
    .dsh-dot--teal   { background: var(--teal); }
    .dsh-dot--blue   { background: var(--chip-blue); }
    .dsh-dot--violet { background: var(--chip-violet); }
    .dsh-chan-name { color: var(--text-muted); }
    .dsh-chan-val  { font-weight: 700; color: var(--text); }

    /* ---- Grille de widgets 2/3 – 1/3 ---- */
    .dsh-grid { display: grid; grid-template-columns: 1fr; gap: 12px; margin-bottom: 22px; }
    @media (min-width: 900px) {
        .dsh-grid { grid-template-columns: 2fr 1fr; }
        .dsh-grid > :nth-child(3) { grid-column: 1 / 2; }
    }
    .dsh-w { padding: 16px; }
    .dsh-w-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
    .dsh-w-head h2 { margin: 0; }
    .dsh-w-link { font-size: 12px; font-weight: 700; color: var(--teal); text-decoration: none; }
    .dsh-w-legend { display: flex; align-items: center; gap: 6px; font-size: 11px; color: var(--text-muted); }
    .dsh-lg { display: inline-block; width: 14px; height: 2px; border-radius: 2px; }
    .dsh-lg--now  { background: var(--teal); }
    .dsh-lg--prev { background: var(--text-faint); }

    .dsh-chart { width: 100%; height: 150px; display: block; overflow: visible; }
    .dsh-grid-line { stroke: var(--border); stroke-width: 1; }
    .dsh-line { fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
    .dsh-line--now  { stroke: var(--teal); }
    .dsh-line--prev { stroke: var(--text-faint); stroke-width: 1.5; stroke-dasharray: 4 4; }
    .dsh-axis { display: flex; justify-content: space-between; gap: 10px; margin-top: 8px; font-size: 11px; color: var(--text-muted); }
    .dsh-axis-max { color: var(--text-faint); }

    /* ---- Leaderboard ---- */
    .dsh-lead { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 10px; }
    .dsh-lead-row { display: flex; align-items: center; gap: 9px; }
    .dsh-lead-rank { font-size: 11px; font-weight: 800; color: var(--text-faint); width: 12px; }
    .dsh-lead-ini {
        width: 30px; height: 30px; border-radius: var(--r-pill); flex: none;
        background: var(--navy); color: var(--teal-light);
        display: flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: 800;
    }
    .dsh-lead-main { flex: 1; min-width: 0; }
    .dsh-lead-name { display: block; font-size: 13px; font-weight: 700; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .dsh-lead-name a { color: inherit; text-decoration: none; }
    .dsh-lead-name a:hover { color: var(--teal); }
    .dsh-lead-sub { display: block; font-size: 11px; color: var(--text-muted); }
    .dsh-lead-amount { font-size: 13px; font-weight: 800; color: var(--text); white-space: nowrap; }

    /* ---- Commandes par mois ---- */
    .dsh-months { display: flex; align-items: flex-end; gap: 8px; height: 130px; }
    .dsh-month { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; height: 100%; }
    .dsh-month-track { flex: 1; width: 100%; display: flex; align-items: flex-end; }
    .dsh-month-bar { width: 100%; background: var(--navy); border-radius: 5px 5px 0 0; }
    /* Hachures = période incomplète, lisible sans légende. */
    .dsh-month-bar.is-partial {
        background: repeating-linear-gradient(45deg, var(--navy) 0 2px, transparent 2px 6px);
        border: 1px solid var(--navy); border-bottom: 0;
    }
    .dsh-month-lbl { font-size: 10.5px; color: var(--text-muted); }

    .dsh-w--pay { display: flex; flex-direction: column; gap: 4px; justify-content: center; }
    .dsh-pay-meta { font-size: 12px; color: var(--text-muted); margin: 0 0 8px; }

    /* ---- Table ---- */
    .dsh-recent { background: var(--surface-inset); border-radius: var(--r-sub); padding: 16px; }
    .dsh-table-wrap { overflow-x: auto; }
    .dsh-table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 620px; }
    .dsh-table th {
        padding: 8px 10px; text-align: left;
        font-size: 10px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .07em; color: var(--text-muted);
    }
    .dsh-table td { padding: 10px; border-top: 1px solid var(--border); color: var(--text-muted); }
    .dsh-table tbody tr { cursor: pointer; }
    .dsh-table tbody tr:hover { background: var(--surface); }
    .dsh-table .r { text-align: right; }
    .dsh-table .c { text-align: center; }
    .dsh-td-ref { display: block; font-weight: 700; color: var(--text); }
    .dsh-td-sub { display: block; font-size: 11px; color: var(--text-faint); margin-top: 1px; }
    .dsh-td-amount { font-weight: 800; color: var(--text); }
    .dsh-td-amount small { font-size: .7em; font-weight: 600; color: var(--text-muted); margin-left: 3px; }
    .dsh-td-chip { width: 28px; height: 28px; }
    .dsh-td-chip svg { width: 14px; height: 14px; }

    @media (prefers-reduced-motion: reduce) { .dsh-seg { transition: none; } }
</style>
@endpush
