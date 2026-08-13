@props([
    'name'          => '',
    'brandLabel'    => null,       // Texte big sur le visuel (ex: "Netflix") — défaut: 1er mot de $name
    'logoUrl'       => null,       // Image officielle afrikard — utilisée en watermark sur le visuel
    'brandColor'    => '#1F2937',  // Fallback si la marque n'est pas dans le registre
    'price'         => 0,
    'faceValue'     => null,       // Valeur NOMINALE dans la devise d'origine (ex: 100 pour "100 EUR"). Défaut: $price.
    'currency'      => 'XAF',
    'href'          => '#',
    'badge'         => null,       // ['label' => 'Populaire', 'tone' => 'success'|'warm'|'teal']
    'discount'      => null,       // Déprécié (badge cosmétique retiré le 10/08) — ignoré.
    'compact'       => false,
    'productsCount' => null,
    'countryCode'   => null,       // 'FR', 'BE', 'EU', 'US' — pilote le drapeau + label région
    'variants'      => null,       // P1 §1 — mini-montants cliquables : [['label' => '10 €', 'url' => …], …] (max 4)
])

@php
    $brandLabel = $brandLabel ?: explode(' ', $name)[0];
    $brandKey   = \App\Support\BrandStyle::detect($brandLabel ?: $name);
    $brandStyle = $brandKey
        ? \App\Support\BrandStyle::style($brandKey)
        : \App\Support\BrandStyle::fallback($brandColor ?: '#1F2937');
    [$flag, $regionLabel] = \App\Support\BrandStyle::region($countryCode);

    // Tones pour le badge "Populaire" / "Promo" / etc.
    $toneMap = [
        'success' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'warm'    => 'bg-amber-50 text-amber-700 border-amber-100',
        'teal'    => 'bg-teal-50 text-teal-700 border-teal-100',
        'slate'   => 'bg-slate-50 text-slate-700 border-slate-200',
    ];
    $badgeClasses = $toneMap[$badge['tone'] ?? 'teal'] ?? $toneMap['teal'];
@endphp

<a href="{{ $href }}"
   {{ $attributes->class([
       'group block overflow-hidden rounded-2xl bg-white border border-slate-100',
       'shadow-card hover:shadow-card-hover transition-all duration-300',
       'hover:-translate-y-1 hover:scale-[1.02] active:scale-[0.99]',
       'focus:outline-none focus-visible:ring-2 focus-visible:ring-[#44A08D]',
   ]) }}>

    {{-- ============================================================
         GIFT CARD VISUAL (= proposition hybride) — partial réutilisé
         ============================================================ --}}
    @include('partials._gift-card-visual', [
        'brandLabel'  => $brandLabel,
        'brandColor'  => $brandColor,
        'countryCode' => $countryCode,
        'currency'    => $currency,
        'faceValue'   => $faceValue ?? $price,
        'compact'     => $compact,
        'logoUrl'     => $logoUrl,
    ])

    @unless($compact)
        {{-- ============================================================
             INFOS PRODUIT (nom, montants, prix)
             ============================================================ --}}
        <div class="relative bg-white p-3 sm:p-4">

            <div class="flex items-start justify-between gap-1.5 sm:gap-2">
                <h4 class="text-xs sm:text-sm font-semibold text-slate-900 leading-snug line-clamp-2 group-hover:text-[#44A08D] transition-colors">
                    {{ $name }}
                </h4>
                @if($badge)
                    <span class="shrink-0 inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold border {{ $badgeClasses }}">
                        {{ $badge['label'] }}
                    </span>
                @endif
            </div>

            @if($productsCount && $productsCount > 1)
                <p class="text-[10px] text-slate-400 mt-0.5 font-medium">
                    {{ $productsCount }} montants au choix
                </p>
            @endif

            @php
                // Économie réelle : la carte vaut sa valeur faciale, le client
                // paie moins. On n'affiche jamais un « ancien prix » — ce n'en
                // est pas un (voir App\Support\ProductPricing).
                $_pricing = \App\Support\ProductPricing::display([
                    'minFaceValue' => $faceValue ?? $price,
                    'price'        => ['min' => $price, 'currencyCode' => $currency],
                ]);
            @endphp

            <div class="mt-2.5 sm:mt-3 pt-2.5 sm:pt-3 border-t border-slate-100">
                <div class="flex items-end justify-between gap-2">
                    <span class="text-[10px] text-slate-400 font-medium">À partir de</span>
                    <span class="text-right leading-tight">
                        @if($_pricing['has_saving'])
                            {{-- La valeur faciale est barrée en tant que VALEUR de la
                                 carte, pas en tant que tarif antérieur. --}}
                            <span class="block text-[10px] text-slate-400 line-through tabular-nums"
                                  title="Valeur de la carte chez le marchand">
                                {{ number_format($_pricing['face_fcfa'], 0, ',', ' ') }} FCFA
                            </span>
                        @endif
                        <span class="text-sm sm:text-base font-black tabular-nums text-slate-900 price-display whitespace-nowrap"
                              data-price="{{ $price }}"
                              data-currency="{{ $currency }}"
                              data-processed="true">
                            {{ \App\Support\Money::formatFcfa($price, $currency) }}
                        </span>
                    </span>
                </div>
            </div>

            {{-- P1 §1 — mini-montants : chaque pill navigue vers l'URL de SA variante.
                 Un <a> imbriqué dans le <a> card serait invalide en HTML → pills en
                 <span role="link" data-variant-href> + handler délégué (@@once bas). --}}
            @if(!empty($variants))
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach(array_slice($variants, 0, 4) as $v)
                        <span role="link" tabindex="0" data-variant-href="{{ $v['url'] }}"
                              class="variant-pill inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] font-bold text-slate-700 tabular-nums hover:border-[#44A08D] hover:text-[#44A08D] transition cursor-pointer">
                            {{ $v['label'] }}
                        </span>
                    @endforeach
                    @if(count($variants) > 4)
                        <span class="inline-flex items-center px-1 py-1 text-[11px] font-bold text-slate-400">+{{ count($variants) - 4 }}</span>
                    @endif
                </div>
            @endif

            {{-- Réassurance : une seule ligne, la promesse qui décide l'achat. --}}
            <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center gap-1.5 text-[10px] font-semibold text-[#0F766E]">
                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Code livré en moins de 2 minutes
            </div>
        </div>
    @endunless
</a>

@once
<script>
    // Pills de variantes DANS un lien card : un <a> imbriqué est invalide en HTML,
    // on navigue donc via data-variant-href (empêche le clic de suivre la card).
    document.addEventListener('click', function (e) {
        const pill = e.target.closest('[data-variant-href]');
        if (pill) {
            e.preventDefault();
            e.stopPropagation();
            window.location.href = pill.dataset.variantHref;
        }
    }, true);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            const pill = e.target.closest('[data-variant-href]');
            if (pill) { e.preventDefault(); window.location.href = pill.dataset.variantHref; }
        }
    }, true);
</script>
@endonce

{{-- Styles du visuel hybride : déplacés dans le partial _gift-card-visual
     pour qu'ils chargent aussi sur les pages qui n'utilisent pas <x-product-card>
     (= card-type.blade.php notamment). --}}
