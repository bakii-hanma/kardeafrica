@extends('admin.layouts.admin')

@section('title', 'Comptes pro')
@section('page-title', 'Comptes pro / commerçants')

@section('content')
<div style="padding:24px;font-family:'Inter','Figtree',sans-serif;">

    @if(session('success'))
        <div style="margin-bottom:16px;padding:12px 16px;background:#D1FAE5;border:1px solid #A7F3D0;border-radius:12px;color:#047857;font-size:14px;font-weight:600;">{{ session('success') }}</div>
    @endif

    {{-- Onglets par statut --}}
    <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:18px;">
        @php
            $tabs = [
                'provisional'    => ['À valider', '#44A08D'],
                'docs_requested' => ['Pièces demandées', '#D97706'],
                'active'         => ['Validés', '#047857'],
                'rejected'       => ['Refusés', '#DC2626'],
                'suspended'      => ['Suspendus', '#64748B'],
            ];
        @endphp
        @foreach($tabs as $key => [$label, $color])
            <a href="{{ route('admin.proprietaires.index', ['status' => $key]) }}"
               style="text-decoration:none;background:white;border-radius:12px;border:1px solid {{ $status === $key ? $color : '#E2E8F0' }};padding:12px 16px;display:flex;align-items:center;gap:10px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
                <span style="font-size:12px;font-weight:700;color:#475569;">{{ $label }}</span>
                <span style="font-family:'Space Grotesk','Inter',sans-serif;font-size:16px;font-weight:800;color:{{ $color }};">{{ $counts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    {{-- Liste --}}
    <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;overflow:hidden;">
        @forelse($owners as $owner)
            <a href="{{ route('admin.proprietaires.show', $owner) }}"
               style="display:flex;align-items:center;gap:16px;padding:16px 18px;text-decoration:none;border-bottom:1px solid #F1F5F9;color:inherit;">
                <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#4ECDC4,#44A08D);display:flex;align-items:center;justify-content:center;color:white;font-weight:800;font-family:'Space Grotesk',sans-serif;flex-shrink:0;">
                    {{ strtoupper(substr($owner->business_name, 0, 1)) }}
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:14px;font-weight:700;color:#0F172A;">{{ $owner->business_name }}</div>
                    <div style="font-size:12px;color:#64748B;margin-top:2px;">
                        {{ $owner->contact_name }} · {{ $owner->city ?: '—' }}{{ $owner->quartier ? ' ('.$owner->quartier.')' : '' }} · {{ $owner->whatsapp_number }}
                    </div>
                </div>
                <div style="font-size:11px;color:#94A3B8;text-align:right;flex-shrink:0;">
                    {{ $owner->kyc_submitted_at?->diffForHumans() ?? $owner->created_at->diffForHumans() }}
                </div>
            </a>
        @empty
            <div style="padding:40px;text-align:center;color:#94A3B8;font-size:14px;">Aucun compte dans cet état.</div>
        @endforelse
    </div>

    <div style="margin-top:16px;">{{ $owners->links() }}</div>
</div>
@endsection
