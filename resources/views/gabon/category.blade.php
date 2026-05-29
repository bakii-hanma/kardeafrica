@extends('layouts.app')

@section('title', $categoryName . ' — Carte Gabon')

@section('content')
<style>
    .gcat-head {
        background: linear-gradient(135deg, #0F172A 0%, #0F4F44 100%);
        color: white;
        padding: 40px 20px 28px;
    }
    .gcat-head-inner { max-width: 1180px; margin: 0 auto; }
    .gcat-back {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 12px; font-weight: 700; color: rgba(255,255,255,.7);
        text-decoration: none;
        margin-bottom: 10px;
    }
    .gcat-back:hover { color: #5EEAD4; }
    .gcat-back svg { width: 12px; height: 12px; }
    .gcat-head h1 {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 30px; font-weight: 800;
        margin: 0; letter-spacing: -0.02em;
    }
    .gcat-head p { margin: 6px 0 0; color: rgba(255,255,255,.7); font-size: 13px; }

    .gcat-cats {
        max-width: 1180px; margin: 18px auto -22px;
        padding: 0 20px; position: relative; z-index: 2;
    }
    .gcat-cats-inner {
        background: white;
        border-radius: 14px;
        padding: 12px;
        box-shadow: 0 12px 28px -8px rgba(15,23,42,.18);
        display: flex; gap: 6px; overflow-x: auto;
    }
    .gcat-cats-inner a {
        flex-shrink: 0;
        padding: 7px 14px;
        background: #F1F5F9;
        color: #334155;
        text-decoration: none;
        border-radius: 9999px;
        font-size: 12px; font-weight: 700;
        white-space: nowrap;
    }
    .gcat-cats-inner a.active {
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
    }

    .gcat-body { max-width: 1180px; margin: 40px auto; padding: 0 20px; }
    .gcat-grid {
        display: grid;
        gap: 18px;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
    .gcat-link { text-decoration: none; color: inherit; display: block; }
    .gcat-info { padding: 12px 4px 0; }
    .gcat-merch { font-size: 11px; font-weight: 700; color: #44A08D; }
    .gcat-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 15px; font-weight: 800; color: #0F172A;
        margin: 4px 0 0;
    }
    .gcat-empty {
        background: white;
        border-radius: 16px;
        padding: 60px 24px;
        text-align: center;
        border: 2px dashed #E2E8F0;
    }
    .gcat-empty p { color: #64748B; margin: 0; }
</style>

<section class="gcat-head">
    <div class="gcat-head-inner">
        <a href="{{ route('gabon.index') }}" class="gcat-back">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Retour à Carte Gabon
        </a>
        <h1>{{ $categoryName }}</h1>
        <p>{{ $cards->total() }} carte{{ $cards->total() > 1 ? 's' : '' }} disponible{{ $cards->total() > 1 ? 's' : '' }}</p>
    </div>
</section>

<div class="gcat-cats">
    <div class="gcat-cats-inner">
        <a href="{{ route('gabon.index') }}">Toutes</a>
        @foreach($categories as $slug => $label)
            <a href="{{ route('gabon.category', $slug) }}" class="{{ $slug === $categorySlug ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>
</div>

<div class="gcat-body">
    @if($cards->isEmpty())
        <div class="gcat-empty">
            <p>Aucune carte dans cette catégorie pour l'instant.</p>
        </div>
    @else
        <div class="gcat-grid">
            @foreach($cards as $card)
                <a href="{{ route('gabon.card', $card) }}" class="gcat-link">
                    @include('partials._merchant-card-visual', ['card' => $card])
                    <div class="gcat-info">
                        <span class="gcat-merch">KardAfrica</span>
                        <h3 class="gcat-title">{{ $card->name }}</h3>
                    </div>
                </a>
            @endforeach
        </div>
        <div style="margin-top:28px;">{{ $cards->links() }}</div>
    @endif
</div>
@endsection
