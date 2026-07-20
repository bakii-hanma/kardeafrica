@extends('layouts.app')

@section('title', 'Télécharger l\'app Kardafrica - Android')

@section('content')
<div class="bg-[#FAFAF7] min-h-screen py-12 px-4">
    <div class="max-w-2xl mx-auto">

        <div class="bg-white rounded-3xl border border-slate-200 shadow-card overflow-hidden">
            {{-- Header --}}
            <div class="bg-gradient-to-br from-[#1F2937] via-[#0F172A] to-[#1F2937] px-6 py-10 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-white/10 border border-white/15 mb-5">
                    <svg class="w-11 h-11 text-[#4ECDC4]" fill="currentColor" viewBox="0 0 24 24"><path d="M17.6 9.48l1.84-3.18a.4.4 0 00-.15-.55.4.4 0 00-.54.15l-1.86 3.22a11.4 11.4 0 00-8.78 0L6.25 5.9a.4.4 0 00-.54-.15.4.4 0 00-.15.55L7.4 9.48A10.8 10.8 0 002 18h20a10.8 10.8 0 00-5.4-8.52zM7 15.25a1 1 0 110-2 1 1 0 010 2zm10 0a1 1 0 110-2 1 1 0 010 2z"/></svg>
                </div>
                <h1 class="font-display text-3xl font-bold text-white tracking-tight">Application Kardafrica</h1>
                <p class="text-slate-300 text-sm mt-2">Achète et gère tes cartes cadeaux depuis ton téléphone Android.</p>
            </div>

            {{-- Bouton de téléchargement --}}
            <div class="px-6 py-8 text-center">
                <a href="{{ $apkUrl }}" download
                   class="inline-flex items-center justify-center gap-3 px-8 py-4 rounded-2xl bg-[#44A08D] hover:bg-[#3d8f7e] text-white font-bold text-lg shadow-lg shadow-[#44A08D]/25 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                    Télécharger pour Android
                </a>
                <p class="text-xs text-slate-400 mt-3">Fichier .apk · Android 6.0+ · Version {{ $version }}</p>
            </div>

            {{-- Instructions d'installation --}}
            <div class="px-6 pb-8">
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5">
                    <h2 class="font-semibold text-amber-900 text-sm mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Comment installer
                    </h2>
                    <ol class="text-sm text-amber-800 space-y-2 list-decimal list-inside">
                        <li>Appuie sur <strong>Télécharger pour Android</strong> ci-dessus.</li>
                        <li>Ouvre le fichier <strong>.apk</strong> téléchargé.</li>
                        <li>Si Android le demande, autorise l'installation depuis <strong>« sources inconnues »</strong> (Réglages &rarr; Sécurité).</li>
                        <li>Appuie sur <strong>Installer</strong>, puis ouvre l'app.</li>
                    </ol>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
