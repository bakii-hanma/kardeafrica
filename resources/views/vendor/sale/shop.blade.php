@extends('vendor.layouts.vendor')

@section('title', 'Vendre une carte')

@php
    $brandPalette = [
        'Netflix' => '#E50914', 'Spotify' => '#1DB954', 'Apple' => '#000000',
        'iTunes' => '#D60017', 'PlayStation' => '#003791', 'Xbox' => '#107C10',
        'Amazon' => '#FF9900', 'Google' => '#01875F', 'Steam' => '#171A21',
        'Roblox' => '#00A2FF', 'Nintendo' => '#E60012', 'Disney' => '#0E47A1',
        'Daywatch' => '#44A08D',
    ];
    $brandColorFor = function ($name) use ($brandPalette) {
        if (!$name) return '#0F172A';
        foreach ($brandPalette as $k => $c) if (stripos($name, $k) !== false) return $c;
        $palette = ['#0F172A', '#44A08D', '#0EA5E9', '#7C3AED', '#DC2626', '#EA580C', '#059669'];
        $hash = 0;
        for ($i = 0; $i < strlen($name); $i++) $hash = (ord($name[$i]) + (($hash << 5) - $hash)) & 0x7FFFFFFF;
        return $palette[(($hash % count($palette)) + count($palette)) % count($palette)];
    };

    $activeFiltersCount = (int)!empty($search)
        + (int)!empty($categoryId)
        + count($priceRange ?? [])
        + count($selectedLoc ?? [])
        + count($selectedRegions ?? [])
        + count($selectedBrands ?? [])
        + (int)!empty($popularOnly)
        + (int)($priceMin !== null || $priceMax !== null);

    $currentCategory = $categoryId ? collect($categories)->firstWhere('id', (int) $categoryId) : null;

    $priceRangeLabels = [
        'under_1000' => '< 1 000',
        '1000_5000'  => '1k — 5k',
        '5000_20000' => '5k — 20k',
        'over_20000' => '> 20k',
    ];

    $sortOptions = [
        'popular'    => 'Populaire',
        'newest'     => 'Nouveautés',
        'price_asc'  => 'Prix croissant',
        'price_desc' => 'Prix décroissant',
        'promo'      => 'Meilleures économies',
    ];
    $sortLabel = $sortOptions[$sort] ?? 'Populaire';

    // Préserve l'état COMPLET des filtres : trier ou changer de page ne doit
    // jamais faire disparaître silencieusement une région ou une marque cochée.
    $urlWith = function (array $overrides) use (
        $search, $categoryId, $priceRange, $selectedLoc, $selectedRegions,
        $selectedBrands, $popularOnly, $priceMin, $priceMax, $sort
    ) {
        $base = [
            'search'      => $search,
            'category'    => $categoryId,
            'price_range' => $priceRange,
            'loc'         => $selectedLoc,
            'region'      => $selectedRegions,
            'marque'      => $selectedBrands,
            'popular'     => $popularOnly ? 1 : null,
            'prix_min'    => $priceMin,
            'prix_max'    => $priceMax,
            'sort'        => $sort,
        ];
        return route('vendor.sell', array_merge($base, $overrides));
    };
@endphp

@section('content')
<div x-data="vendorSale()" class="vsell-wrap">

    @include('vendor.partials._sell-mode-switch', ['mode' => 'digital'])

    {{-- ============= TOP STRIP ============= --}}
    <div class="vsell-top">
        <div>
            <div class="vsell-eyebrow">Catalogue</div>
            <h1 class="vsell-title">
                @if($search)
                    Résultats pour <span style="color:#44A08D;">« {{ $search }} »</span>
                @elseif($currentCategory)
                    {{ $currentCategory['name'] }}
                @else
                    Toutes les cartes
                @endif
            </h1>
            <p class="vsell-lead">
                <strong>{{ $pagination['total'] }}</strong> produit{{ $pagination['total'] > 1 ? 's' : '' }}
                @if($activeFiltersCount > 0) · {{ $activeFiltersCount }} filtre{{ $activeFiltersCount > 1 ? 's' : '' }} actif{{ $activeFiltersCount > 1 ? 's' : '' }}@endif
            </p>
        </div>
        <div class="vsell-counter" x-show="cart.length > 0" x-cloak>
            <span class="vsell-counter-num" x-text="cart.length"></span>
            <span class="vsell-counter-label">au panier</span>
        </div>
    </div>

    {{-- ============= TOOLBAR (search en haut, filtres+tri en bas mobile) ============= --}}
    <div class="vsell-toolbar">
        {{-- Ligne 1 : recherche pleine largeur sur mobile --}}
        <form action="{{ route('vendor.sell') }}" method="GET" class="vsell-search" data-no-loader>
            <input type="hidden" name="sort" value="{{ $sort }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="search" value="{{ $search }}" placeholder="Rechercher (Netflix, Daywatch…)">
            @if($search)
                <a href="{{ $urlWith(['search' => null, 'page' => null]) }}" class="vsell-search-clear" aria-label="Effacer">×</a>
            @endif
            <button type="submit" class="vsell-search-btn">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span class="vsell-search-btn-label">OK</span>
            </button>
        </form>

        {{-- Ligne 2 : actions (filtres pleine largeur en avant + tri compact) --}}
        <div class="vsell-actions">
            {{-- Mobile filter trigger : prend la majorité de la largeur --}}
            <button type="button" @click="filtersOpen = !filtersOpen" class="vsell-filter-trigger">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"/></svg>
                Filtres
                @if($activeFiltersCount > 0)
                    <span class="vsell-filter-trigger-badge">{{ $activeFiltersCount }}</span>
                @endif
            </button>

            {{-- Sort dropdown --}}
            <div class="vsell-sort" x-data="{ open: false }" @click.away="open = false">
                <button @click="open = !open" type="button" class="vsell-sort-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/></svg>
                    <span class="vsell-sort-label">{{ $sortLabel }}</span>
                    <svg :class="open && 'vsell-rot'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" class="vsell-sort-chev"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition class="vsell-sort-menu">
                    @foreach ($sortOptions as $value => $label)
                        <a href="{{ $urlWith(['sort' => $value, 'page' => null]) }}"
                           class="vsell-sort-item {{ $sort === $value ? 'vsell-sort-item--active' : '' }}">
                            {{ $label }}
                            @if($sort === $value)
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ============= ACTIVE FILTER CHIPS ============= --}}
    @if($activeFiltersCount > 0)
        <div class="vsell-chips">
            @if($search)
                <a href="{{ $urlWith(['search' => null, 'page' => null]) }}" class="vsell-chip vsell-chip--strong">
                    Recherche : {{ Str::limit($search, 20) }}
                    <span>×</span>
                </a>
            @endif
            @if($currentCategory)
                <a href="{{ $urlWith(['category' => null, 'page' => null]) }}" class="vsell-chip vsell-chip--strong">
                    {{ $currentCategory['name'] }}
                    <span>×</span>
                </a>
            @endif
            @foreach ($priceRange ?? [] as $range)
                <a href="{{ route('vendor.sell', ['search' => $search, 'category' => $categoryId, 'price_range' => array_values(array_diff($priceRange, [$range])), 'country' => $selectedCountries, 'sort' => $sort]) }}"
                   class="vsell-chip">
                    {{ $priceRangeLabels[$range] ?? $range }} FCFA
                    <span>×</span>
                </a>
            @endforeach
            @php
                $regionLabels = ['europe' => '🇪🇺 Europe', 'usa' => '🇺🇸 États-Unis',
                                 'africa' => '🌍 Gabon & Afrique', 'global' => '🌐 International'];
            @endphp
            @foreach ($selectedLoc ?? [] as $l)
                <a href="{{ $urlWith(['loc' => array_values(array_diff($selectedLoc, [$l])), 'page' => null]) }}" class="vsell-chip">
                    <span class="vsell-chip-flag">🇫🇷</span><span>{{ $l }}</span><span>×</span>
                </a>
            @endforeach
            @foreach ($selectedRegions ?? [] as $rg)
                <a href="{{ $urlWith(['region' => array_values(array_diff($selectedRegions, [$rg])), 'page' => null]) }}" class="vsell-chip">
                    <span>{{ $regionLabels[$rg] ?? $rg }}</span><span>×</span>
                </a>
            @endforeach
            @foreach ($selectedBrands ?? [] as $b)
                <a href="{{ $urlWith(['marque' => array_values(array_diff($selectedBrands, [$b])), 'page' => null]) }}" class="vsell-chip">
                    <span>{{ $b }}</span><span>×</span>
                </a>
            @endforeach
            @if($popularOnly)
                <a href="{{ $urlWith(['popular' => null, 'page' => null]) }}" class="vsell-chip">
                    <span>🔥 Top Afrique</span><span>×</span>
                </a>
            @endif
            <a href="{{ route('vendor.sell') }}" class="vsell-chip-clear">Tout effacer</a>
        </div>
    @endif

    {{-- ============= MAIN GRID : sidebar + products + cart ============= --}}
    <div class="vsell-grid">

        {{-- ============ COLONNE DE GAUCHE : panier PUIS filtres ============
             Le panier est placé AU-DESSUS des filtres pour que le revendeur
             garde sa vente sous les yeux pendant qu'il parcourt le catalogue.
             Les informations client ne sont pas demandées ici mais au Checkout.
             ================================================================ --}}
        <div class="vsell-side-col">
            <form method="POST" action="{{ route('vendor.checkout') }}"
                  @submit="if (cart.length === 0) { $event.preventDefault(); return false; }"
                  x-show="cart.length > 0" x-cloak
                  class="vsell-cart-top">
                @csrf
                <input type="hidden" name="cart" :value="JSON.stringify(cart)">

                {{-- ===== HEADER ===== --}}
                <div class="vct-head">
                    <div class="vct-head-l">
                        <div class="vct-head-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <div>
                            <div class="vct-head-eyebrow">Panier client</div>
                            <div class="vct-head-count">
                                <span x-text="cart.length"></span>
                                <span x-text="cart.length > 1 ? 'cartes sélectionnées' : 'carte sélectionnée'"></span>
                            </div>
                        </div>
                    </div>
                    <button type="button" @click.stop="cart = []" class="vct-clear" title="Vider le panier">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M11 7V4a1 1 0 011-1h0a1 1 0 011 1v3"/></svg>
                        <span class="vct-clear-label">Vider</span>
                    </button>
                </div>

                {{-- ===== ITEMS LIST (scroll horizontal mobile, grid desktop) ===== --}}
                <div class="vct-items">
                    <template x-for="(item, idx) in cart" :key="'i-' + item.product_id">
                        <div class="vct-item">
                            {{-- Visuel mini-carte --}}
                            <div class="vct-item-visual" :style="`background-color:${item.color};`">
                                <template x-if="item.image_url"><img :src="item.image_url" :alt="item.brand" loading="lazy"></template>
                                <template x-if="!item.image_url"><span x-text="(item.brand || item.name).charAt(0).toUpperCase()"></span></template>
                                <span class="vct-item-chip"></span>
                            </div>

                            {{-- Body : marque + nom + ligne prix --}}
                            <div class="vct-item-body">
                                <div class="vct-item-brand" x-text="item.brand"></div>
                                <div class="vct-item-name" x-text="item.name"></div>

                                <div class="vct-item-row">
                                    <div class="vct-item-row-l">
                                        <span class="vct-item-unit"><span x-text="item.price.toLocaleString('fr-FR')"></span> FCFA × <span x-text="item.quantity"></span></span>
                                        <span class="vct-item-total"><span x-text="(item.price * item.quantity).toLocaleString('fr-FR')"></span> <span class="vct-item-total-unit">FCFA</span></span>
                                    </div>
                                    <div class="vct-item-row-r">
                                        <div class="vct-qty">
                                            <button type="button" @click.stop="updateQty(item.product_id, -1)" class="vct-qty-btn" :disabled="item.quantity <= 1" aria-label="Diminuer">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                            </button>
                                            <span class="vct-qty-num" x-text="item.quantity"></span>
                                            <button type="button" @click.stop="updateQty(item.product_id, 1)" class="vct-qty-btn vct-qty-btn--add" aria-label="Augmenter">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                            </button>
                                        </div>
                                        <button type="button" @click.stop="removeItem(item.product_id)" class="vct-remove" aria-label="Retirer">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- ===== FOOTER ===== --}}
                <div class="vct-foot">

                    {{-- Récap totaux structuré comme un ticket --}}
                    <div class="vct-summary">
                        <div class="vct-summary-row">
                            <span class="vct-summary-label">Sous-total</span>
                            <span class="vct-summary-num" x-text="total.toLocaleString('fr-FR') + ' FCFA'"></span>
                        </div>
                        <div class="vct-summary-row vct-summary-row--brand">
                            <span class="vct-summary-label">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                Ta commission ({{ rtrim(rtrim(number_format($reseller->commission_rate, 2), '0'), '.') }}%)
                            </span>
                            <span class="vct-summary-num" x-text="'+' + commission.toLocaleString('fr-FR') + ' FCFA'"></span>
                        </div>
                        <div class="vct-summary-divider"></div>
                        <div class="vct-summary-row vct-summary-row--total">
                            <span class="vct-summary-label">À encaisser</span>
                            <span class="vct-summary-num" x-text="total.toLocaleString('fr-FR') + ' FCFA'"></span>
                        </div>
                    </div>

                    {{-- Bouton submit : aller au checkout --}}
                    <button type="submit"
                            :disabled="total > {{ (int) $reseller->wallet_balance }}"
                            :class="total > {{ (int) $reseller->wallet_balance }} ? 'vct-submit vct-submit--disabled' : 'vct-submit'">
                        <svg x-show="total <= {{ (int) $reseller->wallet_balance }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        <svg x-show="total > {{ (int) $reseller->wallet_balance }}" x-cloak fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span x-show="total <= {{ (int) $reseller->wallet_balance }}">Aller au paiement</span>
                        <span x-show="total > {{ (int) $reseller->wallet_balance }}" x-cloak>Solde insuffisant</span>
                    </button>
                    <p x-show="total > {{ (int) $reseller->wallet_balance }}" x-cloak class="vct-warn">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Il manque <strong x-text="(total - {{ (int) $reseller->wallet_balance }}).toLocaleString('fr-FR')"></strong> FCFA — demande une recharge à ton gérant.
                    </p>
                </div>
            </form>

        {{-- ============ FILTER SIDEBAR ============ --}}
        <aside class="vsell-sidebar" :class="filtersOpen ? 'vsell-sidebar--open' : ''">
            <div class="vsell-sidebar-overlay" x-show="filtersOpen" x-cloak @click="filtersOpen = false"></div>

            <form id="filterForm" action="{{ route('vendor.sell') }}" method="GET" class="vsell-sidebar-card">
                <input type="hidden" name="search" value="{{ $search }}">
                <input type="hidden" name="sort" value="{{ $sort }}">

                <div class="vsell-sidebar-head">
                    <div class="vsell-sidebar-head-l">
                        <span class="vsell-sidebar-head-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"/></svg>
                        </span>
                        <div>
                            <h3>Filtres</h3>
                            <p>{{ $activeFiltersCount > 0 ? "$activeFiltersCount actif".($activeFiltersCount > 1 ? 's' : '') : 'Affine ta recherche' }}</p>
                        </div>
                    </div>
                    @if($activeFiltersCount > 0)
                        <a href="{{ route('vendor.sell') }}" class="vsell-sidebar-clear">Effacer</a>
                    @endif
                    <button type="button" @click="filtersOpen = false" class="vsell-sidebar-close">×</button>
                </div>

                {{-- Catégories --}}
                <div x-data="{ open: true }" class="vsell-section">
                    <button @click="open = !open" type="button" class="vsell-section-toggle">
                        <span>Catégories
                            @if($categoryId)<span class="vsell-section-count">1</span>@endif
                        </span>
                        <svg :class="open && 'vsell-rot'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-collapse class="vsell-section-body">
                        <a href="{{ route('vendor.sell', array_merge(request()->except(['category', 'page']), ['page' => 1])) }}"
                           class="vsell-cat {{ !$categoryId ? 'vsell-cat--active' : '' }}">
                            <span>Toutes</span>
                            @if(!$categoryId)<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>@endif
                        </a>
                        @foreach($categories as $category)
                            <a href="{{ route('vendor.sell', array_merge(request()->except(['category', 'page']), ['category' => $category['id'], 'page' => 1])) }}"
                               class="vsell-cat {{ $categoryId == $category['id'] ? 'vsell-cat--active' : '' }}">
                                <span>{{ $category['name'] }}</span>
                                @if($categoryId == $category['id'])<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>@endif
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Gamme de prix --}}
                <div x-data="{ open: true }" class="vsell-section">
                    <button @click="open = !open" type="button" class="vsell-section-toggle">
                        <span>Gamme de prix
                            @if(count($priceRange ?? []))<span class="vsell-section-count">{{ count($priceRange) }}</span>@endif
                        </span>
                        <svg :class="open && 'vsell-rot'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-collapse class="vsell-section-body vsell-price-grid">
                        @foreach ($priceRangeLabels as $value => $label)
                            @php $isActive = in_array($value, $priceRange ?? []); @endphp
                            <label class="vsell-price-pill">
                                <input type="checkbox" name="price_range[]" value="{{ $value }}"
                                       onchange="this.form.submit()" {{ $isActive ? 'checked' : '' }}>
                                <div class="vsell-price-pill-box {{ $isActive ? 'vsell-price-pill-box--active' : '' }}">
                                    {{ $label }}
                                    <span>FCFA</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Région de la CARTE — mêmes paramètres que la boutique
                     (loc[] / region[]). L'ancien filtre listait des pays de zone
                     monétaire (Sénégal, Mali, Cameroun…) qu'aucune carte ne porte :
                     il ne pouvait renvoyer aucun résultat. --}}
                <div x-data="{ open: true }" class="vsell-section vsell-section--last">
                    <button @click="open = !open" type="button" class="vsell-section-toggle">
                        <span>Région
                            @php $regionCount = count($selectedLoc ?? []) + count($selectedRegions ?? []); @endphp
                            @if($regionCount)<span class="vsell-section-count">{{ $regionCount }}</span>@endif
                        </span>
                        <svg :class="open && 'vsell-rot'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-collapse class="vsell-section-body">
                        @foreach([
                            ['loc',    'FR',     '🇫🇷', 'France',          'fr'],
                            ['region', 'europe', '🇪🇺', 'Europe',          'europe'],
                            ['region', 'usa',    '🇺🇸', 'États-Unis',      'usa'],
                            ['region', 'africa', '🌍', 'Gabon & Afrique', 'africa'],
                            ['region', 'global', '🌐', 'International',   'global'],
                        ] as [$param, $value, $flag, $label, $facetKey])
                            @php
                                $checked = $param === 'loc'
                                    ? in_array($value, $selectedLoc ?? [], true)
                                    : in_array($value, $selectedRegions ?? [], true);
                                $rCount = (int) ($facets['regions'][$facetKey] ?? 0);
                                // Option à 0 : grisée mais jamais masquée, pour que
                                // le vendeur sache qu'elle existe.
                                $rDead  = $rCount === 0 && !$checked;
                            @endphp
                            <label class="vsell-country {{ $rDead ? 'vsell-country--dead' : '' }}">
                                <input type="checkbox" name="{{ $param }}[]" value="{{ $value }}"
                                       onchange="this.form.submit()" {{ $checked ? 'checked' : '' }} {{ $rDead ? 'disabled' : '' }}>
                                <span class="vsell-country-flag">{{ $flag }}</span>
                                <span style="flex:1;">{{ $label }}</span>
                                <span class="vsell-country-count">{{ number_format($rCount, 0, ',', ' ') }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </form>
        </aside>
        </div>{{-- /vsell-side-col --}}

        {{-- ============ PRODUCTS ============ --}}
        <div class="vsell-products-col">


            <div class="vsell-products">
                @forelse($products as $p)
                    @php
                        $isDaywatch = ($p['meta']['source'] ?? null) === 'daywatch';
                        $brand    = $isDaywatch ? 'Daywatch' : ($p['cardType']['name'] ?? 'Brand');
                        $name     = $p['name'] ?? $brand;
                        $rawPrice = (int) ($p['price']['min'] ?? 0);
                        $currency = $p['price']['currencyCode'] ?? 'XAF';
                        $price    = \App\Support\Money::toFcfa($rawPrice, $currency);
                        $img      = $p['cardType']['logoUrl'] ?? null;
                        $color    = $isDaywatch && !empty($p['meta']['color']) ? $p['meta']['color'] : $brandColorFor($brand);
                        $pid      = $p['id'] ?? $p['cardType']['internalId'] ?? null;
                        $patternId = 'vsp-' . md5($brand . $pid);
                    @endphp
                    @if($pid && $price > 0)
                        <div class="vsell-card">
                            <div class="vsell-card-visual" style="background-color:{{ $color }};">
                                <svg class="vsell-card-pattern" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <defs>
                                        <pattern id="{{ $patternId }}" width="40" height="40" patternUnits="userSpaceOnUse">
                                            <circle cx="20" cy="20" r="16" fill="none" stroke="white" stroke-width="1"/>
                                        </pattern>
                                    </defs>
                                    <rect width="100%" height="100%" fill="url(#{{ $patternId }})"/>
                                </svg>
                                <div class="vsell-card-glow"></div>
                                <div class="vsell-card-visual-body">
                                    <span class="vsell-card-tag">Gift Card</span>
                                    <h3 class="vsell-card-brand">{{ $brand }}</h3>
                                </div>
                                <span class="vsell-card-chip"></span>
                            </div>

                            <div class="vsell-card-body">
                                <div class="vsell-card-logo">
                                    @if($img)
                                        <img src="{{ $img }}" alt="{{ $brand }}" loading="lazy">
                                    @else
                                        <span>{{ strtoupper(substr($brand, 0, 1)) }}</span>
                                    @endif
                                </div>

                                <h4 class="vsell-card-name">{{ $name }}</h4>

                                <div class="vsell-card-price-row">
                                    <span class="vsell-card-price-from">À partir de</span>
                                    <span class="vsell-card-price">{{ number_format($price, 0, ',', ' ') }} FCFA</span>
                                </div>

                                @php
                                    // Variantes de montant : une pastille par valeur, chacune
                                    // ajoutant SA variante au panier. Couvre aussi les cartes à
                                    // montant libre (Apple), dépliées en échelle par
                                    // App\Support\VirtualDenominations.
                                    $vList = collect($p['variants'] ?? [])
                                        ->filter(fn ($v) => ($v['face'] ?? 0) > 0 && ($v['price_min'] ?? 0) > 0)
                                        ->sortBy('price_min')
                                        ->values();
                                @endphp

                                @if($vList->count() > 1)
                                    <div class="vsell-variants" role="group" aria-label="Montants disponibles pour {{ $brand }}">
                                        @foreach($vList->take(6) as $v)
                                            @php
                                                $vCur   = $v['currency'] ?? $currency;
                                                $vFcfa  = \App\Support\Money::toFcfa($v['price_min'], $vCur);
                                                $vLabel = \App\Support\Money::formatOriginal($v['face'], $vCur)
                                                          ?? number_format($v['face'], 0, ',', ' ');
                                                $vName  = trim($brand . ' ' . $vLabel);
                                            @endphp
                                            <button type="button"
                                                    @click="addToCart({ product_id: '{{ $v['product_id'] }}', name: '{{ addslashes($vName) }}', brand: '{{ addslashes($brand) }}', image_url: '{{ $img }}', color: '{{ $color }}', price: {{ $vFcfa }}, currency: 'XAF', native_value: {{ $v['face'] }}, native_currency: '{{ $vCur }}' })"
                                                    class="vsell-variant"
                                                    title="{{ $vLabel }} — {{ number_format($vFcfa, 0, ',', ' ') }} FCFA">
                                                {{ $vLabel }}
                                            </button>
                                        @endforeach
                                        @if($vList->count() > 6)
                                            <span class="vsell-variant-more">+{{ $vList->count() - 6 }}</span>
                                        @endif
                                    </div>
                                    <p class="vsell-variants-hint">Touche un montant pour l'ajouter</p>
                                @else
                                    <button type="button"
                                            @click="addToCart({ product_id: '{{ $pid }}', name: '{{ addslashes($name) }}', brand: '{{ addslashes($brand) }}', image_url: '{{ $img }}', color: '{{ $color }}', price: {{ $price }}, currency: 'XAF', native_value: {{ $rawPrice }}, native_currency: '{{ $currency }}' })"
                                            class="vsell-card-add">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                        Ajouter
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="vsell-empty">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <h3>Aucun produit trouvé</h3>
                        <p>Essaie d'ajuster les filtres ou efface ta recherche.</p>
                        @if($activeFiltersCount > 0)
                            <a href="{{ route('vendor.sell') }}" class="vsell-empty-cta">Tout effacer</a>
                        @endif
                    </div>
                @endforelse
            </div>

            {{-- ============= PAGINATION ============= --}}
            @if($pagination['last_page'] > 1)
                <nav class="vsell-pagination" aria-label="Pagination">
                    @php
                        $cur = $pagination['current_page'];
                        $last = $pagination['last_page'];
                        $pages = collect(range(1, $last))
                            ->filter(fn($p) => $p === 1 || $p === $last || abs($p - $cur) <= 1)
                            ->values();
                        $prevPage = null;
                    @endphp

                    @if ($cur > 1)
                        <a href="{{ $urlWith(['page' => $cur - 1]) }}" class="vsell-page-btn" aria-label="Précédent">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </a>
                    @else
                        <span class="vsell-page-btn vsell-page-btn--disabled">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </span>
                    @endif

                    @foreach($pages as $i)
                        @if($prevPage !== null && $i - $prevPage > 1)
                            <span class="vsell-page-gap">…</span>
                        @endif
                        @if ($i == $cur)
                            <span aria-current="page" class="vsell-page-btn vsell-page-btn--current">{{ $i }}</span>
                        @else
                            <a href="{{ $urlWith(['page' => $i]) }}" class="vsell-page-btn">{{ $i }}</a>
                        @endif
                        @php $prevPage = $i; @endphp
                    @endforeach

                    @if ($cur < $last)
                        <a href="{{ $urlWith(['page' => $cur + 1]) }}" class="vsell-page-btn" aria-label="Suivant">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    @else
                        <span class="vsell-page-btn vsell-page-btn--disabled">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </span>
                    @endif
                </nav>
            @endif
        </div>

        {{-- (panier déplacé en haut de la grille produits) --}}
        @php /*

                <div class="vsell-cart" :class="cartOpen ? 'vsell-cart--open' : ''">
                    <div class="vsell-cart-overlay" x-show="cartOpen" x-cloak @click="cartOpen = false"></div>

                    <div class="vsell-cart-card">
                        {{-- HEADER --}}
                        <div class="vsell-cart-head">
                            <div class="vsell-cart-head-l">
                                <div class="vsell-cart-head-icon">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </div>
                                <div>
                                    <div class="vsell-cart-eyebrow">Panier</div>
                                    <div class="vsell-cart-count">
                                        <span x-text="cart.length"></span> <span x-text="cart.length > 1 ? 'cartes' : 'carte'"></span>
                                        <span x-show="cart.length > 0" x-cloak class="vsell-cart-count-qty">
                                            · <span x-text="totalQty"></span> unité<span x-text="totalQty > 1 ? 's' : ''"></span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="vsell-cart-head-r">
                                <button type="button" x-show="cart.length > 0" @click="cart = []" x-cloak class="vsell-cart-clear" title="Vider le panier">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M11 7V4a1 1 0 011-1h0a1 1 0 011 1v3"/></svg>
                                </button>
                                <button type="button" @click="cartOpen = false" class="vsell-cart-close">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        {{-- SOLDE PILL --}}
                        <div class="vsell-cart-balance">
                            <span class="vsell-cart-balance-label">Solde wallet</span>
                            <span class="vsell-cart-balance-value">{{ number_format($reseller->wallet_balance, 0, ',', ' ') }} FCFA</span>
                        </div>

                        {{-- BODY ITEMS --}}
                        <div class="vsell-cart-body">
                            <template x-if="cart.length === 0">
                                <div class="vsell-cart-empty">
                                    <div class="vsell-cart-empty-ico">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    </div>
                                    <div class="vsell-cart-empty-title">Panier vide</div>
                                    <div class="vsell-cart-empty-text">Tap sur une carte pour la sélectionner.</div>
                                </div>
                            </template>
                            <template x-for="(item, idx) in cart" :key="'i-' + item.product_id">
                                <div class="vsell-cart-item">
                                    {{-- Visuel mini-carte (image ou initiale) --}}
                                    <div class="vsell-cart-item-visual" :style="`background-color:${item.color};`">
                                        <template x-if="item.image_url">
                                            <img :src="item.image_url" :alt="item.brand" loading="lazy">
                                        </template>
                                        <template x-if="!item.image_url">
                                            <span x-text="(item.brand || item.name).charAt(0).toUpperCase()"></span>
                                        </template>
                                    </div>

                                    <div class="vsell-cart-item-body">
                                        <div class="vsell-cart-item-brand" x-text="item.brand"></div>
                                        <div class="vsell-cart-item-name" x-text="item.name"></div>
                                        <div class="vsell-cart-item-meta">
                                            <span class="vsell-cart-item-unit" x-text="item.price.toLocaleString('fr-FR') + ' FCFA × ' + item.quantity"></span>
                                            <span class="vsell-cart-item-total" x-text="(item.price * item.quantity).toLocaleString('fr-FR') + ' FCFA'"></span>
                                        </div>
                                    </div>

                                    <div class="vsell-cart-item-actions">
                                        <div class="vsell-cart-qty">
                                            <button type="button" @click="updateQty(idx, -1)" class="vsell-cart-qty-btn" :disabled="item.quantity <= 1">−</button>
                                            <span class="vsell-cart-qty-num" x-text="item.quantity"></span>
                                            <button type="button" @click="updateQty(idx, 1)" class="vsell-cart-qty-btn vsell-cart-qty-btn--add">+</button>
                                        </div>
                                        <button type="button" @click="removeItem(idx)" class="vsell-cart-item-remove" title="Retirer">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M11 7V4a1 1 0 011-1h0a1 1 0 011 1v3"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- FOOT --}}
                        <div x-show="cart.length > 0" x-cloak class="vsell-cart-foot">
                            {{-- Champs client --}}
                            <div class="vsell-cart-fields">
                                <div class="vsell-cart-field">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <input type="text" name="customer_name" placeholder="Nom du client (optionnel)">
                                </div>
                                <div class="vsell-cart-field">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    <input type="text" name="customer_phone" placeholder="Téléphone (optionnel)">
                                </div>
                            </div>

                            {{-- Récap totaux --}}
                            <div class="vsell-cart-summary">
                                <div class="vsell-cart-summary-row">
                                    <span>Sous-total</span>
                                    <span x-text="total.toLocaleString('fr-FR') + ' FCFA'" class="vsell-cart-summary-num"></span>
                                </div>
                                <div class="vsell-cart-summary-row vsell-cart-summary-row--brand">
                                    <span>Ta commission ({{ rtrim(rtrim(number_format($reseller->commission_rate, 2), '0'), '.') }}%)</span>
                                    <span x-text="'+ ' + commission.toLocaleString('fr-FR') + ' FCFA'" class="vsell-cart-summary-num"></span>
                                </div>
                                <div class="vsell-cart-summary-divider"></div>
                                <div class="vsell-cart-summary-row vsell-cart-summary-row--total">
                                    <span>Encaisser</span>
                                    <span x-text="total.toLocaleString('fr-FR') + ' FCFA'" class="vsell-cart-summary-num"></span>
                                </div>
                            </div>

                            {{-- Bouton --}}
                            <button type="submit" :disabled="total > {{ (int) $reseller->wallet_balance }}"
                                    :class="total > {{ (int) $reseller->wallet_balance }} ? 'vsell-cart-submit vsell-cart-submit--disabled' : 'vsell-cart-submit'">
                                <svg x-show="total <= {{ (int) $reseller->wallet_balance }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <svg x-show="total > {{ (int) $reseller->wallet_balance }}" x-cloak fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span x-show="total <= {{ (int) $reseller->wallet_balance }}">Encaisser &amp; générer le QR</span>
                                <span x-show="total > {{ (int) $reseller->wallet_balance }}" x-cloak>Solde insuffisant</span>
                            </button>
                            <p x-show="total > {{ (int) $reseller->wallet_balance }}" x-cloak class="vsell-cart-warn">
                                Manque <strong x-text="(total - {{ (int) $reseller->wallet_balance }}).toLocaleString('fr-FR')"></strong> FCFA — demande une recharge.
                            </p>
                        </div>
                    </div>
                </div>
            </form>

        @php */ @endphp
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }

    /* ====================== LAYOUT ====================== */
    .vsell-wrap { max-width: 1200px; margin: 0 auto; }

    .vsell-top {
        display: flex; align-items: flex-start; justify-content: space-between;
        gap: 14px; margin-bottom: 14px;
    }
    .vsell-eyebrow {
        font-family: 'Inter', sans-serif;
        font-size: 11px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.10em;
        color: #44A08D;
    }
    .vsell-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 22px; font-weight: 800;
        color: #0F172A;
        margin: 4px 0 4px;
        letter-spacing: -0.01em;
        line-height: 1.15;
    }
    .vsell-lead { font-size: 12px; color: #64748B; margin: 0; }
    .vsell-lead strong { color: #0F172A; font-weight: 800; }
    .vsell-counter {
        flex-shrink: 0;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white; border-radius: 14px;
        padding: 8px 14px; text-align: center;
        box-shadow: 0 8px 18px -6px rgba(78,205,196,0.45);
        line-height: 1;
    }
    .vsell-counter-num {
        display: block;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 22px; font-weight: 800;
        font-variant-numeric: tabular-nums;
    }
    .vsell-counter-label {
        display: block;
        font-size: 9px; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.10em;
        opacity: 0.85; margin-top: 2px;
    }

    /* ====================== TOOLBAR ====================== */
    .vsell-toolbar {
        position: sticky;
        top: calc(56px + env(safe-area-inset-top));
        z-index: 20;
        background: #F8FAFC;
        padding: 8px 0;
        margin-bottom: 12px;
        display: flex; gap: 8px;
        flex-direction: column;
    }
    @media (min-width: 768px) {
        .vsell-toolbar { flex-direction: row; align-items: center; flex-wrap: wrap; }
    }
    .vsell-actions {
        display: flex;
        align-items: stretch;
        gap: 8px;
    }
    .vsell-actions > .vsell-filter-trigger {
        flex: 1;
        justify-content: center;
    }
    .vsell-actions > .vsell-sort {
        flex-shrink: 0;
    }
    @media (min-width: 768px) {
        .vsell-actions { flex: 0 0 auto; }
        .vsell-actions > .vsell-filter-trigger { flex: 0 0 auto; }
    }

    .vsell-search {
        flex: 1; min-width: 0;
        display: flex; align-items: center; gap: 8px;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        padding: 4px 4px 4px 12px;
        transition: all .15s ease;
    }
    .vsell-search:focus-within {
        border-color: #44A08D;
        box-shadow: 0 0 0 3px rgba(68,160,141,0.12);
    }
    .vsell-search > svg { width: 15px; height: 15px; color: #94A3B8; flex-shrink: 0; }
    .vsell-search input {
        flex: 1; padding: 9px 0;
        background: transparent; border: 0;
        font-size: 13px; outline: none;
        font-family: inherit; color: #0F172A;
        min-width: 0;
    }
    .vsell-search input::placeholder { color: #94A3B8; }
    .vsell-search-clear {
        width: 26px; height: 26px;
        border-radius: 7px;
        background: #F1F5F9; color: #64748B;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 16px; line-height: 1; font-weight: 700;
        text-decoration: none;
        flex-shrink: 0;
    }
    .vsell-search-btn {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 8px 12px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white; border: 0; border-radius: 9px;
        font-family: inherit;
        font-size: 12px; font-weight: 800;
        cursor: pointer;
        box-shadow: 0 6px 14px -4px rgba(68,160,141,0.45);
        flex-shrink: 0;
    }
    .vsell-search-btn svg { width: 13px; height: 13px; }
    .vsell-search-btn-label { display: none; }
    @media (min-width: 540px) { .vsell-search-btn-label { display: inline; } }

    .vsell-sort { position: relative; flex-shrink: 0; }
    .vsell-sort-btn {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 9px 12px;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 11px;
        font-family: inherit;
        font-size: 12px; font-weight: 700;
        color: #475569;
        cursor: pointer;
        white-space: nowrap;
    }
    .vsell-sort-btn:hover { border-color: #44A08D; }
    .vsell-sort-btn > svg:first-child { width: 13px; height: 13px; color: #94A3B8; }
    .vsell-sort-chev { width: 11px; height: 11px; color: #94A3B8; transition: transform .2s ease; }
    .vsell-rot { transform: rotate(180deg); }
    .vsell-sort-menu {
        position: absolute; right: 0; top: calc(100% + 6px);
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 11px;
        box-shadow: 0 12px 28px -8px rgba(15,23,42,0.20);
        min-width: 180px;
        overflow: hidden;
        z-index: 30;
    }
    .vsell-sort-item {
        display: flex; align-items: center; justify-content: space-between;
        padding: 9px 14px;
        font-size: 13px; color: #475569;
        text-decoration: none;
        transition: background .12s ease;
    }
    .vsell-sort-item:hover { background: #F8FAFC; }
    .vsell-sort-item--active { background: #F0FDFA; color: #0F766E; font-weight: 700; }
    .vsell-sort-item svg { width: 12px; height: 12px; }

    .vsell-filter-trigger {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 11px 14px;
        background: linear-gradient(135deg, #0F172A, #1E293B);
        border: 0;
        border-radius: 12px;
        color: white;
        font-family: inherit;
        font-size: 13px; font-weight: 800;
        cursor: pointer;
        flex-shrink: 0;
        box-shadow: 0 8px 18px -8px rgba(15,23,42,0.40);
        transition: transform .15s ease;
    }
    .vsell-filter-trigger:active { transform: scale(0.97); }
    .vsell-filter-trigger svg { width: 14px; height: 14px; color: #5EEAD4; }
    .vsell-filter-trigger-badge {
        background: #5EEAD4; color: #0F172A;
        padding: 1px 7px; border-radius: 9999px;
        font-size: 10px; font-weight: 800;
        font-variant-numeric: tabular-nums;
    }
    @media (min-width: 1024px) {
        .vsell-filter-trigger {
            display: none;
        }
    }

    /* Le sort label est masqué sur très petit écran pour faire de la place */
    .vsell-sort-label { display: inline; }
    @media (max-width: 360px) {
        .vsell-sort-label { display: none; }
    }
    .vsell-sort-btn {
        padding: 11px 14px !important;
        border-radius: 12px !important;
    }

    /* ====================== CHIPS ====================== */
    .vsell-chips {
        display: flex; flex-wrap: wrap; align-items: center; gap: 6px;
        margin-bottom: 14px;
    }
    .vsell-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 10px;
        background: #F1F5F9;
        color: #475569;
        border-radius: 9999px;
        font-size: 11px; font-weight: 600;
        text-decoration: none;
    }
    .vsell-chip span:last-child { font-size: 14px; line-height: 1; opacity: 0.6; }
    .vsell-chip:hover { background: #E2E8F0; }
    .vsell-chip--strong { background: #0F172A; color: white; }
    .vsell-chip--strong:hover { background: #1E293B; }
    .vsell-chip-flag {
        font-family: 'JetBrains Mono','Fira Code',monospace;
        font-size: 10px; padding: 1px 5px;
        background: white; border-radius: 4px;
    }
    .vsell-chip-clear {
        font-size: 11px; font-weight: 700;
        color: #64748B; text-decoration: underline;
        margin-left: 4px;
    }
    .vsell-chip-clear:hover { color: #44A08D; }

    /* ====================== GRID LAYOUT ====================== */
    .vsell-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
        align-items: start;
    }
    @media (min-width: 1024px) {
        .vsell-grid {
            grid-template-columns: 260px 1fr;
        }
    }

    /* ====================== SIDEBAR (filters) ====================== */
    .vsell-sidebar {
        position: relative;
    }
    @media (max-width: 1023px) {
        .vsell-sidebar {
            position: fixed; inset: 0; z-index: 80;
            display: none;
        }
        .vsell-sidebar--open { display: block; }
        .vsell-sidebar-overlay {
            position: absolute; inset: 0;
            background: rgba(15,23,42,0.55);
            backdrop-filter: blur(4px);
        }
        .vsell-sidebar-card {
            position: absolute; left: 0; top: 0; bottom: 0;
            width: min(85%, 360px);
            background: white;
            overflow-y: auto;
            animation: vsell-slide-r .25s ease;
        }
        @keyframes vsell-slide-r {
            from { transform: translateX(-100%); }
            to   { transform: translateX(0); }
        }
    }
    @media (min-width: 1024px) {
        .vsell-sidebar { position: sticky; top: 100px; }
        .vsell-sidebar-overlay { display: none; }
        .vsell-sidebar-close { display: none; }
    }
    .vsell-sidebar-card {
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }

    .vsell-sidebar-head {
        padding: 14px 16px;
        border-bottom: 1px solid #F1F5F9;
        background: linear-gradient(135deg, #FAFBFC, white);
        display: flex; align-items: center; justify-content: space-between; gap: 8px;
    }
    .vsell-sidebar-head-l { display: flex; align-items: center; gap: 10px; }
    .vsell-sidebar-head-icon {
        width: 30px; height: 30px;
        border-radius: 9px;
        background: linear-gradient(135deg, #4ECDC4, #44A08D);
        color: white;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 10px -3px rgba(68,160,141,0.35);
    }
    .vsell-sidebar-head-icon svg { width: 14px; height: 14px; }
    .vsell-sidebar-head h3 {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 15px; font-weight: 800;
        color: #0F172A; margin: 0; line-height: 1.1;
    }
    .vsell-sidebar-head p {
        font-size: 10px; color: #94A3B8;
        margin: 2px 0 0; line-height: 1;
    }
    .vsell-sidebar-clear {
        font-size: 11px; font-weight: 800;
        color: #44A08D; text-decoration: none;
    }
    .vsell-sidebar-clear:hover { text-decoration: underline; }
    .vsell-sidebar-close {
        background: #F1F5F9; border: 0;
        width: 28px; height: 28px;
        border-radius: 8px;
        font-size: 18px; line-height: 1;
        color: #64748B; cursor: pointer;
    }

    .vsell-section { border-bottom: 1px solid #F1F5F9; }
    .vsell-section--last { border-bottom: 0; }
    .vsell-section-toggle {
        width: 100%;
        display: flex; align-items: center; justify-content: space-between;
        padding: 12px 16px;
        background: white; border: 0;
        font-family: inherit;
        font-size: 13px; font-weight: 700;
        color: #0F172A;
        cursor: pointer;
        text-align: left;
    }
    .vsell-section-toggle:hover { background: #FAFBFC; }
    .vsell-section-toggle > span { display: inline-flex; align-items: center; gap: 6px; }
    .vsell-section-count {
        background: #CCFBF1; color: #0F766E;
        padding: 1px 6px; border-radius: 5px;
        font-size: 10px; font-weight: 800;
        font-variant-numeric: tabular-nums;
    }
    .vsell-section-toggle > svg {
        width: 13px; height: 13px;
        color: #94A3B8;
        transition: transform .2s ease;
    }
    .vsell-section-body { padding: 0 12px 12px; }

    .vsell-cat {
        display: flex; align-items: center; justify-content: space-between;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 13px;
        color: #475569;
        text-decoration: none;
        margin-bottom: 2px;
    }
    .vsell-cat:hover { background: #F8FAFC; }
    .vsell-cat--active {
        background: #0F172A; color: white; font-weight: 700;
    }
    .vsell-cat svg { width: 13px; height: 13px; }

    .vsell-price-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
        padding: 0 16px 14px;
    }
    .vsell-price-pill { cursor: pointer; }
    .vsell-price-pill input { display: none; }
    .vsell-price-pill-box {
        padding: 9px 6px;
        border-radius: 9px;
        text-align: center;
        font-size: 12px; font-weight: 800;
        background: white;
        border: 1.5px solid #E2E8F0;
        color: #475569;
        transition: all .15s ease;
    }
    .vsell-price-pill-box:hover { border-color: #44A08D; }
    .vsell-price-pill-box span {
        display: block;
        font-size: 9px; font-weight: 600;
        color: #94A3B8;
        margin-top: 1px;
    }
    .vsell-price-pill-box--active {
        background: #0F172A; color: white;
        border-color: #0F172A;
    }
    .vsell-price-pill-box--active span { color: rgba(255,255,255,0.65); }

    .vsell-country {
        display: flex; align-items: center; gap: 10px;
        padding: 8px 12px;
        border-radius: 8px;
        cursor: pointer;
        transition: background .12s ease;
    }
    .vsell-country:hover { background: #F8FAFC; }
    .vsell-country input {
        width: 16px; height: 16px;
        accent-color: #44A08D;
    }
    .vsell-country-flag {
        font-family: 'JetBrains Mono','Fira Code',monospace;
        font-size: 10px;
        background: #F1F5F9; color: #475569;
        padding: 1px 6px;
        border-radius: 4px;
    }
    .vsell-country > span:last-child {
        font-size: 13px; color: #475569;
    }

    /* ====================== PRODUCTS ====================== */
    /* Colonne de gauche : panier au-dessus des filtres, l'ensemble suit le
       scroll sur grand écran comme le faisait la sidebar seule. */
    .vsell-side-col { display: flex; flex-direction: column; gap: 14px; min-width: 0; }
    @media (min-width: 1024px) {
        .vsell-side-col { position: sticky; top: 84px; align-self: start; }
    }

    /* Pastilles de montant : chaque variante s'ajoute directement au panier. */
    .vsell-variants { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
    .vsell-variant {
        min-height: 36px; padding: 0 11px;
        border: 1px solid #CBD5E1; border-radius: 9px;
        background: #F8FAFC; color: #0F172A;
        font-family: inherit; font-size: 12.5px; font-weight: 700;
        font-variant-numeric: tabular-nums; cursor: pointer;
        transition: border-color .15s ease, background .15s ease, transform .1s ease;
    }
    .vsell-variant:hover { border-color: #44A08D; background: #F0FDF9; color: #0F766E; }
    .vsell-variant:active { transform: scale(.97); }
    .vsell-variant:focus-visible { outline: 3px solid #4ECDC4; outline-offset: 2px; }
    .vsell-variant-more {
        display: inline-flex; align-items: center; min-height: 36px;
        font-size: 12px; font-weight: 700; color: #94A3B8;
    }
    .vsell-variants-hint { font-size: 11px; color: #94A3B8; margin: 6px 0 0; }

    .vsell-country--dead { opacity: .4; cursor: not-allowed; }
    .vsell-country-count { font-size: 11px; color: #94A3B8; font-variant-numeric: tabular-nums; }

    .vsell-products-col { min-width: 0; }
    .vsell-products {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    @media (min-width: 540px) { .vsell-products { grid-template-columns: repeat(3, 1fr); } }
    @media (min-width: 768px) { .vsell-products { grid-template-columns: repeat(3, 1fr); } }
    @media (min-width: 1024px) { .vsell-products { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1200px) { .vsell-products { grid-template-columns: repeat(3, 1fr); } }

    /* === Card racine === */
    .vsell-card {
        position: relative;
        background: white;
        border: 1px solid #F1F5F9;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(15,23,42,0.06),
                    0 4px 14px -8px rgba(15,23,42,0.10);
        transition: transform .3s ease, box-shadow .3s ease;
    }
    .vsell-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 12px -2px rgba(15,23,42,0.08),
                    0 18px 36px -12px rgba(15,23,42,0.18);
    }
    .vsell-card-visual {
        position: relative;
        height: 128px;
        padding: 16px 16px 0;
        overflow: hidden;
    }
    .vsell-card-pattern {
        position: absolute; inset: 0;
        width: 100%; height: 100%;
        opacity: 0.08; pointer-events: none;
    }
    .vsell-card-glow {
        position: absolute; top: -24px; right: -24px;
        width: 96px; height: 96px;
        border-radius: 50%;
        background: rgba(255,255,255,0.15);
        filter: blur(24px);
    }
    .vsell-card-visual-body { position: relative; z-index: 1; color: white; }
    .vsell-card-tag {
        display: block;
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.20em;
        color: rgba(255,255,255,0.80);
        line-height: 1;
    }
    .vsell-card-brand {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 22px; font-weight: 800;
        line-height: 1.15;
        letter-spacing: -0.01em;
        margin: 4px 0 0;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        padding-right: 40px;
        color: white;
    }
    .vsell-card-chip {
        position: absolute; bottom: 12px; right: 12px;
        width: 28px; height: 20px;
        border-radius: 3px;
        background: linear-gradient(135deg, rgba(254,224,94,0.80), rgba(245,158,11,0.60));
        border: 1px solid rgba(252,211,77,0.40);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.50);
    }
    .vsell-card-body {
        position: relative;
        padding: 28px 16px 16px;
        background: white;
    }
    .vsell-card-logo {
        position: absolute;
        top: -24px; left: 16px;
        width: 48px; height: 48px;
        border-radius: 50%;
        background: white;
        box-shadow: 0 4px 6px -1px rgba(15,23,42,0.10), 0 0 0 2px white;
        border: 1px solid #F1F5F9;
        display: flex; align-items: center; justify-content: center;
        overflow: hidden;
        z-index: 2;
    }
    .vsell-card-logo img { width: 100%; height: 100%; object-fit: cover; }
    .vsell-card-logo span {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 18px; font-weight: 800;
        color: #475569;
    }
    .vsell-card-name {
        font-family: 'Inter', sans-serif;
        font-size: 13px; font-weight: 600;
        color: #0F172A;
        line-height: 1.4;
        min-height: 36px;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        margin: 0 0 12px;
    }
    .vsell-card-price-row {
        display: flex; align-items: flex-end; justify-content: space-between;
        gap: 10px;
        padding-top: 12px;
        border-top: 1px solid #F1F5F9;
        margin-bottom: 12px;
    }
    .vsell-card-price-from {
        font-size: 10px; font-weight: 500;
        color: #94A3B8; line-height: 1;
    }
    .vsell-card-price {
        font-family: 'Inter', sans-serif;
        font-size: 16px; font-weight: 900;
        color: #0F172A;
        font-variant-numeric: tabular-nums;
        line-height: 1;
        letter-spacing: -0.01em;
    }
    .vsell-card-add {
        width: 100%;
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        padding: 10px 12px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        border: 0; border-radius: 11px;
        cursor: pointer;
        font-family: 'Inter', sans-serif;
        font-size: 12px; font-weight: 800;
        line-height: 1;
        box-shadow: 0 8px 18px -8px rgba(78,205,196,0.55), inset 0 1px 0 rgba(255,255,255,0.30);
        transition: all .15s ease;
    }
    .vsell-card-add:hover { transform: translateY(-1px); }
    .vsell-card-add:active { transform: scale(0.97); }
    .vsell-card-add svg { width: 13px; height: 13px; }

    /* ====================== EMPTY ====================== */
    .vsell-empty {
        grid-column: 1 / -1;
        background: white;
        border: 1px dashed #CBD5E1;
        border-radius: 16px;
        padding: 40px 20px;
        text-align: center;
        color: #64748B;
    }
    .vsell-empty svg { width: 36px; height: 36px; color: #94A3B8; margin: 0 auto 10px; display: block; }
    .vsell-empty h3 {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 16px; font-weight: 700;
        color: #0F172A; margin: 0 0 4px;
    }
    .vsell-empty p { font-size: 12px; color: #94A3B8; margin: 0 0 14px; }
    .vsell-empty-cta {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 9px 16px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        border-radius: 10px;
        font-size: 12px; font-weight: 800;
        text-decoration: none;
    }

    /* ====================== PAGINATION ====================== */
    .vsell-pagination {
        display: inline-flex; align-items: center; gap: 4px;
        margin: 24px auto 4px;
        justify-content: center;
        width: 100%;
    }
    .vsell-page-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 38px; height: 38px;
        border-radius: 9px;
        background: white;
        border: 1px solid #E2E8F0;
        color: #475569;
        font-size: 13px; font-weight: 700;
        font-variant-numeric: tabular-nums;
        text-decoration: none;
        cursor: pointer;
        transition: all .15s ease;
    }
    .vsell-page-btn:hover { border-color: #44A08D; color: #44A08D; }
    .vsell-page-btn:active { transform: scale(0.95); }
    .vsell-page-btn svg { width: 14px; height: 14px; }
    .vsell-page-btn--current {
        background: #44A08D !important; color: white !important;
        border-color: #44A08D !important;
        box-shadow: 0 6px 14px -4px rgba(68,160,141,0.45);
    }
    .vsell-page-btn--disabled {
        background: #F8FAFC; color: #CBD5E1;
        cursor: not-allowed;
    }
    .vsell-page-btn--disabled:hover { border-color: #E2E8F0; color: #CBD5E1; }
    .vsell-page-gap {
        width: 24px; text-align: center; color: #94A3B8;
    }

    /* =================================================================== */
    /* ============== PANIER VENDEUR (top inline, version pro) ============ */
    /* =================================================================== */
    .vsell-cart-top {
        position: relative;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 20px;
        overflow: hidden;
        margin-bottom: 18px;
        box-shadow: 0 12px 32px -12px rgba(15,23,42,0.14),
                    0 4px 12px -6px rgba(15,23,42,0.06);
        scroll-margin-top: 80px;
    }
    /* Feedback pulse à la 1ʳᵉ ouverture du panier */
    .vct-pulse {
        animation: vct-pulse-anim .7s ease;
    }
    @keyframes vct-pulse-anim {
        0%   { box-shadow: 0 12px 32px -12px rgba(15,23,42,0.14),
                          0 4px 12px -6px rgba(15,23,42,0.06),
                          0 0 0 0 rgba(78,205,196,0.55); }
        50%  { box-shadow: 0 12px 32px -12px rgba(15,23,42,0.14),
                          0 4px 12px -6px rgba(15,23,42,0.06),
                          0 0 0 14px rgba(78,205,196,0); }
        100% { box-shadow: 0 12px 32px -12px rgba(15,23,42,0.14),
                          0 4px 12px -6px rgba(15,23,42,0.06),
                          0 0 0 0 rgba(78,205,196,0); }
    }

    /* ============== HEADER ============== */
    .vct-head {
        background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
        color: white;
        padding: 16px 18px;
        display: flex; align-items: center; justify-content: space-between;
        gap: 12px;
        position: relative; overflow: hidden;
    }
    .vct-head::before {
        content: '';
        position: absolute; top: -60px; right: -60px;
        width: 200px; height: 200px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(78,205,196,0.22) 0%, transparent 70%);
        pointer-events: none;
    }
    .vct-head::after {
        content: '';
        position: absolute; bottom: -40px; left: -20px;
        width: 120px; height: 120px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(125,211,252,0.10) 0%, transparent 70%);
        pointer-events: none;
    }
    .vct-head-l {
        position: relative;
        display: flex; align-items: center; gap: 12px;
        min-width: 0;
    }
    .vct-head-icon {
        flex-shrink: 0;
        width: 38px; height: 38px;
        border-radius: 11px;
        background: linear-gradient(135deg, rgba(78,205,196,0.20), rgba(94,234,212,0.10));
        border: 1px solid rgba(78,205,196,0.30);
        color: #5EEAD4;
        display: flex; align-items: center; justify-content: center;
        backdrop-filter: blur(8px);
    }
    .vct-head-icon svg { width: 18px; height: 18px; }
    .vct-head-eyebrow {
        font-family: 'Inter', sans-serif;
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.10em;
        color: #5EEAD4;
        line-height: 1;
    }
    .vct-head-count {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 16px; font-weight: 800;
        margin-top: 4px;
        line-height: 1.1;
        display: flex; align-items: baseline; gap: 6px;
    }
    .vct-head-count > span:first-child {
        font-size: 22px;
        color: white;
        font-variant-numeric: tabular-nums;
    }
    .vct-head-count > span:last-child {
        font-size: 12px; font-weight: 600;
        color: #94A3B8;
    }
    .vct-clear {
        position: relative;
        flex-shrink: 0;
        display: inline-flex; align-items: center; gap: 6px;
        background: rgba(244,63,94,0.10);
        border: 1px solid rgba(244,63,94,0.25);
        color: #FCA5A5;
        padding: 8px 12px;
        border-radius: 9999px;
        font-family: inherit;
        font-size: 11px; font-weight: 700;
        cursor: pointer;
        transition: all .15s ease;
    }
    .vct-clear:hover { background: rgba(244,63,94,0.18); color: #FECACA; }
    .vct-clear svg { width: 12px; height: 12px; }
    .vct-clear-label { display: none; }
    @media (min-width: 540px) { .vct-clear-label { display: inline; } }

    /* ============== ITEMS LIST ============== */
    .vct-items {
        padding: 14px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        background: #FAFBFC;
    }

    /* ============================================================
       Panier en colonne étroite (260 px) : il occupait auparavant toute la
       largeur de la zone produits. Sans ces règles, la ligne d'article et le
       sélecteur de quantité se chevauchent.
       ============================================================ */
    @media (min-width: 1024px) {
        .vsell-cart-top { font-size: 13px; }
        /* Article : vignette au-dessus du texte plutôt qu'à côté. */
        .vct-item { flex-direction: column; gap: 8px; padding: 10px; }
        .vct-item-media, .vct-item-visual { width: 100%; height: 54px; }
        .vct-item-right, .vct-item-actions {
            display: flex; align-items: center; justify-content: space-between;
            gap: 8px; width: 100%;
        }
        .vct-qty { flex-shrink: 0; }
        .vct-item-name, .vct-item-brand {
            white-space: normal; overflow-wrap: anywhere;
        }
        /* Totaux : libellé et montant sur deux lignes, jamais tronqués. */
        .vct-foot-row, .vct-total {
            flex-wrap: wrap; row-gap: 2px;
        }
    }

    .vct-item {
        display: flex; align-items: stretch; gap: 12px;
        padding: 12px;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        transition: all .2s ease;
        position: relative;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }
    .vct-item:hover {
        border-color: #CBD5E1;
        box-shadow: 0 6px 14px -6px rgba(15,23,42,0.10);
        transform: translateY(-1px);
    }

    /* Mini gift-card visual avec chip dorée */
    .vct-item-visual {
        position: relative;
        flex-shrink: 0;
        width: 56px; height: 56px;
        border-radius: 11px;
        display: flex; align-items: center; justify-content: center;
        overflow: hidden;
        box-shadow: 0 2px 6px -2px rgba(15,23,42,0.20),
                    inset 0 1px 0 rgba(255,255,255,0.20);
    }
    .vct-item-visual img {
        width: 100%; height: 100%;
        object-fit: cover;
    }
    .vct-item-visual > span {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800; font-size: 22px;
        color: white;
    }
    .vct-item-chip {
        position: absolute; bottom: 4px; right: 4px;
        width: 14px; height: 10px;
        border-radius: 2px;
        background: linear-gradient(135deg, rgba(254,224,94,0.85), rgba(245,158,11,0.65));
        border: 1px solid rgba(252,211,77,0.40);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.50);
    }

    .vct-item-body {
        flex: 1;
        min-width: 0;
        display: flex; flex-direction: column;
    }
    .vct-item-brand {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 14px; font-weight: 800;
        color: #0F172A;
        line-height: 1.2;
        letter-spacing: -0.01em;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .vct-item-name {
        font-size: 11px; color: #64748B;
        font-weight: 500;
        line-height: 1.3;
        margin-top: 2px;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
    }

    .vct-item-row {
        display: flex; align-items: flex-end; justify-content: space-between;
        gap: 10px;
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px dashed #F1F5F9;
    }
    .vct-item-row-l { flex: 1; min-width: 0; }
    .vct-item-row-r {
        display: flex; align-items: center; gap: 6px;
        flex-shrink: 0;
    }
    .vct-item-unit {
        display: block;
        font-size: 10px; color: #94A3B8;
        font-variant-numeric: tabular-nums;
        line-height: 1;
    }
    .vct-item-total {
        display: block;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 16px; font-weight: 800;
        color: #44A08D;
        font-variant-numeric: tabular-nums;
        line-height: 1.1;
        margin-top: 3px;
    }
    .vct-item-total-unit {
        font-size: 9px; font-weight: 700;
        color: #94A3B8;
        margin-left: 2px;
    }

    /* Quantité : pill avec − et + */
    .vct-qty {
        display: inline-flex; align-items: center;
        background: #F1F5F9;
        border-radius: 9px;
        padding: 3px;
        gap: 0;
    }
    .vct-qty-btn {
        width: 26px; height: 26px;
        background: white;
        border: 0;
        border-radius: 6px;
        cursor: pointer;
        color: #475569;
        display: inline-flex; align-items: center; justify-content: center;
        transition: all .15s ease;
        box-shadow: 0 1px 2px rgba(15,23,42,0.06);
    }
    .vct-qty-btn:disabled {
        background: transparent;
        opacity: 0.35; cursor: not-allowed;
        box-shadow: none;
    }
    .vct-qty-btn svg { width: 11px; height: 11px; }
    .vct-qty-btn--add {
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        box-shadow: 0 4px 8px -2px rgba(78,205,196,0.50);
    }
    .vct-qty-btn--add:hover { transform: translateY(-1px); }
    .vct-qty-num {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 13px; font-weight: 800;
        color: #0F172A;
        min-width: 26px; text-align: center;
        font-variant-numeric: tabular-nums;
    }

    /* Bouton retirer (icône only, à côté du qty) */
    .vct-remove {
        width: 28px; height: 28px;
        background: transparent;
        border: 0;
        border-radius: 8px;
        color: #94A3B8;
        cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center;
        transition: all .15s ease;
    }
    .vct-remove:hover { background: #FEF2F2; color: #BE123C; }
    .vct-remove svg { width: 13px; height: 13px; }

    /* ============== FOOTER ============== */
    .vct-foot {
        padding: 16px 18px 18px;
        border-top: 1px solid #F1F5F9;
        background: white;
    }

    .vct-fields {
        display: grid;
        grid-template-columns: 1fr;
        gap: 8px;
        margin-bottom: 14px;
    }
    @media (min-width: 540px) { .vct-fields { grid-template-columns: 1fr 1fr; } }

    .vct-field { position: relative; }
    .vct-field svg {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
        width: 14px; height: 14px;
        color: #94A3B8;
        pointer-events: none;
    }
    .vct-field input {
        width: 100%; padding: 11px 14px 11px 36px;
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 11px;
        font-size: 13px; outline: none;
        font-family: inherit;
        color: #0F172A;
        transition: all .15s ease;
    }
    .vct-field input:focus {
        background: white;
        border-color: #44A08D;
        box-shadow: 0 0 0 3px rgba(68,160,141,0.12);
    }
    .vct-field input::placeholder { color: #94A3B8; }

    /* Récap totaux ticket-style */
    .vct-summary {
        background: linear-gradient(135deg, #FAFBFC, #F8FAFC);
        border: 1px solid #E2E8F0;
        border-radius: 13px;
        padding: 12px 14px;
        margin-bottom: 14px;
    }
    .vct-summary-row {
        display: flex; align-items: center; justify-content: space-between;
        gap: 8px;
        padding: 4px 0;
        font-size: 12px;
        color: #64748B;
        font-weight: 500;
    }
    .vct-summary-label {
        display: inline-flex; align-items: center; gap: 6px;
    }
    .vct-summary-label svg { width: 12px; height: 12px; }
    .vct-summary-num {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800;
        color: #0F172A;
        font-variant-numeric: tabular-nums;
    }
    .vct-summary-row--brand {
        color: #0F766E;
    }
    .vct-summary-row--brand .vct-summary-num { color: #44A08D; }
    .vct-summary-divider {
        height: 1px;
        background: linear-gradient(90deg, transparent, #E2E8F0 30%, #E2E8F0 70%, transparent);
        margin: 8px 0;
    }
    .vct-summary-row--total {
        font-size: 13px; font-weight: 800; color: #0F172A;
    }
    .vct-summary-row--total .vct-summary-num {
        font-size: 22px;
        line-height: 1;
        letter-spacing: -0.01em;
    }

    /* Bouton submit */
    .vct-submit {
        width: 100%;
        display: inline-flex; align-items: center; justify-content: center;
        gap: 8px;
        padding: 15px;
        background: linear-gradient(135deg, #44A08D 0%, #4ECDC4 100%);
        color: white;
        border: 0;
        border-radius: 13px;
        font-family: inherit;
        font-size: 14px; font-weight: 800;
        cursor: pointer;
        box-shadow: 0 14px 28px -10px rgba(68,160,141,0.55),
                    inset 0 1px 0 rgba(255,255,255,0.30);
        transition: all .18s ease;
    }
    .vct-submit:hover:not(.vct-submit--disabled) {
        transform: translateY(-2px);
        box-shadow: 0 18px 36px -10px rgba(68,160,141,0.65),
                    inset 0 1px 0 rgba(255,255,255,0.30);
    }
    .vct-submit svg { width: 15px; height: 15px; }
    .vct-submit--disabled {
        background: linear-gradient(135deg, #FCA5A5, #F87171) !important;
        color: white !important;
        cursor: not-allowed;
        box-shadow: 0 8px 18px -8px rgba(244,63,94,0.45) !important;
    }

    .vct-warn {
        display: flex; align-items: center; gap: 6px;
        margin: 10px 0 0;
        padding: 10px 12px;
        background: #FEF2F2;
        border: 1px solid #FECACA;
        border-radius: 10px;
        font-size: 12px; color: #BE123C;
        font-weight: 600;
        line-height: 1.4;
    }
    .vct-warn svg { width: 14px; height: 14px; flex-shrink: 0; color: #BE123C; }
    .vct-warn strong {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
    }

    /* Erreur AJAX checkout */
    .vct-checkout-error {
        display: flex; align-items: center; gap: 8px;
        margin: 10px 0 0;
        padding: 10px 12px;
        background: #FEF2F2;
        border: 1px solid #FECACA;
        border-radius: 10px;
        font-size: 12px; font-weight: 600;
        color: #BE123C;
        line-height: 1.4;
    }
    .vct-checkout-error svg { width: 14px; height: 14px; flex-shrink: 0; }

    /* Spinner sur le bouton submit pendant l'init */
    .vct-spin {
        animation: vct-spin-anim 1s linear infinite;
    }
    .vct-spin circle {
        stroke-dasharray: 50 100;
        stroke-linecap: round;
        opacity: 0.85;
    }
    @keyframes vct-spin-anim {
        to { transform: rotate(360deg); }
    }

    .vsell-ct-head {
        background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
        color: white;
        padding: 13px 16px;
        display: flex; align-items: center; justify-content: space-between;
        gap: 10px;
        position: relative; overflow: hidden;
    }
    .vsell-ct-head::before {
        content: '';
        position: absolute; top: -40px; right: -40px;
        width: 130px; height: 130px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(78,205,196,0.20) 0%, transparent 70%);
        pointer-events: none;
    }
    .vsell-ct-eyebrow {
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.10em;
        color: #5EEAD4;
        line-height: 1;
    }
    .vsell-ct-count {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 16px; font-weight: 800;
        margin-top: 3px;
        line-height: 1;
        position: relative;
    }
    .vsell-ct-clear {
        position: relative;
        background: rgba(244,63,94,0.15);
        border: 1px solid rgba(244,63,94,0.28);
        color: #FCA5A5;
        padding: 6px 12px;
        border-radius: 8px;
        font-family: inherit;
        font-size: 11px; font-weight: 700;
        cursor: pointer;
    }
    .vsell-ct-clear:hover { background: rgba(244,63,94,0.25); }

    /* Liste items horizontale scrollable sur mobile, grille sur desktop */
    .vsell-ct-items {
        padding: 12px;
        display: flex;
        gap: 8px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
    }
    .vsell-ct-items::-webkit-scrollbar { height: 6px; }
    .vsell-ct-items::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 9999px; }

    .vsell-ct-item {
        flex-shrink: 0;
        width: 240px;
        position: relative;
        display: flex; align-items: center; gap: 10px;
        padding: 10px;
        background: #F8FAFC;
        border: 1px solid #F1F5F9;
        border-radius: 11px;
    }
    @media (min-width: 768px) {
        .vsell-ct-items { flex-wrap: wrap; overflow-x: visible; }
        .vsell-ct-item { flex: 1 1 calc(50% - 4px); min-width: 240px; }
    }

    .vsell-ct-item-visual {
        width: 38px; height: 38px;
        border-radius: 9px;
        flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        overflow: hidden;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.18);
    }
    .vsell-ct-item-visual img { width: 100%; height: 100%; object-fit: cover; }
    .vsell-ct-item-visual span {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800; font-size: 16px;
        color: white;
    }
    .vsell-ct-item-body { flex: 1; min-width: 0; }
    .vsell-ct-item-brand {
        font-size: 12px; font-weight: 800; color: #0F172A;
        line-height: 1.2;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .vsell-ct-item-price {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 13px; font-weight: 800;
        color: #44A08D;
        font-variant-numeric: tabular-nums;
        margin-top: 2px;
    }
    .vsell-ct-unit { font-size: 9px; color: #94A3B8; font-weight: 600; }

    .vsell-ct-item-qty {
        display: inline-flex; align-items: center; gap: 0;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        padding: 2px;
        flex-shrink: 0;
    }
    .vsell-ct-item-qty > span {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 12px; font-weight: 800;
        color: #0F172A;
        min-width: 22px; text-align: center;
    }
    .vsell-ct-qty-btn {
        width: 22px; height: 22px;
        background: #F1F5F9; border: 0;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 800; font-size: 13px;
        color: #475569;
        display: inline-flex; align-items: center; justify-content: center;
        line-height: 1;
    }
    .vsell-ct-qty-btn:disabled { opacity: 0.4; cursor: not-allowed; }
    .vsell-ct-qty-btn--add {
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
    }

    .vsell-ct-item-remove {
        position: absolute;
        top: -7px; right: -7px;
        width: 22px; height: 22px;
        border-radius: 50%;
        background: white;
        color: #BE123C;
        border: 1px solid #FECACA;
        font-size: 14px; font-weight: 800;
        line-height: 1;
        cursor: pointer;
        box-shadow: 0 4px 8px -2px rgba(15,23,42,0.15);
        display: inline-flex; align-items: center; justify-content: center;
    }
    .vsell-ct-item-remove:hover { background: #FEF2F2; }

    /* Footer : champs + totaux + bouton */
    .vsell-ct-foot {
        border-top: 1px solid #F1F5F9;
        padding: 14px;
        background: #FAFBFC;
    }
    .vsell-ct-fields {
        display: grid;
        grid-template-columns: 1fr;
        gap: 8px;
    }
    @media (min-width: 540px) { .vsell-ct-fields { grid-template-columns: 1fr 1fr; } }
    .vsell-ct-fields input {
        width: 100%; padding: 10px 12px;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 10px;
        font-size: 13px; outline: none;
        font-family: inherit;
        color: #0F172A;
    }
    .vsell-ct-fields input:focus {
        border-color: #44A08D;
        box-shadow: 0 0 0 3px rgba(68,160,141,0.10);
    }

    .vsell-ct-totals {
        margin-top: 14px; padding-top: 14px;
        border-top: 1px dashed #E2E8F0;
        display: flex; align-items: baseline; justify-content: space-between;
        gap: 10px;
    }
    .vsell-ct-totals-label {
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.08em;
        color: #94A3B8;
    }
    .vsell-ct-totals-value {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 22px; font-weight: 800;
        color: #0F172A;
        font-variant-numeric: tabular-nums;
        margin-top: 2px;
        line-height: 1;
    }
    .vsell-ct-totals-comm {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 14px; font-weight: 800;
        color: #44A08D;
        font-variant-numeric: tabular-nums;
        margin-top: 2px;
        line-height: 1;
    }

    .vsell-ct-submit {
        margin-top: 12px;
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white; border: 0;
        border-radius: 12px;
        font-family: inherit;
        font-size: 14px; font-weight: 800;
        cursor: pointer;
        box-shadow: 0 12px 26px -10px rgba(68,160,141,0.55),
                    inset 0 1px 0 rgba(255,255,255,0.30);
        transition: all .15s ease;
    }
    .vsell-ct-submit:hover:not(.vsell-ct-submit--disabled) { transform: translateY(-1px); }
    .vsell-ct-submit--disabled {
        background: #E2E8F0 !important;
        color: #94A3B8 !important;
        cursor: not-allowed;
        box-shadow: none !important;
    }
    .vsell-ct-warn {
        font-size: 11px; color: #BE123C;
        text-align: center; margin: 8px 0 0;
        font-weight: 600;
    }
    .vsell-ct-warn strong { font-family: 'Space Grotesk','Inter',sans-serif; font-weight: 800; }

    /* (anciennes classes vsell-cart-* conservées pour compatibilité visuelle si nécessaire) */
    .vsell-cart-col { min-width: 0; }

    @media (max-width: 1023px) {
        .vsell-cart {
            position: fixed; inset: 0; z-index: 90;
            display: none;
        }
        .vsell-cart--open { display: block; }
        .vsell-cart-overlay {
            position: absolute; inset: 0;
            background: rgba(15,23,42,0.55);
            backdrop-filter: blur(4px);
        }
        .vsell-cart-card {
            position: absolute; right: 0; top: 0; bottom: 0;
            width: min(92%, 420px);
            background: white;
            display: flex; flex-direction: column;
            animation: vsell-slide-l .25s ease;
        }
        @keyframes vsell-slide-l {
            from { transform: translateX(100%); }
            to   { transform: translateX(0); }
        }
    }
    @media (min-width: 1024px) {
        .vsell-cart { position: sticky; top: 100px; }
        .vsell-cart-overlay { display: none; }
        .vsell-cart-close { display: none; }
        .vsell-cart-fab { display: none !important; }
    }

    .vsell-cart-card {
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 12px 28px -10px rgba(15,23,42,0.15);
        display: flex; flex-direction: column;
        max-height: 100%;
    }

    /* HEAD */
    .vsell-cart-head {
        background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
        color: white;
        padding: 14px 16px;
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
        position: relative; overflow: hidden;
    }
    .vsell-cart-head::before {
        content: '';
        position: absolute; top: -40px; right: -40px;
        width: 130px; height: 130px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(78,205,196,0.20) 0%, transparent 70%);
        pointer-events: none;
    }
    .vsell-cart-head-l {
        position: relative;
        display: flex; align-items: center; gap: 10px;
        min-width: 0;
    }
    .vsell-cart-head-icon {
        width: 32px; height: 32px;
        border-radius: 9px;
        background: rgba(78,205,196,0.18);
        border: 1px solid rgba(78,205,196,0.30);
        color: #5EEAD4;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .vsell-cart-head-icon svg { width: 14px; height: 14px; }
    .vsell-cart-head-r {
        position: relative;
        display: flex; align-items: center; gap: 6px;
        flex-shrink: 0;
    }
    .vsell-cart-eyebrow {
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.10em;
        color: #5EEAD4;
        line-height: 1;
    }
    .vsell-cart-count {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 16px; font-weight: 800;
        margin-top: 3px;
        line-height: 1;
    }
    .vsell-cart-count-qty {
        font-size: 11px; font-weight: 600;
        color: #94A3B8;
        margin-left: 4px;
    }
    .vsell-cart-clear {
        background: rgba(244,63,94,0.18);
        border: 1px solid rgba(244,63,94,0.28);
        color: #FCA5A5;
        width: 30px; height: 30px;
        border-radius: 8px;
        cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .vsell-cart-clear:hover { background: rgba(244,63,94,0.28); }
    .vsell-cart-clear svg { width: 13px; height: 13px; }
    .vsell-cart-close {
        background: rgba(255,255,255,0.08); border: 0;
        color: white;
        width: 30px; height: 30px;
        border-radius: 8px;
        cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .vsell-cart-close svg { width: 14px; height: 14px; }

    /* SOLDE PILL */
    .vsell-cart-balance {
        padding: 10px 16px;
        background: linear-gradient(135deg, #ECFDF5, #D1FAE5);
        border-bottom: 1px solid #A7F3D0;
        display: flex; align-items: center; justify-content: space-between;
        gap: 8px;
    }
    .vsell-cart-balance-label {
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.08em;
        color: #047857;
    }
    .vsell-cart-balance-value {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 13px; font-weight: 800;
        color: #064E3B;
        font-variant-numeric: tabular-nums;
    }

    /* BODY */
    .vsell-cart-body {
        padding: 12px;
        flex: 1;
        overflow-y: auto;
        max-height: 360px;
    }

    .vsell-cart-empty {
        padding: 36px 16px;
        text-align: center;
    }
    .vsell-cart-empty-ico {
        display: inline-flex; align-items: center; justify-content: center;
        width: 52px; height: 52px;
        border-radius: 14px;
        background: #F1F5F9;
        color: #94A3B8;
        margin-bottom: 10px;
    }
    .vsell-cart-empty-ico svg { width: 24px; height: 24px; }
    .vsell-cart-empty-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 14px; font-weight: 700;
        color: #0F172A;
    }
    .vsell-cart-empty-text {
        font-size: 12px; color: #94A3B8;
        margin-top: 3px;
    }

    .vsell-cart-item {
        display: flex; gap: 10px;
        padding: 10px;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        margin-bottom: 8px;
        transition: all .15s ease;
    }
    .vsell-cart-item:hover { border-color: #CBD5E1; box-shadow: 0 4px 10px -3px rgba(15,23,42,0.06); }

    /* Visuel mini-carte avec image ou initiale */
    .vsell-cart-item-visual {
        width: 44px; height: 44px;
        border-radius: 10px;
        flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        overflow: hidden;
        position: relative;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.18);
    }
    .vsell-cart-item-visual img {
        width: 100%; height: 100%;
        object-fit: cover;
    }
    .vsell-cart-item-visual span {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800; font-size: 18px;
        color: white;
    }

    .vsell-cart-item-body { flex: 1; min-width: 0; padding-top: 1px; }
    .vsell-cart-item-brand {
        font-family: 'Inter', sans-serif;
        font-size: 13px; font-weight: 800;
        color: #0F172A;
        line-height: 1.2;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .vsell-cart-item-name {
        font-size: 11px; color: #64748B;
        line-height: 1.3;
        margin-top: 1px;
        overflow: hidden; text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
    }
    .vsell-cart-item-meta {
        display: flex; align-items: baseline; justify-content: space-between;
        gap: 6px;
        margin-top: 6px;
    }
    .vsell-cart-item-unit {
        font-size: 10px; color: #94A3B8;
        font-variant-numeric: tabular-nums;
    }
    .vsell-cart-item-total {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 13px; font-weight: 800;
        color: #0F172A;
        font-variant-numeric: tabular-nums;
    }

    .vsell-cart-item-actions {
        display: flex; flex-direction: column; align-items: flex-end;
        gap: 6px;
        flex-shrink: 0;
    }
    .vsell-cart-qty {
        display: inline-flex; align-items: center; gap: 0;
        background: #F1F5F9;
        border-radius: 8px;
        padding: 2px;
    }
    .vsell-cart-qty-btn {
        width: 24px; height: 24px;
        background: white; border: 0;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 800; font-size: 14px;
        color: #475569;
        display: inline-flex; align-items: center; justify-content: center;
        line-height: 1;
        transition: all .12s ease;
    }
    .vsell-cart-qty-btn:disabled { opacity: 0.4; cursor: not-allowed; }
    .vsell-cart-qty-btn--add {
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
    }
    .vsell-cart-qty-num {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 12px; font-weight: 800;
        color: #0F172A;
        min-width: 22px; text-align: center;
    }
    .vsell-cart-item-remove {
        background: none; border: 0;
        color: #94A3B8;
        cursor: pointer;
        padding: 3px;
        border-radius: 6px;
        display: inline-flex; align-items: center; justify-content: center;
        transition: all .12s ease;
    }
    .vsell-cart-item-remove:hover { color: #BE123C; background: #FEF2F2; }
    .vsell-cart-item-remove svg { width: 13px; height: 13px; }

    /* FOOT */
    .vsell-cart-foot {
        border-top: 1px solid #F1F5F9;
        padding: 14px 16px;
        background: #FAFBFC;
    }

    .vsell-cart-fields {
        display: flex; flex-direction: column; gap: 6px;
    }
    .vsell-cart-field {
        position: relative;
    }
    .vsell-cart-field svg {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
        width: 13px; height: 13px;
        color: #94A3B8;
        pointer-events: none;
    }
    .vsell-cart-field input {
        width: 100%; padding: 9px 12px 9px 34px;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 10px;
        font-size: 13px; outline: none;
        font-family: inherit;
        color: #0F172A;
        transition: all .15s ease;
    }
    .vsell-cart-field input:focus {
        border-color: #44A08D;
        box-shadow: 0 0 0 3px rgba(68,160,141,0.10);
    }

    /* Récap totaux structuré */
    .vsell-cart-summary {
        margin-top: 14px;
        padding: 12px 14px;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 12px;
    }
    .vsell-cart-summary-row {
        display: flex; align-items: center; justify-content: space-between;
        gap: 8px;
        font-size: 12px;
        color: #64748B;
        padding: 3px 0;
    }
    .vsell-cart-summary-row--brand { color: #44A08D; font-weight: 700; }
    .vsell-cart-summary-num {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800;
        color: #0F172A;
        font-variant-numeric: tabular-nums;
    }
    .vsell-cart-summary-row--brand .vsell-cart-summary-num { color: #44A08D; }
    .vsell-cart-summary-divider {
        height: 1px; background: #F1F5F9;
        margin: 8px 0;
    }
    .vsell-cart-summary-row--total {
        font-size: 13px; font-weight: 700; color: #0F172A;
    }
    .vsell-cart-summary-row--total .vsell-cart-summary-num {
        font-size: 18px;
    }

    /* Bouton submit */
    .vsell-cart-submit {
        margin-top: 12px;
        width: 100%;
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        padding: 14px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white; border: 0;
        border-radius: 12px;
        font-family: inherit;
        font-size: 14px; font-weight: 800;
        cursor: pointer;
        box-shadow: 0 12px 26px -10px rgba(68,160,141,0.55),
                    inset 0 1px 0 rgba(255,255,255,0.30);
        transition: all .15s ease;
    }
    .vsell-cart-submit:hover:not(.vsell-cart-submit--disabled) {
        transform: translateY(-1px);
        box-shadow: 0 16px 32px -10px rgba(68,160,141,0.65),
                    inset 0 1px 0 rgba(255,255,255,0.30);
    }
    .vsell-cart-submit svg { width: 14px; height: 14px; }
    .vsell-cart-submit--disabled {
        background: #E2E8F0 !important;
        color: #94A3B8 !important;
        cursor: not-allowed;
        box-shadow: none !important;
    }

    .vsell-cart-warn {
        font-size: 11px; color: #BE123C;
        text-align: center;
        margin: 8px 0 0;
        font-weight: 600;
    }
    .vsell-cart-warn strong {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
    }

    /* Floating cart toggle (mobile) */
    .vsell-cart-fab {
        position: fixed;
        bottom: calc(72px + env(safe-area-inset-bottom));
        left: 50%; transform: translateX(-50%);
        z-index: 50;
        display: inline-flex !important; align-items: center; gap: 8px;
        padding: 12px 20px;
        background: linear-gradient(135deg, #0F172A, #1E293B);
        color: white;
        border: 0; border-radius: 9999px;
        font-family: inherit;
        font-size: 13px; font-weight: 800;
        cursor: pointer;
        box-shadow: 0 16px 32px -8px rgba(15,23,42,0.45),
                    0 0 0 4px rgba(78,205,196,0.30);
    }
    .vsell-cart-fab svg { width: 16px; height: 16px; color: #5EEAD4; }
    .vsell-cart-fab-badge {
        background: #5EEAD4; color: #0F172A;
        padding: 2px 8px;
        border-radius: 9999px;
        font-size: 11px; font-weight: 800;
        font-variant-numeric: tabular-nums;
    }
</style>

@push('scripts')
{{-- Alpine est déjà chargé via le layout vendor.blade.php — ne PAS doubler. --}}
<script>
    function vendorSale() {
        return {
            // Persiste le panier en localStorage pour qu'il survive aux recharges page
            cart: JSON.parse(localStorage.getItem('vendor.cart') || '[]'),
            cartOpen: false,
            filtersOpen: false,
            commissionRate: {{ (float) $reseller->commission_rate }},

            // Réactivité Alpine via les getters — recalculés à chaque modif de cart
            get total()      { return this.cart.reduce((s, i) => s + Number(i.price) * Number(i.quantity), 0); },
            get totalQty()   { return this.cart.reduce((s, i) => s + Number(i.quantity), 0); },
            get commission() { return Math.round(this.total * this.commissionRate / 100); },

            // Sauvegarde auto à chaque mutation du panier
            init() {
                this.$watch('cart', (val) => {
                    try { localStorage.setItem('vendor.cart', JSON.stringify(val)); } catch (e) {}
                }, { deep: true });
            },

            addToCart(item) {
                const wasEmpty = this.cart.length === 0;
                const existingIdx = this.cart.findIndex(i => i.product_id === item.product_id);
                if (existingIdx >= 0) {
                    // Mutation immuable pour forcer Alpine à détecter le changement
                    const next = [...this.cart];
                    next[existingIdx] = { ...next[existingIdx], quantity: Number(next[existingIdx].quantity) + 1 };
                    this.cart = next;
                } else {
                    this.cart = [...this.cart, {
                        ...item,
                        price:    Number(item.price),
                        quantity: 1,
                    }];
                }
                // Scroll vers le panier après ajout (attend qu'Alpine rende le DOM)
                this.$nextTick(() => {
                    const cartEl = document.querySelector('.vsell-cart-top');
                    if (!cartEl) return;
                    const offsetTop = cartEl.getBoundingClientRect().top + window.scrollY;
                    window.scrollTo({ top: Math.max(0, offsetTop - 80), behavior: 'smooth' });
                    if (wasEmpty) {
                        cartEl.classList.add('vct-pulse');
                        setTimeout(() => cartEl.classList.remove('vct-pulse'), 700);
                    }
                });
            },
            updateQty(productId, delta) {
                // Mutation immuable par product_id (robuste aux re-render x-for)
                this.cart = this.cart.map(i =>
                    i.product_id === productId
                        ? { ...i, quantity: Math.max(1, Number(i.quantity) + Number(delta)) }
                        : i
                );
            },
            removeItem(productId) {
                this.cart = this.cart.filter(i => i.product_id !== productId);
            },
        };
    }
</script>
@endpush
@endsection
