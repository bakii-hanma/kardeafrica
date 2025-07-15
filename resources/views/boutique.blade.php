@extends('layouts.app')

@section('title', 'Boutique - Kardafrica')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Hero Section -->
    <div class="bg-kardafrica-primary rounded-2xl p-8 mb-8 text-white">
        <div class="text-center">
            <h1 class="text-4xl font-bold mb-4">Boutique Kardafrica</h1>
            <p class="text-xl text-gray-100 mb-6">Découvrez nos cartes numériques les plus populaires</p>
            <div class="flex justify-center gap-4">
                <span class="bg-white/20 px-4 py-2 rounded-full text-sm">🎁 Cartes Cadeaux</span>
                <span class="bg-white/20 px-4 py-2 rounded-full text-sm">🎮 Gaming</span>
                <span class="bg-white/20 px-4 py-2 rounded-full text-sm">📱 Mobile</span>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="flex flex-wrap gap-4 mb-8">
        <button class="px-6 py-2 bg-kardafrica-secondary text-white rounded-lg hover-kardafrica">
            Toutes les cartes
        </button>
        <button class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover-kardafrica">
            Streaming
        </button>
        <button class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover-kardafrica">
            Gaming
        </button>
        <button class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover-kardafrica">
            Digital
        </button>
        <button class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover-kardafrica">
            Transport
        </button>
        <button class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover-kardafrica">
            Shopping
        </button>
        <button class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover-kardafrica">
            Mobile
        </button>
    </div>

    <!-- Grille des cartes -->
    @if($cards->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($cards as $card)
                <div class="bg-white rounded-xl card-shadow hover-kardafrica overflow-hidden">
                    <!-- Image -->
                    @if($card->image)
                        <img src="{{ asset($card->image) }}" 
                             alt="{{ $card->name }}" 
                             class="w-full h-48 object-cover">
                    @else
                        <div class="w-full h-48 bg-gradient-to-br from-blue-400 to-purple-600 flex items-center justify-center">
                            <span class="text-white text-4xl font-bold">{{ strtoupper(substr($card->name, 0, 1)) }}</span>
                        </div>
                    @endif

                    <!-- Contenu -->
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $card->name }}</h3>
                            @if($card->brand)
                                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">{{ $card->brand }}</span>
                            @endif
                        </div>
                        
                        <p class="text-sm text-gray-600 mb-4">{{ $card->type }}</p>

                        <!-- Prix -->
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-2xl font-bold text-kardafrica-primary">{{ $card->formatted_value }}</span>
                            <span class="text-sm text-gray-500">{{ $card->currency }}</span>
                        </div>

                        <!-- Description -->
                        @if($card->description)
                            <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $card->description }}</p>
                        @endif

                        <!-- Actions -->
                        <div class="flex gap-2">
                            <a href="{{ route('cards.show', $card) }}" 
                               class="flex-1 bg-kardafrica-primary text-white py-2 px-4 rounded-lg text-center text-sm hover-kardafrica">
                                Voir Détails
                            </a>
                            <button class="flex-1 bg-kardafrica-secondary text-white py-2 px-4 rounded-lg text-sm hover-kardafrica">
                                Acheter
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- État vide -->
        <div class="text-center py-12">
            <div class="mb-4">
                <svg class="w-24 h-24 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Aucune carte disponible</h3>
            <p class="text-gray-600 mb-6">Revenez bientôt pour découvrir nos nouveautés</p>
            <a href="{{ route('home') }}" 
               class="inline-block bg-kardafrica-secondary text-white px-8 py-3 rounded-lg hover-kardafrica">
                Retour à l'accueil
            </a>
        </div>
    @endif
</div>
@endsection 