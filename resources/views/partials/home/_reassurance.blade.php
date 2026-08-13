{{-- ================================================================
     BANDEAU RÉASSURANCE — 3 garanties + moyens de paiement
     ================================================================ --}}
<section class="mb-12">
    <div class="rounded-2xl border border-slate-100 bg-white shadow-card p-5 sm:p-6">
        <div class="grid sm:grid-cols-3 gap-4 sm:gap-6">
            @foreach([
                ['M13 10V3L4 14h7v7l9-11h-7z', 'Livraison instantanée', 'Votre code arrive en moins de 60 secondes après paiement.'],
                ['M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', 'Paiement sécurisé', 'Airtel Money, Moov Money & carte bancaire via notre partenaire.'],
                ['M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'Code garanti', 'Invalide ou non reçu ? Remboursé sous 24 h.'],
            ] as [$icon, $title, $desc])
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#4ECDC4] to-[#44A08D] flex items-center justify-center text-white shadow-lg shadow-[#44A08D]/20 shrink-0">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-bold text-slate-900">{{ $title }}</div>
                        <p class="text-xs text-slate-500 mt-0.5 leading-snug">{{ $desc }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
