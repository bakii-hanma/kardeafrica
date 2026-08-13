{{--
    État vide : icône mutée + libellé. Remplace le rendu des états vides
    existants sans toucher à leur logique d'affichage (les @if restent
    dans les pages).
--}}
@props(['label'])
<div {{ $attributes->merge(['class' => 'ui-empty']) }}>
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
    </svg>
    <p class="ui-empty-label">{{ $label ?? $slot }}</p>
</div>
