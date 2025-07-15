@extends('layouts.app')

@section('title', 'Contact - Kardafrica')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-blue-900 via-indigo-900 to-purple-900 text-white py-20">
        <div class="absolute inset-0 bg-black opacity-40"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-5xl md:text-6xl font-bold mb-6 hero-text-animate">
                    Contactez-<span class="bg-gradient-to-r from-yellow-400 to-orange-500 bg-clip-text text-transparent">nous</span>
                </h1>
                <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto hero-text-animate opacity-90">
                    Notre équipe est à votre écoute pour répondre à toutes vos questions
                </p>
                <div class="flex justify-center space-x-4 hero-text-animate">
                    <span class="inline-flex items-center px-4 py-2 bg-white/20 rounded-full text-sm font-medium">
                        📞 Support 24/7
                    </span>
                    <span class="inline-flex items-center px-4 py-2 bg-white/20 rounded-full text-sm font-medium">
                        ⚡ Réponse Rapide
                    </span>
                    <span class="inline-flex items-center px-4 py-2 bg-white/20 rounded-full text-sm font-medium">
                        🌍 Service Global
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form & Info Section -->
    <section class="py-20 section-animate">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <!-- Contact Form -->
                <div class="bg-white rounded-2xl shadow-xl p-8 card-hover-effect">
                    <div class="mb-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-4 section-title">
                            Envoyez-nous un message
                        </h2>
                        <p class="text-gray-600">
                            Remplissez le formulaire ci-dessous et nous vous répondrons dans les plus brefs délais.
                        </p>
                    </div>

                    <form action="#" method="POST" class="space-y-6">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label for="first_name" class="form-label">
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Prénom
                                </label>
                                <input type="text" id="first_name" name="first_name" class="form-input" placeholder="Votre prénom" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="last_name" class="form-label">
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Nom
                                </label>
                                <input type="text" id="last_name" name="last_name" class="form-input" placeholder="Votre nom" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                Email
                            </label>
                            <input type="email" id="email" name="email" class="form-input" placeholder="votre.email@exemple.com" required>
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                Téléphone
                            </label>
                            <input type="tel" id="phone" name="phone" class="form-input" placeholder="+221 XX XXX XX XX">
                        </div>

                        <div class="form-group">
                            <label for="subject" class="form-label">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                                Sujet
                            </label>
                            <select id="subject" name="subject" class="form-input" required>
                                <option value="">Sélectionnez un sujet</option>
                                <option value="question_generale">Question générale</option>
                                <option value="support_technique">Support technique</option>
                                <option value="probleme_commande">Problème de commande</option>
                                <option value="partenariat">Partenariat</option>
                                <option value="remboursement">Demande de remboursement</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="message" class="form-label">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                                Message
                            </label>
                            <textarea id="message" name="message" rows="6" class="form-input" placeholder="Décrivez votre demande en détail..." required></textarea>
                        </div>

                        <button type="submit" class="w-full form-button flex items-center justify-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            <span>Envoyer le message</span>
                        </button>
                    </form>
                </div>

                <!-- Contact Information -->
                <div class="space-y-8">
                    <!-- Contact Cards -->
                    <div class="bg-white rounded-2xl shadow-xl p-8 card-hover-effect">
                        <h3 class="text-2xl font-bold text-gray-900 mb-6 section-title">
                            Informations de contact
                        </h3>
                        
                        <div class="space-y-6">
                            <!-- Email -->
                            <div class="flex items-start space-x-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 mb-1">Email</h4>
                                    <p class="text-gray-600 mb-2">Notre équipe vous répond sous 24h</p>
                                    <a href="mailto:hello@kardafrica.com" class="text-kardafrica-primary font-medium hover:underline">
                                        hello@kardafrica.com
                                    </a>
                                </div>
                            </div>

                            <!-- Phone -->
                            <div class="flex items-start space-x-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 mb-1">Téléphone</h4>
                                    <p class="text-gray-600 mb-2">Support téléphonique 24/7</p>
                                    <a href="tel:+221XXXXXXXXX" class="text-kardafrica-primary font-medium hover:underline">
                                        +221 XX XXX XX XX
                                    </a>
                                </div>
                            </div>

                            <!-- Address -->
                            <div class="flex items-start space-x-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-violet-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 mb-1">Adresse</h4>
                                    <p class="text-gray-600 mb-2">Siège social</p>
                                    <p class="text-kardafrica-primary font-medium">
                                        Dakar, Sénégal<br>
                                        Plateau, Avenue Georges Pompidou
                                    </p>
                                </div>
                            </div>

                            <!-- Hours -->
                            <div class="flex items-start space-x-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 mb-1">Horaires</h4>
                                    <p class="text-gray-600 mb-2">Support client</p>
                                    <p class="text-kardafrica-primary font-medium">
                                        24h/7j - Service continu<br>
                                        <span class="text-sm text-gray-600">Support prioritaire : 8h-20h GMT</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Quick Links -->
                    <div class="bg-gradient-to-br from-kardafrica-primary to-kardafrica-secondary rounded-2xl p-8 text-white">
                        <h3 class="text-2xl font-bold mb-6">Questions Fréquentes</h3>
                        <div class="space-y-4">
                            <div class="bg-white/10 rounded-lg p-4 hover:bg-white/20 transition-all duration-300 cursor-pointer">
                                <h4 class="font-semibold mb-2">💳 Comment acheter une carte ?</h4>
                                <p class="text-sm opacity-90">Guide complet d'achat de cartes numériques</p>
                            </div>
                            <div class="bg-white/10 rounded-lg p-4 hover:bg-white/20 transition-all duration-300 cursor-pointer">
                                <h4 class="font-semibold mb-2">🔒 Sécurité des paiements</h4>
                                <p class="text-sm opacity-90">Information sur nos mesures de sécurité</p>
                            </div>
                            <div class="bg-white/10 rounded-lg p-4 hover:bg-white/20 transition-all duration-300 cursor-pointer">
                                <h4 class="font-semibold mb-2">📱 Support technique</h4>
                                <p class="text-sm opacity-90">Aide pour résoudre les problèmes techniques</p>
                            </div>
                        </div>
                        <a href="{{ route('support') }}" class="inline-block mt-6 bg-white text-kardafrica-primary px-6 py-3 rounded-lg font-semibold hover:shadow-lg transition-all duration-300">
                            Voir toutes les FAQ →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-white section-animate">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-gray-900 mb-6">
                Besoin d'aide immédiate ?
            </h2>
            <p class="text-xl text-gray-600 mb-8 max-w-2xl mx-auto">
                Notre chat en direct est disponible pour vous aider instantanément
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button id="openLiveChat" class="bg-gradient-to-r from-kardafrica-primary to-kardafrica-secondary text-white px-8 py-4 rounded-xl font-bold text-lg hover:shadow-xl hover:scale-105 transition-all duration-300">
                    💬 Chat en Direct
                </button>
                <a href="{{ route('support') }}" class="border-2 border-kardafrica-primary text-kardafrica-primary px-8 py-4 rounded-xl font-bold text-lg hover:bg-kardafrica-primary hover:text-white transition-all duration-300">
                    📚 Centre d'aide
                </a>
            </div>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Contact form submission
    const contactForm = document.querySelector('form');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Simulate form submission
            const submitBtn = contactForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = `
                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Envoi en cours...</span>
            `;
            submitBtn.disabled = true;
            
            setTimeout(() => {
                alert('Message envoyé avec succès ! Nous vous répondrons sous peu.');
                contactForm.reset();
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 2000);
        });
    }
    
    // Live chat button
    const liveChatBtn = document.getElementById('openLiveChat');
    if (liveChatBtn) {
        liveChatBtn.addEventListener('click', function() {
            // Open chatbot
            const chatbotToggle = document.getElementById('chatbotToggle');
            if (chatbotToggle) {
                chatbotToggle.click();
            }
        });
    }
});
</script>
@endsection 