@extends('admin.layouts.admin')

@section('title', 'Utilisateur - ' . $user->name)
@section('page-title', $user->name)

@php
    $brandPalette = [
        'Netflix' => '#E50914', 'Spotify' => '#1DB954', 'Apple' => '#000000',
        'PlayStation' => '#003791', 'Xbox' => '#107C10', 'Amazon' => '#FF9900',
        'Steam' => '#171A21', 'Nintendo' => '#E60012', 'StarzPlay' => '#7C3AED',
        'Talabat' => '#FF5A00', 'HUAWEI' => '#C7000B',
    ];
    $brandColorFor = function ($name) use ($brandPalette) {
        if (!$name) return '#1F2937';
        foreach ($brandPalette as $key => $color) {
            if (stripos($name, $key) !== false) return $color;
        }
        $palette = ['#0F172A', '#44A08D', '#0EA5E9', '#7C3AED', '#DC2626', '#EA580C', '#059669'];
        $hash = 0;
        for ($i = 0; $i < strlen($name); $i++) $hash = ord($name[$i]) + (($hash << 5) - $hash);
        return $palette[(($hash % count($palette)) + count($palette)) % count($palette)];
    };

    $roleInfo = [
        'admin'     => ['label' => 'Admin',     'bg' => '#EDE9FE', 'text' => '#5B21B6', 'border' => '#DDD6FE'],
        'moderator' => ['label' => 'Modérateur','bg' => '#FEF3C7', 'text' => '#92400E', 'border' => '#FDE68A'],
        'user'      => ['label' => 'Client',    'bg' => '#DBEAFE', 'text' => '#1E40AF', 'border' => '#BFDBFE'],
    ];
    $rInfo = $roleInfo[$user->role] ?? ['label' => $user->role, 'bg' => '#F1F5F9', 'text' => '#475569', 'border' => '#E2E8F0'];

    $statusBadgeMap = [
        'pending'    => ['label' => 'En attente',    'bg' => '#FEF3C7', 'text' => '#B45309', 'border' => '#FDE68A'],
        'processing' => ['label' => 'En traitement', 'bg' => '#E0F2FE', 'text' => '#0369A1', 'border' => '#BAE6FD'],
        'completed'  => ['label' => 'Terminée',      'bg' => '#D1FAE5', 'text' => '#047857', 'border' => '#A7F3D0'],
        'cancelled'  => ['label' => 'Annulée',       'bg' => '#FFE4E6', 'text' => '#BE123C', 'border' => '#FECDD3'],
        'failed'     => ['label' => 'Échouée',       'bg' => '#FFE4E6', 'text' => '#BE123C', 'border' => '#FECDD3'],
    ];
@endphp

@section('content')
<div style="padding:24px;font-family:'Inter','Figtree',sans-serif;">

    <a href="{{ route('admin.users.index') }}"
       style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:white;border:1px solid #E2E8F0;border-radius:9px;color:#475569;text-decoration:none;font-size:13px;font-weight:600;margin-bottom:14px;">
        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Tous les utilisateurs
    </a>

    {{-- Header card (sans actions, juste profil) --}}
    <div style="background:linear-gradient(135deg,#1F2937 0%,#0F172A 100%);border-radius:16px;padding:24px;margin-bottom:14px;color:white;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-40px;right:-40px;width:240px;height:240px;border-radius:50%;background:rgba(78,205,196,0.15);filter:blur(60px);"></div>
        <div style="position:relative;display:flex;align-items:center;gap:18px;flex-wrap:wrap;">
            <div style="width:80px;height:80px;border-radius:20px;background:linear-gradient(135deg,#4ECDC4,#44A08D);display:flex;align-items:center;justify-content:center;font-family:'Space Grotesk', 'Inter', sans-serif;font-weight:700;font-size:32px;color:white;flex-shrink:0;box-shadow:0 8px 20px rgba(78,205,196,0.3);">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div style="flex:1;min-width:200px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;flex-wrap:wrap;">
                    <h1 style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:24px;font-weight:700;margin:0;">{{ $user->name }}</h1>
                    <span style="display:inline-flex;padding:3px 8px;border-radius:9999px;font-size:10px;font-weight:700;background:{{ $rInfo['bg'] }};color:{{ $rInfo['text'] }};">{{ $rInfo['label'] }}</span>
                    @if($user->is_active)
                        <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:9999px;font-size:10px;font-weight:700;background:rgba(16,185,129,0.20);color:#86EFAC;border:1px solid rgba(16,185,129,0.30);">
                            <span style="width:5px;height:5px;border-radius:50%;background:#86EFAC;"></span>
                            Actif
                        </span>
                    @else
                        <span style="display:inline-flex;padding:3px 8px;border-radius:9999px;font-size:10px;font-weight:700;background:rgba(244,63,94,0.25);color:#FCA5A5;border:1px solid rgba(244,63,94,0.35);">
                            <span style="width:5px;height:5px;border-radius:50%;background:#FCA5A5;display:inline-block;margin-right:3px;"></span>
                            Inactif
                        </span>
                    @endif
                </div>
                <div style="font-size:13px;color:#CBD5E1;font-family:monospace;">{{ $user->email }}</div>
                @if($user->phone)
                    <div style="font-size:12px;color:#94A3B8;margin-top:4px;display:flex;align-items:center;gap:4px;">
                        <svg style="width:11px;height:11px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        {{ $user->phone }}
                    </div>
                @endif
                <div style="font-size:11px;color:#64748B;margin-top:6px;">Inscrit le {{ $user->created_at->format('d/m/Y · H:i') }}</div>
            </div>
        </div>
    </div>

    {{-- Action bar : modification rôle + désactivation (clair, large, hors gradient sombre) --}}
    <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);padding:14px 18px;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;">

        {{-- Bloc gauche : modification du rôle --}}
        <div style="display:flex;align-items:center;gap:12px;flex:1;min-width:280px;">
            <div style="width:36px;height:36px;border-radius:10px;background:#EFF6FF;color:#2563EB;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#64748B;">Rôle de l'utilisateur</div>
                <div style="font-size:11px;color:#94A3B8;margin-top:2px;">Modifier les permissions du compte</div>
            </div>
            <form action="{{ route('admin.users.update-role', $user) }}" method="POST" style="margin:0;display:flex;align-items:center;gap:6px;flex-shrink:0;">
                @csrf @method('PATCH')
                <select name="role"
                        style="padding:9px 32px 9px 12px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:9px;font-size:13px;font-weight:600;color:#0F172A;cursor:pointer;outline:none;transition:all 0.15s;appearance:none;background-image:url('data:image/svg+xml;utf8,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; width=&quot;12&quot; height=&quot;12&quot; viewBox=&quot;0 0 24 24&quot; fill=&quot;none&quot; stroke=&quot;%2364748b&quot; stroke-width=&quot;2.5&quot;><path stroke-linecap=&quot;round&quot; stroke-linejoin=&quot;round&quot; d=&quot;M19 9l-7 7-7-7&quot;/></svg>');background-repeat:no-repeat;background-position:right 10px center;"
                        onfocus="this.style.borderColor='#44A08D';this.style.boxShadow='0 0 0 3px rgba(68,160,141,0.1)';"
                        onblur="this.style.borderColor='#E2E8F0';this.style.boxShadow='none';">
                    <option value="user"      {{ $user->role === 'user' ? 'selected' : '' }}>Client</option>
                    <option value="moderator" {{ $user->role === 'moderator' ? 'selected' : '' }}>Modérateur</option>
                    <option value="admin"     {{ $user->role === 'admin' ? 'selected' : '' }}>Administrateur</option>
                </select>
                <button type="submit"
                        style="display:inline-flex;align-items:center;gap:5px;padding:9px 14px;background:#44A08D;color:white;border:none;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;transition:all 0.15s;"
                        onmouseover="this.style.background='#3d9180';" onmouseout="this.style.background='#44A08D';">
                    <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Mettre à jour
                </button>
            </form>
        </div>

        {{-- Séparateur vertical --}}
        <div style="width:1px;height:36px;background:#E2E8F0;flex-shrink:0;"></div>

        {{-- Bloc droit : activation/désactivation --}}
        <div style="display:flex;align-items:center;gap:12px;flex-shrink:0;">
            <div style="width:36px;height:36px;border-radius:10px;background:{{ $user->is_active ? '#FEF2F2' : '#ECFDF5' }};color:{{ $user->is_active ? '#DC2626' : '#059669' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                @if($user->is_active)
                    <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                @else
                    <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                @endif
            </div>
            <div style="min-width:0;">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#64748B;">Statut du compte</div>
                <div style="font-size:11px;color:#94A3B8;margin-top:2px;">
                    @if($user->is_active)
                        L'utilisateur peut se connecter
                    @else
                        Compte désactivé
                    @endif
                </div>
            </div>
            <form action="{{ route('admin.users.toggle-active', $user) }}" method="POST" style="margin:0;flex-shrink:0;"
                  onsubmit="return confirm('{{ $user->is_active ? "Désactiver ce compte ? L'utilisateur ne pourra plus se connecter." : "Réactiver ce compte ?" }}');">
                @csrf @method('PATCH')
                <button type="submit"
                        style="display:inline-flex;align-items:center;gap:5px;padding:9px 14px;background:{{ $user->is_active ? '#FEE2E2' : '#D1FAE5' }};color:{{ $user->is_active ? '#991B1B' : '#065F46' }};border:1px solid {{ $user->is_active ? '#FECACA' : '#A7F3D0' }};border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;transition:all 0.15s;"
                        onmouseover="this.style.background='{{ $user->is_active ? '#FECACA' : '#A7F3D0' }}';"
                        onmouseout="this.style.background='{{ $user->is_active ? '#FEE2E2' : '#D1FAE5' }}';">
                    @if($user->is_active)
                        <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        Désactiver
                    @else
                        <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Réactiver
                    @endif
                </button>
            </form>
        </div>
    </div>

    {{-- Stats compact --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:14px;margin-bottom:18px;">
        @foreach ([
            ['label' => 'Commandes',    'value' => number_format($user->orders_count),    'meta' => $completedOrdersCount . ' terminées', 'color' => '#0F172A', 'bg' => '#F1F5F9', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ['label' => 'Cartes',       'value' => number_format($user->user_cards_count),'meta' => $activeCardsCount . ' actives',      'color' => '#44A08D', 'bg' => '#ECFDF5', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
            ['label' => 'Total dépensé','value' => number_format($totalSpent, 0, ',', ' '),  'meta' => 'FCFA',                              'color' => '#7C3AED', 'bg' => '#FAF5FF', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'],
            ['label' => 'Dernière commande', 'value' => $lastOrderAt ? \Carbon\Carbon::parse($lastOrderAt)->diffForHumans() : '—', 'meta' => $lastOrderAt ? \Carbon\Carbon::parse($lastOrderAt)->format('d/m/Y') : 'Jamais', 'color' => '#0369A1', 'bg' => '#EFF6FF', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'compact' => true],
        ] as $card)
            <div style="background:white;border-radius:14px;padding:16px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:6px;">
                    <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#64748B;">{{ $card['label'] }}</span>
                    <div style="width:30px;height:30px;border-radius:8px;background:{{ $card['bg'] }};color:{{ $card['color'] }};display:flex;align-items:center;justify-content:center;">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}"/></svg>
                    </div>
                </div>
                <div style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:{{ !empty($card['compact']) ? '15px' : '22px' }};font-weight:800;color:#0F172A;line-height:1.1;letter-spacing:-0.01em;">{{ $card['value'] }}</div>
                <div style="font-size:11px;color:#94A3B8;margin-top:3px;">{{ $card['meta'] }}</div>
            </div>
        @endforeach
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;align-items:flex-start;">

        {{-- Commandes recentes --}}
        <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);overflow:hidden;">
            <div style="padding:14px 18px;border-bottom:1px solid #F1F5F9;background:linear-gradient(180deg,#F8FAFC,white);display:flex;align-items:center;justify-content:space-between;">
                <h2 style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:14px;font-weight:700;color:#0F172A;margin:0;">
                    Commandes récentes
                    <span style="font-size:11px;color:#94A3B8;font-weight:500;">· {{ $user->orders->count() }} sur {{ $user->orders_count }}</span>
                </h2>
            </div>
            @if($user->orders->isNotEmpty())
                <div style="max-height:480px;overflow-y:auto;">
                    @foreach($user->orders as $order)
                        @php $sBadge = $statusBadgeMap[$order->status] ?? ['label' => $order->status, 'bg' => '#F1F5F9', 'text' => '#475569', 'border' => '#E2E8F0']; @endphp
                        <a href="{{ route('admin.orders.show', $order) }}"
                           style="display:flex;align-items:center;gap:10px;padding:12px 18px;text-decoration:none;color:inherit;{{ !$loop->last ? 'border-bottom:1px solid #F1F5F9;' : '' }}transition:background 0.15s;"
                           onmouseover="this.style.background='#FAFBFC';" onmouseout="this.style.background='white';">
                            <div style="width:32px;height:32px;border-radius:8px;background:#F1F5F9;display:flex;align-items:center;justify-content:center;color:#94A3B8;flex-shrink:0;">
                                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-family:monospace;font-size:12px;font-weight:700;color:#0F172A;">#{{ $order->order_number }}</div>
                                <div style="display:flex;align-items:center;gap:6px;margin-top:2px;flex-wrap:wrap;">
                                    <span style="display:inline-flex;align-items:center;gap:3px;padding:1px 6px;border-radius:9999px;font-size:9px;font-weight:700;background:{{ $sBadge['bg'] }};color:{{ $sBadge['text'] }};border:1px solid {{ $sBadge['border'] }};">
                                        <span style="width:3px;height:3px;border-radius:50%;background:{{ $sBadge['text'] }};"></span>
                                        {{ $sBadge['label'] }}
                                    </span>
                                    <span style="font-size:10px;color:#94A3B8;">{{ $order->orderItems->count() }} article{{ $order->orderItems->count() > 1 ? 's' : '' }} · {{ $order->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                            <div style="text-align:right;flex-shrink:0;">
                                <div style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:13px;font-weight:700;color:#0F172A;">{{ number_format($order->total_amount, 0, ',', ' ') }}</div>
                                <div style="font-size:9px;color:#94A3B8;">FCFA</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div style="padding:40px;text-align:center;color:#94A3B8;font-size:13px;">Aucune commande</div>
            @endif
        </div>

        {{-- Cartes recentes (grille design carte) --}}
        <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);overflow:hidden;">
            <div style="padding:14px 18px;border-bottom:1px solid #F1F5F9;background:linear-gradient(180deg,#F8FAFC,white);display:flex;align-items:center;justify-content:space-between;">
                <h2 style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:14px;font-weight:700;color:#0F172A;margin:0;">
                    Cartes
                    <span style="font-size:11px;color:#94A3B8;font-weight:500;">· {{ $user->userCards->count() }} sur {{ $user->user_cards_count }}</span>
                </h2>
            </div>
            @if($user->userCards->isNotEmpty())
                <div style="padding:12px;display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;max-height:480px;overflow-y:auto;">
                    @foreach($user->userCards as $card)
                        @php
                            $brandColor = $brandColorFor($card->brand ?? $card->name ?? '');
                            $pricePaid  = (float) ($card->orderItem?->unit_price ?? 0);
                        @endphp
                        <a href="{{ $card->order_id ? route('admin.orders.show', $card->order_id) : route('admin.cards.index') }}"
                           style="background:white;border-radius:10px;border:1px solid #E2E8F0;overflow:hidden;text-decoration:none;color:inherit;transition:transform 0.15s;"
                           onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='none';">
                            <div style="background-color:{{ $brandColor }};padding:10px;height:80px;position:relative;overflow:hidden;">
                                <svg style="position:absolute;inset:0;width:100%;height:100%;opacity:0.08;" aria-hidden="true">
                                    <defs><pattern id="uc-{{ $card->id }}" width="22" height="22" patternUnits="userSpaceOnUse"><circle cx="11" cy="11" r="9" fill="none" stroke="white" stroke-width="1"/></pattern></defs>
                                    <rect width="100%" height="100%" fill="url(#uc-{{ $card->id }})"/>
                                </svg>
                                <div style="position:relative;display:flex;flex-direction:column;height:100%;justify-content:space-between;">
                                    <div>
                                        <div style="color:rgba(255,255,255,0.75);font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:0.15em;">Gift Card</div>
                                        <div style="font-family:'Space Grotesk', 'Inter', sans-serif;color:white;font-size:14px;font-weight:700;line-height:1;margin-top:1px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                            {{ $card->brand ?: explode(' ', $card->name)[0] }}
                                        </div>
                                    </div>
                                    <div style="color:white;font-family:'Space Grotesk', 'Inter', sans-serif;font-size:12px;font-weight:700;font-variant-numeric:tabular-nums;">
                                        {{ number_format($pricePaid, 0, ',', ' ') }} <span style="font-size:9px;font-weight:400;color:rgba(255,255,255,0.7);">FCFA</span>
                                    </div>
                                </div>
                            </div>
                            <div style="padding:8px 10px;">
                                <div style="font-size:10px;font-weight:600;color:#0F172A;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $card->name }}</div>
                                <div style="font-size:9px;color:#94A3B8;margin-top:2px;">{{ $card->created_at->format('d/m/Y') }}
                                    @if($card->status === 'active')
                                        <span style="margin-left:4px;color:#059669;">·  Active</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div style="padding:40px;text-align:center;color:#94A3B8;font-size:13px;">Aucune carte</div>
            @endif
        </div>
    </div>
</div>
@endsection
