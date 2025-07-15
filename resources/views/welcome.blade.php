@extends('layouts.app')

@section('title', 'Kardafrica - Cartes numériques en un clic !')

@section('content')
<!-- Hero Carousel Section -->
<div class="relative h-96 md:h-[500px] mx-4 md:mx-8 mt-2 md:mt-4">
    <div id="bannerCarousel" class="carousel-container relative w-full h-full">
        <!-- Slides -->
        <div class="carousel-slide active netflix">
            <div class="carousel-overlay"></div>
            <div class="carousel-particles" id="particles-0"></div>
            <div class="relative h-full flex items-center justify-center text-center">
                <div class="carousel-content">
                    <h2 class="carousel-title">Netflix</h2>
                    <p class="carousel-subtitle">Profitez de millions de films et séries en streaming</p>
                    <a href="{{ route('boutique') }}" class="carousel-button">
                        Découvrir
                    </a>
                </div>
            </div>
            <div class="carousel-progress"></div>
        </div>
        
        <div class="carousel-slide spotify">
            <div class="carousel-overlay"></div>
            <div class="carousel-particles" id="particles-1"></div>
            <div class="relative h-full flex items-center justify-center text-center">
                <div class="carousel-content">
                    <h2 class="carousel-title">Spotify</h2>
                    <p class="carousel-subtitle">Écoutez vos musiques préférées sans publicité</p>
                    <a href="{{ route('boutique') }}" class="carousel-button">
                        Découvrir
                    </a>
                </div>
            </div>
            <div class="carousel-progress"></div>
        </div>
        
        <div class="carousel-slide apple">
            <div class="carousel-overlay"></div>
            <div class="carousel-particles" id="particles-2"></div>
            <div class="relative h-full flex items-center justify-center text-center">
                <div class="carousel-content">
                    <h2 class="carousel-title">Apple Store</h2>
                    <p class="carousel-subtitle">Apps, jeux, musique et plus encore</p>
                    <a href="{{ route('boutique') }}" class="carousel-button">
                        Découvrir
                    </a>
                </div>
            </div>
            <div class="carousel-progress"></div>
        </div>
        
        <div class="carousel-slide uber">
            <div class="carousel-overlay"></div>
            <div class="carousel-particles" id="particles-3"></div>
            <div class="relative h-full flex items-center justify-center text-center">
                <div class="carousel-content">
                    <h2 class="carousel-title">Uber</h2>
                    <p class="carousel-subtitle">Voyagez en toute simplicité partout dans le monde</p>
                    <a href="{{ route('boutique') }}" class="carousel-button">
                        Découvrir
                    </a>
                </div>
            </div>
            <div class="carousel-progress"></div>
        </div>
        
        <!-- Enhanced Navigation -->
        <button id="prevBtn" class="carousel-nav prev">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </button>
        <button id="nextBtn" class="carousel-nav next">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </button>
        
        <!-- Enhanced Dots -->
        <div class="carousel-dots">
            <button class="carousel-dot active" data-slide="0"></button>
            <button class="carousel-dot" data-slide="1"></button>
            <button class="carousel-dot" data-slide="2"></button>
            <button class="carousel-dot" data-slide="3"></button>
        </div>
    </div>
</div>

<!-- Hero Text Section -->
<div class="section-bg-pattern bg-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="mb-8">
            <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" 
                 alt="Kardafrica Logo" 
                 class="h-20 w-20 mx-auto mb-8 float-animation">
        </div>
        <h1 class="hero-text-animate text-4xl md:text-6xl font-bold text-gray-900 mb-8">
            <span class="section-title">Streaming, gaming, musique…</span><br>
            <span class="hero-text-animate">trouvez la carte parfaite chez Kardafrica</span>
        </h1>
        <p class="hero-text-animate text-xl md:text-2xl text-gray-600 mb-12 max-w-4xl mx-auto">
            ⚡ Livraison Instantanée 24/7 • 🎯 Plus de 50 marques disponibles • 🔒 Paiement sécurisé
        </p>
        
        <!-- Brands Grid -->
        <div class="grid grid-cols-4 md:grid-cols-8 lg:grid-cols-10 gap-4 mb-16 max-w-6xl mx-auto">
            <div class="brand-grid-item bg-gray-100 p-4 rounded-xl text-center hover:shadow-lg transition-all duration-300 cursor-pointer" data-brand="itunes">
                <div class="brand-icon text-2xl mb-2">🍎</div>
                <span class="text-sm font-medium">iTunes</span>
            </div>
            <div class="brand-grid-item bg-gray-100 p-4 rounded-xl text-center hover:shadow-lg transition-all duration-300 cursor-pointer" data-brand="psn">
                <div class="brand-icon text-2xl mb-2">🎮</div>
                <span class="text-sm font-medium">PSN</span>
            </div>
            <div class="brand-grid-item bg-gray-100 p-4 rounded-xl text-center hover:shadow-lg transition-all duration-300 cursor-pointer" data-brand="netflix">
                <div class="brand-icon text-2xl mb-2">🎬</div>
                <span class="text-sm font-medium">Netflix</span>
            </div>
            <div class="brand-grid-item bg-gray-100 p-4 rounded-xl text-center hover:shadow-lg transition-all duration-300 cursor-pointer" data-brand="google">
                <div class="brand-icon text-2xl mb-2">▶️</div>
                <span class="text-sm font-medium">Google Play</span>
            </div>
            <div class="brand-grid-item bg-gray-100 p-4 rounded-xl text-center hover:shadow-lg transition-all duration-300 cursor-pointer" data-brand="spotify">
                <div class="brand-icon text-2xl mb-2">🎵</div>
                <span class="text-sm font-medium">Spotify</span>
            </div>
            <div class="brand-grid-item bg-gray-100 p-4 rounded-xl text-center hover:shadow-lg transition-all duration-300 cursor-pointer" data-brand="amazon">
                <div class="brand-icon text-2xl mb-2">📦</div>
                <span class="text-sm font-medium">Amazon</span>
            </div>
            <div class="brand-grid-item bg-gray-100 p-4 rounded-xl text-center hover:shadow-lg transition-all duration-300 cursor-pointer" data-brand="xbox">
                <div class="brand-icon text-2xl mb-2">🎯</div>
                <span class="text-sm font-medium">Xbox</span>
            </div>
            <div class="brand-grid-item bg-gray-100 p-4 rounded-xl text-center hover:shadow-lg transition-all duration-300 cursor-pointer" data-brand="steam">
                <div class="brand-icon text-2xl mb-2">💨</div>
                <span class="text-sm font-medium">Steam</span>
            </div>
            <div class="brand-grid-item bg-gray-100 p-4 rounded-xl text-center hover:shadow-lg transition-all duration-300 cursor-pointer" data-brand="uber">
                <div class="brand-icon text-2xl mb-2">🚗</div>
                <span class="text-sm font-medium">Uber</span>
            </div>
            <div class="brand-grid-item bg-gray-100 p-4 rounded-xl text-center hover:shadow-lg transition-all duration-300 cursor-pointer" data-brand="roblox">
                <div class="brand-icon text-2xl mb-2">🧊</div>
                <span class="text-sm font-medium">Roblox</span>
            </div>
        </div>
        
        <!-- CTA Buttons -->
        <div class="hero-text-animate flex flex-col sm:flex-row gap-6 justify-center">
            <a href="{{ route('boutique') }}" class="bg-kardafrica-secondary text-white px-16 py-5 rounded-full text-xl font-semibold hover-kardafrica inline-flex items-center justify-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                </svg>
                <span>Voir toutes les cartes</span>
            </a>
            <a href="#" class="bg-kardafrica-primary text-white px-16 py-5 rounded-full text-xl font-semibold hover-kardafrica inline-flex items-center justify-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
                <span>Créer un compte</span>
            </a>
        </div>
    </div>
</div>

<!-- Gaming Section -->
<div class="section-bg-pattern bg-gray-50 py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="section-animate text-center mb-16">
            <h2 class="section-title text-4xl md:text-5xl font-bold mb-6">🎮 Gaming</h2>
            <p class="text-xl text-gray-600">Les meilleures cartes pour vos jeux préférés</p>
            <div class="w-20 h-1 bg-kardafrica-primary mx-auto mt-6 rounded-full"></div>
        </div>
        
        <div class="section-animate grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
            @php
                $gamingCards = \App\Models\Card::where('type', 'gaming')->limit(4)->get();
            @endphp
            
            @foreach($gamingCards as $card)
                <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden group">
                    <div class="relative overflow-hidden">
                        <img src="{{ asset($card->image) }}" 
                             alt="{{ $card->name }}" 
                             class="w-full h-52 object-cover group-hover:scale-110 transition-transform duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    </div>
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ $card->name }}</h3>
                        <div class="flex justify-between items-center">
                            <span class="text-2xl font-bold text-kardafrica-primary">{{ $card->formatted_value }}</span>
                            <button class="bg-kardafrica-secondary text-white px-4 py-2 rounded-full text-sm hover-kardafrica inline-flex items-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                                <span>Acheter</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="section-animate text-center">
            <a href="{{ route('boutique') }}" class="inline-flex items-center space-x-2 bg-kardafrica-primary text-white px-10 py-4 rounded-full text-lg font-semibold hover-kardafrica">
                <span>Voir toutes les cartes Gaming</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
            </a>
        </div>
    </div>
</div>

<!-- Streaming Section -->
<div class="section-bg-pattern bg-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="section-animate text-center mb-16">
            <h2 class="section-title text-4xl md:text-5xl font-bold mb-6">🎬 Streaming & Entertainment</h2>
            <p class="text-xl text-gray-600">Profitez de vos plateformes préférées</p>
            <div class="w-20 h-1 bg-kardafrica-secondary mx-auto mt-6 rounded-full"></div>
        </div>
        
        <div class="section-animate grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
            @php
                $streamingCards = \App\Models\Card::where('type', 'streaming')->limit(3)->get();
            @endphp
            
            @foreach($streamingCards as $card)
                <div class="card-hover-effect bg-gradient-to-br from-white to-gray-50 rounded-2xl card-shadow overflow-hidden group border">
                    <div class="relative overflow-hidden">
                        <img src="{{ asset($card->image) }}" 
                             alt="{{ $card->name }}" 
                             class="w-full h-60 object-cover group-hover:scale-110 transition-transform duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-3">{{ $card->name }}</h3>
                        <p class="text-gray-600 mb-4">{{ $card->description }}</p>
                        <div class="flex justify-between items-center">
                            <span class="text-2xl font-bold text-kardafrica-primary">{{ $card->formatted_value }}</span>
                            <button class="bg-kardafrica-secondary text-white px-6 py-2 rounded-full hover-kardafrica inline-flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                                <span>Acheter</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="section-animate text-center">
            <a href="{{ route('boutique') }}" class="inline-flex items-center space-x-2 bg-kardafrica-secondary text-white px-10 py-4 rounded-full text-lg font-semibold hover-kardafrica">
                <span>Voir toutes les cartes Streaming</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
            </a>
        </div>
    </div>
</div>

<!-- Apple Store Section -->
<div class="section-bg-pattern bg-gray-900 py-20 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-800 to-black"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="section-animate text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">🍎 Apple Store</h2>
            <p class="text-xl text-gray-300">Apps, jeux, musique et plus encore</p>
            <div class="w-20 h-1 bg-white mx-auto mt-6 rounded-full"></div>
        </div>
        
        <div class="section-animate grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
            @php
                $appleCards = \App\Models\Card::where('brand', 'Apple')->limit(3)->get();
            @endphp
            
            @foreach($appleCards as $card)
                <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden group">
                    <div class="relative overflow-hidden">
                        <img src="{{ asset($card->image) }}" 
                             alt="{{ $card->name }}" 
                             class="w-full h-52 object-cover group-hover:scale-110 transition-transform duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    </div>
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ $card->name }}</h3>
                        <div class="flex justify-between items-center">
                            <span class="text-xl font-bold text-kardafrica-primary">{{ $card->formatted_value }}</span>
                            <button class="bg-gray-900 text-white px-4 py-2 rounded-full hover:bg-gray-800 transition inline-flex items-center space-x-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                                <span>Acheter</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="section-animate text-center">
            <a href="{{ route('boutique') }}" class="inline-flex items-center space-x-2 bg-white text-gray-900 px-10 py-4 rounded-full text-lg font-semibold hover:bg-gray-100 transition">
                <span>Voir toutes les cartes Apple</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
            </a>
        </div>
    </div>
</div>

<!-- Shop Life Section -->
<div class="section-bg-pattern bg-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="section-animate text-center mb-16">
            <h2 class="section-title text-4xl md:text-5xl font-bold mb-6">🛍️ Shop Life - Shopping Time !</h2>
            <p class="text-xl text-gray-600">Vos marques mode et lifestyle préférées</p>
            <div class="w-20 h-1 bg-kardafrica-secondary mx-auto mt-6 rounded-full"></div>
        </div>
        
        <div class="section-animate grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
            <!-- Nike Card -->
            <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden group border">
                <div class="relative overflow-hidden">
                    <div class="w-full h-52 bg-gradient-to-br from-black to-gray-800 flex items-center justify-center">
                        <span class="text-white text-3xl font-bold">NIKE</span>
                    </div>
                    <div class="absolute top-2 right-2 bg-orange-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                        NOUVEAU
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Nike Gift Card</h3>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-kardafrica-primary">12 500 FCFA</span>
                        <button class="bg-black text-white px-4 py-2 rounded-full hover:bg-gray-800 transition inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>Acheter</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Zalando Card -->
            <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden group border">
                <div class="relative overflow-hidden">
                    <div class="w-full h-52 bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center">
                        <span class="text-white text-2xl font-bold">ZALANDO</span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Zalando Gift Card</h3>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-kardafrica-primary">25 000 FCFA</span>
                        <button class="bg-orange-500 text-white px-4 py-2 rounded-full hover:bg-orange-600 transition inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>Acheter</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- H&M Card -->
            <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden group border">
                <div class="relative overflow-hidden">
                    <div class="w-full h-52 bg-gradient-to-br from-red-600 to-red-700 flex items-center justify-center">
                        <span class="text-white text-3xl font-bold">H&M</span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">H&M Gift Card</h3>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-kardafrica-primary">15 000 FCFA</span>
                        <button class="bg-red-600 text-white px-4 py-2 rounded-full hover:bg-red-700 transition inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>Acheter</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ASOS Card -->
            <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden group border">
                <div class="relative overflow-hidden">
                    <div class="w-full h-52 bg-gradient-to-br from-black to-gray-900 flex items-center justify-center">
                        <span class="text-white text-3xl font-bold">ASOS</span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">ASOS Gift Card</h3>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-kardafrica-primary">20 000 FCFA</span>
                        <button class="bg-black text-white px-4 py-2 rounded-full hover:bg-gray-800 transition inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>Acheter</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="section-animate text-center">
            <a href="{{ route('boutique') }}" class="inline-flex items-center space-x-2 bg-kardafrica-secondary text-white px-10 py-4 rounded-full text-lg font-semibold hover-kardafrica">
                <span>Voir toutes les cartes Shopping</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
            </a>
        </div>
    </div>
</div>

<!-- Travel Time Section -->
<div class="section-bg-pattern bg-gray-50 py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="section-animate text-center mb-16">
            <h2 class="section-title text-4xl md:text-5xl font-bold mb-6">✈️ Travel Time - Prêt à Voyager !</h2>
            <p class="text-xl text-gray-600">Transport, livraison et voyage en toute simplicité</p>
            <div class="w-20 h-1 bg-gradient-to-r from-blue-500 to-green-500 mx-auto mt-6 rounded-full"></div>
        </div>
        
        <div class="section-animate grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
            <!-- Uber Eats Card -->
            <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden group border">
                <div class="relative overflow-hidden">
                    <div class="w-full h-52 bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                        <span class="text-white text-2xl font-bold">🍔 UBER EATS</span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Uber Eats</h3>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-kardafrica-primary">12 500 FCFA</span>
                        <button class="bg-green-500 text-white px-4 py-2 rounded-full hover:bg-green-600 transition inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>Acheter</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Deliveroo Card -->
            <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden group border">
                <div class="relative overflow-hidden">
                    <div class="w-full h-52 bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center">
                        <span class="text-white text-xl font-bold">🚴 DELIVEROO</span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Deliveroo</h3>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-kardafrica-primary">20€</span>
                        <button class="bg-teal-500 text-white px-4 py-2 rounded-full hover:bg-teal-600 transition inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>Acheter</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Airbnb Card -->
            <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden group border">
                <div class="relative overflow-hidden">
                    <div class="w-full h-52 bg-gradient-to-br from-red-500 to-pink-500 flex items-center justify-center">
                        <span class="text-white text-2xl font-bold">🏠 AIRBNB</span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Airbnb</h3>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-kardafrica-primary">100€</span>
                        <button class="bg-red-500 text-white px-4 py-2 rounded-full hover:bg-red-600 transition inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>Acheter</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Just Eat Card -->
            <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden group border">
                <div class="relative overflow-hidden">
                    <div class="w-full h-52 bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center">
                        <span class="text-white text-2xl font-bold">🍕 JUST EAT</span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Just Eat</h3>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-kardafrica-primary">30€</span>
                        <button class="bg-orange-500 text-white px-4 py-2 rounded-full hover:bg-orange-600 transition inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>Acheter</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="section-animate text-center">
            <a href="{{ route('boutique') }}" class="inline-flex items-center space-x-2 bg-gradient-to-r from-blue-500 to-green-500 text-white px-10 py-4 rounded-full text-lg font-semibold hover-kardafrica">
                <span>Voir toutes les cartes Voyage</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
            </a>
        </div>
    </div>
</div>

<!-- To the Moon - Crypto Section -->
<div class="section-bg-pattern bg-gradient-to-br from-purple-900 via-blue-900 to-indigo-900 py-20 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-purple-900/80 via-blue-900/80 to-indigo-900/80"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="section-animate text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">🚀 To the Moon - L'univers Crypto</h2>
            <p class="text-xl text-gray-300">Investissez dans l'avenir avec nos cartes crypto</p>
            <div class="w-20 h-1 bg-gradient-to-r from-yellow-400 to-orange-500 mx-auto mt-6 rounded-full"></div>
        </div>
        
        <div class="section-animate grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
            <!-- Crypto Voucher -->
            <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden group border">
                <div class="relative overflow-hidden">
                    <div class="w-full h-52 bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center">
                        <span class="text-white text-2xl font-bold">₿ CRYPTO</span>
                    </div>
                    <div class="absolute top-2 right-2 bg-yellow-500 text-black px-2 py-1 rounded-full text-xs font-bold">
                        🔥 HOT
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Crypto Voucher</h3>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-kardafrica-primary">100€</span>
                        <button class="bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-4 py-2 rounded-full hover:from-yellow-500 hover:to-orange-600 transition inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>Acheter</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Binance -->
            <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden group border">
                <div class="relative overflow-hidden">
                    <div class="w-full h-52 bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center">
                        <span class="text-white text-2xl font-bold">⚡ BINANCE</span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Binance</h3>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-kardafrica-primary">200€</span>
                        <button class="bg-yellow-500 text-white px-4 py-2 rounded-full hover:bg-yellow-600 transition inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>Acheter</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- NordVPN -->
            <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden group border">
                <div class="relative overflow-hidden">
                    <div class="w-full h-52 bg-gradient-to-br from-blue-600 to-blue-700 flex items-center justify-center">
                        <span class="text-white text-2xl font-bold">🛡️ NORDVPN</span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">NordVPN</h3>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-kardafrica-primary">69€</span>
                        <button class="bg-blue-600 text-white px-4 py-2 rounded-full hover:bg-blue-700 transition inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>Acheter</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="section-animate text-center">
            <a href="{{ route('boutique') }}" class="inline-flex items-center space-x-2 bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-10 py-4 rounded-full text-lg font-semibold hover-kardafrica">
                <span>Explorer l'univers Crypto</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
            </a>
        </div>
    </div>
</div>

<!-- Shop Life Section -->
<div class="section-bg-pattern bg-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="section-animate text-center mb-16">
            <h2 class="section-title text-4xl md:text-5xl font-bold mb-6">🛍️ Shop Life</h2>
            <p class="text-xl text-gray-600">Shopping Time ! Vos marques mode et lifestyle préférées</p>
            <div class="w-20 h-1 bg-kardafrica-secondary mx-auto mt-6 rounded-full"></div>
        </div>
        
        <div class="section-animate grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
            <!-- Nike Card -->
            <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden group border">
                <div class="relative overflow-hidden">
                    <div class="w-full h-52 bg-gradient-to-br from-black to-gray-800 flex items-center justify-center">
                        <span class="text-white text-3xl font-bold">NIKE</span>
                    </div>
                    <div class="absolute top-2 right-2 bg-orange-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                        NOUVEAU
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Nike Gift Card</h3>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-kardafrica-primary">25€</span>
                        <button class="bg-black text-white px-4 py-2 rounded-full hover:bg-gray-800 transition inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>Acheter</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Zalando Card -->
            <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden group border">
                <div class="relative overflow-hidden">
                    <div class="w-full h-52 bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center">
                        <span class="text-white text-2xl font-bold">ZALANDO</span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Zalando Gift Card</h3>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-kardafrica-primary">50€</span>
                        <button class="bg-orange-500 text-white px-4 py-2 rounded-full hover:bg-orange-600 transition inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>Acheter</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Amazon Card -->
            <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden group border">
                <div class="relative overflow-hidden">
                    <div class="w-full h-52 bg-gradient-to-br from-yellow-500 to-orange-500 flex items-center justify-center">
                        <span class="text-white text-3xl font-bold">amazon</span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Amazon Gift Card</h3>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-kardafrica-primary">100€</span>
                        <button class="bg-yellow-500 text-white px-4 py-2 rounded-full hover:bg-yellow-600 transition inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>Acheter</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- H&M Card -->
            <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden group border">
                <div class="relative overflow-hidden">
                    <div class="w-full h-52 bg-gradient-to-br from-red-600 to-red-700 flex items-center justify-center">
                        <span class="text-white text-3xl font-bold">H&M</span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">H&M Gift Card</h3>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-kardafrica-primary">30€</span>
                        <button class="bg-red-600 text-white px-4 py-2 rounded-full hover:bg-red-700 transition inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>Acheter</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="section-animate text-center">
            <a href="{{ route('boutique') }}" class="inline-flex items-center space-x-2 bg-kardafrica-secondary text-white px-10 py-4 rounded-full text-lg font-semibold hover-kardafrica">
                <span>Voir toutes les cartes Shopping</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
            </a>
        </div>
    </div>
</div>

<!-- Travel Time Section -->
<div class="section-bg-pattern bg-gray-50 py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="section-animate text-center mb-16">
            <h2 class="section-title text-4xl md:text-5xl font-bold mb-6">✈️ Travel Time</h2>
            <p class="text-xl text-gray-600">Prêt à Voyager ! Transport, livraison et voyage en toute simplicité</p>
            <div class="w-20 h-1 bg-gradient-to-r from-blue-500 to-green-500 mx-auto mt-6 rounded-full"></div>
        </div>
        
        <div class="section-animate grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
            <!-- Uber Eats Card -->
            <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden group border">
                <div class="relative overflow-hidden">
                    <div class="w-full h-52 bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                        <span class="text-white text-xl font-bold">🍔 UBER EATS</span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Uber Eats</h3>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-kardafrica-primary">25€</span>
                        <button class="bg-green-500 text-white px-4 py-2 rounded-full hover:bg-green-600 transition inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>Acheter</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Deliveroo Card -->
            <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden group border">
                <div class="relative overflow-hidden">
                    <div class="w-full h-52 bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center">
                        <span class="text-white text-xl font-bold">🚴 DELIVEROO</span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Deliveroo</h3>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-kardafrica-primary">20€</span>
                        <button class="bg-teal-500 text-white px-4 py-2 rounded-full hover:bg-teal-600 transition inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>Acheter</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Airbnb Card -->
            <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden group border">
                <div class="relative overflow-hidden">
                    <div class="w-full h-52 bg-gradient-to-br from-red-500 to-pink-500 flex items-center justify-center">
                        <span class="text-white text-2xl font-bold">🏠 AIRBNB</span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Airbnb</h3>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-kardafrica-primary">100€</span>
                        <button class="bg-red-500 text-white px-4 py-2 rounded-full hover:bg-red-600 transition inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>Acheter</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Just Eat Card -->
            <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden group border">
                <div class="relative overflow-hidden">
                    <div class="w-full h-52 bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center">
                        <span class="text-white text-xl font-bold">🍕 JUST EAT</span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Just Eat</h3>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-kardafrica-primary">30€</span>
                        <button class="bg-orange-500 text-white px-4 py-2 rounded-full hover:bg-orange-600 transition inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>Acheter</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="section-animate text-center">
            <a href="{{ route('boutique') }}" class="inline-flex items-center space-x-2 bg-gradient-to-r from-blue-500 to-green-500 text-white px-10 py-4 rounded-full text-lg font-semibold hover-kardafrica">
                <span>Voir toutes les cartes pour Voyager</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
            </a>
        </div>
    </div>
</div>

<!-- To the Moon - Crypto Section -->
<div class="section-bg-pattern bg-gradient-to-br from-purple-900 via-blue-900 to-indigo-900 py-20 relative overflow-hidden">
    <div class="absolute inset-0">
        <div class="absolute inset-0 bg-gradient-to-br from-purple-900/90 via-blue-900/90 to-indigo-900/90"></div>
        <!-- Stars animation -->
        <div class="absolute inset-0">
            <div class="absolute top-10 left-10 w-1 h-1 bg-white rounded-full animate-pulse"></div>
            <div class="absolute top-20 right-20 w-1 h-1 bg-white rounded-full animate-pulse" style="animation-delay: 0.5s;"></div>
            <div class="absolute bottom-20 left-20 w-1 h-1 bg-white rounded-full animate-pulse" style="animation-delay: 1s;"></div>
            <div class="absolute bottom-10 right-10 w-1 h-1 bg-white rounded-full animate-pulse" style="animation-delay: 1.5s;"></div>
        </div>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="section-animate text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">🚀 To the Moon</h2>
            <p class="text-xl text-gray-300">L'univers Crypto - Investissez dans l'avenir</p>
            <div class="w-20 h-1 bg-gradient-to-r from-yellow-400 to-orange-500 mx-auto mt-6 rounded-full"></div>
        </div>
        
        <div class="section-animate grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
            <!-- Crypto Voucher -->
            <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden group border">
                <div class="relative overflow-hidden">
                    <div class="w-full h-52 bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center">
                        <span class="text-white text-2xl font-bold">₿ CRYPTO</span>
                    </div>
                    <div class="absolute top-2 right-2 bg-yellow-500 text-black px-2 py-1 rounded-full text-xs font-bold">
                        🔥 HOT
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Crypto Voucher</h3>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-kardafrica-primary">100€</span>
                        <button class="bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-4 py-2 rounded-full hover:from-yellow-500 hover:to-orange-600 transition inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>Acheter</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Binance -->
            <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden group border">
                <div class="relative overflow-hidden">
                    <div class="w-full h-52 bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center">
                        <span class="text-white text-2xl font-bold">⚡ BINANCE</span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Binance</h3>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-kardafrica-primary">200€</span>
                        <button class="bg-yellow-500 text-white px-4 py-2 rounded-full hover:bg-yellow-600 transition inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>Acheter</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- NordVPN -->
            <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden group border">
                <div class="relative overflow-hidden">
                    <div class="w-full h-52 bg-gradient-to-br from-blue-600 to-blue-700 flex items-center justify-center">
                        <span class="text-white text-2xl font-bold">🛡️ NORDVPN</span>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">NordVPN</h3>
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-kardafrica-primary">69€</span>
                        <button class="bg-blue-600 text-white px-4 py-2 rounded-full hover:bg-blue-700 transition inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span>Acheter</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="section-animate text-center">
            <a href="{{ route('boutique') }}" class="inline-flex items-center space-x-2 bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-10 py-4 rounded-full text-xl font-semibold hover-kardafrica">
                <span>Explorer l'univers Crypto</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
            </a>
        </div>
    </div>
</div>

<!-- Popular Cards Section -->
<div class="section-bg-pattern bg-gradient-to-br from-gray-50 to-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="section-animate text-center mb-16">
            <h2 class="section-title text-4xl md:text-5xl font-bold mb-6">🔥 Cartes Populaires</h2>
            <p class="text-xl text-gray-600">Les cartes les plus vendues de la semaine</p>
            <div class="w-20 h-1 bg-gradient-to-r from-kardafrica-primary to-kardafrica-secondary mx-auto mt-6 rounded-full"></div>
        </div>
        
        <div class="section-animate grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @php
                $popularCards = \App\Models\Card::where('status', 'active')->limit(8)->get();
            @endphp
            
            @foreach($popularCards as $card)
                <div class="card-hover-effect bg-white rounded-2xl card-shadow overflow-hidden border border-gray-100 group">
                    <div class="relative overflow-hidden">
                        <img src="{{ asset($card->image) }}" 
                             alt="{{ $card->name }}" 
                             class="w-full h-44 object-cover group-hover:scale-110 transition-transform duration-500">
                        <div class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                            🔥 POPULAIRE
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    </div>
                    <div class="p-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-1">{{ $card->name }}</h3>
                        <p class="text-xs text-gray-500 mb-3">{{ $card->brand }}</p>
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-bold text-kardafrica-primary">{{ $card->formatted_value }}</span>
                            <button class="bg-kardafrica-primary text-white px-3 py-1 rounded-full text-xs hover-kardafrica inline-flex items-center space-x-1 shadow-sm">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                </svg>
                                <span>Acheter</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="section-animate text-center mt-12">
            <a href="{{ route('boutique') }}" class="inline-flex items-center space-x-2 bg-kardafrica-primary text-white px-12 py-5 rounded-full text-xl font-semibold hover-kardafrica shadow-lg">
                <span>Voir toutes les cartes</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
            </a>
        </div>
    </div>
</div>
@endsection
