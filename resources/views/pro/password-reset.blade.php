@extends('layouts.app')

@section('title', 'Nouveau mot de passe — Espace pro | KardAfrica')

@section('content')
<div class="bg-[#FAFAF7] min-h-screen pb-20">
    <div class="max-w-md mx-auto px-4 sm:px-6 pt-10 md:pt-14">

        <div class="bg-white rounded-2xl p-6 md:p-8 border border-slate-100 shadow-card text-center">
            <div class="mx-auto w-14 h-14 rounded-2xl bg-gradient-to-br from-[#4ECDC4] to-[#44A08D] flex items-center justify-center text-white shadow-lg shadow-[#44A08D]/25 mb-4">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h1 class="font-display text-2xl font-bold text-slate-900">Code reçu ?</h1>
            <p class="text-sm text-slate-500 mt-1.5">
                Entrez le code à 6 chiffres envoyé sur WhatsApp
                @if(!empty($phoneMasked)) au <span class="font-semibold text-slate-700">{{ $phoneMasked }}</span>@endif,
                puis choisissez votre nouveau mot de passe.
            </p>

            @if(session('status'))
                <div class="mt-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-4 py-3">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('pro.password.reset') }}" class="mt-6 text-left">
                @csrf

                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Code de vérification</label>
                <input type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" required autofocus
                       placeholder="______"
                       class="w-full text-center tracking-[0.5em] font-display text-2xl font-bold rounded-xl border border-slate-200 px-4 py-4 focus:outline-none focus:ring-2 focus:ring-[#44A08D]/40 focus:border-[#44A08D] {{ $errors->has('code') ? 'border-rose-300' : '' }}">
                @error('code')<p class="text-rose-600 text-xs mt-2">{{ $message }}</p>@enderror

                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5 mt-5" for="password">Nouveau mot de passe</label>
                <input id="password" type="password" name="password" required autocomplete="new-password" minlength="8"
                       placeholder=" 8 caractères minimum"
                       class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#44A08D]/40 focus:border-[#44A08D] {{ $errors->has('password') ? 'border-rose-300' : '' }}">
                @error('password')<p class="text-rose-600 text-xs mt-2">{{ $message }}</p>@enderror

                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5 mt-4" for="password_confirmation">Confirmez le mot de passe</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" minlength="8"
                       placeholder=" Le même mot de passe"
                       class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#44A08D]/40 focus:border-[#44A08D]">

                <button type="submit"
                        class="mt-6 w-full inline-flex items-center justify-center gap-2 px-4 py-3.5 rounded-xl bg-gradient-to-br from-[#44A08D] to-[#4ECDC4] text-white font-bold text-sm shadow-lg shadow-[#44A08D]/30 active:scale-[0.99] transition">
                    Réinitialiser le mot de passe
                </button>
            </form>

            <a href="{{ route('pro.password.request') }}" class="inline-block mt-5 text-xs font-semibold text-slate-500 hover:text-[#44A08D] transition">
                Renvoyer un code
            </a>
        </div>
    </div>
</div>
@endsection
