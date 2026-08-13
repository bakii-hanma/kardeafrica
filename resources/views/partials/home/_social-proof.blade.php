{{-- ================================================================
     PREUVE SOCIALE — compteurs (données réelles) + témoignages
     Variables : $homeStats (cards_delivered, brands, delivery_label),
                 $testimonials (config/marketing.php — à remplacer par de vrais avis)
     ================================================================ --}}
<section class="mb-16">
    <div class="rounded-3xl bg-gradient-to-br from-[#0F172A] via-[#0F172A] to-[#0F4F44] p-8 md:p-10 shadow-pop relative overflow-hidden">
        <div class="absolute -top-24 -right-24 w-72 h-72 bg-[#44A08D]/25 rounded-full blur-3xl"></div>

        {{-- Compteurs — données RÉELLES. La tuile "cartes livrées" n'apparaît
             que si le nombre est réel (>0) pour ne jamais afficher "0" au
             lancement (aucun chiffre inventé). --}}
        @php $delivered = (int) ($homeStats['cards_delivered'] ?? 0); @endphp
        <div class="relative grid grid-cols-3 gap-4 text-center">
            @if($delivered > 0)
                <div>
                    <div class="font-display text-2xl sm:text-4xl font-black text-white tabular-nums">{{ number_format($delivered, 0, ',', ' ') }}</div>
                    <div class="text-[11px] sm:text-sm text-slate-400 mt-1">cartes livrées</div>
                </div>
            @else
                <div>
                    <div class="font-display text-2xl sm:text-4xl font-black text-white">100%</div>
                    <div class="text-[11px] sm:text-sm text-slate-400 mt-1">digital & instantané</div>
                </div>
            @endif
            <div class="border-x border-white/10">
                <div class="font-display text-2xl sm:text-4xl font-black text-[#4ECDC4] tabular-nums">{{ $homeStats['delivery_label'] ?? '< 60 s' }}</div>
                <div class="text-[11px] sm:text-sm text-slate-400 mt-1">délai de livraison</div>
            </div>
            <div>
                <div class="font-display text-2xl sm:text-4xl font-black text-white tabular-nums">{{ $homeStats['brands'] ?? 300 }}+</div>
                <div class="text-[11px] sm:text-sm text-slate-400 mt-1">marques disponibles</div>
            </div>
        </div>

        {{-- Témoignages (affichés seulement si de vrais avis sont configurés) --}}
        @if(!empty($testimonials))
            <div class="relative mt-8 grid md:grid-cols-3 gap-4">
                @foreach(array_slice($testimonials, 0, 3) as $t)
                    <div class="bg-white/[0.06] border border-white/10 rounded-2xl p-5">
                        <div class="flex items-center gap-1 mb-2 text-[#FBBF24]">
                            @for($i = 0; $i < (int) ($t['rating'] ?? 5); $i++)
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.447a1 1 0 00-.364 1.118l1.287 3.957c.3.921-.755 1.688-1.54 1.118l-3.367-2.446a1 1 0 00-1.175 0l-3.367 2.446c-.784.57-1.838-.197-1.539-1.118l1.286-3.957a1 1 0 00-.363-1.118L2.075 10.1c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.957z"/></svg>
                            @endfor
                        </div>
                        <p class="text-sm text-slate-200 leading-relaxed">« {{ $t['quote'] ?? '' }} »</p>
                        <div class="mt-3 flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#4ECDC4] to-[#44A08D] flex items-center justify-center text-white text-xs font-bold">
                                {{ strtoupper(substr($t['name'] ?? '?', 0, 1)) }}
                            </div>
                            <div class="text-xs">
                                <div class="text-white font-semibold">{{ $t['name'] ?? '' }}</div>
                                <div class="text-slate-400">{{ $t['city'] ?? '' }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
