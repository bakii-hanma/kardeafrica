{{--
    Partial : visuel d'une carte-cadeau MARCHAND (Carte Gabon).
    Mirroir du _gift-card-visual.blade.php (= boutique), mais pour les cartes
    créées par les marchands locaux. Différence clé :
      - boutique : marque connue (Netflix, Steam, …) + logo officiel + flag pays
      - gabon    : marchand local + image uploadée + ville

    Variables attendues :
      - $card : App\Models\MerchantCard (avec reseller chargé)
      - $compact : ?bool (default false)
--}}
@php
    $_card    = $card;
    $_visual  = $_card->visual_url ? asset($_card->visual_url) : null;
    // Cartes catalogue admin → pas de marchand attaché. Sinon (legacy), affiche le nom.
    $_biz     = $_card->reseller?->business_name ?? $_card->reseller?->name ?? null;
    $_city    = $_card->reseller?->city ?? null;
    $_compact = $compact ?? false;
    $_fill    = $fill ?? false; // true = remplit le parent (utilisé par le flip card sur /gabon/carte/{id})
@endphp

<div class="mcv {{ $_compact ? 'mcv--compact' : '' }} {{ $_fill ? 'mcv--fill' : '' }}">
    {{-- Background : image marchand ou dégradé fallback --}}
    @if($_visual)
        <div class="mcv-bg" style="background-image: url('{{ $_visual }}');"></div>
    @else
        <div class="mcv-bg mcv-bg--fallback"></div>
    @endif
    <div class="mcv-overlay"></div>

    {{-- Frame top : KARDAFRICA + ✓ Vérifié (= identique au boutique) --}}
    <div class="mcv-frame-top">
        <span class="mcv-frame-brand">
            <span class="mcv-frame-mark"></span>
            KardAfrica
        </span>
        <span class="mcv-verified">
            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            Vérifié
        </span>
    </div>

    {{-- Center : marchand BIG si présent, sinon nom de la carte (catalogue admin) --}}
    <div class="mcv-brand">
        <div class="mcv-brand-name">{{ $_biz ?? $_card->name }}</div>
    </div>

    {{-- Chip doré décoratif (= identique au boutique) --}}
    <div class="mcv-chip" aria-hidden="true"></div>

    {{-- Bottom : ville si marchand, sinon "Gabon · Carte locale" pour le catalogue admin --}}
    <div class="mcv-bottom">
        <span class="mcv-region">
            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            {{ $_city ?? 'Gabon' }}
            · {{ $_biz ? 'Marchand local' : 'Carte locale' }}
        </span>
    </div>
</div>

@once
<style>
    .mcv {
        position: relative;
        aspect-ratio: 1.55;
        overflow: hidden;
        color: white;
        background: linear-gradient(135deg, #0F172A 0%, #0F4F44 100%);
    }
    /* fill mode : remplit le parent qui gère lui-même son aspect-ratio (flip card) */
    .mcv--fill {
        aspect-ratio: auto;
        width: 100%; height: 100%;
    }
    .mcv-bg {
        position: absolute; inset: 0;
        background-size: cover; background-position: center;
        transition: transform .4s;
    }
    a:hover .mcv-bg, .group:hover .mcv-bg { transform: scale(1.05); }
    .mcv-bg--fallback {
        background:
            radial-gradient(circle at 25% 25%, rgba(78,205,196,.35) 0%, transparent 55%),
            radial-gradient(circle at 80% 75%, rgba(68,160,141,.25) 0%, transparent 55%),
            linear-gradient(135deg, #0F172A 0%, #1E293B 60%, #0F4F44 100%);
    }
    .mcv-overlay {
        position: absolute; inset: 0;
        background: linear-gradient(160deg,
            rgba(15,23,42,.10) 0%,
            rgba(15,23,42,.30) 45%,
            rgba(15,23,42,.75) 100%);
        pointer-events: none;
    }

    /* Frame top */
    .mcv-frame-top {
        position: absolute; top: 10px; left: 12px; right: 12px;
        display: flex; align-items: center; justify-content: space-between;
        z-index: 2;
    }
    .mcv-frame-brand {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 8px 3px 6px;
        background: rgba(255,255,255,.16);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border-radius: 9999px;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800; font-size: 9px;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: white;
    }
    .mcv-frame-mark {
        display: inline-block; width: 7px; height: 7px;
        background: linear-gradient(135deg, #4ECDC4, #44A08D);
        border-radius: 2px;
    }
    .mcv-verified {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 8px 3px 6px;
        background: rgba(16,185,129,.85);
        border-radius: 9999px;
        font-size: 9px; font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: white;
        backdrop-filter: blur(8px);
    }

    /* Center brand block */
    .mcv-brand {
        position: absolute;
        top: 50%; left: 14px; right: 14px;
        transform: translateY(-50%);
        z-index: 2;
    }
    .mcv-brand-name {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 22px; font-weight: 800;
        line-height: 1.05;
        color: white;
        text-shadow: 0 2px 8px rgba(0,0,0,.55);
        letter-spacing: -0.02em;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        display: -webkit-box;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .mcv--compact .mcv-brand-name { font-size: 18px; }
    @media (max-width: 640px) { .mcv-brand-name { font-size: 16px; } }

    /* Chip décoratif (= boutique) */
    .mcv-chip {
        position: absolute;
        top: 50%; right: 14px;
        transform: translateY(-50%);
        width: 38px; height: 28px;
        background: linear-gradient(135deg, #D4AF37 0%, #F4D77E 40%, #B8860B 100%);
        border-radius: 6px;
        box-shadow: inset 0 1px 2px rgba(255,255,255,.40),
                    inset 0 -1px 2px rgba(0,0,0,.20),
                    0 2px 6px rgba(0,0,0,.30);
        z-index: 2;
    }
    .mcv-chip::before, .mcv-chip::after {
        content: '';
        position: absolute;
        left: 6px; right: 6px;
        height: 1px;
        background: rgba(0,0,0,.25);
    }
    .mcv-chip::before  { top: 8px; }
    .mcv-chip::after   { bottom: 8px; }
    .mcv--compact .mcv-chip { width: 30px; height: 22px; right: 12px; }
    @media (max-width: 640px) { .mcv-chip { width: 28px; height: 20px; right: 10px; } }

    /* Bottom region */
    .mcv-bottom {
        position: absolute; bottom: 11px; left: 14px; right: 14px;
        z-index: 2;
    }
    .mcv-region {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 4px 9px;
        background: rgba(0,0,0,.40);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 9999px;
        font-size: 10px; font-weight: 700;
        color: rgba(255,255,255,.92);
        letter-spacing: .04em;
    }
    .mcv-region svg { color: rgba(255,255,255,.7); }
</style>
@endonce
