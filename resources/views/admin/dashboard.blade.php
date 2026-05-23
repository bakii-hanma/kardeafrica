@extends('admin.layouts.admin')

@section('title', 'Tableau de bord')
@section('page-title', 'Tableau de bord')

@php
    $brandPalette = [
        'Netflix' => '#E50914', 'Spotify' => '#1DB954', 'Apple' => '#000000',
        'iTunes' => '#D60017', 'PlayStation' => '#003791', 'Xbox' => '#107C10',
        'Amazon' => '#FF9900', 'Google' => '#01875F', 'Steam' => '#171A21',
        'Roblox' => '#00A2FF', 'Nintendo' => '#E60012', 'Disney' => '#0E47A1',
        'StarzPlay' => '#7C3AED', 'Talabat' => '#FF5A00', 'HUAWEI' => '#C7000B', 'IKEA' => '#0058A3',
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
@endphp

@section('content')
<div style="padding:24px;font-family:'Inter','Figtree',sans-serif;">

    {{-- Welcome strip --}}
    <div style="background:linear-gradient(135deg,#1F2937 0%,#0F172A 100%);border-radius:16px;padding:20px 24px;margin-bottom:24px;color:white;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-40px;right:-40px;width:200px;height:200px;border-radius:50%;background:rgba(78,205,196,0.15);filter:blur(40px);"></div>
        <div style="position:relative;">
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.1em;color:#94A3B8;font-weight:600;">Bienvenue, {{ explode(' ', Auth::user()->name)[0] }}</div>
            <h2 style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:24px;font-weight:700;margin:4px 0 0;letter-spacing:-0.01em;">Vue d'ensemble · {{ now()->translatedFormat('d F Y') }}</h2>
        </div>
        <div style="display:flex;align-items:center;gap:8px;position:relative;">
            <a href="{{ route('admin.orders.index') }}" style="padding:8px 14px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.15);color:white;border-radius:9px;text-decoration:none;font-size:12px;font-weight:600;">Commandes</a>
            <a href="{{ route('admin.catalog.index') }}" style="padding:8px 14px;background:#4ECDC4;color:#0F172A;border-radius:9px;text-decoration:none;font-size:12px;font-weight:700;">Catalogue</a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:14px;margin-bottom:18px;">
        @foreach ([
            ['label' => 'Revenu total',     'value' => number_format($totalRevenue, 0, ',', ' '),   'unit' => 'FCFA',   'meta' => 'cumulé',                       'gradient' => 'linear-gradient(135deg,#4ECDC4,#44A08D)', 'iconBg' => '#ECFDF5', 'iconColor' => '#059669', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['label' => 'Ce mois',          'value' => number_format($monthlyRevenue, 0, ',', ' '), 'unit' => 'FCFA',   'meta' => 'mois en cours',                'gradient' => '', 'iconBg' => '#EFF6FF', 'iconColor' => '#2563EB', 'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
            ['label' => 'Commandes',        'value' => number_format($totalOrders),                  'unit' => '',       'meta' => $todayOrders . ' aujourd\'hui', 'gradient' => '', 'iconBg' => '#FAF5FF', 'iconColor' => '#7C3AED', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ['label' => 'Utilisateurs',     'value' => number_format($totalUsers),                   'unit' => '',       'meta' => $activeUsers . ' actifs',       'gradient' => '', 'iconBg' => '#FFF7ED', 'iconColor' => '#EA580C', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
        ] as $card)
            <div class="stat-card" style="background:white;border-radius:14px;padding:18px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:8px;">
                    <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#64748B;">{{ $card['label'] }}</span>
                    <div style="width:36px;height:36px;border-radius:10px;background:{{ $card['gradient'] ?: $card['iconBg'] }};display:flex;align-items:center;justify-content:center;color:{{ $card['gradient'] ? 'white' : $card['iconColor'] }};">
                        <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}"/></svg>
                    </div>
                </div>
                <div style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:24px;font-weight:800;color:#0F172A;line-height:1.1;letter-spacing:-0.01em;">
                    {{ $card['value'] }}
                    @if($card['unit'])
                        <span style="font-size:12px;font-weight:500;color:#94A3B8;">{{ $card['unit'] }}</span>
                    @endif
                </div>
                <div style="font-size:11px;color:#64748B;margin-top:4px;">{{ $card['meta'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Second row : 3 cards --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:14px;margin-bottom:24px;">
        <div class="stat-card" style="background:white;border-radius:14px;padding:18px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#64748B;">Cartes actives</div>
                    <div style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:26px;font-weight:800;color:#0F172A;margin-top:6px;line-height:1.1;">{{ number_format($activeCards) }}</div>
                    <div style="font-size:11px;color:#94A3B8;margin-top:2px;">sur {{ $totalCards }} total</div>
                </div>
                <div style="width:48px;height:48px;border-radius:14px;background:#ECFDF5;color:#059669;display:flex;align-items:center;justify-content:center;">
                    <svg style="width:22px;height:22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
            </div>
        </div>

        <div class="stat-card" style="background:white;border-radius:14px;padding:18px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);{{ $pendingOrders > 0 ? 'border-left:3px solid #F59E0B;' : '' }}">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#64748B;">En attente</div>
                    <div style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:26px;font-weight:800;color:{{ $pendingOrders > 0 ? '#D97706' : '#0F172A' }};margin-top:6px;line-height:1.1;">{{ $pendingOrders }}</div>
                    <div style="font-size:11px;color:#94A3B8;margin-top:2px;">commandes à traiter</div>
                </div>
                <div style="width:48px;height:48px;border-radius:14px;background:{{ $pendingOrders > 0 ? '#FEF3C7' : '#F1F5F9' }};color:{{ $pendingOrders > 0 ? '#D97706' : '#94A3B8' }};display:flex;align-items:center;justify-content:center;">
                    <svg style="width:22px;height:22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>

        <a href="{{ route('admin.orders.index') }}?status=processing"
           class="stat-card"
           style="background:linear-gradient(135deg,#1F2937,#0F172A);border-radius:14px;padding:18px;color:white;text-decoration:none;display:flex;align-items:center;justify-content:space-between;position:relative;overflow:hidden;box-shadow:0 4px 16px rgba(15,23,42,0.15);">
            <div style="position:absolute;top:-30px;right:-30px;width:120px;height:120px;border-radius:50%;background:rgba(78,205,196,0.2);filter:blur(30px);"></div>
            <div style="position:relative;">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94A3B8;">Voir les commandes</div>
                <div style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:18px;font-weight:700;margin-top:6px;">en traitement</div>
            </div>
            <svg style="width:26px;height:26px;color:#4ECDC4;position:relative;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </a>
    </div>

    {{-- Cartes récentes (design carte cadeau) --}}
    @if(isset($recentCards) && $recentCards->count() > 0)
        <div style="margin-bottom:24px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <h2 style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:18px;font-weight:700;color:#0F172A;margin:0;display:flex;align-items:center;gap:10px;">
                    <span style="width:30px;height:30px;border-radius:9px;background:linear-gradient(135deg,#4ECDC4,#44A08D);color:white;display:flex;align-items:center;justify-content:center;">
                        <svg style="width:15px;height:15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </span>
                    Cartes récentes
                </h2>
                <a href="{{ route('admin.cards.index') }}" style="font-size:12px;font-weight:600;color:#44A08D;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
                    Voir toutes les cartes
                    <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;">
                @foreach($recentCards as $card)
                    @php
                        $brandColor = $brandColorFor($card->brand ?? $card->name ?? '');
                        $pricePaid  = (float) ($card->orderItem?->unit_price ?? 0);
                    @endphp
                    <a href="{{ $card->order_id ? route('admin.orders.show', $card->order_id) : route('admin.cards.index') }}"
                       style="display:block;background:white;border-radius:14px;border:1px solid #E2E8F0;overflow:hidden;text-decoration:none;color:inherit;box-shadow:0 1px 2px rgba(15,23,42,0.04);transition:all 0.2s ease;"
                       onmouseover="this.style.boxShadow='0 8px 20px rgba(15,23,42,0.08)';this.style.transform='translateY(-2px)';"
                       onmouseout="this.style.boxShadow='0 1px 2px rgba(15,23,42,0.04)';this.style.transform='none';">

                        {{-- Visuel carte (compact) --}}
                        <div style="background-color:{{ $brandColor }};padding:12px;height:90px;position:relative;overflow:hidden;">
                            <svg style="position:absolute;inset:0;width:100%;height:100%;opacity:0.08;" aria-hidden="true">
                                <defs>
                                    <pattern id="dc-{{ $card->id }}" width="28" height="28" patternUnits="userSpaceOnUse">
                                        <circle cx="14" cy="14" r="11" fill="none" stroke="white" stroke-width="1"/>
                                    </pattern>
                                </defs>
                                <rect width="100%" height="100%" fill="url(#dc-{{ $card->id }})"/>
                            </svg>
                            <div style="position:absolute;-top:20px;-right:20px;width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.12);filter:blur(12px);"></div>

                            <div style="position:relative;display:flex;flex-direction:column;height:100%;justify-content:space-between;">
                                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:6px;">
                                    <div style="min-width:0;">
                                        <div style="color:rgba(255,255,255,0.75);font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:0.15em;">Gift Card</div>
                                        <div style="font-family:'Space Grotesk', 'Inter', sans-serif;color:white;font-size:18px;font-weight:700;margin-top:1px;line-height:1.05;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                            {{ $card->brand ?: explode(' ', $card->name)[0] }}
                                        </div>
                                    </div>
                                    @if($card->status === 'active')
                                        <span style="padding:2px 6px;border-radius:9999px;font-size:9px;font-weight:700;color:white;background:rgba(16,185,129,0.30);flex-shrink:0;">
                                            <span style="display:inline-block;width:4px;height:4px;border-radius:50%;background:#86EFAC;margin-right:3px;vertical-align:middle;"></span>Active
                                        </span>
                                    @endif
                                </div>
                                <div style="color:white;font-family:'Space Grotesk', 'Inter', sans-serif;font-size:14px;font-weight:700;font-variant-numeric:tabular-nums;">
                                    {{ number_format($pricePaid, 0, ',', ' ') }} <span style="font-size:10px;font-weight:400;color:rgba(255,255,255,0.7);">FCFA</span>
                                </div>
                            </div>
                        </div>

                        {{-- User info --}}
                        <div style="padding:10px 12px;display:flex;align-items:center;gap:8px;">
                            @if($card->user)
                                <div style="width:26px;height:26px;border-radius:7px;background:linear-gradient(135deg,#4ECDC4,#44A08D);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:10px;flex-shrink:0;">
                                    {{ strtoupper(substr($card->user->name, 0, 1)) }}
                                </div>
                                <div style="flex:1;min-width:0;">
                                    <div style="font-size:11px;font-weight:600;color:#0F172A;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $card->user->name }}</div>
                                    <div style="font-size:10px;color:#94A3B8;">{{ $card->created_at->diffForHumans() }}</div>
                                </div>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Recent Orders --}}
    <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid #F1F5F9;display:flex;align-items:center;justify-content:space-between;">
            <h2 style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:16px;font-weight:700;color:#0F172A;margin:0;">Commandes récentes</h2>
            <a href="{{ route('admin.orders.index') }}" style="font-size:12px;font-weight:600;color:#44A08D;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
                Voir tout
                <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead style="background:#FAFBFC;border-bottom:1px solid #F1F5F9;">
                    <tr>
                        <th style="padding:10px 16px;text-align:left;font-weight:700;color:#64748B;text-transform:uppercase;font-size:10px;letter-spacing:0.08em;">Commande</th>
                        <th style="padding:10px 16px;text-align:left;font-weight:700;color:#64748B;text-transform:uppercase;font-size:10px;letter-spacing:0.08em;">Client</th>
                        <th style="padding:10px 16px;text-align:left;font-weight:700;color:#64748B;text-transform:uppercase;font-size:10px;letter-spacing:0.08em;">Total</th>
                        <th style="padding:10px 16px;text-align:left;font-weight:700;color:#64748B;text-transform:uppercase;font-size:10px;letter-spacing:0.08em;">Statut</th>
                        <th style="padding:10px 16px;text-align:left;font-weight:700;color:#64748B;text-transform:uppercase;font-size:10px;letter-spacing:0.08em;">Paiement</th>
                        <th style="padding:10px 16px;text-align:left;font-weight:700;color:#64748B;text-transform:uppercase;font-size:10px;letter-spacing:0.08em;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                        <tr style="border-bottom:1px solid #F1F5F9;cursor:pointer;transition:background 0.1s;"
                            onmouseover="this.style.background='#FAFBFC'"
                            onmouseout="this.style.background='white'"
                            onclick="window.location='{{ route('admin.orders.show', $order) }}'">
                            <td style="padding:14px 16px;font-family:monospace;font-weight:700;color:#0F172A;font-size:12px;">{{ $order->order_number }}</td>
                            <td style="padding:14px 16px;color:#334155;">{{ $order->user?->name ?? 'N/A' }}</td>
                            <td style="padding:14px 16px;font-weight:700;color:#0F172A;font-variant-numeric:tabular-nums;">{{ $order->formatted_total }}</td>
                            <td style="padding:14px 16px;">
                                <span class="badge-{{ $order->status }}" style="display:inline-flex;padding:3px 8px;border-radius:9999px;font-size:11px;font-weight:700;">{{ $order->status_label }}</span>
                            </td>
                            <td style="padding:14px 16px;">
                                <span class="badge-{{ $order->payment_status }}" style="display:inline-flex;padding:3px 8px;border-radius:9999px;font-size:11px;font-weight:700;">{{ $order->payment_status_label }}</span>
                            </td>
                            <td style="padding:14px 16px;color:#64748B;font-size:12px;">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:60px 16px;text-align:center;color:#94A3B8;">
                                <svg style="width:36px;height:36px;color:#CBD5E1;margin:0 auto 8px;display:block;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                                Aucune commande pour le moment
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
