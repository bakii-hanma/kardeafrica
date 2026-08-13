{{-- ================================================================
     FAQ COURTE — 4 questions clés (accordéon Alpine) + schema FAQPage
     ================================================================ --}}
@php
    // Source unique : alimente l'accordéon ET le JSON-LD FAQPage (audit SEO/GEO —
    // ces questions calquent les PAA Google observées sur le marché).
    $faqItems = [
        ['La carte Netflix (ou autre) fonctionne-t-elle au Gabon ?', 'Oui. Nos cartes cadeaux sont valables sur les comptes de la région indiquée. Vous rechargez votre compte du service concerné avec le code reçu — aucune carte bancaire étrangère nécessaire.'],
        ['Combien de temps pour recevoir mon code ?', 'En général moins de 60 secondes après la confirmation du paiement. Le code apparaît immédiatement dans « Mes cartes » et vous est aussi envoyé.'],
        ['Que se passe-t-il si le code ne fonctionne pas ?', 'Contactez-nous depuis « Mes commandes ». Un code invalide ou non reçu est remboursé sous 24 h — c\'est notre garantie.'],
        ['Puis-je être remboursé ?', 'Oui : si le paiement est débité sans que la commande soit livrée, le remboursement est automatique. Pour tout autre cas, notre support traite votre demande sous 24 h.'],
    ];
@endphp

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => collect($faqItems)->map(fn ($f) => [
        '@type'          => 'Question',
        'name'           => $f[0],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
    ])->values()->all(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

<section class="mb-16" x-data="{ open: 0 }">
    <div class="text-center mb-8">
        <span class="inline-block px-3 py-1 rounded-full bg-teal-50 text-[#44A08D] text-xs font-bold tracking-wider uppercase mb-3 border border-teal-100">Questions fréquentes</span>
        <h2 class="font-display text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">Tout savoir avant d'acheter</h2>
    </div>

    <div class="max-w-2xl mx-auto space-y-3">
        @foreach($faqItems as $i => [$q, $a])
            <div class="bg-white rounded-2xl border border-slate-100 shadow-card overflow-hidden">
                <button type="button" @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                        class="w-full flex items-center justify-between gap-4 text-left px-5 py-4">
                    <span class="text-sm sm:text-base font-semibold text-slate-900">{{ $q }}</span>
                    <svg class="h-5 w-5 text-[#44A08D] shrink-0 transition-transform duration-200" :class="open === {{ $i }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open === {{ $i }}" x-collapse x-cloak>
                    <p class="px-5 pb-5 text-sm text-slate-600 leading-relaxed">{{ $a }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="text-center mt-6">
        <a href="{{ route('support') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-[#44A08D] hover:underline">
            Voir toutes les questions
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>
</section>
