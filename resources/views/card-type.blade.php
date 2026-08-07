@extends('layouts.app')

@section('title', $cardType['name'] . ' - KardAfrica')

@section('content')
@php
    // Brand color generation (overflow-safe)
    $brandPalette = [
        'Netflix' => '#E50914', 'Spotify' => '#1DB954', 'Apple' => '#000000',
        'iTunes' => '#D60017', 'PlayStation' => '#003791', 'Xbox' => '#107C10',
        'Amazon' => '#FF9900', 'Google' => '#01875F', 'Steam' => '#171A21',
        'Uber' => '#000000', 'Roblox' => '#00A2FF', 'Nintendo' => '#E60012',
        'Disney' => '#0E47A1', 'StarzPlay' => '#7C3AED', 'Talabat' => '#FF5A00',
        'HUAWEI' => '#C7000B', 'IKEA' => '#0058A3',
    ];

    $name = $cardType['name'] ?? '';
    $brandColor = '#1F2937';
    foreach ($brandPalette as $key => $color) {
        if (stripos($name, $key) !== false) { $brandColor = $color; break; }
    }
    if ($brandColor === '#1F2937') {
        $palette = ['#0F172A', '#44A08D', '#0EA5E9', '#7C3AED', '#DC2626', '#EA580C', '#059669'];
        $hash = 0;
        for ($i = 0; $i < strlen($name); $i++) $hash = ord($name[$i]) + (($hash << 5) - $hash);
        $idx = (($hash % count($palette)) + count($palette)) % count($palette);
        $brandColor = $palette[$idx];
    }

    // Light vs dark text on brand color (simple luminance)
    $rgb = sscanf($brandColor, '#%02x%02x%02x');
    $luminance = (0.299 * $rgb[0] + 0.587 * $rgb[1] + 0.114 * $rgb[2]) / 255;
    $textColor = $luminance > 0.7 ? '#0F172A' : '#FFFFFF';
@endphp

<div class="min-h-screen bg-[#FAFAF7] pb-32" x-data="productDetails()">

    {{-- ================================================================
         BREADCRUMB STRIP
         ================================================================ --}}
    <div class="bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between">
            <nav class="flex items-center gap-1.5 text-xs text-slate-500">
                <a href="{{ route('home') }}" class="hover:text-[#44A08D] transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Accueil
                </a>
                <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ route('boutique') }}" class="hover:text-[#44A08D] transition">Boutique</a>
                <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-900 font-medium truncate max-w-[200px]">{{ $cardType['name'] }}</span>
            </nav>

            <a href="{{ url()->previous() }}" class="hidden sm:inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-[#44A08D] transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Retour
            </a>
        </div>
    </div>

    {{-- ================================================================
         HERO — Card visual (left) + Selection (right)
         ================================================================ --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-12 relative">
        {{-- Ambient gradient --}}
        <div class="absolute inset-0 -z-10 overflow-hidden pointer-events-none">
            <div class="absolute -top-12 left-1/4 w-[600px] h-[400px] rounded-full blur-3xl opacity-30" style="background-color: {{ $brandColor }}20;"></div>
        </div>

        <div class="lg:grid lg:grid-cols-[minmax(0,_1fr)_minmax(0,_1.1fr)] lg:gap-12 items-start">

            {{-- ====== LEFT: 3D Floating Card ====== --}}
            <div class="mb-10 lg:mb-0 perspective-1000">
                <div class="relative w-full max-w-md mx-auto aspect-[1.55] transition-transform duration-700 transform-style-3d cursor-pointer"
                     :class="{'rotate-y-180': isFlipped}"
                     @click="isFlipped = !isFlipped">

                    {{-- ===== FRONT : design hybride (= proposition mai 2026) ===== --}}
                    <div class="absolute inset-0 backface-hidden rounded-[28px] shadow-pop overflow-hidden bg-white animate-float">
                        @include('partials._gift-card-visual', [
                            'brandLabel'  => $cardType['name'] ?? $name,
                            'brandColor'  => $brandColor,
                            'countryCode' => $cardType['countryCode'] ?? null,
                            'currency'    => $cardType['products'][0]['price']['currencyCode'] ?? null,
                            'compact'     => false,
                            'fill'        => true,   // remplit le parent (aspect-[1.55])
                            'logoUrl'     => $cardType['logoUrl'] ?? null,
                        ])
                    </div>

                    {{-- ===== BACK ===== --}}
                    <div class="absolute inset-0 backface-hidden rotate-y-180 rounded-[28px] shadow-pop overflow-hidden bg-gradient-to-br from-[#1F2937] via-[#0F172A] to-[#1F2937] flex flex-col items-center justify-center p-8 text-center relative">
                        {{-- Glow --}}
                        <div class="absolute -top-20 -left-20 w-64 h-64 rounded-full bg-[#44A08D]/30 blur-3xl"></div>
                        <div class="absolute -bottom-16 -right-16 w-48 h-48 rounded-full bg-[#4ECDC4]/20 blur-3xl"></div>

                        {{-- Pattern --}}
                        <svg class="absolute inset-0 w-full h-full opacity-[0.04]" aria-hidden="true">
                            <defs>
                                <pattern id="back-dots" width="24" height="24" patternUnits="userSpaceOnUse">
                                    <circle cx="2" cy="2" r="1" fill="white"/>
                                </pattern>
                            </defs>
                            <rect width="100%" height="100%" fill="url(#back-dots)"/>
                        </svg>

                        <div class="relative z-10">
                            {{-- Bande magnetique stylisee --}}
                            <div class="absolute -top-2 left-1/2 -translate-x-1/2 w-32 h-4 bg-black/40 rounded"></div>

                            <div class="mt-12">
                                <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" class="w-12 h-12 mx-auto mb-3 opacity-90" alt="">
                                <h3 class="font-display text-white font-bold text-2xl mb-1">KardAfrica</h3>
                                <p class="text-slate-300 text-xs max-w-[240px] mx-auto leading-relaxed">
                                    Le moyen le plus simple d'acheter des cartes cadeaux en Afrique.
                                </p>

                                {{-- Numero fictif --}}
                                <div class="mt-4 flex items-center justify-center gap-2 font-mono text-[#4ECDC4] text-sm">
                                    <span>****</span><span>****</span><span>****</span><span>1234</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Hint --}}
                <p class="text-center text-slate-400 text-xs mt-5 flex items-center justify-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    Touchez la carte pour la retourner
                </p>
            </div>

            {{-- ====== RIGHT: Details + Selection ====== --}}
            <div>
                {{-- Brand badge + title + description --}}
                <div class="mb-6">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white border border-slate-200 shadow-card mb-4">
                        <span class="w-2 h-2 rounded-full" style="background-color: {{ $brandColor }}"></span>
                        <span class="text-xs font-semibold text-slate-700">{{ $cardType['name'] }}</span>
                    </div>

                    <h1 class="font-display text-3xl md:text-4xl font-bold text-slate-900 tracking-tight leading-tight">
                        {{ $cardType['name'] }}
                    </h1>

                    @if(!empty($cardType['description']))
                        <p class="text-sm text-slate-600 mt-3 leading-relaxed line-clamp-3">
                            {{ $cardType['description'] }}
                        </p>
                    @endif
                </div>

                {{-- Montant sélectionné : juste le prix (pas de titre ni de compteur) --}}
                <div class="mb-6">
                    <div class="inline-flex items-center px-6 py-3 rounded-xl border-2 border-[#44A08D] bg-teal-50">
                        <span class="font-black text-xl text-[#44A08D] tabular-nums"
                              x-text="selectedProduct ? formatPrice(selectedProduct.price.min, selectedProduct.price.currencyCode) : '—'"></span>
                    </div>
                </div>

                {{-- Trust strip --}}
                <div class="grid grid-cols-3 gap-2 mb-6">
                    @foreach ([
                        ['icon' => 'bolt',    'top' => 'Livraison', 'bottom' => '30 secondes'],
                        ['icon' => 'shield',  'top' => 'Paiement',  'bottom' => '100% sécurisé'],
                        ['icon' => 'check',   'top' => 'Garantie',  'bottom' => '12 mois'],
                    ] as $trust)
                        <div class="bg-white border border-slate-200 rounded-xl p-3 flex items-start gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center text-[#44A08D] shrink-0">
                                @switch($trust['icon'])
                                    @case('bolt')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        @break
                                    @case('shield')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                        @break
                                    @default
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                @endswitch
                            </div>
                            <div class="min-w-0">
                                <div class="text-[10px] text-slate-400 leading-none">{{ $trust['top'] }}</div>
                                <div class="text-xs font-semibold text-slate-900 mt-0.5 truncate">{{ $trust['bottom'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Tabs : "Comment utiliser" / "Conditions" --}}
                <div x-data="{ tab: 'how' }" class="bg-white rounded-2xl border border-slate-200 shadow-card overflow-hidden">
                    <div class="flex border-b border-slate-100">
                        <button @click="tab='how'" type="button"
                                :class="tab === 'how' ? 'text-[#44A08D] border-[#44A08D]' : 'text-slate-500 border-transparent hover:text-slate-700'"
                                class="flex-1 px-4 py-3 text-sm font-semibold border-b-2 transition">
                            Comment utiliser
                        </button>
                        <button @click="tab='terms'" type="button"
                                :class="tab === 'terms' ? 'text-[#44A08D] border-[#44A08D]' : 'text-slate-500 border-transparent hover:text-slate-700'"
                                class="flex-1 px-4 py-3 text-sm font-semibold border-b-2 transition">
                            Conditions
                        </button>
                    </div>

                    <div x-show="tab === 'how'" class="p-5 space-y-3 text-sm text-slate-600">
                        @foreach ([
                            ['n' => 1, 'text' => 'Achetez votre carte cadeau numérique en quelques secondes.'],
                            ['n' => 2, 'text' => 'Recevez le code instantanément par email et dans votre espace personnel.'],
                            ['n' => 3, 'text' => 'Utilisez le code sur le site officiel de ' . $cardType['name'] . '.'],
                        ] as $step)
                            <div class="flex items-start gap-3">
                                <div class="w-7 h-7 rounded-full bg-teal-50 text-[#44A08D] font-bold text-xs flex items-center justify-center shrink-0">
                                    {{ $step['n'] }}
                                </div>
                                <p class="leading-relaxed pt-1">{{ $step['text'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div x-show="tab === 'terms'" class="p-5 text-sm text-slate-600 leading-relaxed" style="display:none;">
                        <ul class="space-y-2 list-disc list-inside">
                            <li>Carte valide pendant 12 mois à compter de la date d'achat.</li>
                            <li>Non remboursable et non échangeable contre des espèces.</li>
                            <li>Utilisable uniquement sur le site/app officiel de la marque.</li>
                            <li>Le code peut être utilisé en une ou plusieurs fois jusqu'à épuisement.</li>
                        </ul>
                        @if(!empty($cardType['terms']))
                            <p class="mt-4 text-xs text-slate-500 italic">{{ Str::limit(strip_tags($cardType['terms']), 220) }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================================================================
         CARTES SIMILAIRES : les autres montants de la même carte (même type),
         qui étaient auparavant dans la grille de sélection. Cliquer sélectionne
         le montant et remonte en haut de la fiche.
         ================================================================ --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 pb-32" x-show="products.length > 1" x-cloak>
        <div class="border-t border-slate-100 pt-8">
            <h2 class="text-lg font-bold text-slate-900 mb-1">Cartes similaires</h2>
            <p class="text-sm text-slate-500 mb-5">Autres montants disponibles pour cette carte.</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                {{-- Même design que la boutique (composant x-product-card). Cliquer
                     sélectionne le montant + remonte en haut ; le montant courant
                     est masqué (x-show) puisqu'il est déjà affiché en haut. --}}
                @foreach($cardType['products'] as $denom)
                    <x-product-card
                        :name="$denom['name']"
                        :brand-label="$cardType['name']"
                        :brand-color="$brandColor"
                        :logo-url="$cardType['logoUrl'] ?? null"
                        :price="$denom['price']['min'] ?? 0"
                        :currency="$denom['price']['currencyCode'] ?? 'XAF'"
                        :country-code="$cardType['countryCode'] ?? null"
                        :discount="2"
                        href="#"
                        x-show="!selectedProduct || selectedProduct.id !== {{ (int) $denom['id'] }}"
                        x-on:click.prevent="selectProduct({{ \Illuminate\Support\Js::from(['id' => $denom['id'], 'name' => $denom['name'], 'price' => $denom['price']]) }}); window.scrollTo({ top: 0, behavior: 'smooth' })"
                    />
                @endforeach
            </div>
        </div>
    </div>

    {{-- ================================================================
         BOTTOM ACTION BAR (sticky)
         ================================================================ --}}
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 shadow-[0_-8px_24px_-12px_rgba(15,23,42,0.15)] z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 md:py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-[env(safe-area-inset-bottom,0.75rem)]">
            <div class="flex items-center justify-between sm:block">
                <span class="text-xs uppercase tracking-wider text-slate-400 font-bold block leading-none">Total à payer</span>
                <span class="font-display text-2xl md:text-3xl font-black text-slate-900 mt-1 block leading-none tabular-nums"
                      x-text="selectedProduct ? formatPrice(selectedProduct.price.min, selectedProduct.price.currencyCode) : '—'"></span>
            </div>

            <div class="flex gap-2 sm:gap-3 flex-1 sm:max-w-md">
                <button @click="addToCart()"
                        :disabled="!selectedProduct"
                        type="button"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 border-slate-200 hover:border-slate-300 bg-white text-slate-900 font-semibold text-sm transition active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <span class="hidden sm:inline">Ajouter au panier</span>
                    <span class="sm:hidden">Panier</span>
                </button>
                <button @click="buyNow()"
                        :disabled="!selectedProduct"
                        type="button"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-[#44A08D] hover:bg-[#3d9180] text-white font-semibold text-sm shadow-lg shadow-[#44A08D]/30 transition active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span>Acheter</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* === Animations preservees === */
    .perspective-1000 { perspective: 1200px; }
    .transform-style-3d { transform-style: preserve-3d; }
    .backface-hidden { backface-visibility: hidden; -webkit-backface-visibility: hidden; }
    .rotate-y-180 { transform: rotateY(180deg); }

    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50%      { transform: translateY(-12px); }
    }
    .animate-float { animation: float 3.5s ease-in-out infinite; }

    /* Pause flottement quand carte retournee (evite double anim) */
    .rotate-y-180 .animate-float { animation: none; }

    /* Reduced motion */
    @media (prefers-reduced-motion: reduce) {
        .animate-float { animation: none; }
        .transform-style-3d { transition: none !important; }
    }
</style>

<script>
    function productDetails() {
        return {
            products: @json($cardType['products']),
            cardInfo: {
                name: @json($cardType['name']),
                logoUrl: @json($cardType['logoUrl'] ?? ''),
                brandColor: @json($brandColor),
                textColor: @json($textColor),
            },
            selectedProduct: null,
            isFlipped: false,

            get priceRange() {
                if (!this.products.length) return '';
                const min = Math.min(...this.products.map(p => p.price.min));
                const max = Math.max(...this.products.map(p => p.price.max));
                const currency = this.products[0].price.currencyCode;
                if (min === max) return this.formatPrice(min, currency);
                return `${this.formatPrice(min, currency)} – ${this.formatPrice(max, currency)}`;
            },

            get progressWidth() {
                if (!this.selectedProduct || this.products.length <= 1) return 100;
                const min = Math.min(...this.products.map(p => p.price.min));
                const max = Math.max(...this.products.map(p => p.price.max));
                const current = this.selectedProduct.price.min;
                if (min === max) return 100;
                return Math.round(((current - min) / (max - min)) * 80 + 20);
            },

            get selectedTitle() {
                if (!this.selectedProduct) return 'Choisissez un montant';
                return 'Montant sélectionné';
            },

            init() {
                if (this.products.length > 0) this.selectedProduct = this.products[0];
            },

            selectProduct(product) {
                this.selectedProduct = product;
            },

            formatPrice(price, currency) {
                if (typeof window.convertToFCFA === 'function') {
                    const converted = window.convertToFCFA(price, currency);
                    return window.formatFCFA(converted);
                }
                return new Intl.NumberFormat('fr-FR', {
                    style: 'currency',
                    currency: currency || 'XAF',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0,
                }).format(price);
            },

            async addToCart() {
                if (!this.selectedProduct) return;
                let price = this.selectedProduct.price.min;
                if (typeof window.convertToFCFA === 'function') {
                    price = window.convertToFCFA(price, this.selectedProduct.price.currencyCode);
                }
                if (typeof window.addToCart === 'function') {
                    await window.addToCart(
                        this.selectedProduct.id,
                        this.selectedProduct.name,
                        price,
                        this.cardInfo.logoUrl
                    );
                } else {
                    console.error('Global addToCart function not found');
                }
            },

            buyNow() {
                this.addToCart();
                window.location.href = "{{ route('cart.index') }}";
            },
        }
    }
</script>
@endsection
