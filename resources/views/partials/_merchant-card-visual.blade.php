{{--
    Partial : visuel d'une carte-cadeau MARCHAND (Carte Gabon).
    Différent du _gift-card-visual.blade.php qui sert les marques afrikard.

    Variables attendues :
      - $card : App\Models\MerchantCard (avec reseller chargé)
      - $compact : ?bool (default false) — version réduite pour les grilles
--}}
@php
    $_card    = $card;
    $_visual  = $_card->visual_url ? asset($_card->visual_url) : null;
    $_name    = $_card->name;
    $_biz     = $_card->reseller->business_name ?? $_card->reseller->name;
    $_denoms  = (array) ($_card->denominations ?? []);
    $_compact = $compact ?? false;
@endphp

<div class="mcv {{ $_compact ? 'mcv--compact' : '' }}">
    {{-- Background : image marchand ou dégradé fallback --}}
    @if($_visual)
        <div class="mcv-bg" style="background-image: url('{{ $_visual }}');"></div>
    @else
        <div class="mcv-bg mcv-bg--fallback"></div>
    @endif
    <div class="mcv-overlay"></div>

    {{-- Frame top : KardAfrica + vérifié --}}
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

    {{-- Nom marchand (corner) --}}
    <div class="mcv-merchant">{{ \Illuminate\Support\Str::upper($_biz) }}</div>

    {{-- Bottom : titre + dénoms --}}
    <div class="mcv-bottom">
        <h3 class="mcv-title">{{ $_name }}</h3>
        @if(!empty($_denoms))
            <div class="mcv-denoms">
                @foreach(array_slice($_denoms, 0, 5) as $d)
                    <span>{{ number_format($d, 0, ',', ' ') }} F</span>
                @endforeach
                @if(count($_denoms) > 5)
                    <span class="mcv-denoms-more">+{{ count($_denoms) - 5 }}</span>
                @endif
                @if($_card->allow_custom_amount)
                    <span class="mcv-denoms-custom">+ libre</span>
                @endif
            </div>
        @endif
    </div>
</div>

@once
<style>
    .mcv {
        position: relative;
        aspect-ratio: 1.55;
        border-radius: 18px;
        overflow: hidden;
        color: white;
        background: linear-gradient(135deg, #0F172A 0%, #0F4F44 100%);
        box-shadow: 0 14px 32px -10px rgba(15,23,42,.35),
                    inset 0 1px 0 rgba(255,255,255,.15),
                    0 0 0 1px rgba(15,23,42,.05);
        transition: transform .25s, box-shadow .25s;
    }
    .mcv--compact { border-radius: 14px; }
    a:hover .mcv {
        transform: translateY(-3px);
        box-shadow: 0 24px 48px -12px rgba(15,23,42,.45),
                    inset 0 1px 0 rgba(255,255,255,.20);
    }
    .mcv-bg {
        position: absolute; inset: 0;
        background-size: cover; background-position: center;
        transition: transform .4s;
    }
    a:hover .mcv-bg { transform: scale(1.05); }
    .mcv-bg--fallback {
        background:
            radial-gradient(circle at 25% 25%, rgba(78,205,196,.35) 0%, transparent 55%),
            radial-gradient(circle at 80% 75%, rgba(68,160,141,.25) 0%, transparent 55%),
            linear-gradient(135deg, #0F172A 0%, #1E293B 60%, #0F4F44 100%);
    }
    .mcv-overlay {
        position: absolute; inset: 0;
        background: linear-gradient(170deg,
            rgba(15,23,42,.15) 0%,
            rgba(15,23,42,.20) 45%,
            rgba(15,23,42,.85) 100%);
        pointer-events: none;
    }
    .mcv-frame-top {
        position: absolute; top: 11px; left: 12px; right: 12px;
        display: flex; align-items: center; justify-content: space-between;
        z-index: 2;
    }
    .mcv-frame-brand {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 8px 3px 6px;
        background: rgba(255,255,255,.16);
        backdrop-filter: blur(8px);
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
    .mcv-merchant {
        position: absolute; top: 42px; left: 14px;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 9px; font-weight: 800;
        letter-spacing: .14em;
        color: rgba(255,255,255,.85);
        text-shadow: 0 1px 3px rgba(0,0,0,.30);
        z-index: 2;
    }
    .mcv-bottom {
        position: absolute; bottom: 14px; left: 14px; right: 14px;
        z-index: 2;
    }
    .mcv-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 16px; font-weight: 800;
        line-height: 1.2;
        margin: 0 0 8px;
        color: white;
        text-shadow: 0 2px 6px rgba(0,0,0,.5);
        letter-spacing: -0.01em;
    }
    .mcv--compact .mcv-title { font-size: 14px; }
    .mcv-denoms {
        display: flex; flex-wrap: wrap; gap: 4px;
    }
    .mcv-denoms span {
        padding: 3px 8px;
        background: rgba(255,255,255,.18);
        border-radius: 6px;
        font-size: 10px; font-weight: 800;
        font-variant-numeric: tabular-nums;
        color: white;
        backdrop-filter: blur(4px);
        border: 1px solid rgba(255,255,255,.1);
    }
    .mcv-denoms-more {
        background: rgba(255,255,255,.30) !important;
    }
    .mcv-denoms-custom {
        background: rgba(94,234,212,.30) !important;
        color: #ECFDF5 !important;
    }
</style>
@endonce
