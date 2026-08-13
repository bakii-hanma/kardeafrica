@extends('layouts.app')

@section('title', 'Inscription pro — KardAfrica')

@section('content')
<div class="bg-[#FAFAF7] min-h-screen pb-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 md:pt-12">

        @include('pro._stepper', ['current' => 1])

        <div class="mt-6 md:mt-8 grid lg:grid-cols-[1fr_1.2fr] gap-4 md:gap-6 items-start">

            {{-- ============ Bandeau marque (compact, visible sur tous les écrans) ============ --}}
            <div class="relative overflow-hidden rounded-2xl md:rounded-3xl bg-gradient-to-br from-[#1F2937] via-[#0F172A] to-[#0F4F44] p-5 sm:p-6 lg:p-8 shadow-pop">
                {{-- Glows uniquement (grille supprimée) --}}
                <div class="absolute -top-24 -right-24 w-72 h-72 bg-[#44A08D]/30 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-[#4ECDC4]/20 rounded-full blur-3xl"></div>

                <div class="relative">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#44A08D]/20 border border-[#44A08D]/30 mb-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#4ECDC4] animate-pulse"></span>
                        <span class="text-[#4ECDC4] font-bold text-[10px] tracking-wider uppercase">Espace Pro</span>
                    </div>
                    <h2 class="font-display text-white font-bold text-xl sm:text-2xl leading-tight tracking-tight">
                        Votre marque, votre <span class="text-[#4ECDC4]">carte cadeau</span>.
                    </h2>

                    <ul class="mt-4 space-y-2.5 lg:space-y-3">
                        @foreach([
                            'Inscription en 3 minutes, vérifiée par WhatsApp',
                            'Accès immédiat dès le dépôt de votre dossier',
                            'Zéro logistique — tout est digital',
                            'Encaissement au comptoir par QR code sécurisé',
                        ] as $point)
                            <li class="flex items-start gap-2.5">
                                <span class="mt-0.5 w-4.5 h-4.5 min-w-[18px] min-h-[18px] rounded-full bg-[#44A08D]/25 border border-[#44A08D]/40 flex items-center justify-center shrink-0">
                                    <svg class="h-2.5 w-2.5 text-[#4ECDC4]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                <span class="text-[13px] text-slate-300 leading-snug">{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- ============ Formulaire ============ --}}
            <div class="bg-white rounded-3xl border border-slate-100 shadow-card p-6 sm:p-8 md:p-10"
                 x-data="{ showPwd: false, showPwd2: false }">
                <h1 class="font-display text-2xl md:text-[1.7rem] font-bold text-slate-900 tracking-tight">Créez votre compte pro</h1>
                <p class="text-sm text-slate-500 mt-1.5">Étape 1 sur 3 — un code de vérification sera envoyé sur votre WhatsApp.</p>

                @if(session('status'))
                    <div class="mt-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-4 py-3">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('pro.register') }}" class="mt-7 space-y-5">
                    @csrf

                    {{-- Entreprise --}}
                    <div>
                        <label for="business_name" class="block text-[13px] font-semibold text-slate-700 mb-1.5">Nom de l'entreprise</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </span>
                            <input type="text" id="business_name" name="business_name" value="{{ old('business_name') }}" required autofocus
                                   placeholder="Ex. Restaurant Le Phare"
                                   class="w-full rounded-xl border {{ $errors->has('business_name') ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-slate-50/50' }} pl-12 pr-4 py-3.5 text-sm text-slate-900 placeholder-slate-400 transition focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#44A08D]/30 focus:border-[#44A08D]">
                        </div>
                        @error('business_name')<p class="text-rose-600 text-xs mt-1.5">{{ $message }}</p>@enderror
                    </div>

                    {{-- Gérant --}}
                    <div>
                        <label for="contact_name" class="block text-[13px] font-semibold text-slate-700 mb-1.5">Nom du gérant</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </span>
                            <input type="text" id="contact_name" name="contact_name" value="{{ old('contact_name') }}" required
                                   placeholder="Prénom et nom"
                                   class="w-full rounded-xl border {{ $errors->has('contact_name') ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-slate-50/50' }} pl-12 pr-4 py-3.5 text-sm text-slate-900 placeholder-slate-400 transition focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#44A08D]/30 focus:border-[#44A08D]">
                        </div>
                        @error('contact_name')<p class="text-rose-600 text-xs mt-1.5">{{ $message }}</p>@enderror
                    </div>

                    {{-- WhatsApp --}}
                    <div>
                        <label for="phone" class="block text-[13px] font-semibold text-slate-700 mb-1.5">Numéro WhatsApp</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-[#25D366]">
                                <svg class="h-[18px] w-[18px]" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            </span>
                            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required
                                   placeholder="077 12 34 56"
                                   class="w-full rounded-xl border {{ $errors->has('phone') ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-slate-50/50' }} pl-12 pr-4 py-3.5 text-sm text-slate-900 placeholder-slate-400 transition focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#44A08D]/30 focus:border-[#44A08D]">
                        </div>
                        <p class="text-xs text-slate-400 mt-1.5">Le code de vérification sera envoyé sur ce numéro.</p>
                        @error('phone')<p class="text-rose-600 text-xs mt-1.5">{{ $message }}</p>@enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-[13px] font-semibold text-slate-700 mb-1.5">Email</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </span>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required
                                   placeholder="contact@entreprise.com"
                                   class="w-full rounded-xl border {{ $errors->has('email') ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-slate-50/50' }} pl-12 pr-4 py-3.5 text-sm text-slate-900 placeholder-slate-400 transition focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#44A08D]/30 focus:border-[#44A08D]">
                        </div>
                        @error('email')<p class="text-rose-600 text-xs mt-1.5">{{ $message }}</p>@enderror
                    </div>

                    {{-- Mots de passe --}}
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-[13px] font-semibold text-slate-700 mb-1.5">Mot de passe</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </span>
                                <input :type="showPwd ? 'text' : 'password'" id="password" name="password" required
                                       placeholder="8 caractères min."
                                       class="w-full rounded-xl border {{ $errors->has('password') ? 'border-rose-300 bg-rose-50/40' : 'border-slate-200 bg-slate-50/50' }} pl-12 pr-11 py-3.5 text-sm text-slate-900 placeholder-slate-400 transition focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#44A08D]/30 focus:border-[#44A08D]">
                                <button type="button" @click="showPwd = !showPwd" tabindex="-1"
                                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                                    <svg x-show="!showPwd" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="showPwd" x-cloak class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                            @error('password')<p class="text-rose-600 text-xs mt-1.5">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-[13px] font-semibold text-slate-700 mb-1.5">Confirmer</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                </span>
                                <input :type="showPwd2 ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required
                                       placeholder="Répétez le mot de passe"
                                       class="w-full rounded-xl border border-slate-200 bg-slate-50/50 pl-12 pr-11 py-3.5 text-sm text-slate-900 placeholder-slate-400 transition focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#44A08D]/30 focus:border-[#44A08D]">
                                <button type="button" @click="showPwd2 = !showPwd2" tabindex="-1"
                                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                                    <svg x-show="!showPwd2" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="showPwd2" x-cloak class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="submit"
                            class="group w-full mt-1 inline-flex items-center justify-center gap-2 px-5 py-4 rounded-xl bg-gradient-to-r from-[#44A08D] to-[#4ECDC4] hover:from-[#3d9180] hover:to-[#44A08D] text-white font-bold text-[15px] shadow-lg shadow-[#44A08D]/40 transition-all duration-200 active:scale-[0.99]">
                        Recevoir mon code WhatsApp
                        <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>

                    <div class="flex items-center justify-center gap-1.5 text-xs text-slate-400">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Vos données sont protégées et ne sont jamais partagées.
                    </div>
                </form>

                <p class="text-center text-sm text-slate-500 mt-6 pt-5 border-t border-slate-100">
                    Déjà partenaire ?
                    <a href="{{ route('owner.login') }}" class="text-[#44A08D] font-semibold hover:underline">Se connecter</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
