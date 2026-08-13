@extends('layouts.app')

@section('title', 'Mot de passe oublié — Espace pro | KardAfrica')

@section('content')
<div class="bg-[#FAFAF7] min-h-screen pb-20">
    <div class="max-w-md mx-auto px-4 sm:px-6 pt-10 md:pt-14">

        <div class="bg-white rounded-2xl p-6 md:p-8 border border-slate-100 shadow-card text-center">
            <div class="mx-auto w-14 h-14 rounded-2xl bg-gradient-to-br from-[#4ECDC4] to-[#44A08D] flex items-center justify-center text-white shadow-lg shadow-[#44A08D]/25 mb-4">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            </div>
            <h1 class="font-display text-2xl font-bold text-slate-900">Mot de passe oublié ?</h1>
            <p class="text-sm text-slate-500 mt-1.5">
                Saisissez le numéro de téléphone de votre compte pro : nous vous enverrons
                un code de vérification sur <span class="font-semibold text-slate-700">WhatsApp</span>.
            </p>

            <form method="POST" action="{{ route('pro.password.send') }}" class="mt-6 text-left">
                @csrf
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5" for="phone">Numéro de téléphone</label>
                <input id="phone" type="tel" name="phone" required autofocus autocomplete="tel"
                       placeholder=" 06 12 34 56"
                       value="{{ old('phone') }}"
                       class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#44A08D]/40 focus:border-[#44A08D] {{ $errors->has('phone') ? 'border-rose-300' : '' }}">
                @error('phone')<p class="text-rose-600 text-xs mt-2">{{ $message }}</p>@enderror

                <button type="submit"
                        class="mt-5 w-full inline-flex items-center justify-center gap-2 px-4 py-3.5 rounded-xl bg-gradient-to-br from-[#44A08D] to-[#4ECDC4] text-white font-bold text-sm shadow-lg shadow-[#44A08D]/30 active:scale-[0.99] transition">
                    Recevoir le code sur WhatsApp
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </form>

            <a href="{{ route('owner.login') }}" class="inline-block mt-5 text-xs font-semibold text-slate-500 hover:text-[#44A08D] transition">
                ← Retour à la connexion
            </a>
        </div>
    </div>
</div>
@endsection
