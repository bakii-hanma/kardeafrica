@extends('admin.layouts.admin')

@section('title', 'Propriétaires de cartes')
@section('page-title', 'Propriétaires de cartes locales')

@section('content')
<div style="padding:24px;font-family:'Inter','Figtree',sans-serif;">

    @if(session('success'))
        <div style="background:#D1FAE5;color:#065F46;border:1px solid #6EE7B7;padding:12px 14px;border-radius:12px;margin-bottom:14px;font-size:13px;font-weight:600;">
            ✓ {{ session('success') }}
        </div>
    @endif

    {{-- STATS --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:18px;">
        <a href="{{ route('admin.card-owners.index') }}" style="text-decoration:none;background:white;border-radius:14px;border:1px solid {{ $status === '' ? '#44A08D' : '#E2E8F0' }};padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#F8FAFC,#F1F5F9);border:1px solid #E2E8F0;display:flex;align-items:center;justify-content:center;color:#475569;flex-shrink:0;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Total</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:2px;">{{ $stats['total'] }}</div>
            </div>
        </a>
        <a href="{{ route('admin.card-owners.index', ['status' => 'active']) }}" style="text-decoration:none;background:white;border-radius:14px;border:1px solid {{ $status === 'active' ? '#10B981' : '#E2E8F0' }};padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#D1FAE5,#A7F3D0);display:flex;align-items:center;justify-content:center;color:#047857;flex-shrink:0;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Actifs</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#047857;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:2px;">{{ $stats['active'] }}</div>
            </div>
        </a>
        <a href="{{ route('admin.card-owners.index', ['status' => 'inactive']) }}" style="text-decoration:none;background:white;border-radius:14px;border:1px solid {{ $status === 'inactive' ? '#64748B' : '#E2E8F0' }};padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#F1F5F9,#E2E8F0);display:flex;align-items:center;justify-content:center;color:#475569;flex-shrink:0;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            </div>
            <div>
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Inactifs</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#475569;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:2px;">{{ $stats['inactive'] }}</div>
            </div>
        </a>
    </div>

    {{-- TOOLBAR --}}
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-bottom:18px;">
        <a href="{{ route('admin.card-owners.create') }}"
           style="display:inline-flex;align-items:center;gap:8px;padding:12px 18px;background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;border-radius:12px;font-size:14px;font-weight:700;text-decoration:none;box-shadow:0 10px 24px -10px rgba(68,160,141,0.5);">
            <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Nouveau propriétaire
        </a>

        <form method="GET" action="{{ route('admin.card-owners.index') }}"
              style="flex:1 1 auto;min-width:280px;background:white;border-radius:14px;padding:10px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
            <div style="position:relative;flex:1 1 240px;min-width:200px;">
                <svg style="position:absolute;left:14px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#94A3B8;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ $search }}" placeholder="Raison sociale, contact, email, téléphone…"
                       style="width:100%;padding:10px 14px 10px 40px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;outline:none;">
            </div>
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <button type="submit" style="padding:10px 18px;background:#44A08D;color:white;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;">Filtrer</button>
            @if($search || $status)
                <a href="{{ route('admin.card-owners.index') }}" style="padding:10px 14px;color:#64748B;font-size:12px;font-weight:600;text-decoration:none;background:#F1F5F9;border-radius:10px;display:inline-flex;align-items:center;">✕ Reset</a>
            @endif
        </form>
    </div>

    {{-- LIST --}}
    @if($owners->count() > 0)
        <div style="background:white;border:1px solid #E2E8F0;border-radius:14px;overflow:hidden;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead style="background:#F8FAFC;">
                    <tr style="border-bottom:1px solid #E2E8F0;">
                        <th style="text-align:left;padding:12px 16px;font-size:11px;text-transform:uppercase;letter-spacing:0.06em;color:#64748B;font-weight:700;">Commerce</th>
                        <th style="text-align:left;padding:12px 16px;font-size:11px;text-transform:uppercase;letter-spacing:0.06em;color:#64748B;font-weight:700;">Contact</th>
                        <th style="text-align:left;padding:12px 16px;font-size:11px;text-transform:uppercase;letter-spacing:0.06em;color:#64748B;font-weight:700;">Ville</th>
                        <th style="text-align:center;padding:12px 16px;font-size:11px;text-transform:uppercase;letter-spacing:0.06em;color:#64748B;font-weight:700;">Cartes</th>
                        <th style="text-align:center;padding:12px 16px;font-size:11px;text-transform:uppercase;letter-spacing:0.06em;color:#64748B;font-weight:700;">Statut</th>
                        <th style="padding:12px 16px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($owners as $o)
                        <tr style="border-top:1px solid #F1F5F9;">
                            <td style="padding:14px 16px;">
                                <div style="display:flex;align-items:center;gap:10px;">
                                    @if($o->logo_url)
                                        <img src="{{ asset($o->logo_url) }}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:1px solid #E2E8F0;" alt="">
                                    @else
                                        <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;">
                                            {{ strtoupper(substr($o->business_name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <div style="font-weight:700;color:#0F172A;">{{ $o->business_name }}</div>
                                        <div style="font-size:11px;color:#94A3B8;">{{ $o->slug }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding:14px 16px;">
                                <div style="color:#0F172A;">{{ $o->contact_name }}</div>
                                <div style="font-size:11px;color:#64748B;">{{ $o->email }} · {{ $o->phone }}</div>
                            </td>
                            <td style="padding:14px 16px;color:#475569;">{{ $o->city ?? '—' }}</td>
                            <td style="padding:14px 16px;text-align:center;">
                                <span style="display:inline-block;padding:3px 10px;border-radius:9999px;background:#F1F5F9;color:#0F172A;font-weight:700;font-variant-numeric:tabular-nums;">{{ $o->cards_count }}</span>
                            </td>
                            <td style="padding:14px 16px;text-align:center;">
                                @if($o->is_active)
                                    <span style="display:inline-block;padding:3px 10px;border-radius:9999px;background:#D1FAE5;color:#065F46;font-weight:700;font-size:11px;">Actif</span>
                                @else
                                    <span style="display:inline-block;padding:3px 10px;border-radius:9999px;background:#F1F5F9;color:#475569;font-weight:700;font-size:11px;">Inactif</span>
                                @endif
                            </td>
                            <td style="padding:14px 16px;text-align:right;">
                                <a href="{{ route('admin.card-owners.show', $o) }}" style="display:inline-flex;align-items:center;gap:5px;padding:8px 12px;background:#F8FAFC;color:#0F172A;border:1px solid #E2E8F0;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;">
                                    Voir
                                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top:16px;">{{ $owners->links() }}</div>
    @else
        <div style="background:white;border:2px dashed #CBD5E1;border-radius:14px;padding:48px 24px;text-align:center;">
            <div style="font-size:48px;margin-bottom:8px;">👥</div>
            <h3 style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:800;color:#0F172A;margin:0 0 4px;">Aucun propriétaire pour le moment</h3>
            <p style="color:#64748B;font-size:13px;margin:0 0 14px;">Crée le premier compte propriétaire pour commencer à lui attribuer des cartes locales.</p>
            <a href="{{ route('admin.card-owners.create') }}"
               style="display:inline-flex;align-items:center;gap:6px;padding:10px 16px;background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;">
                + Créer un propriétaire
            </a>
        </div>
    @endif
</div>
@endsection
