@extends('owner.layouts.owner')

@section('title', 'Mes cartes')
@section('page-title', 'Mes cartes')
@section('page-subtitle', $cards->total() . ' carte' . ($cards->total() > 1 ? 's' : '') . ' rattachée' . ($cards->total() > 1 ? 's' : '') . ' à votre compte')

@section('content')
{{-- Filtre statut --}}
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px;">
    <a href="{{ route('owner.cards') }}" style="padding:8px 14px;border-radius:10px;background:{{ $status === '' ? '#0F172A' : '#FFF' }};color:{{ $status === '' ? '#FFF' : '#475569' }};border:1px solid {{ $status === '' ? '#0F172A' : '#E2E8F0' }};font-size:12px;font-weight:700;text-decoration:none;">Toutes</a>
    <a href="{{ route('owner.cards', ['status' => 'active']) }}" style="padding:8px 14px;border-radius:10px;background:{{ $status === 'active' ? '#10B981' : '#FFF' }};color:{{ $status === 'active' ? '#FFF' : '#475569' }};border:1px solid {{ $status === 'active' ? '#10B981' : '#E2E8F0' }};font-size:12px;font-weight:700;text-decoration:none;">Actives</a>
    <a href="{{ route('owner.cards', ['status' => 'inactive']) }}" style="padding:8px 14px;border-radius:10px;background:{{ $status === 'inactive' ? '#64748B' : '#FFF' }};color:{{ $status === 'inactive' ? '#FFF' : '#475569' }};border:1px solid {{ $status === 'inactive' ? '#64748B' : '#E2E8F0' }};font-size:12px;font-weight:700;text-decoration:none;">Inactives</a>
</div>

@if($cards->count() === 0)
    <div style="background:white;border:2px dashed #CBD5E1;border-radius:14px;padding:48px 24px;text-align:center;">
        <div style="font-size:48px;margin-bottom:8px;">🎴</div>
        <h3 style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:800;color:#0F172A;margin:0 0 4px;">Aucune carte rattachée</h3>
        <p style="color:#64748B;font-size:13px;margin:0;">Contacte l'admin Kardafrica pour qu'il te rattache des cartes locales.</p>
    </div>
@else
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
        @foreach($cards as $card)
            <a href="{{ route('owner.card.show', $card) }}"
               style="background:white;border:1px solid #E2E8F0;border-radius:14px;overflow:hidden;text-decoration:none;color:inherit;box-shadow:0 2px 8px rgba(15,23,42,.05);transition:transform .15s, box-shadow .15s;display:block;">
                <div style="position:relative;aspect-ratio:1.55;background:linear-gradient(135deg,#1E293B,#0F4F44);">
                    @if($card->visual_url)
                        <img src="{{ asset($card->visual_url) }}" style="width:100%;height:100%;object-fit:cover;" alt="">
                    @endif
                    <span style="position:absolute;top:10px;right:10px;padding:3px 10px;border-radius:9999px;background:{{ $card->is_active ? '#10B981' : '#64748B' }};color:white;font-size:9px;font-weight:800;letter-spacing:.06em;">
                        {{ $card->is_active ? 'ACTIVE' : 'BROUILLON' }}
                    </span>
                </div>
                <div style="padding:14px 16px;">
                    <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:14px;font-weight:800;color:#0F172A;line-height:1.3;margin-bottom:6px;">{{ $card->name }}</div>
                    <div style="display:flex;align-items:center;justify-content:space-between;font-size:11px;color:#64748B;">
                        <span>{{ $card->purchases_paid_count }} vendue{{ $card->purchases_paid_count > 1 ? 's' : '' }}</span>
                        <span style="font-weight:700;color:#0F172A;font-variant-numeric:tabular-nums;">{{ number_format($card->total_revenue ?? 0, 0, ',', ' ') }} FCFA</span>
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    <div style="margin-top:18px;">{{ $cards->links() }}</div>
@endif
@endsection
