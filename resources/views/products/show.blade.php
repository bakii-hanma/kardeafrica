@extends('layouts.app')

@section('title', $product['name'] . ' - Kardafrica')

@section('content')
@php
    // Brand color generation
    $colors = ['#000000', '#1DB954', '#FF9900', '#E50914', '#00A4EF', '#FF0000', '#663399'];
    $hash = 0;
    $str = $product['name'] ?? '';
    for ($i = 0; $i < strlen($str); $i++) {
        $hash = (ord($str[$i]) + (($hash << 5) - $hash)) & 0x7FFFFFFF;
    }
    $brandColor = $colors[abs($hash % count($colors))];
    
    // Determine text color based on background
    $textColor = in_array($brandColor, ['#FF9900', '#FCD34D', '#FFFFFF']) ? '#000000' : '#FFFFFF';

    // Fix logo URL if it points to localhost with wrong port
    $logoUrl = $product['logoUrl'] ?? '';
    if ($logoUrl && (str_contains($logoUrl, 'localhost') || str_contains($logoUrl, '127.0.0.1'))) {
        $logoUrl = parse_url($logoUrl, PHP_URL_PATH);
    }
@endphp

<div class="min-h-screen bg-gray-50 pb-24" x-data="productDetails()">
    <!-- Breadcrumb -->
    <div class="bg-white shadow-sm border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2 text-sm text-gray-500">
                    <li>
                        <a href="{{ route('home') }}" class="hover:text-[#4ECDC4] transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                        </a>
                    </li>
                    <li><span class="mx-2">/</span></li>
                    <li><a href="{{ route('boutique') }}" class="hover:text-[#4ECDC4] transition-colors">Boutique</a></li>
                    @if(!empty($product['categories']))
                        <li><span class="mx-2">/</span></li>
                        <li>
                            <a href="{{ route('category', $product['categories'][0]['id']) }}" class="hover:text-[#4ECDC4] transition-colors">
                                {{ $product['categories'][0]['name'] }}
                            </a>
                        </li>
                    @endif
                    <li><span class="mx-2">/</span></li>
                    <li class="font-medium text-gray-900 truncate max-w-[150px] sm:max-w-xs">{{ $product['name'] }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="lg:grid lg:grid-cols-2 lg:gap-12 items-start">
            
            <!-- Left Column: 3D Card Visual -->
            <div class="mb-8 lg:mb-0 perspective-1000">
                <div class="relative w-full max-w-md mx-auto aspect-[1.3] transition-transform duration-700 transform-style-3d cursor-pointer"
                     :class="{'rotate-y-180': isFlipped}"
                     @click="isFlipped = !isFlipped">
                    
                    <!-- Front of Card -->
                    <div class="absolute inset-0 backface-hidden rounded-3xl shadow-2xl overflow-hidden bg-white animate-float">
                        <!-- Top Section -->
                        <div style="background-color: {{ $brandColor }}; height: 55%;" class="p-6 relative overflow-hidden">
                            <span class="text-white/80 font-bold text-xs tracking-widest uppercase block mb-1">
                                {{ $product['brandName'] ?? explode(' ', $product['name'])[0] }}
                            </span>
                            <h2 style="color: {{ $textColor }}" class="font-black text-4xl tracking-tighter shadow-sm truncate relative z-10" 
                                x-text="cardDisplayTitle">
                                {{ $product['name'] }}
                            </h2>
                            
                            <!-- Decor -->
                            <div class="absolute -right-6 -bottom-6 opacity-20">
                                <div class="w-32 h-32 rounded-full bg-white/30"></div>
                            </div>
                            <div class="absolute right-6 top-6 opacity-80 text-4xl">🎁</div>
                        </div>

                        <!-- Bottom Section -->
                        <div class="h-[45%] bg-white relative">
                            <!-- Floating Logo -->
                            <div class="absolute -top-8 left-6 w-16 h-16 bg-white rounded-full p-1 shadow-md flex items-center justify-center z-20">
                                <div class="w-full h-full rounded-full bg-gray-50 flex items-center justify-center overflow-hidden border border-gray-100">
                                    @if($logoUrl)
                                        <img src="{{ $logoUrl }}" alt="{{ $product['name'] }}" class="w-full h-full object-cover">
                                    @else
                                        <span class="text-xl font-bold text-gray-800">{{ substr($product['name'], 0, 1) }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-col justify-between h-full pt-10 pb-6 px-6">
                                <div class="flex justify-between items-start">
                                    <h3 class="font-bold text-gray-900 text-xl line-clamp-2 pr-2" x-text="selectedProduct ? selectedProduct.name : productInfo.name">
                                        {{ $product['name'] }}
                                    </h3>
                                    <div class="bg-orange-50 px-2 py-1.5 rounded-lg border border-orange-100 flex-shrink-0">
                                        <span class="text-orange-600 font-bold text-xs" x-text="selectedProduct ? formatPrice(selectedProduct.price.min, selectedProduct.price.currencyCode) : priceRange"></span>
                                    </div>
                                </div>
                                
                                <!-- Decorative Bar -->
                                <div class="w-full mt-auto">
                                    <div class="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
                                        <div style="background-color: {{ $brandColor }};" 
                                             :style="'width: ' + progressWidth + '%'"
                                             class="h-full rounded-full transition-all duration-500"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Back of Card (Simple Decor) -->
                    <div class="absolute inset-0 backface-hidden rotate-y-180 rounded-3xl shadow-2xl overflow-hidden bg-gray-900 flex items-center justify-center">
                        <div class="text-center p-8">
                            <div class="text-6xl mb-4">💳</div>
                            <h3 class="text-white font-bold text-xl mb-2">Kardafrica</h3>
                            <p class="text-gray-400 text-sm">Le moyen le plus simple d'acheter des cartes cadeaux en Afrique.</p>
                        </div>
                    </div>
                </div>
                <p class="text-center text-gray-400 text-xs mt-6">Touchez la carte pour la faire tourner</p>
            </div>

            <!-- Right Column: Details & Selection -->
            <div>
                <!-- Selection Grid : n'affiche le sélecteur de montant que s'il
                     existe plusieurs dénominations ; sinon on montre juste la carte. -->
                <div class="mb-8" x-show="products.length > 1">
                    <h3 class="text-lg font-bold text-gray-900 mb-4" x-text="selectedTitle"></h3>
                    <div class="flex flex-wrap gap-3">
                <template x-for="prod in products" :key="prod.id">
                            <button @click="selectProduct(prod)"
                                    class="px-6 py-3 rounded-xl border-2 transition-all duration-200 font-bold text-sm min-w-[100px]"
                                    :class="selectedProduct && selectedProduct.id === prod.id 
                                        ? 'bg-teal-50 border-[#44A08D] text-[#44A08D]' 
                                        : 'bg-white border-gray-200 text-gray-600 hover:border-gray-300'">
                                <span x-text="formatPrice(prod.price.min, prod.price.currencyCode)"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Product Details Accordion/Tabs -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                    <div x-data="{ activeTab: 'description' }">
                        <div class="flex border-b border-gray-100">
                            <button @click="activeTab = 'description'" 
                                    :class="{ 'text-[#44A08D] border-b-2 border-[#44A08D]': activeTab === 'description', 'text-gray-500 hover:text-gray-700': activeTab !== 'description' }"
                                    class="flex-1 py-4 text-sm font-medium text-center transition-colors duration-200">
                                Description
                            </button>
                            @if(!empty($product['redemptionInstructions']))
                            <button @click="activeTab = 'redemption'" 
                                    :class="{ 'text-[#44A08D] border-b-2 border-[#44A08D]': activeTab === 'redemption', 'text-gray-500 hover:text-gray-700': activeTab !== 'redemption' }"
                                    class="flex-1 py-4 text-sm font-medium text-center transition-colors duration-200">
                                Activation
                            </button>
                            @endif
                            @if(!empty($product['terms']))
                            <button @click="activeTab = 'terms'" 
                                    :class="{ 'text-[#44A08D] border-b-2 border-[#44A08D]': activeTab === 'terms', 'text-gray-500 hover:text-gray-700': activeTab !== 'terms' }"
                                    class="flex-1 py-4 text-sm font-medium text-center transition-colors duration-200">
                                Conditions
                            </button>
                            @endif
                        </div>

                        <div class="p-6">
                            <!-- Description Tab -->
                            <div x-show="activeTab === 'description'" class="space-y-4 animate-fadeIn">
                                @if(!empty($product['description']))
                                    <div class="prose prose-sm max-w-none text-gray-600">
                                        {!! nl2br(e($product['description'])) !!}
                                    </div>
                                @else
                                    <p class="text-gray-500 italic">Aucune description disponible.</p>
                                @endif
                            </div>

                            <!-- Redemption Tab -->
                            @if(!empty($product['redemptionInstructions']))
                                <div x-show="activeTab === 'redemption'" class="space-y-4 animate-fadeIn" style="display: none;">
                                    <div class="prose prose-sm max-w-none text-gray-600">
                                        {!! nl2br(e($product['redemptionInstructions'])) !!}
                                    </div>
                                </div>
                            @endif

                            <!-- Terms Tab -->
                            @if(!empty($product['terms']))
                                <div x-show="activeTab === 'terms'" class="space-y-4 animate-fadeIn" style="display: none;">
                                    <div class="prose prose-sm max-w-none text-gray-600">
                                        {!! nl2br(e($product['terms'])) !!}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Features -->
                <div class="space-y-3">
                    <div class="flex items-center">
                        <div class="bg-green-100 p-1.5 rounded-full mr-3">
                            <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <span class="text-gray-700 text-sm font-medium">Livraison instantanée par email</span>
                    </div>
                    <div class="flex items-center">
                        <div class="bg-green-100 p-1.5 rounded-full mr-3">
                            <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <span class="text-gray-700 text-sm font-medium">Paiement sécurisé</span>
                    </div>
                    <div class="flex items-center">
                        <div class="bg-green-100 p-1.5 rounded-full mr-3">
                            <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <span class="text-gray-700 text-sm font-medium">Valide pendant 12 mois</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Similar Products -->
        @if(isset($similarProducts) && count($similarProducts) > 0)
        <div class="mt-16 border-t border-gray-100 pt-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-8">Produits similaires</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6">
                @foreach($similarProducts as $similar)
                    @php
                        // Brand color generation for similar products
                        $simHash = 0;
                        $simStr = $similar['name'] ?? '';
                        for ($i = 0; $i < strlen($simStr); $i++) {
                            $simHash = ord($simStr[$i]) + (($simHash << 5) - $simHash);
                        }
                        $simBrandColor = $colors[abs($simHash) % count($colors)];
                    @endphp
                    <a href="{{ route('product.show', $similar['internalId']) }}" class="group block bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden">
                        <!-- Similar Card Visual -->
                        <div style="background-color: {{ $simBrandColor }}" class="h-24 p-3 relative overflow-hidden">
                             <span class="text-white/80 font-bold text-[8px] tracking-widest uppercase block mb-0.5">Gift Card</span>
                             <h3 class="text-white font-black text-sm tracking-tighter shadow-sm truncate w-full pr-8 relative z-10">
                                 {{ explode(' ', $similar['name'])[0] }}
                             </h3>
                             <div class="absolute -right-2 -bottom-2 opacity-20">
                                 <div class="w-16 h-16 rounded-full bg-white/30"></div>
                             </div>
                        </div>
                        
                        <div class="bg-white p-3 pt-6 relative">
                            <div class="absolute -top-5 left-3 w-10 h-10 bg-white rounded-full p-0.5 shadow-sm flex items-center justify-center z-10">
                                <div class="w-full h-full rounded-full bg-white flex items-center justify-center overflow-hidden">
                                    @if($similar['logoUrl'])
                                        <img src="{{ $similar['logoUrl'] }}" alt="{{ $similar['name'] }}" class="w-full h-full object-cover">
                                    @else
                                        <span class="text-xs font-bold text-gray-800">{{ substr($similar['name'], 0, 1) }}</span>
                                    @endif
                                </div>
                            </div>
                            
                            <h3 class="font-bold text-gray-900 mb-1 truncate text-sm">{{ $similar['name'] }}</h3>
                            <p class="text-[#44A08D] font-medium text-xs">
                                @if(!empty($similar['price']['min']))
                                    {{ \App\Support\Money::formatFcfa($similar['price']['min'], $similar['price']['currencyCode'] ?? 'XAF') }}
                                @else
                                    Voir les offres
                                @endif
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Bottom Action Bar -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] p-4 z-40 pb-8 md:pb-4">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex justify-between items-center md:block">
                <span class="text-gray-500 font-medium text-sm block">Total à payer</span>
                <span class="text-2xl font-black text-[#1F2937]" x-text="selectedProduct ? formatPrice(selectedProduct.price.min, selectedProduct.price.currencyCode) : '0 FCFA'"></span>
            </div>
            
            <div class="flex gap-3 flex-1 md:max-w-md">
                <button @click="addToCart()" 
                        :disabled="!selectedProduct"
                        class="flex-1 py-3.5 rounded-xl font-bold text-[#1F2937] bg-gray-100 hover:bg-gray-200 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    Ajouter au panier
                </button>
                <button @click="buyNow()"
                        :disabled="!selectedProduct"
                        class="flex-1 py-3.5 rounded-xl font-bold text-white bg-[#1F2937] hover:bg-[#374151] shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                    Acheter
                </button>
            </div>
        </div>
    </div>

</div>

<style>
    .perspective-1000 { perspective: 1000px; }
    .transform-style-3d { transform-style: preserve-3d; }
    .backface-hidden { backface-visibility: hidden; }
    .rotate-y-180 { transform: rotateY(180deg); }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-15px); }
    }
    .animate-float {
        animation: float 3s ease-in-out infinite;
    }
    .animate-fadeIn {
        animation: fadeIn 0.3s ease-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(5px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script>
    function productDetails() {
        return {
            products: @json($product['products'] ?? []),
            productInfo: {
                name: @json($product['name']),
                logoUrl: @json($logoUrl),
                brandColor: @json($brandColor),
                textColor: @json($textColor)
            },
            selectedProduct: null,
            isFlipped: false,

            init() {
                if (this.products.length > 0) {
                    // Try to find the current product in the list to select it by default
                    const currentId = @json($product['id']);
                    const found = this.products.find(p => p.id === currentId);

                    if (found) {
                        this.selectedProduct = found;
                    } else {
                        this.selectedProduct = this.products[0];
                    }
                }

                // Meta Pixel — ViewContent (consultation d'une fiche produit)
                if (window.fbq && this.selectedProduct) {
                    let v = this.selectedProduct.price.min;
                    if (typeof window.convertToFCFA === 'function') {
                        v = window.convertToFCFA(v, this.selectedProduct.price.currencyCode);
                    }
                    fbq('track', 'ViewContent', {
                        content_name: this.productInfo.name,
                        content_ids: [String(this.selectedProduct.id)],
                        content_type: 'product',
                        value: Math.round(v),
                        currency: 'XAF'
                    });
                }
            },
            
            get priceRange() {
                if (!this.products.length) return '';
                const min = Math.min(...this.products.map(p => p.price.min));
                const max = Math.max(...this.products.map(p => p.price.max));
                const currency = this.products[0].price.currencyCode;
                return `${this.formatPrice(min, currency)} - ${this.formatPrice(max, currency)}`;
            },

            get progressWidth() {
                if (!this.selectedProduct || this.products.length <= 1) return 100;
                
                const min = Math.min(...this.products.map(p => p.price.min));
                const max = Math.max(...this.products.map(p => p.price.max));
                const current = this.selectedProduct.price.min;
                
                if (min === max) return 100;
                
                // Calculate percentage between min and max (20% to 100% range)
                const percent = ((current - min) / (max - min)) * 80 + 20;
                return Math.round(percent);
            },

            get selectedTitle() {
                if (!this.selectedProduct) return 'Choisir un montant';
                const name = this.selectedProduct.name;
                const price = this.formatPrice(this.selectedProduct.price.min, this.selectedProduct.price.currencyCode);
                
                if (name.includes(this.selectedProduct.price.min)) return name;
                return `${name} ${price}`;
            },

            get cardDisplayTitle() {
                if (!this.selectedProduct) return this.productInfo.name;
                const name = this.selectedProduct.name;
                const price = this.formatPrice(this.selectedProduct.price.min, this.selectedProduct.price.currencyCode);
                
                if (name.includes(this.selectedProduct.price.min) || name.includes(price)) return name;
                return `${name} ${price}`;
            },

            selectProduct(product) {
                this.selectedProduct = product;
            },

            formatPrice(price, currency) {
                if (price === undefined || price === null) return 'N/A';
                
                // Use global conversion if available
                if (typeof window.convertToFCFA === 'function') {
                    const converted = window.convertToFCFA(price, currency);
                    return window.formatFCFA(converted);
                }

                try {
                    return new Intl.NumberFormat('fr-FR', { 
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    }).format(price) + ' FCFA';
                } catch (e) {
                    console.error('Price formatting error:', e);
                    return price + ' FCFA';
                }
            },

            async addToCart() {
                if (!this.selectedProduct) return;
                
                let price = this.selectedProduct.price.min;
                
                // Convert to FCFA for cart
                if (typeof window.convertToFCFA === 'function') {
                    price = window.convertToFCFA(price, this.selectedProduct.price.currencyCode);
                }

                // Meta Pixel — AddToCart
                if (window.fbq) {
                    fbq('track', 'AddToCart', {
                        content_name: this.selectedProduct.name,
                        content_ids: [String(this.selectedProduct.id)],
                        content_type: 'product',
                        value: Math.round(price),
                        currency: 'XAF'
                    });
                }

                // Call global addToCart function
                if (typeof window.addToCart === 'function') {
                    await window.addToCart(
                        this.selectedProduct.id,
                        this.selectedProduct.name,
                        price,
                        this.productInfo.logoUrl
                    );
                } else {
                    console.error('Global addToCart function not found');
                }
            },

            buyNow() {
                this.addToCart();
                window.location.href = "{{ route('cart.index') }}";
            },
            
            showNotification(message) {
                const notification = document.createElement('div');
                notification.className = 'fixed top-4 right-4 z-50 p-4 rounded-xl shadow-lg bg-[#44A08D] text-white font-bold transform transition-all duration-300 translate-y-0 opacity-100';
                notification.textContent = message;
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.classList.add('-translate-y-full', 'opacity-0');
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            }
        }
    }
</script>
@endsection