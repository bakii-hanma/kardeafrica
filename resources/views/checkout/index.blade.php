@extends('layouts.app')

@section('title', 'Paiement - KardAfrica')

@section('content')
<div class="min-h-screen bg-[#FAFAF7] pb-20">

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
                <a href="{{ route('cart.index') }}" class="hover:text-[#44A08D] transition">Panier</a>
                <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-900 font-medium">Paiement</span>
            </nav>
            <a href="{{ route('cart.index') }}" class="hidden sm:inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-[#44A08D] transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Retour au panier
            </a>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 md:pt-10">

        {{-- ================================================================
             TITRE + STEPS INDICATOR
             ================================================================ --}}
        <div class="mb-8">
            <h1 class="font-display text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">Finaliser ma commande</h1>
            <p class="text-sm text-slate-500 mt-1.5">Vérifiez vos informations et procédez au paiement.</p>

            {{-- Steps --}}
            <div class="mt-6 flex items-center gap-2 text-xs">
                <div class="flex items-center gap-1.5 text-emerald-700">
                    <div class="w-6 h-6 rounded-full bg-emerald-50 border border-emerald-200 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <span class="font-semibold hidden sm:inline">Panier</span>
                </div>
                <div class="flex-1 max-w-[60px] h-px bg-slate-200"></div>
                <div class="flex items-center gap-1.5 text-[#44A08D]">
                    <div class="w-6 h-6 rounded-full bg-teal-50 border border-[#44A08D] flex items-center justify-center">
                        <span class="font-bold text-[10px]">2</span>
                    </div>
                    <span class="font-semibold hidden sm:inline">Paiement</span>
                </div>
                <div class="flex-1 max-w-[60px] h-px bg-slate-200"></div>
                <div class="flex items-center gap-1.5 text-slate-400">
                    <div class="w-6 h-6 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center">
                        <span class="font-bold text-[10px]">3</span>
                    </div>
                    <span class="font-semibold hidden sm:inline">Confirmation</span>
                </div>
            </div>
        </div>

        {{-- ================================================================
             LAYOUT 2 COLONNES
             ================================================================ --}}
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_380px] gap-6 lg:gap-8">

            {{-- =====================
                 COLONNE GAUCHE
                 ===================== --}}
            <div class="space-y-6">

                {{-- Coordonnées --}}
                <section class="bg-white rounded-2xl border border-slate-200 shadow-card overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-gradient-to-br from-slate-50 to-white">
                        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-teal-50 text-[#44A08D] flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </span>
                            Vos coordonnées
                        </h2>
                        <a href="{{ route('profile.show') }}" class="text-xs font-semibold text-[#44A08D] hover:underline">
                            Modifier
                        </a>
                    </div>
                    <div class="px-5 py-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Nom complet</label>
                            <p class="text-sm font-semibold text-slate-900 mt-1 truncate">{{ Auth::user()->name }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Email</label>
                            <p class="text-sm font-semibold text-slate-900 mt-1 truncate">{{ Auth::user()->email }}</p>
                        </div>
                        <div>
                            <label class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Téléphone</label>
                            <p class="text-sm font-semibold text-slate-900 mt-1 truncate">
                                @if(Auth::user()->phone)
                                    {{ Auth::user()->phone }}
                                @else
                                    <span class="text-rose-600">Non renseigné</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </section>

                {{-- Articles --}}
                <section class="bg-white rounded-2xl border border-slate-200 shadow-card overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-gradient-to-br from-slate-50 to-white">
                        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-teal-50 text-[#44A08D] flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            </span>
                            Articles
                            <span id="checkout-items-count" class="text-xs text-slate-400 font-normal"></span>
                        </h2>
                        <a href="{{ route('cart.index') }}" class="text-xs font-semibold text-[#44A08D] hover:underline">
                            Modifier
                        </a>
                    </div>

                    <div id="checkout-cart-items" class="px-5">
                        <div class="flex justify-center py-10">
                            <div class="w-10 h-10 border-2 border-slate-200 border-t-[#44A08D] rounded-full animate-spin"></div>
                        </div>
                    </div>
                </section>

                {{-- Méthodes de paiement --}}
                <section class="bg-white rounded-2xl border border-slate-200 shadow-card overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-br from-slate-50 to-white">
                        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <span class="w-7 h-7 rounded-lg bg-teal-50 text-[#44A08D] flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            </span>
                            Mode de paiement
                        </h2>
                    </div>

                    <div class="p-5 space-y-3">

                        {{-- Card principale : Portail E-Billing --}}
                        <div class="relative rounded-xl border-2 border-[#44A08D] bg-gradient-to-br from-teal-50/50 via-white to-white p-4 shadow-sm">
                            <div class="absolute top-3 right-3 w-6 h-6 rounded-full bg-[#44A08D] flex items-center justify-center shadow-md">
                                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-[#4ECDC4] to-[#44A08D] flex items-center justify-center text-white shadow-md shadow-[#44A08D]/25">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                </div>
                                <div class="min-w-0 pr-8">
                                    <h3 class="font-display text-base font-bold text-slate-900">Portail Sécurisé E-Billing</h3>
                                    <p class="text-xs text-slate-600 mt-0.5">Vous serez redirigé vers le portail E-Billing pour finaliser votre paiement en toute sécurité.</p>
                                </div>
                            </div>

                            {{-- Sub-options : Mobile Money + Carte Bancaire --}}
                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white border border-slate-200">
                                    <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-[11px] font-bold text-slate-900 leading-tight">Mobile Money</div>
                                        <div class="text-[10px] text-slate-500 leading-tight">Airtel · Moov</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-white border border-slate-200">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-[11px] font-bold text-slate-900 leading-tight">Carte Bancaire</div>
                                        <div class="text-[10px] text-slate-500 leading-tight">Visa</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Trust note --}}
                        <div class="flex items-start gap-2 px-1">
                            <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-[11px] text-slate-500 leading-relaxed">
                                Tous les paiements sont chiffrés et sécurisés via le portail <span class="font-semibold text-slate-700">E-Billing</span>. Aucune information bancaire n'est stockée sur nos serveurs.
                            </p>
                        </div>
                    </div>
                </section>

                {{-- ============= PAIEMENT CASH CHEZ VENDEUR ============= --}}
                <section class="bg-white rounded-2xl border border-slate-200 shadow-card overflow-hidden"
                         x-data="cashPay()" x-init="init()">
                    <button type="button" @click="open = !open"
                            class="w-full px-5 py-4 border-b border-slate-100 bg-gradient-to-br from-amber-50/40 to-white flex items-center justify-between gap-3 text-left">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-9 h-9 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
                            </span>
                            <div class="min-w-0">
                                <h2 class="text-sm font-bold text-slate-900">Payer en espèces chez un vendeur</h2>
                                <p class="text-[11px] text-slate-500 mt-0.5">Présente le code de confirmation au vendeur, paie cash, reçois tes cartes.</p>
                            </div>
                        </div>
                        <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-slate-400 shrink-0 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-cloak class="p-5 space-y-4">

                        {{-- Code vendeur + lookup --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Code vendeur</label>
                            <div class="flex gap-2">
                                <input type="text" x-model="code" @input="onCodeChange()"
                                       placeholder="KA-V-XXXX" maxlength="12"
                                       class="flex-1 px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-amber-500 focus:outline-none text-sm font-mono uppercase tracking-wider tabular-nums"
                                       style="text-transform:uppercase;">
                            </div>
                            <p class="text-[11px] text-slate-500 mt-1.5">Demande le code à ton vendeur Kardafrica (format <span class="font-mono">KA-V-XXXX</span>).</p>
                        </div>

                        {{-- Statut de la lookup --}}
                        <div x-show="lookupStatus === 'loading'" class="flex items-center gap-2 px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600">
                            <div class="w-3 h-3 border-2 border-slate-300 border-t-slate-600 rounded-full animate-spin"></div>
                            Vérification du vendeur…
                        </div>

                        <div x-show="lookupStatus === 'found'" x-cloak
                             class="flex items-start gap-3 p-4 rounded-xl bg-emerald-50/60 border border-emerald-200">
                            <div class="w-9 h-9 rounded-lg bg-emerald-500 text-white flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-bold text-emerald-900" x-text="vendor.name"></div>
                                <div class="text-[11px] text-emerald-700 mt-0.5">
                                    Vendeur Kardafrica — solde dispo : <span class="font-bold tabular-nums" x-text="fmt(vendor.available_balance) + ' FCFA'"></span>
                                </div>
                                <div x-show="!enoughBalance" x-cloak class="text-[11px] text-rose-700 font-semibold mt-1">
                                    Solde insuffisant pour cette commande. Choisis un autre vendeur.
                                </div>
                            </div>
                        </div>

                        <div x-show="lookupStatus === 'notfound'" x-cloak
                             class="flex items-start gap-3 p-4 rounded-xl bg-rose-50 border border-rose-200">
                            <svg class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <div class="text-xs text-rose-800">Vendeur introuvable ou inactif.</div>
                        </div>

                        {{-- Numéro client --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Ton numéro de téléphone</label>
                            <input type="tel" x-model="phone" placeholder="074 12 34 56"
                                   class="w-full px-4 py-3 rounded-xl border-2 border-slate-200 focus:border-amber-500 focus:outline-none text-sm">
                            <p class="text-[11px] text-slate-500 mt-1.5">Le vendeur pourra te contacter si besoin.</p>
                        </div>

                        {{-- Erreur globale --}}
                        <div x-show="error" x-cloak class="text-xs text-rose-700 bg-rose-50 border border-rose-200 px-3 py-2 rounded-lg" x-text="error"></div>

                        {{-- CTA --}}
                        <button type="button" @click="submit()"
                                :disabled="!canSubmit || submitting"
                                :class="(!canSubmit || submitting) ? 'opacity-50 cursor-not-allowed' : ''"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold shadow-lg shadow-amber-500/30 active:scale-95 transition">
                            <svg x-show="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            <svg x-show="submitting" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            <span x-show="!submitting">Réserver chez ce vendeur</span>
                            <span x-show="submitting" x-cloak>Création de la commande…</span>
                        </button>

                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Le solde du vendeur est <strong>bloqué</strong> dès la réservation : tu as 2h pour aller le voir, payer cash et recevoir tes cartes. Sinon la commande s'annule automatiquement.
                        </p>
                    </div>
                </section>
            </div>

            {{-- =====================
                 COLONNE DROITE — Sticky summary
                 ===================== --}}
            <aside class="lg:sticky lg:top-[120px] lg:self-start space-y-4">

                <div class="bg-white rounded-2xl border border-slate-200 shadow-card overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-br from-slate-50 to-white">
                        <h2 class="font-display text-base font-bold text-slate-900">Récapitulatif</h2>
                    </div>

                    <div class="px-5 py-4 space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600">Sous-total</span>
                            <span class="font-semibold text-slate-900 tabular-nums" id="checkout-subtotal">…</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600">Frais de service</span>
                            <span class="inline-flex items-center gap-1 font-semibold text-emerald-600">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Offerts
                            </span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600">Livraison</span>
                            <span class="inline-flex items-center gap-1 font-semibold text-emerald-600">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                Instantanée
                            </span>
                        </div>

                        <div class="border-t border-slate-100 pt-3 flex justify-between items-end">
                            <div>
                                <span class="text-xs uppercase tracking-wider text-slate-400 font-bold block leading-none">Total à payer</span>
                                <span class="text-[10px] text-slate-400 mt-1 block leading-none" id="checkout-total-meta">—</span>
                            </div>
                            <span class="font-display text-2xl md:text-3xl font-black text-slate-900 tabular-nums leading-none" id="checkout-total">…</span>
                        </div>
                    </div>

                    <div class="px-5 pb-5" x-data="{ simulating: false, simError: null }">
                        <button @click="$dispatch('open-checkout-modal')" type="button"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl bg-[#44A08D] hover:bg-[#3d9180] text-white text-sm font-bold shadow-lg shadow-[#44A08D]/30 active:scale-95 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            Confirmer et payer
                        </button>
                        <p class="text-[11px] text-slate-500 text-center mt-3">
                            En continuant, vous acceptez nos
                            <a href="#" class="text-[#44A08D] hover:underline">conditions générales</a>.
                        </p>

                        @if(config('app.debug'))
                            {{-- Bouton DEV : simule le paiement sans passer par E-Billing --}}
                            <div class="mt-4 pt-4 border-t border-dashed border-slate-200">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="px-1.5 py-0.5 rounded-md bg-amber-100 text-amber-800 text-[9px] font-bold tracking-wider uppercase">DEV</span>
                                    <span class="text-[11px] text-slate-500">Outil de test interne</span>
                                </div>
                                <button
                                    type="button"
                                    @click="
                                        simError = null;
                                        simulating = true;
                                        try {
                                            const res = await fetch('{{ route('checkout.simulate') }}', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'Accept': 'application/json',
                                                    'X-CSRF-TOKEN': document.querySelector('meta[name=&quot;csrf-token&quot;]').getAttribute('content'),
                                                    'X-Requested-With': 'XMLHttpRequest',
                                                },
                                                body: '{}',
                                            });
                                            const ct = res.headers.get('content-type') || '';
                                            if (!ct.includes('application/json')) throw new Error('Reponse non-JSON (status ' + res.status + ')');
                                            const data = await res.json();
                                            if (res.ok && data.success) {
                                                window.location.href = data.redirect_url;
                                            } else {
                                                throw new Error(data.message || 'Echec de la simulation');
                                            }
                                        } catch (e) {
                                            simError = e.message;
                                        } finally {
                                            simulating = false;
                                        }
                                    "
                                    :disabled="simulating"
                                    class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border-2 border-dashed border-amber-400 hover:border-amber-500 bg-amber-50 hover:bg-amber-100 text-amber-900 text-sm font-bold transition active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg x-show="!simulating" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <svg x-show="simulating" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" style="display:none;">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    <span x-show="!simulating">Simuler le paiement</span>
                                    <span x-show="simulating" style="display:none;">Simulation en cours...</span>
                                </button>
                                <p class="text-[10px] text-slate-500 text-center mt-2">
                                    Bypass E-Billing : crée la commande et appelle directement <span class="font-mono">/orders/checkout</span>
                                </p>
                                <p x-show="simError" x-cloak class="mt-2 text-xs text-rose-600 bg-rose-50 border border-rose-200 px-3 py-2 rounded-lg" x-text="simError"></p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Trust badges --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-card p-4 space-y-2.5">
                    @foreach ([
                        ['icon' => 'shield', 'top' => 'Paiement sécurisé', 'bottom' => 'Chiffrement SSL · E-Billing'],
                        ['icon' => 'bolt',   'top' => 'Livraison instant', 'bottom' => 'Code reçu en 30 secondes'],
                        ['icon' => 'check',  'top' => 'Garantie 12 mois',  'bottom' => 'Remplacement si problème'],
                    ] as $trust)
                        <div class="flex items-start gap-3">
                            <div class="shrink-0 w-8 h-8 rounded-lg bg-teal-50 text-[#44A08D] flex items-center justify-center">
                                @switch($trust['icon'])
                                    @case('shield')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                        @break
                                    @case('bolt')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        @break
                                    @default
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                @endswitch
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs font-semibold text-slate-900 leading-tight">{{ $trust['top'] }}</div>
                                <div class="text-[11px] text-slate-500 mt-0.5 leading-tight">{{ $trust['bottom'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </aside>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', async function() {
        try {
            const response = await fetch('{{ route("api.cart.index") }}');
            const data = await response.json();

            if (!data.items || data.items.length === 0) {
                window.location.href = '{{ route("cart.index") }}';
                return;
            }

            const itemsContainer = document.getElementById('checkout-cart-items');
            itemsContainer.innerHTML = '';

            const itemsCountEl = document.getElementById('checkout-items-count');
            const totalUnits = data.items.reduce((sum, i) => sum + parseInt(i.quantity || 1), 0);
            itemsCountEl.textContent = `· ${totalUnits} unité${totalUnits > 1 ? 's' : ''}`;

            const getImageUrl = (url) => {
                if (!url) return null;
                if (url.startsWith('http') || url.startsWith('//')) return url;
                if (url.startsWith('/')) return url;
                if (url.includes('/')) return '/storage/' + url;
                return '/storage/cards/' + url;
            };

            const formatFCFA = (price) => new Intl.NumberFormat('fr-FR', {
                style: 'currency', currency: 'XAF',
                minimumFractionDigits: 0, maximumFractionDigits: 0,
            }).format(price).replace('XAF', 'FCFA');

            data.items.forEach((item, index) => {
                const isLast = index === data.items.length - 1;
                const imgUrl = getImageUrl(item.image_url);
                const itemEl = document.createElement('div');
                itemEl.className = `flex items-center gap-3 py-3.5 ${isLast ? '' : 'border-b border-slate-100'}`;
                itemEl.innerHTML = `
                    <div class="shrink-0 w-12 h-12 rounded-xl overflow-hidden bg-gradient-to-br from-slate-100 to-slate-200 border border-slate-200 flex items-center justify-center">
                        ${imgUrl
                            ? `<img src="${imgUrl}" alt="${item.name}" class="w-full h-full object-cover" onerror="this.style.display='none'; this.parentElement.innerHTML='<span class=\\'text-base font-bold text-slate-500\\'>${(item.name || '?').charAt(0).toUpperCase()}</span>'">`
                            : `<span class="text-base font-bold text-slate-500">${(item.name || '?').charAt(0).toUpperCase()}</span>`
                        }
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[10px] font-bold tabular-nums">${item.quantity}×</span>
                            <p class="text-sm font-semibold text-slate-900 truncate">${item.name}</p>
                        </div>
                        <p class="text-[11px] text-slate-500">${formatFCFA(item.price)} / unité</p>
                    </div>
                    <span class="text-sm font-black text-slate-900 tabular-nums whitespace-nowrap">${formatFCFA(item.price * item.quantity)}</span>
                `;
                itemsContainer.appendChild(itemEl);
            });

            const totalEl = document.getElementById('checkout-total');
            const subEl   = document.getElementById('checkout-subtotal');
            const metaEl  = document.getElementById('checkout-total-meta');
            totalEl.textContent = formatFCFA(data.total);
            subEl.textContent   = formatFCFA(data.total);
            metaEl.textContent  = `${totalUnits} article${totalUnits > 1 ? 's' : ''}`;

            // Meta Pixel — InitiateCheckout (panier chargé sur la page de paiement)
            if (window.fbq) {
                fbq('track', 'InitiateCheckout', {
                    value: Math.round(data.total),
                    currency: 'XAF',
                    num_items: totalUnits
                });
            }

        } catch (error) {
            console.error('Error loading cart for checkout:', error);
            document.getElementById('checkout-cart-items').innerHTML =
                '<div class="py-10 text-center text-sm text-rose-600">Impossible de charger le panier. Rafraîchissez la page.</div>';
        }
    });
</script>

{{-- Alpine factory pour la section "Payer en espèces chez un vendeur" --}}
<script>
window.cashPay = function () {
    return {
        open: false,
        code: '',
        phone: '',
        vendor: { name: null, vendor_code: null, available_balance: 0 },
        lookupStatus: 'idle', // idle | loading | found | notfound
        lookupTimer: null,
        cartTotal: 0,
        submitting: false,
        error: null,

        init() {
            // Récupère le total panier (déjà calculé par le code checkout au-dessus)
            const totalEl = document.getElementById('checkout-total');
            const sync = () => {
                const txt = (totalEl?.textContent || '').replace(/\s/g, '').replace(/[^\d]/g, '');
                this.cartTotal = parseInt(txt, 10) || 0;
            };
            sync();
            // Re-sync à chaque mutation du DOM total (quand le panier finit de charger)
            new MutationObserver(sync).observe(totalEl, { childList: true, characterData: true, subtree: true });
        },

        get enoughBalance() {
            return this.vendor.available_balance >= this.cartTotal && this.cartTotal > 0;
        },
        get canSubmit() {
            return this.lookupStatus === 'found'
                && this.enoughBalance
                && this.phone.trim().length > 0;
        },

        fmt(n) { return Number(n || 0).toLocaleString('fr-FR'); },

        onCodeChange() {
            this.code = this.code.toUpperCase().trim();
            this.error = null;
            clearTimeout(this.lookupTimer);
            if (this.code.length < 6) {
                this.lookupStatus = 'idle';
                return;
            }
            this.lookupStatus = 'loading';
            this.lookupTimer = setTimeout(() => this.doLookup(), 350);
        },

        async doLookup() {
            try {
                const res = await fetch('{{ route('checkout.reseller.lookup') }}?code=' + encodeURIComponent(this.code), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await res.json();
                if (data.found) {
                    this.vendor = {
                        name: data.name,
                        vendor_code: data.vendor_code,
                        available_balance: Number(data.available_balance || 0),
                    };
                    this.lookupStatus = 'found';
                } else {
                    this.lookupStatus = 'notfound';
                }
            } catch (e) {
                this.lookupStatus = 'notfound';
            }
        },

        async submit() {
            this.error = null;
            this.submitting = true;
            try {
                const res = await fetch('{{ route('checkout.cash') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        vendor_code: this.code,
                        phone: this.phone,
                    }),
                });
                const data = await res.json();
                if (res.ok && data.success && data.redirect_url) {
                    window.location.href = data.redirect_url;
                    return;
                }
                this.error = data.message || 'Impossible de réserver la commande.';
            } catch (e) {
                this.error = 'Erreur réseau : ' + e.message;
            } finally {
                this.submitting = false;
            }
        },
    };
};
</script>
@endpush
@endsection
