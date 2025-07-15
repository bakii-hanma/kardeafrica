@extends('layouts.app')

@section('title', 'Support - Kardafrica')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-green-900 via-teal-900 to-blue-900 text-white py-20">
        <div class="absolute inset-0 bg-black opacity-40"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-5xl md:text-6xl font-bold mb-6 hero-text-animate">
                    Centre de <span class="bg-gradient-to-r from-yellow-400 to-orange-500 bg-clip-text text-transparent">Support</span>
                </h1>
                <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto hero-text-animate opacity-90">
                    Trouvez rapidement des réponses à vos questions et obtenez l'aide dont vous avez besoin
                </p>
                <div class="flex justify-center space-x-4 hero-text-animate">
                    <span class="inline-flex items-center px-4 py-2 bg-white/20 rounded-full text-sm font-medium">
                        🔍 Recherche Rapide
                    </span>
                    <span class="inline-flex items-center px-4 py-2 bg-white/20 rounded-full text-sm font-medium">
                        📚 Guides Détaillés
                    </span>
                    <span class="inline-flex items-center px-4 py-2 bg-white/20 rounded-full text-sm font-medium">
                        💬 Support Live
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- Search Section -->
    <section class="py-12 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Comment pouvons-nous vous aider ?</h2>
                <p class="text-gray-600">Recherchez dans notre base de connaissances</p>
            </div>
            
            <div class="relative max-w-2xl mx-auto">
                <input type="text" id="searchInput" placeholder="Tapez votre question ici..." class="w-full px-6 py-4 pr-12 border-2 border-gray-200 rounded-xl text-lg focus:outline-none focus:ring-2 focus:ring-kardafrica-primary focus:border-transparent">
                <button class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-kardafrica-primary">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Quick Links -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
                <button class="quick-search-btn p-4 bg-blue-50 rounded-xl hover:bg-blue-100 transition-all duration-300" data-search="commande">
                    <div class="text-2xl mb-2">📦</div>
                    <div class="text-sm font-medium text-gray-700">Mes Commandes</div>
                </button>
                <button class="quick-search-btn p-4 bg-green-50 rounded-xl hover:bg-green-100 transition-all duration-300" data-search="paiement">
                    <div class="text-2xl mb-2">💳</div>
                    <div class="text-sm font-medium text-gray-700">Paiements</div>
                </button>
                <button class="quick-search-btn p-4 bg-purple-50 rounded-xl hover:bg-purple-100 transition-all duration-300" data-search="carte">
                    <div class="text-2xl mb-2">🎮</div>
                    <div class="text-sm font-medium text-gray-700">Cartes Numériques</div>
                </button>
                <button class="quick-search-btn p-4 bg-orange-50 rounded-xl hover:bg-orange-100 transition-all duration-300" data-search="compte">
                    <div class="text-2xl mb-2">👤</div>
                    <div class="text-sm font-medium text-gray-700">Mon Compte</div>
                </button>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-20 section-animate">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4 section-title">
                    Questions Fréquemment Posées
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Trouvez rapidement des réponses aux questions les plus courantes
                </p>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- FAQ Category 1: Commandes -->
                <div class="bg-white rounded-2xl shadow-lg p-8">
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900">Commandes & Livraison</h3>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="faq-item border-b border-gray-200 pb-4">
                            <button class="faq-question w-full text-left flex justify-between items-center font-semibold text-gray-900 hover:text-kardafrica-primary transition-colors">
                                <span>Comment passer une commande ?</span>
                                <svg class="w-5 h-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="faq-answer hidden mt-3 text-gray-600">
                                <p>1. Parcourez notre catalogue de cartes<br>
                                2. Sélectionnez la carte désirée<br>
                                3. Choisissez le montant<br>
                                4. Ajoutez au panier<br>
                                5. Procédez au paiement sécurisé</p>
                            </div>
                        </div>
                        
                        <div class="faq-item border-b border-gray-200 pb-4">
                            <button class="faq-question w-full text-left flex justify-between items-center font-semibold text-gray-900 hover:text-kardafrica-primary transition-colors">
                                <span>Combien de temps pour recevoir ma carte ?</span>
                                <svg class="w-5 h-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="faq-answer hidden mt-3 text-gray-600">
                                <p>Les cartes numériques sont livrées instantanément par email après validation du paiement. Vous recevrez votre code dans les 5 minutes maximum.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item border-b border-gray-200 pb-4">
                            <button class="faq-question w-full text-left flex justify-between items-center font-semibold text-gray-900 hover:text-kardafrica-primary transition-colors">
                                <span>Puis-je annuler ma commande ?</span>
                                <svg class="w-5 h-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="faq-answer hidden mt-3 text-gray-600">
                                <p>Vous pouvez annuler votre commande avant la livraison de la carte. Une fois la carte livrée, les annulations ne sont plus possibles.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FAQ Category 2: Paiements -->
                <div class="bg-white rounded-2xl shadow-lg p-8">
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900">Paiements & Sécurité</h3>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="faq-item border-b border-gray-200 pb-4">
                            <button class="faq-question w-full text-left flex justify-between items-center font-semibold text-gray-900 hover:text-kardafrica-primary transition-colors">
                                <span>Quels moyens de paiement acceptez-vous ?</span>
                                <svg class="w-5 h-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="faq-answer hidden mt-3 text-gray-600">
                                <p>Nous acceptons : Visa, MasterCard, Orange Money, Wave, PayPal, et les virements bancaires locaux.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item border-b border-gray-200 pb-4">
                            <button class="faq-question w-full text-left flex justify-between items-center font-semibold text-gray-900 hover:text-kardafrica-primary transition-colors">
                                <span>Mes données de paiement sont-elles sécurisées ?</span>
                                <svg class="w-5 h-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="faq-answer hidden mt-3 text-gray-600">
                                <p>Oui, nous utilisons le cryptage SSL 256-bits et sommes certifiés PCI DSS. Vos données bancaires ne sont jamais stockées sur nos serveurs.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item border-b border-gray-200 pb-4">
                            <button class="faq-question w-full text-left flex justify-between items-center font-semibold text-gray-900 hover:text-kardafrica-primary transition-colors">
                                <span>Puis-je obtenir un remboursement ?</span>
                                <svg class="w-5 h-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="faq-answer hidden mt-3 text-gray-600">
                                <p>Les remboursements sont possibles dans les 24h suivant l'achat si la carte n'a pas été utilisée. Contactez notre support pour toute demande.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FAQ Category 3: Cartes -->
                <div class="bg-white rounded-2xl shadow-lg p-8">
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-violet-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900">Cartes Numériques</h3>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="faq-item border-b border-gray-200 pb-4">
                            <button class="faq-question w-full text-left flex justify-between items-center font-semibold text-gray-900 hover:text-kardafrica-primary transition-colors">
                                <span>Comment utiliser ma carte numérique ?</span>
                                <svg class="w-5 h-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="faq-answer hidden mt-3 text-gray-600">
                                <p>1. Connectez-vous à la plateforme concernée<br>
                                2. Allez dans la section "Recharger" ou "Ajouter des fonds"<br>
                                3. Saisissez le code reçu par email<br>
                                4. Validez - Le crédit sera ajouté instantanément</p>
                            </div>
                        </div>
                        
                        <div class="faq-item border-b border-gray-200 pb-4">
                            <button class="faq-question w-full text-left flex justify-between items-center font-semibold text-gray-900 hover:text-kardafrica-primary transition-colors">
                                <span>Ma carte a une date d'expiration ?</span>
                                <svg class="w-5 h-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="faq-answer hidden mt-3 text-gray-600">
                                <p>La plupart de nos cartes n'expirent pas. Cependant, certaines cartes promotionnelles peuvent avoir une durée de validité limitée, précisée lors de l'achat.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item border-b border-gray-200 pb-4">
                            <button class="faq-question w-full text-left flex justify-between items-center font-semibold text-gray-900 hover:text-kardafrica-primary transition-colors">
                                <span>Que faire si ma carte ne fonctionne pas ?</span>
                                <svg class="w-5 h-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="faq-answer hidden mt-3 text-gray-600">
                                <p>Vérifiez d'abord que vous êtes dans la bonne région et que la plateforme accepte ce type de carte. Si le problème persiste, contactez immédiatement notre support.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FAQ Category 4: Compte -->
                <div class="bg-white rounded-2xl shadow-lg p-8">
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900">Compte & Profil</h3>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="faq-item border-b border-gray-200 pb-4">
                            <button class="faq-question w-full text-left flex justify-between items-center font-semibold text-gray-900 hover:text-kardafrica-primary transition-colors">
                                <span>Comment créer un compte ?</span>
                                <svg class="w-5 h-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="faq-answer hidden mt-3 text-gray-600">
                                <p>Cliquez sur "Connexion" puis "Inscription". Remplissez le formulaire avec votre email et un mot de passe sécurisé. Validez votre email pour activer votre compte.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item border-b border-gray-200 pb-4">
                            <button class="faq-question w-full text-left flex justify-between items-center font-semibold text-gray-900 hover:text-kardafrica-primary transition-colors">
                                <span>J'ai oublié mon mot de passe</span>
                                <svg class="w-5 h-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="faq-answer hidden mt-3 text-gray-600">
                                <p>Cliquez sur "Mot de passe oublié" sur la page de connexion. Saisissez votre email pour recevoir un lien de réinitialisation.</p>
                            </div>
                        </div>
                        
                        <div class="faq-item border-b border-gray-200 pb-4">
                            <button class="faq-question w-full text-left flex justify-between items-center font-semibold text-gray-900 hover:text-kardafrica-primary transition-colors">
                                <span>Comment modifier mes informations ?</span>
                                <svg class="w-5 h-5 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div class="faq-answer hidden mt-3 text-gray-600">
                                <p>Connectez-vous à votre compte et accédez à la section "Mon Profil" pour modifier vos informations personnelles, votre mot de passe ou vos préférences.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Support Section -->
    <section class="py-20 bg-gradient-to-r from-kardafrica-primary to-kardafrica-secondary">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-white mb-6">
                Vous ne trouvez pas votre réponse ?
            </h2>
            <p class="text-xl text-white/90 mb-8 max-w-2xl mx-auto">
                Notre équipe de support est là pour vous aider personnellement
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('contact') }}" class="bg-white text-kardafrica-primary px-8 py-4 rounded-xl font-bold text-lg hover:shadow-xl hover:scale-105 transition-all duration-300">
                    📞 Contacter le Support
                </a>
                <button id="openSupportChat" class="border-2 border-white text-white px-8 py-4 rounded-xl font-bold text-lg hover:bg-white hover:text-kardafrica-primary transition-all duration-300">
                    💬 Chat en Direct
                </button>
            </div>
            
            <!-- Support Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-16">
                <div class="text-center">
                    <div class="text-3xl font-bold text-white mb-2">< 5min</div>
                    <div class="text-white/80">Temps de réponse moyen</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-white mb-2">24/7</div>
                    <div class="text-white/80">Support disponible</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-white mb-2">98%</div>
                    <div class="text-white/80">Satisfaction client</div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // FAQ Toggle Functionality
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        const answer = item.querySelector('.faq-answer');
        const icon = question.querySelector('svg');
        
        question.addEventListener('click', function() {
            const isOpen = !answer.classList.contains('hidden');
            
            if (isOpen) {
                answer.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
            } else {
                answer.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';
            }
        });
    });
    
    // Search Functionality
    const searchInput = document.getElementById('searchInput');
    const quickSearchBtns = document.querySelectorAll('.quick-search-btn');
    
    // Quick search buttons
    quickSearchBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const searchTerm = this.getAttribute('data-search');
            searchInput.value = searchTerm;
            performSearch(searchTerm);
        });
    });
    
    // Search input
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        if (searchTerm.length > 2) {
            performSearch(searchTerm);
        } else {
            resetSearch();
        }
    });
    
    function performSearch(term) {
        const faqItems = document.querySelectorAll('.faq-item');
        let hasResults = false;
        
        faqItems.forEach(item => {
            const question = item.querySelector('.faq-question span').textContent.toLowerCase();
            const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
            
            if (question.includes(term) || answer.includes(term)) {
                item.style.display = 'block';
                // Highlight the matching question
                item.querySelector('.faq-question').style.backgroundColor = '#fef3c7';
                hasResults = true;
            } else {
                item.style.display = 'none';
            }
        });
        
        // Show "no results" message if needed
        if (!hasResults) {
            showNoResults();
        } else {
            hideNoResults();
        }
    }
    
    function resetSearch() {
        const faqItems = document.querySelectorAll('.faq-item');
        faqItems.forEach(item => {
            item.style.display = 'block';
            item.querySelector('.faq-question').style.backgroundColor = '';
        });
        hideNoResults();
    }
    
    function showNoResults() {
        let noResultsMsg = document.getElementById('noResultsMessage');
        if (!noResultsMsg) {
            noResultsMsg = document.createElement('div');
            noResultsMsg.id = 'noResultsMessage';
            noResultsMsg.className = 'text-center py-8 text-gray-500';
            noResultsMsg.innerHTML = `
                <div class="text-4xl mb-4">🔍</div>
                <h3 class="text-xl font-semibold mb-2">Aucun résultat trouvé</h3>
                <p>Essayez avec d'autres mots-clés ou contactez notre support.</p>
            `;
            document.querySelector('.max-w-7xl').appendChild(noResultsMsg);
        }
        noResultsMsg.style.display = 'block';
    }
    
    function hideNoResults() {
        const noResultsMsg = document.getElementById('noResultsMessage');
        if (noResultsMsg) {
            noResultsMsg.style.display = 'none';
        }
    }
    
    // Support chat button
    const supportChatBtn = document.getElementById('openSupportChat');
    if (supportChatBtn) {
        supportChatBtn.addEventListener('click', function() {
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