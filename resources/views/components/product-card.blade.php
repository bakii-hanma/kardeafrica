@props([
    'name'          => '',
    'brandLabel'    => null,       // Texte big sur le visuel (ex: "Netflix") — défaut: 1er mot de $name
    'logoUrl'       => null,       // Image officielle afrikard — utilisée en watermark sur le visuel
    'brandColor'    => '#1F2937',  // Fallback si la marque n'est pas dans le registre
    'price'         => 0,
    'currency'      => 'XAF',
    'href'          => '#',
    'badge'         => null,       // ['label' => 'Populaire', 'tone' => 'success'|'warm'|'teal']
    'discount'      => null,
    'compact'       => false,
    'productsCount' => null,
    'countryCode'   => null,       // 'FR', 'BE', 'EU', 'US' — pilote le drapeau + label région
])

@php
    $brandLabel = $brandLabel ?: explode(' ', $name)[0];
    $brandKey   = \App\Support\BrandStyle::detect($brandLabel ?: $name);
    $brandStyle = $brandKey
        ? \App\Support\BrandStyle::style($brandKey)
        : \App\Support\BrandStyle::fallback($brandColor ?: '#1F2937');
    [$flag, $regionLabel] = \App\Support\BrandStyle::region($countryCode);

    // Tones pour le badge "Populaire" / "Promo" / etc.
    $toneMap = [
        'success' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'warm'    => 'bg-amber-50 text-amber-700 border-amber-100',
        'teal'    => 'bg-teal-50 text-teal-700 border-teal-100',
        'slate'   => 'bg-slate-50 text-slate-700 border-slate-200',
    ];
    $badgeClasses = $toneMap[$badge['tone'] ?? 'teal'] ?? $toneMap['teal'];
@endphp

<a href="{{ $href }}"
   {{ $attributes->class([
       'group block overflow-hidden rounded-2xl bg-white border border-slate-100',
       'shadow-card hover:shadow-card-hover transition-all duration-300',
       'hover:-translate-y-1 hover:scale-[1.02] active:scale-[0.99]',
       'focus:outline-none focus-visible:ring-2 focus-visible:ring-[#44A08D]',
   ]) }}>

    {{-- ============================================================
         GIFT CARD VISUAL (= proposition hybride) — partial réutilisé
         ============================================================ --}}
    @include('partials._gift-card-visual', [
        'brandLabel'  => $brandLabel,
        'brandColor'  => $brandColor,
        'countryCode' => $countryCode,
        'currency'    => $currency,
        'compact'     => $compact,
        'logoUrl'     => $logoUrl,
    ])

    @unless($compact)
        {{-- ============================================================
             INFOS PRODUIT (nom, montants, prix)
             ============================================================ --}}
        <div class="relative bg-white p-3 sm:p-4">

            <div class="flex items-start justify-between gap-1.5 sm:gap-2">
                <h4 class="text-xs sm:text-sm font-semibold text-slate-900 leading-snug line-clamp-2 group-hover:text-[#44A08D] transition-colors">
                    {{ $name }}
                </h4>
                @if($badge)
                    <span class="shrink-0 inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold border {{ $badgeClasses }}">
                        {{ $badge['label'] }}
                    </span>
                @endif
            </div>

            @if($productsCount && $productsCount > 1)
                <p class="text-[10px] text-slate-400 mt-0.5 font-medium">
                    {{ $productsCount }} montants disponibles
                </p>
            @endif

            <div class="mt-2.5 sm:mt-3 pt-2.5 sm:pt-3 border-t border-slate-100 flex items-end justify-between gap-2">
                <span class="text-[10px] text-slate-400 font-medium">Dès</span>
                <span class="text-sm sm:text-base font-black tabular-nums text-slate-900 price-display whitespace-nowrap"
                      data-price="{{ $price }}"
                      data-currency="{{ $currency }}"
                      data-processed="true">
                    {{ \App\Support\Money::formatFcfa($price, $currency) }}
                </span>
            </div>

            @if($discount)
                <div class="absolute top-3 right-3 -translate-y-full bg-emerald-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-md shadow-md">
                    -{{ $discount }}%
                </div>
            @endif
        </div>
    @endunless
</a>

{{-- ============================================================
     STYLES — frame hybride (chargés une seule fois par page)
     ============================================================ --}}
@once
<style>
    /* Container */
    .gc-hybrid {
        position: relative;
        aspect-ratio: 1.586 / 1;            /* ratio carte bancaire */
        padding: 14px 16px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        font-family: 'Manrope', 'Inter', sans-serif;
    }
    @media (min-width: 640px) {
        .gc-hybrid { padding: 16px 18px; }
    }
    .gc-hybrid--compact { aspect-ratio: 1.586 / 1; padding: 12px 14px; }

    /* Glow par-dessus le gradient */
    .gc-glow { position: absolute; inset: 0; pointer-events: none; }

    /* Watermark : image officielle de la marque (logoUrl afrikard).
       Positionnée à droite/centre, contain, opacité gérée par inline style.
       z-index 0 = derrière tout sauf le gradient.
       mix-blend-mode pour mieux s'intégrer aux gradients sombres. */
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
    }
    .gc-frame-brand {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 9px; font-weight: 700;
        letter-spacing: 0.12em; text-transform: uppercase;
        opacity: 0.75;
    }
    .gc-frame-brand-mark {
        width: 12px; height: 12px;
        border-radius: 3px;
        background: currentColor;
        opacity: 0.85;
    }
    .gc-verified {
        display: inline-flex; align-items: center; gap: 3px;
        font-size: 9px; font-weight: 600;
        letter-spacing: 0.06em; text-transform: uppercase;
        opacity: 0.70;
    }

    /* ===== Logo + nom ===== */
    .gc-brand { position: relative; z-index: 2; }
    .gc-logo {
        width: 52px; height: 52px;
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
        font-size: 22px; font-weight: 700;
        letter-spacing: -0.02em; line-height: 1;
        margin-top: 2px;
    }
    @media (min-width: 640px) {
        .gc-logo { width: 56px; height: 56px; }
        .gc-name { font-size: 26px; }
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
</style>
@endonce
