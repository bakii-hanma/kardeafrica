@extends('layouts.app')

@section('title', 'Mon Panier - KardAfrica')

@section('content')
<div class="min-h-screen bg-[#FAFAF7] pb-20" x-data="cartManager()">

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
                <span class="text-slate-900 font-medium">Panier</span>
            </nav>
            <a href="{{ route('boutique') }}" class="hidden sm:inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-[#44A08D] transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Continuer mes achats
            </a>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 md:pt-10">

        {{-- ================================================================
             TITRE
             ================================================================ --}}
        <div class="flex items-end justify-between mb-6">
            <div>
                <h1 class="font-display text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">Mon panier</h1>
                <p class="text-sm text-slate-500 mt-1">
                    <span x-show="!loading && items.length > 0">
                        <span class="font-semibold text-slate-700" x-text="items.length"></span>
                        article<span x-show="items.length > 1">s</span> dans votre panier
                    </span>
                    <span x-show="loading">Chargement…</span>
                    <span x-show="!loading && items.length === 0">Aucun article pour le moment.</span>
                </p>
            </div>
        </div>

        {{-- ================================================================
             EMPTY STATE
             ================================================================ --}}
        <template x-if="loading">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-card p-12 text-center">
                <div class="w-12 h-12 border-2 border-slate-200 border-t-[#44A08D] rounded-full animate-spin mx-auto mb-4"></div>
                <p class="text-sm text-slate-500">Chargement de votre panier…</p>
            </div>
        </template>

        <template x-if="!loading && items.length === 0">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-card p-10 md:p-14 text-center">
                <div class="w-20 h-20 mx-auto mb-5 rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center">
                    <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </div>
                <h3 class="font-display text-xl font-bold text-slate-900 mb-2">Votre panier est vide</h3>
                <p class="text-sm text-slate-500 max-w-md mx-auto mb-6">
                    Découvrez plus de 300 marques de cartes cadeaux numériques. Livraison instantanée dès l'achat.
                </p>
                <a href="{{ route('boutique') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#44A08D] hover:bg-[#3d9180] text-white text-sm font-semibold shadow-lg shadow-[#44A08D]/25 active:scale-95 transition">
                    Voir le catalogue
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        </template>

        {{-- ================================================================
             CART CONTENT
             ================================================================ --}}
        <div x-show="!loading && items.length > 0" class="grid grid-cols-1 lg:grid-cols-[1fr_380px] gap-6 lg:gap-8" style="display:none;">

            {{-- LISTE DES ARTICLES --}}
            <div>
                <div class="bg-white rounded-2xl border border-slate-200 shadow-card overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-gradient-to-br from-slate-50 to-white">
                        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#44A08D]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            Articles
                        </h2>
                        <button @click="clearCart()" type="button"
                                class="text-xs font-semibold text-slate-500 hover:text-rose-600 transition">
                            Vider le panier
                        </button>
                    </div>

                    <ul class="divide-y divide-slate-100">
                        <template x-for="item in items" :key="item.id">
                            <li class="p-4 sm:p-5 hover:bg-slate-50/50 transition group">
                                <div class="flex gap-3 sm:gap-4">
                                    {{-- Logo / image --}}
                                    <div class="shrink-0 w-16 h-16 sm:w-20 sm:h-20 rounded-xl overflow-hidden bg-gradient-to-br from-slate-100 to-slate-200 border border-slate-200 flex items-center justify-center">
                                        <template x-if="item.image_url">
                                            <img :src="item.image_url" :alt="item.name" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!item.image_url">
                                            <span class="font-display text-2xl font-bold text-slate-500" x-text="item.name.charAt(0).toUpperCase()"></span>
                                        </template>
                                    </div>

                                    {{-- Détails --}}
                                    <div class="flex-1 min-w-0 flex flex-col gap-2">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <h3 class="text-sm sm:text-base font-semibold text-slate-900 truncate" x-text="item.name"></h3>
                                                <p class="text-xs text-slate-500 mt-0.5">Carte cadeau numérique</p>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <p class="text-sm sm:text-base font-black tabular-nums text-slate-900" x-text="formatPrice(item.price * item.quantity)"></p>
                                                <p x-show="item.quantity > 1" class="text-[10px] text-slate-400 mt-0.5" x-text="formatPrice(item.price) + ' / unité'"></p>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-between gap-3 mt-1">
                                            {{-- Quantite --}}
                                            <div class="inline-flex items-center bg-slate-50 border border-slate-200 rounded-lg overflow-hidden">
                                                <button @click="updateQuantity(item.id, item.quantity - 1)" type="button"
                                                        :disabled="item.quantity <= 1"
                                                        class="w-8 h-8 flex items-center justify-center text-slate-600 hover:bg-white hover:text-rose-600 transition disabled:opacity-30 disabled:cursor-not-allowed active:scale-95"
                                                        aria-label="Diminuer">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                                </button>
                                                <span class="w-9 text-center text-sm font-semibold text-slate-900 tabular-nums" x-text="item.quantity"></span>
                                                <button @click="updateQuantity(item.id, item.quantity + 1)" type="button"
                                                        class="w-8 h-8 flex items-center justify-center text-slate-600 hover:bg-white hover:text-[#44A08D] transition active:scale-95"
                                                        aria-label="Augmenter">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                                </button>
                                            </div>

                                            {{-- Supprimer --}}
                                            <button @click="removeItem(item.id)" type="button"
                                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-rose-600 transition active:scale-95"
                                                    aria-label="Supprimer cet article">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                <span class="hidden sm:inline">Supprimer</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>

                {{-- Continue shopping (mobile) --}}
                <a href="{{ route('boutique') }}"
                   class="lg:hidden mt-4 inline-flex items-center gap-2 text-sm font-semibold text-[#44A08D] hover:underline">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Continuer mes achats
                </a>
            </div>

            {{-- RESUME --}}
            <aside class="lg:sticky lg:top-[120px] lg:self-start space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-card overflow-hidden">

                    {{-- Header summary --}}
                    <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-br from-slate-50 to-white">
                        <h2 class="font-display text-base font-bold text-slate-900">Résumé de la commande</h2>
                    </div>

                    {{-- Lignes --}}
                    <div class="px-5 py-4 space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600">Sous-total</span>
                            <span class="font-semibold text-slate-900 tabular-nums" x-text="formatPrice(total)"></span>
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
                                <span class="text-[10px] text-slate-400 mt-1 block leading-none" x-text="items.length + ' article' + (items.length > 1 ? 's' : '')"></span>
                            </div>
                            <span class="font-display text-2xl md:text-3xl font-black text-slate-900 tabular-nums leading-none" x-text="formatPrice(total)"></span>
                        </div>
                    </div>

                    {{-- CTA --}}
                    <div class="px-5 pb-5">
                        @auth
                            <a href="{{ route('checkout.index') }}"
                               class="w-full inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl bg-[#44A08D] hover:bg-[#3d9180] text-white text-sm font-bold shadow-lg shadow-[#44A08D]/30 active:scale-95 transition">
                                Passer au paiement
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        @else
                            <button @click="$dispatch('open-auth-modal'); $dispatch('set-auth-view', { view: 'login' })" type="button"
                                    class="w-full inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl bg-[#44A08D] hover:bg-[#3d9180] text-white text-sm font-bold shadow-lg shadow-[#44A08D]/30 active:scale-95 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                Se connecter pour payer
                            </button>
                            <p class="text-[11px] text-slate-500 text-center mt-2.5">
                                Pas encore de compte ?
                                <button @click="$dispatch('open-auth-modal'); $dispatch('set-auth-view', { view: 'register' })" type="button"
                                        class="text-[#44A08D] font-semibold hover:underline">
                                    S'inscrire
                                </button>
                            </p>
                        @endauth
                    </div>
                </div>

                {{-- Trust strip --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-card p-4 space-y-2.5">
                    @foreach ([
                        ['icon' => 'shield', 'top' => 'Paiement sécurisé', 'bottom' => 'E-Billing · Mobile Money & cartes'],
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

                {{-- Moyens de paiement acceptes --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-card p-4">
                    <p class="text-[10px] uppercase tracking-wider text-slate-400 font-bold mb-3">Moyens de paiement</p>
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach ([
                            ['name' => 'Airtel Money',   'bg' => 'bg-rose-600',    'short' => 'AIRTEL'],
                            ['name' => 'Moov Money',     'bg' => 'bg-blue-600',    'short' => 'MOOV'],
                            ['name' => 'Visa',           'bg' => 'bg-indigo-700',  'short' => 'VISA'],
                        ] as $pay)
                            <div class="px-2.5 py-1.5 rounded-md {{ $pay['bg'] }} text-white text-[10px] font-bold tracking-tight" title="{{ $pay['name'] }}">
                                {{ $pay['short'] }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<script>
function cartManager() {
    return {
        items: [],
        total: 0,
        loading: true,

        init() {
            this.fetchCart();
            window.addEventListener('cart-updated', () => this.fetchCart());
        },

        async fetchCart() {
            this.loading = true;
            try {
                const response = await fetch("{{ route('api.cart.index') }}");
                const data = await response.json();
                this.items = data.items || [];
                this.total = data.total || 0;
            } catch (error) {
                console.error('Error fetching cart:', error);
            } finally {
                this.loading = false;
            }
        },

        async updateQuantity(id, newQuantity) {
            if (newQuantity < 1) return;
            try {
                const response = await fetch(`/api/cart/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ quantity: newQuantity })
                });
                if (response.ok) {
                    this.fetchCart();
                    window.dispatchEvent(new CustomEvent('cart-updated'));
                }
            } catch (error) {
                console.error('Error updating cart:', error);
            }
        },

        async removeItem(id) {
            if (!confirm('Voulez-vous vraiment retirer cet article du panier ?')) return;
            try {
                const response = await fetch(`/api/cart/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                if (response.ok) {
                    this.fetchCart();
                    window.dispatchEvent(new CustomEvent('cart-updated'));
                }
            } catch (error) {
                console.error('Error removing item:', error);
            }
        },

        async clearCart() {
            if (!confirm('Vider entièrement votre panier ?')) return;
            try {
                const response = await fetch('/api/cart', {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                if (response.ok) {
                    this.fetchCart();
                    window.dispatchEvent(new CustomEvent('cart-updated'));
                }
            } catch (error) {
                console.error('Error clearing cart:', error);
            }
        },

        formatPrice(price) {
            if (typeof window.formatFCFA === 'function') {
                return window.formatFCFA(price);
            }
            return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XAF' }).format(price).replace('XAF', 'FCFA');
        }
    }
}
</script>
@endsection
