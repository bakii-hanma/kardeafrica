@extends('layouts.app')

@section('title', 'Devenez partenaire — KardAfrica')

@section('content')
<div class="bg-[#FAFAF7] min-h-screen pb-20">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 md:pt-16">

        {{-- Hero pro --}}
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#1F2937] via-[#1F2937] to-[#0F172A] p-8 md:p-12 shadow-pop">
            <div class="absolute -top-20 -right-20 w-80 h-80 bg-[#44A08D]/30 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-[#4ECDC4]/20 rounded-full blur-3xl"></div>
            <div class="relative max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#44A08D]/20 border border-[#44A08D]/30 mb-4">
                    <span class="text-[#4ECDC4] font-bold text-[10px] tracking-wider uppercase">Espace Pro</span>
                </div>
                <h1 class="font-display text-white font-bold text-3xl md:text-4xl leading-tight tracking-tight">
                    Créez vos <span class="text-[#4ECDC4]">cartes cadeaux digitales</span> à votre marque.
                </h1>
                <p class="text-slate-300 text-sm md:text-base mt-4 max-w-xl">
                    Commerçants, restaurants, enseignes : lancez vos propres cartes cadeaux sur KardAfrica.
                    Vendues et livrées en quelques secondes, sans logistique. Inscription en 3 étapes, validation rapide.
                </p>
                <a href="{{ route('pro.register.show') }}" class="mt-7 inline-flex items-center gap-2 px-6 py-3.5 rounded-xl bg-[#44A08D] hover:bg-[#3d9180] text-white font-semibold shadow-lg shadow-[#44A08D]/40 transition-all duration-200 active:scale-95">
                    <span>Créer mon compte pro</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
                <p class="text-slate-400 text-xs mt-3">
                    Déjà partenaire ? <a href="{{ route('owner.login') }}" class="text-[#4ECDC4] font-semibold hover:underline">Connectez-vous</a>
                </p>
            </div>
        </div>

        {{-- 3 étapes --}}
        <div class="grid md:grid-cols-3 gap-5 mt-8">
            @foreach([
                ['01','Je m\'inscris','Nom de l\'entreprise, gérant, numéro WhatsApp et email. Vérification par code WhatsApp.'],
                ['02','Je dépose mon dossier','Adresse, pièce d\'identité du gérant et justificatif d\'entreprise. Accès immédiat à titre provisoire.'],
                ['03','Je crée mes cartes','Vous créez vos cartes cadeaux ; notre équipe valide votre compte et publie vos cartes.'],
            ] as [$n,$t,$d])
                <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-card">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-[#4ECDC4] to-[#44A08D] text-white font-display font-bold flex items-center justify-center shadow-lg shadow-[#44A08D]/20 mb-4">{{ $n }}</div>
                    <h3 class="font-display text-lg font-bold text-slate-900 mb-1.5">{{ $t }}</h3>
                    <p class="text-sm text-slate-600 leading-relaxed">{{ $d }}</p>
                </div>
            @endforeach
        </div>

        {{-- Avantages --}}
        <div class="mt-8 bg-white rounded-2xl p-6 md:p-8 border border-slate-100 shadow-card">
            <h2 class="font-display text-xl font-bold text-slate-900 mb-4">Pourquoi KardAfrica ?</h2>
            <div class="grid sm:grid-cols-2 gap-x-8 gap-y-3">
                @foreach([
                    'Aucune logistique : tout est digital, livraison instantanée.',
                    'Encaissement au comptoir via QR code sécurisé.',
                    'Fidélisez vos clients avec votre propre carte cadeau.',
                    'Un nouveau canal de revenu, sans frais de mise en place.',
                ] as $adv)
                    <div class="flex items-start gap-2.5">
                        <svg class="h-5 w-5 text-[#44A08D] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span class="text-sm text-slate-700">{{ $adv }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
