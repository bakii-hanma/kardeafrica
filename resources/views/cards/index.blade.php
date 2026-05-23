@extends('layouts.app')

@section('title', 'Mes Cartes - KardAfrica')

@php
    $brandPalette = [
        'Netflix' => '#E50914', 'Spotify' => '#1DB954', 'Apple' => '#000000',
        'iTunes' => '#D60017', 'PlayStation' => '#003791', 'Xbox' => '#107C10',
        'Amazon' => '#FF9900', 'Google' => '#01875F', 'Steam' => '#171A21',
        'Roblox' => '#00A2FF', 'Nintendo' => '#E60012', 'Disney' => '#0E47A1',
        'StarzPlay' => '#7C3AED', 'Talabat' => '#FF5A00', 'HUAWEI' => '#C7000B', 'IKEA' => '#0058A3',
    ];
    $brandColorFor = function ($name) use ($brandPalette) {
        foreach ($brandPalette as $key => $color) {
            if (stripos($name, $key) !== false) return $color;
        }
        $palette = ['#0F172A', '#44A08D', '#0EA5E9', '#7C3AED', '#DC2626', '#EA580C', '#059669'];
        $hash = 0;
        for ($i = 0; $i < strlen($name); $i++) $hash = ord($name[$i]) + (($hash << 5) - $hash);
        $idx = (($hash % count($palette)) + count($palette)) % count($palette);
        return $palette[$idx];
    };

    $activeFilters = (int)!empty(request('search')) + (int)!empty(request('status'));
@endphp

@section('content')
<div class="min-h-screen bg-[#FAFAF7] pb-20">

    {{-- ================================================================
         BREADCRUMB
         ================================================================ --}}
    <div class="bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between">
            <nav class="flex items-center gap-1.5 text-xs text-slate-500">
                <a href="{{ route('home') }}" class="hover:text-[#44A08D] transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Accueil
                </a>
                <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-900 font-medium">Mes cartes</span>
            </nav>
            <a href="{{ url('/orders') }}" class="hidden sm:inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-[#44A08D] transition">
                Mes commandes
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 md:pt-10">

        {{-- ================================================================
             HEADER + STATS
             ================================================================ --}}
        <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-4 md:items-end mb-6">
            <div>
                <h1 class="font-display text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">Mes cartes</h1>
                <p class="text-sm text-slate-500 mt-1.5">
                    Toutes vos cartes cadeaux livrées, code et PIN à portée de clic.
                </p>
            </div>
            <a href="{{ route('boutique') }}"
               class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-[#44A08D] hover:bg-[#3d9180] text-white text-sm font-semibold shadow-lg shadow-[#44A08D]/25 active:scale-95 transition shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Acheter une carte
            </a>
        </div>

        {{-- Banner : commandes payees mais sans cartes (afrikard a echoue) --}}
        @if(isset($pendingOrders) && $pendingOrders->count() > 0)
            <div class="mb-6 space-y-3">
                @foreach($pendingOrders as $pendingOrder)
                    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="flex items-start gap-3 flex-1 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center text-amber-700 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold text-amber-900">
                                    Commande <span class="font-mono">#{{ $pendingOrder->order_number }}</span> payée mais cartes non livrées
                                </div>
                                <p class="text-xs text-amber-800 mt-0.5">
                                    {{ $pendingOrder->orderItems->count() }} article{{ $pendingOrder->orderItems->count() > 1 ? 's' : '' }} ·
                                    {{ number_format($pendingOrder->total_amount, 0, ',', ' ') }} FCFA ·
                                    Le fournisseur n'a pas répondu lors du paiement.
                                </p>
                            </div>
                        </div>
                        <form action="{{ route('orders.retry-checkout', $pendingOrder) }}" method="POST" class="shrink-0">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-md transition active:scale-95">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Relancer la livraison
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Stats compact --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            <div class="bg-white rounded-xl border border-slate-200 shadow-card p-4">
                <div class="flex items-center gap-2.5 mb-1.5">
                    <div class="w-8 h-8 rounded-lg bg-teal-50 text-[#44A08D] flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Total</span>
                </div>
                <div class="font-display text-2xl font-bold text-slate-900 tabular-nums">{{ $stats['total'] }}</div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 shadow-card p-4">
                <div class="flex items-center gap-2.5 mb-1.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Actives</span>
                </div>
                <div class="font-display text-2xl font-bold text-slate-900 tabular-nums">{{ $stats['active'] }}</div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 shadow-card p-4">
                <div class="flex items-center gap-2.5 mb-1.5">
                    <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7M9 7h6"/></svg>
                    </div>
                    <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Utilisées</span>
                </div>
                <div class="font-display text-2xl font-bold text-slate-900 tabular-nums">{{ $stats['used'] }}</div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 shadow-card p-4">
                <div class="flex items-center gap-2.5 mb-1.5">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
                    </div>
                    <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Valeur</span>
                </div>
                <div class="font-display text-base md:text-lg font-bold text-slate-900 tabular-nums leading-tight">
                    {{ number_format($stats['value_xaf'], 0, ',', ' ') }}
                    <span class="text-xs text-slate-400 font-normal">FCFA</span>
                </div>
            </div>
        </div>

        {{-- ================================================================
             FILTRES
             ================================================================ --}}
        <form action="{{ route('cards.index') }}" method="GET" class="bg-white rounded-2xl border border-slate-200 shadow-card p-2 mb-6 flex flex-col md:flex-row items-stretch md:items-center gap-2" data-no-loader>
            {{-- Search --}}
            <div class="relative flex-1">
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Nom de carte ou marque…"
                       class="w-full pl-10 pr-3 py-2.5 bg-transparent border-0 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:ring-0 focus:outline-none">
            </div>

            <div class="hidden md:block w-px h-8 bg-slate-100"></div>

            {{-- Status --}}
            <div class="relative">
                <select name="status" onchange="this.form.submit()"
                        class="w-full md:w-44 pl-3 pr-9 py-2.5 bg-transparent border-0 rounded-lg text-sm text-slate-700 font-medium focus:ring-0 cursor-pointer appearance-none">
                    <option value="">Tous les statuts</option>
                    <option value="active"  {{ request('status') === 'active'  ? 'selected' : '' }}>Actives</option>
                    <option value="used"    {{ request('status') === 'used'    ? 'selected' : '' }}>Utilisées</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expirées</option>
                </select>
                <svg class="w-3.5 h-3.5 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </div>

            <div class="hidden md:block w-px h-8 bg-slate-100"></div>

            {{-- Sort --}}
            <div class="relative">
                <select name="sort" onchange="this.form.submit()"
                        class="w-full md:w-48 pl-3 pr-9 py-2.5 bg-transparent border-0 rounded-lg text-sm text-slate-700 font-medium focus:ring-0 cursor-pointer appearance-none">
                    <option value="latest"     {{ request('sort', 'latest') === 'latest'     ? 'selected' : '' }}>Plus récentes</option>
                    <option value="oldest"     {{ request('sort') === 'oldest'              ? 'selected' : '' }}>Plus anciennes</option>
                    <option value="price_desc" {{ request('sort') === 'price_desc'          ? 'selected' : '' }}>Valeur décroissante</option>
                    <option value="price_asc"  {{ request('sort') === 'price_asc'           ? 'selected' : '' }}>Valeur croissante</option>
                </select>
                <svg class="w-3.5 h-3.5 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </div>

            @if($activeFilters > 0)
                <a href="{{ route('cards.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-rose-600 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    Effacer
                </a>
            @endif

            {{-- Mobile submit --}}
            <button type="submit" class="md:hidden inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold transition">
                Appliquer
            </button>
        </form>

        {{-- ================================================================
             GRILLE
             ================================================================ --}}
        @if($cards->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($cards as $i => $card)
                    @php
                        $cardCode   = $card->card_code ?? $card->getRawOriginal('card_code') ?? '';
                        $cardPin    = $card->pin ?? $card->getRawOriginal('pin') ?? null;
                        $brandColor = $brandColorFor($card->brand ?? $card->name ?? '');
                        $statusBadge = match($card->status) {
                            'active'  => ['label' => 'Active',   'cls' => 'bg-emerald-500/30 border-emerald-300/40 text-white'],
                            'used'    => ['label' => 'Utilisée', 'cls' => 'bg-slate-500/40 text-white'],
                            'expired' => ['label' => 'Expirée',  'cls' => 'bg-rose-500/40 text-white'],
                            default   => ['label' => ucfirst($card->status), 'cls' => 'bg-white/20 text-white'],
                        };
                    @endphp

                    <article class="card-row" style="animation-delay: {{ ($i % 12) * 60 }}ms"
                             x-data="{ codeShown: false, pinShown: false, copiedField: null,
                                       copy(text, field) {
                                           navigator.clipboard.writeText(text).then(() => {
                                               this.copiedField = field;
                                               setTimeout(() => this.copiedField = null, 1800);
                                           });
                                       } }">

                        <div class="bg-white rounded-2xl border border-slate-200 shadow-card overflow-hidden hover:shadow-card-hover transition-all duration-300">

                            {{-- Visuel carte --}}
                            <div style="background-color: {{ $brandColor }}" class="relative h-40 p-5 overflow-hidden">
                                <svg class="absolute inset-0 w-full h-full opacity-[0.08]" aria-hidden="true">
                                    <defs>
                                        <pattern id="uc-{{ $card->id }}" width="32" height="32" patternUnits="userSpaceOnUse">
                                            <circle cx="16" cy="16" r="13" fill="none" stroke="white" stroke-width="1.2"/>
                                        </pattern>
                                    </defs>
                                    <rect width="100%" height="100%" fill="url(#uc-{{ $card->id }})"/>
                                </svg>
                                <div class="absolute -top-12 -right-12 w-40 h-40 rounded-full bg-white/15 blur-2xl"></div>

                                <div class="relative h-full flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-white/80 text-[10px] font-bold tracking-[0.2em] uppercase">Gift Card</span>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold backdrop-blur-sm border border-transparent {{ $statusBadge['cls'] }}">
                                                @if($card->status === 'active')
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-300"></span>
                                                @endif
                                                {{ $statusBadge['label'] }}
                                            </span>
                                        </div>
                                        <h3 class="font-display text-white font-bold text-2xl tracking-tight mt-1.5 leading-tight truncate">
                                            {{ $card->brand ?: explode(' ', $card->name)[0] }}
                                        </h3>
                                    </div>
                                    <div class="flex items-end justify-between">
                                        <div>
                                            <div class="text-white/60 text-[9px] font-bold uppercase tracking-wider">Prix payé</div>
                                            <div class="text-white font-display text-lg font-bold tabular-nums leading-none mt-0.5">
                                                {{ number_format($card->orderItem?->unit_price ?? 0, 0, ',', ' ') }} <span class="text-xs font-normal text-white/60">FCFA</span>
                                            </div>
                                            @if($card->face_value && $card->currency !== 'XAF')
                                                <div class="text-white/60 text-[9px] mt-0.5 tabular-nums">
                                                    {{ number_format($card->face_value, 0, ',', ' ') }} {{ $card->currency }} de crédit
                                                </div>
                                            @endif
                                        </div>
                                        <div class="w-9 h-6 rounded bg-gradient-to-br from-yellow-200/90 to-yellow-400/70 border border-yellow-300/40 shadow-inner"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Détails --}}
                            <div class="p-4 space-y-3">
                                <div class="flex items-start justify-between gap-2">
                                    <h4 class="text-sm font-semibold text-slate-900 line-clamp-1">{{ $card->name }}</h4>
                                </div>

                                {{-- Code --}}
                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-2.5">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Code</span>
                                        <div class="flex items-center gap-0.5">
                                            <button @click="codeShown ? codeShown = false : window.requireUnlock(() => codeShown = true)" type="button" class="p-1 rounded text-slate-400 hover:text-[#44A08D] hover:bg-white transition">
                                                <svg x-show="!codeShown" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <svg x-show="codeShown" x-cloak class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                            </button>
                                            <button @click="window.requireUnlock(() => copy('{{ $cardCode }}', 'code'))" type="button" class="p-1 rounded text-slate-400 hover:text-[#44A08D] hover:bg-white transition">
                                                <svg x-show="copiedField !== 'code'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                <svg x-show="copiedField === 'code'" x-cloak class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="font-mono text-xs font-bold text-slate-900 tracking-wider truncate"
                                         x-text="codeShown ? '{{ $cardCode }}' : '{{ str_repeat('•', max(8, strlen($cardCode))) }}'"></div>
                                </div>

                                {{-- PIN + Expiration --}}
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    @if($cardPin)
                                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-2.5">
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400">PIN</span>
                                                <button @click="pinShown ? pinShown = false : window.requireUnlock(() => pinShown = true)" type="button" class="p-0.5 rounded text-slate-400 hover:text-[#44A08D] transition">
                                                    <svg x-show="!pinShown" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    <svg x-show="pinShown" x-cloak class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                                </button>
                                            </div>
                                            <div class="font-mono text-xs font-bold text-slate-900 tabular-nums"
                                                 x-text="pinShown ? '{{ $cardPin }}' : '{{ str_repeat('•', strlen($cardPin)) }}'"></div>
                                        </div>
                                    @endif
                                    @if($card->expiration_date)
                                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-2.5">
                                            <div class="text-[10px] uppercase tracking-wider font-bold text-slate-400 mb-1">Expire</div>
                                            <div class="text-xs font-bold text-slate-900 tabular-nums">
                                                {{ \Carbon\Carbon::parse($card->expiration_date)->format('d/m/Y') }}
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                {{-- Footer --}}
                                <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                                    <span class="text-[10px] text-slate-500">
                                        Achetée le {{ $card->created_at->format('d/m/Y') }}
                                    </span>
                                    <button @click="window.requireUnlock(() => copy('{{ $cardCode }}', 'code'))" type="button"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-[#44A08D] hover:bg-[#3d9180] text-white text-[11px] font-semibold transition active:scale-95">
                                        <svg x-show="copiedField !== 'code'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        <svg x-show="copiedField === 'code'" x-cloak class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        <span x-show="copiedField !== 'code'">Copier</span>
                                        <span x-show="copiedField === 'code'" x-cloak>Copié !</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($cards->hasPages())
                <div class="mt-8">
                    {{ $cards->withQueryString()->links() }}
                </div>
            @endif

        @else
            {{-- Empty state --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-card text-center py-16 px-6">
                <div class="w-20 h-20 mx-auto mb-5 rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center">
                    <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
                <h3 class="font-display text-xl font-bold text-slate-900 mb-2">
                    @if($activeFilters > 0)
                        Aucune carte ne correspond
                    @else
                        Vous n'avez pas encore de cartes
                    @endif
                </h3>
                <p class="text-sm text-slate-500 max-w-md mx-auto mb-6">
                    @if($activeFilters > 0)
                        Essayez de modifier vos filtres ou
                        <a href="{{ route('cards.index') }}" class="text-[#44A08D] font-semibold hover:underline">effacez tous les filtres</a>.
                    @else
                        Achetez votre première carte cadeau et elle apparaîtra ici instantanément après le paiement.
                    @endif
                </p>
                <a href="{{ route('boutique') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#44A08D] hover:bg-[#3d9180] text-white text-sm font-semibold shadow-lg shadow-[#44A08D]/25 active:scale-95 transition">
                    Découvrir le catalogue
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        @endif
    </div>
</div>

<style>
    .card-row {
        opacity: 0;
        transform: translateY(16px);
        animation: card-slide-in 500ms cubic-bezier(0.22, 1, 0.36, 1) forwards;
    }
    @keyframes card-slide-in {
        to { opacity: 1; transform: translateY(0); }
    }
    @media (prefers-reduced-motion: reduce) {
        .card-row { animation: none; opacity: 1; transform: none; }
    }
</style>
@endsection
