@extends('layouts.app')

@section('title', 'Créer une Carte - Kardafrica')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Créer une Nouvelle Carte</h1>
        <p class="text-gray-600">Créez votre carte numérique en quelques clics</p>
    </div>

    <div class="bg-white rounded-xl card-shadow p-8">
        <form method="POST" action="{{ route('cards.store') }}" enctype="multipart/form-data">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nom de la carte -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nom de la carte *
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="{{ old('name') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-kardafrica-primary focus:border-transparent @error('name') border-red-500 @enderror"
                           placeholder="Ex: Carte Cadeau Amazon">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Type de carte -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                        Type de carte *
                    </label>
                    <select id="type" 
                            name="type" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-kardafrica-primary focus:border-transparent @error('type') border-red-500 @enderror">
                        <option value="">Choisir un type</option>
                        <option value="cadeau" {{ old('type') === 'cadeau' ? 'selected' : '' }}>Carte Cadeau</option>
                        <option value="prepayee" {{ old('type') === 'prepayee' ? 'selected' : '' }}>Carte Prépayée</option>
                        <option value="gaming" {{ old('type') === 'gaming' ? 'selected' : '' }}>Carte Gaming</option>
                        <option value="mobile" {{ old('type') === 'mobile' ? 'selected' : '' }}>Recharge Mobile</option>
                        <option value="autre" {{ old('type') === 'autre' ? 'selected' : '' }}>Autre</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Valeur -->
                <div>
                    <label for="value" class="block text-sm font-medium text-gray-700 mb-2">
                        Valeur *
                    </label>
                    <input type="number" 
                           id="value" 
                           name="value" 
                           value="{{ old('value') }}"
                           step="0.01"
                           min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-kardafrica-primary focus:border-transparent @error('value') border-red-500 @enderror"
                           placeholder="0.00">
                    @error('value')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Devise -->
                <div>
                    <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">
                        Devise *
                    </label>
                    <select id="currency" 
                            name="currency" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-kardafrica-primary focus:border-transparent @error('currency') border-red-500 @enderror">
                        <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>EUR (Euro)</option>
                        <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>USD (Dollar)</option>
                        <option value="XOF" {{ old('currency') === 'XOF' ? 'selected' : '' }}>XOF (Franc CFA)</option>
                        <option value="GBP" {{ old('currency') === 'GBP' ? 'selected' : '' }}>GBP (Livre Sterling)</option>
                    </select>
                    @error('currency')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Marque -->
                <div>
                    <label for="brand" class="block text-sm font-medium text-gray-700 mb-2">
                        Marque
                    </label>
                    <input type="text" 
                           id="brand" 
                           name="brand" 
                           value="{{ old('brand') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-kardafrica-primary focus:border-transparent @error('brand') border-red-500 @enderror"
                           placeholder="Ex: Amazon, Google Play, Steam">
                    @error('brand')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Date d'expiration -->
                <div>
                    <label for="expiry_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Date d'expiration
                    </label>
                    <input type="date" 
                           id="expiry_date" 
                           name="expiry_date" 
                           value="{{ old('expiry_date') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-kardafrica-primary focus:border-transparent @error('expiry_date') border-red-500 @enderror">
                    @error('expiry_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Description -->
            <div class="mt-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    Description
                </label>
                <textarea id="description" 
                          name="description" 
                          rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-kardafrica-primary focus:border-transparent @error('description') border-red-500 @enderror"
                          placeholder="Description de la carte...">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Image -->
            <div class="mt-6">
                <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                    Image de la carte
                </label>
                <input type="file" 
                       id="image" 
                       name="image" 
                       accept="image/*"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-kardafrica-primary focus:border-transparent @error('image') border-red-500 @enderror">
                @error('image')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">Formats acceptés: JPG, PNG, JPEG (max 2MB)</p>
            </div>

            <!-- Actions -->
            <div class="mt-8 flex gap-4">
                <button type="submit" 
                        class="bg-[#1F2937] text-white px-8 py-3 rounded-lg hover:bg-gray-800 transition-colors">
                    Créer la Carte
                </button>
                <a href="{{ route('cards.index') }}" 
                   class="bg-gray-100 text-gray-700 px-8 py-3 rounded-lg hover-kardafrica">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>
@endsection 