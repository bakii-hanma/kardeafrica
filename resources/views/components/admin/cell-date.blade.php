{{--
    Date relative (« il y a 2 h »), date exacte en title.
    Le relatif se lit d'un coup d'œil dans une liste ; l'exact reste accessible
    sans quitter la page.
--}}
@props(['value'])
@php
    $d = $value instanceof \DateTimeInterface
        ? \Illuminate\Support\Carbon::instance($d = \Illuminate\Support\Carbon::parse($value))
        : ($value ? \Illuminate\Support\Carbon::parse($value) : null);
    $local = $d?->copy()->setTimezone(\App\Support\AdminDashboardStats::TZ);
@endphp
@if ($local)
    <time datetime="{{ $local->toIso8601String() }}" title="{{ $local->format('d/m/Y à H:i') }}" class="cll-date">
        {{ $local->diffForHumans() }}
    </time>
@else
    <span class="cll-date">—</span>
@endif
