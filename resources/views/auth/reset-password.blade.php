@extends('layouts.app')

@section('title', 'Réinitialiser le mot de passe - Kardafrica')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Header -->
        <div class="text-center">
            <div class="mx-auto w-20 h-20 bg-gradient-to-r from-kardafrica-primary to-kardafrica-secondary rounded-full flex items-center justify-center mb-6">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Nouveau mot de passe</h2>
            <p class="text-gray-600">Veuillez saisir votre nouveau mot de passe pour sécuriser votre compte.</p>
        </div>

        <!-- Formulaire -->
        <div class="bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
            <!-- Messages d'erreur -->
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

            <form method="POST" action="{{ route('password.update') }}" class="space-y-6">
                @csrf
                
                <!-- Token caché -->
                <input type="hidden" name="token" value="{{ $token }}">
                
                <!-- Adresse e-mail -->
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
                        value="{{ old('email', request()->email) }}"
                        autocomplete="email" 
                        required 
                        readonly
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-100 text-gray-700 cursor-not-allowed"
                    >
                </div>

                <!-- Nouveau mot de passe -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 0h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        Nouveau mot de passe
                    </label>
                    <div class="relative">
                        <input 
                            id="password" 
                            name="password" 
                            type="password" 
                            autocomplete="new-password" 
                            required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-kardafrica-primary focus:border-transparent transition-all duration-300 bg-gray-50 focus:bg-white pr-12"
                            placeholder="••••••••"
                        >
                        <button 
                            type="button" 
                            id="togglePassword"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none"
                        >
                            <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="mt-2 text-sm text-gray-600">
                        <p>Le mot de passe doit contenir au moins :</p>
                        <ul class="list-disc list-inside mt-1 space-y-1 text-xs">
                            <li>8 caractères minimum</li>
                            <li>Une lettre majuscule et minuscule</li>
                            <li>Un chiffre</li>
                            <li>Un caractère spécial (@, #, $, etc.)</li>
                        </ul>
                    </div>
                </div>

                <!-- Confirmation du mot de passe -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Confirmer le mot de passe
                    </label>
                    <input 
                        id="password_confirmation" 
                        name="password_confirmation" 
                        type="password" 
                        autocomplete="new-password" 
                        required 
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-kardafrica-primary focus:border-transparent transition-all duration-300 bg-gray-50 focus:bg-white"
                        placeholder="••••••••"
                    >
                </div>

                <!-- Indicateur de force du mot de passe -->
                <div id="passwordStrength" class="hidden">
                    <div class="flex items-center justify-between text-sm mb-2">
                        <span class="text-gray-600">Force du mot de passe :</span>
                        <span id="strengthText" class="font-medium"></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div id="strengthBar" class="h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>

                <button 
                    type="submit" 
                    class="w-full flex justify-center items-center py-3 px-4 bg-gradient-to-r from-kardafrica-primary to-kardafrica-secondary text-white font-semibold rounded-xl hover-kardafrica shadow-lg transition-all duration-300 space-x-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                    <span>Réinitialiser le mot de passe</span>
                </button>
            </form>

            <!-- Liens de navigation -->
            <div class="mt-6 text-center">
                <a href="{{ route('home') }}" class="inline-flex items-center text-kardafrica-primary hover:text-kardafrica-secondary font-medium transition-colors duration-300">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Retour à l'accueil
                </a>
            </div>
        </div>

        <!-- Section de sécurité -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.664-.833-2.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <div>
                    <h3 class="text-yellow-800 font-medium mb-1">Sécurité de votre compte</h3>
                    <p class="text-yellow-700 text-sm">
                        Après avoir réinitialisé votre mot de passe, toutes vos sessions actives seront déconnectées pour votre sécurité.
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const eyeIcon = document.getElementById('eyeIcon');
    const strengthIndicator = document.getElementById('passwordStrength');
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');

    // Toggle password visibility
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        if (type === 'text') {
            eyeIcon.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
            `;
        } else {
            eyeIcon.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            `;
        }
    });

    // Password strength indicator
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        
        if (password.length > 0) {
            strengthIndicator.classList.remove('hidden');
            
            let strength = 0;
            let strengthColor = '';
            let strengthMessage = '';

            // Check password criteria
            if (password.length >= 8) strength += 1;
            if (/[a-z]/.test(password)) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;

            // Set strength level
            switch (strength) {
                case 0:
                case 1:
                    strengthColor = '#ef4444';
                    strengthMessage = 'Très faible';
                    break;
                case 2:
                    strengthColor = '#f97316';
                    strengthMessage = 'Faible';
                    break;
                case 3:
                    strengthColor = '#eab308';
                    strengthMessage = 'Moyen';
                    break;
                case 4:
                    strengthColor = '#22c55e';
                    strengthMessage = 'Fort';
                    break;
                case 5:
                    strengthColor = '#16a34a';
                    strengthMessage = 'Très fort';
                    break;
            }

            const percentage = (strength / 5) * 100;
            strengthBar.style.width = percentage + '%';
            strengthBar.style.backgroundColor = strengthColor;
            strengthText.textContent = strengthMessage;
            strengthText.style.color = strengthColor;
        } else {
            strengthIndicator.classList.add('hidden');
        }
    });
});
</script>
@endsection 