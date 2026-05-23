@extends('layouts.app')

@section('title', ($currentCategory['name'] ?? 'Catégorie') . ' - Kardafrica')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
    <!-- Header de la catégorie -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between">
                <div>
                    <nav class="flex" aria-label="Breadcrumb">
                        <ol class="flex items-center space-x-4">
                            <li>
                                <div>
                                    <a href="{{ route('home') }}" class="text-gray-400 hover:text-gray-500">
                                        <svg class="flex-shrink-0 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                                        </svg>
                                    </a>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <a href="{{ route('boutique') }}" class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700">
                                        Boutique
                                    </a>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="ml-4 text-sm font-medium text-gray-900">
                                        {{ $currentCategory ? $currentCategory['name'] : 'Catégorie' }}
                                    </span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                    <h1 class="mt-4 text-3xl font-bold text-gray-900">
                        {{ $currentCategory['name'] ?? 'Catégorie' }}
                    </h1>
                    @if(!empty($currentCategory['description']))
                        <p class="mt-2 text-gray-600">{{ $currentCategory['description'] }}</p>
                    @endif
                </div>
                
                <!-- Filtres et tri -->
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <label for="sort" class="text-sm font-medium text-gray-700">Trier par:</label>
                        <select id="sort" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-kardafrica-primary">
                            <option value="name">Nom</option>
                            <option value="price_asc">Prix croissant</option>
                            <option value="price_desc">Prix décroissant</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Sidebar avec filtres -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Filtres</h3>
                    
                    <!-- Catégories -->
                    <div class="mb-6">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Catégories</h4>
                        <div class="space-y-2">
                            @foreach($categories as $category)
                                <a href="{{ route('category', $category['id']) }}" 
                                   class="flex items-center justify-between text-sm text-gray-600 hover:text-kardafrica-primary transition-colors duration-200 {{ $currentCategory && $currentCategory['id'] == $category['id'] ? 'text-kardafrica-primary font-medium' : '' }}">
                                    <span>{{ $category['name'] }}</span>
                                    @if($currentCategory && $currentCategory['id'] == $category['id'])
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <!-- Prix -->
                    <div class="mb-6">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Prix</h4>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded border-gray-300 text-kardafrica-primary focus:ring-kardafrica-primary">
                                <span class="ml-2 text-sm text-gray-600">Moins de 50 FCFA</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded border-gray-300 text-kardafrica-primary focus:ring-kardafrica-primary">
                                <span class="ml-2 text-sm text-gray-600">50 - 100 FCFA</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded border-gray-300 text-kardafrica-primary focus:ring-kardafrica-primary">
                                <span class="ml-2 text-sm text-gray-600">100 - 500 FCFA</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded border-gray-300 text-kardafrica-primary focus:ring-kardafrica-primary">
                                <span class="ml-2 text-sm text-gray-600">Plus de 500 FCFA</span>
                            </label>
                        </div>
                    </div>

                    <!-- Pays -->
                    <div class="mb-6">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Pays</h4>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded border-gray-300 text-kardafrica-primary focus:ring-kardafrica-primary">
                                <span class="ml-2 text-sm text-gray-600">Sénégal</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded border-gray-300 text-kardafrica-primary focus:ring-kardafrica-primary">
                                <span class="ml-2 text-sm text-gray-600">Côte d'Ivoire</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded border-gray-300 text-kardafrica-primary focus:ring-kardafrica-primary">
                                <span class="ml-2 text-sm text-gray-600">Mali</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grille des produits -->
            <div class="lg:col-span-3">
                @if(count($products) > 0)
                    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-4" id="productsGrid">
                        @foreach($products as $product)
                            @php
                                $brandName = $product['name'];
                                $brandColors = [
                                    'Netflix' => '#E50914',
                                    'Spotify' => '#1DB954',
                                    'Apple' => '#000000',
                                    'iTunes' => '#D60017',
                                    'PlayStation' => '#00439C',
                                    'Xbox' => '#107C10',
                                    'Amazon' => '#FF9900',
                                    'Google' => '#4285F4',
                                    'Steam' => '#171A21',
                                    'Uber' => '#000000',
                                    'Roblox' => '#00A2FF',
                                    'Nintendo' => '#E60012',
                                ];
                                $bgColor = '#4ECDC4'; // Default
                                foreach ($brandColors as $key => $color) {
                                    if (stripos($brandName, $key) !== false) {
                                        $bgColor = $color;
                                        break;
                                    }
                                }
                                // Handle light text for light backgrounds if any (most seem dark/bold colors, so white text is safe)
                                $textColor = 'text-white';
                                
                                // Price handling
                                $minPrice = 0;
                                $currencyCode = 'XAF';
                                if (isset($product['products']) && count($product['products']) > 0) {
                                    $minPrice = $product['products'][0]['price']['min'] ?? 0;
                                    $currencyCode = $product['products'][0]['price']['currencyCode'] ?? 'XAF';
                                }
                            @endphp

                            <div class="relative w-full bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 hover:shadow-xl transition-all duration-300 group transform hover:-translate-y-1">
                                <!-- Top Section (Brand Color) -->
                                <div style="background-color: {{ $bgColor }}" class="h-28 p-3 relative overflow-hidden">
                                    <h4 class="text-white/80 font-bold text-[10px] tracking-widest uppercase mb-1 truncate">
                                        {{ $brandName }}
                                    </h4>
                                    <h3 class="{{ $textColor }} font-black text-lg leading-tight tracking-tighter shadow-sm line-clamp-2">
                                        {{ $product['name'] }}
                                    </h3>
                                    <!-- Decorative Elements -->
                                    <div class="absolute -right-2 -bottom-2 opacity-20">
                                        <div class="w-20 h-20 rounded-full bg-white/30"></div>
                                    </div>
                                    <div class="absolute right-2 bottom-2 text-xl animate-bounce">
                                        🎁
                                    </div>
                                </div>

                                <!-- Bottom Section -->
                                <div class="bg-white p-3 pt-8 relative">
                                    <!-- Floating Logo -->
                                    <div class="absolute -top-5 left-3 w-10 h-10 bg-white rounded-full p-0.5 shadow-md flex items-center justify-center z-10">
                                        <div class="w-full h-full rounded-full bg-gray-50 flex items-center justify-center overflow-hidden">
                                            @if(!empty($product['logoUrl']))
                                                <img src="{{ $product['logoUrl'] }}" 
                                                     alt="{{ $brandName }}" 
                                                     class="w-full h-full object-contain transform group-hover:scale-110 transition-transform duration-300">
                                            @else
                                                <span class="text-sm font-bold text-gray-800">{{ substr($brandName, 0, 1) }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <h3 class="text-sm font-bold text-gray-900 mb-1 truncate" title="{{ $product['name'] }}">
                                        {{ $product['name'] }}
                                    </h3>

                                    <!-- Badge -->
                                    <div class="bg-green-100 self-start inline-block px-1.5 py-0.5 rounded-md mb-2">
                                        <span class="text-green-700 text-[10px] font-bold">2% OFF</span>
                                    </div>

                                    <!-- Price and Action -->
                                    <div class="flex items-end justify-between mt-1">
                                        <div>
                                            <p class="text-[10px] text-gray-500 mb-0.5">À partir de</p>
                                            <p class="text-base font-bold text-gray-900 leading-none price-display"
                                               data-price="{{ $minPrice }}"
                                               data-currency="{{ $currencyCode }}"
                                               data-processed="true">
                                                {{ \App\Support\Money::formatFcfa($minPrice, $currencyCode) }}
                                            </p>
                                        </div>
                                        
                                        <button class="add-to-cart-btn bg-gray-900 text-white p-1.5 rounded-lg hover:bg-kardafrica-primary transition-colors duration-200 shadow-md relative z-20"
                                                data-product-id="{{ $product['id'] }}"
                                                data-product-name="{{ $product['name'] }}"
                                                data-price="{{ $minPrice }}"
                                                data-currency-code="{{ $currencyCode }}"
                                                data-image-url="{{ $product['logoUrl'] ?? '' }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    <!-- View Details Link Overlay -->
                                    <a href="{{ route('product.show', $product['id']) }}" class="absolute inset-0 z-0"></a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-8 flex justify-center">
                        <nav class="flex items-center space-x-2">
                            <a href="#" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                Précédent
                            </a>
                            <a href="#" class="px-3 py-2 text-sm font-medium text-white bg-kardafrica-primary border border-kardafrica-primary rounded-md">
                                1
                            </a>
                            <a href="#" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                2
                            </a>
                            <a href="#" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                3
                            </a>
                            <a href="#" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                Suivant
                            </a>
                        </nav>
                    </div>
                @else
                    <!-- État vide -->
                    <div class="text-center py-12">
                        <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun produit trouvé</h3>
                        <p class="text-gray-500 mb-6">Aucun produit n'est disponible dans cette catégorie pour le moment.</p>
                        <a href="{{ route('boutique') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-kardafrica-primary hover:bg-kardafrica-primary/90">
                            Voir tous les produits
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection 