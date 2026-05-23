@extends('layouts.app')

@section('title', $card->name . ' - Kardafrica')

@section('content')
@php
    $brandName = $card->brand ?? $card->name;
    if (!$card->brand) {
        $brandName = explode(' ', $card->name)[0];
    }

    // Predefined brand colors
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
        'Riot' => '#000000',
        'Valorant' => '#FF4655',
        'League' => '#000000',
    ];
    
    $brandColor = null;
    foreach ($brandColors as $key => $color) {
        if (stripos($brandName, $key) !== false) {
            $brandColor = $color;
            break;
        }
    }

    // Fallback to hash-based color if no specific brand color found
    if (!$brandColor) {
        $colors = ['#000000', '#1DB954', '#FF9900', '#E50914', '#00A4EF', '#FF0000', '#663399'];
        $hash = 0;
        $str = $brandName;
        for ($i = 0; $i < strlen($str); $i++) {
            $hash = ord($str[$i]) + (($hash << 5) - $hash);
        }
        $brandColor = $colors[abs($hash % count($colors))];
    }
    
    // Determine text color based on background
    $textColor = in_array($brandColor, ['#FF9900', '#FCD34D', '#FFFFFF']) ? '#000000' : '#FFFFFF';
@endphp

<div class="min-h-screen bg-gray-50 pb-24" x-data="cardDetails()">
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
                    <li><a href="{{ route('cards.index') }}" class="hover:text-[#4ECDC4] transition-colors">Mes Cartes</a></li>
                    <li><span class="mx-2">/</span></li>
                    <li class="font-medium text-gray-900 truncate max-w-[150px] sm:max-w-xs">{{ $card->name }}</li>
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
                                {{ $brandName }}
                            </span>
                            <h2 style="color: {{ $textColor }}" class="font-black text-4xl tracking-tighter shadow-sm truncate relative z-10">
                                {{ $card->name }}
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
                                    @if($card->image)
                                        <img src="{{ asset($card->image) }}" alt="{{ $brandName }}" class="w-full h-full object-cover"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                        <span class="text-xl font-bold text-gray-800 hidden">{{ substr($brandName, 0, 1) }}</span>
                                    @else
                                        <span class="text-xl font-bold text-gray-800">{{ substr($brandName, 0, 1) }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-col justify-between h-full pt-10 pb-6 px-6">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-bold text-gray-900 text-xl line-clamp-2 pr-2">
                                            {{ $card->name }}
                                        </h3>
                                    </div>
                                    <div class="bg-orange-50 px-2 py-1.5 rounded-lg border border-orange-100 flex-shrink-0">
                                        <span class="text-orange-600 font-bold text-xs">
                                            {{ $card->formatted_balance }}
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Decorative Bar -->
                                <div class="w-full mt-auto">
                                    <div class="flex justify-between text-xs text-gray-400 mb-1">
                                        <span>Expire le</span>
                                        <span>{{ $card->expiry_date ? $card->expiry_date->format('d/m/Y') : 'Jamais' }}</span>
                                    </div>
                                    <div class="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden">
                                        <div style="background-color: {{ $brandColor }}; width: {{ ($card->balance / $card->value) * 100 }}%" 
                                             class="h-full rounded-full transition-all duration-500"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Back of Card (Code Reveal) -->
                    <div class="absolute inset-0 backface-hidden rotate-y-180 rounded-3xl shadow-2xl overflow-hidden bg-white flex flex-col relative">
                        <!-- Top Dark Strip -->
                        <div class="h-12 bg-gray-800 mt-8 w-full"></div>

                        <div class="flex-1 flex flex-col items-center justify-center p-8">
                            <div class="w-full mb-8">
                                <div class="bg-gray-50 p-6 rounded-xl border border-gray-200 shadow-inner">
                                    <p class="text-xs text-gray-400 uppercase font-bold mb-3 text-center">Votre Code Secret</p>
                                    
                                    <div class="relative group">
                                        <div class="flex items-center justify-between bg-white border-2 border-dashed border-gray-300 rounded-lg p-4 transition-colors group-hover:border-[#4ECDC4]">
                                            <code class="text-xl font-mono font-bold text-gray-800 tracking-wider select-all text-center w-full">{{ $card->code }}</code>
                                        </div>
                                        <button @click.stop="copyCode('{{ $card->code }}')" 
                                                class="absolute right-2 top-1/2 -translate-y-1/2 p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-md transition-colors"
                                                title="Copier le code">
                                            <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                            <svg x-show="copied" class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </button>
                                    </div>
                                    <p x-show="copied" x-transition class="text-xs text-green-600 text-center mt-2 font-bold">Copié dans le presse-papier !</p>
                                </div>
                            </div>

                            <div class="w-full space-y-4">
                                <div class="flex justify-between items-center text-sm text-gray-500 border-b border-gray-100 pb-2">
                                    <span>CVV</span>
                                    <span class="font-mono">***</span>
                                </div>
                                <div class="flex justify-between items-center text-sm text-gray-500 border-b border-gray-100 pb-2">
                                    <span>Série</span>
                                    <span class="font-mono">SN-{{ substr(md5($card->id), 0, 8) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="pb-6 text-center">
                            <p class="text-gray-400 text-xs">Touchez pour retourner</p>
                        </div>
                    </div>
                </div>
                <p class="text-center text-gray-400 text-xs mt-6">Touchez la carte pour révéler le code</p>
            </div>

            <!-- Right Column: Details & Actions -->
            <div>
                <!-- Title & Value Selection Lookalike -->
                <div class="mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">{{ $card->name }}</h3>
                    <div class="flex flex-wrap gap-3">
                        <div class="px-6 py-3 rounded-xl border-2 font-bold text-sm min-w-[100px] bg-teal-50 border-[#44A08D] text-[#44A08D]">
                            <span>{{ $card->formatted_value }}</span>
                        </div>
                        <!-- Status Badge as a "tag" -->
                        <div class="px-6 py-3 rounded-xl border border-gray-200 font-bold text-sm min-w-[100px] bg-white text-gray-600">
                            {{ $card->status === 'active' ? 'ACTIF' : ucfirst($card->status) }}
                        </div>
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
                            <button @click="activeTab = 'instructions'" 
                                    :class="{ 'text-[#44A08D] border-b-2 border-[#44A08D]': activeTab === 'instructions', 'text-gray-500 hover:text-gray-700': activeTab !== 'instructions' }"
                                    class="flex-1 py-4 text-sm font-medium text-center transition-colors duration-200">
                                Instructions
                            </button>
                        </div>

                        <div class="p-6">
                            <!-- Description Tab -->
                            <div x-show="activeTab === 'description'" class="space-y-4 animate-fadeIn">
                                @if($card->description)
                                    <div class="prose prose-sm max-w-none text-gray-600">
                                        {!! nl2br(e($card->description)) !!}
                                    </div>
                                @else
                                    <p class="text-gray-500 italic">Aucune description disponible.</p>
                                @endif
                            </div>

                            <!-- Instructions Tab -->
                            <div x-show="activeTab === 'instructions'" class="space-y-4 animate-fadeIn" style="display: none;">
                                <div class="prose prose-sm max-w-none text-gray-600">
                                    <h4 class="font-bold text-gray-900 mb-2">Comment utiliser votre carte :</h4>
                                    <ol class="list-decimal pl-4 space-y-2">
                                        <li>Copiez le code secret au dos de la carte.</li>
                                        <li>Rendez-vous sur le site ou l'application du service ({{ $brandName }}).</li>
                                        <li>Accédez à la section "Utiliser une carte cadeau" ou "Paiement".</li>
                                        <li>Collez le code et validez.</li>
                                    </ol>
                                    <p class="mt-4 text-xs text-gray-500">
                                        Pour toute assistance, contactez notre support client.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Features -->
                <div class="space-y-3">
                    <div class="flex items-center">
                        <div class="bg-green-100 p-1.5 rounded-full mr-3">
                            <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <span class="text-gray-700 text-sm font-medium">
                            Acheté le {{ $card->created_at->format('d/m/Y') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Action Bar -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] p-4 z-40 pb-8 md:pb-4">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex justify-between items-center md:block">
                <span class="text-gray-500 font-medium text-sm block">Valeur de la carte</span>
                <span class="text-2xl font-black text-[#1F2937]">{{ $card->formatted_value }}</span>
            </div>
            
            <div class="flex gap-3 flex-1 md:max-w-md">
                <a href="{{ route('cards.index') }}" 
                   class="flex-1 py-3.5 rounded-xl font-bold text-[#1F2937] bg-gray-100 hover:bg-gray-200 transition-colors text-center">
                    Retour
                </a>
                <button @click="isFlipped = !isFlipped" 
                        class="flex-1 py-3.5 rounded-xl font-bold text-white bg-[#1F2937] hover:bg-black transition-colors">
                    Voir le code
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
        50% { transform: translateY(-10px); }
    }
    .animate-float {
        animation: float 4s ease-in-out infinite;
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
    function cardDetails() {
        return {
            isFlipped: false,
            copied: false,
            
            copyCode(code) {
                navigator.clipboard.writeText(code).then(() => {
                    this.copied = true;
                    setTimeout(() => {
                        this.copied = false;
                    }, 2000);
                });
            }
        }
    }
</script>
@endsection
