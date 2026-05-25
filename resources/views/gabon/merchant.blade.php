@extends('layouts.app')

@section('title', ($merchant->business_name ?? $merchant->name) . ' — Carte Gabon')

@section('content')
<style>
    /* ====== Merchant banner ====== */
    .merchant-banner {
        position: relative;
        background:
            linear-gradient(135deg, rgba(15,23,42,.88), rgba(15,79,68,.92)),
            url('{{ $merchant->cover_url ? asset($merchant->cover_url) : '' }}') center/cover;
        color: white;
        padding: 48px 20px 64px;
        overflow: hidden;
    }
    .merchant-banner::after {
        content: ''; position: absolute;
        bottom: -50%; right: -10%;
        width: 460px; height: 460px;
        background: radial-gradient(circle, rgba(94,234,212,.18), transparent 60%);
        pointer-events: none;
    }
    .merchant-banner-inner {
        max-width: 1180px; margin: 0 auto;
        position: relative; z-index: 2;
        display: flex; align-items: center; gap: 24px;
        flex-wrap: wrap;
    }
    .merchant-back {
        display: inline-flex; align-items: center; gap: 6px;
        color: rgba(255,255,255,.7);
        font-size: 12px; font-weight: 700;
        text-decoration: none;
        margin-bottom: 16px;
        letter-spacing: .04em;
    }
    .merchant-back:hover { color: #5EEAD4; }
    .merchant-back svg { width: 12px; height: 12px; }

    .merchant-avatar {
        flex-shrink: 0;
        width: 96px; height: 96px;
        border-radius: 24px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        display: inline-flex; align-items: center; justify-content: center;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800; font-size: 38px;
        box-shadow: 0 14px 28px -8px rgba(0,0,0,.4),
                    inset 0 1px 0 rgba(255,255,255,.30);
    }
    .merchant-avatar img {
        width: 100%; height: 100%;
        object-fit: cover;
        border-radius: 24px;
    }
    .merchant-meta { flex: 1; min-width: 280px; }
    .merchant-tag {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 10px;
        background: rgba(16,185,129,.20);
        border: 1px solid rgba(16,185,129,.30);
        color: #5EEAD4;
        border-radius: 9999px;
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: .08em;
        margin-bottom: 10px;
    }
    .merchant-tag svg { width: 11px; height: 11px; }
    .merchant-name {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 28px; font-weight: 800;
        letter-spacing: -0.02em;
        margin: 0 0 8px;
    }
    @media (min-width:640px) { .merchant-name { font-size: 36px; } }
    .merchant-info {
        display: flex; gap: 18px; flex-wrap: wrap;
        font-size: 13px; color: rgba(255,255,255,.75);
    }
    .merchant-info span {
        display: inline-flex; align-items: center; gap: 5px;
    }
    .merchant-info svg { width: 13px; height: 13px; color: rgba(255,255,255,.6); }

    /* ====== Actions bar ====== */
    .merchant-actions {
        max-width: 1180px; margin: -32px auto 0;
        padding: 0 20px;
        position: relative; z-index: 3;
        display: flex; gap: 10px; flex-wrap: wrap;
    }
    .merchant-action {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 11px 18px;
        background: white;
        color: #0F172A;
        border-radius: 12px;
        font-size: 13px; font-weight: 700;
        text-decoration: none;
        box-shadow: 0 6px 16px -4px rgba(15,23,42,.18),
                    0 0 0 1px rgba(15,23,42,.04);
        transition: transform .15s;
    }
    .merchant-action:hover { transform: translateY(-1px); }
    .merchant-action svg { width: 14px; height: 14px; }
    .merchant-action--wa {
        background: linear-gradient(135deg, #25D366, #128C7E);
        color: white;
    }

    /* ====== Description ====== */
    .merchant-body {
        max-width: 1180px; margin: 28px auto;
        padding: 0 20px;
    }
    .merchant-desc {
        background: white;
        border-radius: 16px;
        padding: 20px 22px;
        margin-bottom: 28px;
        box-shadow: 0 4px 14px -6px rgba(15,23,42,.08),
                    0 0 0 1px rgba(15,23,42,.04);
        font-size: 14px; line-height: 1.6;
        color: #334155;
    }

    /* ====== Cards grid ====== */
    .merchant-cards-head {
        display: flex; align-items: flex-end; justify-content: space-between;
        margin-bottom: 16px; flex-wrap: wrap; gap: 12px;
    }
    .merchant-cards-head h2 {
        margin: 0;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 22px; font-weight: 800; color: #0F172A;
        letter-spacing: -0.02em;
    }
    .merchant-cards-head p { margin: 4px 0 0; color: #64748B; font-size: 13px; }

    .merchant-grid {
        display: grid;
        gap: 18px;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
    .merchant-card-link {
        display: block;
        text-decoration: none;
        color: inherit;
    }
    .merchant-card-info {
        padding: 12px 4px 0;
    }
    .merchant-card-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 15px; font-weight: 800; color: #0F172A;
        margin: 0 0 2px; line-height: 1.3;
    }
    .merchant-card-cat {
        font-size: 11px; font-weight: 700; color: #44A08D;
        text-transform: uppercase; letter-spacing: .06em;
    }

    /* ====== Empty ====== */
    .merchant-empty {
        background: white;
        border-radius: 16px;
        padding: 60px 24px;
        text-align: center;
        border: 2px dashed #E2E8F0;
    }
    .merchant-empty p { color: #64748B; margin: 0; }
</style>

{{-- ============ BANNER ============ --}}
<section class="merchant-banner">
    <div class="merchant-banner-inner" style="flex-direction:column;align-items:flex-start;">
        <a href="{{ route('gabon.index') }}" class="merchant-back">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Retour à Carte Gabon
        </a>
    </div>
    <div class="merchant-banner-inner">
        <div class="merchant-avatar">
            @if($merchant->logo_url)
                <img src="{{ asset($merchant->logo_url) }}" alt="{{ $merchant->business_name }}">
            @else
                {{ strtoupper(substr($merchant->business_name ?? $merchant->name, 0, 1)) }}
            @endif
        </div>
        <div class="merchant-meta">
            <div class="merchant-tag">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Marchand vérifié
            </div>
            <h1 class="merchant-name">{{ $merchant->business_name ?? $merchant->name }}</h1>
            <div class="merchant-info">
                @if(isset($categories[$merchant->business_type]))
                    <span>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        {{ $categories[$merchant->business_type] }}
                    </span>
                @endif
                @if($merchant->city)
                    <span>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $merchant->city }}@if($merchant->province), {{ $merchant->province }}@endif
                    </span>
                @endif
                <span>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    {{ $cards->count() }} carte{{ $cards->count() > 1 ? 's' : '' }}
                </span>
            </div>
        </div>
    </div>
</section>

{{-- ============ ACTIONS ============ --}}
<div class="merchant-actions">
    @if($merchant->whatsapp_number)
        <a href="https://wa.me/{{ preg_replace('/\D/', '', $merchant->whatsapp_number) }}" target="_blank" rel="noopener" class="merchant-action merchant-action--wa">
            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 018.413 3.488 11.824 11.824 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 001.99 5.93l-.999 3.648 3.498-1.277zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg>
            WhatsApp
        </a>
    @endif
    @if($merchant->address)
        <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($merchant->address . ', ' . $merchant->city) }}" target="_blank" rel="noopener" class="merchant-action">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Itinéraire
        </a>
    @endif
    @if($merchant->phone)
        <a href="tel:{{ $merchant->phone }}" class="merchant-action">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            Appeler
        </a>
    @endif
</div>

{{-- ============ BODY ============ --}}
<div class="merchant-body">
    @if($merchant->description)
        <div class="merchant-desc">{{ $merchant->description }}</div>
    @endif

    <div class="merchant-cards-head">
        <div>
            <h2>Cartes-cadeau disponibles</h2>
            <p>Choisis le montant et envoie en quelques secondes.</p>
        </div>
    </div>

    @if($cards->isEmpty())
        <div class="merchant-empty">
            <p>Ce marchand n'a pas encore de carte-cadeau publiée.</p>
        </div>
    @else
        <div class="merchant-grid">
            @foreach($cards as $card)
                <a href="{{ route('gabon.card', $card) }}" class="merchant-card-link">
                    @include('partials._merchant-card-visual', ['card' => $card])
                    <div class="merchant-card-info">
                        @if(isset($categories[$card->category]))
                            <span class="merchant-card-cat">{{ $categories[$card->category] }}</span>
                        @endif
                        <h3 class="merchant-card-title">{{ $card->name }}</h3>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
