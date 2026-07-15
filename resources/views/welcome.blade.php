@extends('layouts.app')

@section('title', 'Kardafrica - Cartes numériques en un clic !')

@php
    // Helpers locaux pour ne pas polluer les vues / preparer les donnees
    $brandPalette = [
        '#0F172A', '#44A08D', '#1F2937', '#0EA5E9', '#7C3AED',
        '#DC2626', '#EA580C', '#059669', '#0D9488', '#6D28D9', '#BE185D',
    ];

    // Couleurs de marque officielles — priment sur le hash pour que chaque
    // marque connue ait SA couleur (ex. Nintendo = rouge, pas une couleur au
    // hasard). Match par sous-chaîne (insensible à la casse) sur le nom.
    $brandColorMap = [
        'nintendo'     => '#E60012',
        'netflix'      => '#E50914',
        'spotify'      => '#1DB954',
        'apple'        => '#1A1A1A',
        'itunes'       => '#1A1A1A',
        'app store'    => '#1A1A1A',
        'steam'        => '#1B2838',
        'playstation'  => '#003791',
        'psn'          => '#003791',
        'xbox'         => '#107C10',
        'google play'  => '#01875F',
        'play store'   => '#01875F',
        'roblox'       => '#E2231A',
        'prime video'  => '#00A8E1',
        'amazon'       => '#FF9900',
        'deezer'       => '#A238FF',
        'disney'       => '#113CCF',
    ];

    $brandColor = function ($name) use ($brandPalette, $brandColorMap) {
        $name = (string) $name;
        if ($name === '') return $brandPalette[0];
        // 1) Couleur officielle si marque connue
        $lower = strtolower($name);
        foreach ($brandColorMap as $kw => $hex) {
            if (str_contains($lower, $kw)) return $hex;
        }
        // 2) Sinon, couleur déterministe par hash du nom
        $hash = 0;
        for ($i = 0; $i < strlen($name); $i++) {
            $hash = ord($name[$i]) + (($hash << 5) - $hash);
        }
        $count = count($brandPalette);
        // Modulo positif garanti même si $hash est négatif (overflow bitshift sur PHP 64-bit)
        $idx = (($hash % $count) + $count) % $count;
        return $brandPalette[$idx];
    };

    $sanitizeLogo = function ($url) {
        if ($url && (str_contains($url, 'localhost') || str_contains($url, '127.0.0.1'))) {
            return parse_url($url, PHP_URL_PATH);
        }
        return $url;
    };

    // Map d'icones SVG par categorie (no emoji)
    $categoryIcons = [
        'divertissement' => 'film',
        'jeux'           => 'gamepad',
        'jeu'            => 'gamepad',
        'game'           => 'gamepad',
        'musique'        => 'music',
        'music'          => 'music',
        'shopping'       => 'shopping-bag',
        'achat'          => 'shopping-bag',
        'voyage'         => 'plane',
        'travel'         => 'plane',
        'daywatch'       => 'tv',
        'streaming'      => 'tv',
        'app'            => 'smartphone',
        'food'           => 'utensils',
        'manger'         => 'utensils',
    ];

    $iconFor = function ($name) use ($categoryIcons) {
        $n = strtolower($name);
        foreach ($categoryIcons as $kw => $icon) {
            if (str_contains($n, $kw)) return $icon;
        }
        return 'tag';
    };

    // Demo cards utilisees en fallback quand l'API externe ne renvoie rien
    // (filtre pays whitelist FR/US/GLOBAL — l'API ne contient parfois que des cartes UAE)
    $demoBrands = [
        ['name' => 'Netflix Premium',     'brand' => 'Netflix',     'color' => '#E50914', 'price' => 9900,  'currency' => 'XAF'],
        ['name' => 'Spotify Premium',     'brand' => 'Spotify',     'color' => '#1DB954', 'price' => 6500,  'currency' => 'XAF'],
        ['name' => 'Steam Wallet',        'brand' => 'Steam',       'color' => '#171a21', 'price' => 5000,  'currency' => 'XAF'],
        ['name' => 'Apple Gift Card',     'brand' => 'Apple',       'color' => '#000000', 'price' => 15000, 'currency' => 'XAF'],
        ['name' => 'Amazon Gift Card',    'brand' => 'Amazon',      'color' => '#FF9900', 'price' => 12000, 'currency' => 'XAF'],
        ['name' => 'PlayStation Store',   'brand' => 'PlayStation', 'color' => '#003791', 'price' => 10000, 'currency' => 'XAF'],
        ['name' => 'Xbox Live',           'brand' => 'Xbox',        'color' => '#107C10', 'price' => 8500,  'currency' => 'XAF'],
        ['name' => 'Nintendo eShop',      'brand' => 'Nintendo',    'color' => '#E4000F', 'price' => 9300,  'currency' => 'XAF'],
        ['name' => 'Google Play',         'brand' => 'Google',      'color' => '#01875F', 'price' => 7000,  'currency' => 'XAF'],
        ['name' => 'Disney+',             'brand' => 'Disney',      'color' => '#0E47A1', 'price' => 6000,  'currency' => 'XAF'],
    ];

    $hasRealData = isset($cardTypes) && count($cardTypes) > 0;

    // Premieres 3 cartes pour le hero stack — vraies si dispo, sinon demo
    if ($hasRealData) {
        $heroCards = collect($cardTypes)->take(3)->map(fn($c) => [
            'name'        => $c['name'] ?? 'Carte cadeau',
            'brand'       => explode(' ', $c['name'] ?? 'Brand')[0],
            'color'       => $brandColor($c['name'] ?? ''),
            'price'       => $c['products'][0]['price']['min'] ?? 0,
            'currency'    => $c['products'][0]['price']['currencyCode'] ?? 'XAF',
            'logoUrl'     => $sanitizeLogo($c['logoUrl'] ?? null),
            'countryCode' => $c['countryCode'] ?? null,
        ]);
    } else {
        $heroCards = collect($demoBrands)->take(3)->map(fn($d) => array_merge($d, [
            'logoUrl'     => null,
            'countryCode' => 'GLOBAL',
        ]));
    }
@endphp

@section('content')
<div class="bg-[#FAFAF7] min-h-screen pb-20">

    {{-- ================================================================
         HERO — "Vos marques préférées, livrées en 30 secondes."
         ================================================================ --}}
    <section class="relative overflow-hidden">
        {{-- Background ambient gradient --}}
        <div class="absolute inset-0 -z-10">
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[1200px] h-[600px] bg-gradient-to-br from-[#4ECDC4]/10 via-transparent to-[#44A08D]/5 rounded-full blur-3xl"></div>
            <div class="absolute -top-32 right-0 w-[500px] h-[500px] bg-[#44A08D]/5 rounded-full blur-3xl"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 md:pt-20 pb-12 md:pb-24">
            <div class="grid lg:grid-cols-2 gap-8 lg:gap-12 items-center">

                {{-- ============================================================ --}}
                {{-- MOBILE ONLY : card stack 3D compact (landscape) au-dessus du texte --}}
                {{-- `flex lg:hidden` : visible < 1024px, complètement caché ≥ 1024px   --}}
                {{-- IMPORTANT : pas de `display:` en inline sinon ça écrase lg:hidden  --}}
                {{-- ============================================================ --}}
                <div class="flex lg:hidden justify-center items-center" style="padding:0 24px;margin-bottom:8px;">
                    <div style="position:relative;width:100%;max-width:300px;aspect-ratio:1.586/1;">
                        <div class="card-stack card-stack-mobile" style="position:relative;width:100%;height:100%;perspective:1200px;transform-style:preserve-3d;">
                            @foreach ($heroCards as $card)
                                <div class="stack-card" style="position:absolute;inset:0;border-radius:18px;overflow:hidden;border:1px solid rgba(15,23,42,0.05);box-shadow:0 24px 48px -12px rgba(0,0,0,0.30);">
                                    @include('partials._gift-card-visual', [
                                        'brandLabel'  => $card['brand'],
                                        'brandColor'  => $card['color'],
                                        'countryCode' => $card['countryCode'] ?? null,
                                        'currency'    => $card['currency'],
                                        'compact'     => true,
                                        'logoUrl'     => $card['logoUrl'] ?? null,
                                    ])
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Colonne gauche : copy --}}
                <div class="text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white border border-slate-200 shadow-card mb-6">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span class="text-xs font-semibold text-slate-700">Livraison instantanée des cartes</span>
                    </div>

                    <h1 class="font-display text-4xl sm:text-5xl lg:text-6xl font-bold text-slate-900 leading-[1.05] tracking-tight">
                        Vos marques préférées,
                        <span class="block bg-gradient-to-r from-[#44A08D] to-[#4ECDC4] bg-clip-text text-transparent">
                            livrées en 30 secondes.
                        </span>
                    </h1>

                    <p class="mt-6 text-base sm:text-lg text-slate-600 max-w-lg mx-auto lg:mx-0">
                        Achetez vos cartes cadeaux Netflix, Apple, Steam, Nintendo et plus de 300 marques.
                        Paiement Mobile Money, code reçu instantanément.
                    </p>

                    <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center lg:justify-start">
                        <a href="{{ route('boutique') }}"
                           class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-[#44A08D] hover:bg-[#3d9180] text-white font-semibold shadow-lg shadow-[#44A08D]/25 transition-all duration-200 active:scale-95">
                            <span>Voir le catalogue</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                        <a href="#how-it-works"
                           class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-white hover:bg-slate-50 text-slate-900 font-semibold border border-slate-200 transition-all duration-200 active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Comment ça marche</span>
                        </a>
                    </div>

                    {{-- Trust indicators --}}
                    <div class="mt-8 grid grid-cols-3 gap-4 max-w-lg mx-auto lg:mx-0">
                        @foreach ([
                            ['icon' => 'bolt',     'label' => 'Livraison 30s'],
                            ['icon' => 'shield',   'label' => 'Paiement sécurisé'],
                            ['icon' => 'headset',  'label' => 'Support 24/7'],
                        ] as $trust)
                            <div class="flex flex-col items-center lg:items-start gap-1.5">
                                <div class="w-9 h-9 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-[#44A08D] shadow-card">
                                    @if($trust['icon'] === 'bolt')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                    @elseif($trust['icon'] === 'shield')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414" /></svg>
                                    @endif
                                </div>
                                <span class="text-xs font-semibold text-slate-700">{{ $trust['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Colonne droite : card stack 3D (desktop) — utilise le visuel hybride --}}
                <div class="hidden lg:flex justify-center items-center">
                    <div class="relative w-full max-w-md" style="aspect-ratio: 1.586/1;">
                        <div class="card-stack relative w-full h-full">
                            @foreach ($heroCards as $card)
                                <div class="stack-card rounded-3xl overflow-hidden border border-slate-100 shadow-pop">
                                    @include('partials._gift-card-visual', [
                                        'brandLabel'  => $card['brand'],
                                        'brandColor'  => $card['color'],
                                        'countryCode' => $card['countryCode'] ?? null,
                                        'currency'    => $card['currency'],
                                        'compact'     => false,
                                    ])
                                </div>
                            @endforeach
                        </div>

                        {{-- Sous le stack : nom + prix du 1er card (info contextuelle) --}}
                        @if(isset($heroCards[0]))
                            <div class="mt-4 text-center lg:text-left">
                                <p class="text-sm text-slate-500">
                                    Ex : <span class="font-semibold text-slate-900">{{ $heroCards[0]['name'] }}</span>
                                    · dès <span class="font-black text-[#44A08D] tabular-nums">{{ \App\Support\Money::formatFcfa($heroCards[0]['price'], $heroCards[0]['currency']) }}</span>
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ================================================================
             SEARCH (desktop) — bouton qui ouvre le command palette (Ctrl+K)
             ================================================================ --}}
        <section class="hidden md:block mb-12">
            <button type="button" data-search-trigger
                    style="width: 100%; max-width: 640px; margin: 0 auto; display: flex; align-items: center;
                           padding: 16px 20px; background: #ffffff; border: 1px solid #E2E8F0; border-radius: 16px;
                           cursor: pointer; text-align: left; transition: all .2s ease;
                           box-shadow: 0 1px 2px rgba(15,23,42,0.04);"
                    onmouseover="this.style.borderColor='#44A08D';this.style.boxShadow='0 10px 24px -8px rgba(68,160,141,0.20)';"
                    onmouseout="this.style.borderColor='#E2E8F0';this.style.boxShadow='0 1px 2px rgba(15,23,42,0.04)';"
                    aria-label="Rechercher une carte">
                <svg style="width: 20px; height: 20px; color: #94A3B8; margin-right: 12px; flex-shrink: 0;"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <span style="flex: 1; color: #94A3B8; font-size: 15px; font-weight: 500;">
                    Rechercher une carte (Netflix, Apple, Steam, Nintendo…)
                </span>
                <span style="display: inline-flex; align-items: center; gap: 4px; margin-left: 12px;
                             padding: 4px 8px; border-radius: 6px;
                             background: #F8FAFC; border: 1px solid #E2E8F0;
                             font-family: ui-monospace, monospace; font-size: 10px; font-weight: 700; color: #64748B;">
                    ⌘ K
                </span>
            </button>

            {{-- Suggestions chips — déclenchent le modal pré-rempli --}}
            <div class="mt-4 flex flex-wrap justify-center gap-2 text-sm">
                <span class="text-slate-500 mr-1">Populaires :</span>
                @foreach (['Netflix', 'Spotify', 'Apple', 'Steam', 'Nintendo', 'PlayStation'] as $suggestion)
                    <button type="button" data-search-suggestion="{{ $suggestion }}" data-search-trigger
                            style="padding: 4px 12px; border-radius: 9999px; cursor: pointer;
                                   background: #ffffff; border: 1px solid #E2E8F0;
                                   color: #475569; font-size: 12px; font-weight: 500; transition: all .15s;"
                            onmouseover="this.style.borderColor='#44A08D';this.style.color='#44A08D';"
                            onmouseout="this.style.borderColor='#E2E8F0';this.style.color='#475569';">
                        {{ $suggestion }}
                    </button>
                @endforeach
            </div>
        </section>

        {{-- ================================================================
             CATEGORIES — bento gradient cards (one signature color per cat)
             ================================================================ --}}
        @php
            // Mapping: category name keyword -> [icon key, gradient classes, decorative pattern color, bg image]
            // Les images sont uploadées sous public_html/assets/banner/universes/
            $categoryStyles = [
                'divertissement' => ['icon' => 'film',         'gradient' => 'from-rose-500 via-rose-600 to-pink-700',     'glow' => 'bg-rose-400/40',   'bg' => 'assets/banner/universes/divertissement.jpg'],
                'jeu'            => ['icon' => 'gamepad',      'gradient' => 'from-violet-500 via-purple-600 to-indigo-700','glow' => 'bg-violet-400/40','bg' => 'assets/banner/universes/jeu.jpg'],
                'musique'        => ['icon' => 'music',        'gradient' => 'from-emerald-500 via-teal-600 to-cyan-700',  'glow' => 'bg-emerald-400/40','bg' => 'assets/banner/universes/musique.jpg'],
                'shopping'       => ['icon' => 'shopping-bag', 'gradient' => 'from-amber-500 via-orange-600 to-red-600',   'glow' => 'bg-amber-400/40',  'bg' => 'assets/banner/universes/shopping.jpg'],
                'voyage'         => ['icon' => 'plane',        'gradient' => 'from-fuchsia-500 via-pink-600 to-rose-700',  'glow' => 'bg-fuchsia-400/40','bg' => 'assets/banner/universes/voyage.jpg'],
                'daywatch'       => ['icon' => 'tv',           'gradient' => 'from-sky-500 via-blue-600 to-indigo-700',    'glow' => 'bg-sky-400/40',    'bg' => 'assets/banner/universes/daywatch.jpg'],
            ];

            $styleFor = function ($name) use ($categoryStyles) {
                $n = strtolower($name);
                foreach ($categoryStyles as $kw => $style) {
                    if (str_contains($n, $kw)) return $style;
                }
                return ['icon' => 'tag', 'gradient' => 'from-slate-600 via-slate-700 to-slate-800', 'glow' => 'bg-slate-400/40', 'bg' => 'assets/banner/universes/default.jpg'];
            };
        @endphp

        <section class="mb-16">
            <div class="flex items-end justify-between mb-6">
                <div>
                    <h2 class="font-display text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">Explorez par univers</h2>
                    <p class="text-sm text-slate-500 mt-1">Six univers, plus de 300 marques.</p>
                </div>
                <a href="{{ route('boutique') }}" class="hidden sm:inline-flex items-center gap-1 text-sm font-semibold text-[#44A08D] hover:underline">
                    Tout voir
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>

            {{-- Bento grid : 1 anchor "Tout" (col-span-2 sur lg) + 6 categories --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4 auto-rows-[140px] md:auto-rows-[160px]">

                {{-- Anchor "Tout" : col-span-2 sur lg, fond noir charter + photo cartes flottantes --}}
                <a href="{{ route('boutique') }}"
                   class="group relative col-span-2 sm:col-span-3 lg:col-span-2 lg:row-span-2 lg:auto-rows-auto overflow-hidden rounded-3xl bg-gradient-to-br from-[#1F2937] via-[#0F172A] to-[#1F2937] p-6 md:p-8 transition-all duration-300 hover:scale-[1.01] active:scale-[0.99] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#4ECDC4]">

                    {{-- Photo de fond : cartes flottantes — size:contain (style inline
                         pour ne pas dépendre du build Tailwind) = image entière 16:9
                         proportionnelle au cadre, sans zoom/recadrage --}}
                    <div class="absolute inset-0 transition-transform duration-700 group-hover:scale-105"
                         style="background-image: url('{{ asset('assets/banner/universes/default.jpg') }}'); background-size: contain; background-repeat: no-repeat; background-position: center;"></div>

                    {{-- Voile noir léger uniquement à gauche (où se trouve le texte) — l'image reste majoritairement visible --}}
                    <div class="absolute inset-y-0 left-0 w-2/3 bg-gradient-to-r from-[#0F172A]/75 via-[#0F172A]/30 to-transparent pointer-events-none"></div>

                    {{-- Glow background (mix-blend-screen → s'ajoute à l'image au lieu de la masquer) --}}
                    <div class="absolute -top-16 -right-16 w-64 h-64 bg-[#44A08D]/30 rounded-full blur-3xl group-hover:bg-[#44A08D]/40 transition-colors mix-blend-screen pointer-events-none"></div>
                    <div class="absolute -bottom-12 -left-12 w-48 h-48 bg-[#4ECDC4]/20 rounded-full blur-3xl mix-blend-screen pointer-events-none"></div>

                    {{-- Pattern de cercles (style carte) --}}
                    <svg class="absolute inset-0 w-full h-full opacity-[0.04]" aria-hidden="true">
                        <defs>
                            <pattern id="dots-anchor" width="32" height="32" patternUnits="userSpaceOnUse">
                                <circle cx="2" cy="2" r="1.5" fill="white"/>
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#dots-anchor)"/>
                    </svg>

                    <div class="relative h-full flex flex-col justify-between">
                        <div class="flex items-start justify-between">
                            <span class="inline-block px-2.5 py-1 rounded-full bg-white/10 border border-white/20 text-[10px] font-bold tracking-wider uppercase text-[#4ECDC4]">300+ marques</span>
                            <div class="w-12 h-12 rounded-xl bg-white/10 backdrop-blur-sm border border-white/10 flex items-center justify-center text-white group-hover:rotate-12 transition-transform duration-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            </div>
                        </div>

                        <div>
                            <h3 class="font-display text-2xl md:text-3xl font-bold text-white tracking-tight leading-tight">
                                Voir tout<br><span class="text-[#4ECDC4]">le catalogue</span>
                            </h3>
                            <div class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-white/80 group-hover:text-white transition-colors">
                                <span>Explorer</span>
                                <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </div>
                        </div>
                    </div>
                </a>

                {{-- Categories : photo de fond + tint léger de la couleur signature --}}
                @if(isset($categories) && count($categories) > 0)
                    @foreach($categories as $category)
                        @php $style = $styleFor($category['name']); @endphp
                        <a href="{{ route('category', $category['id']) }}"
                           class="group relative overflow-hidden rounded-2xl bg-gradient-to-br {{ $style['gradient'] }} p-4 md:p-5 transition-all duration-300 hover:scale-[1.02] hover:shadow-xl active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-white/50">

                            {{-- Photo de fond : size:contain (style inline) = image entière
                                 proportionnelle au cadre (pas de zoom) ; le gradient
                                 thématique reste visible derrière --}}
                            <div class="absolute inset-0 transition-transform duration-700 group-hover:scale-110"
                                 style="background-image: url('{{ asset($style['bg']) }}'); background-size: contain; background-repeat: no-repeat; background-position: center;"></div>

                            {{-- Voile sombre concentré sur le bas (juste pour rendre le label lisible) --}}
                            <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-black/80 via-black/30 to-transparent pointer-events-none"></div>

                            {{-- Glow accent — mix-blend-screen pour s'intégrer sans masquer l'image --}}
                            <div class="absolute -top-8 -right-8 w-32 h-32 {{ $style['glow'] }} rounded-full blur-2xl group-hover:scale-110 transition-transform duration-500 mix-blend-screen pointer-events-none"></div>

                            <div class="relative h-full flex flex-col justify-between">
                                <div class="w-10 h-10 rounded-xl bg-white/15 backdrop-blur-sm border border-white/20 flex items-center justify-center text-white group-hover:bg-white/25 group-hover:rotate-6 transition-all duration-300">
                                    @switch($style['icon'])
                                        @case('film')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 4h16a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1z"/></svg>
                                            @break
                                        @case('gamepad')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @break
                                        @case('music')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z"/></svg>
                                            @break
                                        @case('shopping-bag')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                            @break
                                        @case('plane')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l3-3 4 4 8-8 3 3-11 11-4-4-3-3z"/></svg>
                                            @break
                                        @case('tv')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/></svg>
                                            @break
                                        @default
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                    @endswitch
                                </div>

                                <div>
                                    <div class="font-display text-base md:text-lg font-bold text-white leading-tight tracking-tight">
                                        {{ $category['name'] }}
                                    </div>
                                    <div class="mt-1 inline-flex items-center gap-1 text-[11px] font-semibold text-white/70 group-hover:text-white transition">
                                        <span>Explorer</span>
                                        <svg class="w-3 h-3 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                @endif
            </div>
        </section>

        {{-- ================================================================
             POPULAR PRODUCTS — frame de carte conserve via <x-product-card>
             ================================================================ --}}
        <section class="mb-16">
            <div class="flex items-end justify-between mb-6">
                <div>
                    <h2 class="font-display text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">Populaires</h2>
                    <p class="text-sm text-slate-500 mt-1">Les cartes que tout le monde s'arrache.</p>
                </div>
                <a href="{{ route('boutique') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#44A08D] hover:underline">
                    Tout voir
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                @if($hasRealData)
                    @foreach($cardTypes as $i => $cardType)
                        @php
                            $minPrice = $cardType['products'][0]['price']['min'] ?? 0;
                            $currency = $cardType['products'][0]['price']['currencyCode'] ?? 'XAF';
                            $logoUrl  = $sanitizeLogo($cardType['logoUrl'] ?? null);
                            $color    = $brandColor($cardType['name'] ?? '');
                            $badge    = $i < 3 ? ['label' => 'POPULAIRE', 'tone' => 'warm'] : null;
                        @endphp
                        <x-product-card
                            :name="$cardType['name'] ?? 'Carte cadeau'"
                            :brand-color="$color"
                            :logo-url="$logoUrl"
                            :price="$minPrice"
                            :currency="$currency"
                            :href="route('card-type.show', $cardType['internalId'] ?? $cardType['id'] ?? '')"
                            :badge="$badge"
                            :products-count="count($cardType['products'] ?? [])"
                            :country-code="$cardType['countryCode'] ?? null"
                        />
                    @endforeach
                @else
                    {{-- Fallback demo : 10 marques iconiques quand l'API est vide --}}
                    @foreach($demoBrands as $i => $demo)
                        <x-product-card
                            :name="$demo['name']"
                            :brand-label="$demo['brand']"
                            :brand-color="$demo['color']"
                            :price="$demo['price']"
                            :currency="$demo['currency']"
                            :href="route('boutique')"
                            :badge="$i < 3 ? ['label' => 'POPULAIRE', 'tone' => 'warm'] : null"
                        />
                    @endforeach
                @endif
            </div>
        </section>

        {{-- ================================================================
             COMMENT CA MARCHE — 3 etapes
             ================================================================ --}}
        <section id="how-it-works" class="mb-16 scroll-mt-24">
            <div class="text-center mb-10">
                <span class="inline-block px-3 py-1 rounded-full bg-teal-50 text-[#44A08D] text-xs font-bold tracking-wider uppercase mb-3 border border-teal-100">Simple & rapide</span>
                <h2 class="font-display text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">Comment ça marche</h2>
                <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto">Trois étapes pour obtenir votre carte cadeau et l'utiliser tout de suite.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                @foreach ([
                    [
                        'step'    => '01',
                        'title'   => 'Choisissez',
                        'desc'    => 'Parcourez le catalogue : Netflix, Steam, Apple, plus de 300 marques disponibles.',
                        'icon'    => 'grid',
                    ],
                    [
                        'step'    => '02',
                        'title'   => 'Payez',
                        'desc'    => 'Mobile Money (Airtel, Moov) ou carte bancaire. Paiement 100% sécurisé via E-Billing.',
                        'icon'    => 'wallet',
                    ],
                    [
                        'step'    => '03',
                        'title'   => 'Recevez',
                        'desc'    => 'Le code de votre carte arrive instantanément dans votre espace personnel. Prêt à utiliser.',
                        'icon'    => 'bolt',
                    ],
                ] as $i => $step)
                    <div class="relative bg-white rounded-2xl p-6 border border-slate-100 shadow-card hover:shadow-card-hover transition-shadow group">
                        {{-- Numero d'etape en filigrane --}}
                        <span class="absolute top-4 right-5 font-display text-5xl font-bold text-slate-100 group-hover:text-teal-50 transition-colors select-none">{{ $step['step'] }}</span>

                        <div class="relative">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#4ECDC4] to-[#44A08D] flex items-center justify-center text-white shadow-lg shadow-[#44A08D]/20 mb-4">
                                @if($step['icon'] === 'grid')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                                @elseif($step['icon'] === 'wallet')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                @endif
                            </div>

                            <h3 class="font-display text-xl font-bold text-slate-900 mb-2">{{ $step['title'] }}</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">{{ $step['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ================================================================
             PROMO BANNER
             ================================================================ --}}
        <section class="mb-16">
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#1F2937] via-[#1F2937] to-[#0F172A] p-8 md:p-12 shadow-pop">
                {{-- Glows --}}
                <div class="absolute -top-20 -right-20 w-80 h-80 bg-[#44A08D]/30 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-[#4ECDC4]/20 rounded-full blur-3xl"></div>

                <div class="relative grid md:grid-cols-2 gap-8 items-center">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#44A08D]/20 border border-[#44A08D]/30 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-[#4ECDC4]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                            <span class="text-[#4ECDC4] font-bold text-[10px] tracking-wider uppercase">Offre Limitée</span>
                        </div>

                        <h3 class="font-display text-white font-bold text-3xl md:text-4xl leading-tight tracking-tight">
                            Gagnez <span class="text-[#4ECDC4]">5% cashback</span><br>
                            sur votre première commande.
                        </h3>
                        <p class="text-slate-400 text-sm mt-3 max-w-md">
                            Profitez de cette offre dès maintenant. Le cashback est crédité automatiquement après votre achat.
                        </p>

                        <a href="{{ route('boutique') }}" class="mt-6 inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-[#44A08D] hover:bg-[#3d9180] text-white font-semibold shadow-lg shadow-[#44A08D]/40 transition-all duration-200 active:scale-95">
                            <span>En profiter</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    </div>

                    {{-- Visual : 3 cartes empilees miniatures --}}
                    <div class="hidden md:flex justify-center items-center">
                        <div class="relative w-72 h-44">
                            <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-[#4ECDC4] to-[#44A08D] rotate-[-8deg] -translate-x-6 shadow-xl"></div>
                            <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-[#7C3AED] to-[#0EA5E9] rotate-[4deg] translate-x-6 translate-y-2 shadow-xl"></div>
                            <div class="absolute inset-0 rounded-2xl bg-white shadow-2xl flex flex-col justify-between p-5">
                                <div class="flex items-center justify-between">
                                    <span class="font-display text-slate-900 text-lg font-bold">KardAfrica</span>
                                    <div class="w-8 h-6 rounded bg-gradient-to-br from-yellow-200 to-yellow-400"></div>
                                </div>
                                <div>
                                    <div class="text-xs text-slate-400 mb-1">CASHBACK</div>
                                    <div class="font-display text-3xl font-black text-[#44A08D]">5%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ================================================================
             TRUST STRIP — fournisseurs / marques connues
             ================================================================ --}}
        <section class="mb-12">
            <p class="text-center text-xs font-bold tracking-[0.2em] uppercase text-slate-400 mb-6">
                Plus de 300 marques de confiance
            </p>
            <div class="flex flex-wrap items-center justify-center gap-x-8 gap-y-4 opacity-70 grayscale hover:grayscale-0 transition duration-500">
                @foreach (['Netflix', 'Apple', 'Spotify', 'Steam', 'Nintendo', 'PlayStation', 'Xbox', 'Amazon'] as $brand)
                    <div class="text-slate-500 font-display font-bold text-lg md:text-xl">{{ $brand }}</div>
                @endforeach
            </div>
        </section>

    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof updatePrices === 'function') updatePrices();
        else if (typeof window.updatePrices === 'function') window.updatePrices();
    });
</script>
@endpush

@endsection
