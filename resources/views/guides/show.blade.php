@extends('layouts.app')

@section('title', $guide['title'] . ' | KardAfrica')
@section('meta_description', $guide['meta'])
@section('og_type', 'article')
@section('og_title', $guide['title'])
@section('og_description', $guide['excerpt'])

@push('head')
{{-- Article + FAQPage + Breadcrumb (JSON-LD via json_encode — jamais en Blade brut, cf. piège @@context) --}}
<script type="application/ld+json">
{!! json_encode([
    '@context'      => 'https://schema.org',
    '@type'         => 'Article',
    'headline'      => $guide['title'],
    'description'   => $guide['meta'],
    'inLanguage'    => 'fr',
    'dateModified'  => $guide['updated'],
    'author'        => ['@type' => 'Organization', 'name' => 'KardAfrica', 'url' => url('/')],
    'publisher'     => ['@type' => 'Organization', 'name' => 'KardAfrica', 'logo' => ['@type' => 'ImageObject', 'url' => asset('assets/logo/FAVCON-KARDAFRICA-.png')]],
    'mainEntityOfPage' => url()->current(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
<script type="application/ld+json">
{!! json_encode([
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => collect($guide['faq'])->map(fn ($f) => [
        '@type'          => 'Question',
        'name'           => $f[0],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
    ])->values()->all(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
<script type="application/ld+json">
{!! json_encode([
    '@context'        => 'https://schema.org',
    '@type'           => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => route('home')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Guides',  'item' => route('guides.index')],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $guide['h1'], 'item' => url()->current()],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<div class="bg-[#FAFAF7] min-h-screen pb-20">

    {{-- Breadcrumb --}}
    <div class="bg-white border-b border-slate-200">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-3">
            <nav class="flex items-center gap-1.5 text-xs text-slate-500">
                <a href="{{ route('home') }}" class="hover:text-[#44A08D] transition">Accueil</a>
                <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ route('guides.index') }}" class="hover:text-[#44A08D] transition">Guides</a>
                <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-900 font-medium truncate max-w-[220px]">{{ $guide['h1'] }}</span>
            </nav>
        </div>
    </div>

    <article class="max-w-3xl mx-auto px-4 sm:px-6 pt-10">

        <h1 class="font-display text-3xl md:text-4xl font-bold text-slate-900 tracking-tight leading-tight">
            {{ $guide['h1'] }}
        </h1>
        <p class="text-xs text-slate-400 mt-3">
            Guide KardAfrica · Mis à jour le {{ \Carbon\Carbon::parse($guide['updated'])->locale('fr')->translatedFormat('d F Y') }}
            @if(!empty($priceTables)) · Prix vérifiés le {{ now()->locale('fr')->translatedFormat('d F Y') }} (catalogue en direct) @endif
        </p>

        {{-- ============ Contenu spécifique du guide ============ --}}
        <div class="prose-guide mt-8">
            @include('guides.content.' . $slug)
        </div>

        {{-- ============ Tableaux de prix LIVE ============ --}}
        @foreach ($priceTables as $label => $rows)
            <section class="mt-10">
                <h2 class="font-display text-xl font-bold text-slate-900 mb-1">{{ $label }}</h2>
                <p class="text-xs text-slate-400 mb-4">Prix réels de notre catalogue, vérifiés le {{ now()->locale('fr')->translatedFormat('d F Y') }} — le prix affiché est le prix final en FCFA.</p>
                <div class="bg-white rounded-2xl border border-slate-100 shadow-card overflow-x-auto">
                    <table class="w-full text-sm" style="border-collapse:collapse;min-width:480px;">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 text-left text-xs">
                                <th class="px-4 py-3 font-semibold">Carte</th>
                                <th class="px-4 py-3 font-semibold">Valeur</th>
                                <th class="px-4 py-3 font-semibold text-right">Prix en FCFA</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $row['name'] }}</td>
                                    <td class="px-4 py-3 text-slate-600 tabular-nums">{{ $row['face'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right font-black tabular-nums text-slate-900">{{ $row['fcfa'] }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ $row['url'] }}" class="text-[#44A08D] font-bold text-xs hover:underline whitespace-nowrap">Acheter →</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach

        {{-- ============ FAQ ============ --}}
        <section class="mt-12" x-data="{ open: 0 }">
            <h2 class="font-display text-xl font-bold text-slate-900 mb-4">Questions fréquentes</h2>
            <div class="space-y-3">
                @foreach ($guide['faq'] as $i => [$q, $a])
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden">
                        <button type="button" @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                                class="w-full flex items-center justify-between gap-4 text-left px-5 py-4">
                            <span class="text-sm font-semibold text-slate-900">{{ $q }}</span>
                            <svg class="h-5 w-5 text-[#44A08D] shrink-0 transition-transform duration-200" :class="open === {{ $i }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open === {{ $i }}" x-collapse x-cloak>
                            <p class="px-5 pb-5 text-sm text-slate-600 leading-relaxed">{{ $a }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ============ CTA ============ --}}
        <section class="mt-12 rounded-2xl bg-gradient-to-br from-[#0F172A] to-[#134E4A] p-8 text-center">
            <h2 class="font-display text-xl font-bold text-white">Prêt à commencer ?</h2>
            <p class="text-sm text-slate-300 mt-2">Payez par Airtel Money ou Moov Money — code reçu en 30 secondes, remboursé sous 24 h si invalide.</p>
            <a href="{{ route('boutique') }}" class="inline-flex items-center gap-2 mt-5 px-6 py-3 rounded-xl bg-[#44A08D] hover:bg-[#3d9180] text-white font-bold text-sm transition">
                Voir toutes les cartes
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </section>

        {{-- ============ Autres guides (maillage interne) ============ --}}
        <section class="mt-12">
            <h2 class="font-display text-lg font-bold text-slate-900 mb-4">À lire aussi</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                @foreach (collect($allGuides)->except($slug)->take(3) as $otherSlug => $other)
                    <a href="{{ route('guides.show', $otherSlug) }}"
                       class="block bg-white rounded-2xl border border-slate-100 shadow-card p-5 hover:shadow-card-hover hover:-translate-y-0.5 transition">
                        <div class="text-sm font-bold text-slate-900 leading-snug">{{ $other['h1'] }}</div>
                        <p class="text-xs text-slate-500 mt-2 line-clamp-2">{{ $other['excerpt'] }}</p>
                        <span class="inline-block mt-3 text-xs font-bold text-[#44A08D]">Lire le guide →</span>
                    </a>
                @endforeach
            </div>
        </section>
    </article>
</div>

@once
<style>
    /* Typo éditoriale des guides (contenu inclus via guides/content/*) */
    .prose-guide { color: #334155; font-size: 15px; line-height: 1.75; }
    .prose-guide p { margin: 0 0 1em; }
    .prose-guide h2 { font-family: 'Space Grotesk','Inter',sans-serif; font-size: 20px; font-weight: 700; color: #0F172A; margin: 1.8em 0 0.6em; }
    .prose-guide a { color: #44A08D; font-weight: 600; }
    .prose-guide a:hover { text-decoration: underline; }
    .prose-guide ol.steps { counter-reset: step; list-style: none; margin: 1.2em 0; padding: 0; }
    .prose-guide ol.steps > li { counter-increment: step; position: relative; padding: 0 0 1.2em 46px; }
    .prose-guide ol.steps > li::before {
        content: counter(step); position: absolute; left: 0; top: 0;
        width: 30px; height: 30px; border-radius: 50%;
        background: #E6F5F1; color: #44A08D; font-weight: 800; font-size: 14px;
        display: flex; align-items: center; justify-content: center;
    }
    .prose-guide ol.steps b { color: #0F172A; }
    .prose-guide .callout {
        background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 14px;
        padding: 14px 18px; margin: 1.2em 0; font-size: 14px; color: #92400E;
    }
    .prose-guide .callout.info { background: #F0FDF9; border-color: #99F6E4; color: #0F766E; }
</style>
@endonce
@endsection
