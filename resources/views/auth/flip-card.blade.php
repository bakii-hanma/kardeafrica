<div x-data="{ 
    isLogin: true,
    isLoading: false,
    step: 1,
    errorMsg: '',
    errors: {},
    loginData: {
        email: '{{ old('email') }}',
        password: '',
        remember: false
    },
    registerData: {
        first_name: '{{ old('first_name') }}',
        last_name: '{{ old('last_name') }}',
        email: '{{ old('email') }}',
        phone: '{{ old('phone') }}',
        password: '',
        password_confirmation: ''
    },
    init() {
        // Initialize based on server-side errors if any (fallback)
        @if($errors->has('first_name') || $errors->has('last_name') || $errors->has('phone') || $errors->has('password_confirmation'))
            this.isLogin = false;
            this.step = {{ $errors->has('first_name') || $errors->has('last_name') || $errors->has('email') ? 1 : ($errors->has('phone') ? 2 : ($errors->has('password') ? 3 : 1)) }};
        @endif
    },
    toggle() { 
        this.isLogin = !this.isLogin;
        this.errors = {};
        this.errorMsg = '';
    },
    validateStep() {
        this.errorMsg = '';
        if (this.step === 1) {
            if (!this.registerData.first_name || !this.registerData.last_name || !this.registerData.email) {
                this.errorMsg = 'Veuillez remplir tous les champs';
                return false;
            }
            if (!this.registerData.email.includes('@')) {
                this.errorMsg = 'Email invalide';
                return false;
            }
        }
        if (this.step === 2) {
            if (!this.registerData.phone) {
                this.errorMsg = 'Veuillez entrer votre numéro de téléphone';
                return false;
            }
        }
        return true;
    },
    next() {
        if (this.validateStep()) {
            this.step++;
        }
    },
    async handleLogin() {
        this.isLoading = true;
        this.errors = {};
        
        try {
            const response = await fetch('{{ route('login') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                },
                body: JSON.stringify(this.loginData)
            });
            
            const data = await response.json();
            
            if (response.ok) {
                window.location.href = data.redirect || '/dashboard'; 
            } else {
                if (data.errors) {
                    this.errors = data.errors;
                } else {
                    this.errors = { email: [data.message || 'Identifiants incorrects'] };
                }
            }
        } catch (error) {
            console.error('Error:', error);
            this.errors = { email: ['Une erreur technique est survenue.'] };
        } finally {
            this.isLoading = false;
        }
    },
    async handleRegister() {
        this.isLoading = true;
        this.errors = {};
        
        try {
            const response = await fetch('{{ route('register') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').getAttribute('content')
                },
                body: JSON.stringify(this.registerData)
            });
            
            const data = await response.json();
            
            if (response.ok) {
                window.location.href = data.redirect || '/dashboard';
            } else {
                if (data.errors) {
                    this.errors = data.errors;
                    // Reset step based on error
                    if (this.errors.first_name || this.errors.last_name || this.errors.email) this.step = 1;
                    else if (this.errors.phone) this.step = 2;
                    else if (this.errors.password) this.step = 3;
                }
            }
        } catch (error) {
            console.error('Error:', error);
        } finally {
            this.isLoading = false;
        }
    }
}" 
@set-auth-view.window="console.log('Received event:', $event.detail); isLogin = ($event.detail.view === 'login'); console.log('isLogin set to:', isLogin);"
class="perspective w-full h-[650px] animate-float auth-modal-content">
    <style>
        /* Floating Animation Styles */
        @keyframes float-card {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        .animate-float-delay-1 { animation: float-card 4s ease-in-out infinite; animation-delay: 0s; }
        .animate-float-delay-2 { animation: float-card 4s ease-in-out infinite; animation-delay: 1s; }
        .animate-float-delay-3 { animation: float-card 4s ease-in-out infinite; animation-delay: 2s; }

        /* Fix for Chrome/Edge autofill */
        /* Login Form (Light Background) */
        #login-form input:-webkit-autofill,
        #login-form input:-webkit-autofill:hover, 
        #login-form input:-webkit-autofill:focus, 
        #login-form input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px white inset !important;
            -webkit-text-fill-color: #1F2937 !important;
            caret-color: #1F2937;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* Register Form (Dark Background) */
        #register-form input:-webkit-autofill,
        #register-form input:-webkit-autofill:hover, 
        #register-form input:-webkit-autofill:focus, 
        #register-form input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px #1F2937 inset !important;
            -webkit-text-fill-color: white !important;
            caret-color: white;
            transition: background-color 5000s ease-in-out 0s;
        }
        
        /* Remove default outlines */
        input:focus {
            outline: none !important;
            box-shadow: none !important;
        }
    </style>

    <!-- Floating Cards Decoration -->
    <div class="absolute -top-32 left-0 right-0 flex justify-center pointer-events-none z-20">
         <div class="flex items-end justify-center space-x-6 mb-4 h-32">
            <!-- Card 1: Gift -->
            <div class="animate-float-delay-1 transform -rotate-12 z-10">
                <div class="w-12 h-16 bg-rose-500/90 rounded-xl shadow-lg border border-white/30 flex items-center justify-center backdrop-blur-md">
                    <span class="text-2xl">🎁</span>
                </div>
            </div>
            <!-- Card 2: Logo -->
            <div class="animate-float-delay-2 transform z-20 mb-4">
                <div class="w-16 h-24 bg-[#44A08D] rounded-xl shadow-lg border border-white/30 flex items-center justify-center">
                    <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" class="w-10 h-10 object-contain" alt="Logo">
                </div>
            </div>
            <!-- Card 3: Game -->
            <div class="animate-float-delay-3 transform rotate-12 z-10">
                 <div class="w-12 h-16 bg-blue-500/90 rounded-xl shadow-lg border border-white/30 flex items-center justify-center backdrop-blur-md">
                    <span class="text-2xl">🎮</span>
                </div>
            </div>
         </div>
    </div>

    <!-- Flip Container -->
    <div class="relative w-full h-full transition-transform duration-700 preserve-3d" 
         :class="isLogin ? 'rotate-y-0' : 'rotate-y-180'">
        
        <!-- Login Side (Front) -->
        <div class="absolute inset-0 backface-hidden bg-white rounded-3xl shadow-2xl p-8 flex flex-col overflow-hidden transition-all duration-300"
             :class="isLogin ? 'z-20 opacity-100 pointer-events-auto' : 'z-10 opacity-0 pointer-events-none'"
             style="backface-visibility: hidden;">
            <!-- Header -->
            <div class="text-center mb-8 pt-4">
                <div class="w-20 h-20 bg-gray-900 rounded-full mx-auto mb-4 flex items-center justify-center shadow-lg animate-float">
                    <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" class="w-12 h-12 object-contain" alt="Logo">
                </div>
                <h2 class="text-3xl font-black text-gray-900 tracking-tight">Bon retour !</h2>
                <p class="text-gray-500 mt-2 font-medium">Connectez-vous à votre compte</p>
            </div>

            <!-- Error Display (Alpine) -->
            <div x-show="Object.keys(errors).length > 0 && isLogin" x-transition class="mb-4 bg-red-50 border border-red-200 text-red-600 px-4 py-2 rounded-xl text-sm relative" role="alert" style="display: none;">
                <ul class="list-disc list-inside">
                    <template x-for="field in Object.keys(errors)" :key="field">
                        <template x-for="error in errors[field]" :key="error">
                            <li x-text="error"></li>
                        </template>
                    </template>
                </ul>
            </div>

            <!-- Error Display (Server Fallback) -->
            @if ($errors->any())
                <div x-show="Object.keys(errors).length === 0" class="mb-4 bg-red-50 border border-red-200 text-red-600 px-4 py-2 rounded-xl text-sm relative" role="alert">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form @submit.prevent="handleLogin" class="space-y-5 flex-1" id="login-form" data-no-loader="true">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 ml-1 uppercase tracking-wider text-[10px] font-bold">Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <input type="email" x-model="loginData.email" required class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl leading-5 bg-transparent placeholder-gray-400 focus:outline-none focus:bg-transparent focus:ring-2 focus:ring-[#4ECDC4] focus:border-[#4ECDC4] transition duration-150 ease-in-out sm:text-sm" placeholder="exemple@email.com">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1 ml-1 uppercase tracking-wider text-[10px] font-bold">Mot de passe</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input type="password" x-model="loginData.password" required class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl leading-5 bg-transparent placeholder-gray-400 focus:outline-none focus:bg-transparent focus:ring-2 focus:ring-[#4ECDC4] focus:border-[#4ECDC4] transition duration-150 ease-in-out sm:text-sm" placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember_me" x-model="loginData.remember" type="checkbox" class="h-4 w-4 text-[#4ECDC4] focus:ring-[#4ECDC4] border-gray-300 rounded">
                        <label for="remember_me" class="ml-2 block text-sm text-gray-900">Se souvenir</label>
                    </div>
                </div>

                <button type="submit" :disabled="isLoading" class="w-full flex justify-center py-3.5 px-4 border border-transparent rounded-xl shadow-lg text-sm font-bold text-white bg-[#1F2937] hover:bg-[#374151] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#4ECDC4] transition-all duration-300 transform hover:scale-[1.02] disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!isLoading">Se connecter</span>
                    <span x-show="isLoading" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Connexion...
                    </span>
                </button>
            </form>

            <div class="mt-auto text-center pt-6 border-t border-gray-100">
                <p class="text-gray-600 font-medium text-sm">Pas encore de compte ?</p>
                <button type="button" @click.stop="toggle()" class="text-[#44A08D] font-bold hover:underline mt-1 text-base transition-colors relative z-50">
                    Créer un compte
                </button>
            </div>
        </div>

        <!-- Register Side (Back) -->
        <div class="absolute inset-0 backface-hidden rotate-y-180 bg-[#1F2937] rounded-3xl shadow-2xl p-8 flex flex-col text-white transition-all duration-300"
             :class="!isLogin ? 'z-20 opacity-100 pointer-events-auto' : 'z-10 opacity-0 pointer-events-none'"
             style="backface-visibility: hidden;">
             <!-- Header -->
            <div class="text-center mb-6 pt-2 relative">
                 <button type="button" x-show="step > 1" @click="step--" class="absolute left-0 top-1 p-1 z-10 text-white hover:text-gray-300 transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                 </button>
                 <h2 class="text-2xl font-black text-white">Inscription</h2>
                 
                 <!-- Step Indicator -->
                 <div class="flex flex-row items-center justify-center space-x-2 mt-4 mb-2">
                    <template x-for="i in 3">
                        <div class="h-2 rounded-full transition-all duration-300"
                             :class="step >= i ? (step === i ? 'w-6 bg-[#44A08D]' : 'w-2 bg-[#44A08D]') : 'w-2 bg-gray-600'">
                        </div>
                    </template>
                 </div>
                 <p class="text-gray-400 text-xs text-center mt-1" x-text="'Étape ' + step + ' sur 3'"></p>
                 
                 <!-- JS Validation Error -->
                 <div x-show="errorMsg" x-transition class="mt-2 text-center">
                    <span class="text-red-400 text-xs font-bold bg-red-900/30 px-3 py-1 rounded-full border border-red-500/50" x-text="errorMsg"></span>
                 </div>
            </div>
            
            <form id="register-form" @submit.prevent="handleRegister" class="space-y-4 flex-1 overflow-y-auto custom-scrollbar px-1" novalidate data-no-loader="true">
                
                <!-- Step 1: Identity -->
                <div x-show="step === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 mb-1 ml-1 uppercase tracking-wider">Prénom</label>
                            <div class="flex items-center bg-gray-800/50 border border-gray-700 rounded-xl px-3 py-3 transition-colors">
                                <svg class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <input type="text" x-model="registerData.first_name" class="block w-full ml-2 bg-transparent border-none text-white placeholder-gray-500 focus:ring-0 sm:text-sm" placeholder="Jean">
                            </div>
                            <template x-if="errors.first_name">
                                <p class="mt-1 text-xs text-red-400" x-text="errors.first_name[0]"></p>
                            </template>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 mb-1 ml-1 uppercase tracking-wider">Nom</label>
                            <div class="flex items-center bg-gray-800/50 border border-gray-700 rounded-xl px-3 py-3 transition-colors">
                                <input type="text" x-model="registerData.last_name" class="block w-full bg-transparent border-none text-white placeholder-gray-500 focus:ring-0 sm:text-sm" placeholder="Dupont">
                            </div>
                            <template x-if="errors.last_name">
                                <p class="mt-1 text-xs text-red-400" x-text="errors.last_name[0]"></p>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 mb-1 ml-1 uppercase tracking-wider">Email</label>
                        <div class="flex items-center bg-gray-800/50 border border-gray-700 rounded-xl px-3 py-3 transition-colors">
                            <svg class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                            </svg>
                            <input type="email" x-model="registerData.email" class="block w-full ml-2 bg-transparent border-none text-white placeholder-gray-500 focus:ring-0 sm:text-sm" placeholder="exemple@email.com">
                        </div>
                        <template x-if="errors.email">
                            <p class="mt-1 text-xs text-red-400" x-text="errors.email[0]"></p>
                        </template>
                    </div>
                </div>

                <!-- Step 2: Phone -->
                <div x-show="step === 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" class="space-y-4" style="display: none;">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 mb-1 ml-1 uppercase tracking-wider">Téléphone</label>
                        <div class="flex items-center bg-gray-800/50 border border-gray-700 rounded-xl px-3 py-3 transition-colors">
                            <div class="flex items-center border-r border-gray-600 pr-2 mr-2">
                                <span class="text-white font-bold text-sm">+241</span>
                            </div>
                            <svg class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <input type="tel" x-model="registerData.phone" class="block w-full ml-2 bg-transparent border-none text-white placeholder-gray-500 focus:ring-0 sm:text-sm" placeholder="00 00 00 00">
                        </div>
                        <template x-if="errors.phone">
                            <p class="mt-1 text-xs text-red-400" x-text="errors.phone[0]"></p>
                        </template>
                        <p class="text-[10px] text-gray-500 ml-1 mt-1">Un code de vérification sera envoyé à ce numéro.</p>
                    </div>
                </div>

                <!-- Step 3: Security -->
                <div x-show="step === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" class="space-y-4" style="display: none;">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 mb-1 ml-1 uppercase tracking-wider">Mot de passe</label>
                        <div class="flex items-center bg-gray-800/50 border border-gray-700 rounded-xl px-3 py-3 transition-colors">
                            <svg class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <input type="password" x-model="registerData.password" class="block w-full ml-2 bg-transparent border-none text-white placeholder-gray-500 focus:ring-0 sm:text-sm" placeholder="••••••••">
                        </div>
                        <template x-if="errors.password">
                            <p class="mt-1 text-xs text-red-400" x-text="errors.password[0]"></p>
                        </template>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 mb-1 ml-1 uppercase tracking-wider">Confirmer</label>
                        <div class="flex items-center bg-gray-800/50 border border-gray-700 rounded-xl px-3 py-3 transition-colors">
                            <svg class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <input type="password" x-model="registerData.password_confirmation" class="block w-full ml-2 bg-transparent border-none text-white placeholder-gray-500 focus:ring-0 sm:text-sm" placeholder="••••••••">
                        </div>
                    </div>

                    <div class="text-xs text-gray-400 bg-gray-800/30 p-3 rounded-lg border border-gray-700">
                        <p class="font-medium text-gray-300 mb-1">Sécurité du mot de passe :</p>
                        <ul class="list-disc list-inside space-y-0.5 text-gray-500">
                            <li>Au moins 8 caractères</li>
                            <li>Au moins un chiffre</li>
                        </ul>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="pt-4 flex gap-3">
                    <button type="button" x-show="step < 3" @click="next()" class="w-full py-3 bg-[#44A08D] text-white rounded-xl font-bold hover:bg-[#3d9180] shadow-md transition-colors flex items-center justify-center gap-2">
                        Suivant
                    </button>
                    <button type="submit" x-show="step === 3" :disabled="isLoading" class="w-full py-3 bg-white text-gray-900 rounded-xl font-bold hover:bg-gray-100 shadow-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center">
                        <span x-show="!isLoading">S'inscrire</span>
                        <span x-show="isLoading" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Inscription...
                        </span>
                    </button>
                </div>
            </form>

            <div class="mt-auto text-center pt-4 border-t border-gray-700">
                <p class="text-gray-400 font-medium text-sm">Déjà inscrit ?</p>
                <button type="button" @click.stop="toggle()" class="text-[#44A08D] font-bold hover:underline mt-1 text-base transition-colors relative z-50">
                    Se connecter
                </button>
            </div>
        </div>
    </div>
</div>
