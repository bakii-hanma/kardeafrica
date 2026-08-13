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
    // Valeur faciale nominale dans la devise d'origine (ex: "100 €"), affichée
    // sur le visuel après « · EUR ». Null pour XAF/XOF (déjà en FCFA).
    $_faceLabel   = \App\Support\Money::formatOriginal($faceValue ?? 0, $currency ?? null);
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
        {{-- Le badge seul n'apportait rien : l'infobulle dit ce qu'il garantit. --}}
        <span class="gc-verified" title="Code testé et fournisseur validé par KardAfrica"
              aria-label="Vérifié : code testé et fournisseur validé par KardAfrica">
            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
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

    {{-- Région + drapeau + valeur faciale --}}
    <div class="gc-bottom">
        <span class="gc-region">
            @if($_flag)<span class="gc-flag">{{ $_flag }}</span>@endif
            {{ $_regionLabel ?: ($countryCode ?? '') }}
            @if(!empty($currency) && !in_array(strtoupper($currency), ['XAF', 'XOF'], true))
                · {{ strtoupper($currency) }}
            @endif
        </span>
        @if($_faceLabel)
            <span class="gc-value">{{ $_faceLabel }}</span>
        @endif
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
        padding: 8px 12px;
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
        .gc-hybrid--fill { padding: 22px 24px; }
    }

    /* Glow par-dessus le gradient */
    .gc-glow { position: absolute; inset: 0; pointer-events: none; }

    /* Watermark : image officielle afrikard */
    .gc-bg-watermark {
        position: absolute;
        inset: 0;
        background-repeat: no-repeat;
        /* Logo CENTRÉ et plus grand : il était calé à droite, ce qui donnait
           des vignettes de composition différente d'une marque à l'autre selon
           la forme du logo. Centré, la structure est identique partout. */
        background-position: center center;
        background-size: 46% auto;
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
    /* Sur grandes cards (card-type detail --fill), le frame est plus gros */
    @media (min-width: 768px) {
        .gc-hybrid--fill .gc-frame-brand,
        .gc-hybrid--fill .gc-verified { font-size: 10px; }
        .gc-hybrid--fill .gc-frame-brand-mark { width: 14px; height: 14px; }
    }

    /* ===== Logo + nom ===== */
    .gc-brand { position: relative; z-index: 2; }
    .gc-logo {
        width: 34px; height: 34px;
        margin-bottom: 2px;
        display: flex; align-items: center; justify-content: center;
    }
    .gc-logo svg { width: 100%; height: 100%; display: block; }
    .gc-logo-fallback {
        width: 32px; height: 32px; border-radius: 9px;
        display: grid; place-items: center;
        font-family: 'Bricolage Grotesque', serif;
        font-size: 17px; font-weight: 800; letter-spacing: -0.04em;
        margin-bottom: 2px;
    }
    .gc-name {
        font-family: 'Bricolage Grotesque', 'Inter', serif;
        font-size: 15px; font-weight: 700;
        letter-spacing: -0.02em; line-height: 1.05;
        margin-top: 0;
        max-width: 72%;             /* laisse de la place au chip */
        /* Mobile : 1 ligne (le nom complet est dans le bloc infos sous la carte,
           2 lignes feraient déborder la ligne région + valeur hors du visuel).
           ≥640 px : jusqu'à 2 lignes. */
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    @media (min-width: 640px) {
        .gc-logo { width: 56px; height: 56px; }
        .gc-name { font-size: 22px; -webkit-line-clamp: 2; }
    }
    /* Sur card-type detail (grand format) — UNIQUEMENT la grande carte (--fill),
       pas les cartes de grille (sinon logo/nom trop hauts → la ligne
       région + valeur déborde hors du visuel). */
    @media (min-width: 768px) {
        .gc-hybrid--fill .gc-logo { width: 64px; height: 64px; }
        .gc-hybrid--fill .gc-name { font-size: 28px; }
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
        .gc-hybrid--fill .gc-chip {
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
        .gc-hybrid--fill .gc-region { font-size: 14px; }
    }

    /* ===== Valeur faciale (pilule verre dépoli, lisible sur tout fond) ===== */
    .gc-value {
        flex-shrink: 0;
        display: inline-flex; align-items: center;
        padding: 2px 7px;
        font-size: 10.5px; font-weight: 800;
        letter-spacing: -0.01em;
        line-height: 1;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.22);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.35);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        text-shadow: 0 1px 2px rgba(0,0,0,0.25);
        white-space: nowrap;
    }
    @media (min-width: 640px) {
        .gc-value { font-size: 13px; padding: 4px 11px; }
    }
    @media (min-width: 768px) {
        .gc-hybrid:not(.gc-hybrid--compact) .gc-value { font-size: 15px; padding: 5px 13px; }
    }
</style>
@endonce
