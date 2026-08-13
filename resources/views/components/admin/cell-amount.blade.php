{{-- Montant compact : valeur en plein, unité mutée, chiffres tabulaires. --}}
@props(['value', 'unit' => 'FCFA'])
<span class="cll-amount">{{ number_format((float) $value, 0, ',', ' ') }}@if($unit)<small>{{ $unit }}</small>@endif</span>
