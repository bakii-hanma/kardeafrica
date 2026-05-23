@extends('layouts.app')

@section('title', 'Mon Panier - Kardafrica')

@section('content')
<div class="bg-gray-50 min-h-screen py-8" x-data="cartManager()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Mon Panier</h1>

        <!-- Panier Vide -->
        <div x-show="cart.length === 0" class="text-center py-16 bg-white rounded-2xl shadow-sm border border-gray-100" style="display: none;">
            <div class="mb-6">
                <svg class="w-24 h-24 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900 mb-2">Votre panier est vide</h2>
            <p class="text-gray-500 mb-8">Découvrez nos cartes cadeaux et commencez vos achats !</p>
            <a href="{{ route('boutique') }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-xl text-white bg-[#1F2937] hover:bg-[#374151] transition-colors shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                Aller à la boutique
            </a>
        </div>

        <!-- Liste du Panier -->
        <div x-show="cart.length > 0" class="lg:grid lg:grid-cols-12 lg:gap-8" style="display: none;">
            <!-- Articles -->
            <div class="lg:col-span-8">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <ul class="divide-y divide-gray-100">
                        <template x-for="(item, index) in cart" :key="item.id">
                            <li class="p-6 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center">
                                    <!-- Image / Initial -->
                                    <div class="flex-shrink-0 w-16 h-16 rounded-xl overflow-hidden bg-gray-100 border border-gray-200 flex items-center justify-center text-xl font-bold text-gray-400">
                                        <template x-if="item.image_url">
                                            <img :src="item.image_url" :alt="item.name" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!item.image_url">
                                            <span x-text="item.name.charAt(0)"></span>
                                        </template>
                                    </div>

                                    <!-- Détails -->
                                    <div class="ml-6 flex-1">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-lg font-bold text-gray-900" x-text="item.name"></h3>
                                            <p class="text-lg font-bold text-[#44A08D]" x-text="formatPrice(item.price * item.quantity)"></p>
                                        </div>
                                        <p class="mt-1 text-sm text-gray-500" x-text="'Prix unitaire: ' + formatPrice(item.price)"></p>
                                        
                                        <div class="mt-4 flex items-center justify-between">
                                            <!-- Quantité -->
                                            <div class="flex items-center border border-gray-300 rounded-lg">
                                                <button @click="updateQuantity(index, -1)" class="px-3 py-1 text-gray-600 hover:bg-gray-100 rounded-l-lg transition-colors disabled:opacity-50" :disabled="item.quantity <= 1">-</button>
                                                <span class="px-3 py-1 text-gray-900 font-medium w-8 text-center" x-text="item.quantity"></span>
                                                <button @click="updateQuantity(index, 1)" class="px-3 py-1 text-gray-600 hover:bg-gray-100 rounded-r-lg transition-colors">+</button>
                                            </div>

                                            <!-- Supprimer -->
                                            <button @click="removeItem(index)" class="text-red-500 hover:text-red-700 text-sm font-medium transition-colors flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                Supprimer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>

            <!-- Résumé -->
            <div class="lg:col-span-4 mt-8 lg:mt-0">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-32">
                    <h2 class="text-lg font-bold text-gray-900 mb-6">Résumé de la commande</h2>
                    
                    <div class="space-y-4 mb-6">
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Sous-total</span>
                            <span class="font-medium text-gray-900" x-text="formatPrice(total)"></span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Frais de service</span>
                            <span class="font-medium text-gray-900">Gratuit</span>
                        </div>
                        <div class="border-t border-gray-100 pt-4 flex justify-between items-center">
                            <span class="text-base font-bold text-gray-900">Total</span>
                            <span class="text-2xl font-black text-[#1F2937]" x-text="formatPrice(total)"></span>
                        </div>
                    </div>

                    <button @click="checkout()" class="w-full bg-[#1F2937] text-white font-bold py-4 rounded-xl shadow-lg hover:bg-[#374151] transition-all transform hover:-translate-y-0.5 mb-4 flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        Passer à la caisse
                    </button>
                    
                    <div class="flex items-center justify-center space-x-2 text-gray-400 text-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <span>Paiement 100% Sécurisé</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function cartManager() {
        return {
            cart: [],
            isLoading: false,
            
            get total() {
                return this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            },

            init() {
                this.fetchCart();
                // Listen for cart updates from other components
                window.addEventListener('cart-updated', () => this.fetchCart());
            },

            async fetchCart() {
                this.isLoading = true;
                try {
                    const response = await fetch('{{ route("api.cart.index") }}');
                    if (response.ok) {
                        const data = await response.json();
                        this.cart = data.items.map(item => ({
                            ...item,
                            price: parseFloat(item.price),
                            quantity: parseInt(item.quantity)
                        }));
                    }
                } catch (error) {
                    console.error('Erreur lors de la récupération du panier:', error);
                } finally {
                    this.isLoading = false;
                }
            },

            async updateQuantity(index, change) {
                const item = this.cart[index];
                const newQuantity = item.quantity + change;
                
                if (newQuantity > 0) {
                    try {
                        const response = await fetch(`/api/cart/${item.id}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ quantity: newQuantity })
                        });
                        
                        if (response.ok) {
                            const data = await response.json();
                            this.cart = data.items.map(item => ({
                                ...item,
                                price: parseFloat(item.price),
                                quantity: parseInt(item.quantity)
                            }));
                            // Dispatch event to update other components
                            window.dispatchEvent(new Event('cart-updated'));
                        }
                    } catch (error) {
                        console.error('Erreur lors de la mise à jour de la quantité:', error);
                    }
                }
            },

            async removeItem(index) {
                if(confirm('Voulez-vous vraiment retirer cet article ?')) {
                    const item = this.cart[index];
                    try {
                        const response = await fetch(`/api/cart/${item.id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            }
                        });
                        
                        if (response.ok) {
                            const data = await response.json();
                            this.cart = data.items.map(item => ({
                                ...item,
                                price: parseFloat(item.price),
                                quantity: parseInt(item.quantity)
                            }));
                            // Dispatch event to update other components
                            window.dispatchEvent(new Event('cart-updated'));
                        }
                    } catch (error) {
                        console.error('Erreur lors de la suppression de l\'article:', error);
                    }
                }
            },

            formatPrice(price) {
                if (typeof window.formatFCFA === 'function') {
                    return window.formatFCFA(price);
                }
                return new Intl.NumberFormat('fr-FR', { 
                    style: 'currency', 
                    currency: 'XAF',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(price).replace('XAF', 'FCFA');
            },

            checkout() {
                // Si l'utilisateur n'est pas connecté, ouvrir le modal
                @guest
                    window.dispatchEvent(new CustomEvent('open-auth-modal'));
                @else
                    // Logique de commande pour utilisateur connecté
                    window.location.href = "{{ route('checkout.index') }}";
                @endguest
            }
        }
    }
</script>
@endsection
