{{--
    Cellule « Cartes » de la liste commandes : affiche jusqu'à 3 cartes (nom +
    code courts), puis « +N autres ». Les codes restent lisibles en tooltip.
--}}
@props(['cards'])
@php
    $all = $cards instanceof \Illuminate\Support\Collection ? $cards->values() : collect($cards)->values();
    $visible = $all->take(3);
    $rest = max(0, $all->count() - 3);
@endphp
<div style="display:flex;flex-direction:column;gap:3px;max-width:220px;">
    @forelse ($visible as $card)
        @php
            $name = $card->name ?: 'Carte cadeau';
            $code = $card->card_code ?? '';
            $title = $name . ($code ? ' · ' . $code : '');
        @endphp
        <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;line-height:1.35;color:var(--text);">
            <svg style="width:11px;height:11px;color:var(--teal);flex:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h14a2 2 0 012 2v2H3V7zm0 4h18v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6zm5 4h6"/></svg>
            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $title }}">{{ $name }}</span>
        </span>
    @empty
        <span style="font-size:11px;color:var(--text-faint);">—</span>
    @endforelse
    @if ($rest > 0)
        <a href="#" class="lst-ref-sub" style="text-decoration:none;color:var(--teal);font-weight:700;font-size:11px;" title="{{ $all->skip(3)->pluck('name')->implode("\n") }}">+ {{ $rest }} autre{{ $rest > 1 ? 's' : '' }}</a>
    @endif
</div>