@extends('vendor.layouts.vendor')

@section('title', 'Tableau de bord')

@php
    // Palette de statuts — inchangée, sémantique conservée (vert = livrée,
    // ambre = en attente, violet = remboursée).
    $statusMap = [
        'pending'    => ['#B45309', '#FEF3C7', 'En attente'],
        'processing' => ['#1D4ED8', '#DBEAFE', 'En livraison'],
        'completed'  => ['#047857', '#D1FAE5', 'Livrée'],
        'cancelled'  => ['#475569', '#E2E8F0', 'Annulée'],
        'failed'     => ['#BE123C', '#FEE2E2', 'Échec'],
        'refunding'  => ['#7C3AED', '#EDE9FE', 'Remb. en cours'],
        'refunded'   => ['#7C3AED', '#EDE9FE', 'Remboursée'],
    ];

    $cashToRemit = (float) $reseller->cash_to_remit;
    $walletPct   = min(100, max(0, $reseller->wallet_percentage));
    $tone        = $reseller->walletTone();          // ok | warn | danger
    // Teinte pour le TEXTE : plus sombre que la couleur de jauge, pour tenir le
    // contraste AA sur fond blanc (#44A08D n'y est qu'à 3,15).
    $toneColor   = ['ok' => '#0F766E', 'warn' => '#B45309', 'danger' => '#BE123C'][$tone];
    $toneFill    = [
        'ok'     => 'linear-gradient(90deg,#44A08D,#4ECDC4)',
        'warn'   => 'linear-gradient(90deg,#D97706,#F59E0B)',
        'danger' => 'linear-gradient(90deg,#BE123C,#F43F5E)',
    ][$tone];
    $fmt = fn ($n) => number_format((float) $n, 0, ',', ' ');
@endphp

@section('content')
<div class="vd-wrap">

    {{-- ============= 1. MÉTRIQUE HÉRO : SOLDE DE VENTE ============= --}}
    <section class="vd-hero" aria-labelledby="vd-hero-title">
        <div class="vd-hero-head">
            <div>
                <div class="vd-hero-eyebrow" id="vd-hero-title">Solde de vente</div>
                <div class="vd-hero-amount">{{ $fmt($reseller->wallet_balance) }} <span>FCFA</span></div>
            </div>
            <div class="vd-hero-pct" style="color:{{ $toneColor }};">
                {{ rtrim(rtrim(number_format($walletPct, 1, ',', ' '), '0'), ',') }} %
                <span class="vd-hero-pct-sub">de ta cagnotte</span>
            </div>
        </div>

        <div class="vd-hero-gauge" role="progressbar"
             aria-valuenow="{{ (int) round($walletPct) }}" aria-valuemin="0" aria-valuemax="100"
             aria-label="Solde de vente utilisé sur le plafond">
            <div class="vd-hero-gauge-fill" style="width:{{ $walletPct }}%;background:{{ $toneFill }};"></div>
        </div>

        {{-- Formulation en langage courant : « plafond » et « bloqués » ne
             parlaient pas aux vendeurs. On dit ce qu'ils peuvent faire, et
             pourquoi une partie de l'argent ne compte pas. --}}
        <div class="vd-hero-foot">
            @if((float) $reseller->wallet_locked > 0)
                <span class="vd-hero-usable">
                    Tu peux vendre pour <strong>{{ $fmt($reseller->available_balance) }} FCFA</strong> tout de suite
                </span>
                <span class="vd-hero-note">
                    {{ $fmt($reseller->wallet_locked) }} FCFA sont mis de côté le temps que tes ventes
                    encaissées en espèces soient confirmées.
                </span>
            @else
                <span class="vd-hero-usable">
                    Tu peux vendre pour <strong>{{ $fmt($reseller->wallet_balance) }} FCFA</strong> tout de suite
                </span>
            @endif
            <span class="vd-hero-note">
                Ta cagnotte peut monter jusqu'à {{ $fmt($reseller->max_wallet) }} FCFA.
                Elle se vide à chaque vente et se remplit quand tu la recharges.
            </span>
        </div>

        @if($tone !== 'ok')
            <a href="{{ route('vendor.wallet.recharge') }}" class="vd-hero-recharge" style="color:{{ $toneColor }};border-color:{{ $toneColor }}40;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Recharger ma cagnotte
            </a>
        @endif
    </section>

    {{-- ============= 2. ACTIONS RAPIDES ============= --}}
    <nav class="vd-actions" aria-label="Actions rapides">
        <a href="{{ route('vendor.sell') }}" class="vd-action vd-action--primary">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span>Vendre</span>
        </a>
        <a href="{{ route('vendor.cash.index') }}" class="vd-action">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2m2-6h-6a2 2 0 00-2 2v2a2 2 0 002 2h6a2 2 0 002-2v-2a2 2 0 00-2-2z"/></svg>
            <span>Encaisser</span>
        </a>
        <a href="{{ route('vendor.remittance.index') }}" class="vd-action">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            <span>Remettre</span>
            @if($cashToRemit > 0)
                <span class="vd-action-badge">{{ $fmt($cashToRemit) }}</span>
            @endif
        </a>
        {{-- Les cartes locales ne sont plus une destination à part : on les
             atteint par la bascule en tête de l'écran de vente. La 4e action
             rapide mène donc à l'historique des ventes. --}}
        <a href="{{ route('vendor.orders') }}" class="vd-action">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <span>Mes ventes</span>
        </a>
    </nav>

    {{-- ============= 3. ALERTES ACTIONNABLES ============= --}}
    @if(!empty($stats['alerts']))
        <div class="vd-todo">
            @foreach($stats['alerts'] as $alert)
                <a href="{{ $alert['url'] }}" class="vd-todo-row vd-todo-row--{{ $alert['tone'] }}">
                    <span class="vd-todo-dot" aria-hidden="true"></span>
                    <span class="vd-todo-label">{{ $alert['label'] }}</span>
                    <svg class="vd-todo-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            @endforeach
        </div>
    @endif

    {{-- ============= 4. KPIs SECONDAIRES ============= --}}
    <div class="vd-kpis">
        <a href="{{ route('vendor.orders') }}" class="vd-kpi">
            <span class="vd-kpi-label">Ventes du jour</span>
            <span class="vd-kpi-value">
                {{ $fmt($stats['volume_today']) }} <span class="vd-kpi-unit">FCFA</span>
                @if($stats['volume_trend'] !== null)
                    <span class="vd-kpi-trend {{ $stats['volume_trend'] >= 0 ? 'is-up' : 'is-down' }}">
                        {{ $stats['volume_trend'] >= 0 ? '+' : '' }}{{ $stats['volume_trend'] }} %
                    </span>
                @endif
            </span>
            <span class="vd-kpi-meta">
                {{ $stats['sales_today'] }} vente{{ $stats['sales_today'] > 1 ? 's' : '' }} aujourd'hui
                @if($stats['volume_trend'] !== null) · hier {{ $fmt($stats['volume_yesterday']) }} FCFA @endif
            </span>
        </a>

        <a href="{{ route('vendor.orders') }}" class="vd-kpi">
            <span class="vd-kpi-label">Commandes digitales</span>
            <span class="vd-kpi-value">{{ $stats['orders_total'] }}</span>
            <span class="vd-kpi-meta">
                {{ $stats['orders_awaiting'] }} en attente · {{ $stats['orders_delivering'] }} en livraison
            </span>
        </a>

        <a href="{{ route('vendor.orders', ['status' => 'completed']) }}" class="vd-kpi">
            <span class="vd-kpi-label">Volume cumulé</span>
            <span class="vd-kpi-value">{{ $fmt($stats['volume_total']) }} <span class="vd-kpi-unit">FCFA</span></span>
            <span class="vd-kpi-meta">livré depuis l'inscription, Carte Gabon incluse</span>
        </a>
    </div>

    {{-- ============= 5. GRAPHIQUES ============= --}}
    @php
        $serie      = $stats['daily_series'];
        $serieMax   = max(1, max(array_column($serie, 'amount')));
        $serieTotal = array_sum(array_column($serie, 'amount'));
        $split      = $stats['channel_split'];
        $brands     = $stats['top_brands'];
        $brandMax   = $brands ? max(array_column($brands, 'amount')) : 1;

        // Anneau : on ne dessine que ce qui existe. Un arc à 0 % laisserait
        // une amorce de trait visible et ferait croire à une vente.
        $pctDigital = $split['total'] > 0 ? $split['digital'] / $split['total'] * 100 : 0;
        $circ       = 2 * M_PI * 42;
    @endphp

    <section class="vd-charts" aria-labelledby="vd-charts-title">
        <h2 class="vd-charts-title" id="vd-charts-title">Ton activité en un coup d'œil</h2>

        <div class="vd-charts-grid">

            {{-- 5a. Rythme de vente sur 14 jours --}}
            <article class="vd-chart">
                <header class="vd-chart-head">
                    <h3 class="vd-chart-name">Ventes des 14 derniers jours</h3>
                    <span class="vd-chart-sum">{{ $fmt($serieTotal) }} <span>FCFA</span></span>
                </header>

                @if ($serieTotal <= 0)
                    <p class="vd-chart-empty">Aucune vente sur les 14 derniers jours. Ta première vente apparaîtra ici.</p>
                @else
                    <div class="vd-bars" role="img"
                         aria-label="Volume vendu jour par jour sur 14 jours, du {{ $serie[0]['label'] }} au {{ end($serie)['label'] }}. Meilleure journée : {{ $fmt($serieMax) }} FCFA.">
                        @foreach ($serie as $d)
                            @php $h = $d['amount'] > 0 ? max(4, round($d['amount'] / $serieMax * 100)) : 0; @endphp
                            <span class="vd-bar-col {{ $d['today'] ? 'is-today' : '' }}">
                                <span class="vd-bar-track">
                                    <span class="vd-bar-fill" style="height:{{ $h }}%;"
                                          title="{{ $d['label'] }} — {{ $fmt($d['amount']) }} FCFA"></span>
                                </span>
                                <span class="vd-bar-day">{{ $d['day'] }}</span>
                            </span>
                        @endforeach
                    </div>
                    <p class="vd-chart-foot">Meilleure journée&nbsp;: {{ $fmt($serieMax) }} FCFA</p>
                @endif
            </article>

            {{-- 5b. Ce qui porte le chiffre : digital ou comptoir --}}
            <article class="vd-chart">
                <header class="vd-chart-head">
                    <h3 class="vd-chart-name">Digital ou Carte Gabon</h3>
                </header>

                @if ($split['total'] <= 0)
                    <p class="vd-chart-empty">Rien à répartir pour l'instant&nbsp;: aucune vente livrée.</p>
                @else
                    <div class="vd-donut-wrap">
                        <svg class="vd-donut" viewBox="0 0 100 100" role="img"
                             aria-label="{{ round($pctDigital) }} % du volume vient des cartes digitales, {{ round(100 - $pctDigital) }} % de la Carte Gabon.">
                            <circle cx="50" cy="50" r="42" fill="none" stroke="#F59E0B" stroke-width="14"></circle>
                            @if ($pctDigital > 0)
                                <circle cx="50" cy="50" r="42" fill="none" stroke="#0F9E8E" stroke-width="14"
                                        stroke-dasharray="{{ round($circ * $pctDigital / 100, 2) }} {{ round($circ, 2) }}"
                                        transform="rotate(-90 50 50)" stroke-linecap="butt"></circle>
                            @endif
                            <text x="50" y="47" class="vd-donut-num">{{ round($pctDigital) }}%</text>
                            <text x="50" y="60" class="vd-donut-cap">digital</text>
                        </svg>

                        <ul class="vd-legend">
                            <li>
                                <span class="vd-dot" style="background:#0F9E8E;"></span>
                                <span class="vd-legend-name">Cartes digitales</span>
                                <span class="vd-legend-val">{{ $fmt($split['digital']) }} FCFA</span>
                            </li>
                            <li>
                                <span class="vd-dot" style="background:#F59E0B;"></span>
                                <span class="vd-legend-name">Carte Gabon</span>
                                <span class="vd-legend-val">{{ $fmt($split['local']) }} FCFA</span>
                            </li>
                        </ul>
                    </div>
                @endif
            </article>

            {{-- 5c. Ce qui se vend vraiment --}}
            <article class="vd-chart">
                <header class="vd-chart-head">
                    <h3 class="vd-chart-name">Tes meilleures ventes</h3>
                </header>

                @if (empty($brands))
                    <p class="vd-chart-empty">Le classement se remplira dès tes premières ventes livrées.</p>
                @else
                    <ul class="vd-rank">
                        @foreach ($brands as $b)
                            <li class="vd-rank-row">
                                {{-- Les noms de commerçants dépassent souvent la colonne : l'ellipse
                                     coupe l'affichage, le title garde l'information accessible. --}}
                                <span class="vd-rank-name" title="{{ $b['name'] }} — {{ $b['count'] }} vente{{ $b['count'] > 1 ? 's' : '' }}">{{ $b['name'] }}</span>
                                <span class="vd-rank-track">
                                    <span class="vd-rank-fill" style="width:{{ max(6, round($b['amount'] / $brandMax * 100)) }}%;"></span>
                                </span>
                                <span class="vd-rank-val">{{ $fmt($b['amount']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="vd-chart-foot">Montants en FCFA, livrés · digital et comptoir confondus</p>
                @endif
            </article>

        </div>
    </section>

    {{-- ============= 5. COMMANDES RÉCENTES ============= --}}
    <section class="vd-section" x-data="{ view: localStorage.getItem('vendor.recent.view') || 'list' }"
             x-init="$watch('view', v => localStorage.setItem('vendor.recent.view', v))">
        <div class="vd-section-head">
            <h2 class="vd-section-title">Commandes récentes</h2>
            <div class="vd-section-tools">
                @if($recentOrders->count() > 0)
                    {{-- Bascule liste/tableau — desktop seulement (mobile = cartes) --}}
                    <div class="vd-viewtoggle" role="group" aria-label="Affichage des commandes">
                        <button type="button" @click="view = 'list'" :aria-pressed="view === 'list'"
                                :class="view === 'list' ? 'is-on' : ''" title="Vue liste">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            <span class="vd-sr">Vue liste</span>
                        </button>
                        <button type="button" @click="view = 'table'" :aria-pressed="view === 'table'"
                                :class="view === 'table' ? 'is-on' : ''" title="Vue tableau">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 6h18M3 14h18M3 18h18"/></svg>
                            <span class="vd-sr">Vue tableau</span>
                        </button>
                    </div>
                    <a href="{{ route('vendor.orders') }}" class="vd-section-link">
                        Voir tout
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @endif
            </div>
        </div>

        @if($recentOrders->count() > 0)
            {{-- Cartes : seul affichage sur mobile, par défaut sur desktop --}}
            <div class="vd-orderlist" :class="view === 'table' ? 'vd-hide-desktop' : ''">
                @foreach($recentOrders as $o)
                    @php $st = $statusMap[$o->status] ?? ['#475569', '#E2E8F0', ucfirst($o->status)]; @endphp
                    <a href="{{ route('vendor.orders.show', $o) }}" class="vd-ordercard">
                        <div class="vd-ordercard-main">
                            <div class="vd-ordercard-l1">
                                <span class="vd-ordercard-customer">{{ $o->customer_name ?: 'Client' }}</span>
                                <span class="vd-status-pill" style="background:{{ $st[1] }};color:{{ $st[0] }};">{{ $st[2] }}</span>
                            </div>
                            <div class="vd-ordercard-l2">
                                <span class="vd-ordercard-num">#{{ $o->order_number }}</span>
                                <span aria-hidden="true">·</span>
                                <span>{{ $o->created_at->format('d/m H:i') }}</span>
                            </div>
                        </div>
                        <div class="vd-ordercard-amounts">
                            <span class="vd-ordercard-total">{{ $fmt($o->total_amount) }} <span>FCFA</span></span>
                            <span class="vd-ordercard-comm">+{{ $fmt($o->commission_earned) }}</span>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- Tableau : desktop uniquement, sur demande --}}
            <div class="vd-table-wrap vd-desktop-only" x-cloak x-show="view === 'table'">
                <table class="vd-table">
                    <thead>
                        <tr>
                            <th scope="col">Commande</th>
                            <th scope="col">Client</th>
                            <th scope="col">Total</th>
                            <th scope="col">Commission</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $o)
                            @php $st = $statusMap[$o->status] ?? ['#475569', '#E2E8F0', ucfirst($o->status)]; @endphp
                            <tr>
                                <td><a href="{{ route('vendor.orders.show', $o) }}" class="vd-table-num">#{{ $o->order_number }}</a></td>
                                <td>{{ $o->customer_name ?: 'Client' }}</td>
                                <td class="vd-table-amount">{{ $fmt($o->total_amount) }} <span>FCFA</span></td>
                                <td class="vd-table-comm">+{{ $fmt($o->commission_earned) }}</td>
                                <td><span class="vd-status-pill" style="background:{{ $st[1] }};color:{{ $st[0] }};">{{ $st[2] }}</span></td>
                                <td class="vd-table-date">{{ $o->created_at->format('d/m H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="vd-empty">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                <h3>Aucune commande pour le moment</h3>
                <p>Lance ta première vente pour voir l'activité ici.</p>
                <a href="{{ route('vendor.sell') }}" class="vd-empty-cta">
                    Nouvelle vente
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
            </div>
        @endif
    </section>
</div>

{{-- ============= 6. CTA FLOTTANT (mobile) ============= --}}
<a href="{{ route('vendor.sell') }}" class="vd-fab">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
    Nouvelle vente
</a>
@endsection

@push('head')
<style>
    .vd-wrap { max-width: 1200px; margin: 0 auto; }
    .vd-sr {
        position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
        overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0;
    }
    /* Focus visible homogène sur toute la page */
    .vd-wrap a:focus-visible, .vd-wrap button:focus-visible, .vd-fab:focus-visible {
        outline: 3px solid #4ECDC4; outline-offset: 2px; border-radius: 12px;
    }

    /* ========== 1. HÉRO SOLDE ========== */
    .vd-hero {
        background: #fff; border: 1px solid #E2E8F0; border-radius: 16px;
        padding: 18px; margin-bottom: 14px;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }
    @media (min-width: 768px) { .vd-hero { padding: 24px 26px; } }
    .vd-hero-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
    .vd-hero-eyebrow {
        font-size: 11px; font-weight: 800; letter-spacing: .12em;
        text-transform: uppercase; color: #64748B;
    }
    .vd-hero-amount {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 32px; font-weight: 800; color: #0F172A;
        line-height: 1.1; margin-top: 6px;
        font-variant-numeric: tabular-nums; letter-spacing: -0.02em;
    }
    .vd-hero-amount span { font-size: 15px; font-weight: 700; color: #64748B; }
    @media (min-width: 768px) { .vd-hero-amount { font-size: 40px; } }
    .vd-hero-pct {
        text-align: right; flex-shrink: 0;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 20px; font-weight: 800; font-variant-numeric: tabular-nums;
    }
    .vd-hero-pct-sub { display: block; font-size: 10.5px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: .08em; margin-top: 2px; }

    .vd-hero-gauge { height: 10px; border-radius: 9999px; background: #EEF2F6; overflow: hidden; margin-top: 16px; }
    .vd-hero-gauge-fill { height: 100%; border-radius: 9999px; transition: width .4s ease; }

    .vd-hero-foot { display: flex; flex-direction: column; gap: 4px; margin-top: 14px; }
    .vd-hero-usable {
        font-size: 14px; color: #334155; font-variant-numeric: tabular-nums;
    }
    .vd-hero-usable strong {
        font-family: 'Space Grotesk','Inter',sans-serif;
        color: #0F172A; font-weight: 800;
    }
    .vd-hero-note {
        font-size: 12.5px; color: #64748B; line-height: 1.45;
        font-variant-numeric: tabular-nums;
    }
    .vd-hero-recharge {
        display: inline-flex; align-items: center; gap: 7px;
        min-height: 44px; margin-top: 14px; padding: 0 16px;
        border: 1.5px solid; border-radius: 12px;
        font-size: 13.5px; font-weight: 700; text-decoration: none;
        background: #fff;
    }
    .vd-hero-recharge svg { width: 15px; height: 15px; }

    /* ========== 2. ACTIONS RAPIDES ========== */
    .vd-actions {
        display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;
        margin-bottom: 14px;
    }
    @media (min-width: 768px) { .vd-actions { grid-template-columns: repeat(4, 1fr); gap: 12px; } }
    .vd-action {
        position: relative;
        display: flex; align-items: center; justify-content: center; gap: 9px;
        min-height: 56px; padding: 0 12px;
        background: #fff; border: 1px solid #E2E8F0; border-radius: 14px;
        color: #0F172A; font-size: 14px; font-weight: 700; text-decoration: none;
        transition: border-color .15s ease, transform .1s ease;
    }
    .vd-action svg { width: 19px; height: 19px; color: #475569; flex-shrink: 0; }
    .vd-action:hover { border-color: #44A08D; }
    .vd-action:active { transform: scale(.98); }
    .vd-action--primary {
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        border-color: transparent; color: #fff;
        box-shadow: 0 8px 18px -8px rgba(78,205,196,.65);
    }
    .vd-action--primary svg { color: #fff; }
    .vd-action--primary:hover { border-color: transparent; filter: brightness(1.05); }
    .vd-action-badge {
        position: absolute; top: -7px; right: 8px;
        background: #BE123C; color: #fff;
        font-size: 10.5px; font-weight: 800; font-variant-numeric: tabular-nums;
        padding: 3px 8px; border-radius: 9999px;
        box-shadow: 0 2px 6px rgba(190,18,60,.35);
    }

    /* ========== 3. ALERTES ACTIONNABLES ========== */
    .vd-todo {
        display: flex; flex-direction: column; gap: 6px;
        margin-bottom: 14px;
    }
    .vd-todo-row {
        display: flex; align-items: center; gap: 10px;
        min-height: 48px; padding: 10px 14px;
        background: #fff; border: 1px solid #E2E8F0; border-left-width: 4px;
        border-radius: 12px;
        text-decoration: none; color: #0F172A;
        font-size: 13.5px; font-weight: 600;
        transition: border-color .15s ease;
    }
    .vd-todo-row--urgent { border-left-color: #BE123C; }
    .vd-todo-row--warn   { border-left-color: #D97706; }
    .vd-todo-row:hover { border-color: #CBD5E1; }
    .vd-todo-row--urgent:hover { border-left-color: #BE123C; }
    .vd-todo-row--warn:hover   { border-left-color: #D97706; }
    .vd-todo-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
    .vd-todo-row--urgent .vd-todo-dot { background: #BE123C; }
    .vd-todo-row--warn   .vd-todo-dot { background: #D97706; }
    .vd-todo-label { flex: 1; min-width: 0; }
    .vd-todo-arrow { width: 14px; height: 14px; color: #64748B; flex-shrink: 0; }

    /* ========== 4. KPIs ========== */
    /* ---------- Graphiques ---------- */
    .vd-charts { margin-bottom: 18px; }
    .vd-charts-title {
        font-size: 15px; font-weight: 800; color: #0F172A; margin: 0 0 10px;
    }
    .vd-charts-grid { display: grid; grid-template-columns: 1fr; gap: 12px; }
    @media (min-width: 640px) { .vd-charts-grid { grid-template-columns: repeat(2, 1fr); } }
    /* Le graphe temporel a besoin de largeur : il passe en pleine ligne. */
    @media (min-width: 640px) and (max-width: 1023px) { .vd-charts-grid > :first-child { grid-column: 1 / -1; } }
    @media (min-width: 1024px) { .vd-charts-grid { grid-template-columns: 1.5fr 1fr 1.2fr; } }

    .vd-chart {
        background: #fff; border: 1px solid #E7EBF0; border-radius: 16px;
        padding: 14px 16px 16px; display: flex; flex-direction: column;
    }
    .vd-chart-head {
        display: flex; align-items: baseline; justify-content: space-between;
        gap: 10px; margin-bottom: 12px;
    }
    .vd-chart-name {
        font-size: 12px; font-weight: 800; color: #475569;
        text-transform: uppercase; letter-spacing: .06em; margin: 0;
    }
    .vd-chart-sum { font-size: 15px; font-weight: 800; color: #0F172A; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .vd-chart-sum span { font-size: 11px; font-weight: 700; color: #64748B; }
    .vd-chart-foot { font-size: 11.5px; color: #64748B; margin: 10px 0 0; }
    .vd-chart-empty { font-size: 13px; color: #64748B; margin: 0; line-height: 1.5; }

    /* Barres jour par jour */
    .vd-bars { display: flex; align-items: flex-end; gap: 4px; height: 120px; }
    .vd-bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 5px; height: 100%; }
    .vd-bar-track {
        flex: 1; width: 100%; display: flex; align-items: flex-end;
        background: #F4F6F9; border-radius: 5px; overflow: hidden;
    }
    .vd-bar-fill {
        width: 100%; background: #0F9E8E; border-radius: 5px 5px 0 0;
        transition: height .45s cubic-bezier(.2,.8,.3,1);
    }
    .vd-bar-col.is-today .vd-bar-fill { background: #0B7F72; }
    .vd-bar-col.is-today .vd-bar-day { color: #0B7F72; font-weight: 800; }
    .vd-bar-day { font-size: 10px; color: #94A3B8; font-variant-numeric: tabular-nums; }

    /* Anneau digital / Carte Gabon */
    .vd-donut-wrap { display: flex; align-items: center; gap: 14px; }
    .vd-donut { width: 92px; height: 92px; flex: none; }
    .vd-donut-num { font-size: 20px; font-weight: 800; fill: #0F172A; text-anchor: middle; }
    .vd-donut-cap { font-size: 9px; font-weight: 700; fill: #64748B; text-anchor: middle; text-transform: uppercase; letter-spacing: .08em; }
    .vd-legend { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 10px; min-width: 0; }
    .vd-legend li { display: grid; grid-template-columns: auto 1fr; column-gap: 8px; align-items: center; }
    .vd-dot { width: 9px; height: 9px; border-radius: 3px; grid-row: 1 / 3; }
    .vd-legend-name { font-size: 12px; font-weight: 700; color: #475569; }
    .vd-legend-val { font-size: 13px; font-weight: 800; color: #0F172A; font-variant-numeric: tabular-nums; }

    /* Classement des marques */
    .vd-rank { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 9px; }
    .vd-rank-row { display: grid; grid-template-columns: minmax(0, 88px) 1fr auto; gap: 8px; align-items: center; }
    .vd-rank-name {
        font-size: 12px; font-weight: 700; color: #334155;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .vd-rank-track { height: 8px; background: #F4F6F9; border-radius: 9999px; overflow: hidden; }
    .vd-rank-fill {
        display: block; height: 100%; border-radius: 9999px; background: #0F9E8E;
        transition: width .45s cubic-bezier(.2,.8,.3,1);
    }
    .vd-rank-val { font-size: 12px; font-weight: 800; color: #0F172A; font-variant-numeric: tabular-nums; }

    @media (prefers-reduced-motion: reduce) {
        .vd-bar-fill, .vd-rank-fill { transition: none; }
    }

    .vd-kpis { display: grid; grid-template-columns: 1fr; gap: 10px; margin-bottom: 18px; }
    @media (min-width: 600px) { .vd-kpis { grid-template-columns: repeat(3, 1fr); gap: 12px; } }
    .vd-kpi {
        display: flex; flex-direction: column; gap: 5px;
        min-height: 84px; padding: 14px 16px;
        background: #fff; border: 1px solid #E2E8F0; border-radius: 14px;
        text-decoration: none; transition: border-color .15s ease;
    }
    .vd-kpi:hover { border-color: #CBD5E1; }
    .vd-kpi-label {
        font-size: 10.5px; font-weight: 800; letter-spacing: .1em;
        text-transform: uppercase; color: #64748B;
    }
    .vd-kpi-value {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 20px; font-weight: 800; color: #0F172A;
        font-variant-numeric: tabular-nums; letter-spacing: -0.01em;
    }
    .vd-kpi-unit { font-size: 12px; font-weight: 700; color: #64748B; }
    .vd-kpi-meta { font-size: 12px; color: #64748B; font-variant-numeric: tabular-nums; }
    .vd-kpi-trend {
        display: inline-block; margin-left: 6px;
        font-family: 'Inter', sans-serif;
        font-size: 11px; font-weight: 800;
        padding: 2px 7px; border-radius: 9999px;
        vertical-align: middle;
    }
    .vd-kpi-trend.is-up   { color: #047857; background: #D1FAE5; }
    .vd-kpi-trend.is-down { color: #BE123C; background: #FEE2E2; }

    /* ========== 5. COMMANDES ========== */
    .vd-section { margin-bottom: 18px; }
    .vd-section-head {
        display: flex; align-items: center; justify-content: space-between;
        gap: 12px; margin-bottom: 10px;
    }
    .vd-section-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 16px; font-weight: 800; color: #0F172A; margin: 0;
    }
    .vd-section-tools { display: flex; align-items: center; gap: 8px; }
    .vd-section-link {
        display: inline-flex; align-items: center; gap: 4px;
        min-height: 44px; padding: 0 6px;
        color: #0F766E; font-size: 13px; font-weight: 700; text-decoration: none;
    }
    .vd-section-link svg { width: 13px; height: 13px; }
    .vd-section-link:hover { color: #115E59; }

    .vd-viewtoggle { display: none; background: #EEF2F6; border-radius: 10px; padding: 3px; gap: 2px; }
    @media (min-width: 768px) { .vd-viewtoggle { display: inline-flex; } }
    .vd-viewtoggle button {
        display: inline-flex; align-items: center; justify-content: center;
        width: 36px; height: 32px; border: 0; border-radius: 8px;
        background: transparent; color: #64748B; cursor: pointer;
    }
    .vd-viewtoggle button svg { width: 16px; height: 16px; }
    .vd-viewtoggle button.is-on { background: #fff; color: #0F172A; box-shadow: 0 1px 3px rgba(15,23,42,.10); }

    .vd-orderlist { display: flex; flex-direction: column; gap: 8px; }
    .vd-ordercard {
        display: flex; align-items: center; gap: 12px;
        min-height: 64px; padding: 12px 14px;
        background: #fff; border: 1px solid #E2E8F0; border-radius: 14px;
        text-decoration: none; transition: border-color .15s ease;
    }
    .vd-ordercard:hover { border-color: #CBD5E1; }
    .vd-ordercard-main { flex: 1; min-width: 0; }
    .vd-ordercard-l1 { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .vd-ordercard-customer {
        font-size: 14px; font-weight: 700; color: #0F172A;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%;
    }
    .vd-ordercard-l2 {
        display: flex; align-items: center; gap: 6px; margin-top: 3px;
        font-size: 11.5px; color: #64748B; font-variant-numeric: tabular-nums;
    }
    .vd-ordercard-num {
        font-family: 'JetBrains Mono','Fira Code',monospace;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 150px;
    }
    .vd-ordercard-amounts { text-align: right; flex-shrink: 0; }
    .vd-ordercard-total {
        display: block;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 14.5px; font-weight: 800; color: #0F172A; font-variant-numeric: tabular-nums;
    }
    .vd-ordercard-total span { font-size: 10.5px; font-weight: 700; color: #64748B; }
    .vd-ordercard-comm { display: block; font-size: 11.5px; font-weight: 700; color: #0F766E; font-variant-numeric: tabular-nums; margin-top: 2px; }

    .vd-status-pill {
        display: inline-flex; align-items: center;
        padding: 3px 9px; border-radius: 9999px;
        font-size: 10px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase;
        white-space: nowrap;
    }

    /* Tableau — desktop uniquement */
    .vd-desktop-only { display: none; }
    @media (min-width: 768px) { .vd-desktop-only { display: block; } }
    @media (min-width: 768px) { .vd-hide-desktop { display: none; } }
    .vd-table-wrap { background: #fff; border: 1px solid #E2E8F0; border-radius: 14px; overflow-x: auto; }
    .vd-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .vd-table th {
        text-align: left; padding: 11px 14px;
        font-size: 10.5px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase;
        color: #64748B; background: #F8FAFC; border-bottom: 1px solid #E2E8F0; white-space: nowrap;
    }
    .vd-table td { padding: 12px 14px; border-bottom: 1px solid #F1F5F9; color: #334155; white-space: nowrap; }
    .vd-table tbody tr:last-child td { border-bottom: 0; }
    .vd-table tbody tr:hover { background: #F8FAFC; }
    .vd-table-num { font-family: 'JetBrains Mono','Fira Code',monospace; font-size: 11.5px; color: #0F766E; font-weight: 700; text-decoration: none; }
    .vd-table-amount { font-weight: 800; color: #0F172A; font-variant-numeric: tabular-nums; }
    .vd-table-amount span { font-size: 10px; color: #64748B; }
    .vd-table-comm { font-weight: 700; color: #0F766E; font-variant-numeric: tabular-nums; }
    .vd-table-date { color: #64748B; font-variant-numeric: tabular-nums; }

    /* État vide */
    .vd-empty {
        background: #fff; border: 1px solid #E2E8F0; border-radius: 16px;
        padding: 36px 20px; text-align: center;
    }
    .vd-empty svg { width: 40px; height: 40px; color: #CBD5E1; margin: 0 auto 12px; display: block; }
    .vd-empty h3 { font-family: 'Space Grotesk','Inter',sans-serif; font-size: 15px; font-weight: 800; color: #0F172A; margin: 0 0 6px; }
    .vd-empty p { font-size: 13px; color: #64748B; margin: 0 0 16px; }
    .vd-empty-cta {
        display: inline-flex; align-items: center; gap: 7px;
        min-height: 44px; padding: 0 20px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: #fff; border-radius: 12px;
        font-size: 14px; font-weight: 700; text-decoration: none;
    }
    .vd-empty-cta svg { width: 15px; height: 15px; color: #fff; margin: 0; }

    /* ========== 6. CTA FLOTTANT (mobile) ========== */
    .vd-fab {
        position: fixed; z-index: 55;
        left: 12px; right: 12px;
        /* au-dessus de la barre d'onglets (56px de tab + 2×8px de padding + marge) */
        bottom: calc(88px + env(safe-area-inset-bottom));
        display: flex; align-items: center; justify-content: center; gap: 8px;
        min-height: 52px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: #fff; border-radius: 14px;
        font-size: 15px; font-weight: 800; text-decoration: none;
        box-shadow: 0 12px 28px -8px rgba(78,205,196,.75), 0 2px 6px rgba(15,23,42,.12);
    }
    .vd-fab svg { width: 17px; height: 17px; }
    .vd-fab:active { transform: scale(.99); }
    @media (min-width: 768px) { .vd-fab { display: none; } }
    /* Réserve la place du CTA pour qu'il ne masque jamais la fin de page */
    @media (max-width: 767px) {
        .vd-wrap { padding-bottom: 64px; }
    }
</style>
@endpush
