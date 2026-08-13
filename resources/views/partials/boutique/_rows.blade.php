{{-- ================================================================
     P1 §2 — CATALOGUE EN LIGNES THÉMATIQUES
     Desktop (≥768px) : sections empilées, GRILLE STATIQUE 3-4 colonnes.
     Mobile : les MÊMES sections en galeries défilantes horizontales
     (scroll-snap, cards ~70 % de largeur, dernier élément = « Voir tout »).
     Reçoit : $rows (CatalogRows::build), + closures du parent
     ($brandColorFor, $sanitizeLogo, $urlWith).
     ================================================================ --}}
@foreach($rows as $row)
    <section class="mb-10">
        <div class="flex items-center justify-between gap-3 mb-4">
            <h2 class="font-display text-lg md:text-xl font-bold text-slate-900 tracking-tight">{{ $row['title'] }}</h2>
            <a href="{{ $urlWith(array_merge($row['see_all'], ['page' => null])) }}"
               class="shrink-0 inline-flex items-center gap-1 text-xs md:text-sm font-bold text-[#44A08D] hover:underline whitespace-nowrap">
                Voir tout
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <div class="flex gap-3 overflow-x-auto snap-x snap-mandatory pb-3 -mx-4 px-4
                    md:grid md:grid-cols-3 lg:grid-cols-4 md:gap-4 md:overflow-visible md:mx-0 md:px-0 md:pb-0"
             style="scrollbar-width: none; -ms-overflow-style: none;">

            @foreach($row['items'] as $product)
                @php
                    $brandName = $product['cardType']['name'] ?? ($product['name'] ?? 'Carte');
                    $rCtId     = $product['cardType']['internalId'] ?? $product['cardType']['id'] ?? null;
                    $rHref     = $rCtId ? route('card-type.show', $rCtId) : route('boutique');
                    $rMin      = $product['price']['min'] ?? 0;
                    $rCur      = $product['price']['currencyCode'] ?? 'XAF';

                    $rVariants = collect($product['variants'] ?? [])
                        ->filter(fn ($v) => ($v['face'] ?? 0) > 0 && $rCtId)
                        ->map(fn ($v) => [
                            'label' => \App\Support\Money::formatOriginal($v['face'], $v['currency'])
                                ?? number_format($v['face'], 0, ',', ' '),
                            'url'   => route('card-type.variant', [
                                $v['card_type_id'] ?? $rCtId,
                                fmod((float) $v['face'], 1.0) === 0.0
                                    ? (string) (int) $v['face']
                                    : rtrim(rtrim(number_format((float) $v['face'], 2, '.', ''), '0'), '.'),
                            ]),
                        ])->values()->all();
                @endphp
                <div class="snap-start shrink-0 w-[70%] xs:w-[55%] sm:w-[38%] md:w-auto md:shrink">
                    <x-product-card
                        :name="$brandName"
                        :brand-label="$brandName"
                        :brand-color="$brandColorFor($brandName)"
                        :logo-url="$sanitizeLogo($product['cardType']['logoUrl'] ?? null)"
                        :price="$rMin"
                        :face-value="$product['minFaceValue'] ?? $rMin"
                        :currency="$rCur"
                        :href="$rHref"
                        :products-count="$product['variants_count'] ?? null"
                        :country-code="$product['cardType']['countryCode'] ?? null"
                        :variants="$rVariants"
                    />
                </div>
            @endforeach

            {{-- Mobile uniquement : dernier élément = card « Voir tout » --}}
            <a href="{{ $urlWith(array_merge($row['see_all'], ['page' => null])) }}"
               class="md:hidden snap-start shrink-0 w-[40%] rounded-2xl border-2 border-dashed border-slate-200 bg-white flex flex-col items-center justify-center gap-2 text-center p-4 text-[#44A08D] active:scale-95 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                <span class="text-xs font-bold leading-tight">Voir tout<br>{{ $row['title'] }}</span>
            </a>
        </div>
    </section>
@endforeach

@once
<style>
    /* Masque la scrollbar des galeries mobiles (le snap suffit comme affordance) */
    .snap-x::-webkit-scrollbar { display: none; }
</style>
@endonce
