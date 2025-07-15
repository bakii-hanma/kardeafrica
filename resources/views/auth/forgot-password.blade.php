@extends('layouts.app')

@section('title', 'Mot de passe oublié - Kardafrica')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Header -->
        <div class="text-center">
            <div class="mx-auto w-20 h-20 bg-gradient-to-r from-kardafrica-primary to-kardafrica-secondary rounded-full flex items-center justify-center mb-6">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 0h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Mot de passe oublié ?</h2>
            <p class="text-gray-600">Pas de problème ! Entrez votre adresse e-mail et nous vous enverrons un lien pour réinitialiser votre mot de passe.</p>
        </div>

        <!-- Formulaire -->
        <div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
            <!-- Messages de succès ou d'erreur -->
            @if (session('status'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-green-800 font-medium">{{ session('status') }}</p>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <div class="flex items-center mb-2">
                        <svg class="w-5 h-5 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-red-800 font-medium">Erreur :</p>
                    </div>
                    <ul class="text-red-700 list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                @csrf
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Adresse e-mail
                    </label>
                    <input 
                        id="email" 
                        name="email" 
                        type="email" 
                        value="{{ old('email') }}"
                        autocomplete="email" 
                        required 
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-kardafrica-primary focus:border-transparent transition-all duration-300 bg-gray-50 focus:bg-white"
                        placeholder="votre.email@exemple.com"
                    >
                </div>

                <button 
                    type="submit" 
                    class="w-full flex justify-center items-center py-3 px-4 bg-gradient-to-r from-kardafrica-primary to-kardafrica-secondary text-white font-semibold rounded-xl hover-kardafrica shadow-lg transition-all duration-300 space-x-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                    <span>Envoyer le lien de réinitialisation</span>
                </button>
            </form>

            <!-- Liens de navigation -->
            <div class="mt-6 text-center space-y-3">
                <p class="text-sm text-gray-600">
                    Vous vous souvenez de votre mot de passe ?
                    <a href="#" onclick="openAuthModal()" class="text-kardafrica-primary hover:text-kardafrica-secondary font-medium transition-colors duration-300">
                        Se connecter
                    </a>
                </p>
                
                <div class="pt-3 border-t border-gray-200">
                    <a href="{{ route('home') }}" class="inline-flex items-center text-kardafrica-primary hover:text-kardafrica-secondary font-medium transition-colors duration-300">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Retour à l'accueil
                    </a>
                </div>
            </div>
        </div>

        <!-- Section d'aide -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h3 class="text-blue-800 font-medium mb-1">Besoin d'aide ?</h3>
                    <p class="text-blue-700 text-sm">
                        Si vous ne recevez pas l'e-mail de réinitialisation, vérifiez votre dossier spam ou 
                        <a href="{{ route('contact') }}" class="underline hover:no-underline">contactez notre support</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-kardafrica:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }
</style>
@endsection 