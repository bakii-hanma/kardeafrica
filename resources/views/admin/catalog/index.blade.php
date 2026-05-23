@extends('admin.layouts.admin')

@section('title', 'Catalogue')
@section('page-title', 'Catalogue des produits')

@section('content')
@php
    $brandPalette = [
        'Netflix'      => '#E50914', 'Spotify'      => '#1DB954', 'Apple'        => '#000000',
        'iTunes'       => '#D60017', 'PlayStation'  => '#003791', 'PSN'          => '#003791',
        'Xbox'         => '#107C10', 'Amazon'       => '#FF9900', 'Google'       => '#01875F',
        'Steam'        => '#171A21', 'Roblox'       => '#00A2FF', 'Nintendo'     => '#E60012',
        'Disney'       => '#0E47A1', 'StarzPlay'    => '#7C3AED', 'Talabat'      => '#FF5A00',
        'HUAWEI'       => '#C7000B', 'IKEA'         => '#0058A3', 'HBO'          => '#B833FF',
        'Twitch'       => '#9146FF', 'Deezer'       => '#FF0092', 'Fortnite'     => '#7B68EE',
        'Minecraft'    => '#62B47A', 'PUBG'         => '#F2A900', 'Free Fire'    => '#FF6600',
        'Valorant'     => '#FF4655', 'League'       => '#C89B3C', 'Call of Duty' => '#3A3A3A',
        'FIFA'         => '#326295', 'Blizzard'     => '#00AEFF', 'Epic'         => '#2F2F2F',
    ];
    $brandColorFor = function ($name) use ($brandPalette) {
        if (!$name) return '#1F2937';
        foreach ($brandPalette as $key => $color) {
            if (stripos($name, $key) !== false) return $color;
        }
        $palette = ['#0F172A', '#44A08D', '#0EA5E9', '#7C3AED', '#DC2626', '#EA580C', '#059669'];
        $hash = 0;
        for ($i = 0; $i < strlen($name); $i++) $hash = ord($name[$i]) + (($hash << 5) - $hash);
        $idx = (($hash % count($palette)) + count($palette)) % count($palette);
        return $palette[$idx];
    };

    $activeFilters = (int)!empty($search) + (int)!empty($category);
@endphp

<div style="padding:24px;font-family:'Inter','Figtree',sans-serif;">

    {{-- ===== Stats ===== --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:16px;margin-bottom:20px;">
        <div style="background:white;border-radius:14px;padding:18px 20px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:6px;">
                <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#64748B;">Produits</span>
                <div style="width:32px;height:32px;border-radius:9px;background:#ECFDF5;color:#059669;display:flex;align-items:center;justify-content:center;">
                    <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
            </div>
            <div style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:28px;font-weight:800;color:#0F172A;line-height:1.1;">{{ number_format($total, 0, ',', ' ') }}</div>
            <div style="font-size:12px;color:#64748B;margin-top:4px;">dans le catalogue</div>
        </div>

        <div style="background:white;border-radius:14px;padding:18px 20px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:6px;">
                <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#64748B;">Catégories</span>
                <div style="width:32px;height:32px;border-radius:9px;background:#EFF6FF;color:#2563EB;display:flex;align-items:center;justify-content:center;">
                    <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                </div>
            </div>
            <div style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:28px;font-weight:800;color:#0F172A;line-height:1.1;">{{ count($categories) }}</div>
            <div style="font-size:12px;color:#64748B;margin-top:4px;">disponibles</div>
        </div>

        {{-- Toggle devise --}}
        <div style="background:white;border-radius:14px;padding:14px 18px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);display:flex;flex-direction:column;justify-content:center;">
            <div style="font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">Affichage prix</div>
            <div id="currency-toggle" style="display:inline-flex;background:#F1F5F9;border-radius:8px;padding:3px;gap:2px;border:1px solid #E2E8F0;">
                <button type="button" id="btn-fcfa" onclick="switchCurrency('fcfa')"
                        style="padding:6px 14px;font-size:12px;font-weight:700;border:none;cursor:pointer;border-radius:6px;transition:all 0.15s;background:#0F172A;color:white;">
                    FCFA
                </button>
                <button type="button" id="btn-original" onclick="switchCurrency('original')"
                        style="padding:6px 14px;font-size:12px;font-weight:700;border:none;cursor:pointer;border-radius:6px;transition:all 0.15s;background:transparent;color:#64748B;">
                    Devise originale
                </button>
            </div>
        </div>
    </div>

    {{-- ===== Filtres ===== --}}
    <form method="GET" action="{{ route('admin.catalog.index') }}"
          style="background:white;border-radius:14px;padding:12px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);margin-bottom:20px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">

        <div style="position:relative;flex:1;min-width:220px;">
            <svg style="position:absolute;left:14px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#94A3B8;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="search" value="{{ $search }}" placeholder="Rechercher par nom ou marque…"
                   style="width:100%;padding:10px 14px 10px 40px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;outline:none;transition:all 0.15s;"
                   onfocus="this.style.background='white';this.style.borderColor='#44A08D';this.style.boxShadow='0 0 0 3px rgba(68,160,141,0.1)';"
                   onblur="this.style.background='#F8FAFC';this.style.borderColor='#E2E8F0';this.style.boxShadow='none';">
        </div>

        <select name="category" onchange="this.form.submit()"
                style="padding:10px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;font-weight:500;color:#334155;outline:none;cursor:pointer;min-width:200px;">
            <option value="">Toutes les catégories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat['id'] }}" {{ $category == $cat['id'] ? 'selected' : '' }}>{{ $cat['name'] }}</option>
            @endforeach
        </select>

        <button type="submit"
                style="padding:10px 20px;background:#44A08D;color:white;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;transition:all 0.15s;"
                onmouseover="this.style.background='#3d9180';"
                onmouseout="this.style.background='#44A08D';">
            Filtrer
        </button>

        @if($activeFilters > 0)
            <a href="{{ route('admin.catalog.index') }}"
               style="padding:10px 14px;color:#64748B;font-size:13px;font-weight:600;text-decoration:none;border-radius:10px;transition:all 0.15s;"
               onmouseover="this.style.background='#F1F5F9';this.style.color='#0F172A';"
               onmouseout="this.style.background='transparent';this.style.color='#64748B';">
                Effacer
            </a>
        @endif
    </form>

    {{-- ===== Grille produits ===== --}}
    @if(count($products) > 0)
        <div id="products-grid" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(240px, 1fr));gap:16px;margin-bottom:24px;">
            @foreach($products as $product)
                @php
                    $name      = $product['name'] ?? 'Produit';
                    $brandName = $product['cardType']['name'] ?? $product['brand']['name'] ?? '';
                    $brandLabel= $brandName ?: explode(' ', $name)[0];
                    $logoUrl   = $product['cardType']['logoUrl'] ?? $product['brand']['logoUrl'] ?? $product['logoUrl'] ?? '';
                    $priceMin  = $product['price']['min'] ?? 0;
                    $priceMax  = $product['price']['max'] ?? $priceMin;
                    $currency  = $product['price']['currencyCode'] ?? 'XAF';
                    $productId = $product['id'] ?? '';
                    $country   = $product['country']['name'] ?? '';
                    $flagUrl   = $product['country']['flagUrl'] ?? '';
                    $bgColor   = $brandColorFor($brandLabel);
                @endphp

                <article class="product-card"
                         data-price-min="{{ $priceMin }}" data-price-max="{{ $priceMax }}" data-currency="{{ $currency }}"
                         style="background:white;border-radius:16px;border:1px solid #E2E8F0;overflow:hidden;transition:all 0.25s ease;box-shadow:0 1px 2px rgba(15,23,42,0.04);"
                         onmouseover="this.style.boxShadow='0 12px 28px rgba(15,23,42,0.10)';this.style.transform='translateY(-3px)';this.style.borderColor='#CBD5E1';"
                         onmouseout="this.style.boxShadow='0 1px 2px rgba(15,23,42,0.04)';this.style.transform='none';this.style.borderColor='#E2E8F0';">

                    {{-- Visuel haut --}}
                    <div style="background-color:{{ $bgColor }};height:140px;padding:14px;position:relative;overflow:hidden;">
                        {{-- Pattern emboss --}}
                        <svg style="position:absolute;inset:0;width:100%;height:100%;opacity:0.08;pointer-events:none;" aria-hidden="true">
                            <defs>
                                <pattern id="ac-{{ md5($brandLabel . $productId) }}" width="32" height="32" patternUnits="userSpaceOnUse">
                                    <circle cx="16" cy="16" r="13" fill="none" stroke="white" stroke-width="1.2"/>
                                </pattern>
                            </defs>
                            <rect width="100%" height="100%" fill="url(#ac-{{ md5($brandLabel . $productId) }})"/>
                        </svg>

                        {{-- Glow accent --}}
                        <div style="position:absolute;top:-30px;right:-30px;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,0.15);filter:blur(20px);"></div>

                        {{-- Drapeau --}}
                        @if($flagUrl)
                            <img src="{{ $flagUrl }}" alt="{{ $country }}"
                                 style="position:absolute;top:12px;right:12px;width:24px;height:16px;border-radius:3px;object-fit:cover;box-shadow:0 1px 3px rgba(0,0,0,0.3);">
                        @endif

                        <div style="position:relative;z-index:1;display:flex;flex-direction:column;height:100%;justify-content:space-between;">
                            <div>
                                <div style="color:rgba(255,255,255,0.75);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.18em;margin-bottom:2px;">Gift Card</div>
                                <div style="font-family:'Space Grotesk', 'Inter', sans-serif;color:white;font-size:22px;font-weight:700;line-height:1.05;letter-spacing:-0.02em;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    {{ explode(' ', $brandLabel)[0] }}
                                </div>
                            </div>

                            <div style="display:flex;align-items:flex-end;justify-content:space-between;">
                                <div>
                                    <div style="color:rgba(255,255,255,0.6);font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;">ID</div>
                                    <div style="color:rgba(255,255,255,0.9);font-family:monospace;font-size:11px;font-weight:600;margin-top:2px;">#{{ $productId }}</div>
                                </div>
                                {{-- Chip carte bancaire --}}
                                <div style="width:36px;height:24px;border-radius:4px;background:linear-gradient(135deg,rgba(254,240,138,0.9),rgba(250,204,21,0.7));border:1px solid rgba(255,255,255,0.3);"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Body --}}
                    <div style="padding:14px 14px 16px;position:relative;">
                        {{-- Logo flottant --}}
                        <div style="position:absolute;top:-22px;left:14px;width:44px;height:44px;background:white;border-radius:50%;padding:2px;box-shadow:0 4px 10px rgba(15,23,42,0.12);border:2px solid white;">
                            <div style="width:100%;height:100%;border-radius:50%;background:#F8FAFC;display:flex;align-items:center;justify-content:center;overflow:hidden;border:1px solid #F1F5F9;">
                                @if($logoUrl)
                                    <img src="{{ $logoUrl }}" alt="{{ $brandLabel }}" style="width:100%;height:100%;object-fit:contain;" loading="lazy">
                                @else
                                    <span style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:14px;font-weight:700;color:#475569;">{{ strtoupper(substr($brandLabel, 0, 2)) }}</span>
                                @endif
                            </div>
                        </div>

                        <div style="padding-top:28px;">
                            <h4 style="font-size:13px;font-weight:600;color:#0F172A;margin:0 0 6px;line-height:1.3;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;min-height:34px;">
                                {{ $name }}
                            </h4>

                            @if($brandName)
                                <div style="font-size:11px;color:#64748B;margin-bottom:8px;">{{ $brandName }}</div>
                            @endif

                            <div style="display:flex;align-items:flex-end;justify-content:space-between;padding-top:8px;border-top:1px solid #F1F5F9;">
                                <div>
                                    <div style="font-size:9px;color:#94A3B8;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;">À partir de</div>
                                    <div class="price-display" style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:15px;font-weight:800;color:#0F172A;margin-top:2px;font-variant-numeric:tabular-nums;">
                                        {{-- Rempli par updateAllPrices() --}}
                                    </div>
                                </div>
                                @if($country)
                                    <span style="font-size:10px;color:#94A3B8;background:#F1F5F9;padding:2px 6px;border-radius:4px;font-family:monospace;">{{ $country }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($lastPage > 1)
            <div style="display:flex;justify-content:center;align-items:center;gap:6px;flex-wrap:wrap;padding:16px 0;">
                @if($page > 1)
                    <a href="{{ route('admin.catalog.index', array_merge(request()->query(), ['page' => $page - 1])) }}"
                       style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;background:white;border:1px solid #E2E8F0;border-radius:9px;color:#475569;text-decoration:none;transition:all 0.15s;"
                       onmouseover="this.style.borderColor='#44A08D';this.style.color='#44A08D';"
                       onmouseout="this.style.borderColor='#E2E8F0';this.style.color='#475569';">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                @else
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:9px;color:#CBD5E1;cursor:not-allowed;">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </span>
                @endif

                @for($i = max(1, $page - 2); $i <= min($lastPage, $page + 2); $i++)
                    @if($i === $page)
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;background:#44A08D;color:white;border-radius:9px;font-size:13px;font-weight:700;box-shadow:0 4px 12px rgba(68,160,141,0.25);">{{ $i }}</span>
                    @else
                        <a href="{{ route('admin.catalog.index', array_merge(request()->query(), ['page' => $i])) }}"
                           style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;background:white;border:1px solid #E2E8F0;border-radius:9px;color:#475569;text-decoration:none;font-size:13px;font-weight:600;transition:all 0.15s;"
                           onmouseover="this.style.borderColor='#44A08D';this.style.color='#44A08D';"
                           onmouseout="this.style.borderColor='#E2E8F0';this.style.color='#475569';">{{ $i }}</a>
                    @endif
                @endfor

                @if($page < $lastPage)
                    <a href="{{ route('admin.catalog.index', array_merge(request()->query(), ['page' => $page + 1])) }}"
                       style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;background:white;border:1px solid #E2E8F0;border-radius:9px;color:#475569;text-decoration:none;transition:all 0.15s;"
                       onmouseover="this.style.borderColor='#44A08D';this.style.color='#44A08D';"
                       onmouseout="this.style.borderColor='#E2E8F0';this.style.color='#475569';">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @else
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:9px;color:#CBD5E1;cursor:not-allowed;">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </span>
                @endif
            </div>
        @endif

    @else
        <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:80px 40px;text-align:center;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,#F8FAFC,#F1F5F9);margin:0 auto 16px;display:flex;align-items:center;justify-content:center;">
                <svg style="width:28px;height:28px;color:#CBD5E1;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
            <div style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:18px;font-weight:700;color:#0F172A;margin-bottom:6px;">Aucun produit trouvé</div>
            <p style="font-size:13px;color:#64748B;margin:0 0 16px;max-width:380px;margin-left:auto;margin-right:auto;">
                @if($activeFilters > 0)
                    Aucun produit ne correspond à vos filtres.
                @else
                    Le catalogue externe ne renvoie aucun produit pour le moment.
                @endif
            </p>
            @if($activeFilters > 0)
                <a href="{{ route('admin.catalog.index') }}"
                   style="display:inline-flex;align-items:center;gap:6px;padding:10px 18px;background:#44A08D;color:white;text-decoration:none;border-radius:10px;font-size:13px;font-weight:600;">
                    Réinitialiser les filtres
                </a>
            @endif
        </div>
    @endif
</div>

<script>
var EXCHANGE_RATES = {
    'XAF': 1, 'XOF': 1, 'EUR': 655.957, 'USD': 620, 'AED': 170, 'GBP': 780, 'CAD': 450,
    'ARS': 0.7, 'AUD': 405, 'BRL': 120, 'TRY': 20, 'MXN': 35, 'INR': 7.5, 'ZAR': 35,
    'NGN': 0.4, 'SAR': 165, 'QAR': 170, 'KWD': 2015, 'BHD': 1645, 'OMR': 1610, 'JPY': 4.2,
    'KRW': 0.47, 'CHF': 700, 'SEK': 60, 'NOK': 58, 'DKK': 88, 'PLN': 155, 'CZK': 27,
    'HUF': 1.7, 'RON': 132, 'BGN': 335, 'HRK': 87, 'RUB': 7, 'CNY': 85, 'THB': 17.5,
    'MYR': 140, 'SGD': 460, 'HKD': 79, 'TWD': 19.5, 'PHP': 11, 'IDR': 0.04, 'VND': 0.025,
    'EGP': 12.7, 'MAD': 62, 'TND': 200, 'KES': 4.8, 'GHS': 40, 'CLP': 0.66, 'COP': 0.15, 'PEN': 167,
};

var currentMode = 'fcfa';
function convertToFCFA(amount, currencyCode) {
    if (!currencyCode) return amount;
    var rate = EXCHANGE_RATES[currencyCode.toUpperCase()] || 0;
    return rate === 0 ? amount : Math.round(amount * rate);
}
function formatNumber(num) { return new Intl.NumberFormat('fr-FR').format(num); }

function switchCurrency(mode) {
    currentMode = mode;
    var btnFcfa = document.getElementById('btn-fcfa');
    var btnOriginal = document.getElementById('btn-original');
    if (mode === 'fcfa') {
        btnFcfa.style.background = '#0F172A'; btnFcfa.style.color = 'white';
        btnOriginal.style.background = 'transparent'; btnOriginal.style.color = '#64748B';
    } else {
        btnOriginal.style.background = '#0F172A'; btnOriginal.style.color = 'white';
        btnFcfa.style.background = 'transparent'; btnFcfa.style.color = '#64748B';
    }
    updateAllPrices();
}

function updateAllPrices() {
    document.querySelectorAll('.product-card').forEach(function(card) {
        var priceMin = parseFloat(card.getAttribute('data-price-min')) || 0;
        var priceMax = parseFloat(card.getAttribute('data-price-max')) || priceMin;
        var currency = card.getAttribute('data-currency') || 'XAF';
        var priceEl = card.querySelector('.price-display');
        if (!priceEl) return;

        var min, max, label;
        if (currentMode === 'fcfa') {
            min = convertToFCFA(priceMin, currency);
            max = convertToFCFA(priceMax, currency);
            label = 'FCFA';
        } else {
            min = priceMin; max = priceMax; label = currency;
        }
        var labelHtml = ' <span style="font-size:10px;font-weight:500;color:#94A3B8;">' + label + '</span>';
        priceEl.innerHTML = (min === max || max === 0)
            ? formatNumber(min) + labelHtml
            : formatNumber(min) + ' – ' + formatNumber(max) + labelHtml;
    });
}

document.addEventListener('DOMContentLoaded', updateAllPrices);
</script>
@endsection
