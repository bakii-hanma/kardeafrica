{{-- Client : initiales en pastille + nom, sous-ligne optionnelle. --}}
@props(['name' => null, 'sub' => null, 'tone' => 'navy'])
@php $nom = trim((string) $name); @endphp
<span class="cll-user">
    <span class="cll-ini cll-ini--{{ $tone }}" aria-hidden="true">
        {{ $nom !== '' ? mb_strtoupper(mb_substr($nom, 0, 2)) : '—' }}
    </span>
    <span class="cll-user-txt">
        <span class="cll-user-name">{{ $nom !== '' ? $nom : 'Client' }}</span>
        @if ($sub)<span class="cll-user-sub">{{ $sub }}</span>@endif
    </span>
</span>
