{{--
    Panneau du langage « layered ».
    variant : default (blanc, ombre douce) · inset (grège, posé DANS un panneau)
              · highlight (navy, texte blanc, accent teal via .ui-card-accent)
    Aucune couleur ici : tout vient des tokens (admin-tokens.css).
--}}
@props(['variant' => 'default'])
<div {{ $attributes->merge(['class' => 'ui-card' . ($variant !== 'default' ? ' ui-card--' . $variant : '')]) }}>
    {{ $slot }}
</div>
