@extends('admin.layouts.admin')

@section('title', 'Utilisateurs')
@section('page-title', 'Utilisateurs')

@php
    $roleInfo = [
        'admin'     => ['label' => 'Admin',     'bg' => '#EDE9FE', 'text' => '#5B21B6', 'border' => '#DDD6FE'],
        'moderator' => ['label' => 'Modérateur','bg' => '#FEF3C7', 'text' => '#92400E', 'border' => '#FDE68A'],
        'user'      => ['label' => 'Client',    'bg' => '#DBEAFE', 'text' => '#1E40AF', 'border' => '#BFDBFE'],
    ];
    $activeFilters = (int)!empty(request('search')) + (int)!empty(request('role')) + (request('is_active') !== null && request('is_active') !== '');
    $totalUsers   = \App\Models\User::count();
    $activeCount  = \App\Models\User::where('is_active', true)->count();
    $adminsCount  = \App\Models\User::where('role', 'admin')->count();
    $todayCount   = \App\Models\User::whereDate('created_at', today())->count();
@endphp

@section('content')
<div style="padding:24px;font-family:'Inter','Figtree',sans-serif;">

    {{-- Stats --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:14px;margin-bottom:18px;">
        @foreach ([
            ['label' => 'Total comptes',   'value' => number_format($totalUsers),  'color' => '#0F172A', 'bg' => '#F1F5F9', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
            ['label' => 'Actifs',          'value' => number_format($activeCount), 'color' => '#059669', 'bg' => '#ECFDF5', 'icon' => 'M5 13l4 4L19 7'],
            ['label' => 'Administrateurs', 'value' => number_format($adminsCount), 'color' => '#7C3AED', 'bg' => '#FAF5FF', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
            ['label' => "Aujourd'hui",     'value' => number_format($todayCount),  'color' => '#44A08D', 'bg' => '#ECFDF5', 'icon' => 'M12 4v16m8-8H4'],
        ] as $card)
            <div style="background:white;border-radius:14px;padding:16px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:6px;">
                    <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#64748B;">{{ $card['label'] }}</span>
                    <div style="width:30px;height:30px;border-radius:8px;background:{{ $card['bg'] }};color:{{ $card['color'] }};display:flex;align-items:center;justify-content:center;">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}"/></svg>
                    </div>
                </div>
                <div style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:22px;font-weight:800;color:#0F172A;line-height:1.1;">{{ $card['value'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('admin.users.index') }}"
          style="background:white;border-radius:14px;padding:12px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);margin-bottom:18px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <div style="position:relative;flex:1;min-width:240px;">
            <svg style="position:absolute;left:14px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#94A3B8;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, email, téléphone..."
                   style="width:100%;padding:10px 14px 10px 40px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;outline:none;">
        </div>
        <select name="role" onchange="this.form.submit()"
                style="padding:10px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:13px;font-weight:500;color:#334155;outline:none;cursor:pointer;min-width:160px;">
            <option value="">Tous rôles</option>
            <option value="admin"     {{ request('role') === 'admin'     ? 'selected' : '' }}>Admin</option>
            <option value="moderator" {{ request('role') === 'moderator' ? 'selected' : '' }}>Modérateur</option>
            <option value="user"      {{ request('role') === 'user'      ? 'selected' : '' }}>Client</option>
        </select>
        <select name="is_active" onchange="this.form.submit()"
                style="padding:10px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:13px;font-weight:500;color:#334155;outline:none;cursor:pointer;min-width:140px;">
            <option value="">Tous statuts</option>
            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Actifs</option>
            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactifs</option>
        </select>
        <button type="submit" style="padding:10px 18px;background:#44A08D;color:white;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;">Filtrer</button>
        @if($activeFilters > 0)
            <a href="{{ route('admin.users.index') }}" style="padding:10px 14px;color:#64748B;font-size:12px;font-weight:600;text-decoration:none;background:#F1F5F9;border-radius:10px;display:inline-flex;align-items:center;">✕</a>
        @endif

        <a href="{{ route('admin.users.create') }}"
           style="margin-left:auto;padding:10px 16px;background:#0F172A;color:white;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
            <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Nouvel utilisateur
        </a>
    </form>

    @if($users->count() > 0)
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:12px;">
            @foreach($users as $user)
                @php $rInfo = $roleInfo[$user->role] ?? ['label' => $user->role, 'bg' => '#F1F5F9', 'text' => '#475569', 'border' => '#E2E8F0']; @endphp
                <a href="{{ route('admin.users.show', $user) }}"
                   style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:14px;text-decoration:none;color:inherit;box-shadow:0 1px 2px rgba(15,23,42,0.04);transition:all 0.2s ease;display:flex;flex-direction:column;gap:10px;"
                   onmouseover="this.style.boxShadow='0 8px 20px rgba(15,23,42,0.08)';this.style.transform='translateY(-2px)';this.style.borderColor='#CBD5E1';"
                   onmouseout="this.style.boxShadow='0 1px 2px rgba(15,23,42,0.04)';this.style.transform='none';this.style.borderColor='#E2E8F0';">

                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#4ECDC4,#44A08D);display:flex;align-items:center;justify-content:center;color:white;font-family:'Space Grotesk', 'Inter', sans-serif;font-weight:700;font-size:18px;flex-shrink:0;{{ !$user->is_active ? 'opacity:0.5;' : '' }}">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                <span style="font-size:14px;font-weight:600;color:#0F172A;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:160px;">{{ $user->name }}</span>
                                @if(!$user->is_active)
                                    <span style="padding:1px 6px;border-radius:4px;background:#FEE2E2;color:#991B1B;font-size:9px;font-weight:700;">Inactif</span>
                                @endif
                            </div>
                            <div style="font-size:11px;color:#64748B;font-family:monospace;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $user->email }}</div>
                        </div>
                        <span style="display:inline-flex;padding:3px 8px;border-radius:9999px;font-size:10px;font-weight:700;background:{{ $rInfo['bg'] }};color:{{ $rInfo['text'] }};border:1px solid {{ $rInfo['border'] }};flex-shrink:0;">
                            {{ $rInfo['label'] }}
                        </span>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;padding-top:10px;border-top:1px solid #F1F5F9;">
                        <div>
                            <div style="font-size:9px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Commandes</div>
                            <div style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:16px;font-weight:700;color:#0F172A;margin-top:2px;">{{ $user->orders_count ?? 0 }}</div>
                        </div>
                        <div>
                            <div style="font-size:9px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Cartes</div>
                            <div style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:16px;font-weight:700;color:#0F172A;margin-top:2px;">{{ $user->user_cards_count ?? 0 }}</div>
                        </div>
                    </div>

                    <div style="display:flex;align-items:center;justify-content:space-between;font-size:10px;color:#94A3B8;">
                        <span>Inscrit le {{ $user->created_at->format('d/m/Y') }}</span>
                        @if($user->phone)
                            <span style="font-family:monospace;">{{ $user->phone }}</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        @if($users->hasPages())
            <div style="margin-top:18px;">{{ $users->links() }}</div>
        @endif
    @else
        <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:60px 40px;text-align:center;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="width:60px;height:60px;border-radius:14px;background:#F1F5F9;margin:0 auto 14px;display:flex;align-items:center;justify-content:center;">
                <svg style="width:26px;height:26px;color:#CBD5E1;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <div style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:16px;font-weight:700;color:#0F172A;margin-bottom:4px;">Aucun utilisateur trouvé</div>
            <div style="font-size:13px;color:#64748B;">{{ $activeFilters > 0 ? 'Aucun utilisateur ne correspond à vos filtres' : 'Aucun utilisateur enregistré.' }}</div>
        </div>
    @endif
</div>
@endsection
