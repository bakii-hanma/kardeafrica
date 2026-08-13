{{--
    variant :
      delta  — « +12 % » avec flèche, fond teal-soft, texte navy (AA : teal sur
               teal-soft = 2,2:1, refusé — décision documentée dans les tokens)
      count  — badge compteur de sidebar
      status — mappe les statuts métier EXISTANTS sur 6 tons sémantiques,
               sans réécrire leur logique d'affichage
--}}
@props(['variant' => 'status', 'status' => null, 'down' => false])
@php
    // Mapping partagé (App\Support\AdminStatus) : la pill, les onglets des
    // écrans liste et les tests lisent la même table.
    $classe = match ($variant) {
        'delta' => 'ui-pill--delta',
        'count' => 'ui-pill--count',
        default => 'ui-pill--status-' . \App\Support\AdminStatus::tone($status),
    };
@endphp
<span {{ $attributes->merge(['class' => 'ui-pill ' . $classe]) }}>
    @if ($variant === 'delta')
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3" aria-hidden="true">
            @if ($down)
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
            @else
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
            @endif
        </svg>
    @endif
    {{ $slot }}
</span>
