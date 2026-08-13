{{--
    Canal de paiement en IconChip — même convention de couleur que la barre
    segmentée du P2 : Mobile Money teal, carte bleu, autre violet.
--}}
@props(['method'])
@php
    $canal  = \App\Support\AdminDashboardStats::channelBucket($method);
    $teinte = ['mobile' => 'teal', 'card' => 'blue'][$canal] ?? 'violet';
    $libelle = ['mobile' => 'Mobile Money', 'card' => 'Carte bancaire'][$canal] ?? 'Autre';
@endphp
<x-ui.icon-chip :color="$teinte" class="cll-chan" :title="$libelle . ($method ? ' · ' . $method : '')">
    @if ($canal === 'mobile')
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
    @elseif ($canal === 'card')
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
    @else
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    @endif
</x-ui.icon-chip>
