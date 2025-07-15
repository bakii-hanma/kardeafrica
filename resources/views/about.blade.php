@extends('layouts.app')

@section('title', 'À propos - Kardafrica')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 text-white py-20">
        <div class="absolute inset-0 bg-black opacity-50"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-5xl md:text-6xl font-bold mb-6 hero-text-animate">
                    À propos de <span class="bg-gradient-to-r from-yellow-400 to-orange-500 bg-clip-text text-transparent">Kardafrica</span>
                </h1>
                <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto hero-text-animate opacity-90">
                    La plateforme de référence pour les cartes numériques en Afrique, révolutionnant l'accès aux services digitaux
                </p>
                <div class="flex justify-center space-x-4 hero-text-animate">
                    <span class="inline-flex items-center px-4 py-2 bg-white/20 rounded-full text-sm font-medium">
                        🚀 Innovation
                    </span>
                    <span class="inline-flex items-center px-4 py-2 bg-white/20 rounded-full text-sm font-medium">
                        🌍 Afrique
                    </span>
                    <span class="inline-flex items-center px-4 py-2 bg-white/20 rounded-full text-sm font-medium">
                        💳 Digital
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission Section -->
    <section class="py-20 section-animate">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-4xl font-bold text-gray-900 mb-6 section-title">
                        Notre Mission
                    </h2>
                    <p class="text-lg text-gray-700 mb-6 leading-relaxed">
                        Chez Kardafrica, nous croyons que l'accès aux services numériques ne devrait pas être un privilège, mais un droit pour tous les Africains. Notre mission est de démocratiser l'accès aux cartes numériques et de faciliter l'inclusion financière sur le continent.
                    </p>
                    <p class="text-lg text-gray-700 mb-8 leading-relaxed">
                        Nous nous engageons à offrir une plateforme sécurisée, accessible et innovante qui répond aux besoins spécifiques de notre communauté africaine.
                    </p>
                    <div class="grid grid-cols-2 gap-6">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-kardafrica-primary mb-2">50K+</div>
                            <div class="text-gray-600">Utilisateurs actifs</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-kardafrica-primary mb-2">100+</div>
                            <div class="text-gray-600">Cartes disponibles</div>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <div class="bg-gradient-to-br from-kardafrica-primary to-kardafrica-secondary rounded-3xl p-8 shadow-2xl transform rotate-3 hover:rotate-0 transition-transform duration-500">
                        <div class="bg-white rounded-2xl p-6 shadow-lg">
                            <div class="flex items-center space-x-4 mb-6">
                                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900">Innovation Constante</h3>
                                    <p class="text-gray-600">Technologies de pointe</p>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-center space-x-3">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <span class="text-gray-700">Sécurité renforcée</span>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    <span class="text-gray-700">Support 24/7</span>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
                                    <span class="text-gray-700">Interface intuitive</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="py-20 bg-white section-animate">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4 section-title">
                    Nos Valeurs
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Les principes qui guident chacune de nos actions et décisions
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Valeur 1 -->
                <div class="text-center group card-hover-effect bg-gradient-to-br from-blue-50 to-indigo-100 p-8 rounded-2xl">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Sécurité</h3>
                    <p class="text-gray-600">Protection maximale de vos données et transactions avec les dernières technologies de cryptage.</p>
                </div>

                <!-- Valeur 2 -->
                <div class="text-center group card-hover-effect bg-gradient-to-br from-green-50 to-emerald-100 p-8 rounded-2xl">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Accessibilité</h3>
                    <p class="text-gray-600">Rendre les services numériques accessibles à tous, peu importe la localisation ou le niveau technique.</p>
                </div>

                <!-- Valeur 3 -->
                <div class="text-center group card-hover-effect bg-gradient-to-br from-purple-50 to-violet-100 p-8 rounded-2xl">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-violet-600 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Innovation</h3>
                    <p class="text-gray-600">Développement continu de solutions innovantes adaptées aux besoins africains.</p>
                </div>

                <!-- Valeur 4 -->
                <div class="text-center group card-hover-effect bg-gradient-to-br from-orange-50 to-red-100 p-8 rounded-2xl">
                    <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-red-600 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Engagement</h3>
                    <p class="text-gray-600">Dévouement total envers la satisfaction client et le développement de l'Afrique numérique.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="py-20 section-bg-pattern section-animate">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4 section-title">
                    Notre Équipe
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Des professionnels passionnés qui travaillent pour révolutionner l'écosystème numérique africain
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Team Member 1 -->
                <div class="bg-white rounded-2xl p-8 shadow-lg card-hover-effect text-center">
                    <div class="w-24 h-24 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-2xl font-bold text-white">AD</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Amadou Diallo</h3>
                    <p class="text-kardafrica-primary font-medium mb-4">CEO & Fondateur</p>
                    <p class="text-gray-600 text-sm">Expert en fintech avec plus de 10 ans d'expérience dans l'innovation numérique en Afrique.</p>
                </div>

                <!-- Team Member 2 -->
                <div class="bg-white rounded-2xl p-8 shadow-lg card-hover-effect text-center">
                    <div class="w-24 h-24 bg-gradient-to-br from-green-500 to-teal-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-2xl font-bold text-white">FK</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Fatou Kone</h3>
                    <p class="text-kardafrica-primary font-medium mb-4">CTO</p>
                    <p class="text-gray-600 text-sm">Architecte logiciel passionnée par les technologies émergentes et la sécurité informatique.</p>
                </div>

                <!-- Team Member 3 -->
                <div class="bg-white rounded-2xl p-8 shadow-lg card-hover-effect text-center">
                    <div class="w-24 h-24 bg-gradient-to-br from-orange-500 to-red-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-2xl font-bold text-white">OS</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Omar Sow</h3>
                    <p class="text-kardafrica-primary font-medium mb-4">Directeur Marketing</p>
                    <p class="text-gray-600 text-sm">Spécialiste du marketing digital avec une expertise approfondie du marché africain.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gradient-to-r from-kardafrica-primary to-kardafrica-secondary">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-white mb-6">
                Rejoignez la Révolution Numérique Africaine
            </h2>
            <p class="text-xl text-white/90 mb-8 max-w-2xl mx-auto">
                Découvrez dès maintenant notre marketplace et accédez à des centaines de cartes numériques en quelques clics
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('boutique') }}" class="bg-white text-kardafrica-primary px-8 py-4 rounded-xl font-bold text-lg hover:shadow-xl hover:scale-105 transition-all duration-300">
                    🛍️ Explorer la Marketplace
                </a>
                <a href="{{ route('contact') }}" class="border-2 border-white text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-white hover:text-kardafrica-primary transition-all duration-300">
                    📞 Nous Contacter
                </a>
            </div>
        </div>
    </section>
</div>
@endsection 