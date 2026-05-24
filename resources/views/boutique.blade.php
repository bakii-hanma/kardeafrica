@extends('layouts.app')

@section('title', 'Boutique - KardAfrica')

@php
    $brandPalette = [
        'Netflix' => '#E50914', 'Spotify' => '#1DB954', 'Apple' => '#000000',
        'iTunes' => '#D60017', 'PlayStation' => '#003791', 'Xbox' => '#107C10',
        'Amazon' => '#FF9900', 'Google' => '#01875F', 'Steam' => '#171A21',
        'Uber' => '#000000', 'Roblox' => '#00A2FF', 'Nintendo' => '#E60012',
        'Disney' => '#0E47A1', 'StarzPlay' => '#7C3AED', 'Talabat' => '#FF5A00',
        'HUAWEI' => '#C7000B', 'IKEA' => '#0058A3',
    ];

    $brandColorFor = function ($brandName) use ($brandPalette) {
        foreach ($brandPalette as $key => $color) {
            if (stripos($brandName, $key) !== false) return $color;
        }
        $palette = ['#0F172A', '#44A08D', '#0EA5E9', '#7C3AED', '#DC2626', '#EA580C', '#059669'];
        $hash = 0;
        for ($i = 0; $i < strlen($brandName); $i++) $hash = ord($brandName[$i]) + (($hash << 5) - $hash);
        // Garantit un index positif meme en cas d'overflow d'entier
        $idx = (($hash % count($palette)) + count($palette)) % count($palette);
        return $palette[$idx];
    };

    $sanitizeLogo = function ($url) {
        if ($url && (str_contains($url, 'localhost') || str_contains($url, '127.0.0.1'))) {
            return parse_url($url, PHP_URL_PATH);
        }
        return $url;
    };

    $selectedRegions = $selectedRegions ?? [];
    $popularOnly     = $popularOnly ?? false;

    $activeFiltersCount = (int)!empty($search)
        + (int)!empty($categoryId)
        + count($priceRange ?? [])
        + count($selectedCountries ?? [])
        + count($selectedRegions)
        + (int) $popularOnly;

    $regionList = [
        ['europe', 'Europe',     '🇪🇺'],
        ['usa',    'États-Unis', '🇺🇸'],
        ['global', 'Mondial',    '🌍'],
        ['africa', 'Afrique',    '🌍'],
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
    ];
    $sort = $sort ?? 'popular';
    $sortLabel = $sortOptions[$sort] ?? 'Populaire';

    // Helper pour conserver les filtres actuels en changeant un seul param
    $urlWith = function (array $overrides) use ($search, $categoryId, $priceRange, $selectedCountries, $selectedRegions, $popularOnly, $sort) {
        $base = [
            'search'      => $search,
            'category'    => $categoryId,
            'price_range' => $priceRange,
            'country'     => $selectedCountries,
            'region'      => $selectedRegions,
            'popular'     => $popularOnly ? 1 : null,
            'sort'        => $sort,
        ];
        return route('boutique', array_merge($base, $overrides));
    };
@endphp

@section('content')
<div class="bg-[#FAFAF7] min-h-screen pb-20"
     x-data="{ view: localStorage.getItem('boutique-view') || 'grid' }"
     x-init="$watch('view', v => localStorage.setItem('boutique-view', v))">

    {{-- ================================================================
         TOP STRIP — breadcrumb + title + toolbar (sort/view)
         ================================================================ --}}
    <section class="bg-white border-b border-slate-200">
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

            {{-- Title + résumé --}}
            <div class="pb-3 md:pb-4">
                <h1 class="font-display text-xl md:text-3xl font-bold text-slate-900 tracking-tight leading-tight">
                    @if($search)
                        Résultats pour <span class="text-[#44A08D]">"{{ $search }}"</span>
                    @elseif($currentCategory)
                        {{ $currentCategory['name'] }}
                    @else
                        Toutes les cartes cadeaux
                    @endif
                </h1>
                <p class="text-xs md:text-sm text-slate-500 mt-1">
                    @if(isset($pagination))
                        <span class="font-semibold text-slate-700">{{ $pagination['total'] }}</span>
                        produit{{ $pagination['total'] > 1 ? 's' : '' }}
                        @if($activeFiltersCount > 0)
                            · {{ $activeFiltersCount }} filtre{{ $activeFiltersCount > 1 ? 's' : '' }} actif{{ $activeFiltersCount > 1 ? 's' : '' }}
                        @endif
                    @else
                        Plus de 300 marques disponibles
                    @endif
                </p>
            </div>

            {{-- Quick filter pills : Top Afrique / Régions --}}
            <div class="pb-4 -mx-4 sm:mx-0 px-4 sm:px-0 overflow-x-auto">
                <div class="flex items-center gap-2 min-w-max sm:min-w-0 sm:flex-wrap">
                    {{-- Top Afrique : Steam, PSN, Xbox, Netflix, Spotify, Apple, Roblox, Riot, Epic --}}
                    <a href="{{ $urlWith(['popular' => $popularOnly ? null : 1, 'page' => null]) }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full text-xs font-bold whitespace-nowrap transition active:scale-95
                              {{ $popularOnly
                                 ? 'bg-gradient-to-br from-orange-500 to-rose-500 text-white shadow-md shadow-rose-500/30'
                                 : 'bg-white border border-slate-200 text-slate-700 hover:border-orange-300 hover:bg-orange-50' }}">
                        <span class="text-sm leading-none">🔥</span>
                        Top Afrique
                        @if($popularOnly)
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        @endif
                    </a>

                    <span class="w-px h-5 bg-slate-200 mx-1"></span>

                    {{-- Régions : pills cliquables (toggle) --}}
                    @foreach($regionList as [$rcode, $rlabel, $remoji])
                        @php
                            $rIsActive = in_array($rcode, $selectedRegions, true);
                            $newRegions = $rIsActive
                                ? array_values(array_diff($selectedRegions, [$rcode]))
                                : array_merge($selectedRegions, [$rcode]);
                        @endphp
                        <a href="{{ $urlWith(['region' => $newRegions, 'page' => null]) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-full text-xs font-bold whitespace-nowrap transition active:scale-95
                                  {{ $rIsActive
                                     ? 'bg-slate-900 text-white shadow-md'
                                     : 'bg-white border border-slate-200 text-slate-700 hover:border-[#44A08D] hover:bg-teal-50' }}">
                            <span class="text-sm leading-none">{{ $remoji }}</span>
                            {{ $rlabel }}
                            @if($rIsActive)
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Marques populaires (raccourcis recherche directe) --}}
            @php
                $brandQuickPills = [
                    ['Steam',      'Steam',      '#171A21'],
                    ['PSN',        'Playstation','#003791'],
                    ['Xbox',       'Xbox',       '#107C10'],
                    ['Netflix',    'Netflix',    '#E50914'],
                    ['Spotify',    'Spotify',    '#1DB954'],
                    ['Apple',      'Apple',      '#000000'],
                    ['Roblox',     'Roblox',     '#00A2FF'],
                    ['Riot',       'Riot',       '#D13639'],
                    ['Epic',       'Epic',       '#2A2A2A'],
                    ['Google Play','Google Play','#01875F'],
                ];
            @endphp
            <div class="pb-4 -mx-4 sm:mx-0 px-4 sm:px-0 overflow-x-auto">
                <div class="flex items-center gap-2 min-w-max sm:min-w-0 sm:flex-wrap">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mr-1 whitespace-nowrap">Marques</span>
                    @foreach($brandQuickPills as [$label, $term, $color])
                        @php $bIsActive = strtolower($search ?? '') === strtolower($term); @endphp
                        <a href="{{ $urlWith(['search' => $bIsActive ? null : $term, 'page' => null]) }}"
                           class="inline-flex items-center gap-1.5 pl-1.5 pr-3 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition active:scale-95
                                  {{ $bIsActive
                                     ? 'bg-slate-900 text-white shadow-md ring-2 ring-slate-900/10'
                                     : 'bg-white border border-slate-200 text-slate-700 hover:border-slate-400' }}">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-white text-[10px] font-black"
                                  style="background:{{ $color }};">{{ strtoupper(substr($label, 0, 1)) }}</span>
                            {{ $label }}
                            @if($bIsActive)
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Toolbar : search full-width + actions row mobile-first --}}
            <div class="pb-4 space-y-2 md:space-y-0 md:flex md:items-center md:gap-3 md:justify-end">
                {{-- Search : full-width sur mobile --}}
                <form action="{{ route('boutique') }}" method="GET" class="flex items-center bg-slate-50 border border-slate-200 rounded-xl pl-3 pr-1 py-1 focus-within:bg-white focus-within:border-[#44A08D] focus-within:shadow-card transition w-full md:w-auto md:min-w-[320px]" data-no-loader>
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    <svg class="w-4 h-4 text-slate-400 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Rechercher une marque…"
                           class="flex-1 min-w-0 bg-transparent border-0 text-slate-900 placeholder-slate-400 focus:ring-0 text-sm focus:outline-none py-2">
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

                {{-- Actions row : Filtres mobile + Tri + View --}}
                <div class="flex items-center gap-2">
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

                    {{-- View toggle (caché < md) --}}
                    <div class="hidden md:flex items-center bg-slate-50 border border-slate-200 rounded-lg p-0.5 shrink-0">
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
                    </div>
                </div>
            </div>

            {{-- Active filter chips --}}
            @if($activeFiltersCount > 0)
                <div class="flex flex-wrap items-center gap-2 pb-4">
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
                    <input type="hidden" name="search" value="{{ $search }}">

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
                            @foreach($categories as $category)
                                <a href="{{ route('boutique', array_merge(request()->except(['category', 'page']), ['category' => $category['id'], 'page' => 1])) }}"
                                   class="flex items-center justify-between px-3 py-2 rounded-lg text-sm transition
                                          {{ $categoryId == $category['id'] ? 'bg-slate-900 text-white font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
                                    <span>{{ $category['name'] }}</span>
                                    @if($categoryId == $category['id'])
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- ===== Section : Prix (pills) ===== --}}
                    <div x-data="{ open: true }" class="border-b border-slate-100">
                        <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50/50 transition">
                            <span class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                                Gamme de prix
                                @if(count($priceRange ?? []))
                                    <span class="px-1.5 py-0.5 rounded-md bg-teal-50 text-[#44A08D] text-[10px] font-bold">{{ count($priceRange) }}</span>
                                @endif
                            </span>
                            <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-collapse class="px-5 pb-4">
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ($priceRangeLabels as $value => $label)
                                    @php $isActive = in_array($value, $priceRange ?? []); @endphp
                                    <label class="cursor-pointer">
                                        <input type="checkbox" name="price_range[]" value="{{ $value }}"
                                               onchange="this.form.submit()"
                                               {{ $isActive ? 'checked' : '' }}
                                               class="sr-only peer">
                                        <div class="px-3 py-2.5 rounded-lg text-center text-xs font-semibold border-2 transition
                                                    {{ $isActive ? 'bg-slate-900 border-slate-900 text-white' : 'bg-white border-slate-200 text-slate-700 hover:border-[#44A08D]/40' }}">
                                            {{ $label }}
                                            <span class="block text-[10px] font-normal opacity-75 mt-0.5">FCFA</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- ===== Section : Top Afrique (bouton link, plus fiable que le toggle peer) ===== --}}
                    <div class="px-5 py-4 border-b border-slate-100">
                        <a href="{{ $urlWith(['popular' => $popularOnly ? null : 1, 'page' => null]) }}"
                           class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl border-2 transition active:scale-95
                                  {{ $popularOnly
                                     ? 'bg-gradient-to-br from-orange-50 to-rose-50 border-orange-400 text-rose-700'
                                     : 'bg-white border-slate-200 text-slate-700 hover:border-orange-300' }}">
                            <span class="flex items-center gap-2 text-sm font-bold">
                                <span class="text-base leading-none">🔥</span>
                                Top Afrique uniquement
                            </span>
                            @if($popularOnly)
                                <span class="inline-flex w-5 h-5 rounded-full bg-orange-500 text-white items-center justify-center shadow">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </span>
                            @else
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Activer</span>
                            @endif
                        </a>
                        <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">Steam, PSN, Xbox, Netflix, Spotify, Apple, Roblox, Riot, Epic — les plus utilisées en Afrique.</p>
                    </div>

                    {{-- ===== Section : Marques populaires (raccourcis recherche directe) ===== --}}
                    @php
                        $brandShortcuts = [
                            ['Steam',      'Steam',      '#171A21'],
                            ['PSN',        'Playstation','#003791'],
                            ['Xbox',       'Xbox',       '#107C10'],
                            ['Netflix',    'Netflix',    '#E50914'],
                            ['Spotify',    'Spotify',    '#1DB954'],
                            ['Apple',      'Apple',      '#000000'],
                            ['Roblox',     'Roblox',     '#00A2FF'],
                            ['Riot',       'Riot',       '#D13639'],
                            ['Epic Games', 'Epic',       '#2A2A2A'],
                            ['Google Play','Google Play','#01875F'],
                        ];
                    @endphp
                    <div x-data="{ open: true }" class="border-b border-slate-100">
                        <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50/50 transition">
                            <span class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                                Marques populaires
                            </span>
                            <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-collapse class="px-3 pb-4">
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($brandShortcuts as [$label, $term, $color])
                                    @php $isActive = strtolower($search ?? '') === strtolower($term); @endphp
                                    <a href="{{ $urlWith(['search' => $isActive ? null : $term, 'page' => null]) }}"
                                       class="group flex items-center gap-2 px-3 py-2 rounded-lg border-2 transition active:scale-95
                                              {{ $isActive ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-400' }}">
                                        <span class="w-5 h-5 rounded-md flex items-center justify-center text-white text-[10px] font-black flex-shrink-0"
                                              style="background:{{ $color }};">{{ strtoupper(substr($label, 0, 1)) }}</span>
                                        <span class="text-xs font-bold truncate">{{ $label }}</span>
                                    </a>
                                @endforeach
                            </div>
                            <p class="text-[10px] text-slate-400 mt-2 px-1">Clic = recherche directe sur la marque.</p>
                        </div>
                    </div>

                    {{-- Sections "Région" et "Pays disponibles" retirées sur demande user
                         (mai 2026). Les filtres Region restent disponibles via les
                         quick-pills en haut de la boutique. --}}

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
                            Voir {{ $pagination['total'] ?? 0 }} produit{{ ($pagination['total'] ?? 0) > 1 ? 's' : '' }}
                        </button>
                    </div>
                </form>
            </aside>

            {{-- ============================
                 PRODUCTS GRID
                 ============================ --}}
            <div>
                @if(isset($products) && count($products) > 0)
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
                                $cardHref  = route('card-type.show', $product['cardType']['internalId'] ?? $product['cardType']['id'] ?? '');
                                $ctId      = $product['cardType']['id'] ?? null;
                                $ctCount   = $ctId !== null ? ($cardTypeCounts[$ctId] ?? 1) : 1;
                            @endphp

                            {{-- Vue grille : carte verticale (composant existant) --}}
                            <div x-show="view === 'grid'">
                                <x-product-card
                                    :name="$product['name']"
                                    :brand-label="$brandName"
                                    :brand-color="$bgColor"
                                    :logo-url="$logoUrl"
                                    :price="$minPrice"
                                    :currency="$currency"
                                    :href="$cardHref"
                                    :discount="2"
                                    :products-count="$ctCount"
                                    :country-code="$product['cardType']['countryCode'] ?? null"
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
                                            <h4 class="text-sm font-semibold text-slate-900 line-clamp-1 group-hover:text-[#44A08D] transition">{{ $product['name'] }}</h4>
                                            <p class="text-xs text-slate-500 mt-0.5">
                                                {{ $brandName }}@if($ctCount > 1) <span class="text-slate-400">· {{ $ctCount }} montants disponibles</span>@endif
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between sm:justify-end gap-3 sm:shrink-0">
                                        <div class="text-right">
                                            <p class="text-[10px] text-slate-400 leading-none">À partir de</p>
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

                    {{-- Pagination --}}
                    @if(isset($pagination) && $pagination['last_page'] > 1)
                        <div class="mt-10 flex items-center justify-center">
                            <nav class="inline-flex items-center gap-1" aria-label="Pagination">
                                @if ($pagination['current_page'] > 1)
                                    <a href="{{ route('boutique', array_merge(request()->except('page'), ['page' => $pagination['current_page'] - 1])) }}"
                                       class="w-10 h-10 rounded-lg flex items-center justify-center bg-white border border-slate-200 text-slate-700 hover:border-[#44A08D] hover:text-[#44A08D] transition active:scale-95">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                    </a>
                                @else
                                    <span class="w-10 h-10 rounded-lg flex items-center justify-center bg-slate-50 border border-slate-200 text-slate-300 cursor-not-allowed">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                    </span>
                                @endif

                                @php
                                    $cur = $pagination['current_page'];
                                    $last = $pagination['last_page'];
                                    $pages = collect(range(1, $last))
                                        ->filter(fn($p) => $p === 1 || $p === $last || abs($p - $cur) <= 1)
                                        ->values();
                                    $prevPage = null;
                                @endphp

                                @foreach($pages as $i)
                                    @if($prevPage !== null && $i - $prevPage > 1)
                                        <span class="w-10 h-10 flex items-center justify-center text-slate-400">…</span>
                                    @endif
                                    @if ($i == $cur)
                                        <span aria-current="page" class="w-10 h-10 rounded-lg flex items-center justify-center bg-[#44A08D] text-white text-sm font-bold shadow-md shadow-[#44A08D]/25">
                                            {{ $i }}
                                        </span>
                                    @else
                                        <a href="{{ route('boutique', array_merge(request()->except('page'), ['page' => $i])) }}"
                                           class="w-10 h-10 rounded-lg flex items-center justify-center bg-white border border-slate-200 text-slate-700 hover:border-[#44A08D] hover:text-[#44A08D] text-sm font-semibold transition active:scale-95">
                                            {{ $i }}
                                        </a>
                                    @endif
                                    @php $prevPage = $i; @endphp
                                @endforeach

                                @if ($pagination['current_page'] < $pagination['last_page'])
                                    <a href="{{ route('boutique', array_merge(request()->except('page'), ['page' => $pagination['current_page'] + 1])) }}"
                                       class="w-10 h-10 rounded-lg flex items-center justify-center bg-white border border-slate-200 text-slate-700 hover:border-[#44A08D] hover:text-[#44A08D] transition active:scale-95">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                @else
                                    <span class="w-10 h-10 rounded-lg flex items-center justify-center bg-slate-50 border border-slate-200 text-slate-300 cursor-not-allowed">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </span>
                                @endif
                            </nav>
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
@endpush

@endsection
