<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#44A08D">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Espace vendeur') — KardAfrica</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}">

    {{-- ========== PWA : transforme le portail vendeur en app installable ========== --}}
    <link rel="manifest" href="{{ asset('vendor-manifest.webmanifest') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Kardafrica V">
    <link rel="apple-touch-icon" href="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}">

    {{-- Mêmes fonts que admin et site officiel --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900|space-grotesk:500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css'])
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body {
            margin: 0;
            font-family: 'Inter','Figtree',sans-serif;
            background: #F8FAFC;
            min-height: 100vh; min-height: 100dvh;
            color: #0F172A;
        }

        /* ============================================== */
        /* ============== TOP HEADER ===================== */
        /* ============================================== */
        .v-header {
            position: sticky; top: 0; z-index: 50;
            background: linear-gradient(180deg, #0F172A 0%, #1E293B 100%);
            color: white;
            padding: 12px 16px;
            padding-top: max(12px, env(safe-area-inset-top));
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .v-header-inner {
            max-width: 1200px; margin: 0 auto;
            display: flex; align-items: center; gap: 12px;
        }

        /* Brand compact */
        .v-brand {
            display: flex; align-items: center; gap: 10px;
            color: white; text-decoration: none;
            flex-shrink: 0;
        }
        .v-brand img { width: 32px; height: 32px; }
        .v-brand-text { display: none; }
        @media (min-width: 480px) { .v-brand-text { display: block; } }
        .v-brand-tag {
            font-size: 8px; font-weight: 700; letter-spacing: 0.18em;
            color: #5EEAD4; text-transform: uppercase; line-height: 1;
        }
        .v-brand-name {
            font-family: 'Space Grotesk','Inter',sans-serif;
            font-size: 14px; font-weight: 800; letter-spacing: -0.01em;
            margin-top: 1px;
        }

        /* Wallet pill (always visible — primary mobile info) */
        .v-wallet-pill {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 7px 12px;
            background: linear-gradient(135deg, #44A08D, #4ECDC4);
            color: white;
            border-radius: 9999px;
            font-family: 'Space Grotesk','Inter',sans-serif;
            font-weight: 800; font-size: 13px;
            font-variant-numeric: tabular-nums;
            box-shadow: 0 6px 14px -6px rgba(78,205,196,0.50),
                        inset 0 1px 0 rgba(255,255,255,0.30);
            margin-left: auto;
        }
        .v-wallet-pill svg { width: 13px; height: 13px; color: white; }
        .v-wallet-pill > span { color: rgba(255,255,255,0.80) !important; }

        /* User menu */
        .v-user-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, #44A08D, #4ECDC4);
            color: white;
            border: 0; cursor: pointer;
            font-family: 'Space Grotesk','Inter',sans-serif;
            font-weight: 800; font-size: 14px;
            flex-shrink: 0;
            box-shadow: 0 4px 10px -3px rgba(78,205,196,0.40),
                        inset 0 1px 0 rgba(255,255,255,0.25);
        }

        /* Drop-down user panel */
        .v-user-panel {
            position: absolute; right: 16px; top: calc(100% + 6px);
            background: white;
            border-radius: 14px;
            box-shadow: 0 20px 40px -10px rgba(15,23,42,0.30),
                        0 0 0 1px rgba(15,23,42,0.06);
            min-width: 220px;
            padding: 8px;
            z-index: 100;
        }
        .v-user-panel-info {
            padding: 10px 12px 12px;
            border-bottom: 1px solid #F1F5F9;
            margin-bottom: 6px;
        }
        .v-user-panel-name {
            font-family: 'Space Grotesk','Inter',sans-serif;
            font-size: 14px; font-weight: 800; color: #0F172A;
        }
        .v-user-panel-code {
            font-family: 'JetBrains Mono','Fira Code',monospace;
            font-size: 11px; color: #44A08D; font-weight: 700;
            margin-top: 2px;
        }
        .v-user-panel-row {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px;
            border-radius: 9px;
            font-size: 13px; color: #475569; font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            background: none; border: 0; width: 100%;
            text-align: left;
            font-family: inherit;
        }
        .v-user-panel-row:hover { background: #F8FAFC; color: #0F172A; }
        .v-user-panel-row.danger { color: #BE123C; }
        .v-user-panel-row.danger:hover { background: #FEF2F2; }
        .v-user-panel-row svg { width: 14px; height: 14px; flex-shrink: 0; }

        /* Desktop nav (hidden on mobile) */
        .v-nav-desktop {
            display: none;
            gap: 4px; align-items: center;
        }
        @media (min-width: 768px) {
            .v-nav-desktop { display: inline-flex; }
        }
        .v-nav-link {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 14px;
            border-radius: 10px;
            color: #94A3B8;
            font-size: 13px; font-weight: 600;
            text-decoration: none;
            transition: all .15s ease;
        }
        .v-nav-link:hover { color: white; background: rgba(255,255,255,0.06); }
        .v-nav-link.active {
            color: white;
            background: rgba(78,205,196,0.15);
            border: 1px solid rgba(78,205,196,0.30);
        }
        .v-nav-link svg { width: 14px; height: 14px; }

        /* ============================================== */
        /* ============== MAIN CONTENT =================== */
        /* ============================================== */
        .v-main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 16px 16px;
            padding-bottom: calc(96px + env(safe-area-inset-bottom)); /* room for bottom tab */
        }
        @media (min-width: 768px) {
            .v-main { padding-bottom: 24px; }
        }

        /* ============================================== */
        /* ============== BOTTOM TAB BAR ================= */
        /* ============================================== */
        .v-tabbar {
            position: fixed; bottom: 0; left: 0; right: 0;
            z-index: 60;
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-top: 1px solid #E2E8F0;
            padding: 8px 12px;
            padding-bottom: max(8px, env(safe-area-inset-bottom));
            box-shadow: 0 -8px 24px -8px rgba(15,23,42,0.10);
        }
        @media (min-width: 768px) {
            .v-tabbar { display: none; }
        }
        .v-tabbar-inner {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 4px;
            position: relative;
            max-width: 480px; margin: 0 auto;
        }
        .v-tab {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 3px;
            padding: 8px 4px;
            border-radius: 12px;
            color: #94A3B8;
            text-decoration: none;
            font-family: 'Inter', sans-serif;
            font-size: 10px; font-weight: 700;
            cursor: pointer;
            transition: all .15s ease;
            background: none; border: 0;
            min-height: 56px;
            position: relative;
        }
        .v-tab svg { width: 22px; height: 22px; transition: transform .2s ease; }
        .v-tab.active { color: #44A08D; }
        .v-tab.active svg { transform: scale(1.1); }
        .v-tab.active::before {
            content: '';
            position: absolute; top: 4px; left: 50%;
            transform: translateX(-50%);
            width: 24px; height: 3px;
            border-radius: 9999px;
            background: linear-gradient(90deg, #44A08D, #4ECDC4);
        }

        /* Tab "Vendre" — toujours en brand pour bien sortir du lot */
        .v-tab-sell {
            color: #44A08D;
        }
        .v-tab-sell svg {
            stroke-width: 2.5;
        }
        .v-tab-sell .v-tab-icon-wrap {
            position: relative;
            display: inline-flex; align-items: center; justify-content: center;
            width: 30px; height: 30px;
            border-radius: 10px;
            background: linear-gradient(135deg, #44A08D, #4ECDC4);
            color: white;
            box-shadow: 0 6px 14px -4px rgba(78,205,196,0.55),
                        inset 0 1px 0 rgba(255,255,255,0.30);
            transition: transform .15s ease;
            margin-bottom: -1px;
        }
        .v-tab-sell .v-tab-icon-wrap svg {
            width: 16px; height: 16px;
        }
        .v-tab-sell:active .v-tab-icon-wrap { transform: scale(0.92); }
        .v-tab-sell.active .v-tab-icon-wrap {
            box-shadow: 0 8px 20px -4px rgba(78,205,196,0.70),
                        inset 0 1px 0 rgba(255,255,255,0.40);
        }

        /* ============================================== */
        /* ============== FLASH MESSAGES ================= */
        /* ============================================== */
        .v-flash {
            position: fixed; left: 16px; right: 16px;
            top: calc(64px + env(safe-area-inset-top));
            z-index: 70;
            padding: 13px 16px;
            border-radius: 13px;
            font-size: 13px; font-weight: 700;
            display: flex; align-items: center; gap: 10px;
            box-shadow: 0 16px 32px -8px rgba(15,23,42,0.20);
            animation: v-flash-in .35s cubic-bezier(.22, 1, .36, 1);
        }
        @media (min-width: 768px) {
            .v-flash {
                top: calc(72px + env(safe-area-inset-top));
                left: 50%; right: auto; transform: translateX(-50%);
                min-width: 360px; max-width: 600px;
            }
        }
        @keyframes v-flash-in {
            from { opacity: 0; transform: translateY(-12px) translateX(-50%) scale(.97); }
            to   { opacity: 1; transform: translateY(0) translateX(-50%) scale(1); }
        }
        @media (max-width: 767px) {
            @keyframes v-flash-in {
                from { opacity: 0; transform: translateY(-12px); }
                to   { opacity: 1; transform: translateY(0); }
            }
        }
        .v-flash.success { background: linear-gradient(135deg, #ECFDF5, #D1FAE5); color: #047857; border: 1px solid #6EE7B7; }
        .v-flash.error   { background: linear-gradient(135deg, #FEF2F2, #FEE2E2); color: #BE123C; border: 1px solid #FCA5A5; }
        .v-flash svg { width: 18px; height: 18px; flex-shrink: 0; }
        .v-flash-close {
            margin-left: auto; background: none; border: 0; color: inherit;
            opacity: 0.6; cursor: pointer; padding: 4px;
        }
        .v-flash-close:hover { opacity: 1; }
    </style>
    @stack('head')
</head>
<body x-data="{ menuOpen: false }">

    @php
        $reseller = auth('vendor')->user();
        $pendingCashCount = $reseller
            ? \App\Models\Order::where('cash_reseller_id', $reseller->id)
                ->where('payment_method', \App\Models\Order::PAYMENT_METHOD_CASH_RESELLER)
                ->where('payment_status', \App\Models\Order::PAYMENT_STATUS_PENDING)
                ->where(function($q){ $q->whereNull('cash_lock_expires_at')->orWhere('cash_lock_expires_at', '>', now()); })
                ->count()
            : 0;
    @endphp

    {{-- =============== TOP HEADER =============== --}}
    <header class="v-header">
        <div class="v-header-inner" style="position:relative;">
            <a href="{{ route('vendor.dashboard') }}" class="v-brand">
                <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" alt="KardAfrica">
                <div class="v-brand-text">
                    <div class="v-brand-tag">Vendeur</div>
                    <div class="v-brand-name">KardAfrica</div>
                </div>
            </a>

            {{-- Desktop nav --}}
            <nav class="v-nav-desktop" style="margin-left:18px;">
                <a href="{{ route('vendor.dashboard') }}" class="v-nav-link {{ request()->routeIs('vendor.dashboard') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Tableau
                </a>
                <a href="{{ route('vendor.sell') }}" class="v-nav-link {{ request()->routeIs('vendor.sell') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Vendre
                </a>
                <a href="{{ route('vendor.orders') }}" class="v-nav-link {{ request()->routeIs('vendor.orders*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Mes ventes
                </a>
                <a href="{{ route('vendor.cash.index') }}" class="v-nav-link {{ request()->routeIs('vendor.cash*') ? 'active' : '' }}" style="position:relative;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
                    Encaisser
                    @if($pendingCashCount > 0)
                        <span style="display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 5px;border-radius:9999px;background:#F59E0B;color:white;font-size:10px;font-weight:800;margin-left:6px;">{{ $pendingCashCount }}</span>
                    @endif
                </a>
                @if($reseller && (float) $reseller->cash_to_remit > 0)
                <a href="{{ route('vendor.remittance.index') }}" class="v-nav-link {{ request()->routeIs('vendor.remittance*') ? 'active' : '' }}" style="position:relative;color:#BE123C;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    Remettre
                    <span style="display:inline-flex;align-items:center;justify-content:center;height:18px;padding:0 6px;border-radius:9999px;background:#BE123C;color:white;font-size:10px;font-weight:800;margin-left:6px;font-variant-numeric:tabular-nums;">{{ number_format($reseller->cash_to_remit, 0, ',', ' ') }}</span>
                </a>
                @endif
                <a href="{{ route('vendor.merchant-cards.index') }}" class="v-nav-link {{ request()->routeIs('vendor.merchant-cards*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    Mes cartes
                </a>
                <a href="{{ route('vendor.profile') }}" class="v-nav-link {{ request()->routeIs('vendor.profile') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Mon compte
                </a>
            </nav>

            {{-- User avatar (toggles panel) --}}
            <button type="button" class="v-user-btn" @click="menuOpen = !menuOpen" :aria-expanded="menuOpen.toString()" style="margin-left:auto;">
                {{ strtoupper(substr($reseller->name, 0, 1)) }}
            </button>

            {{-- User panel --}}
            <div x-show="menuOpen" x-cloak @click.outside="menuOpen = false" class="v-user-panel"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100">
                <div class="v-user-panel-info">
                    <div class="v-user-panel-name">{{ $reseller->name }}</div>
                    <div class="v-user-panel-code">{{ $reseller->vendor_code }}</div>
                </div>
                <a href="{{ route('vendor.dashboard') }}" class="v-user-panel-row">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3"/></svg>
                    Mon tableau
                </a>
                <a href="{{ route('vendor.orders') }}" class="v-user-panel-row">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/></svg>
                    Mes ventes
                </a>
                <a href="{{ route('vendor.merchant-cards.index') }}" class="v-user-panel-row">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    Mes cartes-cadeau
                </a>
                <a href="{{ route('vendor.cash.index') }}" class="v-user-panel-row" style="position:relative;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
                    À encaisser
                    @if($pendingCashCount > 0)
                        <span style="margin-left:auto;display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;padding:0 6px;border-radius:9999px;background:#F59E0B;color:white;font-size:10px;font-weight:800;">{{ $pendingCashCount }}</span>
                    @endif
                </a>
                @if($reseller && (float) $reseller->cash_to_remit > 0)
                <a href="{{ route('vendor.remittance.index') }}" class="v-user-panel-row" style="position:relative;color:#BE123C;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    Remettre cash
                    <span style="margin-left:auto;display:inline-flex;align-items:center;justify-content:center;height:20px;padding:0 7px;border-radius:9999px;background:#BE123C;color:white;font-size:10px;font-weight:800;font-variant-numeric:tabular-nums;">{{ number_format($reseller->cash_to_remit, 0, ',', ' ') }}</span>
                </a>
                @endif
                {{-- Bouton « Installer l'app » : caché par défaut, le JS l'active si beforeinstallprompt fire --}}
                <button type="button" id="pwaMenuInstallBtn" class="v-user-panel-row" style="display:none;color:#0F4F44;background:linear-gradient(90deg,#ECFDF5,white);font-weight:700;width:100%;text-align:left;border:0;cursor:pointer;align-items:center;gap:10px;">
                    <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" alt="Kardafrica" style="width:20px;height:20px;object-fit:contain;flex-shrink:0;">
                    Installer l'app Kardafrica
                    <span style="margin-left:auto;font-size:10px;font-weight:600;color:#44A08D;text-transform:uppercase;letter-spacing:0.06em;">PWA</span>
                </button>
                <form method="POST" action="{{ route('vendor.logout') }}" style="margin:0;">@csrf
                    <button type="submit" class="v-user-panel-row danger">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Se déconnecter
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- =============== FLASH =============== --}}
    @if(session('success'))
        <div class="v-flash success" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4500)">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('success') }}</span>
            <button class="v-flash-close" @click="show = false">✕</button>
        </div>
    @endif
    @if(session('error'))
        <div class="v-flash error" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5500)">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>{{ session('error') }}</span>
            <button class="v-flash-close" @click="show = false">✕</button>
        </div>
    @endif

    {{-- =============== MAIN =============== --}}
    <main class="v-main">
        @yield('content')
    </main>

    {{-- =============== BOTTOM TAB BAR (mobile only) =============== --}}
    <nav class="v-tabbar">
        <div class="v-tabbar-inner">
            <a href="{{ route('vendor.dashboard') }}" class="v-tab {{ request()->routeIs('vendor.dashboard') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Tableau
            </a>

            <a href="{{ route('vendor.cash.index') }}" class="v-tab {{ request()->routeIs('vendor.cash*') ? 'active' : '' }}" style="position:relative;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
                Encaisser
                @if($pendingCashCount > 0)
                    <span style="position:absolute;top:6px;right:calc(50% - 22px);min-width:18px;height:18px;padding:0 5px;border-radius:9999px;background:#F59E0B;color:white;font-size:10px;font-weight:800;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 1px 3px rgba(0,0,0,0.2);">{{ $pendingCashCount }}</span>
                @endif
            </a>

            <a href="{{ route('vendor.sell') }}" class="v-tab v-tab-sell {{ request()->routeIs('vendor.sell') ? 'active' : '' }}">
                <span class="v-tab-icon-wrap">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                </span>
                Vendre
            </a>

            <a href="{{ route('vendor.orders') }}" class="v-tab {{ request()->routeIs('vendor.orders*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Ventes
            </a>

            <a href="{{ route('vendor.profile') }}" class="v-tab {{ request()->routeIs('vendor.profile') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Compte
            </a>
        </div>
    </nav>

    {{-- ================ PWA : Install prompt + Service Worker ================ --}}
    {{-- Bandeau d'invite à installer l'app (apparaît si éligible et pas déjà installée) --}}
    <div id="pwaInstallBanner"
         style="display:none;position:fixed;bottom:96px;left:12px;right:12px;z-index:60;background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;padding:14px 16px;border-radius:16px;box-shadow:0 18px 40px -10px rgba(68,160,141,0.55),inset 0 1px 0 rgba(255,255,255,0.30);">
        <div style="display:flex;align-items:center;gap:12px;">
            {{-- Logo Kardafrica officiel (carré favicon) --}}
            <div style="width:44px;height:44px;border-radius:11px;background:white;display:flex;align-items:center;justify-content:center;flex-shrink:0;padding:4px;box-shadow:inset 0 1px 0 rgba(255,255,255,0.40);">
                <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" alt="Kardafrica"
                     style="width:100%;height:100%;object-fit:contain;">
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:800;line-height:1.2;">Installer Kardafrica Vendeur</div>
                <div style="font-size:11px;opacity:0.92;line-height:1.4;margin-top:2px;">Accès rapide depuis ton écran d'accueil, sans navigateur.</div>
            </div>
            <button type="button" id="pwaInstallBtn"
                    style="background:white;color:#0F4F44;border:0;padding:8px 14px;border-radius:9px;font-size:12px;font-weight:800;cursor:pointer;flex-shrink:0;">Installer</button>
            <button type="button" id="pwaInstallDismiss"
                    style="background:rgba(255,255,255,0.20);color:white;border:0;width:28px;height:28px;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;" aria-label="Fermer">
                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    <script>
    (function () {
        // 1. Enregistrement Service Worker (uniquement si HTTPS ou localhost)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('{{ asset('sw-vendor.js') }}', { scope: '/vendor/' })
                    .catch((err) => console.warn('SW vendor registration failed:', err));
            });
        }

        // 2. Capture du beforeinstallprompt + UI bouton
        let deferredPrompt = null;
        const banner    = document.getElementById('pwaInstallBanner');
        const btnInstall= document.getElementById('pwaInstallBtn');
        const btnClose  = document.getElementById('pwaInstallDismiss');
        const menuBtn   = document.getElementById('pwaMenuInstallBtn'); // optionnel : bouton dans le menu user

        const isStandalone = window.matchMedia('(display-mode: standalone)').matches
                          || window.navigator.standalone === true;
        const dismissed = localStorage.getItem('pwa.vendor.dismissed') === '1';

        if (isStandalone) {
            // déjà installé : rien à faire
            if (menuBtn) menuBtn.style.display = 'none';
            return;
        }

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            // Affiche bandeau seulement si l'utilisateur n'a pas déjà fermé
            if (banner && !dismissed) banner.style.display = 'block';
            if (menuBtn) menuBtn.style.display = 'flex';
        });

        if (btnInstall) {
            btnInstall.addEventListener('click', async () => {
                if (!deferredPrompt) return;
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                deferredPrompt = null;
                if (banner) banner.style.display = 'none';
                if (menuBtn) menuBtn.style.display = 'none';
                if (outcome === 'dismissed') {
                    localStorage.setItem('pwa.vendor.dismissed', '1');
                }
            });
        }

        if (btnClose) {
            btnClose.addEventListener('click', () => {
                if (banner) banner.style.display = 'none';
                localStorage.setItem('pwa.vendor.dismissed', '1');
            });
        }

        if (menuBtn) {
            menuBtn.addEventListener('click', async () => {
                if (!deferredPrompt) return;
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                deferredPrompt = null;
                menuBtn.style.display = 'none';
                if (outcome === 'dismissed') {
                    localStorage.setItem('pwa.vendor.dismissed', '1');
                }
            });
        }

        window.addEventListener('appinstalled', () => {
            if (banner) banner.style.display = 'none';
            if (menuBtn) menuBtn.style.display = 'none';
            deferredPrompt = null;
        });
    })();
    </script>

    @stack('scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
</body>
</html>
