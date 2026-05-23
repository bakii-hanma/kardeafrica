@extends('layouts.app')

@section('title', 'Mon profil — KardAfrica')

@php
    $brandPalette = [
        'Netflix' => '#E50914', 'Spotify' => '#1DB954', 'Apple' => '#000000',
        'iTunes' => '#D60017', 'PlayStation' => '#003791', 'Xbox' => '#107C10',
        'Amazon' => '#FF9900', 'Google' => '#01875F', 'Steam' => '#171A21',
        'Roblox' => '#00A2FF', 'Nintendo' => '#E60012', 'Disney' => '#0E47A1',
        'Daywatch' => '#44A08D',
    ];
    $brandColorFor = function ($name) use ($brandPalette) {
        if (!$name) return '#0F172A';
        foreach ($brandPalette as $k => $c) if (stripos($name, $k) !== false) return $c;
        $palette = ['#0F172A', '#44A08D', '#0EA5E9', '#7C3AED', '#DC2626', '#EA580C', '#059669'];
        $hash = 0;
        for ($i = 0; $i < strlen($name); $i++) $hash = ord($name[$i]) + (($hash << 5) - $hash);
        return $palette[(($hash % count($palette)) + count($palette)) % count($palette)];
    };

    $statusInfo = [
        'pending'    => ['label' => 'En attente',    'bg' => '#FEF3C7', 'text' => '#B45309'],
        'processing' => ['label' => 'En cours',      'bg' => '#E0F2FE', 'text' => '#0369A1'],
        'completed'  => ['label' => 'Terminée',      'bg' => '#D1FAE5', 'text' => '#047857'],
        'failed'     => ['label' => 'Échouée',       'bg' => '#FFE4E6', 'text' => '#BE123C'],
        'cancelled'  => ['label' => 'Annulée',       'bg' => '#FFE4E6', 'text' => '#BE123C'],
    ];
@endphp

@section('content')
<style>
    @keyframes ka-profile-pulse { 0%,100% { transform: scale(1); opacity: 0.6; } 50% { transform: scale(1.05); opacity: 1; } }
    .ka-profile-dot { animation: ka-profile-pulse 2.4s ease-in-out infinite; }
    .ka-stat-card { transition: transform .2s ease, box-shadow .2s ease; }
    .ka-stat-card:hover { transform: translateY(-2px); box-shadow: 0 14px 30px -12px rgba(15,23,42,0.12); }
</style>

<div style="background:#FAFAF7;min-height:100vh;font-family:'Inter','Figtree',sans-serif;">

    {{-- ============================================================
         HERO sombre premium
       ============================================================ --}}
    <section style="position:relative;overflow:hidden;
                    background:
                      radial-gradient(circle at 18% 0%, rgba(78,205,196,0.22) 0%, transparent 45%),
                      radial-gradient(circle at 82% 100%, rgba(124,58,237,0.18) 0%, transparent 45%),
                      linear-gradient(135deg, #060A14 0%, #0F172A 50%, #1E293B 100%);
                    padding: 32px 16px 80px;">

        {{-- Grid pattern overlay --}}
        <div style="position:absolute;inset:0;pointer-events:none;
                    background-image: linear-gradient(to right, rgba(255,255,255,0.04) 1px, transparent 1px),
                                      linear-gradient(to bottom, rgba(255,255,255,0.04) 1px, transparent 1px);
                    background-size: 48px 48px;
                    -webkit-mask-image: radial-gradient(ellipse 80% 60% at 50% 30%, black 30%, transparent 100%);
                            mask-image: radial-gradient(ellipse 80% 60% at 50% 30%, black 30%, transparent 100%);"></div>

        <div style="position:relative;max-width:1200px;margin:0 auto;">
            {{-- Breadcrumb mini --}}
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;">
                <a href="{{ route('home') }}" style="font-size:11px;color:#94A3B8;text-decoration:none;">Accueil</a>
                <span style="color:#64748B;font-size:11px;">/</span>
                <span style="font-size:11px;font-weight:700;color:#5EEAD4;letter-spacing:0.12em;text-transform:uppercase;">Mon profil</span>
            </div>

            {{-- Profile header : avatar + identity + actions --}}
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:24px;">
                {{-- Avatar avec halo --}}
                <div style="position:relative;flex-shrink:0;">
                    {{-- Halo flouté --}}
                    <div style="position:absolute;inset:-8px;border-radius:50%;
                                background:radial-gradient(circle, rgba(78,205,196,0.45) 0%, transparent 70%);
                                filter:blur(20px);"></div>

                    <div style="position:relative;width:120px;height:120px;border-radius:50%;
                                border:4px solid rgba(255,255,255,0.12);
                                background:linear-gradient(135deg, #44A08D, #4ECDC4);
                                display:flex;align-items:center;justify-content:center;
                                overflow:hidden;
                                box-shadow:0 20px 40px -10px rgba(78,205,196,0.5);">
                        @if(optional($user->profile)->avatar)
                            <img src="{{ $user->profile->avatar }}" alt="{{ $user->name }}"
                                 style="width:100%;height:100%;object-fit:cover;">
                        @else
                            <span style="font-family:'Space Grotesk','Inter',sans-serif;font-size:48px;font-weight:800;color:white;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </span>
                        @endif
                    </div>

                    {{-- Camera button --}}
                    <form action="{{ route('profile.avatar.update') }}" method="POST" enctype="multipart/form-data" id="avatar-form" style="display:none;">
                        @csrf @method('PUT')
                        <input type="file" name="avatar" id="avatar-upload" accept="image/*" onchange="document.getElementById('avatar-form').submit()">
                    </form>
                    <label for="avatar-upload"
                           style="position:absolute;bottom:0;right:0;
                                  width:36px;height:36px;border-radius:50%;
                                  background:white;border:3px solid #0F172A;
                                  display:flex;align-items:center;justify-content:center;
                                  cursor:pointer;
                                  box-shadow:0 8px 16px -4px rgba(0,0,0,0.4);">
                        <svg style="width:16px;height:16px;color:#44A08D;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </label>
                </div>

                {{-- Identity --}}
                <div style="flex:1;min-width:240px;">
                    <h1 style="font-family:'Space Grotesk','Inter',sans-serif;font-size:clamp(24px, 4vw, 36px);font-weight:800;color:white;letter-spacing:-0.02em;line-height:1.1;margin:0;">
                        {{ $user->name }}
                    </h1>
                    <div style="display:flex;align-items:center;gap:6px;margin-top:6px;">
                        <svg style="width:14px;height:14px;color:#94A3B8;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span style="color:#CBD5E1;font-size:14px;font-family:monospace;">{{ $user->email }}</span>
                    </div>

                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:14px;">
                        {{-- Compte verifié --}}
                        <span style="display:inline-flex;align-items:center;gap:5px;
                                     padding:4px 10px;border-radius:9999px;
                                     background:rgba(16,185,129,0.15);
                                     border:1px solid rgba(16,185,129,0.30);
                                     color:#6EE7B7;font-size:11px;font-weight:700;">
                            <svg style="width:11px;height:11px;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            Compte vérifié
                        </span>
                        {{-- Membre depuis --}}
                        <span style="display:inline-flex;align-items:center;gap:5px;
                                     padding:4px 10px;border-radius:9999px;
                                     background:rgba(255,255,255,0.06);
                                     border:1px solid rgba(255,255,255,0.10);
                                     color:#CBD5E1;font-size:11px;font-weight:600;">
                            <svg style="width:11px;height:11px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Membre depuis {{ $user->created_at->isoFormat('MMMM Y') }}
                        </span>
                        {{-- Rôle si admin --}}
                        @if(method_exists($user, 'isAdmin') && $user->isAdmin())
                            <span style="display:inline-flex;align-items:center;gap:5px;
                                         padding:4px 10px;border-radius:9999px;
                                         background:rgba(124,58,237,0.18);
                                         border:1px solid rgba(124,58,237,0.35);
                                         color:#A78BFA;font-size:11px;font-weight:700;">
                                <span class="ka-profile-dot" style="width:5px;height:5px;border-radius:50%;background:#A78BFA;"></span>
                                Administrateur
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    <a href="{{ route('cards.index') }}"
                       style="display:inline-flex;align-items:center;gap:8px;
                              padding:12px 20px;border-radius:12px;
                              background:rgba(255,255,255,0.06);
                              border:1px solid rgba(255,255,255,0.15);
                              color:white;font-weight:600;font-size:14px;text-decoration:none;
                              backdrop-filter:blur(10px);"
                       onmouseover="this.style.background='rgba(255,255,255,0.10)';"
                       onmouseout="this.style.background='rgba(255,255,255,0.06)';">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        Mes cartes
                    </a>
                    {{-- Lien Kara — ne s'affiche que si la route est déployée
                         (évite un Route::has() crash si la migration de routes n'a
                         pas encore été pushée en prod) --}}
                    @if(Route::has('profile.assistant'))
                        <a href="{{ route('profile.assistant') }}"
                           style="display:inline-flex;align-items:center;gap:8px;
                                  padding:12px 20px;border-radius:12px;
                                  background:rgba(255,255,255,0.06);
                                  border:1px solid rgba(78,205,196,0.30);
                                  color:white;font-weight:600;font-size:14px;text-decoration:none;
                                  backdrop-filter:blur(10px);position:relative;"
                           onmouseover="this.style.background='rgba(78,205,196,0.12)';"
                           onmouseout="this.style.background='rgba(255,255,255,0.06)';">
                            <svg style="width:14px;height:14px;color:#5EEAD4;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/></svg>
                            Kara · Assistante IA
                            <span style="width:6px;height:6px;border-radius:50%;background:#34D399;box-shadow:0 0 0 0 rgba(52,211,153,0.6);"></span>
                        </a>
                    @endif
                    <a href="{{ route('profile.edit') }}"
                       style="display:inline-flex;align-items:center;gap:8px;
                              padding:12px 22px;border-radius:12px;
                              background:linear-gradient(135deg, #44A08D 0%, #4ECDC4 100%);
                              color:#0F172A;font-weight:700;font-size:14px;text-decoration:none;
                              box-shadow:0 14px 30px -10px rgba(78,205,196,0.55), inset 0 1px 0 rgba(255,255,255,0.45);">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Modifier le profil
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================
         CONTENU
       ============================================================ --}}
    <section style="max-width:1200px;margin:-56px auto 0;padding:0 16px 80px;position:relative;">

        {{-- ===== Stats grid (4 cards flottantes) ===== --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:24px;">
            @foreach ([
                ['label' => 'Cartes totales',   'value' => $stats['total_cards'],     'unit' => '', 'color' => '#0F172A', 'iconBg' => '#F1F5F9', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                ['label' => 'Cartes actives',   'value' => $stats['active_cards'],    'unit' => '', 'color' => '#10B981', 'iconBg' => '#D1FAE5', 'icon' => 'M5 13l4 4L19 7'],
                ['label' => 'Commandes',        'value' => $stats['total_orders'],    'unit' => '', 'color' => '#3B82F6', 'iconBg' => '#DBEAFE', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                ['label' => 'Total dépensé',    'value' => number_format($stats['total_spent'], 0, ',', ' '),  'unit' => 'FCFA', 'color' => '#44A08D', 'iconBg' => '#D1FAE5', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ] as $card)
                <div class="ka-stat-card" style="background:white;border:1px solid #E2E8F0;border-radius:18px;padding:18px;box-shadow:0 4px 12px -4px rgba(15,23,42,0.06);">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px;">
                        <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.10em;color:#64748B;">{{ $card['label'] }}</span>
                        <div style="width:36px;height:36px;border-radius:11px;background:{{ $card['iconBg'] }};color:{{ $card['color'] }};display:flex;align-items:center;justify-content:center;">
                            <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}"/></svg>
                        </div>
                    </div>
                    <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:24px;font-weight:800;color:#0F172A;line-height:1.05;letter-spacing:-0.01em;font-variant-numeric:tabular-nums;">
                        {{ $card['value'] }}
                        @if(!empty($card['unit']))
                            <span style="font-size:11px;font-weight:600;color:#94A3B8;">{{ $card['unit'] }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ===== Layout 2 colonnes : main + sidebar ===== --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px;align-items:start;">

            {{-- ============= MAIN ============= --}}
            <div style="display:flex;flex-direction:column;gap:18px;grid-column:span 2;min-width:0;"
                 class="profile-main-col">

                {{-- Informations personnelles --}}
                <div style="background:white;border:1px solid #E2E8F0;border-radius:18px;padding:24px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
                        <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#DBEAFE,#BFDBFE);color:#1D4ED8;display:flex;align-items:center;justify-content:center;">
                            <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div>
                            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.10em;color:#3B82F6;">Identité</div>
                            <h2 style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:700;color:#0F172A;margin:2px 0 0;">Informations personnelles</h2>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
                        @foreach ([
                            ['label' => 'Nom complet', 'value' => $user->name,                            'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                            ['label' => 'E-mail',      'value' => $user->email,                           'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                            ['label' => 'Téléphone',   'value' => $user->phone ?: 'Non renseigné',        'icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'],
                            ['label' => 'Pays',        'value' => $user->country ?: 'Non renseigné',      'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z'],
                        ] as $field)
                            @php $isEmpty = str_contains(strtolower($field['value']), 'non renseigné'); @endphp
                            <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:12px;padding:12px 14px;">
                                <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                                    <svg style="width:12px;height:12px;color:#94A3B8;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $field['icon'] }}"/></svg>
                                    <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94A3B8;">{{ $field['label'] }}</span>
                                </div>
                                <div style="font-size:14px;font-weight:600;color:{{ $isEmpty ? '#94A3B8' : '#0F172A' }};{{ $field['label'] === 'E-mail' || $field['label'] === 'Téléphone' ? 'font-family:monospace;' : '' }}">
                                    {{ $field['value'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Activité récente --}}
                @if($recentOrders->count() > 0)
                    <div style="background:white;border:1px solid #E2E8F0;border-radius:18px;padding:24px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:8px;">
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#FEF3C7,#FDE68A);color:#B45309;display:flex;align-items:center;justify-content:center;">
                                    <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </div>
                                <div>
                                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.10em;color:#B45309;">Activité</div>
                                    <h2 style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:700;color:#0F172A;margin:2px 0 0;">Commandes récentes</h2>
                                </div>
                            </div>
                            <a href="{{ url('/orders') }}" style="font-size:12px;font-weight:700;color:#44A08D;text-decoration:none;">Tout voir →</a>
                        </div>

                        <div style="display:flex;flex-direction:column;gap:8px;">
                            @foreach($recentOrders as $order)
                                @php
                                    $sInfo = $statusInfo[$order->status] ?? ['label' => $order->status, 'bg' => '#F1F5F9', 'text' => '#475569'];
                                    $firstItem = $order->orderItems->first();
                                    $itemColor = $brandColorFor($firstItem?->name ?? '');
                                @endphp
                                <a href="{{ url('/orders/'.$order->id) }}"
                                   style="display:flex;align-items:center;gap:12px;
                                          padding:12px;border-radius:12px;
                                          background:#F8FAFC;border:1px solid #E2E8F0;
                                          text-decoration:none;color:inherit;
                                          transition:all .15s;"
                                   onmouseover="this.style.background='white';this.style.borderColor='#CBD5E1';this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 12px -4px rgba(15,23,42,0.06)';"
                                   onmouseout="this.style.background='#F8FAFC';this.style.borderColor='#E2E8F0';this.style.transform='none';this.style.boxShadow='none';">
                                    {{-- Mini-card brand --}}
                                    <div style="width:42px;height:42px;border-radius:11px;background:{{ $itemColor }};display:flex;align-items:center;justify-content:center;color:white;font-family:'Space Grotesk','Inter',sans-serif;font-weight:800;font-size:16px;flex-shrink:0;position:relative;overflow:hidden;">
                                        <div style="position:absolute;top:-10px;right:-10px;width:32px;height:32px;border-radius:16px;background:rgba(255,255,255,0.18);"></div>
                                        <span style="position:relative;">{{ strtoupper(substr($firstItem?->name ?? '?', 0, 1)) }}</span>
                                    </div>
                                    <div style="flex:1;min-width:0;">
                                        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                            <span style="font-family:monospace;font-size:12px;font-weight:700;color:#0F172A;">#{{ $order->order_number }}</span>
                                            <span style="font-size:10px;color:#94A3B8;">·</span>
                                            <span style="font-size:11px;color:#64748B;">{{ $order->created_at->diffForHumans() }}</span>
                                        </div>
                                        @if($firstItem)
                                            <div style="font-size:12px;color:#475569;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                                {{ $firstItem->name }}{{ $order->orderItems->count() > 1 ? ' + ' . ($order->orderItems->count() - 1) . ' autre' . ($order->orderItems->count() > 2 ? 's' : '') : '' }}
                                            </div>
                                        @endif
                                    </div>
                                    <div style="text-align:right;flex-shrink:0;">
                                        <span style="display:inline-block;padding:3px 8px;border-radius:9999px;font-size:10px;font-weight:700;background:{{ $sInfo['bg'] }};color:{{ $sInfo['text'] }};">{{ $sInfo['label'] }}</span>
                                        <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:14px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;margin-top:4px;">
                                            {{ number_format($order->total_amount, 0, ',', ' ') }}
                                            <span style="font-size:10px;font-weight:500;color:#94A3B8;">FCFA</span>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Sécurité --}}
                <div style="background:white;border:1px solid #E2E8F0;border-radius:18px;padding:24px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
                        <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#FEE2E2,#FECACA);color:#BE123C;display:flex;align-items:center;justify-content:center;">
                            <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <div>
                            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.10em;color:#BE123C;">Sécurité</div>
                            <h2 style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:700;color:#0F172A;margin:2px 0 0;">Compte & confidentialité</h2>
                        </div>
                    </div>

                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:12px;flex-wrap:wrap;">
                        <div style="display:flex;align-items:center;gap:12px;flex:1;min-width:240px;">
                            <div style="width:36px;height:36px;border-radius:10px;background:white;border:1px solid #E2E8F0;color:#475569;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:700;color:#0F172A;">Mot de passe</div>
                                <div style="font-size:12px;color:#64748B;">Modifie ton mot de passe pour sécuriser ton compte</div>
                            </div>
                        </div>
                        <a href="{{ route('profile.edit') }}"
                           style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:10px;background:#0F172A;color:white;font-size:13px;font-weight:700;text-decoration:none;">
                            Modifier
                            <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>

                    @if(method_exists($user, 'isAdmin') && $user->isAdmin())
                        <a href="{{ route('admin.dashboard') }}"
                           style="margin-top:8px;display:flex;align-items:center;gap:12px;padding:14px 16px;border-radius:12px;background:linear-gradient(135deg,#0F172A,#1E293B);color:white;text-decoration:none;">
                            <div style="width:34px;height:34px;border-radius:10px;background:rgba(124,58,237,0.20);color:#A78BFA;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <div style="flex:1;">
                                <div style="font-size:13px;font-weight:700;">Espace administrateur</div>
                                <div style="font-size:11px;color:#94A3B8;margin-top:2px;">Accède au back-office KardAfrica</div>
                            </div>
                            <svg style="width:14px;height:14px;color:#94A3B8;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}" style="margin-top:8px;">
                        @csrf
                        <button type="submit"
                                style="width:100%;display:flex;align-items:center;gap:12px;padding:14px 16px;border-radius:12px;background:#FEF2F2;border:1px solid #FECACA;color:#BE123C;cursor:pointer;text-align:left;">
                            <div style="width:34px;height:34px;border-radius:10px;background:white;color:#BE123C;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            </div>
                            <div style="flex:1;">
                                <div style="font-size:13px;font-weight:700;">Se déconnecter</div>
                                <div style="font-size:11px;color:#991B1B;margin-top:2px;">Termine ta session sur cet appareil</div>
                            </div>
                        </button>
                    </form>
                </div>
            </div>

            {{-- ============= SIDEBAR ============= --}}
            <aside style="display:flex;flex-direction:column;gap:14px;min-width:0;">

                {{-- Quick actions --}}
                <div style="background:linear-gradient(135deg,#0F172A,#1E293B);border-radius:18px;padding:22px;color:white;position:relative;overflow:hidden;box-shadow:0 14px 30px -10px rgba(15,23,42,0.4);">
                    <div style="position:absolute;top:-30px;right:-30px;width:140px;height:140px;border-radius:70px;background:radial-gradient(circle, rgba(78,205,196,0.30) 0%, transparent 70%);"></div>
                    <div style="position:relative;">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.10em;color:#5EEAD4;">Raccourcis</div>
                        <h3 style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:700;margin:4px 0 14px;">Actions rapides</h3>

                        <div style="display:flex;flex-direction:column;gap:8px;">
                            @foreach ([
                                ['label' => 'Boutique', 'desc' => '300+ marques', 'href' => route('boutique'), 'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z'],
                                ['label' => 'Mes commandes', 'desc' => 'Historique complet', 'href' => url('/orders'), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                                ['label' => 'Centre d\'aide', 'desc' => 'FAQ + contact', 'href' => route('support'), 'icon' => 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z'],
                            ] as $action)
                                <a href="{{ $action['href'] }}"
                                   style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:11px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.10);text-decoration:none;transition:background .15s;"
                                   onmouseover="this.style.background='rgba(78,205,196,0.15)';this.style.borderColor='rgba(78,205,196,0.30)';"
                                   onmouseout="this.style.background='rgba(255,255,255,0.06)';this.style.borderColor='rgba(255,255,255,0.10)';">
                                    <div style="width:32px;height:32px;border-radius:9px;background:rgba(78,205,196,0.15);color:#5EEAD4;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $action['icon'] }}"/></svg>
                                    </div>
                                    <div style="flex:1;min-width:0;">
                                        <div style="font-size:13px;font-weight:700;color:white;">{{ $action['label'] }}</div>
                                        <div style="font-size:10px;color:#94A3B8;margin-top:1px;">{{ $action['desc'] }}</div>
                                    </div>
                                    <svg style="width:12px;height:12px;color:#5EEAD4;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Cartes récentes --}}
                @if($recentCards->count() > 0)
                    <div style="background:white;border:1px solid #E2E8F0;border-radius:18px;padding:18px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                            <h3 style="font-family:'Space Grotesk','Inter',sans-serif;font-size:14px;font-weight:700;color:#0F172A;margin:0;">Dernières cartes</h3>
                            <a href="{{ route('cards.index') }}" style="font-size:11px;font-weight:700;color:#44A08D;text-decoration:none;">Voir →</a>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:8px;">
                            @foreach($recentCards as $card)
                                @php $cColor = $brandColorFor($card->brand ?? $card->name); @endphp
                                <div style="display:flex;align-items:center;gap:10px;padding:8px;border-radius:10px;background:#F8FAFC;">
                                    <div style="width:34px;height:34px;border-radius:9px;background:{{ $cColor }};color:white;display:flex;align-items:center;justify-content:center;font-family:'Space Grotesk','Inter',sans-serif;font-weight:800;font-size:13px;flex-shrink:0;">
                                        {{ strtoupper(substr($card->brand ?? $card->name, 0, 1)) }}
                                    </div>
                                    <div style="flex:1;min-width:0;">
                                        <div style="font-size:12px;font-weight:700;color:#0F172A;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $card->brand ?? $card->name }}</div>
                                        <div style="font-size:10px;color:#94A3B8;margin-top:1px;">{{ $card->created_at->diffForHumans() }}</div>
                                    </div>
                                    @if($card->status === 'active')
                                        <span style="width:7px;height:7px;border-radius:50%;background:#10B981;flex-shrink:0;" title="Active"></span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Loyalty / Newsletter --}}
                <div style="background:linear-gradient(135deg, #F0FDF4, #D1FAE5);border:1px solid #A7F3D0;border-radius:18px;padding:18px;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                        <span style="font-size:24px;">🎁</span>
                        <div>
                            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#047857;">Membre VIP</div>
                            <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:14px;font-weight:700;color:#064E3B;">{{ $stats['days_since'] }} {{ $stats['days_since'] > 1 ? 'jours' : 'jour' }} avec nous</div>
                        </div>
                    </div>
                    <p style="font-size:12px;color:#065F46;line-height:1.5;margin:0;">
                        Merci pour ta confiance ! Continue de profiter de nos cartes et bénéficie d'offres exclusives bientôt.
                    </p>
                </div>
            </aside>
        </div>
    </section>
</div>

<style>
    /* Sur petit écran, la colonne main reprend la largeur normale */
    @media (max-width: 768px) {
        .profile-main-col { grid-column: span 1 !important; }
    }
</style>
@endsection
