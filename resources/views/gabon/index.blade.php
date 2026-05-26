@extends('layouts.app')

@section('title', 'Carte Gabon — Cartes-cadeau des marchands gabonais')

@section('content')
<style>
    /* ============== Hero ============== */
    .gabon-hero {
        position: relative;
        background: linear-gradient(135deg, #0F172A 0%, #1E293B 45%, #0F4F44 100%);
        color: white;
        padding: 56px 20px 72px;
        overflow: hidden;
    }
    .gabon-hero::before {
        content: ''; position: absolute;
        top: -30%; right: -10%;
        width: 540px; height: 540px;
        background: radial-gradient(circle, rgba(94,234,212,.20), transparent 60%);
        pointer-events: none;
    }
    .gabon-hero::after {
        content: ''; position: absolute;
        bottom: -40%; left: -5%;
        width: 420px; height: 420px;
        background: radial-gradient(circle, rgba(68,160,141,.18), transparent 60%);
        pointer-events: none;
    }
    .gabon-hero-inner {
        max-width: 1180px; margin: 0 auto;
        position: relative;
    }
    .gabon-hero-tag {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 12px;
        background: rgba(94,234,212,.18);
        border: 1px solid rgba(94,234,212,.30);
        border-radius: 9999px;
        font-size: 10px; font-weight: 800;
        color: #5EEAD4;
        letter-spacing: .14em;
        text-transform: uppercase;
        margin-bottom: 14px;
    }
    .gabon-hero-tag::before {
        content: ''; display: inline-block;
        width: 6px; height: 6px;
        background: #5EEAD4; border-radius: 50%;
        animation: pulse 2s ease-in-out infinite;
    }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
    .gabon-hero h1 {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 34px; font-weight: 800;
        letter-spacing: -0.03em;
        line-height: 1.1;
        margin: 0 0 14px;
        max-width: 720px;
    }
    @media (min-width:768px) { .gabon-hero h1 { font-size: 48px; } }
    .gabon-hero p {
        font-size: 16px; line-height: 1.6;
        color: rgba(255,255,255,.78);
        max-width: 620px; margin: 0 0 28px;
    }
    .gabon-hero-stats {
        display: flex; flex-wrap: wrap; gap: 20px;
        font-size: 12px; color: rgba(255,255,255,.7);
    }
    .gabon-hero-stats strong {
        display: block; font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 22px; color: white; font-weight: 800;
        font-variant-numeric: tabular-nums;
    }

    /* ============== Search bar ============== */
    .gabon-search {
        margin-top: 28px;
        background: white;
        border-radius: 16px;
        padding: 8px;
        display: flex; gap: 6px;
        box-shadow: 0 24px 48px -16px rgba(0,0,0,.4);
        max-width: 720px;
    }
    .gabon-search input, .gabon-search select {
        flex: 1; padding: 12px 14px;
        font-size: 14px; color: #0F172A;
        border: 0; border-radius: 10px;
        background: #F8FAFC;
        font-family: inherit;
    }
    .gabon-search select { flex: 0 0 130px; }
    .gabon-search input:focus, .gabon-search select:focus {
        outline: 0; background: white;
        box-shadow: 0 0 0 2px #44A08D;
    }
    .gabon-search button {
        padding: 12px 22px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white; font-weight: 800; font-size: 13px;
        border: 0; border-radius: 10px; cursor: pointer;
        display: inline-flex; align-items: center; gap: 6px;
        box-shadow: 0 6px 14px -4px rgba(78,205,196,.5),
                    inset 0 1px 0 rgba(255,255,255,.25);
    }
    .gabon-search button svg { width: 14px; height: 14px; }
    @media (max-width:600px) {
        .gabon-search { flex-direction: column; }
        .gabon-search select { flex: 1; }
    }

    /* ============== Categories scroller ============== */
    .gabon-cat-wrap {
        max-width: 1180px; margin: -36px auto 0;
        padding: 0 20px; position: relative; z-index: 2;
    }
    .gabon-cat-card {
        background: white;
        border-radius: 18px;
        padding: 18px;
        box-shadow: 0 14px 32px -10px rgba(15,23,42,.18),
                    0 0 0 1px rgba(15,23,42,.04);
    }
    .gabon-cat-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 11px; font-weight: 800; color: #64748B;
        text-transform: uppercase; letter-spacing: .10em;
        margin: 0 0 12px;
    }
    .gabon-cats {
        display: flex; gap: 8px;
        overflow-x: auto;
        scrollbar-width: none;          /* Firefox */
        -ms-overflow-style: none;       /* IE / Edge legacy */
    }
    .gabon-cats::-webkit-scrollbar { display: none; } /* Chrome / Safari / new Edge */
    .gabon-cat {
        flex-shrink: 0;
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 16px;
        background: #F1F5F9;
        color: #334155;
        text-decoration: none;
        border-radius: 9999px;
        font-size: 12px; font-weight: 700;
        white-space: nowrap;
        transition: background .15s, color .15s;
        border: 1px solid transparent;
    }
    .gabon-cat:hover {
        background: #ECFDF5; color: #0F4F44;
        border-color: #BBF7D0;
    }
    .gabon-cat--active {
        background: linear-gradient(135deg, #44A08D, #4ECDC4) !important;
        color: white !important;
        border-color: transparent !important;
    }

    /* ============== Section header ============== */
    .gabon-section {
        max-width: 1180px; margin: 48px auto 0;
        padding: 0 20px;
    }
    .gabon-section-head {
        display: flex; align-items: flex-end; justify-content: space-between;
        gap: 16px; flex-wrap: wrap;
        margin-bottom: 18px;
    }
    .gabon-section-head h2 {
        margin: 0;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 22px; font-weight: 800; color: #0F172A;
        letter-spacing: -0.02em;
    }
    .gabon-section-head p {
        margin: 4px 0 0; color: #64748B; font-size: 13px;
    }
    .gabon-section-head a {
        color: #44A08D; font-size: 13px; font-weight: 700;
        text-decoration: none;
        display: inline-flex; align-items: center; gap: 4px;
    }

    /* ============== Featured merchants ============== */
    .gabon-merchants {
        display: grid;
        gap: 14px;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    }
    .gabon-merchant-card {
        display: block;
        background: white;
        border-radius: 14px;
        padding: 16px;
        text-decoration: none;
        color: inherit;
        box-shadow: 0 4px 14px -6px rgba(15,23,42,.08),
                    0 0 0 1px rgba(15,23,42,.04);
        transition: transform .15s, box-shadow .15s;
        text-align: center;
    }
    .gabon-merchant-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px -6px rgba(15,23,42,.15);
    }
    .gabon-merchant-avatar {
        display: inline-flex; align-items: center; justify-content: center;
        width: 60px; height: 60px;
        border-radius: 18px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800; font-size: 22px;
        margin: 0 auto 10px;
        box-shadow: 0 6px 14px -4px rgba(78,205,196,.40);
    }
    .gabon-merchant-name {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 13px; font-weight: 800; color: #0F172A;
        line-height: 1.25;
        margin: 0 0 4px;
    }
    .gabon-merchant-city {
        font-size: 11px; color: #64748B;
        display: inline-flex; align-items: center; gap: 3px;
    }
    .gabon-merchant-city svg { width: 10px; height: 10px; color: #94A3B8; }
    .gabon-merchant-count {
        display: inline-block;
        margin-top: 8px;
        padding: 3px 9px;
        background: #ECFDF5;
        color: #0F4F44;
        border-radius: 9999px;
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: .06em;
    }

    /* ============== Cards grid ============== */
    .gabon-grid {
        display: grid;
        gap: 18px;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
    .gabon-card {
        display: block;
        text-decoration: none;
        color: inherit;
    }
    .gabon-card-info {
        padding: 12px 4px 0;
    }
    .gabon-card-merchant {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 11px; font-weight: 700; color: #44A08D;
        letter-spacing: .04em;
    }
    .gabon-card-merchant svg { width: 11px; height: 11px; }
    .gabon-card-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 15px; font-weight: 800; color: #0F172A;
        margin: 4px 0 2px;
        line-height: 1.3;
    }
    .gabon-card-city {
        font-size: 11px; color: #94A3B8;
    }

    /* ============== Empty state ============== */
    .gabon-empty {
        background: white;
        border-radius: 16px;
        padding: 60px 24px;
        text-align: center;
        border: 2px dashed #E2E8F0;
    }
    .gabon-empty-icon {
        display: inline-flex; align-items: center; justify-content: center;
        width: 72px; height: 72px;
        border-radius: 50%;
        background: linear-gradient(135deg,#ECFDF5,#D1FAE5);
        color: #44A08D;
        margin-bottom: 16px;
    }
    .gabon-empty-icon svg { width: 36px; height: 36px; }
    .gabon-empty h3 {
        margin: 0 0 6px;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 18px; font-weight: 800; color: #0F172A;
    }
    .gabon-empty p { margin: 0; color: #64748B; font-size: 14px; }

    /* ============== Pagination ============== */
    .gabon-pagination { margin: 28px 0 60px; }
</style>

{{-- ============ HERO ============ --}}
<section class="gabon-hero">
    <div class="gabon-hero-inner">
        <span class="gabon-hero-tag">Carte Gabon · Cartes-cadeau locales</span>
        <h1>Offre une carte-cadeau d'un commerçant gabonais.</h1>
        <p>Restaurants, mode, beauté, spa, services… Choisis un marchand près de toi et envoie une carte-cadeau par SMS, WhatsApp ou email en quelques secondes.</p>

        <form method="GET" action="{{ route('gabon.index') }}" class="gabon-search">
            <input type="text" name="q" value="{{ $currentSearch }}" placeholder="Restaurant, boutique, salon…">
            <select name="city">
                <option value="">Toutes les villes</option>
                @foreach($cities as $c)
                    <option value="{{ $c }}" {{ $currentCity === $c ? 'selected' : '' }}>{{ $c }}</option>
                @endforeach
            </select>
            <button type="submit">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Rechercher
            </button>
        </form>

        <div class="gabon-hero-stats" style="margin-top:32px;">
            <div>
                <strong>{{ $cards->total() }}+</strong>
                Cartes disponibles
            </div>
            <div>
                <strong>{{ $featuredMerchants->count() }}+</strong>
                Marchands validés
            </div>
            <div>
                <strong>{{ $cities->count() }}+</strong>
                Villes couvertes
            </div>
        </div>
    </div>
</section>

{{-- ============ CATEGORIES SCROLLER ============ --}}
<div class="gabon-cat-wrap">
    <div class="gabon-cat-card">
        <p class="gabon-cat-title">Explore par catégorie</p>
        <div class="gabon-cats">
            <a href="{{ route('gabon.index') }}" class="gabon-cat gabon-cat--active">Toutes</a>
            @foreach($categories as $slug => $label)
                <a href="{{ route('gabon.category', $slug) }}" class="gabon-cat">{{ $label }}</a>
            @endforeach
        </div>
    </div>
</div>

{{-- ============ FEATURED MERCHANTS ============ --}}
@if($featuredMerchants->isNotEmpty())
    <section class="gabon-section">
        <div class="gabon-section-head">
            <div>
                <h2>Marchands en vedette</h2>
                <p>Les commerces partenaires qui proposent leurs cartes-cadeau.</p>
            </div>
        </div>
        <div class="gabon-merchants">
            @foreach($featuredMerchants as $m)
                <a href="{{ route('gabon.merchant', $m->slug) }}" class="gabon-merchant-card">
                    <div class="gabon-merchant-avatar">
                        {{ strtoupper(substr($m->business_name ?? $m->name, 0, 1)) }}
                    </div>
                    <h3 class="gabon-merchant-name">{{ $m->business_name ?? $m->name }}</h3>
                    @if($m->city)
                        <span class="gabon-merchant-city">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ $m->city }}
                        </span>
                    @endif
                    <div>
                        <span class="gabon-merchant-count">{{ $m->merchant_cards_count }} carte{{ $m->merchant_cards_count > 1 ? 's' : '' }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endif

{{-- ============ CARDS GRID ============ --}}
<section class="gabon-section" id="cards">
    <div class="gabon-section-head">
        <div>
            <h2>
                @if($currentSearch || $currentCity)
                    Résultats
                @else
                    Toutes les cartes
                @endif
            </h2>
            <p>{{ $cards->total() }} carte{{ $cards->total() > 1 ? 's' : '' }} disponible{{ $cards->total() > 1 ? 's' : '' }}.</p>
        </div>
    </div>

    @if($cards->isEmpty())
        <div class="gabon-empty">
            <div class="gabon-empty-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <h3>Aucune carte ne correspond</h3>
            <p>Essaie une autre catégorie ou une autre ville.</p>
        </div>
    @else
        <div class="gabon-grid">
            @foreach($cards as $card)
                <a href="{{ route('gabon.card', $card) }}" class="gabon-card">
                    @include('partials._merchant-card-visual', ['card' => $card])
                    <div class="gabon-card-info">
                        <span class="gabon-card-merchant">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            {{ $card->reseller->business_name ?? $card->reseller->name }}
                        </span>
                        <h3 class="gabon-card-title">{{ $card->name }}</h3>
                        @if($card->reseller->city)
                            <span class="gabon-card-city">{{ $card->reseller->city }}</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <div class="gabon-pagination">
            {{ $cards->links() }}
        </div>
    @endif
</section>
@endsection
