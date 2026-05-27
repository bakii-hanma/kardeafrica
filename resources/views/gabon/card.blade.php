@extends('layouts.app')

@section('title', $card->name . ($merchant ? ' — '.($merchant->business_name ?? $merchant->name) : ' — KardAfrica'))

@section('content')
@php
    $merchantName    = $merchant?->business_name ?? $merchant?->name ?? 'KardAfrica';
    $merchantCity    = $merchant?->city;
    $merchantSlug    = $merchant?->slug;
    $merchantInitial = strtoupper(substr($merchantName, 0, 1));
    $minDenom = collect($card->denominations ?? [])->min() ?? 0;
    $maxDenom = collect($card->denominations ?? [])->max() ?? 0;
@endphp

<div class="min-h-screen bg-[#FAFAF7] pb-32" x-data="merchantCardDetails()">

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
                <a href="{{ route('gabon.index') }}" class="hover:text-[#44A08D] transition">Carte Gabon</a>
                <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                @if($merchantSlug)
                    <a href="{{ route('gabon.merchant', $merchantSlug) }}" class="hover:text-[#44A08D] transition truncate max-w-[160px]">{{ $merchantName }}</a>
                    <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                @endif
                <span class="text-slate-900 font-medium truncate max-w-[200px]">{{ $card->name }}</span>
            </nav>

            <a href="{{ route('gabon.index') }}" class="hidden sm:inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-[#44A08D] transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Retour
            </a>
        </div>
    </div>

    {{-- ================================================================
         HERO — 3D Card visual (left) + Selection (right)
         ================================================================ --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-12 relative">
        {{-- Ambient gradient teal --}}
        <div class="absolute inset-0 -z-10 overflow-hidden pointer-events-none">
            <div class="absolute -top-12 left-1/4 w-[600px] h-[400px] rounded-full blur-3xl opacity-25 bg-[#44A08D]"></div>
        </div>

        <div class="lg:grid lg:grid-cols-[minmax(0,_1fr)_minmax(0,_1.1fr)] lg:gap-12 items-start">

            {{-- ====== LEFT: 3D Floating Card ====== --}}
            <div class="mb-10 lg:mb-0 perspective-1000">
                <div class="relative w-full max-w-md mx-auto aspect-[1.55] transition-transform duration-700 transform-style-3d cursor-pointer"
                     :class="{'rotate-y-180': isFlipped}"
                     @click="isFlipped = !isFlipped">

                    {{-- ===== FRONT : merchant card visual ===== --}}
                    <div class="absolute inset-0 backface-hidden rounded-[28px] shadow-pop overflow-hidden bg-white animate-float">
                        @include('partials._merchant-card-visual', ['card' => $card, 'fill' => true])
                    </div>

                    {{-- ===== BACK : KardAfrica branding ===== --}}
                    <div class="absolute inset-0 backface-hidden rotate-y-180 rounded-[28px] shadow-pop overflow-hidden bg-gradient-to-br from-[#1F2937] via-[#0F172A] to-[#1F2937] flex flex-col items-center justify-center p-8 text-center relative">
                        <div class="absolute -top-20 -left-20 w-64 h-64 rounded-full bg-[#44A08D]/30 blur-3xl"></div>
                        <div class="absolute -bottom-16 -right-16 w-48 h-48 rounded-full bg-[#4ECDC4]/20 blur-3xl"></div>
                        <svg class="absolute inset-0 w-full h-full opacity-[0.04]" aria-hidden="true">
                            <defs>
                                <pattern id="back-dots-merchant" width="24" height="24" patternUnits="userSpaceOnUse">
                                    <circle cx="2" cy="2" r="1" fill="white"/>
                                </pattern>
                            </defs>
                            <rect width="100%" height="100%" fill="url(#back-dots-merchant)"/>
                        </svg>
                        <div class="relative z-10">
                            <div class="absolute -top-2 left-1/2 -translate-x-1/2 w-32 h-4 bg-black/40 rounded"></div>
                            <div class="mt-12">
                                <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" class="w-12 h-12 mx-auto mb-3 opacity-90" alt="">
                                <h3 class="font-display text-white font-bold text-2xl mb-1">KardAfrica</h3>
                                <p class="text-slate-300 text-xs max-w-[240px] mx-auto leading-relaxed">
                                    @if($merchant)
                                        Carte-cadeau utilisable chez <strong class="text-[#4ECDC4]">{{ $merchantName }}</strong>{{ $merchantCity ? ' à '.$merchantCity : '' }}.
                                    @else
                                        Carte-cadeau locale Kardafrica utilisable chez tous les marchands partenaires au Gabon.
                                    @endif
                                </p>
                                <div class="mt-4 flex items-center justify-center gap-2 font-mono text-[#4ECDC4] text-sm">
                                    <span>****</span><span>****</span><span>****</span><span>{{ str_pad($card->id, 4, '0', STR_PAD_LEFT) }}</span>
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
                {{-- Merchant badge + title + description --}}
                <div class="mb-6">
                    @php
                        $badgeTag = $merchantSlug ? 'a' : 'div';
                        $badgeAttrs = $merchantSlug ? 'href="'.route('gabon.merchant', $merchantSlug).'"' : '';
                    @endphp
                    <{!! $badgeTag !!} {!! $badgeAttrs !!} class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white border border-slate-200 shadow-card mb-4 {{ $merchantSlug ? 'hover:border-[#44A08D] transition' : '' }}">
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-white text-[10px] font-black bg-gradient-to-br from-[#44A08D] to-[#4ECDC4]">{{ $merchantInitial }}</span>
                        <span class="text-xs font-semibold text-slate-700">{{ $merchantName }}</span>
                        @if($merchantCity)
                            <span class="text-[10px] text-slate-400">· {{ $merchantCity }}</span>
                        @endif
                    </{!! $badgeTag !!}>

                    @if(isset($categories[$card->category]))
                        <div class="text-[11px] font-bold uppercase tracking-wider text-[#44A08D] mb-1.5">{{ $categories[$card->category] }}</div>
                    @endif

                    <h1 class="font-display text-3xl md:text-4xl font-bold text-slate-900 tracking-tight leading-tight">
                        {{ $card->name }}
                    </h1>

                    @if($card->description)
                        <p class="text-sm text-slate-600 mt-3 leading-relaxed line-clamp-3">{{ $card->description }}</p>
                    @endif
                </div>

                {{-- Selection : pills modernes (= card-type style) --}}
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-bold text-slate-900" x-text="selectedTitle"></h3>
                        <span class="text-xs text-slate-500">
                            {{ count($card->denominations ?? []) }} montant{{ count($card->denominations ?? []) > 1 ? 's' : '' }}{{ $card->allow_custom_amount ? ' + libre' : '' }}
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($card->denominations ?? [] as $d)
                            <button @click="selectDenom({{ $d }})" type="button"
                                    class="relative px-5 py-3 rounded-xl border-2 transition-all duration-200 font-bold text-sm min-w-[100px] active:scale-95"
                                    :class="selectedDenom === {{ $d }}
                                        ? 'border-[#44A08D] bg-teal-50 text-[#44A08D] shadow-md shadow-[#44A08D]/15'
                                        : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'">
                                <span class="tabular-nums">{{ number_format($d, 0, ',', ' ') }}</span>
                                <span class="text-[10px] font-bold opacity-75 ml-0.5">FCFA</span>
                                <span x-show="selectedDenom === {{ $d }}" class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-[#44A08D] flex items-center justify-center shadow-lg">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </span>
                            </button>
                        @endforeach

                        @if($card->allow_custom_amount)
                            <button @click="toggleCustom()" type="button"
                                    class="relative px-5 py-3 rounded-xl border-2 border-dashed transition-all duration-200 font-bold text-sm min-w-[100px] active:scale-95"
                                    :class="customMode
                                        ? 'border-[#44A08D] bg-teal-50 text-[#44A08D]'
                                        : 'border-slate-300 bg-white text-slate-600 hover:border-slate-400'">
                                <svg class="w-4 h-4 inline-block mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                Libre
                            </button>
                        @endif
                    </div>

                    {{-- Custom amount input (toggled) --}}
                    @if($card->allow_custom_amount)
                        <div x-show="customMode" x-cloak x-transition class="mt-3 flex items-center gap-2 p-3 bg-white border-2 border-dashed border-[#44A08D]/40 rounded-xl">
                            <label class="text-xs font-bold text-slate-700 shrink-0">Montant&nbsp;:</label>
                            <input type="number" x-model.number="customAmount"
                                   min="{{ (int) ($card->min_amount ?? 500) }}"
                                   max="{{ (int) ($card->max_amount ?? 1000000) }}"
                                   step="500"
                                   placeholder="{{ number_format($card->min_amount ?? 1000, 0, ',', ' ') }}"
                                   class="flex-1 min-w-0 px-3 py-2 rounded-lg border border-slate-200 focus:border-[#44A08D] focus:ring-2 focus:ring-[#44A08D]/20 outline-none font-bold text-base tabular-nums text-slate-900">
                            <span class="text-xs font-bold text-slate-500 shrink-0">FCFA</span>
                        </div>
                        <p x-show="customMode" x-cloak class="text-[11px] text-slate-400 mt-1.5">
                            Entre <span class="font-semibold tabular-nums">{{ number_format($card->min_amount ?? 0, 0, ',', ' ') }}</span>
                            et <span class="font-semibold tabular-nums">{{ number_format($card->max_amount ?? 0, 0, ',', ' ') }}</span> FCFA
                        </p>
                    @endif
                </div>

                {{-- Trust strip --}}
                <div class="grid grid-cols-3 gap-2 mb-6">
                    @foreach ([
                        ['icon' => 'bolt',    'top' => 'Livraison', 'bottom' => 'Instantanée'],
                        ['icon' => 'shield',  'top' => 'Paiement',  'bottom' => '100% sécurisé'],
                        ['icon' => 'check',   'top' => 'Validité',  'bottom' => $card->validity_months . ' mois'],
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
                            ['n' => 1, 'text' => 'Choisis ton montant et achète la carte en quelques clics.'],
                            ['n' => 2, 'text' => 'Reçois ton code unique + QR par WhatsApp et email instantanément.'],
                            ['n' => 3, 'text' => 'Présente le QR ou le code en boutique' . ($merchant ? ' chez '.$merchantName : ' chez un marchand Kardafrica partenaire') . '.'],
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
                            <li>Valide pendant <strong>{{ $card->validity_months }} mois</strong> à compter de la date d'achat.</li>
                            <li>Utilisable @if($merchant)uniquement chez <strong>{{ $merchantName }}</strong>{{ $merchantCity ? ' à '.$merchantCity : '' }}@else chez les marchands partenaires Kardafrica au Gabon@endif.</li>
                            <li>Non remboursable et non échangeable contre de l'espèce.</li>
                            <li>Peut être utilisée en une ou plusieurs fois jusqu'à épuisement du solde.</li>
                        </ul>
                        @if(!empty($card->terms_conditions))
                            <p class="mt-4 text-xs text-slate-500 italic whitespace-pre-line">{{ $card->terms_conditions }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================
             OTHER CARDS FROM SAME MERCHANT
             ============================================================ --}}
        @if($otherCards->isNotEmpty())
            <section class="mt-16 pt-12 border-t border-slate-200">
                <div class="flex items-end justify-between mb-6 flex-wrap gap-3">
                    <div>
                        <h2 class="font-display text-xl md:text-2xl font-bold text-slate-900 tracking-tight">{{ $merchant ? 'Autres cartes de '.$merchantName : 'Autres cartes locales' }}</h2>
                        <p class="text-xs text-slate-500 mt-1">{{ $merchant ? 'Du même marchand, à découvrir aussi.' : 'À découvrir aussi dans le catalogue.' }}</p>
                    </div>
                    @if($merchantSlug)
                        <a href="{{ route('gabon.merchant', $merchantSlug) }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#44A08D] hover:underline">
                            Tout voir
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    @endif
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach($otherCards as $other)
                        @php
                            $otherMin = collect($other->denominations ?? [])->min() ?? 0;
                            $otherCount = count($other->denominations ?? []);
                        @endphp
                        <a href="{{ route('gabon.card', $other) }}"
                           class="group block overflow-hidden rounded-2xl bg-white border border-slate-100 shadow-card hover:shadow-card-hover transition-all duration-300 hover:-translate-y-1">
                            @include('partials._merchant-card-visual', ['card' => $other])
                            <div class="p-3">
                                <h4 class="text-xs sm:text-sm font-semibold text-slate-900 leading-snug line-clamp-2 group-hover:text-[#44A08D] transition">{{ $other->name }}</h4>
                                @if($otherCount > 1)
                                    <p class="text-[10px] text-slate-400 mt-0.5">{{ $otherCount }} montants</p>
                                @endif
                                <div class="mt-2 pt-2 border-t border-slate-100 flex items-end justify-between">
                                    <span class="text-[10px] text-slate-400">Dès</span>
                                    <span class="text-sm font-black tabular-nums text-slate-900">{{ number_format($otherMin, 0, ',', ' ') }} <span class="text-[10px] font-bold text-slate-500">FCFA</span></span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    {{-- ================================================================
         BOTTOM ACTION BAR (sticky)
         ================================================================ --}}
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 shadow-[0_-8px_24px_-12px_rgba(15,23,42,0.15)] z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 md:py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-[env(safe-area-inset-bottom,0.75rem)]">
            <div class="flex items-center justify-between sm:block">
                <span class="text-xs uppercase tracking-wider text-slate-400 font-bold block leading-none">Total à payer</span>
                <span class="font-display text-2xl md:text-3xl font-black text-slate-900 mt-1 block leading-none tabular-nums">
                    <span x-text="formatFcfa(currentAmount)"></span> <span class="text-sm font-bold text-slate-500">FCFA</span>
                </span>
            </div>

            <div class="flex gap-2 sm:gap-3 flex-1 sm:max-w-md">
                <button @click="addToCart()"
                        :disabled="!currentAmount"
                        type="button"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 border-slate-200 hover:border-slate-300 bg-white text-slate-900 font-semibold text-sm transition active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <span class="hidden sm:inline">Ajouter au panier</span>
                    <span class="sm:hidden">Panier</span>
                </button>
                <button @click="buyNow()"
                        :disabled="!currentAmount"
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
    .perspective-1000 { perspective: 1200px; }
    .transform-style-3d { transform-style: preserve-3d; }
    .backface-hidden { backface-visibility: hidden; -webkit-backface-visibility: hidden; }
    .rotate-y-180 { transform: rotateY(180deg); }

    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50%      { transform: translateY(-12px); }
    }
    .animate-float { animation: float 3.5s ease-in-out infinite; }
    .rotate-y-180 .animate-float { animation: none; }

    @media (prefers-reduced-motion: reduce) {
        .animate-float { animation: none; }
        .transform-style-3d { transition: none !important; }
    }

    [x-cloak] { display: none !important; }
</style>

<script>
    function merchantCardDetails() {
        return {
            denoms:       {{ json_encode($card->denominations ?? []) }},
            allowCustom:  {{ $card->allow_custom_amount ? 'true' : 'false' }},
            minAmount:    {{ (int) ($card->min_amount ?? 0) }},
            maxAmount:    {{ (int) ($card->max_amount ?? 0) }},
            selectedDenom: null,
            customMode:   false,
            customAmount: null,
            isFlipped:    false,

            get currentAmount() {
                if (this.customMode && this.customAmount) {
                    const v = Number(this.customAmount);
                    if (v >= this.minAmount && v <= this.maxAmount) return v;
                    return 0;
                }
                return this.selectedDenom || 0;
            },

            get selectedTitle() {
                if (this.customMode && this.customAmount) return 'Montant libre';
                if (this.selectedDenom) return 'Montant sélectionné';
                return 'Choisis un montant';
            },

            init() {
                if (this.denoms.length > 0) this.selectedDenom = this.denoms[0];
            },

            selectDenom(d) {
                this.selectedDenom = d;
                this.customMode = false;
                this.customAmount = null;
            },

            toggleCustom() {
                this.customMode = !this.customMode;
                if (this.customMode) {
                    this.selectedDenom = null;
                } else {
                    this.customAmount = null;
                    if (this.denoms.length > 0) this.selectedDenom = this.denoms[0];
                }
            },

            formatFcfa(n) {
                return Number(n || 0).toLocaleString('fr-FR', { useGrouping: true });
            },

            // ====== Mêmes appels que /card-type/{id} ======
            // Le product_id encode le marchand + montant : "merchant_<card_id>_<amount>"
            // → permet à ProcessCheckoutJob de générer le code LOCALEMENT (pas via afrikard).
            cartProductId() {
                return 'merchant_{{ $card->id }}_' + this.currentAmount;
            },

            cartProductName() {
                return @js($merchantName . ' — ' . $card->name)
                       + ' — ' + this.formatFcfa(this.currentAmount) + ' F';
            },

            cartImageUrl() {
                return @js($card->visual_url ? asset($card->visual_url) : '');
            },

            async addToCart() {
                if (!this.currentAmount) return;
                // Le montant marchand est déjà en XAF → on n'utilise PAS convertToFCFA().
                if (typeof window.addToCart === 'function') {
                    await window.addToCart(
                        this.cartProductId(),
                        this.cartProductName(),
                        this.currentAmount,
                        this.cartImageUrl()
                    );
                } else {
                    console.error('Global addToCart function not found');
                }
            },

            async buyNow() {
                await this.addToCart();
                window.location.href = "{{ route('cart.index') }}";
            },
        };
    }
</script>
@endsection
