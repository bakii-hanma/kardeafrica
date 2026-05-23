{{--
    Partial : visuel hybride d'une gift card (= proposition design).
    Variables attendues (passées via @include) :
      - $brandLabel  : string  (ex: "Apple", "Roblox")
      - $brandColor  : string  (hex, ex: '#E50914') — fallback si marque inconnue
      - $countryCode : ?string (ex: 'FR', 'BE')
      - $currency    : ?string (ex: 'EUR') — affiché si ≠ XAF
      - $compact     : ?bool   (default: false) — padding réduit
      - $hashId      : ?string (default: md5(brand)) — pour différencier les SVG ids

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
    $_compact = $compact ?? false;
@endphp

<div class="gc-hybrid {{ $_compact ? 'gc-hybrid--compact' : '' }}"
     style="background: {{ $_brandStyle['background'] }}; color: {{ $_brandStyle['text'] }};">

    {{-- Glow d'ambiance --}}
    @if(!empty($_brandStyle['glow']))
        <div class="gc-glow" style="background: {{ $_brandStyle['glow'] }};"></div>
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
