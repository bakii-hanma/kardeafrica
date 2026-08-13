@extends('admin.layouts.admin')

@section('title', 'Cartes')
@section('page-title', 'Cartes utilisateurs')

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
        for ($i = 0; $i < strlen($name); $i++) $hash = (ord($name[$i]) + (($hash << 5) - $hash)) & 0x7FFFFFFF;
        $idx = (($hash % count($palette)) + count($palette)) % count($palette);
        return $palette[$idx];
    };

    $activeFilters = (int)!empty(request('search')) + (int)!empty(request('status'))
        + (int)!empty(request('brand')) + (int)!empty(request('date_from')) + (int)!empty(request('date_to'))
        + (int)!empty(request('user_id'));
@endphp

@section('content')
<div style="padding:24px;font-family:'Inter','Figtree',sans-serif;">

    {{-- ===== Banner: filtré par client ===== --}}
    @if(isset($filteredUser) && $filteredUser)
        <div style="background:linear-gradient(135deg,var(--teal-soft),var(--teal-soft));border:1px solid var(--teal-light);border-radius:14px;padding:14px 16px;margin-bottom:18px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--teal),var(--teal-light));color:var(--surface);display:flex;align-items:center;justify-content:center;font-family:'Space Grotesk','Inter',sans-serif;font-weight:800;font-size:16px;flex-shrink:0;">
                {{ strtoupper(substr($filteredUser->name, 0, 1)) }}
            </div>
            <div style="flex:1;min-width:200px;">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.10em;color:var(--teal);">Filtré par client</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:16px;font-weight:700;color:var(--text);margin-top:2px;">{{ $filteredUser->name }}</div>
                <div style="font-size:12px;color:var(--text-muted);font-family:monospace;">{{ $filteredUser->email }}</div>
            </div>
            <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                <div style="text-align:right;">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.10em;color:var(--text-faint);">Cartes affichées</div>
                    <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:800;color:var(--text);font-variant-numeric:tabular-nums;line-height:1.05;">{{ $cards->total() }}</div>
                </div>
                <a href="{{ route('admin.users.show', $filteredUser) }}"
                   style="display:inline-flex;align-items:center;gap:6px;padding:9px 14px;border-radius:10px;background:var(--surface);border:1px solid var(--teal-light);color:var(--teal);font-size:13px;font-weight:700;text-decoration:none;transition:all .15s;"
                   onmouseover="this.style.background='#047857';this.style.color='white';this.style.borderColor='#047857';"
                   onmouseout="this.style.background='white';this.style.color='#047857';this.style.borderColor='#6EE7B7';">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Profil client
                </a>
                <a href="{{ route('admin.cards.index') }}"
                   style="display:inline-flex;align-items:center;gap:6px;padding:9px 12px;border-radius:10px;background:rgba(15,23,42,0.05);color:var(--text-muted);font-size:12px;font-weight:600;text-decoration:none;"
                   title="Retirer le filtre">
                    <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    Toutes les cartes
                </a>
            </div>
        </div>
    @endif

    {{-- ===== Stuck orders banner (paid but no cards) ===== --}}
    @if(isset($stuckOrders) && $stuckOrders->count() > 0)
        <div style="background:linear-gradient(135deg,rgb(245 158 11 / .14),var(--chip-orange));border:1px solid #FBBF24;border-radius:14px;padding:16px;margin-bottom:18px;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                <div style="width:36px;height:36px;border-radius:10px;background:var(--chip-orange);color:var(--surface);display:flex;align-items:center;justify-content:center;">
                    <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div style="flex:1;">
                    <div style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:14px;font-weight:700;color:#78350F;">
                        {{ $stuckOrders->count() }} commande{{ $stuckOrders->count() > 1 ? 's' : '' }} payée{{ $stuckOrders->count() > 1 ? 's' : '' }} sans cartes livrées
                    </div>
                    <div style="font-size:12px;color:var(--chip-orange);margin-top:2px;">L'API afrikard a échoué lors de ces paiements. Relancer la livraison ci-dessous.</div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:8px;">
                @foreach($stuckOrders as $stuckOrder)
                    <div style="background:var(--surface);border:1px solid #FCD34D;border-radius:10px;padding:10px;display:flex;align-items:center;gap:10px;">
                        <div style="flex:1;min-width:0;">
                            <div style="font-family:monospace;font-size:11px;font-weight:700;color:var(--text);">#{{ $stuckOrder->order_number }}</div>
                            <div style="font-size:10px;color:var(--text-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                {{ $stuckOrder->user?->name }} · {{ $stuckOrder->orderItems->count() }} article{{ $stuckOrder->orderItems->count() > 1 ? 's' : '' }} · {{ number_format($stuckOrder->total_amount, 0, ',', ' ') }} FCFA
                            </div>
                        </div>
                        <form action="{{ route('admin.orders.retry', $stuckOrder) }}" method="POST" style="margin:0;">
                            @csrf
                            <button type="submit"
                                    style="display:inline-flex;align-items:center;gap:4px;padding:6px 10px;background:var(--chip-orange);color:var(--surface);border:none;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;"
                                    onmouseover="this.style.background='#D97706';"
                                    onmouseout="this.style.background='#F59E0B';">
                                <svg style="width:11px;height:11px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Relancer
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ===== Stats ===== --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:14px;margin-bottom:18px;">
        @foreach ([
            ['label' => 'Total cartes',   'value' => number_format($totalCards),                 'unit' => '',     'color' => '#0F172A', 'bg' => '#F1F5F9', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
            ['label' => 'Actives',        'value' => number_format($activeCards),                'unit' => '',     'color' => '#059669', 'bg' => '#ECFDF5', 'icon' => 'M5 13l4 4L19 7'],
            ['label' => 'Utilisées',      'value' => number_format($usedCards),                  'unit' => '',     'color' => '#64748B', 'bg' => '#F1F5F9', 'icon' => 'M9 12l2 2 4-4'],
            ['label' => 'Expirées',       'value' => number_format($expiredCards),               'unit' => '',     'color' => '#DC2626', 'bg' => '#FEF2F2', 'icon' => 'M6 18L18 6M6 6l12 12'],
            ['label' => 'Revenu cumulé',  'value' => number_format($totalRevenue, 0, ',', ' '),  'unit' => 'FCFA', 'color' => '#44A08D', 'bg' => '#ECFDF5', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'],
        ] as $card)
            <div style="background:var(--surface);border-radius:14px;padding:16px;border:1px solid var(--border);box-shadow:0 1px 2px rgba(15,23,42,0.04);">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:6px;">
                    <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--text-muted);">{{ $card['label'] }}</span>
                    <div style="width:32px;height:32px;border-radius:9px;background:{{ $card['bg'] }};color:{{ $card['color'] }};display:flex;align-items:center;justify-content:center;">
                        <svg style="width:15px;height:15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}"/></svg>
                    </div>
                </div>
                <div style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:22px;font-weight:800;color:var(--text);line-height:1.1;letter-spacing:-0.01em;">
                    {{ $card['value'] }}
                    @if(!empty($card['unit']))
                        <span style="font-size:11px;font-weight:500;color:var(--text-faint);">{{ $card['unit'] }}</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===== Filtres ===== --}}
    <form method="GET" action="{{ route('admin.cards.index') }}"
          style="background:var(--surface);border-radius:14px;padding:12px;border:1px solid var(--border);box-shadow:0 1px 2px rgba(15,23,42,0.04);margin-bottom:18px;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:8px;align-items:center;">
        <div style="position:relative;grid-column:span 2;min-width:200px;">
            <svg style="position:absolute;left:14px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:var(--text-faint);" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Email, nom client, marque, serial..."
                   style="width:100%;padding:10px 14px 10px 40px;background:var(--surface-inset);border:1px solid var(--border);border-radius:10px;font-size:14px;outline:none;">
        </div>
        <select name="status" onchange="this.form.submit()"
                style="padding:10px 14px;background:var(--surface-inset);border:1px solid var(--border);border-radius:10px;font-size:13px;font-weight:500;color:var(--text);outline:none;cursor:pointer;">
            <option value="">Tous statuts</option>
            <option value="active"  {{ request('status') === 'active'  ? 'selected' : '' }}>Actives</option>
            <option value="used"    {{ request('status') === 'used'    ? 'selected' : '' }}>Utilisées</option>
            <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expirées</option>
        </select>
        <input type="text" name="brand" value="{{ request('brand') }}" placeholder="Marque…"
               style="padding:10px 14px;background:var(--surface-inset);border:1px solid var(--border);border-radius:10px;font-size:13px;outline:none;">
        <input type="date" name="date_from" value="{{ request('date_from') }}"
               style="padding:10px 14px;background:var(--surface-inset);border:1px solid var(--border);border-radius:10px;font-size:13px;color:var(--text);outline:none;">
        <input type="date" name="date_to" value="{{ request('date_to') }}"
               style="padding:10px 14px;background:var(--surface-inset);border:1px solid var(--border);border-radius:10px;font-size:13px;color:var(--text);outline:none;">
        <div style="display:flex;gap:6px;">
            <button type="submit" style="flex:1;padding:10px 18px;background:var(--teal);color:var(--surface);border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;">Filtrer</button>
            @if($activeFilters > 0)
                <a href="{{ route('admin.cards.index') }}" style="padding:10px 14px;color:var(--text-muted);font-size:12px;font-weight:600;text-decoration:none;background:var(--surface-inset);border-radius:10px;display:inline-flex;align-items:center;">✕</a>
            @endif
        </div>
    </form>

    {{-- ===== Grille cartes ===== --}}
    @if($cards->count() > 0)
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px;">
            @foreach($cards as $i => $card)
                @php
                    $cardCode  = $card->card_code ?? '';
                    $cardPin   = $card->pin;
                    $brandColor= $brandColorFor($card->brand ?? $card->name ?? '');
                    $pricePaid = (float) ($card->orderItem?->unit_price ?? 0);
                    $statusBadge = match($card->status) {
                        'active'  => ['label' => 'Active',   'cls' => 'bg-emerald-500/30', 'dot' => true],
                        'used'    => ['label' => 'Utilisée', 'cls' => 'bg-slate-500/40',   'dot' => false],
                        'expired' => ['label' => 'Expirée',  'cls' => 'bg-rose-500/40',    'dot' => false],
                        default   => ['label' => ucfirst($card->status ?? '—'), 'cls' => 'bg-white/20', 'dot' => false],
                    };
                @endphp

                <article x-data="{ codeShown: false, pinShown: false, copied: null,
                                   copy(text, field) { navigator.clipboard.writeText(text).then(() => { this.copied = field; setTimeout(() => this.copied = null, 1500); }); } }"
                         style="background:var(--surface);border-radius:16px;border:1px solid var(--border);overflow:hidden;box-shadow:0 1px 2px rgba(15,23,42,0.04);transition:all 0.2s ease;"
                         onmouseover="this.style.boxShadow='0 8px 20px rgba(15,23,42,0.08)';this.style.transform='translateY(-2px)';"
                         onmouseout="this.style.boxShadow='0 1px 2px rgba(15,23,42,0.04)';this.style.transform='none';">

                    {{-- Visuel carte --}}
                    <div style="background-color:{{ $brandColor }};padding:14px;position:relative;overflow:hidden;height:140px;">
                        <svg style="position:absolute;inset:0;width:100%;height:100%;opacity:0.08;pointer-events:none;" aria-hidden="true">
                            <defs>
                                <pattern id="ac-{{ $card->id }}" width="32" height="32" patternUnits="userSpaceOnUse">
                                    <circle cx="16" cy="16" r="13" fill="none" stroke="white" stroke-width="1.2"/>
                                </pattern>
                            </defs>
                            <rect width="100%" height="100%" fill="url(#ac-{{ $card->id }})"/>
                        </svg>
                        <div style="position:absolute;top:-30px;right:-30px;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,0.15);filter:blur(20px);"></div>

                        <div style="position:relative;z-index:1;display:flex;flex-direction:column;height:100%;justify-content:space-between;">
                            <div style="display:flex;align-items:flex-start;justify-content:space-between;">
                                <div style="min-width:0;flex:1;">
                                    <div style="color:rgba(255,255,255,0.75);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.18em;">Gift Card</div>
                                    <div style="font-family:'Space Grotesk', 'Inter', sans-serif;color:var(--surface);font-size:22px;font-weight:700;line-height:1.05;letter-spacing:-0.02em;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        {{ $card->brand ?: explode(' ', $card->name)[0] }}
                                    </div>
                                </div>
                                <div class="{{ $statusBadge['cls'] }}" style="padding:3px 8px;border-radius:9999px;font-size:10px;font-weight:700;color:var(--surface);display:inline-flex;align-items:center;gap:4px;backdrop-filter:blur(4px);margin-left:8px;flex-shrink:0;">
                                    @if($statusBadge['dot'])
                                        <span style="width:5px;height:5px;border-radius:50%;background:#86EFAC;"></span>
                                    @endif
                                    {{ $statusBadge['label'] }}
                                </div>
                            </div>

                            <div style="display:flex;align-items:flex-end;justify-content:space-between;">
                                <div>
                                    <div style="color:rgba(255,255,255,0.6);font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;">Prix payé</div>
                                    <div style="font-family:'Space Grotesk', 'Inter', sans-serif;color:var(--surface);font-size:18px;font-weight:700;font-variant-numeric:tabular-nums;margin-top:1px;">
                                        {{ number_format($pricePaid, 0, ',', ' ') }} <span style="font-size:11px;font-weight:400;color:rgba(255,255,255,0.7);">FCFA</span>
                                    </div>
                                    @if($card->face_value && $card->currency !== 'XAF')
                                        <div style="color:rgba(255,255,255,0.6);font-size:10px;margin-top:2px;">{{ number_format($card->face_value, 0, ',', ' ') }} {{ $card->currency }} de crédit</div>
                                    @endif
                                </div>
                                <div style="width:36px;height:24px;border-radius:4px;background:linear-gradient(135deg,rgba(254,240,138,0.9),rgba(250,204,21,0.7));border:1px solid rgba(255,255,255,0.3);"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Body --}}
                    <div style="padding:14px;">
                        @if($card->user)
                            @php
                                $userCardsCount = isset($filteredUser) && $filteredUser?->id === $card->user_id
                                    ? null
                                    : ($userCardCounts[$card->user_id] ?? 0);
                            @endphp

                            <div style="margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid var(--surface-inset);">
                                {{-- Ligne 1 : Avatar + nom + email + #commande --}}
                                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                                    <div style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,var(--teal-light),var(--teal));display:flex;align-items:center;justify-content:center;color:var(--surface);font-weight:700;font-size:12px;flex-shrink:0;box-shadow:0 4px 8px -2px rgba(68,160,141,0.35);">
                                        {{ strtoupper(substr($card->user->name, 0, 1)) }}
                                    </div>
                                    <div style="flex:1;min-width:0;">
                                        <div style="font-size:13px;font-weight:700;color:var(--text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $card->user->name }}</div>
                                        <div style="font-size:10px;color:var(--text-muted);font-family:monospace;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $card->user->email }}</div>
                                    </div>
                                    @if($card->order)
                                        <a href="{{ route('admin.orders.show', $card->order_id) }}"
                                           style="font-family:monospace;font-size:10px;color:var(--teal);text-decoration:none;background:var(--teal-soft);padding:4px 7px;border-radius:6px;font-weight:700;flex-shrink:0;border:1px solid var(--teal-soft);"
                                           title="Voir la commande"
                                           onmouseover="this.style.background='#44A08D';this.style.color='white';"
                                           onmouseout="this.style.background='#ECFDF5';this.style.color='#44A08D';">
                                            #{{ Str::limit($card->order->order_number ?? $card->order_id, 12, '') }}
                                        </a>
                                    @endif
                                </div>

                                {{-- Ligne 2 : 2 boutons d'action client --}}
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
                                    <a href="{{ route('admin.users.show', $card->user) }}"
                                       style="display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:6px 10px;border-radius:8px;background:var(--surface-inset);border:1px solid var(--border);color:var(--text-muted);font-size:11px;font-weight:700;text-decoration:none;transition:all .15s;"
                                       onmouseover="this.style.background='#0F172A';this.style.color='white';this.style.borderColor='#0F172A';"
                                       onmouseout="this.style.background='#F8FAFC';this.style.color='#475569';this.style.borderColor='#E2E8F0';"
                                       title="Voir la fiche du client">
                                        <svg style="width:11px;height:11px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        Profil client
                                    </a>
                                    @if($userCardsCount !== null)
                                        <a href="{{ route('admin.cards.index', ['user_id' => $card->user_id]) }}"
                                           style="display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:6px 10px;border-radius:8px;background:rgba(68,160,141,0.10);border:1px solid rgba(68,160,141,0.30);color:var(--teal);font-size:11px;font-weight:700;text-decoration:none;transition:all .15s;"
                                           onmouseover="this.style.background='#44A08D';this.style.color='white';this.style.borderColor='#44A08D';"
                                           onmouseout="this.style.background='rgba(68,160,141,0.10)';this.style.color='#44A08D';this.style.borderColor='rgba(68,160,141,0.30)';"
                                           title="Voir les autres cartes de ce client">
                                            <svg style="width:11px;height:11px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                            Ses cartes
                                            <span style="background:rgba(255,255,255,0.4);padding:1px 5px;border-radius:9999px;font-size:9px;font-weight:800;">{{ $userCardsCount }}</span>
                                        </a>
                                    @else
                                        <span style="display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:6px 10px;border-radius:8px;background:var(--surface-inset);color:var(--text-faint);font-size:11px;font-weight:700;">
                                            <svg style="width:11px;height:11px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            Filtre actif
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:10px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $card->name }}
                        </div>

                        {{-- Code --}}
                        <div style="background:var(--surface-inset);border:1px solid var(--border);border-radius:9px;padding:8px 10px;margin-bottom:8px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                                <span style="font-size:9px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:var(--text-faint);">Code</span>
                                <div style="display:flex;align-items:center;gap:2px;">
                                    <button type="button" @click="codeShown = !codeShown" style="background:none;border:none;cursor:pointer;padding:3px;color:var(--text-faint);border-radius:4px;">
                                        <svg x-show="!codeShown" style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <svg x-show="codeShown" x-cloak style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                    </button>
                                    <button type="button" @click="copy('{{ $cardCode }}', 'code')" style="background:none;border:none;cursor:pointer;padding:3px;color:var(--text-faint);border-radius:4px;">
                                        <svg x-show="copied !== 'code'" style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        <svg x-show="copied === 'code'" x-cloak style="width:12px;height:12px;color:var(--teal);" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div style="font-family:monospace;font-size:11px;font-weight:700;color:var(--text);letter-spacing:0.5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                 x-text="codeShown ? '{{ $cardCode }}' : '{{ str_repeat('•', max(8, strlen($cardCode))) }}'"></div>
                        </div>

                        <div style="display:grid;grid-template-columns:{{ $cardPin ? '1fr 1fr' : '1fr' }};gap:6px;">
                            @if($cardPin)
                                <div style="background:var(--surface-inset);border:1px solid var(--border);border-radius:9px;padding:8px 10px;">
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                                        <span style="font-size:9px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:var(--text-faint);">PIN</span>
                                        <button type="button" @click="pinShown = !pinShown" style="background:none;border:none;cursor:pointer;padding:2px;color:var(--text-faint);">
                                            <svg x-show="!pinShown" style="width:10px;height:10px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <svg x-show="pinShown" x-cloak style="width:10px;height:10px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                        </button>
                                    </div>
                                    <div style="font-family:monospace;font-size:11px;font-weight:700;color:var(--text);font-variant-numeric:tabular-nums;"
                                         x-text="pinShown ? '{{ $cardPin }}' : '{{ str_repeat('•', strlen($cardPin)) }}'"></div>
                                </div>
                            @endif
                            @if($card->expiration_date)
                                <div style="background:var(--surface-inset);border:1px solid var(--border);border-radius:9px;padding:8px 10px;">
                                    <div style="font-size:9px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:var(--text-faint);margin-bottom:4px;">Expire</div>
                                    <div style="font-size:11px;font-weight:700;color:var(--text);font-variant-numeric:tabular-nums;">
                                        {{ \Carbon\Carbon::parse($card->expiration_date)->format('d/m/Y') }}
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:10px;padding-top:10px;border-top:1px solid var(--surface-inset);">
                            <span style="font-size:10px;color:var(--text-faint);">
                                {{ $card->created_at->format('d/m/Y') }}
                                @if($card->serial_number)
                                    · <span style="font-family:monospace;color:var(--text-faint);">{{ Str::limit($card->serial_number, 12, '…') }}</span>
                                @endif
                            </span>
                            <button type="button" @click="copy('{{ $cardCode }}', 'btn')"
                                    style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:var(--teal);color:var(--surface);border:none;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;">
                                <svg x-show="copied !== 'btn'" style="width:11px;height:11px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                <svg x-show="copied === 'btn'" x-cloak style="width:11px;height:11px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span x-show="copied !== 'btn'">Copier</span>
                                <span x-show="copied === 'btn'" x-cloak>Copié !</span>
                            </button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        @if($cards->hasPages())
            <div style="margin-top:24px;">{{ $cards->links() }}</div>
        @endif
    @else
        <div style="background:var(--surface);border-radius:14px;border:1px solid var(--border);padding:60px 40px;text-align:center;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="width:60px;height:60px;border-radius:14px;background:var(--surface-inset);margin:0 auto 14px;display:flex;align-items:center;justify-content:center;">
                <svg style="width:26px;height:26px;color:var(--border);" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <div style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:16px;font-weight:700;color:var(--text);margin-bottom:4px;">Aucune carte trouvée</div>
            <div style="font-size:13px;color:var(--text-muted);">{{ $activeFilters > 0 ? 'Essayez de modifier vos filtres' : 'Aucune carte n\'a encore été délivrée.' }}</div>
        </div>
    @endif
</div>
@endsection
