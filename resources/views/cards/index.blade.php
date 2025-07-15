@extends('layouts.app')

@section('title', 'Mes Cartes - Kardafrica')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- En-tête -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Mes Cartes Numériques</h1>
        <div class="flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center">
            <!-- Barre de recherche -->
            <form method="GET" action="{{ route('cards.search') }}" class="flex-1 max-w-md">
                <div class="relative">
                    <input type="text" 
                           name="search" 
                           placeholder="Rechercher une carte..." 
                           value="{{ request('search') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-kardafrica-primary focus:border-transparent">
                    <button type="submit" class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                </div>
            </form>
            
            <!-- Bouton d'ajout -->
            <a href="{{ route('boutique') }}" 
               class="bg-kardafrica-secondary text-white px-6 py-2 rounded-lg hover-kardafrica">
                🛒 Marketplace
            </a>
        </div>
    </div>

    <!-- Grille des cartes -->
    @if($cards->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($cards as $card)
                <div class="bg-white rounded-xl card-shadow hover-kardafrica overflow-hidden">
                    <!-- Image de la carte -->
                    @if($card->image)
                        <img src="{{ asset($card->image) }}" 
                             alt="{{ $card->name }}" 
                             class="w-full h-48 object-cover">
                    @else
                        <div class="w-full h-48 bg-kardafrica-accent flex items-center justify-center">
                            <span class="text-white text-4xl font-bold">{{ strtoupper(substr($card->name, 0, 1)) }}</span>
                        </div>
                    @endif

                    <!-- Contenu de la carte -->
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $card->name }}</h3>
                        <p class="text-sm text-gray-600 mb-3">{{ $card->type }}</p>
                        
                        <!-- Statut -->
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-medium mb-3
                            @if($card->status === 'active') bg-green-100 text-green-800
                            @elseif($card->status === 'expired') bg-red-100 text-red-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ ucfirst($card->status) }}
                        </span>

                        <!-- Valeur et solde -->
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-sm text-gray-500">Solde:</span>
                            <span class="text-lg font-bold text-kardafrica-primary">{{ $card->formatted_balance }}</span>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2">
                            <a href="{{ route('cards.show', $card) }}" 
                               class="flex-1 bg-kardafrica-primary text-white py-2 px-4 rounded-lg text-center text-sm hover-kardafrica">
                                Voir
                            </a>
                            <a href="{{ route('cards.edit', $card) }}" 
                               class="flex-1 bg-gray-100 text-gray-700 py-2 px-4 rounded-lg text-center text-sm hover-kardafrica">
                                Modifier
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $cards->links() }}
        </div>
    @else
        <!-- État vide -->
        <div class="text-center py-12">
            <div class="mb-4">
                <svg class="w-24 h-24 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Aucune carte dans votre collection</h3>
            <p class="text-gray-600 mb-6">Découvrez notre marketplace pour acheter vos premières cartes</p>
            <a href="{{ route('boutique') }}" 
               class="inline-block bg-kardafrica-secondary text-white px-8 py-3 rounded-lg hover-kardafrica">
                Explorer la Marketplace
            </a>
        </div>
    @endif
</div>
@endsection 