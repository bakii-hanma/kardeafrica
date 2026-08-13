@extends('layouts.app')

@section('title', 'Guides — Cartes cadeaux & Mobile Money au Gabon | KardAfrica')
@section('meta_description', 'Guides pratiques : payer Netflix par Airtel Money, acheter une carte PSN au Gabon, prix des cartes cadeaux en FCFA, payer en ligne par Mobile Money. Étapes claires + prix réels.')

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context'        => 'https://schema.org',
    '@type'           => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => route('home')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Guides',  'item' => url()->current()],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<div class="bg-[#FAFAF7] min-h-screen pb-20">

    <div class="bg-gradient-to-br from-[#0F172A] to-[#134E4A]">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-14 text-center">
            <span class="inline-block px-3 py-1 rounded-full bg-white/10 text-[#4ECDC4] text-xs font-bold tracking-wider uppercase mb-4 border border-white/10">Guides pratiques</span>
            <h1 class="font-display text-3xl md:text-4xl font-bold text-white tracking-tight">
                Cartes cadeaux & Mobile Money,<br class="hidden sm:block"> expliqués simplement.
            </h1>
            <p class="text-sm text-slate-300 mt-4 max-w-xl mx-auto">
                Des réponses concrètes aux questions que tout le monde se pose au Gabon :
                étapes détaillées, prix réels en FCFA, zéro jargon.
            </p>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 -mt-6">
        <div class="grid sm:grid-cols-2 gap-5">
            @foreach ($guides as $slug => $guide)
                <a href="{{ route('guides.show', $slug) }}"
                   class="block bg-white rounded-2xl border border-slate-100 shadow-card p-6 hover:shadow-card-hover hover:-translate-y-1 transition-all duration-300">
                    <h2 class="font-display text-lg font-bold text-slate-900 leading-snug">{{ $guide['h1'] }}</h2>
                    <p class="text-sm text-slate-500 mt-2 leading-relaxed">{{ $guide['excerpt'] }}</p>
                    <div class="flex items-center justify-between mt-4">
                        <span class="text-xs text-slate-400">Mis à jour le {{ \Carbon\Carbon::parse($guide['updated'])->locale('fr')->translatedFormat('d/m/Y') }}</span>
                        <span class="text-xs font-bold text-[#44A08D]">Lire le guide →</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
