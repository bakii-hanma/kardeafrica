{{--
    Montant en deux teintes : la valeur en plein, l'unité (« FCFA ») mutée à 60 %.
    `label` optionnel au-dessus, en petites capitales mutées.
    Les chiffres sont tabulaires par héritage du shell (.adm).
--}}
@props(['value', 'unit' => 'FCFA', 'label' => null])
<div {{ $attributes }}>
    @if ($label)
        <span class="ui-stat-label">{{ $label }}</span>
    @endif
    <span class="ui-stat">{{ is_numeric($value) ? number_format((float) $value, 0, ',', ' ') : $value }}@if($unit !== null && $unit !== '')<small>{{ $unit }}</small>@endif</span>
</div>
