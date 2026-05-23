@extends('admin.layouts.admin')

@section('title', 'Vendeurs')
@section('page-title', 'Réseau revendeurs')

@php
    $activeFilters = (int)!empty(request('search')) + (int)!empty(request('status'));
@endphp

@section('content')
<div style="padding:24px;font-family:'Inter','Figtree',sans-serif;">

    {{-- Stats --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:18px;">
        <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#F8FAFC,#F1F5F9);border:1px solid #E2E8F0;color:#475569;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Total vendeurs</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:2px;">{{ $stats['total'] }}</div>
            </div>
        </div>

        <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#D1FAE5,#A7F3D0);color:#047857;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Actifs</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#047857;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:2px;">{{ $stats['active'] }}</div>
            </div>
        </div>

        <div style="background:linear-gradient(135deg,#0F172A,#1E293B);border-radius:14px;padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 4px 12px rgba(15,23,42,0.15);position:relative;overflow:hidden;">
            <div style="position:absolute;top:-20px;right:-20px;width:90px;height:90px;border-radius:50%;background:radial-gradient(circle,rgba(78,205,196,0.18) 0%,transparent 70%);"></div>
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;display:flex;align-items:center;justify-content:center;flex-shrink:0;position:relative;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <div style="position:relative;">
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:rgba(255,255,255,0.55);">Solde réseau</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:800;color:white;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:2px;">
                    {{ number_format($stats['wallet_sum'], 0, ',', ' ') }}
                    <span style="font-size:11px;font-weight:600;color:rgba(255,255,255,0.55);">FCFA</span>
                </div>
            </div>
        </div>

        <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#FEF3C7,#FDE68A);color:#B45309;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Commissions versées</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:800;color:#B45309;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:2px;">
                    {{ number_format($stats['commission_sum'], 0, ',', ' ') }}
                    <span style="font-size:11px;font-weight:600;color:#94A3B8;">FCFA</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-bottom:18px;">
        <form method="GET" action="{{ route('admin.resellers.index') }}"
              style="flex:1 1 auto;min-width:280px;background:white;border-radius:14px;padding:10px;border:1px solid #E2E8F0;display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
            <div style="position:relative;flex:1 1 220px;min-width:200px;">
                <svg style="position:absolute;left:14px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#94A3B8;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, code, téléphone, email…"
                       style="width:100%;padding:10px 14px 10px 40px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;outline:none;">
            </div>
            <select name="status" onchange="this.form.submit()"
                    style="padding:10px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:13px;font-weight:500;color:#334155;outline:none;cursor:pointer;">
                <option value="">Tous statuts</option>
                <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Actifs</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactifs</option>
            </select>
            <button type="submit" style="padding:10px 18px;background:#44A08D;color:white;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;">Filtrer</button>
            @if($activeFilters > 0)
                <a href="{{ route('admin.resellers.index') }}" style="padding:10px 14px;color:#64748B;font-size:12px;font-weight:600;text-decoration:none;background:#F1F5F9;border-radius:10px;display:inline-flex;align-items:center;">✕</a>
            @endif
        </form>

        <a href="{{ route('admin.resellers.create') }}"
           style="display:inline-flex;align-items:center;gap:8px;padding:12px 18px;background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;border-radius:12px;font-size:14px;font-weight:600;text-decoration:none;box-shadow:0 10px 24px -10px rgba(68,160,141,0.5);">
            <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Nouveau vendeur
        </a>
    </div>

    {{-- Liste --}}
    @if($resellers->count() > 0)
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(360px,1fr));gap:14px;">
            @foreach($resellers as $r)
                <a href="{{ route('admin.resellers.show', $r) }}"
                   style="background:white;border:1px solid #E2E8F0;border-radius:16px;padding:18px;text-decoration:none;color:inherit;box-shadow:0 1px 2px rgba(15,23,42,0.04);transition:all .2s;"
                   onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 12px 24px -12px rgba(15,23,42,0.10)';"
                   onmouseout="this.style.transform='none';this.style.boxShadow='0 1px 2px rgba(15,23,42,0.04)';">

                    <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:14px;">
                        <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;display:flex;align-items:center;justify-content:center;font-family:'Space Grotesk','Inter',sans-serif;font-weight:800;font-size:18px;flex-shrink:0;">
                            {{ strtoupper(substr($r->name, 0, 1)) }}
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:16px;font-weight:700;color:#0F172A;">{{ $r->name }}</div>
                            <div style="font-family:monospace;font-size:11px;color:#64748B;margin-top:1px;">{{ $r->vendor_code }}</div>
                        </div>
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:9999px;font-size:10px;font-weight:700;
                                     background:{{ $r->is_active ? '#D1FAE5' : '#F1F5F9' }};color:{{ $r->is_active ? '#047857' : '#94A3B8' }};border:1px solid {{ $r->is_active ? '#A7F3D0' : '#E2E8F0' }};">
                            <span style="width:5px;height:5px;border-radius:50%;background:{{ $r->is_active ? '#10B981' : '#CBD5E1' }};"></span>
                            {{ $r->is_active ? 'Actif' : 'Inactif' }}
                        </span>
                    </div>

                    {{-- Wallet bar --}}
                    <div style="margin-bottom:10px;">
                        <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:4px;">
                            <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94A3B8;">Solde portefeuille</span>
                            <span style="font-family:'Space Grotesk','Inter',sans-serif;font-size:14px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;">
                                {{ number_format($r->wallet_balance, 0, ',', ' ') }}
                                <span style="font-size:10px;font-weight:500;color:#94A3B8;">/ {{ number_format($r->max_wallet, 0, ',', ' ') }} FCFA</span>
                            </span>
                        </div>
                        <div style="height:6px;background:#F1F5F9;border-radius:9999px;overflow:hidden;">
                            <div style="height:100%;background:linear-gradient(90deg,#44A08D,#4ECDC4);width:{{ min(100, $r->wallet_percentage) }}%;transition:width .3s;"></div>
                        </div>
                    </div>

                    <div style="display:flex;align-items:center;justify-content:space-between;padding-top:10px;border-top:1px solid #F1F5F9;font-size:11px;color:#64748B;">
                        <span>📞 {{ $r->phone ?: '—' }}</span>
                        <span>Commission <strong style="color:#44A08D;">{{ rtrim(rtrim(number_format($r->commission_rate, 2), '0'), '.') }}%</strong></span>
                    </div>
                </a>
            @endforeach
        </div>

        <div style="margin-top:18px;">{{ $resellers->links() }}</div>
    @else
        <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:60px 40px;text-align:center;">
            <div style="width:60px;height:60px;border-radius:14px;background:#F1F5F9;margin:0 auto 14px;display:flex;align-items:center;justify-content:center;">
                <svg style="width:26px;height:26px;color:#CBD5E1;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:16px;font-weight:700;color:#0F172A;margin-bottom:4px;">Aucun vendeur</div>
            <div style="font-size:13px;color:#64748B;margin-bottom:16px;">{{ $activeFilters > 0 ? 'Aucun vendeur ne correspond aux filtres.' : 'Crée ton premier vendeur pour démarrer le réseau.' }}</div>
            @if($activeFilters === 0)
                <a href="{{ route('admin.resellers.create') }}" style="display:inline-flex;align-items:center;gap:6px;padding:10px 18px;background:#44A08D;color:white;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;">+ Nouveau vendeur</a>
            @endif
        </div>
    @endif
</div>
@endsection
