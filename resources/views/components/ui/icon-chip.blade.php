{{--
    Carré 40 px, fond teinté à 12 % de la couleur sémantique, icône pleine.
    color : teal · blue · violet · orange · navy
    L'icône est passée dans le slot (svg 24×24 stroke currentColor).
--}}
@props(['color' => 'teal'])
<span {{ $attributes->merge(['class' => 'ui-ichip ui-ichip--' . $color]) }} aria-hidden="true">
    {{ $slot }}
</span>
