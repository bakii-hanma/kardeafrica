@extends('layouts.app')

@section('title', 'Vérification — KardAfrica')

@section('content')
<div class="bg-[#FAFAF7] min-h-screen pb-20">
    <div class="max-w-md mx-auto px-4 sm:px-6 pt-10 md:pt-14">

        @include('pro._stepper', ['current' => 2])

        <div class="bg-white rounded-2xl p-6 md:p-8 border border-slate-100 shadow-card mt-6 text-center">
            <div class="mx-auto w-14 h-14 rounded-2xl bg-gradient-to-br from-[#4ECDC4] to-[#44A08D] flex items-center justify-center text-white shadow-lg shadow-[#44A08D]/25 mb-4">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h1 class="font-display text-2xl font-bold text-slate-900">Vérifiez votre numéro</h1>
            <p class="text-sm text-slate-500 mt-1.5">
                Nous avons envoyé un code à 6 chiffres sur WhatsApp
                @if(!empty($phoneMasked)) au <span class="font-semibold text-slate-700">{{ $phoneMasked }}</span>@endif.
            </p>

            @if(session('status'))
                <div class="mt-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-4 py-3">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('pro.verification.verify') }}" class="mt-6">
                @csrf
                <input type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" required autofocus
                       placeholder="______"
                       class="w-full text-center tracking-[0.5em] font-display text-2xl font-bold rounded-xl border border-slate-200 px-4 py-4 focus:outline-none focus:ring-2 focus:ring-[#44A08D]/40 focus:border-[#44A08D]">
                @error('code')<p class="text-rose-600 text-xs mt-2">{{ $message }}</p>@enderror

                <button type="submit" class="w-full mt-4 inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl bg-[#44A08D] hover:bg-[#3d9180] text-white font-semibold shadow-lg shadow-[#44A08D]/40 transition-all active:scale-[0.99]">
                    Valider le code
                </button>
            </form>

            <form method="POST" action="{{ route('pro.verification.resend') }}" class="mt-4">
                @csrf
                <button type="submit" class="text-sm text-[#44A08D] font-semibold hover:underline">Renvoyer un code</button>
            </form>
        </div>
    </div>
</div>
@endsection
