{{--
    Partial : visuel hybride d'une gift card (= proposition design).
    Variables attendues (passées via @include) :
      - $brandLabel  : string  (ex: "Apple", "Roblox")
      - $brandColor  : string  (hex, ex: '#E50914') — fallback si marque inconnue
      - $countryCode : ?string (ex: 'FR', 'BE')
      - $currency    : ?string (ex: 'EUR') — affiché si ≠ XAF
      - $compact     : ?bool   (default: false) — padding réduit
      - $logoUrl     : ?string — image officielle afrikard, utilisée en background
                                 watermark (= remplit le rôle de "image de fond")

    Utilisé par :
      - resources/views/components/product-card.blade.php (catalogue)
      - resources/views/welcome.blade.php (hero card stack)
--}}
@php
    $_brandKey   = \App\Support\BrandStyle::detect($brandLabel ?? '');
    $_brandStyle = $_brandKey
        ? \App\Support\BrandStyle::style($_brandKey)
        : \App\Support\BrandStyle::fallback($brandColor ?? '#1F2937');
    [$_flag, $_regionLabel] = \App\Support\BrandStyle::region($countryCode ?? null);
    $_compact     = $compact ?? false;
    $_logoUrl     = $logoUrl ?? null;
    $_fill        = $fill ?? false;   // true = remplit le parent (hauteur+largeur),
                                      // utilisé par card-type.blade.php (3D flip card)
    // Si on a un logoUrl ET pas de SVG inline (= marque inconnue), on push
    // un peu plus l'opacité du watermark pour qu'il soit clairement visible.
    $_logoOpacity = !empty($_brandStyle['logo']) ? '0.18' : '0.40';
@endphp

<div class="gc-hybrid {{ $_compact ? 'gc-hybrid--compact' : '' }} {{ $_fill ? 'gc-hybrid--fill' : '' }}"
     style="background: {{ $_brandStyle['background'] }}; color: {{ $_brandStyle['text'] }};">

    {{-- Glow d'ambiance --}}
    @if(!empty($_brandStyle['glow']))
        <div class="gc-glow" style="background: {{ $_brandStyle['glow'] }};"></div>
    @endif

    {{-- Image officielle de la marque en watermark (= "image en fond")
         Sert pour TOUTES les marques (pas juste les 9 SVG hardcodés).
         Positionnée à droite, centrée verticalement, contain — ne déforme pas. --}}
    @if($_logoUrl)
        <div class="gc-bg-watermark" aria-hidden="true"
             style="background-image: url('{{ $_logoUrl }}'); opacity: {{ $_logoOpacity }};"></div>
    @endif

    {{-- Frame top : KardAfrica + ✓ Vérifié --}}
    <div class="gc-frame-top">
        <span class="gc-frame-brand">
            <span class="gc-frame-brand-mark"></span>
            KardAfrica
        </span>
        <span class="gc-verified">
            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            Vérifié
        </span>
    </div>

    {{-- Logo + nom de la marque --}}
    <div class="gc-brand">
        @if(!empty($_brandStyle['logo']))
            <div class="gc-logo">{!! $_brandStyle['logo'] !!}</div>
        @else
            <div class="gc-logo-fallback" style="background: {{ $_brandStyle['text'] }}; color: {{ $brandColor ?? '#1F2937' }};">
                {{ strtoupper(substr($brandLabel ?? '?', 0, 1)) }}
            </div>
        @endif
        <div class="gc-name">{{ $brandLabel ?? '' }}</div>
    </div>

    {{-- Chip doré --}}
    <div class="gc-chip" aria-hidden="true"></div>

    {{-- Région + drapeau --}}
    <div class="gc-bottom">
        <span class="gc-region">
            @if($_flag)<span class="gc-flag">{{ $_flag }}</span>@endif
            {{ $_regionLabel ?: ($countryCode ?? '') }}
            @if(!empty($currency) && !in_array(strtoupper($currency), ['XAF', 'XOF'], true))
                · {{ strtoupper($currency) }}
            @endif
        </span>
    </div>
</div>

{{-- ============================================================
     STYLES — chargés une seule fois par page (peu importe où le
     partial est inclus : product-card composant, card-type detail,
     welcome hero stack…)
     ============================================================ --}}
@once
<style>
    /* Container : remplit 100% width du parent, hauteur dérivée de l'aspect
       ratio 1.586/1 (ratio carte bancaire). Dans les contextes où le parent
       a déjà sa propre dimension (ex: card-type flip card en aspect-[1.55]),
       on bascule sur height:100% pour remplir intégralement.
       L'opt-out se fait via la classe modifier `.gc-hybrid--fill`. */
    .gc-hybrid {
        position: relative;
        width: 100%;
        aspect-ratio: 1.586 / 1;
        padding: 14px 16px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        font-family: 'Manrope', 'Inter', sans-serif;
        box-sizing: border-box;
    }
    /* Modifier : remplit le parent (hauteur+largeur), ignore aspect-ratio */
    .gc-hybrid--fill {
        height: 100%;
        aspect-ratio: auto;
    }

    @media (min-width: 640px) {
        .gc-hybrid { padding: 16px 18px; }
    }
    .gc-hybrid--compact { padding: 12px 14px; }
    @media (min-width: 1024px) {
        .gc-hybrid { padding: 22px 24px; }
    }

    /* Glow par-dessus le gradient */
    .gc-glow { position: absolute; inset: 0; pointer-events: none; }

    /* Watermark : image officielle afrikard */
    .gc-bg-watermark {
        position: absolute;
        inset: 0;
        background-repeat: no-repeat;
        background-position: 78% center;
        background-size: 55% auto;
        pointer-events: none;
        z-index: 0;
        filter: drop-shadow(0 2px 8px rgba(0,0,0,0.15));
    }

    /* ===== Frame top : KardAfrica + Vérifié ===== */
    .gc-frame-top {
        position: relative; z-index: 2;
        display: flex; justify-content: space-between; align-items: center;
        gap: 8px;
    }
    .gc-frame-brand {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 9px; font-weight: 700;
        letter-spacing: 0.12em; text-transform: uppercase;
        opacity: 0.85;
        white-space: nowrap;
    }
    .gc-frame-brand-mark {
        width: 12px; height: 12px;
        border-radius: 3px;
        background: currentColor;
        opacity: 0.85;
        flex-shrink: 0;
    }
    .gc-verified {
        display: inline-flex; align-items: center; gap: 3px;
        font-size: 9px; font-weight: 600;
        letter-spacing: 0.06em; text-transform: uppercase;
        opacity: 0.75;
        white-space: nowrap;
    }
    /* Sur grandes cards (card-type detail), le frame est plus gros */
    @media (min-width: 768px) {
        .gc-hybrid:not(.gc-hybrid--compact) .gc-frame-brand,
        .gc-hybrid:not(.gc-hybrid--compact) .gc-verified { font-size: 10px; }
        .gc-hybrid:not(.gc-hybrid--compact) .gc-frame-brand-mark { width: 14px; height: 14px; }
    }

    /* ===== Logo + nom ===== */
    .gc-brand { position: relative; z-index: 2; }
    .gc-logo {
        width: 48px; height: 48px;
        margin-bottom: 4px;
        display: flex; align-items: center; justify-content: center;
    }
    .gc-logo svg { width: 100%; height: 100%; display: block; }
    .gc-logo-fallback {
        width: 44px; height: 44px; border-radius: 12px;
        display: grid; place-items: center;
        font-family: 'Bricolage Grotesque', serif;
        font-size: 22px; font-weight: 800; letter-spacing: -0.04em;
        margin-bottom: 4px;
    }
    .gc-name {
        font-family: 'Bricolage Grotesque', 'Inter', serif;
        font-size: 18px; font-weight: 700;
        letter-spacing: -0.02em; line-height: 1;
        margin-top: 2px;
        max-width: 65%;             /* laisse de la place au chip */
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    @media (min-width: 640px) {
        .gc-logo { width: 56px; height: 56px; }
        .gc-name { font-size: 22px; }
    }
    /* Sur card-type detail (grand format) */
    @media (min-width: 768px) {
        .gc-hybrid:not(.gc-hybrid--compact) .gc-logo { width: 64px; height: 64px; }
        .gc-hybrid:not(.gc-hybrid--compact) .gc-name { font-size: 28px; }
    }

    /* ===== Chip doré ===== */
    .gc-chip {
        position: absolute; right: 14px; top: 50%;
        transform: translateY(-50%);
        width: 34px; height: 26px;
        background: linear-gradient(135deg, #f4d77a, #c89b3a);
        border-radius: 4px;
        box-shadow: inset 0 0 0 1px rgba(0,0,0,0.12);
        z-index: 1;
    }
    .gc-chip::before, .gc-chip::after {
        content: ''; position: absolute;
        left: 4px; right: 4px; height: 1px;
        background: rgba(0,0,0,0.18);
    }
    .gc-chip::before { top: 7px; }
    .gc-chip::after  { top: 14px; }
    @media (min-width: 640px) {
        .gc-chip { right: 16px; width: 38px; height: 28px; }
    }
    @media (min-width: 768px) {
        .gc-hybrid:not(.gc-hybrid--compact) .gc-chip {
            right: 22px; width: 46px; height: 34px;
        }
    }

    /* ===== Région + drapeau ===== */
    .gc-bottom {
        position: relative; z-index: 2;
        display: flex; align-items: center; justify-content: space-between;
    }
    .gc-region {
        font-size: 11px; font-weight: 500; opacity: 0.85;
        display: inline-flex; align-items: center;
    }
    .gc-flag { font-size: 14px; margin-right: 5px; }
    @media (min-width: 640px) {
        .gc-region { font-size: 13px; }
    }
    @media (min-width: 768px) {
        .gc-hybrid:not(.gc-hybrid--compact) .gc-region { font-size: 14px; }
    }
</style>
@endonce
