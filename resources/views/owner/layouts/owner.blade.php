<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Espace propriétaire') — KardAfrica</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}">
    @vite(['resources/css/app.css'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        *,*::before,*::after { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Inter',system-ui,sans-serif; background:#F8FAFC; color:#0F172A; min-height:100vh; min-height:100dvh; }

        .ka-shell { display:flex; min-height:100vh; min-height:100dvh; }

        /* Sidebar */
        .ka-side {
            width:240px; flex-shrink:0;
            background: linear-gradient(180deg,#060A14 0%,#0F172A 100%);
            color:#fff; padding:22px 14px; position:sticky; top:0; height:100vh; height:100dvh; overflow-y:auto;
        }
        .ka-side-brand { display:flex; align-items:center; gap:10px; padding: 4px 10px 22px; border-bottom:1px solid rgba(255,255,255,.08); margin-bottom:14px; }
        .ka-side-brand img { width:32px; height:32px; }
        .ka-side-brand-text { font-family:'Space Grotesk','Inter',sans-serif; font-size:15px; font-weight:800; letter-spacing:-0.01em; }
        .ka-side-brand-tag { font-size:8px; font-weight:700; letter-spacing:.18em; text-transform:uppercase; color:#5EEAD4; margin-top:1px; }

        .ka-nav-link {
            display:flex; align-items:center; gap:11px;
            padding:11px 12px; margin-bottom:3px;
            border-radius:10px; color:rgba(255,255,255,.66);
            font-size:13px; font-weight:600; text-decoration:none;
            transition: background .15s, color .15s;
        }
        .ka-nav-link:hover { background: rgba(255,255,255,.05); color:#fff; }
        .ka-nav-link.active { background: linear-gradient(135deg,rgba(78,205,196,.18),rgba(68,160,141,.08)); color:#5EEAD4; }
        .ka-nav-link svg { width:18px; height:18px; flex-shrink:0; }
        .ka-nav-soon { margin-left:auto; font-size:9px; font-weight:800; color:rgba(255,255,255,.4); background:rgba(255,255,255,.06); padding:2px 6px; border-radius:9999px; letter-spacing:.04em; text-transform:uppercase; }

        .ka-side-foot { position: sticky; bottom: 0; padding-top:14px; margin-top:14px; border-top:1px solid rgba(255,255,255,.08); background: linear-gradient(180deg, transparent, #0F172A 30%); }
        .ka-side-user { display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:10px; }
        .ka-side-user-av { width:34px; height:34px; border-radius:50%; background: linear-gradient(135deg,#44A08D,#4ECDC4); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:14px; }
        .ka-side-user-info { min-width:0; flex:1; }
        .ka-side-user-name { font-size:12px; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .ka-side-user-tag { font-size:10px; color:rgba(255,255,255,.45); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .ka-side-logout {
            display:flex; align-items:center; gap:8px;
            margin-top:6px; padding:9px 10px;
            background: rgba(220,38,38,.10); color:#FCA5A5;
            border:1px solid rgba(220,38,38,.18);
            border-radius:10px; font-size:12px; font-weight:700; cursor:pointer; width:100%;
            text-decoration:none; justify-content:center;
        }

        /* Main */
        .ka-main { flex:1; min-width:0; padding: 0 0 40px; }
        .ka-topbar {
            display:flex; align-items:center; justify-content:space-between; gap:12px;
            padding:16px 28px;
            background:white; border-bottom:1px solid #E2E8F0;
            position: sticky; top:0; z-index:10;
        }
        .ka-topbar-title { font-family:'Space Grotesk','Inter',sans-serif; font-size:18px; font-weight:800; color:#0F172A; }
        .ka-topbar-sub { font-size:12px; color:#64748B; }
        .ka-mobile-burger { display:none; background:transparent; border:0; padding:6px; cursor:pointer; color:#0F172A; }

        .ka-content { padding: 24px 28px; max-width:1280px; margin: 0 auto; }

        @media (max-width: 900px) {
            .ka-side { position: fixed; transform: translateX(-100%); transition: transform .25s; z-index: 30; }
            .ka-side.open { transform: translateX(0); box-shadow: 0 20px 60px rgba(0,0,0,.4); }
            .ka-mobile-burger { display:inline-flex; }
            .ka-content { padding: 18px 16px; }
            .ka-topbar { padding: 12px 16px; }
        }
        .ka-side-backdrop { display:none; }
        @media (max-width: 900px) {
            .ka-side-backdrop.open { display:block; position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:20; }
        }

        [x-cloak] { display:none !important; }
    </style>
    @stack('head')
</head>
<body x-data="{ sidebarOpen: false }">
@php
    $owner = Auth::guard('card_owner')->user();
@endphp

<div class="ka-shell">
    <aside class="ka-side" :class="sidebarOpen && 'open'">
        <div class="ka-side-brand">
            <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" alt="">
            <div>
                <div class="ka-side-brand-text">KardAfrica</div>
                <div class="ka-side-brand-tag">Propriétaire</div>
            </div>
        </div>

        <nav>
            <a href="{{ route('owner.dashboard') }}" class="ka-nav-link {{ request()->routeIs('owner.dashboard') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Tableau de bord
            </a>
            <a href="{{ route('owner.cards') }}" class="ka-nav-link {{ request()->routeIs('owner.cards*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Mes cartes
            </a>
            <a href="{{ route('owner.scan') }}" class="ka-nav-link {{ request()->routeIs('owner.scan*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                Scanner / Valider
            </a>
            <a href="{{ route('owner.history') }}" class="ka-nav-link {{ request()->routeIs('owner.history') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Historique
            </a>
        </nav>

        <div class="ka-side-foot">
            <div class="ka-side-user">
                @if($owner->logo_url)
                    <img src="{{ asset($owner->logo_url) }}" class="ka-side-user-av" style="object-fit:cover;" alt="">
                @else
                    <div class="ka-side-user-av">{{ strtoupper(substr($owner->business_name, 0, 1)) }}</div>
                @endif
                <div class="ka-side-user-info">
                    <div class="ka-side-user-name">{{ $owner->business_name }}</div>
                    <div class="ka-side-user-tag">{{ $owner->email }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('owner.logout') }}">
                @csrf
                <button type="submit" class="ka-side-logout">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Déconnexion
                </button>
            </form>
        </div>
    </aside>

    <div class="ka-side-backdrop" :class="sidebarOpen && 'open'" @click="sidebarOpen = false"></div>

    <main class="ka-main">
        <header class="ka-topbar">
            <div style="display:flex;align-items:center;gap:12px;">
                <button class="ka-mobile-burger" @click="sidebarOpen = true" aria-label="Menu">
                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div>
                    <div class="ka-topbar-title">@yield('page-title', 'Tableau de bord')</div>
                    @hasSection('page-subtitle')
                        <div class="ka-topbar-sub">@yield('page-subtitle')</div>
                    @endif
                </div>
            </div>
            @hasSection('topbar-actions')
                <div>@yield('topbar-actions')</div>
            @endif
        </header>

        @if(session('success'))
            <div style="margin:16px 28px 0;padding:12px 14px;background:#D1FAE5;color:#065F46;border:1px solid #6EE7B7;border-radius:12px;font-size:13px;font-weight:600;">✓ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div style="margin:16px 28px 0;padding:12px 14px;background:#FEE2E2;color:#991B1B;border:1px solid #FCA5A5;border-radius:12px;font-size:13px;font-weight:600;">{{ session('error') }}</div>
        @endif

        <section class="ka-content">
            @yield('content')
        </section>
    </main>
</div>

@stack('scripts')
</body>
</html>
