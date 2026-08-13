@extends('layouts.app')

@section('title', 'Boutique — Toutes les cartes cadeaux | KardAfrica')
@section('meta_description', 'Plus de 300 marques de cartes cadeaux : Netflix, Apple, Steam, PlayStation, Xbox, Google Play… Filtrez par région et payez par Airtel Money, Moov Money ou carte bancaire.')

@php
    $brandPalette = [
        'Netflix' => '#E50914', 'Spotify' => '#1DB954', 'Apple' => '#000000',
        'iTunes' => '#D60017', 'PlayStation' => '#003791', 'Xbox' => '#107C10',
        'Amazon' => '#FF9900', 'Google' => '#01875F', 'Steam' => '#171A21',
        'Uber' => '#000000', 'Roblox' => '#00A2FF', 'Nintendo' => '#E60012',
        'Disney' => '#0E47A1', 'StarzPlay' => '#7C3AED', 'Talabat' => '#FF5A00',
        'HUAWEI' => '#C7000B', 'IKEA' => '#0058A3',
        // Crypto : noir Binance et orange bitcoin (le libellé est en blanc
        // par-dessus — le jaune #F0B90B ne passerait pas en contraste).
        'Binance' => '#1E2026', 'Crypto' => '#F7931A',
    ];

    $brandColorFor = function ($brandName) use ($brandPalette) {
        foreach ($brandPalette as $key => $color) {
            if (stripos($brandName, $key) !== false) return $color;
        }
        $palette = ['#0F172A', '#44A08D', '#0EA5E9', '#7C3AED', '#DC2626', '#EA580C', '#059669'];
        $hash = 0;
        // Masque 31 bits : garde $hash entier borné (évite l'overflow en float,
        // qui casse le cast en int sous PHP 8.5+).
        for ($i = 0; $i < strlen($brandName); $i++) {
            $hash = (ord($brandName[$i]) + (($hash << 5) - $hash)) & 0x7FFFFFFF;
        }
        $idx = $hash % count($palette);
        return $palette[$idx];
    };

    $sanitizeLogo = function ($url) {
        if ($url && (str_contains($url, 'localhost') || str_contains($url, '127.0.0.1'))) {
            return parse_url($url, PHP_URL_PATH);
        }
        return $url;
    };

    $selectedRegions = $selectedRegions ?? [];
    $selectedBrands  = $selectedBrands ?? [];
    $popularOnly     = $popularOnly ?? false;
    $priceMin        = $priceMin ?? null;
    $priceMax        = $priceMax ?? null;
    $priceBounds     = $priceBounds ?? ['min' => 0, 'max' => 100000];
    $facets          = $facets ?? ['categories' => [], 'regions' => [], 'brands' => []];
    $hasPriceSlider  = $priceMin !== null || $priceMax !== null;

    $activeFiltersCount = (int)!empty($search)
        + (int)!empty($categoryId)
        + count($priceRange ?? [])
        + (int) $hasPriceSlider
        + count($selectedCountries ?? [])
        + count($selectedLoc ?? [])
        + count($selectedRegions)
        + count($selectedBrands)
        + (int) $popularOnly;

    $regionList = [
        ['europe', 'EU',      '🇪🇺'],
        ['usa',    'US',      '🇺🇸'],
        ['global', 'Mondial', '🌍'],
        ['africa', 'Afrique', '🌍'],
    ];

    $currentCategory = $categoryId ? collect($categories)->firstWhere('id', (int) $categoryId) : null;

    $priceRangeLabels = [
        'under_1000' => '< 1 000',
        '1000_5000'  => '1k — 5k',
        '5000_20000' => '5k — 20k',
        'over_20000' => '> 20k',
    ];

    $countryList = [
        ['SN', 'Sénégal'],
        ['CI', 'Côte d\'Ivoire'],
        ['ML', 'Mali'],
        ['GA', 'Gabon'],
        ['CM', 'Cameroun'],
    ];

    $sortOptions = [
        'popular'    => 'Populaire',
        'newest'     => 'Nouveautés',
        'price_asc'  => 'Prix croissant',
        'price_desc' => 'Prix décroissant',
        // Économie la plus forte d'abord (valeur faciale vs prix payé).
        'promo'      => 'Meilleures économies',
    ];
    $sort = $sort ?? 'popular';
    $sortLabel = $sortOptions[$sort] ?? 'Populaire';

    // Helper pour conserver les filtres actuels en changeant un seul param
    $selectedLoc = $selectedLoc ?? [];
    $urlWith = function (array $overrides) use ($search, $categoryId, $priceRange, $priceMin, $priceMax, $selectedCountries, $selectedLoc, $selectedRegions, $selectedBrands, $popularOnly, $sort) {
        $base = [
            'search'      => $search,
            'category'    => $categoryId,
            'price_range' => $priceRange,
            'prix_min'    => $priceMin,
            'prix_max'    => $priceMax,
            'country'     => $selectedCountries,
            'loc'         => $selectedLoc,
            'region'      => $selectedRegions,
            'marque'      => $selectedBrands,
            'popular'     => $popularOnly ? 1 : null,
            'sort'        => $sort,
        ];
        return route('boutique', array_merge($base, $overrides));
    };

    // Toggle d'une marque dans marque[] (état unique pills haut / sidebar / chips)
    $brandToggleUrl = function (string $brand) use ($urlWith, $selectedBrands) {
        $lower  = array_map('mb_strtolower', $selectedBrands);
        $active = in_array(mb_strtolower($brand), $lower, true);
        $next   = $active
            ? array_values(array_filter($selectedBrands, fn ($b) => mb_strtolower($b) !== mb_strtolower($brand)))
            : array_merge($selectedBrands, [$brand]);
        return $urlWith(['marque' => $next, 'page' => null, 'view' => null]);
    };
    $brandIsActive = fn (string $brand) => in_array(mb_strtolower($brand), array_map('mb_strtolower', $selectedBrands), true);

    // Vrais logos de marque (SVG monochromes locaux, colorés à la couleur
    // d'origine via mask CSS). null = pas de logo → fallback initiale.
    $brandAssets = [
        'steam'       => ['steam',       '#171A21'],
        'playstation' => ['playstation', '#003791'],
        'xbox'        => ['xbox',        '#107C10'],
        'netflix'     => ['netflix',     '#E50914'],
        'spotify'     => ['spotify',     '#1DB954'],
        'apple'       => ['apple',       '#000000'],
        'roblox'      => ['roblox',      '#00A2FF'],
        'riot'        => ['riotgames',   '#EB0029'],
        'epic'        => ['epicgames',   '#2A2A2A'],
        'google play' => ['googleplay',  '#01875F'],
        'nintendo'    => ['nintendo',    '#E60012'],
        'amazon'      => ['amazon',      '#FF9900'],
        'disney'      => [null,          '#0E47A1'],
        'deezer'      => ['deezer',      '#A238FF'],
        'binance'     => ['binance',     '#F0B90B'],
    ];
    // $onDark : sur fond sombre (pill active) le logo passe en blanc pour rester lisible.
    $brandMark = function (string $term, string $size = 'w-5 h-5', bool $onDark = false) use ($brandAssets) {
        [$file, $color] = $brandAssets[mb_strtolower($term)] ?? [null, '#334155'];
        $fill = $onDark ? '#FFFFFF' : $color;
        if ($file) {
            $url = asset('logos/brands/' . $file . '.svg');
            return '<span class="' . $size . ' shrink-0 inline-block" aria-hidden="true" style="background-color: ' . $fill . '; -webkit-mask: url(\'' . $url . '\') center / contain no-repeat; mask: url(\'' . $url . '\') center / contain no-repeat;"></span>';
        }
        return '<span class="' . $size . ' shrink-0 rounded-md inline-flex items-center justify-center text-white text-[10px] font-black" style="background:' . $color . ';">' . strtoupper(mb_substr($term, 0, 1)) . '</span>';
    };
@endphp

@section('content')
<div class="bg-[#FAFAF7] min-h-screen pb-20"
     x-data="{ view: localStorage.getItem('boutique-view') || 'grid' }"
     x-init="$watch('view', v => localStorage.setItem('boutique-view', v))">

    {{-- ================================================================
         P1-bis §4 — BARRE COMPACTE STICKY (recherche + Filtres (n))
         Apparaît au scroll sous l'en-tête, disparaît au retour en haut.
         `position: fixed` → hors flux, donc aucun saut de layout (CLS ≈ 0).
         Réutilise le MÊME composant d'autocomplétion (aucun état dupliqué :
         les filtres vivent dans l'URL).
         ================================================================ --}}
    <div id="ka-sticky-bar" class="bg-white/95 backdrop-blur border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5 flex items-center gap-2">
            <form action="{{ route('boutique') }}" method="GET"
                  x-data="searchSuggest()" @click.outside="open = false"
                  class="relative flex items-center flex-1 min-w-0 bg-slate-50 border border-slate-200 rounded-xl pl-3 pr-1 focus-within:bg-white focus-within:border-[#44A08D] transition" data-no-loader>
                <input type="hidden" name="sort" value="{{ $sort }}">
                <svg class="w-4 h-4 text-slate-400 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Rechercher une marque…" autocomplete="off" aria-label="Rechercher une marque"
                       @input="onInput($event.target.value)" @focus="if (items.length || empty) open = true"
                       class="flex-1 min-w-0 bg-transparent border-0 text-slate-900 placeholder-slate-400 focus:ring-0 text-sm focus:outline-none py-2.5">
                <div x-show="open" x-cloak x-transition.opacity
                     class="absolute left-0 right-0 top-full mt-2 z-50 bg-white rounded-xl border border-slate-200 shadow-xl overflow-hidden">
                    <template x-for="item in items" :key="item.url + item.name">
                        <a :href="item.url" class="flex items-center gap-3 px-3 py-2.5 hover:bg-slate-50 transition">
                            <span class="w-8 h-8 rounded-lg bg-slate-100 shrink-0 overflow-hidden flex items-center justify-center">
                                <template x-if="item.logo"><img :src="item.logo" class="w-full h-full object-cover" alt="" loading="lazy"></template>
                                <template x-if="!item.logo"><span class="text-xs font-black text-slate-500" x-text="(item.name || '?')[0]"></span></template>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-slate-900 truncate" x-text="item.name"></span>
                                <span class="block text-[11px] text-slate-400" x-text="item.region"></span>
                            </span>
                            <span class="text-xs font-bold tabular-nums text-[#44A08D] whitespace-nowrap">à partir de <span x-text="item.price"></span></span>
                        </a>
                    </template>
                    <div x-show="empty" class="px-3 py-3 text-sm text-slate-500">
                        Aucune marque trouvée pour «&nbsp;<span class="font-semibold" x-text="lastQuery"></span>&nbsp;».
                    </div>
                </div>
                <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#44A08D] text-white text-xs font-bold shrink-0" aria-label="Lancer la recherche">OK</button>
            </form>

            {{-- Desktop : remonte à la sidebar (toujours visible) ; mobile : ouvre le drawer P1 --}}
            <button type="button" onclick="kaOpenFilters(this)"
                    class="shrink-0 inline-flex items-center gap-1.5 px-3.5 min-h-[44px] rounded-xl bg-slate-900 text-white text-sm font-bold active:scale-95 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#44A08D] focus-visible:ring-offset-2">
                <svg class="w-4 h-4 text-[#5EEAD4]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"/></svg>
                <span class="hidden sm:inline">Filtres</span>
                @if($activeFiltersCount > 0)
                    <span class="px-1.5 py-0.5 rounded-md bg-[#44A08D] text-white text-[10px] font-bold tabular-nums">{{ $activeFiltersCount }}</span>
                @endif
            </button>
        </div>
    </div>

    {{-- ================================================================
         TOP STRIP — breadcrumb + titre+badge + recherche + accès rapides
         ================================================================ --}}
    <section id="ka-header" class="bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-1.5 text-xs text-slate-500 pt-5 pb-2">
                <a href="{{ route('home') }}" class="hover:text-[#44A08D] transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Accueil
                </a>
                <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ route('boutique') }}" class="hover:text-[#44A08D] transition">Boutique</a>
                @if($currentCategory)
                    <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-slate-900 font-medium">{{ $currentCategory['name'] }}</span>
                @endif
                @if($search)
                    <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-slate-900 font-medium">Recherche</span>
                @endif
            </nav>
            {{-- ============================================================
                 P1-bis (11/08) — EN-TÊTE RÉAGENCÉ EN « BARRE DE COMMANDE »
                 Ordre : titre+badge → RECHERCHE (1er élément interactif) →
                 accès rapides → marques → chips. Aucun nouvel état : tout
                 réutilise les filtres du P1 (URL = source unique).
                 ============================================================ --}}

            {{-- a. Titre + compteur en badge --}}
            <div class="pt-1 pb-3 flex items-center gap-3 flex-wrap">
                <h1 class="font-display text-xl md:text-3xl font-bold text-slate-900 tracking-tight leading-tight">
                    @if($search)
                        Résultats pour <span class="text-[#44A08D]">"{{ $search }}"</span>
                    @elseif(count($selectedBrands) === 1)
                        Cartes {{ $selectedBrands[0] }}
                    @elseif($currentCategory)
                        {{ $currentCategory['name'] }}
                    @else
                        Toutes les cartes cadeaux
                    @endif
                </h1>
                @if(isset($pagination))
                    <span x-data="countUp({{ (int) $pagination['total'] }})" x-init="run()"
                          class="inline-flex items-center px-2.5 py-1 rounded-full bg-teal-50 border border-teal-100 text-[#44A08D] text-xs md:text-sm font-bold tabular-nums"
                          aria-label="{{ $pagination['total'] }} cartes correspondent à votre recherche">
                        <span x-text="display"></span>
                    </span>
                @endif
                @if($activeFiltersCount > 0)
                    <span class="text-xs text-slate-400">{{ $activeFiltersCount }} filtre{{ $activeFiltersCount > 1 ? 's' : '' }} actif{{ $activeFiltersCount > 1 ? 's' : '' }}</span>
                @endif
            </div>

            {{-- b. RECHERCHE — pleine largeur, premier élément interactif --}}
            <div class="pb-3">
<form action="{{ route('boutique') }}" method="GET"
                      x-data="searchSuggest()" @click.outside="open = false"
                      class="relative flex items-center bg-white border border-slate-200 rounded-2xl pl-4 pr-1.5 py-1.5 shadow-card focus-within:border-[#44A08D] focus-within:ring-2 focus-within:ring-[#44A08D]/15 transition w-full" data-no-loader>
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    <svg class="w-5 h-5 text-slate-400 mr-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Rechercher une marque…" data-placeholder-lg="Rechercher une marque… (Netflix, PSN, Google Play)" autocomplete="off" aria-label="Rechercher une marque"
                           @input="onInput($event.target.value)" @focus="if (items.length || empty) open = true"
                           class="flex-1 min-w-0 bg-transparent border-0 text-slate-900 placeholder-slate-400 focus:ring-0 text-sm md:text-base focus:outline-none py-2.5">

                    {{-- Dropdown suggestions --}}
                    <div x-show="open" x-cloak x-transition.opacity
                         class="absolute left-0 right-0 top-full mt-2 z-40 bg-white rounded-xl border border-slate-200 shadow-xl overflow-hidden">
                        <template x-for="item in items" :key="item.url + item.name">
                            <a :href="item.url" class="flex items-center gap-3 px-3 py-2.5 hover:bg-slate-50 transition">
                                <span class="w-8 h-8 rounded-lg bg-slate-100 shrink-0 overflow-hidden flex items-center justify-center">
                                    <template x-if="item.logo"><img :src="item.logo" class="w-full h-full object-cover" alt="" loading="lazy"></template>
                                    <template x-if="!item.logo"><span class="text-xs font-black text-slate-500" x-text="(item.name || '?')[0]"></span></template>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold text-slate-900 truncate" x-text="item.name"></span>
                                    <span class="block text-[11px] text-slate-400" x-text="item.region"></span>
                                </span>
                                <span class="text-xs font-bold tabular-nums text-[#44A08D] whitespace-nowrap">à partir de <span x-text="item.price"></span></span>
                            </a>
                        </template>
                        <div x-show="empty" class="px-3 py-3 text-sm text-slate-500">
                            Aucune marque trouvée pour «&nbsp;<span class="font-semibold" x-text="lastQuery"></span>&nbsp;».
                        </div>
                    </div>
                    @if($search)
                        <a href="{{ $urlWith(['search' => null, 'page' => null]) }}" class="w-7 h-7 rounded-md flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 mr-1 shrink-0" aria-label="Effacer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-gradient-to-br from-[#44A08D] to-[#4ECDC4] text-white text-sm font-bold shadow-md shadow-[#44A08D]/30 active:scale-95 transition shrink-0"
                            aria-label="Lancer la recherche">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <span class="hidden sm:inline">OK</span>
                    </button>
                </form>

            </div>

            {{-- c. Accès rapides : régions + Top Afrique (scroll horizontal + fondu sur mobile) --}}
            @php
                $frActive = in_array('FR', $selectedLoc, true);
                $frLoc = $frActive
                    ? array_values(array_diff($selectedLoc, ['FR']))
                    : array_merge($selectedLoc, ['FR']);
                // Style unifié des pills (repos / survol / sélectionné)
                $pillBase = 'ka-pill inline-flex items-center gap-1.5 px-3.5 min-h-[44px] rounded-full text-xs font-bold whitespace-nowrap transition active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#44A08D] focus-visible:ring-offset-2 snap-start';
                $pillOff  = 'bg-white border border-slate-200 text-slate-700 hover:border-[#44A08D] hover:bg-teal-50';
                $pillOn   = 'bg-slate-900 text-white shadow-md';
            @endphp
            <div class="relative pb-2">
                <div class="flex items-center gap-2 overflow-x-auto snap-x sm:flex-wrap sm:overflow-visible -mx-4 px-4 sm:mx-0 sm:px-0 pb-1 ka-hide-scroll">
                    @foreach([['loc', 'FR', '🇫🇷', 'France', $frActive, $frLoc], ['region', 'europe', '🇪🇺', 'EU', in_array('europe', $selectedRegions, true), null], ['region', 'usa', '🇺🇸', 'US', in_array('usa', $selectedRegions, true), null]] as [$param, $val, $flag, $label, $isOn, $locNext])
                        @php
                            $href = $param === 'loc'
                                ? $urlWith(['loc' => $locNext, 'page' => null])
                                : $urlWith(['region' => $isOn
                                    ? array_values(array_diff($selectedRegions, [$val]))
                                    : array_merge($selectedRegions, [$val]), 'page' => null]);
                        @endphp
                        <a href="{{ $href }}" role="button" aria-pressed="{{ $isOn ? 'true' : 'false' }}"
                           class="{{ $pillBase }} {{ $isOn ? $pillOn : $pillOff }}">
                            <span class="text-sm leading-none">{{ $flag }}</span>
                            {{ $label }}
                            @if($isOn)
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @endif
                        </a>
                    @endforeach

                    <span class="w-px h-6 bg-slate-200 mx-1 shrink-0 hidden sm:block"></span>

                    <a href="{{ $urlWith(['popular' => $popularOnly ? null : 1, 'page' => null]) }}"
                       role="button" aria-pressed="{{ $popularOnly ? 'true' : 'false' }}"
                       class="{{ $pillBase }} {{ $popularOnly
                            ? 'bg-gradient-to-br from-orange-500 to-rose-500 text-white shadow-md shadow-rose-500/30'
                            : 'bg-white border border-slate-200 text-slate-700 hover:border-orange-300 hover:bg-orange-50' }}">
                        <span class="text-sm leading-none">🔥</span>
                        Top Afrique
                        @if($popularOnly)
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        @endif
                    </a>
                </div>
                <div class="ka-fade sm:hidden" aria-hidden="true"></div>
            </div>

            {{-- d. MARQUES --}}

            {{-- Marques populaires (raccourcis recherche directe) --}}
            @php
                $brandQuickPills = [
                    ['Steam',      'Steam',      '#171A21'],
                    ['PSN',        'Playstation','#003791'],
                    ['Xbox',       'Xbox',       '#107C10'],
                    ['Netflix',    'Netflix',    '#E50914'],
                    ['Deezer',     'Deezer',     '#A238FF'],
                    ['Apple',      'Apple',      '#000000'],
                    ['Roblox',     'Roblox',     '#00A2FF'],
                    ['Riot',       'Riot',       '#D13639'],
                    ['Epic',       'Epic',       '#2A2A2A'],
                    ['Google Play','Google Play','#01875F'],
                    ['Binance',    'Binance',    '#F0B90B'],
                ];
            @endphp
            <div class="relative pb-3">
                <div class="flex items-center gap-2 overflow-x-auto snap-x sm:flex-wrap sm:overflow-visible -mx-4 px-4 sm:mx-0 sm:px-0 pb-1 ka-hide-scroll">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mr-0.5 whitespace-nowrap shrink-0">Marques</span>
                    @foreach($brandQuickPills as [$label, $term, $color])
                        @php $bIsActive = $brandIsActive($term); @endphp
                        <a href="{{ $brandToggleUrl($term) }}"
                           role="button" aria-pressed="{{ $bIsActive ? 'true' : 'false' }}"
                           class="{{ $pillBase }} pl-2.5 {{ $bIsActive ? $pillOn . ' ring-2 ring-slate-900/10' : $pillOff }}">
                            {{-- Vrai logo, couleur d'origine (blanc sur pill active) --}}
                            {!! $brandMark($term, 'w-4 h-4', $bIsActive) !!}
                            {{ $label }}
                            @if($bIsActive)
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @endif
                        </a>
                    @endforeach
                </div>
                <div class="ka-fade sm:hidden" aria-hidden="true"></div>
            </div>

                {{-- Actions row : Filtres mobile + Tri + View (alignés à droite en desktop) --}}
                <div class="flex items-center gap-2 lg:justify-end pb-3">
                    {{-- Bouton Filtres mobile (full-width avant 1024px) --}}
                    <button type="button" @click="$dispatch('open-filters')"
                            class="lg:hidden flex items-center justify-center gap-1.5 flex-1 px-3 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-bold active:scale-95 transition">
                        <svg class="w-4 h-4 text-[#5EEAD4]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"/></svg>
                        Filtres
                        @if($activeFiltersCount > 0)
                            <span class="px-1.5 py-0.5 rounded-md bg-[#44A08D] text-white text-[10px] font-bold tabular-nums">{{ $activeFiltersCount }}</span>
                        @endif
                    </button>

                    {{-- Sort --}}
                    <div class="relative shrink-0" x-data="{ open: false }" @click.away="open = false">
                        <button @click="open = !open" type="button" class="flex items-center gap-1.5 px-3 py-2.5 md:py-2 rounded-xl md:rounded-lg bg-slate-50 border border-slate-200 hover:bg-white hover:border-[#44A08D] text-sm font-semibold text-slate-700 transition active:scale-95">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/></svg>
                            <span class="hidden sm:inline">{{ $sortLabel }}</span>
                            <svg class="w-3 h-3 text-slate-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-transition class="absolute right-0 mt-1.5 w-44 bg-white rounded-xl border border-slate-200 shadow-pop overflow-hidden z-30" style="display:none;">
                            @foreach ($sortOptions as $value => $label)
                                <a href="{{ $urlWith(['sort' => $value, 'page' => null]) }}"
                                   class="flex items-center justify-between px-3 py-2 text-sm transition
                                          {{ $sort === $value ? 'bg-slate-50 text-[#44A08D] font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
                                    <span>{{ $label }}</span>
                                    @if($sort === $value)
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- View toggle (caché < md). Fix 11/08 : en MODE LIGNES le
                         toggle Alpine ne s'appliquait à rien (« le bouton liste ne
                         change pas la page ») → cliquer « liste » bascule désormais
                         vers le listing plat (?view=all) en vue liste. --}}
                    <div class="hidden md:flex items-center bg-slate-50 border border-slate-200 rounded-lg p-0.5 shrink-0">
                        @if($rows !== null)
                            {{-- Mode lignes : grille = état courant ; liste = navigation vers le listing --}}
                            <span class="w-8 h-8 rounded-md flex items-center justify-center bg-white shadow-sm text-[#44A08D]" aria-label="Vue grille (actuelle)">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            </span>
                            <button type="button"
                                    @click="localStorage.setItem('boutique-view', 'list'); window.location.href = '{{ $urlWith(['view' => 'all', 'page' => null]) }}'"
                                    class="w-8 h-8 rounded-md flex items-center justify-center text-slate-500 hover:text-slate-700 transition" aria-label="Vue liste">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            </button>
                        @else
                            <button @click="view='grid'" type="button"
                                    :class="view === 'grid' ? 'bg-white shadow-sm text-[#44A08D]' : 'text-slate-500 hover:text-slate-700'"
                                    class="w-8 h-8 rounded-md flex items-center justify-center transition" aria-label="Vue grille">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            </button>
                            <button @click="view='list'" type="button"
                                    :class="view === 'list' ? 'bg-white shadow-sm text-[#44A08D]' : 'text-slate-500 hover:text-slate-700'"
                                    class="w-8 h-8 rounded-md flex items-center justify-center transition" aria-label="Vue liste">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            </button>
                        @endif
                    </div>
                </div>

            {{-- Active filter chips — scroll horizontal sur mobile (P1 §8) --}}
            @if($activeFiltersCount > 0)
                <div class="flex flex-nowrap sm:flex-wrap items-center gap-2 pb-4 overflow-x-auto sm:overflow-visible -mx-4 px-4 sm:mx-0 sm:px-0 whitespace-nowrap sm:whitespace-normal"
                     style="scrollbar-width: none;">
                    @if($search)
                        <a href="{{ route('boutique', request()->except(['search', 'page'])) }}"
                           class="group inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-900 text-white text-xs font-medium hover:bg-slate-700 transition">
                            <span>Recherche : {{ Str::limit($search, 20) }}</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                    @if($currentCategory)
                        <a href="{{ route('boutique', request()->except(['category', 'page'])) }}"
                           class="group inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-900 text-white text-xs font-medium hover:bg-slate-700 transition">
                            <span>{{ $currentCategory['name'] }}</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                    @foreach ($priceRange ?? [] as $range)
                        <a href="{{ route('boutique', ['search' => $search, 'category' => $categoryId, 'price_range' => array_values(array_diff($priceRange, [$range])), 'country' => $selectedCountries]) }}"
                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-medium hover:bg-slate-200 transition">
                            <span>{{ $priceRangeLabels[$range] ?? $range }} FCFA</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endforeach
                    @foreach ($selectedCountries ?? [] as $country)
                        <a href="{{ route('boutique', ['search' => $search, 'category' => $categoryId, 'price_range' => $priceRange, 'country' => array_values(array_diff($selectedCountries, [$country]))]) }}"
                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-medium hover:bg-slate-200 transition">
                            <span class="font-mono text-[10px] px-1 py-0.5 rounded bg-white">{{ $country }}</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endforeach
                    @foreach ($selectedRegions as $r)
                        @php $rLabel = collect($regionList)->firstWhere(0, $r)[1] ?? $r; @endphp
                        <a href="{{ $urlWith(['region' => array_values(array_diff($selectedRegions, [$r])), 'page' => null]) }}"
                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-medium hover:bg-slate-200 transition">
                            <span>{{ $rLabel }}</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endforeach
                    @php $locLabels = ['FR' => 'France', 'US' => 'US', 'GB' => 'Royaume-Uni', 'DE' => 'Allemagne', 'IT' => 'Italie', 'ES' => 'Espagne', 'BE' => 'Belgique', 'CA' => 'Canada', 'EU' => 'EU']; @endphp
                    @foreach ($selectedLoc as $lc)
                        <a href="{{ $urlWith(['loc' => array_values(array_diff($selectedLoc, [$lc])), 'page' => null]) }}"
                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-medium hover:bg-slate-200 transition">
                            <span>{{ $locLabels[$lc] ?? $lc }}</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endforeach
                    @if($hasPriceSlider)
                        <a href="{{ $urlWith(['prix_min' => null, 'prix_max' => null, 'page' => null]) }}"
                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-medium hover:bg-slate-200 transition">
                            <span class="tabular-nums">{{ number_format($priceMin ?? $priceBounds['min'], 0, ',', ' ') }} – {{ number_format($priceMax ?? $priceBounds['max'], 0, ',', ' ') }} FCFA</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                    @foreach ($selectedBrands as $b)
                        <a href="{{ $brandToggleUrl($b) }}"
                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-900 text-white text-xs font-bold hover:bg-slate-700 transition">
                            <span>{{ $b }}</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endforeach
                    @if($popularOnly)
                        <a href="{{ $urlWith(['popular' => null, 'page' => null]) }}"
                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-gradient-to-br from-orange-500 to-rose-500 text-white text-xs font-bold hover:opacity-90 transition">
                            <span>🔥 Top Afrique</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                    <a href="{{ route('boutique') }}" class="text-xs font-semibold text-slate-500 hover:text-[#44A08D] underline ml-1 transition">
                        Tout effacer
                    </a>
                </div>
            @endif
        </div>
    </section>

    {{-- ================================================================
         CONTENT — Sidebar + Grid
         ================================================================ --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
        <div class="grid grid-cols-1 lg:grid-cols-[300px_1fr] gap-6">

            {{-- ============================
                 SIDEBAR — drawer mobile / sticky desktop
                 ============================ --}}
            <aside x-data="{ mobileOpen: false }"
                   @open-filters.window="mobileOpen = true"
                   @keydown.escape.window="mobileOpen = false"
                   :class="mobileOpen ? 'fixed inset-0 z-50' : ''"
                   class="lg:sticky lg:top-[120px] lg:self-start lg:!relative lg:!inset-auto lg:!z-auto">

                {{-- Overlay mobile --}}
                <div x-show="mobileOpen" x-transition.opacity x-cloak
                     @click="mobileOpen = false"
                     class="lg:hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

                <form id="filterForm" action="{{ route('boutique') }}" method="GET"
                      :class="mobileOpen
                        ? 'fixed left-0 top-0 bottom-0 w-[88%] max-w-sm overflow-y-auto rounded-r-2xl rounded-l-none border-r border-slate-200'
                        : 'hidden lg:block'"
                      class="lg:!static lg:!w-auto lg:!max-w-none lg:!overflow-visible lg:rounded-2xl lg:!border bg-white border border-slate-200 shadow-card overflow-hidden">
                    {{-- État COMPLET préservé au submit (fix 10/08 : cocher un prix
                         perdait la catégorie/région choisies — seul search était hidden).
                         price_range/loc/region/marque/popular sont des inputs du form. --}}
                    @if($search)<input type="hidden" name="search" value="{{ $search }}">@endif
                    @if($categoryId)<input type="hidden" name="category" value="{{ $categoryId }}">@endif
                    @if($sort !== 'popular')<input type="hidden" name="sort" value="{{ $sort }}">@endif
                    @foreach($selectedCountries ?? [] as $c)
                        <input type="hidden" name="country[]" value="{{ $c }}">
                    @endforeach

                    {{-- Header --}}
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3 bg-gradient-to-br from-slate-50 to-white sticky top-0 z-10">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#4ECDC4] to-[#44A08D] flex items-center justify-center text-white shadow-md shadow-[#44A08D]/20 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-display text-base font-bold text-slate-900 leading-none">Filtres</h3>
                                @if($activeFiltersCount > 0)
                                    <p class="text-[11px] text-slate-500 mt-1 leading-none">{{ $activeFiltersCount }} actif{{ $activeFiltersCount > 1 ? 's' : '' }}</p>
                                @else
                                    <p class="text-[11px] text-slate-500 mt-1 leading-none">Affinez votre recherche</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            @if($activeFiltersCount > 0)
                                <a href="{{ route('boutique') }}" class="text-xs font-semibold text-[#44A08D] hover:underline">
                                    Effacer
                                </a>
                            @endif
                            <button type="button" @click="mobileOpen = false" class="lg:hidden w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center active:scale-95 transition" aria-label="Fermer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- ===== Top Afrique : switch en TÊTE de sidebar (P1 §6) ===== --}}
                    <div class="px-5 py-4 border-b border-slate-100">
                        <label class="flex items-center justify-between gap-3 cursor-pointer select-none">
                            <span class="flex items-center gap-2 text-sm font-bold text-slate-900">
                                <span class="text-base leading-none">🔥</span>
                                Top Afrique uniquement
                            </span>
                            <input type="checkbox" name="popular" value="1" class="sr-only peer"
                                   onchange="this.form.submit()" {{ $popularOnly ? 'checked' : '' }}>
                            <span class="relative w-10 h-6 rounded-full transition-colors shrink-0
                                         {{ $popularOnly ? 'bg-orange-500' : 'bg-slate-200' }}">
                                <span class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all
                                             {{ $popularOnly ? 'left-[calc(100%-1.375rem)]' : 'left-0.5' }}"></span>
                            </span>
                        </label>
                        <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">Steam, PSN, Xbox, Netflix, Deezer, Apple, Roblox, Riot, Epic.</p>
                    </div>

                    {{-- ===== Section : Catégories ===== --}}
                    <div x-data="{ open: true }" class="border-b border-slate-100">
                        <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50/50 transition">
                            <span class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                                Catégories
                                @if($categoryId)
                                    <span class="px-1.5 py-0.5 rounded-md bg-teal-50 text-[#44A08D] text-[10px] font-bold">1</span>
                                @endif
                            </span>
                            <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-collapse class="px-3 pb-4 space-y-0.5">
                            <a href="{{ route('boutique', array_merge(request()->except(['category', 'page']), ['page' => 1])) }}"
                               class="flex items-center justify-between px-3 py-2 rounded-lg text-sm transition
                                      {{ !$categoryId ? 'bg-slate-900 text-white font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
                                <span>Toutes</span>
                                @if(!$categoryId)
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                @endif
                            </a>
                            @php
                                // P1 §4 — icônes sobres par catégorie (clé 'icon' du service)
                                $catIconPaths = [
                                    'film'          => 'M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z',
                                    'gamepad-2'     => 'M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                                    'music'         => 'M9 19V6l12-3v13M9 19a3 3 0 11-6 0 3 3 0 016 0zm12-3a3 3 0 11-6 0 3 3 0 016 0z',
                                    'shopping-cart' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
                                    'tv'            => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                                    'map'           => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
                                    'sparkles'      => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
                                    // Crypto : pièce + symbole ₿ stylisé
                                    'bitcoin'       => 'M12 21a9 9 0 100-18 9 9 0 000 18zM9.5 8h4a2 2 0 010 4h-4m0 0h4.5a2 2 0 010 4H9.5m0-8v8m2-10v2m0 8v2',
                                    // Logiciels : écran + socle (licences, antivirus, VPN)
                                    'monitor'       => 'M9.75 17h4.5m-2.25 0v3m-6 0h8m-9-4h10a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v5a2 2 0 002 2z',
                                    // Mobile & Recharges : téléphone
                                    'smartphone'    => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
                                    // App Store & Google Play : grille d'applications.
                                    // Distincte de `smartphone` : les deux rayons se
                                    // suivaient dans le filtre avec la même icône.
                                    'grid'          => 'M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z',
                                ];
                            @endphp
                            @foreach($categories as $category)
                                @php
                                    $catCount = (int) ($facets['categories'][$category['id']] ?? 0);
                                    $catActive = $categoryId == $category['id'];
                                    $iconPath = $catIconPaths[$category['icon'] ?? ''] ?? null;
                                @endphp
                                @if($catCount === 0 && !$catActive)
                                    {{-- Option à 0 : grisée, non cliquable, jamais masquée (P1 §4) --}}
                                    <span class="flex items-center justify-between px-3 py-2 rounded-lg text-sm text-slate-300 cursor-not-allowed select-none">
                                        <span class="flex items-center gap-2">
                                            @if($iconPath)<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/></svg>@endif
                                            {{ $category['name'] }}
                                        </span>
                                        <span class="text-[11px] tabular-nums">0</span>
                                    </span>
                                @else
                                    <a href="{{ route('boutique', array_merge(request()->except(['category', 'page']), ['category' => $category['id'], 'page' => 1])) }}"
                                       class="flex items-center justify-between px-3 py-2 rounded-lg text-sm transition
                                              {{ $catActive ? 'bg-slate-900 text-white font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
                                        <span class="flex items-center gap-2">
                                            @if($iconPath)<svg class="w-4 h-4 {{ $catActive ? 'text-white' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/></svg>@endif
                                            {{ $category['name'] }}
                                        </span>
                                        <span class="text-[11px] tabular-nums {{ $catActive ? 'text-white/80' : 'text-slate-400' }}">{{ number_format($catCount, 0, ',', ' ') }}</span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- ===== Section : Prix — SLIDER double curseur (P1 §5).
                         Bornes = prix réels du catalogue ; debounce 400 ms ;
                         s'applique aux variantes (filtre avant dédoublonnage). ===== --}}
                    <div x-data="priceSlider({
                            boundMin: {{ (int) $priceBounds['min'] }},
                            boundMax: {{ (int) $priceBounds['max'] }},
                            curMin:   {{ $priceMin !== null ? (int) $priceMin : 'null' }},
                            curMax:   {{ $priceMax !== null ? (int) $priceMax : 'null' }},
                         })"
                         class="border-b border-slate-100">
                        <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50/50 transition">
                            <span class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                                Prix (FCFA)
                                @if($hasPriceSlider)
                                    <span class="px-1.5 py-0.5 rounded-md bg-teal-50 text-[#44A08D] text-[10px] font-bold">1</span>
                                @endif
                            </span>
                            <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-collapse class="px-5 pb-5">
                            {{-- Double range superposé --}}
                            <div class="relative h-6 mt-1">
                                <div class="absolute top-1/2 -translate-y-1/2 left-0 right-0 h-1.5 rounded-full bg-slate-200"></div>
                                <div class="absolute top-1/2 -translate-y-1/2 h-1.5 rounded-full bg-[#44A08D]"
                                     :style="`left:${pct(vMin)}%; right:${100 - pct(vMax)}%`"></div>
                                <input type="range" :min="boundMin" :max="boundMax" step="100"
                                       x-model.number="vMin" @input="clampMin(); schedule()"
                                       class="price-thumb absolute inset-0 w-full appearance-none bg-transparent pointer-events-none">
                                <input type="range" :min="boundMin" :max="boundMax" step="100"
                                       x-model.number="vMax" @input="clampMax(); schedule()"
                                       class="price-thumb absolute inset-0 w-full appearance-none bg-transparent pointer-events-none">
                            </div>
                            {{-- Champs min / max --}}
                            <div class="flex items-center gap-2 mt-3">
                                <div class="flex-1">
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Min</label>
                                    <input type="number" :min="boundMin" :max="boundMax" step="100"
                                           x-model.number="vMin" @input="clampMin(); schedule()"
                                           class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm tabular-nums focus:border-[#44A08D] focus:ring-[#44A08D]">
                                </div>
                                <span class="text-slate-300 mt-4">—</span>
                                <div class="flex-1">
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Max</label>
                                    <input type="number" :min="boundMin" :max="boundMax" step="100"
                                           x-model.number="vMax" @input="clampMax(); schedule()"
                                           class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-sm tabular-nums focus:border-[#44A08D] focus:ring-[#44A08D]">
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-2 tabular-nums"
                               x-text="fmt(vMin) + ' – ' + fmt(vMax) + ' FCFA'"></p>
                            {{-- Les valeurs soumises (uniquement si resserrées vs bornes) --}}
                            <input type="hidden" name="prix_min" :value="vMin > boundMin ? vMin : ''" :disabled="vMin <= boundMin">
                            <input type="hidden" name="prix_max" :value="vMax < boundMax ? vMax : ''" :disabled="vMax >= boundMax">
                        </div>
                    </div>

                    {{-- ===== Section : Région (P1 §6 — checkboxes multi, MÊME état
                         que les pills du haut : params loc[] / region[]) ===== --}}
                    <div x-data="{ open: true }" class="border-b border-slate-100">
                        <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50/50 transition">
                            <span class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                                Région
                                @if(count($selectedLoc) + count($selectedRegions) > 0)
                                    <span class="px-1.5 py-0.5 rounded-md bg-teal-50 text-[#44A08D] text-[10px] font-bold">{{ count($selectedLoc) + count($selectedRegions) }}</span>
                                @endif
                            </span>
                            <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-collapse class="px-5 pb-4 space-y-1.5">
                            @foreach([
                                ['loc',    'FR',     '🇫🇷', 'France',          'fr'],
                                ['region', 'europe', '🇪🇺', 'Europe',          'europe'],
                                ['region', 'usa',    '🇺🇸', 'États-Unis',      'usa'],
                                ['region', 'africa', '🌍', 'Gabon & Afrique', 'africa'],
                                ['region', 'global', '🌐', 'International',   'global'],
                            ] as [$param, $value, $flag, $label, $facetKey])
                                @php
                                    $checked = $param === 'loc' ? in_array($value, $selectedLoc, true) : in_array($value, $selectedRegions, true);
                                    $rCount  = (int) ($facets['regions'][$facetKey] ?? 0);
                                    $rDead   = $rCount === 0 && !$checked;
                                @endphp
                                <label class="flex items-center gap-2.5 px-2 py-1.5 rounded-lg transition
                                              {{ $rDead ? 'opacity-40 cursor-not-allowed select-none' : 'cursor-pointer hover:bg-slate-50' }}">
                                    <input type="checkbox" name="{{ $param }}[]" value="{{ $value }}"
                                           onchange="this.form.submit()" {{ $checked ? 'checked' : '' }} {{ $rDead ? 'disabled' : '' }}
                                           class="w-4 h-4 rounded border-slate-300 text-[#44A08D] focus:ring-[#44A08D]">
                                    <span class="text-sm">{{ $flag }}</span>
                                    <span class="flex-1 text-sm {{ $checked ? 'font-bold text-slate-900' : 'text-slate-700' }}">{{ $label }}</span>
                                    <span class="text-[11px] tabular-nums text-slate-400">{{ number_format($rCount, 0, ',', ' ') }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- ===== Section : Marques (P1 §6 — recherche + liste à cocher,
                         param marque[] partagé avec les pills du haut) ===== --}}
                    @php
                        $brandChecklist = [
                            ['Steam', '#171A21'], ['Playstation', '#003791'], ['Xbox', '#107C10'],
                            ['Netflix', '#E50914'], ['Deezer', '#A238FF'], ['Apple', '#000000'],
                            ['Roblox', '#00A2FF'], ['Riot', '#D13639'], ['Epic', '#2A2A2A'],
                            ['Google Play', '#01875F'], ['Nintendo', '#E60012'], ['Amazon', '#FF9900'],
                            ['Disney', '#0E47A1'], ['Deezer', '#A238FF'],
                        ];
                    @endphp
                    <div x-data="{ open: true, brandQuery: '' }" class="border-b border-slate-100">
                        <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50/50 transition">
                            <span class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                                Marques
                                @if(count($selectedBrands))
                                    <span class="px-1.5 py-0.5 rounded-md bg-teal-50 text-[#44A08D] text-[10px] font-bold">{{ count($selectedBrands) }}</span>
                                @endif
                            </span>
                            <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-collapse class="px-5 pb-4">
                            <input type="text" x-model="brandQuery" placeholder=" Rechercher une marque…"
                                   class="w-full mb-2 rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-[#44A08D] focus:ring-[#44A08D]">
                            <div class="space-y-1 max-h-56 overflow-y-auto">
                                @foreach($brandChecklist as [$brand, $color])
                                    @php
                                        $checked = $brandIsActive($brand);
                                        $bCount  = (int) ($facets['brands'][mb_strtolower($brand)] ?? 0);
                                        $bDead   = $bCount === 0 && !$checked;
                                    @endphp
                                    <label x-show="'{{ mb_strtolower($brand) }}'.includes(brandQuery.toLowerCase())"
                                           class="flex items-center gap-2.5 px-2 py-1.5 rounded-lg transition
                                                  {{ $bDead ? 'opacity-40 cursor-not-allowed select-none' : 'cursor-pointer hover:bg-slate-50' }}">
                                        <input type="checkbox" name="marque[]" value="{{ $brand }}"
                                               onchange="this.form.submit()" {{ $checked ? 'checked' : '' }} {{ $bDead ? 'disabled' : '' }}
                                               class="w-4 h-4 rounded border-slate-300 text-[#44A08D] focus:ring-[#44A08D]">
                                        {{-- Vrai logo, couleur d'origine --}}
                                        {!! $brandMark($brand, 'w-5 h-5') !!}
                                        <span class="flex-1 text-sm {{ $checked ? 'font-bold text-slate-900' : 'text-slate-700' }}">{{ $brand }}</span>
                                        <span class="text-[11px] tabular-nums text-slate-400">{{ number_format($bCount, 0, ',', ' ') }}</span>
                                    </label>
                                @endforeach
                            </div>
                            {{-- Marque hors liste → bascule sur la recherche libre --}}
                            <template x-if="brandQuery.length > 1 && ![@foreach($brandChecklist as [$brand, $c])'{{ mb_strtolower($brand) }}',@endforeach].some(b => b.includes(brandQuery.toLowerCase()))">
                                <a :href="'{{ route('boutique') }}?search=' + encodeURIComponent(brandQuery)"
                                   class="block mt-2 px-3 py-2 rounded-lg bg-slate-50 text-xs font-semibold text-[#44A08D] hover:bg-teal-50 transition">
                                    Rechercher « <span x-text="brandQuery"></span> » dans le catalogue →
                                </a>
                            </template>
                        </div>
                    </div>

                    {{-- Footer mobile : sticky "Voir les résultats" --}}
                    <div class="lg:hidden sticky bottom-0 left-0 right-0 z-10 px-4 py-3 bg-white border-t border-slate-100 flex gap-2">
                        @if($activeFiltersCount > 0)
                            <a href="{{ route('boutique') }}"
                               class="flex-1 px-3 py-3 rounded-xl bg-slate-100 text-slate-700 text-sm font-bold text-center active:scale-95 transition">
                                Effacer
                            </a>
                        @endif
                        <button type="button" @click="mobileOpen = false"
                                class="flex-1 px-3 py-3 rounded-xl bg-gradient-to-br from-[#44A08D] to-[#4ECDC4] text-white text-sm font-bold shadow-md shadow-[#44A08D]/30 active:scale-95 transition">
                            Voir {{ $pagination['total'] ?? 0 }} carte{{ ($pagination['total'] ?? 0) > 1 ? 's' : '' }}
                        </button>
                    </div>
                </form>
            </aside>

            {{-- ============================
                 PRODUCTS GRID
                 ============================ --}}
            <div>
                {{-- Mise en avant d'une carte populaire (tirage à chaque visite)
                     — accueil boutique UNIQUEMENT (mode lignes sans filtre),
                     jamais dans un listing filtré. --}}
                @if($activeFiltersCount === 0 && !empty($rows))
                    <div class="mb-4">
                        @include('partials._popular-highlight')
                    </div>
                @endif

                {{-- P1 §2 — MODE LIGNES THÉMATIQUES (défaut). La pagination
                     classique vit désormais dans les pages « Voir tout ». --}}
                @if(!empty($rows))
                    @include('partials.boutique._rows')

                    @if(!empty($rowsTruncated))
                        <div class="text-center mt-2 mb-8">
                            <a href="{{ $urlWith(['view' => 'all', 'page' => null]) }}"
                               class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-slate-900 text-white text-sm font-bold hover:bg-slate-800 transition">
                                Voir toutes les {{ $pagination['total'] }} cartes
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        </div>
                    @endif
                @elseif(($rows !== null) && empty($rows))
                    {{-- Mode lignes mais 0 résultat après filtres --}}
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-card p-10 text-center">
                        <p class="text-sm font-semibold text-slate-900 mb-1">Aucune carte ne correspond à ces filtres.</p>
                        <p class="text-xs text-slate-500 mb-4">Essayez d'élargir la gamme de prix ou de retirer un filtre.</p>
                        <a href="{{ route('boutique') }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold">Tout effacer</a>
                    </div>
                @elseif(isset($products) && count($products) > 0)
                    <div id="productsGrid"
                         :class="view === 'grid'
                            ? 'grid grid-cols-2 lg:grid-cols-3 gap-4'
                            : 'grid grid-cols-1 gap-3'">
                        @foreach($products as $product)
                            @php
                                $brandName = $product['cardType']['name'] ?? 'Brand';
                                $bgColor   = $brandColorFor($brandName);
                                $logoUrl   = $sanitizeLogo($product['cardType']['logoUrl'] ?? null);
                                $minPrice  = $product['price']['min'] ?? 0;
                                $currency  = $product['price']['currencyCode'] ?? 'XAF';
                                $faceValue = $product['minFaceValue'] ?? $minPrice;

                                // P1 §1 — mini-montants cliquables vers l'URL de chaque variante
                                $variantCtId  = $product['cardType']['internalId'] ?? $product['cardType']['id'] ?? null;
                                $variantPills = collect($product['variants'] ?? [])
                                    ->filter(fn ($v) => ($v['face'] ?? 0) > 0 && $variantCtId)
                                    ->map(fn ($v) => [
                                        'label' => \App\Support\Money::formatOriginal($v['face'], $v['currency'])
                                            ?? number_format($v['face'], 0, ',', ' '),
                                        'url'   => route('card-type.variant', [
                                            $v['card_type_id'] ?? $variantCtId,
                                            fmod((float) $v['face'], 1.0) === 0.0
                                                ? (string) (int) $v['face']
                                                : rtrim(rtrim(number_format((float) $v['face'], 2, '.', ''), '0'), '.'),
                                        ]),
                                    ])->values()->all();
                                $cardHref  = route('card-type.show', $product['cardType']['internalId'] ?? $product['cardType']['id'] ?? '');
                                $ctId      = $product['cardType']['id'] ?? null;
                                $ctCount   = $ctId !== null ? ($cardTypeCounts[$ctId] ?? 1) : 1;
                            @endphp

                            {{-- Vue grille : carte verticale (composant existant) --}}
                            <div x-show="view === 'grid'">
                                <x-product-card
                                    :name="$product['cardType']['name'] ?? $product['name']"
                                    :brand-label="$brandName"
                                    :brand-color="$bgColor"
                                    :logo-url="$logoUrl"
                                    :price="$minPrice"
                                    :face-value="$faceValue"
                                    :currency="$currency"
                                    :href="$cardHref"
                                    :products-count="$product['variants_count'] ?? $ctCount"
                                    :country-code="$product['cardType']['countryCode'] ?? null"
                                    :variants="$variantPills"
                                />
                            </div>

                            {{-- Vue liste : carte horizontale --}}
                            <a x-show="view === 'list'" href="{{ $cardHref }}"
                               class="group flex items-stretch overflow-hidden rounded-xl bg-white border border-slate-100 shadow-card hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-300 active:scale-[0.99]">
                                {{-- Brand color bloc gauche --}}
                                <div style="background-color: {{ $bgColor }}" class="relative w-32 sm:w-40 shrink-0 overflow-hidden flex flex-col justify-between p-3">
                                    <svg class="absolute inset-0 w-full h-full opacity-[0.08]" aria-hidden="true">
                                        <defs>
                                            <pattern id="ll-{{ md5($brandName . $product['id'] ?? '') }}" width="32" height="32" patternUnits="userSpaceOnUse">
                                                <circle cx="16" cy="16" r="13" fill="none" stroke="white" stroke-width="1"/>
                                            </pattern>
                                        </defs>
                                        <rect width="100%" height="100%" fill="url(#ll-{{ md5($brandName . $product['id'] ?? '') }})"/>
                                    </svg>
                                    <span class="relative text-white/80 font-bold text-[9px] tracking-[0.2em] uppercase">Gift Card</span>
                                    <h3 class="relative font-display text-white font-bold text-base leading-tight truncate">
                                        {{ $brandName }}
                                    </h3>
                                </div>

                                {{-- Contenu droite --}}
                                <div class="flex-1 min-w-0 px-4 py-3 flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <div class="w-10 h-10 shrink-0 rounded-full bg-white border border-slate-100 ring-2 ring-white shadow-sm flex items-center justify-center overflow-hidden">
                                            @if($logoUrl)
                                                <img src="{{ $logoUrl }}" alt="{{ $brandName }}" class="w-full h-full object-cover" loading="lazy"/>
                                            @else
                                                <span class="text-sm font-bold text-slate-700">{{ strtoupper(substr($brandName, 0, 1)) }}</span>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="text-sm font-semibold text-slate-900 line-clamp-1 group-hover:text-[#44A08D] transition">{{ $product['cardType']['name'] ?? $product['name'] }}</h4>
                                            <p class="text-xs text-slate-500 mt-0.5">
                                                @php $vCount = $product['variants_count'] ?? $ctCount; @endphp
                                                {{ $brandName }}@if($vCount > 1) <span class="text-slate-400">· {{ $vCount }} montants disponibles</span>@endif
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between sm:justify-end gap-3 sm:shrink-0">
                                        @php $originalList = \App\Support\Money::formatOriginal($faceValue, $currency); @endphp
                                        <div class="text-right">
                                            <p class="text-[10px] text-slate-400 leading-none">À partir de</p>
                                            @if($originalList)
                                                <span class="inline-block mt-0.5 rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-bold text-slate-600 tabular-nums">{{ $originalList }}</span>
                                            @endif
                                            <p class="text-base font-black tabular-nums text-slate-900 mt-0.5 price-display"
                                               data-price="{{ $minPrice }}"
                                               data-currency="{{ $currency }}"
                                               data-processed="true">
                                                {{ \App\Support\Money::formatFcfa($minPrice, $currency) }}
                                            </p>
                                        </div>
                                        <span class="hidden sm:inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-50 group-hover:bg-[#44A08D] group-hover:text-white text-xs font-semibold text-slate-700 transition">
                                            Voir
                                            <svg class="w-3 h-3 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    {{-- ============================================================
                         CHARGEMENT PROGRESSIF (P3)
                         Remplace la pagination classique. On récupère la MÊME page
                         HTML (?page=N) et on en extrait les cards : le composant
                         Blade reste la source unique du rendu, aucun duplicata de
                         la logique de prix/variantes en JavaScript.
                         ============================================================ --}}
                    @if(isset($pagination) && $pagination['last_page'] > 1)
                        <div id="loadMoreZone"
                             data-current-page="{{ $pagination['current_page'] }}"
                             data-last-page="{{ $pagination['last_page'] }}"
                             data-total="{{ $pagination['total'] }}"
                             class="mt-8">

                            {{-- Squelettes affichés pendant le chargement --}}
                            <div id="loadMoreSkeletons" hidden aria-hidden="true"
                                 :class="view === 'grid'
                                    ? 'grid grid-cols-2 lg:grid-cols-3 gap-4'
                                    : 'grid grid-cols-1 gap-3'">
                                @for($i = 0; $i < 3; $i++)
                                    <div class="ka-skel rounded-2xl border border-slate-100 bg-white overflow-hidden">
                                        <div class="ka-skel-block" style="aspect-ratio: 16 / 10;"></div>
                                        <div class="p-3 sm:p-4 space-y-2">
                                            <div class="ka-skel-block h-3 w-3/4 rounded"></div>
                                            <div class="ka-skel-block h-3 w-1/2 rounded"></div>
                                            <div class="ka-skel-block h-4 w-2/5 rounded ml-auto"></div>
                                        </div>
                                    </div>
                                @endfor
                            </div>

                            {{-- Annonce des ajouts aux lecteurs d'écran --}}
                            <p id="loadMoreStatus" class="sr-only" role="status" aria-live="polite"></p>

                            <div class="mt-6 flex flex-col items-center gap-3">
                                <button type="button" id="loadMoreBtn"
                                        class="inline-flex items-center gap-2 min-h-[48px] px-6 rounded-xl bg-slate-900 text-white text-sm font-bold
                                               hover:bg-slate-800 active:scale-[0.98] transition focus:outline-none
                                               focus-visible:ring-2 focus-visible:ring-[#44A08D] focus-visible:ring-offset-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                    Charger plus de cartes
                                </button>

                                {{-- Erreur localisée : ne remplace que cette zone. --}}
                                <div id="loadMoreError" hidden
                                     class="w-full max-w-md rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-center">
                                    <p class="text-xs font-semibold text-rose-700">Connexion interrompue.</p>
                                    <button type="button" id="loadMoreRetry"
                                            class="mt-2 inline-flex items-center min-h-[44px] px-4 rounded-lg bg-rose-600 text-white text-xs font-bold
                                                   hover:bg-rose-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400 focus-visible:ring-offset-2">
                                        Réessayer
                                    </button>
                                </div>

                                {{-- Fin de liste --}}
                                <div id="loadMoreDone" hidden class="text-center">
                                    <p class="text-xs text-slate-500">
                                        Vous avez vu les {{ number_format($pagination['total'], 0, ',', ' ') }} cartes de cette sélection.
                                    </p>
                                    <a href="{{ route('boutique') }}" class="mt-1 inline-block text-xs font-bold text-[#0F766E] hover:underline">
                                        Revenir aux catégories
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif

                @else
                    {{-- Empty state --}}
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-card text-center py-16 px-6">
                        <div class="w-20 h-20 mx-auto mb-5 rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center">
                            <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <h3 class="font-display text-xl font-bold text-slate-900 mb-2">Aucun produit trouvé</h3>
                        <p class="text-sm text-slate-500 max-w-md mx-auto mb-6">
                            @if($search)
                                Aucun produit ne correspond à <strong>"{{ $search }}"</strong>. Essayez avec d'autres mots-clés.
                            @else
                                Aucun produit ne correspond aux filtres sélectionnés.
                            @endif
                        </p>
                        <a href="{{ route('boutique') }}"
                           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#44A08D] hover:bg-[#3d9180] text-white text-sm font-semibold shadow-lg shadow-[#44A08D]/25 active:scale-95 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Réinitialiser les filtres
                        </a>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>

{{-- Alpine collapse plugin (pour x-collapse sur les accordeons) --}}
@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>

<script>
/* ================================================================
   Chargement progressif des listings « Voir tout ».
   Aucun endpoint dédié : on demande la MÊME page (?page=N) et on en
   extrait les cards. Le composant Blade reste la seule source du
   rendu — dupliquer la logique de prix/variantes en JS serait une
   dette immédiate.
   ================================================================ */
(function () {
    var zone = document.getElementById('loadMoreZone');
    var grid = document.getElementById('productsGrid');
    if (!zone || !grid) return;

    var btn       = document.getElementById('loadMoreBtn');
    var retry     = document.getElementById('loadMoreRetry');
    var skeletons = document.getElementById('loadMoreSkeletons');
    var errorBox  = document.getElementById('loadMoreError');
    var doneBox   = document.getElementById('loadMoreDone');
    var status    = document.getElementById('loadMoreStatus');

    var page      = parseInt(zone.dataset.currentPage, 10) || 1;
    var lastPage  = parseInt(zone.dataset.lastPage, 10) || 1;
    var loading   = false;
    var autoLoads = 0;                 // auto-chargements consécutifs
    var AUTO_MAX  = 2;                 // au-delà, le bouton reprend la main
    var TIMEOUT   = 10000;             // jamais de squelette qui tourne sans fin

    var calme = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* Mémorise l'état pour le retour arrière (contenu + position). */
    var CACHE_KEY = 'ka.listing.' + location.pathname + location.search;

    function restore() {
        try {
            var raw = sessionStorage.getItem(CACHE_KEY);
            if (!raw) return false;
            var snap = JSON.parse(raw);
            if (!snap || !snap.html || snap.page <= 1) return false;
            grid.innerHTML = snap.html;
            page = snap.page;
            if (page >= lastPage) finish();
            // Restaure la position APRÈS le rendu, sans animation.
            requestAnimationFrame(function () { window.scrollTo(0, snap.scroll || 0); });
            return true;
        } catch (e) { return false; }
    }

    function remember() {
        try {
            sessionStorage.setItem(CACHE_KEY, JSON.stringify({
                html: grid.innerHTML, page: page, scroll: window.scrollY,
            }));
        } catch (e) { /* quota plein : le cache est un confort, pas une exigence */ }
    }

    function setBusy(on) {
        loading = on;
        skeletons.hidden = !on;
        if (btn) btn.disabled = on;
        if (on) errorBox.hidden = true;
    }

    function finish() {
        if (btn) btn.hidden = true;
        doneBox.hidden = false;
        if (observer) observer.disconnect();
    }

    function fail() {
        setBusy(false);
        errorBox.hidden = false;
        if (btn) btn.hidden = true;      // le bouton Réessayer prend le relais
    }

    function load(auto) {
        if (loading || page >= lastPage) return;
        setBusy(true);

        var next = page + 1;
        var url  = new URL(window.location.href);
        url.searchParams.set('page', next);

        var ctrl  = new AbortController();
        var timer = setTimeout(function () { ctrl.abort(); }, TIMEOUT);

        fetch(url.toString(), {
            signal: ctrl.signal,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(function (r) {
            if (!r.ok) throw new Error('http ' + r.status);
            return r.text();
        })
        .then(function (html) {
            clearTimeout(timer);
            var doc      = new DOMParser().parseFromString(html, 'text/html');
            var incoming = doc.getElementById('productsGrid');
            if (!incoming) throw new Error('grille absente');

            // Anti-doublon par PRODUIT déjà affiché. Attention : chaque produit
            // rend DEUX enfants partageant le même lien (la card grille et la
            // card liste). L'ensemble de référence est donc figé AVANT la
            // boucle — l'alimenter au fil de l'eau supprimait la card liste des
            // nouveaux produits.
            var vus = {};
            grid.querySelectorAll('a[href]').forEach(function (a) { vus[a.getAttribute('href')] = 1; });

            var ajoutes = 0;
            Array.prototype.forEach.call(incoming.children, function (el) {
                var lien = el.matches('a[href]') ? el : el.querySelector('a[href]');
                var href = lien && lien.getAttribute('href');
                if (href && vus[href]) return;
                // Pas d'animation en cascade sur les ajouts : elle est réservée
                // au premier affichage (et désactivée en mode calme).
                grid.appendChild(el.cloneNode(true));
                ajoutes++;
            });

            page = next;
            setBusy(false);
            status.textContent = ajoutes + ' cartes supplémentaires chargées.';
            remember();

            if (page >= lastPage) finish();
            if (auto) autoLoads++; else autoLoads = 0;
        })
        .catch(function () { clearTimeout(timer); fail(); });
    }

    if (btn)   btn.addEventListener('click', function () { autoLoads = 0; load(false); });
    if (retry) retry.addEventListener('click', function () {
        errorBox.hidden = true;
        if (btn) btn.hidden = false;
        load(false);
    });

    /* Déclenchement automatique — plafonné pour ne pas pomper la data mobile. */
    var observer = null;
    if ('IntersectionObserver' in window) {
        observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (!e.isIntersecting || loading) return;
                if (autoLoads >= AUTO_MAX) return;   // le bouton reprend la main
                load(true);
            });
        }, { rootMargin: '600px' });
        observer.observe(zone);
    }

    /* Mémorise avant de quitter la page (clic sur une card, notamment). */
    window.addEventListener('pagehide', remember);
    restore();
})();

/* ================================================================
   Hors-ligne : bandeau discret, retiré au retour du réseau.
   ================================================================ */
(function () {
    var bandeau = null;

    function afficher() {
        if (bandeau) return;
        bandeau = document.createElement('div');
        bandeau.setAttribute('role', 'status');
        bandeau.className = 'ka-offline';
        bandeau.textContent = 'Vous êtes hors ligne — les prix affichés peuvent dater.';
        document.body.appendChild(bandeau);
    }

    function masquer() {
        if (!bandeau) return;
        bandeau.remove();
        bandeau = null;
    }

    window.addEventListener('offline', afficher);
    window.addEventListener('online', masquer);
    if (navigator.onLine === false) afficher();
})();
</script>
@endpush

{{-- P1-bis — styles de l'en-tête (rangées scrollables + fondu de bord) --}}
<style>
    /* Rangées de pills : scroll horizontal mobile sans scrollbar visible */
    .ka-hide-scroll { scrollbar-width: none; -ms-overflow-style: none; scroll-padding-left: 1rem; }
    .ka-hide-scroll::-webkit-scrollbar { display: none; }
    /* Fondu de bord droit : signale qu'il reste des pills à droite (mobile) */
    /* ===== P3 : squelettes de chargement + hors-ligne ===== */
    .ka-skel-block {
        background: linear-gradient(90deg, #F1F5F9 25%, #E2E8F0 37%, #F1F5F9 63%);
        background-size: 400% 100%;
        animation: ka-skel 1.4s ease infinite;
    }
    @keyframes ka-skel { 0% { background-position: 100% 50%; } 100% { background-position: 0 50%; } }

    .ka-offline {
        position: fixed; top: 0; left: 0; right: 0; z-index: 80;
        background: #0F172A; color: #fff;
        padding: 10px 16px;
        padding-top: max(10px, env(safe-area-inset-top));
        font-size: 12.5px; font-weight: 600; text-align: center;
    }

    /* Le mode calme coupe TOUTES les animations ajoutées ici. */
    @media (prefers-reduced-motion: reduce) {
        .ka-skel-block { animation: none; background: #F1F5F9; }
        .ka-cart-pop { animation: none !important; }
    }

    .ka-fade {
        position: absolute; top: 0; right: 0; bottom: 4px; width: 40px;
        pointer-events: none;
        background: linear-gradient(to left, #FFFFFF 15%, rgba(255,255,255,0));
    }
    /* Barre compacte sticky : hors flux (fixed) → aucun saut de layout (CLS 0).
       L'ÉTAT (transform/opacity) est piloté en style inline par le JS : la
       cascade ne peut pas l'écraser, et la transition reste ici. */
    #ka-sticky-bar {
        position: fixed; left: 0; right: 0; z-index: 40; /* sous le header du site (z-50) */
        transition: transform 180ms ease, opacity 180ms ease;
        will-change: transform, opacity;
    }
</style>

{{-- P1 §5/§7 — composants Alpine (définis AVANT le démarrage d'Alpine, defer) --}}
<script>
    // P1-bis §4 — bouton « Filtres » de la barre sticky.
    // Desktop : la sidebar est déjà visible → on y remonte. Mobile : drawer P1.
    function kaOpenFilters(el) {
        if (window.innerWidth < 1024) {
            el.dispatchEvent(new CustomEvent('open-filters', { bubbles: true }));
            return;
        }
        const form = document.getElementById('filterForm');
        if (form) {
            window.scrollTo({ top: Math.max(0, form.getBoundingClientRect().top + window.scrollY - 90), behavior: 'smooth' });
        }
    }

    // P1-bis §4 — bascule en-tête complet ⇄ barre compacte au scroll.
    document.addEventListener('DOMContentLoaded', function () {
        // Placeholder long réservé au desktop (tronqué sur mobile sinon)
        const applyPlaceholder = () => {
            document.querySelectorAll('[data-placeholder-lg]').forEach(el => {
                el.placeholder = window.innerWidth >= 768
                    ? el.dataset.placeholderLg
                    : 'Rechercher une marque…';
            });
        };
        applyPlaceholder();
        window.addEventListener('resize', applyPlaceholder, { passive: true });

        const header = document.getElementById('ka-header');
        const bar    = document.getElementById('ka-sticky-bar');
        if (!header || !bar) return;

        // Le header du site est `fixed` en mobile (z-50) : la barre compacte
        // doit se poser DESSOUS, sinon elle est invisible. Hauteur mesurée
        // (et re-mesurée au resize) plutôt que codée en dur.
        const siteHeader = document.querySelector('.md\\:hidden.fixed.top-0');
        const placeBar = () => {
            const h = (siteHeader && getComputedStyle(siteHeader).display !== 'none')
                ? Math.round(siteHeader.getBoundingClientRect().height)
                : 0;
            bar.style.top = h + 'px';
        };
        placeBar();
        window.addEventListener('resize', placeBar, { passive: true });

        let shown = null;
        const paint = (visible) => {
            bar.style.transform     = visible ? 'translateY(0)' : 'translateY(-110%)';
            bar.style.opacity       = visible ? '1' : '0';
            bar.style.pointerEvents = visible ? 'auto' : 'none';
            bar.setAttribute('aria-hidden', visible ? 'false' : 'true');
        };
        paint(false);

        const onScroll = () => {
            // Seuil : bas de l'en-tête (hystérésis pour éviter le clignotement)
            const bottom = header.getBoundingClientRect().bottom;
            const next = shown ? bottom < 40 : bottom < 0;
            if (next !== shown) {
                shown = next;
                paint(shown);
            }
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    });

    // Badge compteur : petit comptage animé quand la valeur change (P1-bis §5)
    function countUp(total) {
        return {
            display: new Intl.NumberFormat('fr-FR').format(total), // valeur finale par défaut
            run() {
                const fmt = (n) => new Intl.NumberFormat('fr-FR').format(n);
                const settle = () => { this.display = fmt(total); };
                // rAF est suspendu dans un onglet caché : ne jamais animer alors
                // (le badge resterait figé à 0), ni si l'utilisateur réduit les animations.
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches
                    || document.hidden || total < 1) {
                    settle(); return;
                }
                const start = performance.now(), dur = 550;
                this.display = '0';
                const step = (now) => {
                    if (document.hidden) { settle(); return; }   // filet de sécurité
                    const p = Math.min(1, (now - start) / dur);
                    // easeOutCubic
                    this.display = fmt(Math.round(total * (1 - Math.pow(1 - p, 3))));
                    if (p < 1) requestAnimationFrame(step);
                };
                requestAnimationFrame(step);
            },
        };
    }
</script>
<script>
    // Autocomplétion recherche (debounce 250 ms, endpoint JSON /boutique/suggest)
    function searchSuggest() {
        return {
            open: false, items: [], empty: false, lastQuery: '', timer: null,
            onInput(value) {
                clearTimeout(this.timer);
                const q = value.trim();
                if (q.length < 2) { this.open = false; this.items = []; this.empty = false; return; }
                this.timer = setTimeout(async () => {
                    try {
                        const res = await fetch('{{ route('boutique.suggest') }}?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } });
                        const data = await res.json();
                        this.items = data.items || [];
                        this.lastQuery = q;
                        this.empty = this.items.length === 0;
                        this.open = true;
                    } catch (e) { /* réseau : on laisse la recherche classique */ }
                }, 250);
            },
        };
    }
</script>
<script>
    function priceSlider({ boundMin, boundMax, curMin, curMax }) {
        return {
            open: true,
            boundMin, boundMax,
            vMin: curMin ?? boundMin,
            vMax: curMax ?? boundMax,
            timer: null,
            pct(v) { return this.boundMax > this.boundMin ? (v - this.boundMin) / (this.boundMax - this.boundMin) * 100 : 0; },
            fmt(v) { return new Intl.NumberFormat('fr-FR').format(v ?? 0); },
            clampMin() {
                if (this.vMin === null || isNaN(this.vMin)) this.vMin = this.boundMin;
                if (this.vMin > this.vMax) this.vMin = this.vMax;
                if (this.vMin < this.boundMin) this.vMin = this.boundMin;
            },
            clampMax() {
                if (this.vMax === null || isNaN(this.vMax)) this.vMax = this.boundMax;
                if (this.vMax < this.vMin) this.vMax = this.vMin;
                if (this.vMax > this.boundMax) this.vMax = this.boundMax;
            },
            // Debounce 400 ms puis soumission du form (SSR : recharge = état à jour)
            schedule() {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => {
                    const form = this.$root.closest('form');
                    if (form) form.submit();
                }, 400);
            },
        };
    }
</script>
<style>
    /* Double range : seuls les curseurs sont cliquables (pistes superposées) */
    .price-thumb { height: 24px; }
    .price-thumb::-webkit-slider-thumb {
        pointer-events: auto; -webkit-appearance: none; appearance: none;
        width: 18px; height: 18px; border-radius: 50%;
        background: #fff; border: 2.5px solid #44A08D;
        box-shadow: 0 1px 4px rgba(15,23,42,0.25); cursor: pointer;
    }
    .price-thumb::-moz-range-thumb {
        pointer-events: auto; width: 18px; height: 18px; border-radius: 50%;
        background: #fff; border: 2.5px solid #44A08D;
        box-shadow: 0 1px 4px rgba(15,23,42,0.25); cursor: pointer;
    }
    .price-thumb::-moz-range-track { background: transparent; }
</style>

@endsection
