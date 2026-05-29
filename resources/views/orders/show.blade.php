@extends('layouts.app')

@section('title', 'Commande #' . ($order->order_number ?? $order->id) . ' - KardAfrica')

@php
    // Brand color helper (overflow-safe)
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

    $statusMap = [
        'pending'    => ['label' => 'En attente',       'tone' => 'amber'],
        'processing' => ['label' => 'En traitement',    'tone' => 'sky'],
        'completed'  => ['label' => 'Terminée',         'tone' => 'emerald'],
        'cancelled'  => ['label' => 'Annulée',          'tone' => 'rose'],
        'failed'     => ['label' => 'Échouée',          'tone' => 'rose'],
        'shipped'    => ['label' => 'Expédiée',         'tone' => 'sky'],
        'delivered'  => ['label' => 'Livrée',           'tone' => 'emerald'],
        'refunded'   => ['label' => 'Remboursée',       'tone' => 'slate'],
    ];
    $payMap = [
        'completed'  => ['label' => 'Payé',          'tone' => 'emerald'],
        'pending'    => ['label' => 'En attente',    'tone' => 'amber'],
        'processing' => ['label' => 'En cours',      'tone' => 'sky'],
        'failed'     => ['label' => 'Échoué',        'tone' => 'rose'],
        'cancelled'  => ['label' => 'Annulé',        'tone' => 'rose'],
        'refunded'   => ['label' => 'Remboursé',     'tone' => 'slate'],
        'paid'       => ['label' => 'Payé',          'tone' => 'emerald'],
    ];
    $toneClasses = [
        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'amber'   => 'bg-amber-50 text-amber-700 border-amber-200',
        'sky'     => 'bg-sky-50 text-sky-700 border-sky-200',
        'rose'    => 'bg-rose-50 text-rose-700 border-rose-200',
        'slate'   => 'bg-slate-50 text-slate-700 border-slate-200',
    ];

    $statusInfo = $statusMap[$order->status] ?? ['label' => ucfirst($order->status), 'tone' => 'slate'];
    $payInfo    = $payMap[$order->payment_status] ?? ['label' => ucfirst($order->payment_status), 'tone' => 'slate'];

    $cards = $userCards ?? collect();
@endphp

@section('content')
<div class="min-h-screen bg-[#FAFAF7] pb-20">

    {{-- ================================================================
         BREADCRUMB
         ================================================================ --}}
    <div class="bg-white border-b border-slate-200">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between">
            <nav class="flex items-center gap-1.5 text-xs text-slate-500">
                <a href="{{ route('home') }}" class="hover:text-[#44A08D] transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Accueil
                </a>
                <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ url('/orders') }}" class="hover:text-[#44A08D] transition">Mes commandes</a>
                <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-900 font-medium font-mono">#{{ $order->order_number ?? $order->id }}</span>
            </nav>
            <div class="hidden sm:flex items-center gap-3 text-xs font-semibold">
                <a href="{{ route('cards.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-teal-50 text-[#44A08D] hover:bg-teal-100 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    Mes cartes
                </a>
                <a href="{{ url('/orders') }}" class="inline-flex items-center gap-1.5 text-slate-600 hover:text-[#44A08D] transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Toutes mes commandes
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 md:pt-10">

        {{-- Retry banner si commande payee mais sans cartes (afrikard a echoue) --}}
        {{-- Les cartes marchand (MerchantCardPurchase) comptent aussi comme "livrées". --}}
        @php $mpCount = ($merchantPurchases ?? collect())->count(); @endphp
        @if($order->payment_status === 'completed' && $cards->count() === 0 && $mpCount === 0)
            <div class="mb-6 bg-amber-50 border border-amber-200 rounded-2xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="flex items-start gap-3 flex-1 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center text-amber-700 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-semibold text-amber-900">Cartes non livrées</div>
                        <p class="text-xs text-amber-800 mt-0.5">
                            Le paiement est confirmé mais le fournisseur n'a pas pu livrer les cartes.
                            Cliquez ci-dessous pour relancer la livraison.
                        </p>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 shrink-0" x-data="{ refundModal: false }">
                    <form action="{{ route('orders.retry-checkout', $order) }}" method="POST" class="shrink-0">
                        @csrf
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-md transition active:scale-95">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Relancer
                        </button>
                    </form>
                    <button type="button" @click="refundModal = true"
                            class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg bg-white border border-rose-200 hover:bg-rose-50 text-rose-700 text-xs font-bold shadow-sm transition active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z"/></svg>
                        Demander un remboursement
                    </button>

                    {{-- Modal remboursement --}}
                    <div x-show="refundModal" x-cloak
                         class="fixed inset-0 z-50 flex items-center justify-center p-4"
                         style="background:rgba(15,23,42,0.55);backdrop-filter:blur(6px);"
                         @click.self="refundModal = false"
                         @keydown.escape.window="refundModal = false"
                         x-transition.opacity>
                        <div x-show="refundModal" x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             class="bg-white rounded-3xl overflow-hidden shadow-pop max-w-md w-full">
                            <div class="relative p-5 bg-gradient-to-br from-rose-700 via-rose-500 to-pink-400 text-white flex items-center gap-4">
                                <div class="absolute -top-12 -right-12 w-44 h-44 rounded-full" style="background:radial-gradient(circle,rgba(255,255,255,0.30) 0%,transparent 70%);"></div>
                                <div class="relative w-12 h-12 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z"/></svg>
                                </div>
                                <div class="relative">
                                    <div class="text-[10px] font-extrabold uppercase tracking-widest opacity-95">Remboursement</div>
                                    <h3 class="font-display text-lg font-bold leading-tight mt-0.5">Demander un remboursement&nbsp;?</h3>
                                </div>
                            </div>
                            <div class="p-5 text-sm text-slate-700 leading-relaxed">
                                <p class="mb-2">Montant à rembourser : <strong class="text-slate-900">{{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</strong></p>
                                @if($order->payment_method === 'ebilling')
                                    <p class="text-xs text-slate-500">L'argent te sera renvoyé via E-Billing sur ton moyen de paiement initial (Mobile Money / carte). Délai : quelques minutes à quelques heures.</p>
                                @elseif($order->payment_method === \App\Models\Order::PAYMENT_METHOD_CASH_RESELLER)
                                    <p class="text-xs text-slate-500">Tu as payé en cash chez un vendeur Kardafrica. Tu dois aller voir <strong class="text-slate-900">{{ optional($order->cashReseller)->name ?: 'le vendeur' }}</strong> pour récupérer ton argent en cash. Le remboursement est noté dans son compte.</p>
                                @endif
                            </div>
                            <div class="flex gap-2 p-4 bg-slate-50 border-t border-slate-100">
                                <button type="button" @click="refundModal = false" class="flex-1 px-4 py-2.5 rounded-xl bg-white border border-slate-200 hover:border-slate-400 text-slate-600 text-sm font-bold transition">Annuler</button>
                                <form method="POST" action="{{ route('orders.refund', $order) }}" class="flex-1">
                                    @csrf
                                    <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-gradient-to-br from-rose-700 to-rose-500 hover:from-rose-800 hover:to-rose-600 text-white text-sm font-bold shadow-md transition active:scale-95">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        Confirmer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif


        {{-- ================================================================
             HEADER : titre + status badges
             ================================================================ --}}
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
            <div>
                @if($order->status === 'completed')
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 mb-3">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span class="text-xs font-semibold text-emerald-700">Commande livrée avec succès</span>
                    </div>
                @endif
                <h1 class="font-display text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">
                    Commande <span class="font-mono text-[#44A08D]">#{{ $order->order_number ?? $order->id }}</span>
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Passée le {{ $order->created_at->translatedFormat('d F Y à H:i') }}
                    @if($order->completed_at)
                        · Complétée le {{ $order->completed_at->translatedFormat('d F Y à H:i') }}
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-bold {{ $toneClasses[$statusInfo['tone']] }}">
                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                    {{ $statusInfo['label'] }}
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-bold {{ $toneClasses[$payInfo['tone']] }}">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    {{ $payInfo['label'] }}
                </span>
                <a href="{{ route('cards.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#44A08D] hover:bg-[#3d9180] text-white text-xs font-bold shadow-md shadow-[#44A08D]/20 transition active:scale-95">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    Voir mes cartes
                </a>
            </div>
        </div>

        {{-- ================================================================
             VOS CARTES LIVREES (UserCard) — animees par ligne
             Les cartes locales Gabon y figurent aussi via leur miroir UserCard
             (source = merchant), avec code + PIN + expiration. Pas de section
             séparée pour éviter le doublon.
             ================================================================ --}}
        @if($cards->count() > 0)
            <section class="mb-10">
                <div class="flex items-end justify-between mb-4">
                    <h2 class="font-display text-lg md:text-xl font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-gradient-to-br from-[#4ECDC4] to-[#44A08D] text-white flex items-center justify-center shadow-md shadow-[#44A08D]/20">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </span>
                        Vos cartes
                        <span class="text-xs font-normal text-slate-500">· {{ $cards->count() }} reçue{{ $cards->count() > 1 ? 's' : '' }}</span>
                    </h2>
                </div>

                <div class="space-y-4">
                    @foreach($cards as $i => $card)
                        @php
                            $cardCode = $card->card_code ?? $card->getRawOriginal('card_code') ?? '';
                            $cardPin  = $card->pin ?? $card->getRawOriginal('pin') ?? null;
                            $brandColor = $brandColorFor($card->brand ?? $card->name ?? '');
                        @endphp
                        <div class="card-row" style="animation-delay: {{ $i * 100 }}ms"
                             x-data="{ codeShown: false, pinShown: false, copiedField: null,
                                       copy(text, field) {
                                           navigator.clipboard.writeText(text).then(() => {
                                               this.copiedField = field;
                                               setTimeout(() => this.copiedField = null, 1800);
                                           });
                                       } }">

                            <div class="bg-white rounded-2xl border border-slate-200 shadow-card overflow-hidden hover:shadow-card-hover transition-all duration-300">
                                <div class="grid grid-cols-1 md:grid-cols-[280px_1fr]">

                                    {{-- Visuel carte gauche --}}
                                    <div style="background-color: {{ $brandColor }}" class="relative p-5 overflow-hidden min-h-[180px] flex flex-col justify-between">
                                        {{-- Pattern --}}
                                        <svg class="absolute inset-0 w-full h-full opacity-[0.08]" aria-hidden="true">
                                            <defs>
                                                <pattern id="oc-{{ $card->id }}" width="32" height="32" patternUnits="userSpaceOnUse">
                                                    <circle cx="16" cy="16" r="13" fill="none" stroke="white" stroke-width="1.2"/>
                                                </pattern>
                                            </defs>
                                            <rect width="100%" height="100%" fill="url(#oc-{{ $card->id }})"/>
                                        </svg>

                                        {{-- Glow --}}
                                        <div class="absolute -top-12 -right-12 w-40 h-40 rounded-full bg-white/15 blur-2xl"></div>

                                        <div class="relative flex flex-col h-full justify-between">
                                            <div>
                                                <div class="flex items-center justify-between">
                                                    <span class="text-white/80 text-[10px] font-bold tracking-[0.2em] uppercase">Gift Card</span>
                                                    @if($card->status === 'active')
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-500/30 backdrop-blur-sm border border-emerald-300/40 text-white text-[10px] font-bold">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-300"></span>
                                                            Active
                                                        </span>
                                                    @elseif($card->status === 'used')
                                                        <span class="px-2 py-0.5 rounded-full bg-slate-500/40 text-white text-[10px] font-bold">Utilisée</span>
                                                    @elseif($card->status === 'expired')
                                                        <span class="px-2 py-0.5 rounded-full bg-rose-500/40 text-white text-[10px] font-bold">Expirée</span>
                                                    @endif
                                                </div>
                                                <h3 class="font-display text-white font-bold text-2xl tracking-tight mt-1.5 leading-tight truncate">
                                                    {{ $card->brand ?: explode(' ', $card->name)[0] }}
                                                </h3>
                                            </div>

                                            <div class="flex items-end justify-between">
                                                <div>
                                                    <div class="text-white/60 text-[9px] font-bold uppercase tracking-wider">Prix payé</div>
                                                    <div class="text-white font-display text-xl font-bold tabular-nums leading-none mt-0.5">
                                                        {{ number_format($card->orderItem?->unit_price ?? 0, 0, ',', ' ') }} <span class="text-sm font-normal text-white/60">FCFA</span>
                                                    </div>
                                                    @if($card->face_value && $card->currency !== 'XAF')
                                                        <div class="text-white/60 text-[10px] mt-1 tabular-nums">
                                                            {{ number_format($card->face_value, 0, ',', ' ') }} {{ $card->currency }} de crédit
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="w-10 h-7 rounded bg-gradient-to-br from-yellow-200/90 to-yellow-400/70 border border-yellow-300/40 shadow-inner"></div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Détails droite --}}
                                    <div class="p-5 flex flex-col justify-between">
                                        <div>
                                            <h4 class="text-base font-semibold text-slate-900 mb-3 line-clamp-1">{{ $card->name }}</h4>

                                            <div class="space-y-2.5">
                                                {{-- Code carte --}}
                                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                                                    <div class="flex items-center justify-between mb-1">
                                                        <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Code</span>
                                                        <div class="flex items-center gap-1">
                                                            <button @click="codeShown ? codeShown = false : window.requireUnlock(() => codeShown = true)" type="button"
                                                                    class="p-1 rounded text-slate-400 hover:text-[#44A08D] hover:bg-white transition"
                                                                    :title="codeShown ? 'Masquer' : 'Afficher'">
                                                                <svg x-show="!codeShown" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                                <svg x-show="codeShown" x-cloak class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                                            </button>
                                                            <button @click="window.requireUnlock(() => copy('{{ $cardCode }}', 'code'))" type="button"
                                                                    class="p-1 rounded text-slate-400 hover:text-[#44A08D] hover:bg-white transition relative"
                                                                    title="Copier">
                                                                <svg x-show="copiedField !== 'code'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                                <svg x-show="copiedField === 'code'" x-cloak class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="font-mono text-sm font-bold text-slate-900 tracking-wider break-all"
                                                         x-text="codeShown ? '{{ $cardCode }}' : '{{ str_repeat('•', max(8, strlen($cardCode))) }}'"></div>
                                                </div>

                                                {{-- PIN + expiration --}}
                                                <div class="grid grid-cols-2 gap-2.5">
                                                    @if($cardPin)
                                                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                                                            <div class="flex items-center justify-between mb-1">
                                                                <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400">PIN</span>
                                                                <div class="flex items-center gap-1">
                                                                    <button @click="pinShown ? pinShown = false : window.requireUnlock(() => pinShown = true)" type="button" class="p-1 rounded text-slate-400 hover:text-[#44A08D] hover:bg-white transition" :title="pinShown ? 'Masquer' : 'Afficher'">
                                                                        <svg x-show="!pinShown" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                                        <svg x-show="pinShown" x-cloak class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                                                    </button>
                                                                    <button @click="window.requireUnlock(() => copy('{{ $cardPin }}', 'pin'))" type="button" class="p-1 rounded text-slate-400 hover:text-[#44A08D] hover:bg-white transition" title="Copier">
                                                                        <svg x-show="copiedField !== 'pin'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                                        <svg x-show="copiedField === 'pin'" x-cloak class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="font-mono text-sm font-bold text-slate-900 tabular-nums"
                                                                 x-text="pinShown ? '{{ $cardPin }}' : '{{ str_repeat('•', strlen($cardPin)) }}'"></div>
                                                        </div>
                                                    @endif

                                                    @if($card->expiration_date)
                                                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                                                            <div class="text-[10px] uppercase tracking-wider font-bold text-slate-400 mb-1">Expiration</div>
                                                            <div class="text-sm font-bold text-slate-900 tabular-nums">
                                                                {{ \Carbon\Carbon::parse($card->expiration_date)->format('d/m/Y') }}
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>

                                                {{-- Serial number (small) --}}
                                                @if($card->serial_number)
                                                    <div class="flex items-center gap-2 text-[11px] text-slate-500 px-1">
                                                        <span class="font-bold text-[10px] uppercase tracking-wider text-slate-400">Serial:</span>
                                                        <span class="font-mono truncate">{{ $card->serial_number }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Actions footer --}}
                                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center gap-2">
                                            <button @click="window.requireUnlock(() => copy('{{ $cardCode }}', 'code'))" type="button"
                                                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-[#44A08D] hover:bg-[#3d9180] text-white text-xs font-semibold transition active:scale-95">
                                                <svg x-show="copiedField !== 'code'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                <svg x-show="copiedField === 'code'" x-cloak class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                <span x-show="copiedField !== 'code'">Copier le code</span>
                                                <span x-show="copiedField === 'code'" x-cloak>Copié !</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ================================================================
             RECAPITULATIF COMMANDE
             ================================================================ --}}
        <section class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-6">

            {{-- Articles --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-card overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-br from-slate-50 to-white">
                    <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-teal-50 text-[#44A08D] flex items-center justify-center">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        </span>
                        Articles commandés
                    </h2>
                </div>

                <ul class="divide-y divide-slate-100">
                    @foreach($order->orderItems as $item)
                        @php
                            $itemImg = $item->image_url ?: ($item->card_details['image'] ?? null);
                            if ($itemImg && !filter_var($itemImg, FILTER_VALIDATE_URL) && !str_starts_with($itemImg, '/')) {
                                $itemImg = asset($itemImg);
                            }
                        @endphp
                        <li class="px-5 py-3.5 flex items-center gap-3">
                            <div class="shrink-0 w-12 h-12 rounded-xl overflow-hidden bg-gradient-to-br from-slate-100 to-slate-200 border border-slate-200 flex items-center justify-center">
                                @if($itemImg)
                                    <img src="{{ $itemImg }}" alt="{{ $item->name }}" class="w-full h-full object-cover">
                                @else
                                    <span class="font-display text-base font-bold text-slate-500">{{ strtoupper(substr($item->name, 0, 1)) }}</span>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[10px] font-bold tabular-nums">{{ $item->quantity }}×</span>
                                    <p class="text-sm font-semibold text-slate-900 truncate">{{ $item->name }}</p>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5 tabular-nums">
                                    {{ number_format($item->unit_price, 0, ',', ' ') }} {{ $order->currency ?? 'FCFA' }} / unité
                                </p>
                            </div>
                            <span class="text-sm font-black text-slate-900 tabular-nums whitespace-nowrap">
                                {{ number_format($item->total_price, 0, ',', ' ') }} {{ $order->currency ?? 'FCFA' }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Résumé / Paiement --}}
            <aside class="space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-card overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-br from-slate-50 to-white">
                        <h2 class="font-display text-base font-bold text-slate-900">Récapitulatif</h2>
                    </div>
                    <div class="px-5 py-4 space-y-2.5 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-600">Sous-total</span>
                            <span class="font-semibold text-slate-900 tabular-nums">{{ number_format($order->subtotal, 0, ',', ' ') }} {{ $order->currency ?? 'FCFA' }}</span>
                        </div>
                        @if($order->tax_amount > 0)
                            <div class="flex justify-between">
                                <span class="text-slate-600">TVA</span>
                                <span class="font-semibold text-slate-900 tabular-nums">{{ number_format($order->tax_amount, 0, ',', ' ') }} {{ $order->currency ?? 'FCFA' }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-slate-600">Frais de service</span>
                            <span class="font-semibold text-emerald-600">Offerts</span>
                        </div>
                        <div class="border-t border-slate-100 pt-2.5 flex justify-between items-end">
                            <span class="text-xs uppercase tracking-wider text-slate-400 font-bold">Total payé</span>
                            <span class="font-display text-xl font-black text-slate-900 tabular-nums leading-none">{{ number_format($order->total_amount, 0, ',', ' ') }} {{ $order->currency ?? 'FCFA' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Paiement --}}
                @if($order->payment_method)
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-card p-4">
                        <p class="text-[10px] uppercase tracking-wider text-slate-400 font-bold mb-2">Mode de paiement</p>
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-[#4ECDC4] to-[#44A08D] text-white flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-slate-900">
                                    @if($order->payment_method === 'simulated')
                                        Paiement simulé
                                    @elseif($order->payment_method === 'ebilling')
                                        E-Billing
                                    @else
                                        {{ ucfirst($order->payment_method) }}
                                    @endif
                                </div>
                                @if($order->external_reference)
                                    <div class="text-[10px] text-slate-500 font-mono truncate">{{ $order->external_reference }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Help link --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-card p-4">
                    <p class="text-[10px] uppercase tracking-wider text-slate-400 font-bold mb-2">Besoin d'aide ?</p>
                    <p class="text-xs text-slate-600 mb-3 leading-relaxed">
                        Un problème avec votre commande ? Notre équipe est disponible 24/7.
                    </p>
                    <a href="{{ route('support') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#44A08D] hover:underline">
                        Contacter le support
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </aside>
        </section>

    </div>
</div>

<style>
    .card-row {
        opacity: 0;
        transform: translateY(20px);
        animation: card-slide-in 600ms cubic-bezier(0.22, 1, 0.36, 1) forwards;
    }
    @keyframes card-slide-in {
        to { opacity: 1; transform: translateY(0); }
    }
    @media (prefers-reduced-motion: reduce) {
        .card-row { animation: none; opacity: 1; transform: none; }
    }
</style>
@endsection
