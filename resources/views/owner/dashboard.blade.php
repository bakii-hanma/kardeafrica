@extends('owner.layouts.owner')

@section('title', 'Tableau de bord')
@section('page-title', 'Bonjour, ' . $owner->contact_name . ' 👋')
@section('page-subtitle', $owner->business_name . ($owner->city ? ' · ' . $owner->city : ''))

@section('topbar-actions')
    <a href="{{ route('owner.scan') }}" style="display:inline-flex;align-items:center;gap:6px;padding:10px 16px;background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;border-radius:11px;font-size:13px;font-weight:700;text-decoration:none;box-shadow:0 10px 24px -10px rgba(68,160,141,.5);">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
        Scanner une carte
    </a>
@endsection

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', ' ');

    $serie    = $stats['daily'];
    $serieMax = max(1, max(array_column($serie, 'amount')));
    $topMax   = $stats['top_cards'] ? max(array_column($stats['top_cards'], 'amount')) : 1;
@endphp

{{-- ============ 1. CE QUE KARDAFRICA TE DOIT ============
     Le héros est le solde à recevoir, pas « les revenus générés ». L'ancien
     écran mettait en avant ce que le CLIENT avait payé — un montant que le
     commerçant ne touche jamais et sur lequel il ne peut rien décider. --}}
<section class="od-hero" aria-labelledby="od-hero-title">
    <div class="od-hero-head">
        <div>
            <div class="od-hero-eyebrow" id="od-hero-title">À recevoir de KardAfrica</div>
            <div class="od-hero-amount">{{ $fmt($stats['outstanding']) }} <span>FCFA</span></div>
        </div>
        @if ($stats['redeemed_today'] > 0)
            <div class="od-hero-today">
                <span class="od-hero-today-n">+{{ $fmt($stats['redeemed_today']) }}</span>
                <span class="od-hero-today-l">servi aujourd'hui</span>
            </div>
        @endif
    </div>

    <div class="od-hero-foot">
        <span>Exigible&nbsp;: <strong>{{ $fmt($stats['due_net']) }} FCFA</strong></span>
        <span>Déjà versé&nbsp;: <strong>{{ $fmt($stats['settled']) }} FCFA</strong></span>
    </div>
</section>

{{-- Échéance : un versement par semaine, le lundi suivant l'achat. Sans cette
     ligne, le commerçant ne peut pas savoir quand l'argent arrive — la question
     qu'il se pose en premier. --}}
<section class="od-next">
    <div>
        <div class="od-next-label">Prochain versement · lundi {{ $stats['next_payout']->format('d/m') }}</div>
        <div class="od-next-amount">{{ $fmt($stats['upcoming_net']) }} <span>FCFA</span></div>
    </div>
    <p class="od-next-note">
        Tes ventes de la semaine en cours. Chaque achat est réglé le lundi qui suit,
        que le client soit déjà passé ou non.
    </p>
</section>

{{-- ============ 2. CE QUE TU DOIS ENCORE SERVIR ============
     Une dette, pas un revenu. L'ancien écran l'affichait dans le même langage
     visuel que les montants gagnés. --}}
@if ($stats['liability'] > 0)
    <section class="od-liab">
        <div>
            <div class="od-liab-label">Marchandise à servir</div>
            <div class="od-liab-amount">{{ $fmt($stats['liability']) }} <span>FCFA</span></div>
        </div>
        <p class="od-liab-note">
            Des clients détiennent des cartes non consommées. Ce montant n'est pas un gain&nbsp;:
            c'est ce que tu leur dois encore en produits ou services.
        </p>
    </section>
@endif

{{-- ============ 3. KPI SECONDAIRES ============ --}}
<div class="od-kpis">
    <a href="{{ route('owner.history') }}" class="od-kpi">
        <span class="od-kpi-label">Validations au comptoir</span>
        <span class="od-kpi-value">{{ $stats['redeem_count'] }}</span>
        <span class="od-kpi-meta">
            @if ($stats['redeem_today'] > 0)
                dont {{ $stats['redeem_today'] }} aujourd'hui
            @else
                aucune aujourd'hui
            @endif
        </span>
    </a>
    <a href="{{ route('owner.cards') }}" class="od-kpi">
        <span class="od-kpi-label">Cartes publiées</span>
        <span class="od-kpi-value">{{ $stats['cards_active'] }}<span class="od-kpi-unit">/ {{ $stats['cards_total'] }}</span></span>
        <span class="od-kpi-meta">{{ $stats['sales_count'] }} vendue{{ $stats['sales_count'] > 1 ? 's' : '' }} au total</span>
    </a>
    <div class="od-kpi">
        <span class="od-kpi-label">Encaissé par KardAfrica</span>
        <span class="od-kpi-value">{{ $fmt($stats['gross_sold']) }} <span class="od-kpi-unit">FCFA</span></span>
        <span class="od-kpi-meta">payé par tes clients · tu en gardes 85 %</span>
    </div>
</div>

{{-- ============ 4. GRAPHIQUES ============ --}}
<section class="od-charts" aria-labelledby="od-charts-title">
    <h2 class="od-charts-title" id="od-charts-title">Ton comptoir en un coup d'œil</h2>

    <div class="od-charts-grid">
        <article class="od-chart">
            <header class="od-chart-head">
                <h3 class="od-chart-name">Servi sur 14 jours</h3>
                <span class="od-chart-sum">{{ $fmt(array_sum(array_column($serie, 'amount'))) }} <span>FCFA</span></span>
            </header>

            @if (array_sum(array_column($serie, 'amount')) <= 0)
                <p class="od-chart-empty">Aucune carte validée sur les 14 derniers jours.</p>
            @else
                <div class="od-bars" role="img"
                     aria-label="Montant servi au comptoir jour par jour sur 14 jours. Meilleure journée : {{ $fmt($serieMax) }} FCFA.">
                    @foreach ($serie as $d)
                        @php $h = $d['amount'] > 0 ? max(4, round($d['amount'] / $serieMax * 100)) : 0; @endphp
                        <span class="od-bar-col {{ $d['today'] ? 'is-today' : '' }}">
                            <span class="od-bar-track">
                                <span class="od-bar-fill" style="height:{{ $h }}%;"
                                      title="{{ $d['label'] }} — {{ $fmt($d['amount']) }} FCFA"></span>
                            </span>
                            <span class="od-bar-day">{{ $d['day'] }}</span>
                        </span>
                    @endforeach
                </div>
                <p class="od-chart-foot">Meilleure journée&nbsp;: {{ $fmt($serieMax) }} FCFA</p>
            @endif
        </article>

        <article class="od-chart">
            <header class="od-chart-head">
                <h3 class="od-chart-name">Tes cartes qui tournent</h3>
            </header>

            @if (empty($stats['top_cards']))
                <p class="od-chart-empty">Le classement se remplira dès les premières validations.</p>
            @else
                <ul class="od-rank">
                    @foreach ($stats['top_cards'] as $c)
                        <li class="od-rank-row">
                            <span class="od-rank-name" title="{{ $c['name'] }} — {{ $c['count'] }} validation{{ $c['count'] > 1 ? 's' : '' }}">{{ $c['name'] }}</span>
                            <span class="od-rank-track">
                                <span class="od-rank-fill" style="width:{{ max(6, round($c['amount'] / $topMax * 100)) }}%;"></span>
                            </span>
                            <span class="od-rank-val">{{ $fmt($c['amount']) }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="od-chart-foot">Mesuré sur ce qui est servi, pas sur ce qui est vendu</p>
            @endif
        </article>
    </div>
</section>

{{-- ============ 5. DERNIÈRES VALIDATIONS ============ --}}
<section class="od-section">
    <div class="od-section-head">
        <h2 class="od-section-title">Dernières validations</h2>
        <a href="{{ route('owner.history') }}" class="od-section-link">Tout voir →</a>
    </div>

    @if ($recentRedemptions->isEmpty())
        <p class="od-empty">Aucune carte validée pour l'instant. Utilise « Scanner une carte » quand un client se présente.</p>
    @else
        <ul class="od-list">
            @foreach ($recentRedemptions as $r)
                <li class="od-row">
                    <div class="od-row-main">
                        <span class="od-row-name">{{ $r->purchase?->merchantCard?->name ?? 'Carte' }}</span>
                        <span class="od-row-meta">{{ $r->redeemed_at?->format('d/m/Y à H:i') }}</span>
                    </div>
                    <span class="od-row-amount">−{{ $fmt($r->amount_used) }} <span>FCFA</span></span>
                </li>
            @endforeach
        </ul>
    @endif
</section>

{{-- ============ 6. REVERSEMENTS ============ --}}
<section class="od-section">
    <div class="od-section-head">
        <h2 class="od-section-title">Reversements reçus</h2>
    </div>

    @if ($settlements->isEmpty())
        <p class="od-empty">
            Aucun versement enregistré. Ton solde à recevoir est de
            <strong>{{ $fmt($stats['outstanding']) }} FCFA</strong>.
        </p>
    @else
        <ul class="od-list">
            @foreach ($settlements as $s)
                <li class="od-row">
                    <div class="od-row-main">
                        <span class="od-row-name">{{ $s->methodLabel() }}</span>
                        <span class="od-row-meta">
                            {{ $s->settled_at?->format('d/m/Y') }}
                            @if ($s->reference) · réf. {{ $s->reference }} @endif
                        </span>
                    </div>
                    <span class="od-row-amount od-row-amount--in">+{{ $fmt($s->amount) }} <span>FCFA</span></span>
                </li>
            @endforeach
        </ul>
    @endif
</section>
@endsection

@push('head')
<style>
    /* ---------- Héros : le solde à recevoir ---------- */
    .od-hero {
        background: linear-gradient(135deg, #0F172A, #0F4F44);
        color: #fff; border-radius: 18px; padding: 20px 22px; margin-bottom: 14px;
        box-shadow: 0 12px 30px -14px rgba(15,79,68,.6);
    }
    .od-hero-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; flex-wrap: wrap; }
    .od-hero-eyebrow {
        font-size: 10.5px; font-weight: 800; text-transform: uppercase;
        letter-spacing: .10em; color: rgba(255,255,255,.72);
    }
    .od-hero-amount {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 34px; font-weight: 800; line-height: 1.1; margin-top: 6px;
        font-variant-numeric: tabular-nums;
    }
    .od-hero-amount span { font-size: 14px; font-weight: 700; color: rgba(255,255,255,.7); }
    @media (min-width: 768px) { .od-hero-amount { font-size: 40px; } }
    .od-hero-today { text-align: right; }
    .od-hero-today-n {
        display: block; font-size: 18px; font-weight: 800; color: #6EE7B7;
        font-variant-numeric: tabular-nums;
    }
    .od-hero-today-l { font-size: 10.5px; font-weight: 700; color: rgba(255,255,255,.65); text-transform: uppercase; letter-spacing: .06em; }
    .od-hero-foot {
        display: flex; flex-direction: column; gap: 4px; margin-top: 16px;
        padding-top: 14px; border-top: 1px solid rgba(255,255,255,.14);
        font-size: 12.5px; color: rgba(255,255,255,.78);
    }
    @media (min-width: 600px) { .od-hero-foot { flex-direction: row; justify-content: space-between; } }
    .od-hero-foot strong { color: #fff; font-variant-numeric: tabular-nums; }

    /* ---------- Prochaine échéance ---------- */
    .od-next {
        background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 16px;
        padding: 14px 16px; margin-bottom: 12px;
    }
    .od-next-label {
        font-size: 10.5px; font-weight: 800; text-transform: uppercase;
        letter-spacing: .08em; color: #047857;
    }
    .od-next-amount {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 22px; font-weight: 800; color: #065F46;
        font-variant-numeric: tabular-nums; margin-top: 2px;
    }
    .od-next-amount span { font-size: 12px; font-weight: 700; color: #047857; }
    .od-next-note { font-size: 12px; color: #065F46; line-height: 1.55; margin: 8px 0 0; }

    /* ---------- Engagement : une dette, pas un gain ---------- */
    .od-liab {
        background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 16px;
        padding: 14px 16px; margin-bottom: 18px;
    }
    .od-liab-label {
        font-size: 10.5px; font-weight: 800; text-transform: uppercase;
        letter-spacing: .08em; color: #B45309;
    }
    .od-liab-amount {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 22px; font-weight: 800; color: #92400E;
        font-variant-numeric: tabular-nums; margin-top: 2px;
    }
    .od-liab-amount span { font-size: 12px; font-weight: 700; color: #B45309; }
    .od-liab-note { font-size: 12px; color: #92400E; line-height: 1.55; margin: 8px 0 0; }

    /* ---------- KPI secondaires ---------- */
    .od-kpis { display: grid; grid-template-columns: 1fr; gap: 10px; margin-bottom: 20px; }
    @media (min-width: 600px) { .od-kpis { grid-template-columns: repeat(3, 1fr); gap: 12px; } }
    .od-kpi {
        display: flex; flex-direction: column; gap: 4px;
        background: #fff; border: 1px solid #E7EBF0; border-radius: 14px;
        padding: 14px 16px; text-decoration: none; color: inherit;
    }
    a.od-kpi:hover { border-color: #CBD5E1; }
    .od-kpi-label {
        font-size: 10.5px; font-weight: 800; text-transform: uppercase;
        letter-spacing: .07em; color: #64748B;
    }
    .od-kpi-value {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 22px; font-weight: 800; color: #0F172A; font-variant-numeric: tabular-nums;
    }
    .od-kpi-unit { font-size: 12px; font-weight: 700; color: #94A3B8; margin-left: 4px; }
    .od-kpi-meta { font-size: 11.5px; color: #64748B; }

    /* ---------- Graphiques ---------- */
    .od-charts { margin-bottom: 20px; }
    .od-charts-title { font-size: 15px; font-weight: 800; color: #0F172A; margin: 0 0 10px; }
    .od-charts-grid { display: grid; grid-template-columns: 1fr; gap: 12px; }
    @media (min-width: 900px) { .od-charts-grid { grid-template-columns: 1.6fr 1fr; } }
    .od-chart {
        background: #fff; border: 1px solid #E7EBF0; border-radius: 16px;
        padding: 14px 16px 16px;
    }
    .od-chart-head { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
    .od-chart-name {
        font-size: 12px; font-weight: 800; color: #475569;
        text-transform: uppercase; letter-spacing: .06em; margin: 0;
    }
    .od-chart-sum { font-size: 15px; font-weight: 800; color: #0F172A; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .od-chart-sum span { font-size: 11px; font-weight: 700; color: #64748B; }
    .od-chart-foot { font-size: 11.5px; color: #64748B; margin: 10px 0 0; }
    .od-chart-empty { font-size: 13px; color: #64748B; margin: 0; line-height: 1.5; }

    .od-bars { display: flex; align-items: flex-end; gap: 4px; height: 120px; }
    .od-bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 5px; height: 100%; }
    .od-bar-track { flex: 1; width: 100%; display: flex; align-items: flex-end; background: #F4F6F9; border-radius: 5px; overflow: hidden; }
    .od-bar-fill { width: 100%; background: #0F9E8E; border-radius: 5px 5px 0 0; transition: height .45s cubic-bezier(.2,.8,.3,1); }
    .od-bar-col.is-today .od-bar-fill { background: #0B7F72; }
    .od-bar-col.is-today .od-bar-day { color: #0B7F72; font-weight: 800; }
    .od-bar-day { font-size: 10px; color: #94A3B8; font-variant-numeric: tabular-nums; }

    .od-rank { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 9px; }
    .od-rank-row { display: grid; grid-template-columns: minmax(0, 96px) 1fr auto; gap: 8px; align-items: center; }
    .od-rank-name { font-size: 12px; font-weight: 700; color: #334155; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .od-rank-track { height: 8px; background: #F4F6F9; border-radius: 9999px; overflow: hidden; }
    .od-rank-fill { display: block; height: 100%; border-radius: 9999px; background: #0F9E8E; transition: width .45s cubic-bezier(.2,.8,.3,1); }
    .od-rank-val { font-size: 12px; font-weight: 800; color: #0F172A; font-variant-numeric: tabular-nums; }

    /* ---------- Listes ---------- */
    .od-section { margin-bottom: 20px; }
    .od-section-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
    .od-section-title { font-size: 15px; font-weight: 800; color: #0F172A; margin: 0; }
    .od-section-link { font-size: 12.5px; font-weight: 700; color: #0F9E8E; text-decoration: none; }
    .od-empty { font-size: 13px; color: #64748B; line-height: 1.6; margin: 0; }
    .od-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 8px; }
    .od-row {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        background: #fff; border: 1px solid #E7EBF0; border-radius: 12px; padding: 12px 14px;
    }
    .od-row-main { min-width: 0; }
    .od-row-name { display: block; font-size: 13.5px; font-weight: 700; color: #0F172A; }
    .od-row-meta { display: block; font-size: 11.5px; color: #64748B; margin-top: 2px; }
    .od-row-amount { font-size: 14px; font-weight: 800; color: #B45309; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .od-row-amount--in { color: #047857; }
    .od-row-amount span { font-size: 10px; font-weight: 700; color: #94A3B8; }

    @media (prefers-reduced-motion: reduce) {
        .od-bar-fill, .od-rank-fill { transition: none; }
    }
</style>
@endpush
