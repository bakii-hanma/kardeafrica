@extends('layouts.app')

@section('title', 'Carte Gabon — Cartes-cadeau des marchands gabonais')

@php
    // Helper : reconstruit l'URL avec des params modifiés (mirror /boutique)
    $urlWith = function (array $changes) {
        $params = array_merge(request()->query(), $changes);
        // Nettoie : vire les clés à null/empty array
        foreach ($params as $k => $v) {
            if ($v === null || (is_array($v) && empty($v)) || $v === '') {
                unset($params[$k]);
            }
        }
        return route('gabon.index') . (empty($params) ? '' : '?' . http_build_query($params));
    };
@endphp

@section('content')
<div class="bg-slate-50 min-h-screen pb-12"
     x-data="{ view: localStorage.getItem('gabon-view') || 'grid' }"
     x-init="$watch('view', v => localStorage.setItem('gabon-view', v))">

    {{-- ================================================================
         TOP STRIP — breadcrumb + title + toolbar (search/sort/view)
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
                <a href="{{ route('gabon.index') }}" class="hover:text-[#44A08D] transition">Carte Gabon</a>
                @if($currentCategory)
                    <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-slate-900 font-medium">{{ $currentCategory }}</span>
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
                        {{ $currentCategory }}
                    @else
                        Cartes-cadeau locales · Gabon
                    @endif
                </h1>
                <p class="text-xs md:text-sm text-slate-500 mt-1">
                    <span class="font-semibold text-slate-700">{{ $cards->total() }}</span>
                    carte{{ $cards->total() > 1 ? 's' : '' }} disponible{{ $cards->total() > 1 ? 's' : '' }}
                    @if($activeFiltersCount > 0)
                        · {{ $activeFiltersCount }} filtre{{ $activeFiltersCount > 1 ? 's' : '' }} actif{{ $activeFiltersCount > 1 ? 's' : '' }}
                    @endif
                </p>
            </div>

            {{-- Quick filter pills : Catégories --}}
            <div class="pb-3">
                <div class="flex items-center gap-2 flex-wrap">
                    <a href="{{ $urlWith(['category' => null, 'page' => null]) }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full text-xs font-bold whitespace-nowrap transition active:scale-95
                              {{ $categorySlug === '' ? 'bg-slate-900 text-white shadow-md' : 'bg-white border border-slate-200 text-slate-700 hover:border-[#44A08D] hover:bg-teal-50' }}">
                        Toutes
                        @if($categorySlug === '')
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        @endif
                    </a>
                    @foreach($categories as $slug => $label)
                        @php $catActive = $categorySlug === $slug; @endphp
                        <a href="{{ $urlWith(['category' => $catActive ? null : $slug, 'page' => null]) }}"
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full text-xs font-bold whitespace-nowrap transition active:scale-95
                                  {{ $catActive ? 'bg-slate-900 text-white shadow-md' : 'bg-white border border-slate-200 text-slate-700 hover:border-[#44A08D] hover:bg-teal-50' }}">
                            {{ $label }}
                            @if($catActive)
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Toolbar : search + actions row --}}
            <div class="pb-4 space-y-2 md:space-y-0 md:flex md:items-center md:gap-3 md:justify-end">
                {{-- Search --}}
                <form action="{{ route('gabon.index') }}" method="GET" class="flex items-center bg-slate-50 border border-slate-200 rounded-xl pl-3 pr-1 py-1 focus-within:bg-white focus-within:border-[#44A08D] focus-within:shadow-card transition w-full md:w-auto md:min-w-[320px]" data-no-loader>
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    @if($categorySlug)<input type="hidden" name="category" value="{{ $categorySlug }}">@endif
                    <svg class="w-4 h-4 text-slate-400 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Rechercher une carte…"
                           class="flex-1 min-w-0 bg-transparent border-0 text-slate-900 placeholder-slate-400 focus:ring-0 text-sm focus:outline-none py-2">
                    @if($search)
                        <a href="{{ $urlWith(['search' => null, 'page' => null]) }}" class="w-7 h-7 rounded-md flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 mr-1 shrink-0" aria-label="Effacer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-gradient-to-br from-[#44A08D] to-[#4ECDC4] text-white text-sm font-bold shadow-md shadow-[#44A08D]/30 active:scale-95 transition shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <span class="hidden sm:inline">OK</span>
                    </button>
                </form>

                {{-- Actions row : Filtres mobile + Tri + View --}}
                <div class="flex items-center gap-2">
                    {{-- Bouton Filtres mobile --}}
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

                    {{-- View toggle (desktop) --}}
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
                        <a href="{{ $urlWith(['search' => null, 'page' => null]) }}"
                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-900 text-white text-xs font-medium hover:bg-slate-700 transition">
                            <span>Recherche : {{ \Illuminate\Support\Str::limit($search, 20) }}</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                    @if($currentCategory)
                        <a href="{{ $urlWith(['category' => null, 'page' => null]) }}"
                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-900 text-white text-xs font-medium hover:bg-slate-700 transition">
                            <span>{{ $currentCategory }}</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                    @foreach($priceRanges as $range)
                        <a href="{{ $urlWith(['price_range' => array_values(array_diff($priceRanges, [$range])), 'page' => null]) }}"
                           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-medium hover:bg-slate-200 transition">
                            <span>{{ $priceRangeLabels[$range] ?? $range }} F</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endforeach
                    <a href="{{ route('gabon.index') }}" class="text-xs font-semibold text-slate-500 hover:text-[#44A08D] underline ml-1 transition">
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

            {{-- ============ SIDEBAR (drawer mobile / sticky desktop) ============ --}}
            <aside x-data="{ mobileOpen: false }"
                   @open-filters.window="mobileOpen = true"
                   @keydown.escape.window="mobileOpen = false"
                   :class="mobileOpen ? 'fixed inset-0 z-50' : ''"
                   class="lg:sticky lg:top-[120px] lg:self-start lg:!relative lg:!inset-auto lg:!z-auto">

                <div x-show="mobileOpen" x-transition.opacity x-cloak
                     @click="mobileOpen = false"
                     class="lg:hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

                <form id="filterForm" action="{{ route('gabon.index') }}" method="GET"
                      :class="mobileOpen
                        ? 'fixed left-0 top-0 bottom-0 w-[88%] max-w-sm overflow-y-auto rounded-r-2xl rounded-l-none border-r border-slate-200'
                        : 'hidden lg:block'"
                      class="lg:!static lg:!w-auto lg:!max-w-none lg:!overflow-visible lg:rounded-2xl lg:!border bg-white border border-slate-200 shadow-card overflow-hidden">

                    @if($search)<input type="hidden" name="search" value="{{ $search }}">@endif
                    @if($sort !== 'newest')<input type="hidden" name="sort" value="{{ $sort }}">@endif

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
                                <a href="{{ route('gabon.index') }}" class="text-xs font-semibold text-[#44A08D] hover:underline">Effacer</a>
                            @endif
                            <button type="button" @click="mobileOpen = false" class="lg:hidden w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center active:scale-95 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- ===== Section : Catégories ===== --}}
                    <div x-data="{ open: true }" class="border-b border-slate-100">
                        <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50/50 transition">
                            <span class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                                Catégories
                                @if($categorySlug)
                                    <span class="px-1.5 py-0.5 rounded-md bg-teal-50 text-[#44A08D] text-[10px] font-bold">1</span>
                                @endif
                            </span>
                            <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-collapse class="px-3 pb-4 space-y-0.5">
                            <a href="{{ $urlWith(['category' => null, 'page' => null]) }}"
                               class="flex items-center justify-between px-3 py-2 rounded-lg text-sm transition
                                      {{ $categorySlug === '' ? 'bg-slate-900 text-white font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
                                <span>Toutes</span>
                                @if($categorySlug === '')
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                @endif
                            </a>
                            @foreach($categories as $slug => $label)
                                <a href="{{ $urlWith(['category' => $slug, 'page' => null]) }}"
                                   class="flex items-center justify-between px-3 py-2 rounded-lg text-sm transition
                                          {{ $categorySlug === $slug ? 'bg-slate-900 text-white font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
                                    <span>{{ $label }}</span>
                                    @if($categorySlug === $slug)
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- ===== Section : Gamme de prix ===== --}}
                    <div x-data="{ open: true }" class="border-b border-slate-100">
                        <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50/50 transition">
                            <span class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                                Gamme de prix
                                @if(count($priceRanges))
                                    <span class="px-1.5 py-0.5 rounded-md bg-teal-50 text-[#44A08D] text-[10px] font-bold">{{ count($priceRanges) }}</span>
                                @endif
                            </span>
                            <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open" x-collapse class="px-5 pb-4">
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($priceRangeLabels as $value => $label)
                                    @php $isActive = in_array($value, $priceRanges, true); @endphp
                                    <label class="cursor-pointer">
                                        <input type="checkbox" name="price_range[]" value="{{ $value }}" onchange="this.form.submit()"
                                               {{ $isActive ? 'checked' : '' }} class="sr-only peer">
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

                    {{-- Footer mobile : "Voir les résultats" --}}
                    <div class="lg:hidden sticky bottom-0 left-0 right-0 px-5 py-3 bg-white border-t border-slate-100 flex items-center gap-2">
                        @if($activeFiltersCount > 0)
                            <a href="{{ route('gabon.index') }}" class="px-3 py-2.5 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">Effacer</a>
                        @endif
                        <button type="button" @click="mobileOpen = false" class="flex-1 px-3 py-2.5 rounded-lg bg-gradient-to-br from-[#44A08D] to-[#4ECDC4] text-white text-sm font-bold shadow-md">
                            Voir {{ $cards->total() }} carte{{ $cards->total() > 1 ? 's' : '' }}
                        </button>
                    </div>
                </form>
            </aside>

            {{-- ============ GRID / LIST ============ --}}
            <main>
                @if($cards->isEmpty())
                    <div class="bg-white rounded-2xl border-2 border-dashed border-slate-200 p-12 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-teal-50 to-emerald-50 text-[#44A08D] mb-4">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                        <h3 class="font-display text-lg font-bold text-slate-900 mb-1">Aucune carte trouvée</h3>
                        <p class="text-sm text-slate-500 mb-4">
                            @if($search || $categorySlug || count($priceRanges))
                                Aucun résultat pour ces filtres. Essaie d'élargir ta recherche.
                            @else
                                Aucune carte locale n'est encore publiée. Reviens bientôt !
                            @endif
                        </p>
                        @if($activeFiltersCount > 0)
                            <a href="{{ route('gabon.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-700 transition">
                                Réinitialiser
                            </a>
                        @endif
                    </div>
                @else
                    {{-- ===== Grid view (mirror /boutique <x-product-card>) ===== --}}
                    <div x-show="view === 'grid'" class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($cards as $card)
                            @php
                                $denomsCount = count($card->denominations ?? []);
                                $min = collect($card->denominations ?? [])->min() ?? 0;
                            @endphp
                            <a href="{{ route('gabon.card', $card) }}"
                               class="group block overflow-hidden rounded-2xl bg-white border border-slate-100 shadow-card hover:shadow-card-hover transition-all duration-300 hover:-translate-y-1 hover:scale-[1.02] active:scale-[0.99] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#44A08D]">

                                {{-- Visual --}}
                                @include('partials._merchant-card-visual', ['card' => $card])

                                {{-- Info block (= identique au <x-product-card>) --}}
                                <div class="relative bg-white p-3 sm:p-4">
                                    <h4 class="text-xs sm:text-sm font-semibold text-slate-900 leading-snug line-clamp-2 group-hover:text-[#44A08D] transition-colors">
                                        {{ $card->name }}
                                    </h4>

                                    @if($denomsCount > 1)
                                        <p class="text-[10px] text-slate-400 mt-0.5 font-medium">
                                            {{ $denomsCount }} montants disponibles{{ $card->allow_custom_amount ? ' + libre' : '' }}
                                        </p>
                                    @elseif($card->allow_custom_amount)
                                        <p class="text-[10px] text-slate-400 mt-0.5 font-medium">Montant libre</p>
                                    @endif

                                    <div class="mt-2.5 sm:mt-3 pt-2.5 sm:pt-3 border-t border-slate-100 flex items-end justify-between gap-2">
                                        <span class="text-[10px] text-slate-400 font-medium">Dès</span>
                                        <span class="text-sm sm:text-base font-black tabular-nums text-slate-900 whitespace-nowrap">
                                            {{ number_format($min, 0, ',', ' ') }} <span class="text-[10px] font-bold text-slate-500">FCFA</span>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    {{-- ===== List view ===== --}}
                    <div x-show="view === 'list'" class="space-y-3" x-cloak>
                        @foreach($cards as $card)
                            @php $min = collect($card->denominations ?? [])->min(); @endphp
                            <a href="{{ route('gabon.card', $card) }}" class="flex bg-white rounded-2xl border border-slate-200 hover:border-[#44A08D] hover:shadow-card overflow-hidden transition group">
                                {{-- Left visual (fixed width) --}}
                                <div class="w-32 sm:w-44 flex-shrink-0 relative">
                                    @if($card->visual_url)
                                        <img src="{{ asset($card->visual_url) }}" alt="" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full bg-gradient-to-br from-slate-800 to-[#0F4F44]"></div>
                                    @endif
                                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-transparent to-white/0 pointer-events-none"></div>
                                </div>
                                {{-- Right info --}}
                                <div class="flex-1 p-4 flex items-center gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-[10px] font-bold uppercase tracking-wider text-[#44A08D]">
                                            KardAfrica
                                        </div>
                                        <h3 class="font-display text-base font-bold text-slate-900 mt-0.5 line-clamp-1 group-hover:text-[#44A08D] transition">
                                            {{ $card->name }}
                                        </h3>
                                        <p class="text-xs text-slate-500 mt-1 line-clamp-1">
                                            @if(isset($categories[$card->category])){{ $categories[$card->category] }} · @endif
                                            Gabon · {{ $card->validity_months }} mois
                                        </p>
                                        <div class="flex flex-wrap gap-1 mt-2">
                                            @foreach(array_slice($card->denominations ?? [], 0, 3) as $d)
                                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px] font-bold tabular-nums">{{ number_format($d, 0, ',', ' ') }} F</span>
                                            @endforeach
                                            @if($card->allow_custom_amount)
                                                <span class="px-2 py-0.5 rounded bg-teal-50 text-[#44A08D] text-[10px] font-bold">+ libre</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Dès</div>
                                        <div class="font-display text-lg font-bold text-slate-900 tabular-nums">{{ number_format($min ?? 0, 0, ',', ' ') }}<span class="text-xs text-slate-500 font-semibold ml-0.5">F</span></div>
                                        <span class="inline-flex items-center gap-1 mt-1 text-xs font-bold text-[#44A08D]">
                                            Voir
                                            <svg class="w-3.5 h-3.5 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    {{-- ===== Pagination ===== --}}
                    @if($cards->lastPage() > 1)
                        <div class="mt-8 flex items-center justify-center gap-1.5 flex-wrap">
                            @if($cards->onFirstPage())
                                <span class="w-9 h-9 rounded-lg bg-slate-100 text-slate-300 flex items-center justify-center cursor-not-allowed">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                </span>
                            @else
                                <a href="{{ $cards->previousPageUrl() }}" class="w-9 h-9 rounded-lg bg-white border border-slate-200 text-slate-700 hover:border-[#44A08D] hover:text-[#44A08D] flex items-center justify-center transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                                </a>
                            @endif

                            @php
                                $current = $cards->currentPage();
                                $last    = $cards->lastPage();
                                $pages   = collect(range(1, $last))->filter(fn ($p) => $p === 1 || $p === $last || abs($p - $current) <= 1);
                                $prev    = 0;
                            @endphp
                            @foreach($pages as $p)
                                @if($prev > 0 && $p - $prev > 1)
                                    <span class="w-9 h-9 flex items-center justify-center text-slate-400">…</span>
                                @endif
                                @if($p === $current)
                                    <span class="min-w-9 h-9 px-3 rounded-lg bg-gradient-to-br from-[#44A08D] to-[#4ECDC4] text-white text-sm font-bold flex items-center justify-center shadow-md shadow-[#44A08D]/30">{{ $p }}</span>
                                @else
                                    <a href="{{ $cards->url($p) }}" class="min-w-9 h-9 px-3 rounded-lg bg-white border border-slate-200 text-slate-700 hover:border-[#44A08D] hover:text-[#44A08D] text-sm font-semibold flex items-center justify-center transition">{{ $p }}</a>
                                @endif
                                @php $prev = $p; @endphp
                            @endforeach

                            @if($cards->hasMorePages())
                                <a href="{{ $cards->nextPageUrl() }}" class="w-9 h-9 rounded-lg bg-white border border-slate-200 text-slate-700 hover:border-[#44A08D] hover:text-[#44A08D] flex items-center justify-center transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            @else
                                <span class="w-9 h-9 rounded-lg bg-slate-100 text-slate-300 flex items-center justify-center cursor-not-allowed">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </span>
                            @endif
                        </div>
                        <p class="text-center text-xs text-slate-500 mt-2">
                            Page {{ $current }} sur {{ $last }} · {{ $cards->total() }} carte{{ $cards->total() > 1 ? 's' : '' }}
                        </p>
                    @endif
                @endif
            </main>
        </div>
    </div>
</div>
@endsection
