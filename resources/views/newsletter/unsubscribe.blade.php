@extends('layouts.app')

@section('title', 'Désinscription newsletter - KardAfrica')

@section('content')
<div class="min-h-screen bg-[#FAFAF7] flex items-center justify-center px-4 py-20">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-card max-w-md w-full p-8 text-center">

        @if($status === 'success')
            <div class="w-16 h-16 mx-auto mb-5 rounded-2xl bg-emerald-50 flex items-center justify-center">
                <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h1 class="font-display text-2xl font-bold text-slate-900 mb-2">Désinscription confirmée</h1>
            <p class="text-sm text-slate-600 mb-1">{{ $message }}</p>
            @if(!empty($email))
                <p class="text-xs text-slate-400 font-mono">{{ $email }}</p>
            @endif
            <p class="text-xs text-slate-500 mt-4">
                Vous ne recevrez plus aucun email de notre part. Vous nous manquerez !
            </p>
        @elseif($status === 'already')
            <div class="w-16 h-16 mx-auto mb-5 rounded-2xl bg-slate-100 flex items-center justify-center">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h1 class="font-display text-2xl font-bold text-slate-900 mb-2">Déjà désinscrit</h1>
            <p class="text-sm text-slate-600">{{ $message }}</p>
            @if(!empty($email))
                <p class="text-xs text-slate-400 font-mono mt-2">{{ $email }}</p>
            @endif
        @else
            <div class="w-16 h-16 mx-auto mb-5 rounded-2xl bg-rose-50 flex items-center justify-center">
                <svg class="w-8 h-8 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <h1 class="font-display text-2xl font-bold text-slate-900 mb-2">Lien invalide</h1>
            <p class="text-sm text-slate-600">{{ $message }}</p>
        @endif

        <a href="{{ route('home') }}"
           class="mt-6 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#44A08D] hover:bg-[#3d9180] text-white text-sm font-semibold shadow-lg shadow-[#44A08D]/25 transition active:scale-95">
            Retour à l'accueil
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </a>
    </div>
</div>
@endsection
