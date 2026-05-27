@extends('admin.layouts.admin')

@section('title', 'Cartes locales')
@section('page-title', 'Cartes-cadeau locales (Carte Gabon)')

@section('content')
<div style="padding:24px;font-family:'Inter','Figtree',sans-serif;">

    {{-- ============ STATS ============ --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:18px;">
        {{-- Total --}}
        <a href="{{ route('admin.merchant-cards.index') }}" style="text-decoration:none;background:white;border-radius:14px;border:1px solid {{ $status === '' ? '#44A08D' : '#E2E8F0' }};padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,0.04);transition:transform .15s;">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#F8FAFC,#F1F5F9);border:1px solid #E2E8F0;display:flex;align-items:center;justify-content:center;color:#475569;flex-shrink:0;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <div>
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Total</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:2px;">{{ $stats['total'] }}</div>
            </div>
        </a>

        {{-- Active (publiées) --}}
        <a href="{{ route('admin.merchant-cards.index', ['status' => 'active']) }}" style="text-decoration:none;background:white;border-radius:14px;border:1px solid {{ $status === 'active' ? '#10B981' : '#E2E8F0' }};padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,0.04);transition:transform .15s;">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#D1FAE5,#A7F3D0);display:flex;align-items:center;justify-content:center;color:#047857;flex-shrink:0;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Publiées</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#047857;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:2px;">{{ $stats['active'] }}</div>
            </div>
        </a>

        {{-- Brouillons --}}
        <a href="{{ route('admin.merchant-cards.index', ['status' => 'pending']) }}" style="text-decoration:none;background:white;border-radius:14px;border:1px solid {{ $status === 'pending' ? '#64748B' : '#E2E8F0' }};padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,0.04);transition:transform .15s;">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#F1F5F9,#E2E8F0);display:flex;align-items:center;justify-content:center;color:#475569;flex-shrink:0;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Brouillons</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#475569;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:2px;">{{ $stats['pending'] }}</div>
            </div>
        </a>

        {{-- Rejected --}}
    </div>

    {{-- ============ TOOLBAR ============ --}}
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-bottom:18px;">
        <a href="{{ route('admin.merchant-cards.create') }}"
           style="display:inline-flex;align-items:center;gap:8px;padding:12px 18px;background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;border-radius:12px;font-size:14px;font-weight:700;text-decoration:none;box-shadow:0 10px 24px -10px rgba(68,160,141,0.5);order:2;">
            <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Nouvelle carte
        </a>

        <form method="GET" action="{{ route('admin.merchant-cards.index') }}"
              style="flex:1 1 auto;min-width:280px;background:white;border-radius:14px;padding:10px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
            <div style="position:relative;flex:1 1 240px;min-width:200px;">
                <svg style="position:absolute;left:14px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#94A3B8;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ $search }}" placeholder="Nom de carte, marchand, code vendeur (KA-V-…)"
                       style="width:100%;padding:10px 14px 10px 40px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;outline:none;">
            </div>
            @if($status)
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <button type="submit" style="padding:10px 18px;background:#44A08D;color:white;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;">Filtrer</button>
            @if($search || $status)
                <a href="{{ route('admin.merchant-cards.index') }}" style="padding:10px 14px;color:#64748B;font-size:12px;font-weight:600;text-decoration:none;background:#F1F5F9;border-radius:10px;display:inline-flex;align-items:center;">✕ Reset</a>
            @endif
        </form>
    </div>

    {{-- ============ GRID ============ --}}
    @if($cards->count() > 0)
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">
            @foreach($cards as $card)
                @php
                    $isActive = $card->is_active;
                    $badgeBg   = $isActive ? '#10B981' : '#64748B';
                    $badgeText = $isActive ? 'ACTIVE' : 'BROUILLON';
                @endphp
                <a href="{{ route('admin.merchant-cards.show', $card) }}"
                   style="background:white;border-radius:14px;border:1px solid #E2E8F0;overflow:hidden;text-decoration:none;color:inherit;box-shadow:0 2px 8px rgba(15,23,42,0.05);transition:transform .15s,box-shadow .15s;display:block;">

                    {{-- Visual preview --}}
                    <div style="position:relative;aspect-ratio:1.55;background:linear-gradient(135deg,#1E293B,#0F4F44);">
                        @if($card->visual_url)
                            <img src="{{ asset($card->visual_url) }}" alt="" style="width:100%;height:100%;object-fit:cover;">
                        @else
                            <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.5);font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;">Aucun visuel</div>
                        @endif
                        <div style="position:absolute;top:10px;left:10px;background:{{ $badgeBg }};color:white;font-size:10px;font-weight:800;padding:4px 10px;border-radius:9999px;letter-spacing:0.06em;backdrop-filter:blur(4px);">
                            {{ $badgeText }}
                        </div>
                    </div>

                    {{-- Body --}}
                    <div style="padding:14px 16px;">
                        @if(isset($categories[$card->category]))
                            <div style="font-size:10px;font-weight:800;color:#44A08D;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:3px;">{{ $categories[$card->category] }}</div>
                        @endif
                        <h3 style="font-family:'Space Grotesk','Inter',sans-serif;font-size:15px;font-weight:800;color:#0F172A;margin:0 0 6px;line-height:1.25;">{{ $card->name }}</h3>

                        <div style="font-size:12px;color:#64748B;margin-bottom:10px;">
                            <span style="display:inline-flex;align-items:center;gap:5px;">
                                <svg style="width:11px;height:11px;color:#94A3B8;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                {{ $card->reseller?->business_name ?? $card->reseller?->name ?? 'Catalogue admin' }}
                            </span>
                            @if($card->reseller?->vendor_code)
                                <span style="margin-left:6px;font-family:ui-monospace,monospace;font-size:10px;color:#94A3B8;">{{ $card->reseller->vendor_code }}</span>
                            @endif
                        </div>

                        <div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:10px;">
                            @foreach(array_slice($card->denominations ?? [], 0, 4) as $d)
                                <span style="background:#F1F5F9;color:#475569;font-size:10px;font-weight:700;padding:3px 7px;border-radius:5px;font-variant-numeric:tabular-nums;">{{ number_format($d, 0, ',', ' ') }}</span>
                            @endforeach
                            @if(count($card->denominations ?? []) > 4)
                                <span style="background:#F1F5F9;color:#94A3B8;font-size:10px;font-weight:700;padding:3px 7px;border-radius:5px;">+{{ count($card->denominations) - 4 }}</span>
                            @endif
                        </div>

                        <div style="display:inline-flex;align-items:center;justify-content:center;padding:8px 14px;background:#F1F5F9;color:#475569;font-size:12px;font-weight:700;border-radius:8px;margin-top:4px;">Détails →</div>
                    </div>
                </a>
            @endforeach
        </div>

        <div style="margin-top:20px;">{{ $cards->links() }}</div>
    @else
        <div style="background:white;border-radius:14px;padding:60px 24px;text-align:center;border:2px dashed #E2E8F0;">
            <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;border-radius:50%;background:#F1F5F9;color:#94A3B8;margin-bottom:14px;">
                <svg style="width:28px;height:28px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <h3 style="margin:0 0 6px;font-family:'Space Grotesk','Inter',sans-serif;font-weight:800;color:#0F172A;">Aucune carte</h3>
            <p style="margin:0;color:#64748B;font-size:14px;">
                @if($search || $status)
                    Aucun résultat pour ces filtres.
                @else
                    Aucune carte locale pour le moment. Clique sur « Nouvelle carte » pour en créer une.
                @endif
            </p>
        </div>
    @endif
</div>

@endsection
