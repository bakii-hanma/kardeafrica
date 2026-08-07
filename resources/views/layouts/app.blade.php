<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('partials._meta-pixel')

    <title>@yield('title', 'Kardafrica - Cartes numériques en un clic !')</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}">
    
    {{-- Preconnect au host des images de marques (S3) — accélère le chargement
         des visuels de cartes (handshake DNS/TLS anticipé). --}}
    <link rel="preconnect" href="https://bamboo-assets.s3.amazonaws.com" crossorigin>
    <link rel="dns-prefetch" href="https://bamboo-assets.s3.amazonaws.com">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900|space-grotesk:500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    
    <script>
        window.currentUser = @json(auth()->user() ? [
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'phone' => auth()->user()->phone ?? '00000000'
        ] : null);
    </script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/kardafrica-inline.css') }}?v=1">
</head>
<body x-data class="bg-gray-50 font-sans antialiased">
    <!-- Page Loader -->
    <x-loader />

    <!-- Mobile Header (Visible only on mobile/tablet) -->
    @unless(View::hasSection('hide_mobile_header'))
    <div class="md:hidden bg-[#1F2937] px-4 pt-3 pb-4 shadow-lg fixed top-0 w-full z-50 border-b border-white/5">
        <div class="flex justify-between items-center mb-3">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" class="w-9 h-9 rounded-lg bg-white/10 p-1 object-contain" alt="Logo">
                <span class="font-display text-xl font-bold text-white tracking-tight">KardAfrica</span>
            </a>
            <div class="flex items-center gap-2">
                <button id="cartBtnMobile" class="relative w-10 h-10 rounded-xl flex items-center justify-center text-white hover:bg-white/10 active:scale-95 transition" aria-label="Panier">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <span id="cartCountMobileHeader" class="absolute top-1 right-1 min-w-[16px] h-4 px-1 rounded-full bg-[#44A08D] text-white text-[9px] font-bold flex items-center justify-center ring-2 ring-[#1F2937]">0</span>
                </button>
                <button id="mobileMenuBtn" class="w-10 h-10 rounded-xl flex items-center justify-center text-white hover:bg-white/10 active:scale-95 transition" aria-label="Menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>

        <button type="button" data-search-trigger
                class="relative w-full flex items-center bg-white/[0.06] rounded-xl pl-3 pr-2 py-2 border border-white/10 hover:border-[#4ECDC4]/40 hover:bg-white/[0.08] active:scale-[0.99] transition text-left">
            <svg class="w-4 h-4 text-slate-400 mr-2 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <span class="text-slate-400 text-sm">Rechercher Netflix, Apple…</span>
        </button>
    </div>
    @endunless
    
    <!-- Desktop Header Wrapper (Hidden on mobile) -->
    <div class="hidden md:block">
        <!-- Top Bar (slim) -->
        <div class="top-bar text-slate-300 fixed top-0 w-full z-50 h-[40px] border-b border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full">
            <div class="flex justify-between items-center text-xs h-full">
                <div class="hidden md:flex items-center gap-5">
                    <a href="mailto:hello@kardafrica.com" class="flex items-center gap-1.5 hover:text-[#4ECDC4] transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span>hello@kardafrica.com</span>
                    </a>
                    <span class="text-white/10">·</span>
                    <a href="tel:+241XXXXXXXX" class="flex items-center gap-1.5 hover:text-[#4ECDC4] transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span>+241 06 87 13 09</span>
                    </a>
                    <span class="text-white/10">·</span>
                    <span class="flex items-center gap-1.5">
                        <span class="relative flex h-1.5 w-1.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                        </span>
                        Service client 24/7
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex items-center gap-4 text-slate-400">
                        <a href="{{ route('about') }}" class="hover:text-[#4ECDC4] transition">À propos</a>
                        <a href="{{ route('contact') }}" class="hover:text-[#4ECDC4] transition">Contact</a>
                        <a href="{{ route('support') }}" class="hover:text-[#4ECDC4] transition">Support</a>
                    </div>
                    
                    <!-- Réseaux sociaux -->
                    <div class="flex items-center space-x-2 ml-4 pl-4 border-l border-gray-600">
                        <a href="#" class="social-link" title="Facebook">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        <a href="#" class="social-link" title="Twitter">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </a>
                        <a href="#" class="social-link" title="Instagram">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                            </svg>
                        </a>
                        <a href="#" class="social-link" title="LinkedIn">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="bg-[#1F2937]/95 backdrop-blur-md border-b border-white/5 fixed top-[40px] w-full z-40 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
                    <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}"
                         alt="Kardafrica"
                         class="h-9 w-9 transition-transform duration-300 group-hover:scale-105">
                    <span class="font-display text-xl font-bold text-white tracking-tight">KardAfrica</span>
                </a>

                <!-- Menu de navigation -->
                <div class="hidden md:flex items-center gap-1">
                    <a href="{{ route('home') }}"
                       class="px-3.5 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('home') ? 'text-white bg-white/[0.08]' : 'text-slate-300 hover:text-white hover:bg-white/[0.06]' }}">
                        Accueil
                    </a>

                    <!-- Dropdown Catalogue -->
                    <div class="relative group">
                        <button class="flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-white/[0.06] transition">
                            <span>Catalogue</span>
                            <svg class="w-3.5 h-3.5 transition-transform duration-300 group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div class="absolute left-1/2 -translate-x-1/2 top-full pt-3 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 transform translate-y-2 group-hover:translate-y-0 z-50">
                            <div class="w-[420px] bg-white rounded-2xl shadow-pop border border-slate-100 overflow-hidden">
                                <div class="grid grid-cols-2 p-2">
                                    @php
                                        $catalogLinks = [
                                            ['title' => 'Gaming',     'desc' => 'PlayStation, Xbox, Steam',  'icon' => 'gamepad',      'cat' => 2],
                                            ['title' => 'Streaming',  'desc' => 'Netflix, Spotify, Disney+', 'icon' => 'film',         'cat' => 1],
                                            ['title' => 'Musique',    'desc' => 'Apple Music, Deezer',       'icon' => 'music',        'cat' => 3],
                                            ['title' => 'Shopping',   'desc' => 'Amazon, Nike, Zalando',     'icon' => 'shopping-bag', 'cat' => 4],
                                            ['title' => 'Daywatch',   'desc' => 'Streaming local',           'icon' => 'tv',           'cat' => 5],
                                            ['title' => 'Voyage',     'desc' => 'Uber, Airbnb, Booking',     'icon' => 'plane',        'cat' => 6],
                                        ];
                                    @endphp

                                    @foreach($catalogLinks as $link)
                                        <a href="{{ route('category', $link['cat']) }}"
                                           class="group/item flex items-start gap-3 p-3 rounded-xl hover:bg-slate-50 transition">
                                            <div class="shrink-0 w-10 h-10 rounded-lg bg-teal-50 group-hover/item:bg-teal-100 flex items-center justify-center text-[#44A08D] transition">
                                                @switch($link['icon'])
                                                    @case('gamepad')
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        @break
                                                    @case('film')
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 4h16a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1z"/></svg>
                                                        @break
                                                    @case('music')
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z"/></svg>
                                                        @break
                                                    @case('shopping-bag')
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                                        @break
                                                    @case('tv')
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/></svg>
                                                        @break
                                                    @case('plane')
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l3-3 4 4 8-8 3 3-11 11-4-4-3-3z"/></svg>
                                                        @break
                                                @endswitch
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-semibold text-slate-900 text-sm group-hover/item:text-[#44A08D] transition-colors">{{ $link['title'] }}</div>
                                                <div class="text-xs text-slate-500 truncate">{{ $link['desc'] }}</div>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                                <a href="{{ route('boutique') }}" class="flex items-center justify-between px-4 py-3 bg-slate-50 border-t border-slate-100 text-sm font-semibold text-[#44A08D] hover:bg-slate-100 transition group/all">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                                        Voir tout le catalogue
                                    </span>
                                    <svg class="w-4 h-4 transition-transform group-hover/all:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>

                    <a href="{{ url('/#how-it-works') }}"
                       class="px-3.5 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-white/[0.06] transition">
                        Comment ça marche
                    </a>

                    <a href="{{ route('gabon.index') }}"
                       class="px-3.5 py-2 rounded-lg text-sm font-bold text-white bg-gradient-to-r from-[#44A08D] to-[#4ECDC4] hover:from-[#0F4F44] hover:to-[#44A08D] transition flex items-center gap-1.5 shadow-lg shadow-teal-500/20">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        Carte Gabon
                    </a>
                </div>

                <!-- Controles a droite : recherche, panier, user -->
                <div class="hidden md:flex items-center gap-2">
                    <!-- Télécharger l'app : icône seule (même style que loupe / panier) -->
                    <a href="{{ route('download') }}" aria-label="Télécharger l'application Android" title="Télécharger l'app"
                       class="w-10 h-10 rounded-xl flex items-center justify-center active:scale-95 transition {{ request()->routeIs('download') ? 'text-white bg-white/[0.08]' : 'text-slate-300 hover:text-white hover:bg-white/[0.06]' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                    </a>

                    <!-- Search trigger (desktop) — icone seule, Ctrl+K disponible -->
                    <button type="button" data-search-trigger
                            class="w-10 h-10 rounded-xl flex items-center justify-center text-slate-300 hover:text-white hover:bg-white/[0.06] active:scale-95 transition"
                            aria-label="Rechercher (Ctrl+K)" title="Rechercher (Ctrl+K)">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>

                    <!-- Panier -->
                    <div class="relative" x-data="{ open: false }" @open-cart-dropdown.window="open = true" @toggle-cart-dropdown.window="open = !open" @click.away="open = false">
                        <button id="cartBtn" class="relative w-10 h-10 rounded-xl flex items-center justify-center text-slate-300 hover:text-white hover:bg-white/[0.06] active:scale-95 transition" aria-label="Panier">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            <span id="cartCount" class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-[#44A08D] text-white text-[10px] font-bold flex items-center justify-center ring-2 ring-[#1F2937]">0</span>
                        </button>
                        
                        <!-- Dropdown du panier -->
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 translate-y-2"
                             id="cartDropdown" 
                             class="absolute right-0 mt-4 w-96 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden z-50"
                             style="display: none;">
                            
                            <!-- Header -->
                            <div class="px-6 py-4 border-b border-gray-100 bg-white flex justify-between items-center">
                                <h3 class="font-bold text-gray-900 text-lg flex items-center gap-2">
                                    <span class="text-kardafrica-primary">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                        </svg>
                                    </span>
                                    Mon Panier
                                </h3>
                                <span class="text-xs font-medium px-2.5 py-1 bg-gray-100 text-gray-600 rounded-full" id="cartCountBadge">0 articles</span>
                            </div>

                            <!-- Items -->
                            <div class="max-h-[24rem] overflow-y-auto custom-scrollbar" id="cartItems">
                                <div class="text-center py-12 px-6">
                                    <div class="w-20 h-20 mx-auto mb-4 bg-gray-50 rounded-full flex items-center justify-center border border-gray-100">
                                        <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                        </svg>
                                    </div>
                                    <p class="text-gray-900 font-bold mb-1">Votre panier est vide</p>
                                    <p class="text-sm text-gray-500 mb-6">Découvrez nos cartes numériques et commencez vos achats.</p>
                                    <a href="{{ route('boutique') }}" class="inline-flex items-center justify-center px-6 py-2.5 border border-transparent text-sm font-medium rounded-xl text-white bg-kardafrica-primary hover:bg-kardafrica-secondary transition-colors shadow-lg shadow-kardafrica-primary/20">
                                        Découvrir la boutique
                                    </a>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="p-6 bg-gray-50 border-t border-gray-100">
                                <div class="flex justify-between items-end mb-6">
                                    <span class="text-gray-500 font-medium">Total à payer</span>
                                    <div class="text-right">
                                        <span id="cartTotal" class="block text-2xl font-bold text-gray-900">0 FCFA</span>
                                        <span class="text-xs text-green-600 font-medium">Frais de service offerts</span>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-3">
                                    <a href="{{ route('cart.index') }}" class="flex items-center justify-center px-4 py-3 border border-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-white hover:border-gray-300 hover:shadow-sm transition-all duration-200">
                                        Voir le panier
                                    </a>
                                    <a href="{{ route('cart.index') }}" class="flex items-center justify-center px-4 py-3 bg-[#1F2937] text-white font-semibold rounded-xl hover:bg-[#374151] hover:shadow-lg transition-all duration-200">
                                        Commander
                                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    @auth
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.away="open = false"
                                class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-xl bg-white/[0.06] border border-white/10 hover:bg-white/[0.10] transition">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#4ECDC4] to-[#44A08D] flex items-center justify-center overflow-hidden">
                                @if(optional(Auth::user()->profile)->avatar)
                                    <img src="{{ Auth::user()->profile->avatar }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover">
                                @else
                                    <span class="text-white font-bold text-xs">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                                @endif
                            </div>
                            <span class="font-medium text-sm text-white">{{ explode(' ', Auth::user()->name)[0] }}</span>
                            <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 translate-y-2"
                             class="absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-pop border border-slate-100 overflow-hidden z-50"
                             style="display: none;">
                            <div class="px-4 py-3 border-b border-slate-100">
                                <p class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Connecté</p>
                                <p class="text-sm font-semibold text-slate-900 truncate mt-0.5">{{ Auth::user()->email }}</p>
                            </div>
                            <div class="p-2">
                                <a href="{{ route('profile.show') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-700 hover:bg-slate-50 hover:text-[#44A08D] transition group/item">
                                    <div class="w-8 h-8 rounded-lg bg-slate-100 group-hover/item:bg-teal-50 flex items-center justify-center text-slate-600 group-hover/item:text-[#44A08D] transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </div>
                                    <span class="font-medium text-sm">Mon profil</span>
                                </a>
                                <a href="{{ route('cards.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-700 hover:bg-slate-50 hover:text-[#44A08D] transition group/item">
                                    <div class="w-8 h-8 rounded-lg bg-slate-100 group-hover/item:bg-teal-50 flex items-center justify-center text-slate-600 group-hover/item:text-[#44A08D] transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    </div>
                                    <span class="font-medium text-sm">Mes cartes</span>
                                </a>
                                <a href="{{ url('/orders') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-700 hover:bg-slate-50 hover:text-[#44A08D] transition group/item">
                                    <div class="w-8 h-8 rounded-lg bg-slate-100 group-hover/item:bg-teal-50 flex items-center justify-center text-slate-600 group-hover/item:text-[#44A08D] transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    </div>
                                    <span class="font-medium text-sm">Mes commandes</span>
                                </a>
                            </div>
                            <form method="POST" action="{{ route('logout') }}" class="p-2 border-t border-slate-100">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-rose-600 hover:bg-rose-50 transition text-left group/item">
                                    <div class="w-8 h-8 rounded-lg bg-rose-50 group-hover/item:bg-rose-100 flex items-center justify-center transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    </div>
                                    <span class="font-medium text-sm">Déconnexion</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    @else
                    <button @click="$dispatch('open-auth-modal'); $dispatch('set-auth-view', { view: 'login' })"
                            class="px-4 py-2 rounded-xl bg-[#44A08D] hover:bg-[#3d9180] text-white text-sm font-semibold shadow-lg shadow-[#44A08D]/25 active:scale-95 transition">
                        Connexion
                    </button>
                    @endauth
                </div>

            </div>
        </div>
    </nav>
    </div>

    <!-- Contenu principal -->
    <main class="min-h-screen pt-[120px] md:pt-[104px]">

        @if(session('success') || session('error') || session('info'))
            @php
                $msgType = session('success') ? 'success' : (session('error') ? 'error' : 'info');
                $msg = session($msgType);
            @endphp
            <x-flash-modal :type="$msgType" :message="$msg" />
        @endif

        @yield('content')
    </main>



    <!-- Chatbot — Assistant Kardafrica (Kara) -->
    {{-- Backdrop mobile (sibling, pas enfant — évite les soucis de stacking) --}}
    <div id="kaBotBackdrop" class="ka-bot-backdrop" aria-hidden="true"></div>

    <div class="ka-bot-container" id="kaBot">
        {{-- Window --}}
        <div id="chatbotWindow" class="ka-bot-window" role="dialog" aria-label="Assistant Kardafrica">

            {{-- Header --}}
            <div class="ka-bot-header">
                <div style="position:relative;display:flex;align-items:center;gap:12px;">
                    <div class="ka-bot-avatar">
                        <svg style="width:24px;height:24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/></svg>
                        <span class="ka-bot-avatar-dot"></span>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:16px;font-weight:700;line-height:1.1;">Kara</div>
                        <div style="font-size:11px;opacity:.85;display:flex;align-items:center;gap:5px;margin-top:2px;">
                            <span style="width:5px;height:5px;border-radius:50%;background:#34D399;"></span>
                            En ligne · Réponse instantanée
                        </div>
                    </div>
                    <button id="closeChatbot" type="button"
                            style="width:32px;height:32px;border:0;border-radius:9px;background:rgba(255,255,255,0.15);color:white;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s;"
                            onmouseover="this.style.background='rgba(255,255,255,0.25)';"
                            onmouseout="this.style.background='rgba(255,255,255,0.15)';"
                            aria-label="Fermer">
                        <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Messages --}}
            <div class="ka-bot-messages" id="chatMessages"></div>

            {{-- Suggestion chips --}}
            <div class="ka-bot-suggestions" id="kaBotChips"></div>

            {{-- Composer --}}
            <div class="ka-bot-composer">
                <input type="text" id="chatInput" class="ka-bot-input" placeholder="Posez votre question…" autocomplete="off">
                <button id="sendMessage" type="button" class="ka-bot-send" aria-label="Envoyer">
                    <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                </button>
            </div>
        </div>

        {{-- Toggle launcher avec étiquette "Kara" affichée à côté --}}
        <div class="ka-bot-launcher">
            {{-- Étiquette "Kara — Assistante IA" — se cache après la 1ère ouverture --}}
            <div class="ka-bot-launcher-label" id="kaBotLabel">
                <span class="ka-bot-launcher-label-name">Kara</span>
                <span class="ka-bot-launcher-label-sub">Assistante IA · En ligne</span>
            </div>

            <button id="chatbotToggle" type="button" class="ka-bot-toggle" aria-label="Ouvrir l'assistante Kara">
                <svg style="width:26px;height:26px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/></svg>
                <span class="ka-bot-badge" id="kaBotBadge" style="display:none;">1</span>
            </button>
        </div>
    </div>

    <!-- Overlay pour la sidebar mobile -->
    <div id="mobileMenuOverlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[998] opacity-0 invisible transition-all duration-300 md:hidden" style="display:none;"></div>

    <!-- Overlay pour la sidebar panier mobile -->
    <div id="mobileCartOverlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[998] opacity-0 invisible transition-all duration-300 md:hidden" style="display:none;"></div>

    <!-- Mobile Sidebar -->
    <div id="mobileMenu" class="fixed top-0 right-0 h-full w-[85%] max-w-[360px] bg-white shadow-2xl z-[999] transform translate-x-full transition-transform duration-300 ease-out md:hidden border-l border-slate-200">
        <!-- Sidebar Header -->
        <div class="sidebar-header bg-gradient-to-br from-[#1F2937] to-[#0F172A] px-5 py-5 text-white relative overflow-hidden">
            <div class="absolute -top-12 -right-12 w-40 h-40 bg-[#44A08D]/20 rounded-full blur-3xl"></div>
            <div class="relative flex items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" alt="Logo" class="w-9 h-9">
                    <span class="font-display text-xl font-bold tracking-tight">KardAfrica</span>
                </a>
                <button id="closeMobileMenu" class="w-9 h-9 rounded-xl flex items-center justify-center hover:bg-white/10 active:scale-95 transition" aria-label="Fermer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <!-- Sidebar Content -->
        <div class="sidebar-content flex-1 overflow-y-auto px-3 py-4 h-[calc(100vh-180px)] bg-white">

            {{-- Nav links --}}
            <nav class="space-y-1">
                <a href="{{ route('home') }}" class="sidebar-item flex items-center gap-3 px-3 py-3 rounded-xl text-slate-700 hover:bg-slate-50 hover:text-[#44A08D] transition group">
                    <div class="w-9 h-9 rounded-lg bg-slate-100 group-hover:bg-teal-50 flex items-center justify-center text-slate-600 group-hover:text-[#44A08D] transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    </div>
                    <span class="font-medium text-sm">Accueil</span>
                </a>

                <a href="{{ route('download') }}" class="sidebar-item flex items-center gap-3 px-3 py-3 rounded-xl text-[#0f766e] bg-teal-50/60 hover:bg-teal-50 transition group">
                    <div class="w-9 h-9 rounded-lg bg-teal-100 flex items-center justify-center text-[#0f766e] transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                    </div>
                    <span class="font-semibold text-sm">Télécharger l'app</span>
                </a>

                <a href="{{ route('boutique') }}" class="sidebar-item flex items-center gap-3 px-3 py-3 rounded-xl text-slate-700 hover:bg-slate-50 hover:text-[#44A08D] transition group">
                    <div class="w-9 h-9 rounded-lg bg-slate-100 group-hover:bg-teal-50 flex items-center justify-center text-slate-600 group-hover:text-[#44A08D] transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    </div>
                    <span class="font-medium text-sm">Boutique</span>
                </a>

                {{-- Menu cartes avec dropdown --}}
                <div class="sidebar-item">
                    <button id="mobileCardsBtn" class="w-full flex items-center justify-between gap-3 px-3 py-3 rounded-xl text-slate-700 hover:bg-slate-50 transition group">
                        <span class="flex items-center gap-3">
                            <span class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            </span>
                            <span class="font-medium text-sm">Catalogue</span>
                        </span>
                        <svg id="mobileCardsIcon" class="w-4 h-4 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="mobileCardsSubmenu" class="ml-12 mt-1 space-y-0.5 max-h-0 overflow-hidden transition-all duration-300">
                        @foreach ([
                            ['Streaming',  'film',         1],
                            ['Gaming',     'gamepad',      2],
                            ['Musique',    'music',        3],
                            ['Shopping',   'shopping-bag', 4],
                            ['Daywatch',   'tv',           5],
                            ['Voyage',     'plane',        6],
                        ] as [$label, $icon, $catId])
                            <a href="{{ route('category', $catId) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-[#44A08D] text-sm transition">
                                <span class="text-[#44A08D]">
                                    @switch($icon)
                                        @case('film')         <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 4h16a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1z"/></svg>@break
                                        @case('gamepad')      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>@break
                                        @case('music')        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z"/></svg>@break
                                        @case('shopping-bag') <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>@break
                                        @case('tv')           <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/></svg>@break
                                        @case('plane')        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l3-3 4 4 8-8 3 3-11 11-4-4-3-3z"/></svg>@break
                                    @endswitch
                                </span>
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <a href="{{ route('gabon.index') }}" class="sidebar-item flex items-center gap-3 px-3 py-3 rounded-xl text-white bg-gradient-to-r from-[#44A08D] to-[#4ECDC4] hover:from-[#0F4F44] hover:to-[#44A08D] transition group shadow-lg shadow-teal-500/20">
                    <div class="w-9 h-9 rounded-lg bg-white/20 flex items-center justify-center text-white transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <span class="font-bold text-sm">Carte Gabon</span>
                </a>

                <a href="{{ url('/#how-it-works') }}" class="sidebar-item flex items-center gap-3 px-3 py-3 rounded-xl text-slate-700 hover:bg-slate-50 hover:text-[#44A08D] transition group">
                    <div class="w-9 h-9 rounded-lg bg-slate-100 group-hover:bg-teal-50 flex items-center justify-center text-slate-600 group-hover:text-[#44A08D] transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="font-medium text-sm">Comment ça marche</span>
                </a>

                <a href="{{ route('support') }}" class="sidebar-item flex items-center gap-3 px-3 py-3 rounded-xl text-slate-700 hover:bg-slate-50 hover:text-[#44A08D] transition group">
                    <div class="w-9 h-9 rounded-lg bg-slate-100 group-hover:bg-teal-50 flex items-center justify-center text-slate-600 group-hover:text-[#44A08D] transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414"/></svg>
                    </div>
                    <span class="font-medium text-sm">Support 24/7</span>
                </a>

                <a href="{{ route('about') }}" class="sidebar-item flex items-center gap-3 px-3 py-3 rounded-xl text-slate-700 hover:bg-slate-50 hover:text-[#44A08D] transition group">
                    <div class="w-9 h-9 rounded-lg bg-slate-100 group-hover:bg-teal-50 flex items-center justify-center text-slate-600 group-hover:text-[#44A08D] transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="font-medium text-sm">À propos</span>
                </a>
            </nav>

            {{-- Quick contact --}}
            <div class="mt-6 pt-6 border-t border-slate-100">
                <div class="px-2">
                    <p class="text-[10px] uppercase tracking-wider font-bold text-slate-400 mb-3">Contact rapide</p>
                    <div class="space-y-2">
                        <a href="mailto:hello@kardafrica.com" class="flex items-center gap-2 text-xs text-slate-600 hover:text-[#44A08D] transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            hello@kardafrica.com
                        </a>
                        <a href="tel:+241000000" class="flex items-center gap-2 text-xs text-slate-600 hover:text-[#44A08D] transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            +241 06 87 13 09
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Footer -->
        <div class="border-t border-slate-100 p-3 bg-white">
            @auth
            <div class="space-y-1">
                <div class="px-3 py-2.5 rounded-xl bg-slate-50 mb-2 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-[#4ECDC4] to-[#44A08D] flex items-center justify-center overflow-hidden">
                        @if(optional(Auth::user()->profile)->avatar)
                            <img src="{{ Auth::user()->profile->avatar }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover">
                        @else
                            <span class="text-white font-bold text-xs">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-900 truncate">{{ Auth::user()->name }}</p>
                        <p class="text-[11px] text-slate-500 truncate">{{ Auth::user()->email }}</p>
                    </div>
                </div>

                <a href="{{ route('profile.show') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-700 hover:bg-slate-50 hover:text-[#44A08D] transition group">
                    <div class="w-7 h-7 rounded-md bg-slate-100 group-hover:bg-teal-50 flex items-center justify-center text-slate-600 group-hover:text-[#44A08D] transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <span class="text-sm font-medium">Mon profil</span>
                </a>

                <a href="{{ route('cards.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-700 hover:bg-slate-50 hover:text-[#44A08D] transition group">
                    <div class="w-7 h-7 rounded-md bg-slate-100 group-hover:bg-teal-50 flex items-center justify-center text-slate-600 group-hover:text-[#44A08D] transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <span class="text-sm font-medium">Mes cartes</span>
                </a>

                <a href="{{ url('/orders') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-700 hover:bg-slate-50 hover:text-[#44A08D] transition group">
                    <div class="w-7 h-7 rounded-md bg-slate-100 group-hover:bg-teal-50 flex items-center justify-center text-slate-600 group-hover:text-[#44A08D] transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <span class="text-sm font-medium">Mes commandes</span>
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-rose-600 hover:bg-rose-50 transition group">
                        <div class="w-7 h-7 rounded-md bg-rose-50 group-hover:bg-rose-100 flex items-center justify-center transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </div>
                        <span class="text-sm font-medium">Déconnexion</span>
                    </button>
                </form>
            </div>
            @else
            <button id="mobileAuthBtn" @click="$dispatch('open-auth-modal'); $dispatch('set-auth-view', { view: 'login' })"
                    class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-[#44A08D] hover:bg-[#3d9180] text-white text-sm font-semibold shadow-lg shadow-[#44A08D]/25 active:scale-95 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Connexion
            </button>
            @endauth
        </div>
            </div>
    
    <!-- Mobile Cart Sidebar -->
    <div id="mobileCartSidebar" class="fixed top-0 right-0 h-full w-80 bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out md:hidden border-l border-gray-200" style="background-color: #ffffff !important;">
        <!-- Cart Sidebar Header -->
        <div class="sidebar-header bg-[#1F2937] p-6 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="bg-white/20 p-2 rounded-lg backdrop-blur-sm">
                        <svg class="w-6 h-6" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
        </div>
                    <div>
                        <h2 class="font-bold text-lg">Mon Panier</h2>
                        <p class="text-sm opacity-90">Vos cartes sélectionnées</p>
        </div>
            </div>
                <button id="closeMobileCart" class="p-2 hover:bg-white/20 rounded-lg transition-all duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
            </button>
        </div>
    </div>

        <!-- Cart Sidebar Content -->
        <div class="sidebar-content flex-1 overflow-y-auto px-4 py-6 h-[calc(100vh-220px)]" style="background-color: #ffffff !important;">
            <div id="mobileCartItems">
                <div class="text-center py-8">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                </div>
                    <p class="text-gray-500 font-medium mb-2">Votre panier est vide</p>
                    <p class="text-sm text-gray-400">Découvrez nos cartes numériques</p>
            </div>
                    </div>
                </div>
        
        <!-- Cart Sidebar Footer -->
        <div class="border-t border-gray-200 p-4" style="background-color: #ffffff !important;">
            <div class="flex justify-between items-center mb-4">
                <span class="font-semibold text-gray-900">Total:</span>
                <div class="flex items-center space-x-2">
                    <span id="mobileCartTotal" class="text-2xl font-bold text-kardafrica-primary">0 FCFA</span>
                    <div class="bg-kardafrica-primary/10 p-1 rounded-full">
                        <svg class="w-4 h-4 text-kardafrica-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                </div>
            </div>
        </div>
            
            <!-- Boutons d'action -->
            <div class="space-y-3">
                <a href="{{ route('cart.index') }}" class="w-full border-2 border-gray-300 text-gray-700 py-3 px-4 rounded-xl font-medium hover:bg-gray-50 hover:border-gray-400 transition-all duration-300 flex items-center justify-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <span>Voir détails</span>
                </a>
                <button onclick="startPayment()" class="w-full bg-kardafrica-primary text-white py-3 px-4 rounded-xl font-semibold hover-kardafrica shadow-lg flex items-center justify-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    <span>Commander maintenant</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="relative bg-[#0F172A] text-slate-300 overflow-hidden">
        {{-- Glow ambient en haut --}}
        <div class="pointer-events-none absolute -top-32 left-1/2 -translate-x-1/2 w-[800px] h-[400px] bg-[#44A08D]/10 rounded-full blur-3xl"></div>

        {{-- ===== Newsletter strip ===== --}}
        <div class="relative border-b border-white/5">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="grid md:grid-cols-2 gap-6 items-center">
                    <div>
                        <h3 class="font-display text-2xl md:text-3xl font-bold text-white tracking-tight">
                            Promos & nouvelles cartes
                            <span class="block text-[#4ECDC4]">droit dans ta boîte mail.</span>
                        </h3>
                        <p class="text-sm text-slate-400 mt-2">Une newsletter par semaine. Pas de spam, désinscription en un clic.</p>
                    </div>
                    <div x-data="newsletterForm()">
                        <form @submit.prevent="submit()" data-no-loader class="flex flex-col sm:flex-row gap-2">
                            @csrf
                            <div class="flex-1 flex items-center bg-white/[0.06] rounded-xl border border-white/10 focus-within:border-[#4ECDC4]/50 transition px-3.5"
                                 :class="{ 'border-rose-400/60': error, 'border-emerald-400/60': success }">
                                <svg class="w-4 h-4 text-slate-400 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <input type="email" x-model="email" :disabled="loading || success" required placeholder="ton.email@exemple.com"
                                       class="bg-transparent border-0 text-white placeholder-slate-500 focus:ring-0 w-full text-sm py-3 focus:outline-none disabled:opacity-50">
                            </div>
                            <button type="submit" :disabled="loading || success || !email"
                                    class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-[#44A08D] hover:bg-[#3d9180] text-white text-sm font-semibold shadow-lg shadow-[#44A08D]/30 active:scale-95 transition disabled:opacity-60 disabled:cursor-not-allowed">
                                <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                <svg x-show="success" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span x-show="!loading && !success">S'inscrire</span>
                                <span x-show="loading" x-cloak>Inscription...</span>
                                <span x-show="success" x-cloak>Inscrit !</span>
                            </button>
                        </form>
                        <p x-show="message" x-cloak x-transition.opacity class="mt-2 text-xs"
                           :class="error ? 'text-rose-300' : 'text-emerald-300'"
                           x-text="message"></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Main columns ===== --}}
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-8">

                {{-- Brand col (2 cols sur lg) --}}
                <div class="col-span-2 lg:col-span-2">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5">
                        <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" class="w-10 h-10" alt="KardAfrica">
                        <span class="font-display text-2xl font-bold text-white tracking-tight">KardAfrica</span>
                    </a>
                    <p class="mt-4 text-sm text-slate-400 leading-relaxed max-w-sm">
                        La marketplace n°1 de cartes cadeaux numériques en Afrique. Plus de 300 marques, paiement Mobile Money, livraison instantanée.
                    </p>

                    {{-- Social links --}}
                    <div class="mt-6 flex items-center gap-2">
                        @foreach ([
                            ['name' => 'Facebook',  'href' => '#', 'path' => 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z'],
                            ['name' => 'Twitter',   'href' => '#', 'path' => 'M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z'],
                            ['name' => 'Instagram', 'href' => '#', 'path' => 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z'],
                            ['name' => 'LinkedIn',  'href' => '#', 'path' => 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z'],
                        ] as $social)
                            <a href="{{ $social['href'] }}"
                               aria-label="{{ $social['name'] }}"
                               class="w-9 h-9 rounded-lg bg-white/[0.06] hover:bg-[#44A08D] border border-white/10 hover:border-[#44A08D] flex items-center justify-center text-slate-400 hover:text-white transition active:scale-95">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="{{ $social['path'] }}"/></svg>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Catalogue --}}
                <div>
                    <h4 class="text-white text-sm font-bold uppercase tracking-wider mb-4">Catalogue</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="{{ route('boutique') }}" class="hover:text-[#4ECDC4] transition">Toutes les cartes</a></li>
                        <li><a href="{{ route('category', 1) }}" class="hover:text-[#4ECDC4] transition">Streaming</a></li>
                        <li><a href="{{ route('category', 2) }}" class="hover:text-[#4ECDC4] transition">Gaming</a></li>
                        <li><a href="{{ route('category', 3) }}" class="hover:text-[#4ECDC4] transition">Musique</a></li>
                        <li><a href="{{ route('category', 4) }}" class="hover:text-[#4ECDC4] transition">Shopping</a></li>
                        <li><a href="{{ route('download') }}" class="hover:text-[#4ECDC4] transition inline-flex items-center gap-1.5 font-semibold text-[#4ECDC4]">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                            Télécharger l'app
                        </a></li>
                    </ul>
                </div>

                {{-- Aide --}}
                <div>
                    <h4 class="text-white text-sm font-bold uppercase tracking-wider mb-4">Aide</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="{{ url('/#how-it-works') }}" class="hover:text-[#4ECDC4] transition">Comment ça marche</a></li>
                        <li><a href="{{ route('support') }}" class="hover:text-[#4ECDC4] transition">Support 24/7</a></li>
                        <li><a href="{{ route('contact') }}" class="hover:text-[#4ECDC4] transition">Nous contacter</a></li>
                        <li><a href="#" class="hover:text-[#4ECDC4] transition">FAQ</a></li>
                        <li><a href="#" class="hover:text-[#4ECDC4] transition">Suivi de commande</a></li>
                    </ul>
                </div>

                {{-- Légal --}}
                <div>
                    <h4 class="text-white text-sm font-bold uppercase tracking-wider mb-4">Légal</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="{{ route('about') }}" class="hover:text-[#4ECDC4] transition">À propos</a></li>
                        <li><a href="{{ route('privacy') }}" class="hover:text-[#4ECDC4] transition">Politique de confidentialité</a></li>
                        <li><a href="{{ route('data-deletion') }}" class="hover:text-[#4ECDC4] transition">Suppression des données</a></li>
                    </ul>
                </div>
            </div>

            {{-- Contact strip --}}
            <div class="mt-12 pt-8 border-t border-white/5 grid md:grid-cols-3 gap-6">
                <a href="mailto:hello@kardafrica.com" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-white/[0.06] border border-white/10 flex items-center justify-center text-[#4ECDC4] group-hover:bg-[#44A08D] group-hover:text-white group-hover:border-[#44A08D] transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-slate-500 font-bold">Email</div>
                        <div class="text-sm text-white font-medium">hello@kardafrica.com</div>
                    </div>
                </a>
                <a href="tel:+241000000" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-white/[0.06] border border-white/10 flex items-center justify-center text-[#4ECDC4] group-hover:bg-[#44A08D] group-hover:text-white group-hover:border-[#44A08D] transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-slate-500 font-bold">Téléphone</div>
                        <div class="text-sm text-white font-medium">+241 06 87 13 09</div>
                    </div>
                </a>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/[0.06] border border-white/10 flex items-center justify-center text-[#4ECDC4]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-slate-500 font-bold">Adresse</div>
                        <div class="text-sm text-white font-medium">Libreville, Gabon</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Bottom bar ===== --}}
        <div class="relative border-t border-white/5">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-xs text-slate-500">
                    &copy; {{ date('Y') }} KardAfrica. Tous droits réservés. Conçu avec
                    <span class="text-rose-500">♥</span>
                    en Afrique.
                </p>

                {{-- Moyens de paiement --}}
                <div class="flex items-center gap-3">
                    <span class="text-[10px] uppercase tracking-wider text-slate-500 font-bold mr-2">Paiement</span>
                    @foreach ([
                        ['name' => 'Airtel Money',   'bg' => 'bg-rose-600'],
                        ['name' => 'Moov Money',     'bg' => 'bg-blue-600'],
                        ['name' => 'Visa',           'bg' => 'bg-indigo-700'],
                    ] as $pay)
                        <div class="px-2.5 py-1.5 rounded-md {{ $pay['bg'] }} text-white text-[10px] font-bold tracking-tight" title="{{ $pay['name'] }}">
                            {{ strtoupper(substr($pay['name'], 0, 4)) }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript pour le loader -->
    <script>
        // Messages de chargement dynamiques
        const loadingMessages = [
            'Chargement en cours...',
            'Préparation de votre expérience...',
            'Connexion à la marketplace...',
            'Chargement des cartes numériques...',
            'Initialisation de Kardafrica...',
            'Presque prêt...'
        ];
        
        function showLoader(message = null) {
            const loader = document.getElementById('pageLoader');
            const loaderText = document.getElementById('loaderText');
            
            if (message) {
                loaderText.textContent = message;
            } else {
                loaderText.textContent = loadingMessages[Math.floor(Math.random() * loadingMessages.length)];
            }
            
            loader.style.display = 'flex';
            loader.classList.remove('loader-fadeout');
            loader.style.opacity = '1';
        }
        
        function hideLoader() {
            const loader = document.getElementById('pageLoader');
            // Masquage IMMÉDIAT (fondu 300ms). L'ancien délai de 2000ms faisait
            // rester le loader ~2,5s de trop — c'était ça la lenteur perçue.
            loader.classList.add('loader-fadeout');
            setTimeout(function() {
                loader.style.display = 'none';
            }, 300);
        }
        
        // Afficher le loader au début
        document.addEventListener('DOMContentLoaded', function() {
            const loader = document.getElementById('pageLoader');

            // Masquer le loader une seule fois, dès que possible.
            // ⚠ Ne PAS attendre window.load : il attend TOUTES les ressources
            // (images de marques externes, fbevents.net, Google Fonts…), ce qui
            // pouvait bloquer la page 30-60s si une ressource tierce est lente.
            // On cache dès que le DOM est prêt, avec un plafond de sécurité.
            var loaderHidden = false;
            function safeHide() {
                if (loaderHidden) return;
                loaderHidden = true;
                hideLoader();
            }
            // Le DOM est prêt ici → la page est affichable : on cache quasi
            // tout de suite (le contenu est déjà rendu côté serveur).
            setTimeout(safeHide, 120);
            // Filet de sécurité absolu.
            setTimeout(safeHide, 1500);
            // Si tout charge très vite, on cache aussi sur 'load'.
            window.addEventListener('load', safeHide);
            
            // Afficher le loader lors de la navigation
            const links = document.querySelectorAll('a:not([href^="#"]):not([href^="mailto"]):not([href^="tel"]):not([target="_blank"]):not([download]):not([data-no-loader])');
            links.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    // Jamais de loader pour un téléchargement de fichier (la page ne
                    // se recharge pas → le loader resterait bloqué indéfiniment).
                    if (link.hasAttribute('download') || link.hasAttribute('data-no-loader')) return;
                    // Vérifier si c'est un lien interne
                    if (link.hostname === window.location.hostname) {
                        // Ajouter un délai pour s'assurer que le loader est visible
                        e.preventDefault();
                        
                        if (link.textContent.includes('Marketplace') || link.textContent.includes('Boutique')) {
                            showLoader('Chargement de la marketplace...');
                        } else if (link.textContent.includes('Mes Cartes')) {
                            showLoader('Chargement de vos cartes...');
                        } else if (link.textContent.includes('Voir') || link.textContent.includes('Détails')) {
                            showLoader('Chargement des détails...');
                        } else if (link.textContent.includes('Accueil')) {
                            showLoader('Retour à l\'accueil...');
                        } else {
                            showLoader();
                        }
                        
                        // Naviguer quasi immédiatement (juste le temps que le
                        // loader s'affiche). L'ancien délai de 300ms ralentissait
                        // chaque navigation pour rien.
                        setTimeout(function() {
                            window.location.href = link.href;
                        }, 60);
                    }
                });
            });
            
            // Gérer les soumissions de formulaires
            const forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    // Ignorer les formulaires avec l'attribut data-no-loader
                    if (form.hasAttribute('data-no-loader')) {
                        return;
                    }

                    if (form.querySelector('input[name="search"]')) {
                        showLoader('Recherche en cours...');
                    } else {
                        showLoader('Traitement en cours...');
                    }
                });
            });
        });
        
        // Masquer le loader en cas d'erreur de navigation
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                const loader = document.getElementById('pageLoader');
                loader.style.display = 'none';
            }
        });
        
        // Enhanced Navbar Scroll Effect
        document.addEventListener('DOMContentLoaded', function() {
            const navbar = document.querySelector('nav');
            
            function handleScroll() {
                const scrolled = window.pageYOffset;
                
                if (scrolled > 50) {
                    navbar.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.2)';
                } else {
                    navbar.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.1)';
                }
            }
            
            window.addEventListener('scroll', handleScroll);
        });

        // Enhanced Carousel functionality with animations and particles
        document.addEventListener('DOMContentLoaded', function() {
            const carousel = document.getElementById('bannerCarousel');
            if (carousel) {
                const slides = carousel.querySelectorAll('.carousel-slide');
                const dots = carousel.querySelectorAll('.carousel-dot');
                const nextBtn = carousel.querySelector('#nextBtn');
                const prevBtn = carousel.querySelector('#prevBtn');
                let currentSlide = 0;
                let autoSlideInterval;
                let isTransitioning = false;
                
                // Create floating particles for each slide
                function createParticles(slideIndex) {
                    const particleContainer = document.getElementById(`particles-${slideIndex}`);
                    if (!particleContainer) return;
                    
                    particleContainer.innerHTML = '';
                    
                    for (let i = 0; i < 20; i++) {
                        const particle = document.createElement('div');
                        particle.className = 'particle';
                        particle.style.left = Math.random() * 100 + '%';
                        particle.style.animationDelay = Math.random() * 8 + 's';
                        particle.style.animationDuration = (Math.random() * 4 + 4) + 's';
                        particleContainer.appendChild(particle);
                    }
                }
                
                // Initialize particles for all slides
                slides.forEach((slide, index) => {
                    createParticles(index);
                });
                
                function showSlide(index, direction = 'next') {
                    if (isTransitioning) return;
                    isTransitioning = true;
                    
                    const currentSlideElement = slides[currentSlide];
                    const nextSlideElement = slides[index];
                    
                    // Reset progress bars
                    slides.forEach(slide => {
                        const progressBar = slide.querySelector('.carousel-progress');
                        if (progressBar) {
                            progressBar.style.width = '0%';
                        }
                    });
                    
                    // Set initial positions
                    nextSlideElement.style.transform = direction === 'next' ? 'translateX(100%)' : 'translateX(-100%)';
                    nextSlideElement.style.opacity = '0';
                    
                    // Remove active class from all slides
                    slides.forEach(slide => {
                        slide.classList.remove('active');
                    });
                    
                    // Add active class to next slide
                    nextSlideElement.classList.add('active');
                    
                    // Animate the transition
                    requestAnimationFrame(() => {
                        currentSlideElement.style.transform = direction === 'next' ? 'translateX(-100%)' : 'translateX(100%)';
                        currentSlideElement.style.opacity = '0';
                        
                        nextSlideElement.style.transform = 'translateX(0)';
                        nextSlideElement.style.opacity = '1';
                    });
                    
                    // Update dots
                    if (dots.length > 0) {
                        dots.forEach(dot => dot.classList.remove('active'));
                        if (dots[index]) {
                            dots[index].classList.add('active');
                        }
                    }
                    
                    // Reset current slide
                    currentSlide = index;
                    
                    // Start progress bar animation
                    setTimeout(() => {
                        const progressBar = nextSlideElement.querySelector('.carousel-progress');
                        if (progressBar) {
                            progressBar.style.width = '100%';
                        }
                    }, 100);
                    
                    // Reset transition flag
                    setTimeout(() => {
                        isTransitioning = false;
                        
                        // Reset previous slide position
                        setTimeout(() => {
                            slides.forEach((slide, i) => {
                                if (i !== currentSlide) {
                                    slide.style.transform = 'translateX(100%)';
                                    slide.style.opacity = '0';
                                }
                            });
                        }, 100);
                    }, 800);
                }
                
                function nextSlide() {
                    const next = (currentSlide + 1) % slides.length;
                    showSlide(next, 'next');
                }
                
                function prevSlide() {
                    const prev = (currentSlide - 1 + slides.length) % slides.length;
                    showSlide(prev, 'prev');
                }
                
                function startAutoSlide() {
                    autoSlideInterval = setInterval(nextSlide, 5000);
                }
                
                function stopAutoSlide() {
                    clearInterval(autoSlideInterval);
                }
                
                // Event listeners
                if (nextBtn) {
                    nextBtn.addEventListener('click', () => {
                        stopAutoSlide();
                        nextSlide();
                        startAutoSlide();
                    });
                }
                
                if (prevBtn) {
                    prevBtn.addEventListener('click', () => {
                        stopAutoSlide();
                        prevSlide();
                        startAutoSlide();
                    });
                }
                
                dots.forEach((dot, index) => {
                    dot.addEventListener('click', () => {
                        if (index !== currentSlide) {
                            stopAutoSlide();
                            const direction = index > currentSlide ? 'next' : 'prev';
                            showSlide(index, direction);
                            startAutoSlide();
                        }
                    });
                });
                
                // Pause on hover
                carousel.addEventListener('mouseenter', stopAutoSlide);
                carousel.addEventListener('mouseleave', startAutoSlide);
                
                // Touch/swipe support
                let touchStartX = 0;
                let touchEndX = 0;
                
                carousel.addEventListener('touchstart', (e) => {
                    touchStartX = e.changedTouches[0].screenX;
                });
                
                carousel.addEventListener('touchend', (e) => {
                    touchEndX = e.changedTouches[0].screenX;
                    handleSwipe();
                });
                
                function handleSwipe() {
                    const swipeThreshold = 50;
                    const diff = touchStartX - touchEndX;
                    
                    if (Math.abs(diff) > swipeThreshold) {
                        stopAutoSlide();
                        if (diff > 0) {
                            nextSlide();
                        } else {
                            prevSlide();
                        }
                        startAutoSlide();
                    }
                }
                
                // Keyboard navigation
                document.addEventListener('keydown', (e) => {
                    if (carousel.matches(':hover')) {
                        if (e.key === 'ArrowRight') {
                            stopAutoSlide();
                            nextSlide();
                            startAutoSlide();
                        } else if (e.key === 'ArrowLeft') {
                            stopAutoSlide();
                            prevSlide();
                            startAutoSlide();
                        }
                    }
                });
                
                // Initialize
                showSlide(0, 'next');
                startAutoSlide();
            }
        });
        
        // Cart and Chatbot functionality
        // Currency conversion — taux EUR/USD/AED viennent de la BDD (admin),
        // les autres restent figés en client. Synchronisé avec App\Support\Money
        // via Blade : si l'admin change EUR=750 → 800, le rendu suivant l'utilise.
        const EXCHANGE_RATES = @json(\App\Support\Money::currentRates());
        const FCFA_ROUND_STEP = {{ \App\Support\Money::roundStep() }};

        // Convert to FCFA (applique le même arrondi vers le haut que côté serveur).
        function convertToFCFA(amount, currencyCode) {
            if (!currencyCode) return amount;
            const rate = EXCHANGE_RATES[currencyCode.toUpperCase()] || 0;
            if (rate === 0) return amount;
            const converted = amount * rate;
            if (FCFA_ROUND_STEP <= 1) return Math.ceil(converted);
            // Arrondi au plus haut multiple de FCFA_ROUND_STEP (1016 → 1100)
            return Math.ceil(converted / FCFA_ROUND_STEP) * FCFA_ROUND_STEP;
        }

        // Format as FCFA
        function formatFCFA(amount) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'XAF',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount).replace('XAF', 'FCFA');
        }

        // Expose globally
        window.convertToFCFA = convertToFCFA;
        window.formatFCFA = formatFCFA;
        
        // Auto-convert prices
        function updatePrices() {
            document.querySelectorAll('.price-display').forEach(el => {
                // Avoid double conversion if already processed (optional check)
                if (el.dataset.processed === 'true') return;
                
                const price = parseFloat(el.dataset.price);
                const currency = el.dataset.currency;
                
                if (!isNaN(price) && currency) {
                    const converted = convertToFCFA(price, currency);
                    el.textContent = formatFCFA(converted);
                    el.dataset.processed = 'true';
                }
            });
        }
        window.updatePrices = updatePrices;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Run price update on load
            updatePrices();

            // Mobile sidebar functionality
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const mobileMenu = document.getElementById('mobileMenu');
            const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
            const closeMobileMenu = document.getElementById('closeMobileMenu');
            const mobileCardsBtn = document.getElementById('mobileCardsBtn');
            const mobileCardsSubmenu = document.getElementById('mobileCardsSubmenu');
            const mobileCardsIcon = document.getElementById('mobileCardsIcon');
            const mobileAuthBtn = document.getElementById('mobileAuthBtn');
            
            function openMobileSidebar() {
                if (mobileMenu && mobileMenuOverlay) {
                    // Empêcher le scroll du body
                    document.body.classList.add('mobile-menu-open');
                    
                    // Afficher l'overlay avec animation
                    mobileMenuOverlay.classList.add('show');
                    mobileMenuOverlay.style.display = 'block';
                    
                    // Afficher la sidebar avec animation
                    mobileMenu.classList.add('show');
                    
                    // Animation avec délai pour les éléments
                    setTimeout(() => {
                        mobileMenuOverlay.style.opacity = '1';
                        mobileMenuOverlay.style.visibility = 'visible';
                    }, 10);
                }
            }
            
            function closeMobileSidebar() {
                if (mobileMenu && mobileMenuOverlay) {
                    // Réactiver le scroll du body
                    document.body.classList.remove('mobile-menu-open');
                    
                    // Masquer la sidebar
                    mobileMenu.classList.remove('show');
                    
                    // Masquer l'overlay avec transition
                    mobileMenuOverlay.classList.remove('show');
                    mobileMenuOverlay.style.opacity = '0';
                    mobileMenuOverlay.style.visibility = 'hidden';
                    
                    // Masquer complètement après l'animation
                    setTimeout(() => {
                        mobileMenuOverlay.style.display = 'none';
                    }, 300);
                    
                    // Reset cards submenu
                    if (mobileCardsSubmenu && mobileCardsIcon) {
                        mobileCardsSubmenu.style.maxHeight = '0px';
                        mobileCardsIcon.style.transform = 'rotate(0deg)';
                    }
                }
            }
            
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    openMobileSidebar();
                });
            }
            
            if (closeMobileMenu) {
                closeMobileMenu.addEventListener('click', function() {
                    closeMobileSidebar();
                });
            }
            
            // Fermer en cliquant sur l'overlay
            if (mobileMenuOverlay) {
                mobileMenuOverlay.addEventListener('click', function() {
                    closeMobileSidebar();
                });
            }
            
            // Mobile cards dropdown functionality
            if (mobileCardsBtn && mobileCardsSubmenu && mobileCardsIcon) {
                mobileCardsBtn.addEventListener('click', function() {
                    const isOpen = mobileCardsSubmenu.style.maxHeight && mobileCardsSubmenu.style.maxHeight !== '0px';
                    if (isOpen) {
                        mobileCardsSubmenu.style.maxHeight = '0px';
                        mobileCardsIcon.style.transform = 'rotate(0deg)';
                    } else {
                        mobileCardsSubmenu.style.maxHeight = mobileCardsSubmenu.scrollHeight + 'px';
                        mobileCardsIcon.style.transform = 'rotate(180deg)';
                    }
                });
            }
            
            // Mobile auth button functionality
            if (mobileAuthBtn) {
                mobileAuthBtn.addEventListener('click', function() {
                    openAuthModal();
                    // Fermer la sidebar après avoir ouvert le modal
                    closeMobileSidebar();
                });
            }
            
            // Fermer avec la touche Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeMobileSidebar();
                }
            });
            
            // Support du swipe pour fermer la sidebar
            let startX = 0;
            let currentX = 0;
            let isDragging = false;
            
            if (mobileMenu) {
                mobileMenu.addEventListener('touchstart', function(e) {
                    startX = e.touches[0].clientX;
                    isDragging = true;
                });
                
                mobileMenu.addEventListener('touchmove', function(e) {
                    if (!isDragging) return;
                    
                    currentX = e.touches[0].clientX;
                    const diffX = currentX - startX;
                    
                    // Permettre uniquement le swipe vers la droite
                    if (diffX > 0) {
                        const translateValue = Math.min(diffX, 320); // 320px = largeur de la sidebar
                        mobileMenu.style.transform = `translateX(${translateValue}px)`;
                        
                        // Ajuster l'opacité de l'overlay
                        const opacity = Math.max(0, 1 - (diffX / 320));
                        if (mobileMenuOverlay) {
                            mobileMenuOverlay.style.opacity = opacity;
                        }
                    }
                });
                
                mobileMenu.addEventListener('touchend', function(e) {
                    if (!isDragging) return;
                    isDragging = false;
                    
                    const diffX = currentX - startX;
                    
                    // Si le swipe dépasse 100px, fermer la sidebar
                    if (diffX > 100) {
                        closeMobileSidebar();
                    } else {
                        // Sinon, remettre en position
                        mobileMenu.style.transform = 'translateX(0)';
                        if (mobileMenuOverlay) {
                            mobileMenuOverlay.style.opacity = '1';
                        }
                    }
                });
            }
            
            // Auto-convert prices on load (Removed from here, moved to global scope)
            // updatePrices();
            
            // Expose updatePrices for dynamic content (Removed from here)
            // window.updatePrices = updatePrices;

        }); // End of previous DOMContentLoaded

        // Global Cart Variables and Functions
        let cart = [];
        
        function getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        }

        function isMobile() {
            return window.innerWidth <= 768;
        }

        async function fetchCart() {
            try {
                const response = await fetch('{{ route("api.cart.index") }}');
                const data = await response.json();
                cart = data.items;
                updateCartDisplay(data);
            } catch (error) {
                console.error('Erreur lors de la récupération du panier:', error);
            }
        }

        async function addToCart(productId, name, price, imageUrl = null) {
            try {
                const csrfToken = getCsrfToken();
                const response = await fetch('{{ route("api.cart.add") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        name: name,
                        price: price,
                        image_url: imageUrl,
                        quantity: 1
                    })
                });
                
                if (response.ok) {
                    const data = await response.json();
                    window.dispatchEvent(new CustomEvent('cart-updated', { detail: data }));
                    
                    if (isMobile()) {
                        openMobileCartSidebar();
                    } else {
                        window.dispatchEvent(new CustomEvent('open-cart-dropdown'));
                    }
                }
            } catch (error) {
                console.error('Erreur lors de l\'ajout au panier:', error);
            }
        }

        async function removeFromCart(id) {
            try {
                const csrfToken = getCsrfToken();
                const response = await fetch(`/api/cart/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    window.dispatchEvent(new CustomEvent('cart-updated', { detail: data }));
                }
            } catch (error) {
                console.error('Erreur lors de la suppression du panier:', error);
            }
        }

        function openMobileCartSidebar() {
            const mobileCartSidebar = document.getElementById('mobileCartSidebar');
            const mobileCartOverlay = document.getElementById('mobileCartOverlay');
            
            if (mobileCartSidebar && mobileCartOverlay) {
                document.body.classList.add('mobile-cart-open');
                mobileCartOverlay.classList.add('show');
                mobileCartOverlay.style.display = 'block';
                mobileCartSidebar.classList.add('show');
                setTimeout(() => {
                    mobileCartOverlay.style.opacity = '1';
                    mobileCartOverlay.style.visibility = 'visible';
                }, 10);
            }
        }

        function closeMobileCartSidebar() {
            const mobileCartSidebar = document.getElementById('mobileCartSidebar');
            const mobileCartOverlay = document.getElementById('mobileCartOverlay');
            
            if (mobileCartSidebar && mobileCartOverlay) {
                document.body.classList.remove('mobile-cart-open');
                mobileCartSidebar.classList.remove('show');
                mobileCartOverlay.classList.remove('show');
                mobileCartOverlay.style.opacity = '0';
                mobileCartOverlay.style.visibility = 'hidden';
                setTimeout(() => {
                    mobileCartOverlay.style.display = 'none';
                }, 300);
            }
        }

        function updateCartDisplay(cartData) {
            const cartCount = document.getElementById('cartCount');
            const cartCountMobile = document.getElementById('cartCountMobile');
            const cartCountMobileHeader = document.getElementById('cartCountMobileHeader');
            const mobileCartItems = document.getElementById('mobileCartItems');
            const mobileCartTotal = document.getElementById('mobileCartTotal');
            const cartTotal = document.getElementById('cartTotal');
            const cartItems = document.getElementById('cartItems');

            const count = cartData ? (cartData.count || 0) : cart.length;

            if (cartCount) cartCount.textContent = count;
            if (cartCountMobile) cartCountMobile.textContent = count;
            if (cartCountMobileHeader) cartCountMobileHeader.textContent = count;
            
            const emptyCartHtml = `
                        <div class="text-center py-8">
                            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                            </div>
                    <p class="text-gray-500 font-medium mb-2">Votre panier est vide</p>
                    <p class="text-sm text-gray-400">Découvrez nos cartes numériques</p>
                        </div>
                    `;
            
            const cartItemsHtml = cart.map(item => `
                <div class="cart-item flex justify-between items-center p-3 mb-2 bg-white rounded-lg border border-gray-100">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%);">
                                    ${item.image_url ? `<img src="${item.image_url}" class="w-full h-full object-cover rounded-lg" />` : 
                                    `<svg class="w-5 h-5" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                    </svg>`}
                                </div>
                                <div>
                            <span class="text-sm font-medium text-gray-900 block truncate w-32">${item.name}</span>
                            <p class="text-xs text-gray-500">Qté: ${item.quantity}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                        <span class="text-sm font-bold text-kardafrica-primary">${new Intl.NumberFormat('fr-FR').format(item.price * item.quantity)} FCFA</span>
                        <button class="remove-from-cart-btn p-1 hover:bg-red-50 rounded transition-all duration-200 text-red-500" data-id="${item.id}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    `).join('');
            
            if (cartItems) {
                cartItems.innerHTML = cart.length === 0 ? emptyCartHtml : cartItemsHtml;
            }
            
            if (mobileCartItems) {
                mobileCartItems.innerHTML = cart.length === 0 ? emptyCartHtml : cartItemsHtml;
            }
                
            const total = cart.reduce((sum, item) => sum + parseFloat(item.price), 0);
            const totalText = new Intl.NumberFormat('fr-FR').format(total) + ' FCFA';
            
            if (cartTotal) cartTotal.textContent = totalText;
            
            if (mobileCartTotal) mobileCartTotal.textContent = totalText;

            document.querySelectorAll('.remove-from-cart-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = this.getAttribute('data-id');
                    removeFromCart(id);
                });
            });
        }

        // Expose globally
        window.addToCart = addToCart;
        window.fetchCart = fetchCart;
        window.removeFromCart = removeFromCart;
        window.updateCartDisplay = updateCartDisplay;
        window.openMobileCartSidebar = openMobileCartSidebar;
        window.closeMobileCartSidebar = closeMobileCartSidebar;

        // Listen for cart updates
        window.addEventListener('cart-updated', (e) => {
            if (e.detail && e.detail.items) {
                cart = e.detail.items;
                updateCartDisplay(e.detail);
            } else {
                fetchCart();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Cart functionality (Event Listeners)
            const cartBtn = document.getElementById('cartBtn');
            const cartBtnMobile = document.getElementById('cartBtnMobile');
            const closeMobileCart = document.getElementById('closeMobileCart');
            const mobileCartOverlay = document.getElementById('mobileCartOverlay');
            const mobileCartSidebar = document.getElementById('mobileCartSidebar');

            // Global click listener for Add to Cart buttons
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.add-to-cart-btn');
                if (btn) {
                    e.preventDefault();
                    const productId = btn.dataset.productId;
                    const productName = btn.dataset.productName;
                    let price = parseFloat(btn.dataset.price);
                    const currencyCode = btn.dataset.currencyCode;
                    const imageUrl = btn.dataset.imageUrl;
                    
                    if (currencyCode) {
                        price = convertToFCFA(price, currencyCode);
                    }
                    
                    if (productId) {
                        addToCart(productId, productName, price, imageUrl);
                    }
                }
            });
            
            if (cartBtn) {
                cartBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (isMobile()) {
                        openMobileCartSidebar();
                    } else {
                        window.dispatchEvent(new CustomEvent('toggle-cart-dropdown'));
                    }
                });
            }
            
            if (cartBtnMobile) {
                cartBtnMobile.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    openMobileCartSidebar();
                });
            }

            if (closeMobileCart) {
                closeMobileCart.addEventListener('click', function() {
                    closeMobileCartSidebar();
                });
            }
            
            if (mobileCartOverlay) {
                mobileCartOverlay.addEventListener('click', function() {
                    closeMobileCartSidebar();
                });
            }
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && mobileCartSidebar && mobileCartSidebar.classList.contains('show')) {
                    closeMobileCartSidebar();
                }
            });

            // Support du swipe pour fermer la sidebar panier
            let cartStartX = 0;
            let cartCurrentX = 0;
            let cartIsDragging = false;
            
            if (mobileCartSidebar) {
                mobileCartSidebar.addEventListener('touchstart', function(e) {
                    cartStartX = e.touches[0].clientX;
                    cartIsDragging = true;
                });
                
                mobileCartSidebar.addEventListener('touchmove', function(e) {
                    if (!cartIsDragging) return;
                    
                    cartCurrentX = e.touches[0].clientX;
                    const diffX = cartCurrentX - cartStartX;
                    
                    if (diffX > 0) {
                        const translateValue = Math.min(diffX, 320);
                        mobileCartSidebar.style.transform = `translateX(${translateValue}px)`;
                        
                        const opacity = Math.max(0, 1 - (diffX / 320));
                        if (mobileCartOverlay) {
                            mobileCartOverlay.style.opacity = opacity;
                        }
                    }
                });
                
                mobileCartSidebar.addEventListener('touchend', function(e) {
                    if (!cartIsDragging) return;
                    cartIsDragging = false;
                    
                    const diffX = cartCurrentX - cartStartX;
                    
                    if (diffX > 100) {
                        closeMobileCartSidebar();
                    } else {
                        mobileCartSidebar.style.transform = 'translateX(0)';
                        if (mobileCartOverlay) {
                            mobileCartOverlay.style.opacity = '1';
                        }
                    }
                });
            }
            
            // Initialize cart display
            fetchCart();
            
            // Section animations on scroll
            const sections = document.querySelectorAll('.section-animate');
            const brandItems = document.querySelectorAll('.brand-grid-item');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate');
                    }
                });
            }, {
                threshold: 0.1
            });
            
            sections.forEach(section => {
                observer.observe(section);
            });
            
            // Animate brand items with delay
            const brandObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const index = Array.from(brandItems).indexOf(entry.target);
                        setTimeout(() => {
                            entry.target.classList.add('animate');
                        }, index * 100);
                    }
                });
            }, {
                threshold: 0.1
            });
            
            brandItems.forEach(item => {
                brandObserver.observe(item);
            });
            
            // ============================================================
            //  Assistant Kardafrica — Kara
            //  - keyword routing avec liens vers les pages internes
            //  - typing indicator + persistence sessionStorage
            //  - badge unread sur le launcher
            // ============================================================
            (function () {
                const toggle    = document.getElementById('chatbotToggle');
                const wnd       = document.getElementById('chatbotWindow');
                const closeBtn  = document.getElementById('closeChatbot');
                const input     = document.getElementById('chatInput');
                const sendBtn   = document.getElementById('sendMessage');
                const messages  = document.getElementById('chatMessages');
                const chips     = document.getElementById('kaBotChips');
                const badge     = document.getElementById('kaBotBadge');
                if (!toggle || !wnd) return;

                // Routes Laravel exposées au JS
                const routes = {
                    boutique:    @json(route('boutique')),
                    contact:     @json(route('contact')),
                    support:     @json(route('support')),
                    about:       @json(route('about')),
                    cart:        @json(route('cart.index')),
                    @auth
                    orders:      @json(route('orders.index')),
                    cards:       @json(route('cards.index')),
                    profile:     @json(route('profile.show')),
                    @endauth
                    daywatch:    @json(url('/category/5')),
                    netflix:     @json(url('/boutique?search=Netflix')),
                    spotify:     @json(url('/boutique?search=Spotify')),
                    playstation: @json(url('/boutique?search=PlayStation')),
                };
                const isAuth = @json(auth()->check());

                // === Knowledge base : intent -> { reply, links?, suggestions? } ===
                const KB = [
                    {
                        keywords: ['bonjour', 'salut', 'hello', 'hi', 'coucou', 'bonsoir', 'hey'],
                        reply: () => `Bonjour 👋 Je suis Kara, l'assistante de KardAfrica. Comment puis-je t'aider aujourd'hui ?`,
                        suggestions: ['Voir le catalogue', 'Comment payer ?', 'Délai de livraison'],
                    },
                    {
                        keywords: ['merci', 'thanks', 'thank you', 'super', 'parfait', 'génial'],
                        reply: () => `Avec plaisir ! N'hésite pas si tu as d'autres questions 😊`,
                        suggestions: ['Voir mes cartes', 'Contacter le support'],
                    },
                    {
                        keywords: ['catalogue', 'cartes', 'boutique', 'produits', 'marques', 'marque', 'voir', 'shop'],
                        reply: () => `On a plus de 120 marques disponibles : Netflix, Spotify, Apple, PlayStation, Steam, Amazon… et notre offre locale Daywatch.`,
                        links: [
                            { label: 'Toute la boutique →', href: routes.boutique },
                            { label: 'Daywatch (streaming local)', href: routes.daywatch },
                        ],
                        suggestions: ['Comment payer ?', 'Délai de livraison'],
                    },
                    {
                        keywords: ['daywatch', 'streaming local', 'streaming africain'],
                        reply: () => `Daywatch, c'est notre offre de streaming locale 🇬🇦. Films, séries, sport, kids — paiement Mobile Money en quelques secondes.`,
                        links: [{ label: 'Voir les abonnements Daywatch →', href: routes.daywatch }],
                        suggestions: ['Catalogue complet', 'Comment payer ?'],
                    },
                    {
                        keywords: ['netflix'], reply: () => `Cartes Netflix dispo en plusieurs montants !`,
                        links: [{ label: 'Voir Netflix →', href: routes.netflix }],
                    },
                    {
                        keywords: ['spotify'], reply: () => `Cartes Spotify Premium pour profiter de ta musique en illimité.`,
                        links: [{ label: 'Voir Spotify →', href: routes.spotify }],
                    },
                    {
                        keywords: ['playstation', 'psn', 'ps5', 'ps4'],
                        reply: () => `Cartes PlayStation Store pour recharger ton compte PSN.`,
                        links: [{ label: 'Voir PlayStation →', href: routes.playstation }],
                    },
                    {
                        keywords: ['paiement', 'payer', 'paie', 'mobile money', 'airtel', 'moov', 'visa', 'mastercard', 'carte bancaire', 'comment paye'],
                        reply: () => `Tu peux payer en :\n• Airtel Money 🟧\n• Moov Money 🟦\n• Visa 💳\n\nTout est sécurisé via notre partenaire Futursowax.`,
                        suggestions: ['Délai de livraison', 'Mon paiement a échoué'],
                    },
                    {
                        keywords: ['livraison', 'delai', 'délai', 'reçu', 'recu', 'recevoir', 'rapide', 'temps'],
                        reply: () => `Livraison instantanée ! Le code arrive dans ta boîte mail en moins de 60 secondes après confirmation du paiement, et il est aussi visible dans « Mes cartes ».`,
                        links: isAuth ? [{ label: 'Voir mes cartes →', href: routes.cards }] : [{ label: 'Se connecter', href: '#', auth: true }],
                        suggestions: ['Je n\'ai pas reçu mon code', 'Contacter le support'],
                    },
                    {
                        keywords: ['pas reçu', 'pas recu', 'jamais reçu', 'rien reçu', 'manque', 'manquant', 'reçu', 'echoué', 'echouer', 'échec', 'echec'],
                        reply: () => `Pas de panique 🙂 Vérifie d'abord ton dossier spam. Si rien dans 5 minutes, va dans « Mes commandes » et clique sur « Relancer la livraison ».`,
                        links: isAuth
                            ? [{ label: 'Mes commandes →', href: routes.orders }]
                            : [{ label: 'Centre d\'aide', href: routes.support }],
                        suggestions: ['Contacter le support', 'Comment payer ?'],
                    },
                    {
                        keywords: ['remboursement', 'rembourser', 'refund', 'annuler', 'annulation'],
                        reply: () => `Si le débit a eu lieu sans validation de la commande, le montant est remboursé automatiquement sous 24h. Pour toute autre demande, contacte-nous.`,
                        links: [{ label: 'Contacter le support', href: routes.contact }],
                    },
                    {
                        keywords: ['compte', 'inscription', 'inscrire', 'créer', 'creer', 'register'],
                        reply: () => isAuth
                            ? `Tu es déjà connecté ! Tu peux gérer ton compte depuis ton profil.`
                            : `Crée ton compte en 30 secondes : juste un email + un mot de passe.`,
                        links: isAuth
                            ? [{ label: 'Mon profil →', href: routes.profile }]
                            : [{ label: 'Créer un compte', href: '#', authRegister: true }],
                    },
                    {
                        keywords: ['connexion', 'se connecter', 'login', 'connecter', 'mot de passe', 'oublié'],
                        reply: () => isAuth
                            ? `Tu es déjà connecté.`
                            : `Connecte-toi avec ton email + mot de passe. Mot de passe oublié ? Clique sur « Mot de passe oublié » dans la page de connexion.`,
                        links: isAuth ? [] : [{ label: 'Se connecter', href: '#', auth: true }],
                    },
                    {
                        keywords: ['panier', 'cart'],
                        reply: () => `Voici ton panier !`,
                        links: [{ label: 'Voir le panier →', href: routes.cart }],
                    },
                    {
                        keywords: ['contact', 'humain', 'agent', 'support', 'aide', 'whatsapp', 'téléphone', 'telephone', 'mail', 'email'],
                        reply: () => `Notre équipe répond en moins d'1h en heures ouvrées. Plusieurs canaux dispo :`,
                        links: [
                            { label: 'Email / Formulaire', href: routes.contact },
                            { label: 'Centre d\'aide', href: routes.support },
                        ],
                    },
                    {
                        keywords: ['kardafrica', 'qui', 'kard africa', 'à propos', 'a propos', 'about'],
                        reply: () => `KardAfrica connecte des millions d'Africains aux meilleures plateformes mondiales. Une seule app, paiement Mobile Money, livraison instantanée.`,
                        links: [{ label: 'En savoir plus →', href: routes.about }],
                    },
                ];

                const FALLBACK = {
                    reply: () => `Je ne suis pas sûre d'avoir bien compris 🤔. Tu peux essayer une question sur les cartes, le paiement ou la livraison — sinon notre équipe support est dispo en moins d'1h !`,
                    links: [{ label: 'Contacter le support', href: routes.contact }],
                    suggestions: ['Voir le catalogue', 'Comment payer ?', 'Délai de livraison'],
                };

                const DEFAULT_CHIPS = ['Voir le catalogue', 'Comment payer ?', 'Délai de livraison', 'Daywatch'];
                const STORAGE_KEY = 'kardafrica-bot-history';
                const OPEN_KEY    = 'kardafrica-bot-opened';

                function escapeHtml(s) {
                    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
                }
                function formatTime(d) {
                    return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                }

                function renderMessage(text, isUser, links = [], skipPersist = false) {
                    const wrap = document.createElement('div');
                    wrap.className = `ka-bot-msg ${isUser ? 'user' : 'bot'}`;

                    const avatar = document.createElement('div');
                    avatar.className = 'ka-bot-msg-mini-avatar';
                    if (!isUser) {
                        avatar.innerHTML = '<svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>';
                    } else {
                        avatar.style.background = '#0F172A';
                        avatar.style.color = 'white';
                        avatar.textContent = 'M';
                    }

                    const bubble = document.createElement('div');
                    bubble.className = 'ka-bot-msg-bubble';
                    bubble.innerHTML = escapeHtml(text).replace(/\n/g, '<br/>');

                    if (!isUser && links && links.length > 0) {
                        const linksWrap = document.createElement('div');
                        linksWrap.className = 'ka-bot-quick-links';
                        links.forEach(l => {
                            const a = document.createElement('a');
                            a.className = 'ka-bot-quick-link';
                            a.textContent = l.label;
                            if (l.auth) {
                                a.href = '#';
                                a.addEventListener('click', e => { e.preventDefault(); window.dispatchEvent(new CustomEvent('open-auth-modal')); window.dispatchEvent(new CustomEvent('set-auth-view', { detail: { view: 'login' } })); });
                            } else if (l.authRegister) {
                                a.href = '#';
                                a.addEventListener('click', e => { e.preventDefault(); window.dispatchEvent(new CustomEvent('open-auth-modal')); window.dispatchEvent(new CustomEvent('set-auth-view', { detail: { view: 'register' } })); });
                            } else {
                                a.href = l.href;
                            }
                            linksWrap.appendChild(a);
                        });
                        bubble.appendChild(linksWrap);
                    }

                    wrap.appendChild(avatar);
                    wrap.appendChild(bubble);
                    messages.appendChild(wrap);

                    requestAnimationFrame(() => { messages.scrollTop = messages.scrollHeight; });

                    if (!skipPersist) {
                        try {
                            const hist = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]');
                            hist.push({ text, isUser, links: !isUser ? links : [] });
                            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(hist.slice(-50)));
                        } catch (_) {}
                    }
                }

                function showTyping() {
                    const wrap = document.createElement('div');
                    wrap.className = 'ka-bot-msg bot';
                    wrap.id = 'kaBotTyping';
                    wrap.innerHTML = `
                        <div class="ka-bot-msg-mini-avatar"><svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25"/></svg></div>
                        <div class="ka-bot-typing"><span></span><span></span><span></span></div>
                    `;
                    messages.appendChild(wrap);
                    messages.scrollTop = messages.scrollHeight;
                }
                function hideTyping() {
                    const t = document.getElementById('kaBotTyping');
                    if (t) t.remove();
                }

                function findIntent(query) {
                    const q = query.toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '');
                    let best = null;
                    let bestScore = 0;
                    for (const intent of KB) {
                        let score = 0;
                        for (const kw of intent.keywords) {
                            const k = kw.normalize('NFD').replace(/\p{Diacritic}/gu, '');
                            if (q.includes(k)) score += k.length;
                        }
                        if (score > bestScore) { bestScore = score; best = intent; }
                    }
                    return best || FALLBACK;
                }

                function renderChips(list) {
                    chips.innerHTML = '';
                    list.forEach(text => {
                        const b = document.createElement('button');
                        b.type = 'button';
                        b.className = 'ka-bot-chip';
                        b.textContent = text;
                        b.addEventListener('click', () => {
                            input.value = text;
                            handleSend();
                        });
                        chips.appendChild(b);
                    });
                }

                function handleSend() {
                    const text = input.value.trim();
                    if (!text) return;
                    renderMessage(text, true);
                    input.value = '';
                    sendBtn.disabled = true;

                    showTyping();
                    setTimeout(() => {
                        hideTyping();
                        const intent = findIntent(text);
                        renderMessage(intent.reply(), false, intent.links || []);
                        renderChips(intent.suggestions || DEFAULT_CHIPS);
                        sendBtn.disabled = false;
                    }, 600 + Math.random() * 400);
                }

                const label    = document.getElementById('kaBotLabel');
                const backdrop = document.getElementById('kaBotBackdrop');
                const LABEL_HIDDEN_KEY = 'kaBotLabelHidden';

                function openBot() {
                    wnd.classList.add('show');
                    if (backdrop) backdrop.classList.add('show');
                    document.body.classList.add('ka-bot-open');
                    badge.style.display = 'none';
                    sessionStorage.setItem(OPEN_KEY, '1');
                    if (label) {
                        label.classList.add('hidden');
                        localStorage.setItem(LABEL_HIDDEN_KEY, '1');
                    }
                    // Sur desktop on focus l'input ; sur mobile on évite (évite le clavier qui couvre)
                    if (window.matchMedia('(min-width: 541px)').matches) {
                        setTimeout(() => input.focus(), 250);
                    }
                }
                function closeBot() {
                    wnd.classList.remove('show');
                    if (backdrop) backdrop.classList.remove('show');
                    document.body.classList.remove('ka-bot-open');
                }

                // Cache le label si déjà ouvert auparavant
                if (label && localStorage.getItem(LABEL_HIDDEN_KEY) === '1') {
                    label.classList.add('hidden');
                }

                // Click sur le backdrop ferme la fenêtre (UX iOS-style)
                if (backdrop) backdrop.addEventListener('click', closeBot);

                // Echap ferme aussi
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && wnd.classList.contains('show')) closeBot();
                });

                // Restore history if any, otherwise greet
                let restored = false;
                try {
                    const hist = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]');
                    if (hist.length > 0) {
                        hist.forEach(m => renderMessage(m.text, m.isUser, m.links || [], true));
                        restored = true;
                    }
                } catch (_) {}

                if (!restored) {
                    setTimeout(() => {
                        renderMessage(`Bonjour 👋 Je suis Kara, l'assistante KardAfrica. Comment puis-je t'aider aujourd'hui ?`, false);
                    }, 100);
                }
                renderChips(DEFAULT_CHIPS);

                // Show launcher badge if user never opened the bot in this session
                if (!sessionStorage.getItem(OPEN_KEY)) {
                    badge.style.display = '';
                }

                // Wire events
                toggle.addEventListener('click', () => {
                    wnd.classList.contains('show') ? closeBot() : openBot();
                });
                closeBtn.addEventListener('click', closeBot);
                sendBtn.addEventListener('click', handleSend);
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        handleSend();
                    }
                });

                // ESC ferme
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && wnd.classList.contains('show')) closeBot();
                });
            })();
        });
    </script>

    <!-- Auth Modal avec Alpine.js -->
    <div x-data="{ open: false }" 
         @open-auth-modal.window="open = true"
         @keydown.escape.window="open = false"
         x-show="open" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         style="display: none;"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div @click.outside="open = false" class="relative w-full max-w-md">
            <button @click="open = false" class="absolute -top-12 right-0 text-white hover:text-gray-200 focus:outline-none z-50">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            
            @include('auth.flip-card')
        </div>
    </div>

    <!-- Script global pour déclencher le modal auth -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initial fetch of cart data
            if (typeof fetchCart === 'function') {
                fetchCart();
            }
            
            // Listen for cart updates
            window.addEventListener('cart-updated', function() {
                if (typeof fetchCart === 'function') {
                    fetchCart();
                }
            });

            // Intercepter les clics sur les boutons/liens de connexion/inscription
            // IMPORTANT : on n'intercepte que de vrais <a>/<button> avec un texte court,
            // sinon on remontait jusqu'au <footer> et "désinscription" matchait "inscription".
            document.addEventListener('click', function(e) {
                // Ignorer les clics à l'intérieur du modal d'auth lui-même
                if (e.target.closest('.auth-modal-content')) return;
                // Ignorer les clics à l'intérieur d'un formulaire
                if (e.target.closest('form')) return;

                // On ne traite que les vrais éléments cliquables
                const element = e.target.closest('a, button');
                if (!element) return;

                // Si l'élément a déjà un href ou un onclick explicite, on le laisse faire
                if (element.tagName === 'A') {
                    const href = element.getAttribute('href') || '';
                    // Liens "réels" (vers une page) ou ancres : on ne touche pas
                    if (href && href !== '#' && !href.startsWith('javascript:')) return;
                }

                const text = (element.textContent || '').trim().toLowerCase();
                // Garde-fou : ignorer les éléments avec trop de texte (header/footer, etc.)
                if (text.length === 0 || text.length > 40) return;

                // Mots ENTIERS uniquement — \b évite de matcher "désinscription" sur "inscription"
                const isLogin    = /\b(connexion|se\s+connecter)\b/i.test(text);
                const isRegister = /\b(inscription|s['’]inscrire|cr[ée]er\s+un\s+compte)\b/i.test(text);

                if (isLogin) {
                    e.preventDefault();
                    window.dispatchEvent(new CustomEvent('open-auth-modal'));
                    window.dispatchEvent(new CustomEvent('set-auth-view', { detail: { view: 'login' } }));
                } else if (isRegister) {
                    e.preventDefault();
                    window.dispatchEvent(new CustomEvent('open-auth-modal'));
                    window.dispatchEvent(new CustomEvent('set-auth-view', { detail: { view: 'register' } }));
                }
            });

            // Exposer une fonction globale pour ouvrir le modal (compatibilité)
            window.openAuthModal = function() {
                window.dispatchEvent(new CustomEvent('open-auth-modal'));
                window.dispatchEvent(new CustomEvent('set-auth-view', { detail: { view: 'login' } }));
            };
        });
    </script>
    @stack('scripts')

    {{-- ===================== Global Search Modal (Ctrl+K) ===================== --}}
    <style>
        @keyframes ka-search-spin { to { transform: rotate(360deg); } }
        #searchModal kbd { padding: 2px 6px; border-radius: 4px; background: #fff; border: 1px solid #E2E8F0; font-family: ui-monospace, monospace; font-size: 10px; color: #475569; }
        .ka-search-suggest:hover { background: #F1F5F9 !important; border-color: #CBD5E1 !important; }
        .ka-search-cat:hover     { background: rgba(78,205,196,0.08) !important; border-color: rgba(68,160,141,0.40) !important; color: #0F766E !important; }
        .ka-search-close:hover   { background: #E2E8F0 !important; }
        .ka-search-cta:hover     { color: #0F766E !important; }
    </style>
    <div id="searchModal"
         style="position: fixed; inset: 0; z-index: 1000; display: none;"
         role="dialog" aria-modal="true" aria-labelledby="searchModalTitle">
        {{-- backdrop --}}
        <div data-search-close id="searchBackdrop"
             style="position: absolute; inset: 0; background: rgba(2,6,23,0.72);
                    backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
                    opacity: 0; transition: opacity .2s ease;"></div>

        {{-- wrapper --}}
        <div style="position: relative; height: 100%; width: 100%;
                    display: flex; align-items: flex-start; justify-content: center;
                    padding: clamp(40px, 8vh, 110px) 16px 24px; overflow-y: auto;">

            {{-- panel --}}
            <div id="searchPanel"
                 style="position: relative; width: 100%; max-width: 640px;
                        background: #ffffff; border-radius: 18px;
                        box-shadow: 0 50px 100px -20px rgba(2,6,23,0.55), 0 0 0 1px rgba(15,23,42,0.06);
                        opacity: 0; transform: translateY(-8px);
                        transition: opacity .2s ease, transform .2s ease;
                        overflow: hidden; display: flex; flex-direction: column; max-height: 80vh;
                        font-family: 'Inter','Figtree',sans-serif;">

                {{-- Header / input --}}
                <div style="display: flex; align-items: center; gap: 12px;
                            padding: 14px 16px; border-bottom: 1px solid #F1F5F9;
                            flex-shrink: 0;">
                    <svg style="width: 20px; height: 20px; color: #94A3B8; flex-shrink: 0;"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input id="searchInput" type="text" autocomplete="off" spellcheck="false"
                           placeholder="Rechercher Netflix, Apple, Spotify…"
                           style="flex: 1; min-width: 0;
                                  background: transparent; border: 0; outline: none;
                                  color: #0F172A; font-size: 16px; font-weight: 500;
                                  font-family: inherit;">
                    <span id="searchSpinner"
                          style="display: none; width: 16px; height: 16px; flex-shrink: 0;
                                 border: 2px solid #E2E8F0; border-top-color: #44A08D;
                                 border-radius: 50%; animation: ka-search-spin .8s linear infinite;"></span>
                    <button type="button" data-search-close class="ka-search-close"
                            style="flex-shrink: 0; padding: 4px 8px; border-radius: 6px;
                                   background: #F1F5F9; border: 0; cursor: pointer;
                                   color: #64748B; font-size: 10px; font-weight: 700; font-family: ui-monospace, monospace;
                                   transition: background .15s;">
                        ESC
                    </button>
                </div>

                {{-- Results / suggestions --}}
                <div id="searchResults" style="flex: 1; min-height: 0; overflow-y: auto; padding: 12px;">

                    {{-- Default state (suggestions) --}}
                    <div id="searchDefault" style="padding: 8px;">
                        <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em;
                                    color: #94A3B8; margin: 0 8px 10px;">Suggestions populaires</div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px;">
                            @foreach (['Netflix', 'Spotify', 'Apple', 'PlayStation', 'Amazon', 'Steam'] as $suggestion)
                                <button type="button" data-search-suggestion="{{ $suggestion }}" class="ka-search-suggest"
                                        style="display: flex; align-items: center; gap: 8px;
                                               padding: 10px 12px; border-radius: 10px;
                                               background: #F8FAFC; border: 1px solid #E2E8F0;
                                               color: #334155; font-size: 14px; font-weight: 500;
                                               text-align: left; cursor: pointer; transition: all .15s;
                                               font-family: inherit;">
                                    <svg style="width: 14px; height: 14px; color: #94A3B8; flex-shrink: 0;"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                    {{ $suggestion }}
                                </button>
                            @endforeach
                        </div>

                        <div style="margin-top: 20px; padding: 0 8px;">
                            <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em;
                                        color: #94A3B8; margin-bottom: 10px;">Catégories</div>
                            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                @foreach ([
                                    ['name' => 'Divertissement', 'emoji' => '🎬', 'id' => 1],
                                    ['name' => 'Jeux Vidéo',     'emoji' => '🎮', 'id' => 2],
                                    ['name' => 'Musique',        'emoji' => '🎵', 'id' => 3],
                                    ['name' => 'Shopping',       'emoji' => '🛍️', 'id' => 4],
                                ] as $cat)
                                    <a href="{{ route('category', $cat['id']) }}" class="ka-search-cat"
                                       style="display: inline-flex; align-items: center; gap: 6px;
                                              padding: 6px 12px; border-radius: 9999px;
                                              background: #ffffff; border: 1px solid #E2E8F0;
                                              color: #475569; font-size: 12px; font-weight: 500;
                                              text-decoration: none; transition: all .15s;">
                                        <span>{{ $cat['emoji'] }}</span> {{ $cat['name'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Live results container (filled by JS) --}}
                    <div id="searchList" style="display: none;"></div>

                    {{-- Empty state --}}
                    <div id="searchEmpty" style="display: none; padding: 40px 20px; text-align: center;">
                        <div style="width: 48px; height: 48px; border-radius: 14px;
                                    background: #F1F5F9; margin: 0 auto 12px;
                                    display: flex; align-items: center; justify-content: center;
                                    color: #94A3B8;">
                            <svg style="width: 22px; height: 22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div style="font-size: 14px; font-weight: 600; color: #334155;">Aucun résultat</div>
                        <div style="font-size: 12px; color: #94A3B8; margin-top: 4px;">Essayez avec un autre nom de marque.</div>
                    </div>
                </div>

                {{-- Footer --}}
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;
                            padding: 10px 16px; background: #F8FAFC; border-top: 1px solid #F1F5F9;
                            font-size: 11px; color: #64748B; flex-shrink: 0;">
                    <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                        <span style="display: inline-flex; align-items: center; gap: 6px;"><kbd>↵</kbd> Ouvrir</span>
                        <span style="display: inline-flex; align-items: center; gap: 6px;"><kbd>↑↓</kbd> Naviguer</span>
                    </div>
                    <a href="{{ route('boutique') }}" id="searchSeeAll" class="ka-search-cta"
                       style="font-weight: 600; color: #44A08D; text-decoration: none; transition: color .15s;">
                        Voir toute la boutique →
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal      = document.getElementById('searchModal');
            const backdrop   = document.getElementById('searchBackdrop');
            const panel      = document.getElementById('searchPanel');
            const input      = document.getElementById('searchInput');
            const spinner    = document.getElementById('searchSpinner');
            const list       = document.getElementById('searchList');
            const emptyState = document.getElementById('searchEmpty');
            const defaultState = document.getElementById('searchDefault');
            if (!modal || !input) return;

            const cardTypeRoute = "{{ url('/card-type') }}";
            const searchPageRoute = "{{ route('search') }}";
            const apiProductsRoute = "{{ route('api.products') }}";

            let activeIndex = -1;
            let currentResults = [];
            let debounceTimer = null;
            let abortCtrl = null;

            function open() {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
                requestAnimationFrame(() => {
                    backdrop.style.opacity = '1';
                    panel.style.opacity = '1';
                    panel.style.transform = 'translateY(0)';
                    input.focus();
                    input.select();
                });
            }

            function close() {
                backdrop.style.opacity = '0';
                panel.style.opacity = '0';
                panel.style.transform = 'translateY(-8px)';
                setTimeout(() => {
                    modal.style.display = 'none';
                    document.body.style.overflow = '';
                    input.value = '';
                    showDefault();
                }, 180);
            }

            function showDefault() {
                defaultState.style.display = '';
                list.style.display = 'none';
                emptyState.style.display = 'none';
                list.innerHTML = '';
                activeIndex = -1;
                currentResults = [];
            }

            function showLoading() {
                spinner.style.display = 'inline-block';
            }
            function hideLoading() {
                spinner.style.display = 'none';
            }

            function brandColor(name) {
                const palette = {
                    netflix: '#E50914', spotify: '#1DB954', apple: '#000000',
                    itunes: '#D60017', playstation: '#003791', xbox: '#107C10',
                    amazon: '#FF9900', google: '#01875F', steam: '#171A21',
                    uber: '#000000', roblox: '#00A2FF', nintendo: '#E60012',
                    disney: '#0E47A1',
                };
                const lower = (name || '').toLowerCase();
                for (const k in palette) if (lower.includes(k)) return palette[k];
                const fallbacks = ['#0F172A', '#44A08D', '#0EA5E9', '#7C3AED', '#DC2626', '#EA580C', '#059669'];
                let h = 0;
                for (let i = 0; i < (name || '').length; i++) h = ((h << 5) - h) + name.charCodeAt(i);
                return fallbacks[Math.abs(h) % fallbacks.length];
            }

            function renderResults(items) {
                currentResults = items;
                activeIndex = items.length ? 0 : -1;
                defaultState.style.display = 'none';

                if (!items.length) {
                    list.style.display = 'none';
                    emptyState.style.display = 'block';
                    return;
                }
                emptyState.style.display = 'none';
                list.style.display = 'flex';
                list.style.flexDirection = 'column';
                list.style.gap = '2px';

                list.innerHTML = items.map((p, i) => {
                    const ct = p.cardType || p.brand || {};
                    const name = ct.name || p.name || 'Carte';
                    const id = ct.internalId || ct.id || p.id;
                    const logo = ct.logoUrl || ct.logo || null;
                    // p.price est un objet {min, max, currencyCode} renvoyé par afrikard,
                    // pas un nombre. On extrait min, sinon fallback sur d'autres champs.
                    const priceObj = p.price && typeof p.price === 'object' ? p.price : null;
                    const rawPrice = priceObj
                        ? (priceObj.min ?? priceObj.max ?? null)
                        : (typeof p.price === 'number' ? p.price : (p.minPrice || p.minFaceValue || null));
                    const price = (rawPrice !== null && !isNaN(rawPrice)) ? Number(rawPrice) : null;
                    const currency = (priceObj && priceObj.currencyCode) || p.currency || 'XAF';
                    const color = brandColor(name);
                    const initial = name.charAt(0).toUpperCase();
                    const href = `${cardTypeRoute}/${id}`;
                    const initials = `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:${color};color:#fff;font-weight:700;font-size:14px;">${initial}</div>`;
                    const logoHtml = logo
                        ? `<img src="${logo}" alt="${name}" loading="lazy" onerror="this.outerHTML=this.dataset.fallback" data-fallback='${initials.replace(/'/g, "&#39;")}' style="width:100%;height:100%;object-fit:contain;background:#fff;">`
                        : initials;

                    return `
                        <a href="${href}" data-result-index="${i}" class="search-result-item"
                           style="display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:10px;text-decoration:none;color:inherit;cursor:pointer;transition:background .15s;">
                            <div style="width:40px;height:40px;border-radius:12px;border:1px solid #E2E8F0;overflow:hidden;flex-shrink:0;box-shadow:0 1px 2px rgba(15,23,42,0.04);">${logoHtml}</div>
                            <div style="min-width:0;flex:1;">
                                <div style="font-size:14px;font-weight:600;color:#0F172A;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${name}</div>
                                <div style="font-size:12px;color:#64748B;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${p.name || 'Carte numérique'}</div>
                            </div>
                            ${price ? `<div style="font-size:14px;font-weight:700;color:#0F172A;font-variant-numeric:tabular-nums;flex-shrink:0;">${(window.convertToFCFA ? window.convertToFCFA(price, currency) : price).toLocaleString('fr-FR')} <span style="font-size:10px;color:#94A3B8;font-weight:500;">FCFA</span></div>` : ''}
                            <svg style="width:16px;height:16px;color:#CBD5E1;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>`;
                }).join('');

                highlightActive();
            }

            function highlightActive() {
                list.querySelectorAll('.search-result-item').forEach((el, i) => {
                    if (i === activeIndex) {
                        el.style.background = '#F1F5F9';
                        el.scrollIntoView({ block: 'nearest' });
                    } else {
                        el.style.background = '';
                    }
                });
            }

            async function runSearch(q) {
                if (!q || q.trim().length < 2) {
                    showDefault();
                    hideLoading();
                    return;
                }
                if (abortCtrl) abortCtrl.abort();
                abortCtrl = new AbortController();
                showLoading();
                try {
                    const url = `${apiProductsRoute}?search=${encodeURIComponent(q)}&size=12`;
                    const res = await fetch(url, { signal: abortCtrl.signal, headers: { Accept: 'application/json' } });
                    if (!res.ok) throw new Error('http_' + res.status);
                    const data = await res.json();
                    const items = (data && data.success && Array.isArray(data.data)) ? data.data : [];
                    renderResults(items);
                } catch (e) {
                    if (e.name !== 'AbortError') {
                        list.style.display = 'none';
                        defaultState.style.display = 'none';
                        emptyState.style.display = 'block';
                    }
                } finally {
                    hideLoading();
                }
            }

            // Input handler (debounced) + maintien du lien "Voir toute la boutique"
            const seeAllLink = document.getElementById('searchSeeAll');
            const boutiqueBaseUrl = "{{ route('boutique') }}";
            const updateSeeAllLink = (q) => {
                if (!seeAllLink) return;
                const trimmed = (q || '').trim();
                if (trimmed) {
                    seeAllLink.href = `${boutiqueBaseUrl}?search=${encodeURIComponent(trimmed)}`;
                    seeAllLink.textContent = `Voir tous les résultats pour "${trimmed}" →`;
                } else {
                    seeAllLink.href = boutiqueBaseUrl;
                    seeAllLink.textContent = 'Voir toute la boutique →';
                }
            };

            input.addEventListener('input', (e) => {
                const q = e.target.value;
                updateSeeAllLink(q);
                if (debounceTimer) clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => runSearch(q), 220);
            });

            // Keyboard navigation
            input.addEventListener('keydown', (e) => {
                if (!currentResults.length) {
                    if (e.key === 'Enter' && input.value.trim()) {
                        // Va direct à la boutique avec le filtre search appliqué
                        window.location.href = `${boutiqueBaseUrl}?search=${encodeURIComponent(input.value.trim())}`;
                    }
                    return;
                }
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    activeIndex = (activeIndex + 1) % currentResults.length;
                    highlightActive();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    activeIndex = (activeIndex - 1 + currentResults.length) % currentResults.length;
                    highlightActive();
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    const link = list.querySelector(`[data-result-index="${activeIndex}"]`);
                    if (link) window.location.href = link.getAttribute('href');
                }
            });

            // Triggers
            // - data-search-close      → ferme le modal
            // - data-search-suggestion → pré-remplit + lance la recherche (et ouvre si fermé)
            // - data-search-trigger    → ouvre le modal
            document.addEventListener('click', (e) => {
                const closeBtn = e.target.closest('[data-search-close]');
                if (closeBtn) { e.preventDefault(); close(); return; }

                const suggest = e.target.closest('[data-search-suggestion]');
                if (suggest) {
                    e.preventDefault();
                    const isOpen = modal.style.display === 'block';
                    if (!isOpen) open();
                    input.value = suggest.dataset.searchSuggestion;
                    runSearch(input.value);
                    setTimeout(() => input.focus(), 100);
                    return;
                }

                const trigger = e.target.closest('[data-search-trigger]');
                if (trigger) { e.preventDefault(); open(); }
            });

            // Ctrl+K / Cmd+K + ESC
            document.addEventListener('keydown', (e) => {
                const isOpen = modal.style.display !== 'none' && modal.style.display !== '';
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                    e.preventDefault();
                    isOpen ? close() : open();
                } else if (e.key === '/' && !isOpen) {
                    const tag = (document.activeElement && document.activeElement.tagName) || '';
                    if (!['INPUT', 'TEXTAREA', 'SELECT'].includes(tag)) {
                        e.preventDefault();
                        open();
                    }
                } else if (e.key === 'Escape' && isOpen) {
                    e.preventDefault();
                    close();
                }
            });
        })();
    </script>
    {{-- ===================== /Global Search Modal ===================== --}}

    <!-- Checkout/Payment Modal -->
    <div x-data="checkoutModal()"
         x-show="isOpen"
         x-on:open-checkout-modal.window="openModal()"
         x-on:keydown.escape.window="closeModal()"
         class="relative z-[60]"
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true"
         style="display: none;">
        
        <!-- Background backdrop -->
        <div x-show="isOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" 
             @click="closeModal()"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                
                <!-- Modal panel -->
                <div x-show="isOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 w-full max-w-4xl h-[85vh]">
                    
                    <!-- Close button -->
                    <button @click="closeModal()" class="absolute top-4 right-4 z-50 bg-white/80 backdrop-blur rounded-full p-2 text-gray-500 hover:text-gray-700 hover:bg-white shadow-sm transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>

                    <!-- Loading State -->
                    <div x-show="loading" class="absolute inset-0 flex flex-col items-center justify-center bg-white z-40 p-8 text-center">
                        <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-kardafrica-primary mb-6"></div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Redirection vers E-Billing...</h3>
                        <p class="text-gray-500 font-medium max-w-md mx-auto">Veuillez patienter, vous allez être redirigé vers le portail de paiement sécurisé pour finaliser votre transaction.</p>
                        <p class="text-sm text-gray-400 mt-8">Ne fermez pas cette fenêtre.</p>
                    </div>

                    <!-- Error State -->
                    <div x-show="!loading && error" class="absolute inset-0 flex flex-col items-center justify-center bg-white z-30 p-8 text-center">
                        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4 text-red-600">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Erreur d'initialisation</h3>
                        <p x-text="error" class="text-gray-500 mb-6"></p>
                        <button @click="closeModal()" class="px-6 py-2 bg-gray-900 text-white rounded-xl font-medium hover:bg-gray-800 transition-colors">
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('checkoutModal', () => ({
                isOpen: false,
                loading: false,
                iframeUrl: '',
                error: null,
                user: null,

                async openModal() {
                    // Check user
                    const user = window.currentUser;
                    if (!user) {
                        if (typeof openAuthModal === 'function') {
                            openAuthModal();
                        } else {
                            window.location.href = '/login';
                        }
                        return;
                    }
                    this.user = user;
                    this.isOpen = true;
                    this.loading = true;
                    this.error = null;
                    
                    try {
                        // Demarre le paiement : creation de l'Order + appel futursowax cote serveur
                        const response = await fetch('{{ route("checkout.start") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                name:  this.user.name,
                                email: this.user.email,
                                phone: this.user.phone,
                            }),
                        });

                        // Securite : si on recoit du HTML (redirection auth) au lieu de JSON
                        const contentType = response.headers.get('content-type') || '';
                        if (!contentType.includes('application/json')) {
                            if (response.status === 401 || response.status === 419) {
                                window.location.href = '/login?redirect=checkout';
                                return;
                            }
                            throw new Error('Reponse inattendue du serveur (status ' + response.status + ').');
                        }

                        const data = await response.json();

                        if (response.ok && data.success) {
                            // Redirige vers le portail de paiement futursowax
                            window.location.href = data.portal_url;
                        } else {
                            throw new Error(data.message || 'Impossible d\'initialiser le paiement.');
                        }

                    } catch (error) {
                        console.error('Checkout error:', error);
                        this.error = error.message;
                        this.loading = false;
                    }
                },

                closeModal() {
                    this.isOpen = false;
                    this.iframeUrl = '';
                    // Reload page on close if payment might have succeeded? 
                    // Or let user check status manually.
                    // Ideally, we listen for a message from the iframe, but cross-origin might block it.
                }
            }));
        });


        // Global bridge functions
        window.openCheckoutModal = function() {
            window.dispatchEvent(new CustomEvent('open-checkout-modal'));
        };

        window.closeCheckoutModal = function() {
            window.dispatchEvent(new CustomEvent('keydown', { key: 'Escape' })); // Mock escape to close or just rely on Alpine
            // Better: Dispatch a custom close event if we added listener, but for now user action closes it.
        };
        
        // Deprecated/Compatibility
        window.startPayment = function() {
            window.openCheckoutModal();
        };

        // ===== Newsletter form (footer) =====
        document.addEventListener('alpine:init', () => {
            Alpine.data('newsletterForm', () => ({
                email: '',
                loading: false,
                success: false,
                error: false,
                message: '',

                async submit() {
                    if (!this.email) return;
                    this.loading = true;
                    this.error = false;
                    this.success = false;
                    this.message = '';
                    try {
                        const res = await fetch('{{ route('newsletter.subscribe') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ email: this.email, source: 'footer' }),
                        });
                        const ct = res.headers.get('content-type') || '';
                        if (!ct.includes('application/json')) {
                            throw new Error('Reponse inattendue');
                        }
                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.success = true;
                            this.message = data.message;
                        } else {
                            this.error = true;
                            this.message = data.message || (data.errors?.email?.[0]) || 'Une erreur est survenue.';
                        }
                    } catch (e) {
                        this.error = true;
                        this.message = e.message || 'Erreur réseau.';
                    } finally {
                        this.loading = false;
                    }
                },
            }));
        });
    </script>

    {{-- ================================================================
         PASSWORD GATE MODAL (gate les actions sensibles type reveal code/PIN)
         Usage cote client :
            window.requireUnlock(callback)
         Si l'utilisateur a deja entre son mot de passe dans les 5 dernieres minutes,
         le callback est execute immediatement. Sinon, ouvre une modal qui demande
         le mot de passe et appelle le callback en cas de succes.
         ================================================================ --}}
    @auth
    <div x-data="passwordGate()"
         x-show="open"
         x-transition.opacity
         @keydown.escape.window="close()"
         @open-password-gate.window="openWith($event.detail?.callback)"
         class="fixed inset-0 z-[1010] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         style="display:none;">

        <div @click.outside="close()"
             class="bg-white rounded-2xl shadow-pop w-full max-w-sm overflow-hidden">

            <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3 bg-gradient-to-br from-slate-50 to-white">
                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-[#4ECDC4] to-[#44A08D] text-white flex items-center justify-center shadow-md shadow-[#44A08D]/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-display text-base font-bold text-slate-900 leading-tight">Confirmation requise</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Entrez votre mot de passe pour révéler</p>
                </div>
                <button @click="close()" type="button" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition" aria-label="Fermer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form @submit.prevent="verify()" class="p-5" data-no-loader>
                <label class="block text-[10px] uppercase tracking-wider font-bold text-slate-400 mb-2">Mot de passe</label>
                <div class="relative">
                    <input :type="passwordVisible ? 'text' : 'password'"
                           x-model="password"
                           x-ref="passwordInput"
                           autocomplete="current-password"
                           placeholder="••••••••"
                           class="w-full pl-3 pr-10 py-2.5 rounded-lg border border-slate-200 bg-slate-50 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:border-[#44A08D] focus:ring-2 focus:ring-[#44A08D]/20 focus:outline-none transition">
                    <button type="button" @click="passwordVisible = !passwordVisible" class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded text-slate-400 hover:text-slate-700 transition" tabindex="-1">
                        <svg x-show="!passwordVisible" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="passwordVisible" x-cloak class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                </div>
                <p x-show="error" x-cloak class="mt-2 text-xs text-rose-600 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span x-text="error"></span>
                </p>

                <div class="flex items-center gap-2 mt-4">
                    <button type="button" @click="close()" class="flex-1 px-4 py-2.5 rounded-lg border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition active:scale-95">
                        Annuler
                    </button>
                    <button type="submit" :disabled="loading || !password"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg bg-[#44A08D] hover:bg-[#3d9180] disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-bold shadow-md shadow-[#44A08D]/20 transition active:scale-95">
                        <svg x-show="loading" x-cloak class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <span x-show="!loading">Confirmer</span>
                        <span x-show="loading" x-cloak>Vérification...</span>
                    </button>
                </div>

                <p class="mt-3 text-[10px] text-slate-400 text-center">
                    Verrouillage automatique après 5 minutes d'inactivité.
                </p>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('passwordGate', () => ({
                open: false,
                password: '',
                passwordVisible: false,
                loading: false,
                error: null,
                pendingCallback: null,

                openWith(callback) {
                    this.pendingCallback = typeof callback === 'function' ? callback : null;
                    this.password = '';
                    this.error = null;
                    this.passwordVisible = false;
                    this.open = true;
                    this.$nextTick(() => this.$refs.passwordInput?.focus());
                },

                close() {
                    this.open = false;
                    this.password = '';
                    this.error = null;
                    this.pendingCallback = null;
                },

                async verify() {
                    if (!this.password) return;
                    this.loading = true;
                    this.error = null;
                    try {
                        const res = await fetch('{{ route('verify-password') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ password: this.password }),
                        });
                        const ct = res.headers.get('content-type') || '';
                        if (!ct.includes('application/json')) {
                            throw new Error('Reponse inattendue (status ' + res.status + ')');
                        }
                        const data = await res.json();
                        if (res.ok && data.ok) {
                            // Unlock pour 5 minutes
                            const expireAt = Date.now() + 5 * 60 * 1000;
                            sessionStorage.setItem('cards-unlock-exp', String(expireAt));
                            window.dispatchEvent(new CustomEvent('cards-unlocked'));
                            const cb = this.pendingCallback;
                            this.close();
                            if (cb) cb();
                        } else {
                            this.error = data.message || 'Mot de passe incorrect.';
                        }
                    } catch (e) {
                        this.error = e.message || 'Erreur lors de la verification.';
                    } finally {
                        this.loading = false;
                    }
                },
            }));
        });

        // Helpers globaux
        window.cardsAreUnlocked = function () {
            const exp = sessionStorage.getItem('cards-unlock-exp');
            return exp && parseInt(exp, 10) > Date.now();
        };
        window.requireUnlock = function (callback) {
            if (window.cardsAreUnlocked()) {
                if (typeof callback === 'function') callback();
                return;
            }
            window.dispatchEvent(new CustomEvent('open-password-gate', { detail: { callback } }));
        };
    </script>
    @endauth
</body>
</html> 