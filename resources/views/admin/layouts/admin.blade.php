<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - Kardafrica</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900|space-grotesk:300,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    {{-- Le langage visuel « layered » vit dans resources/css/admin-tokens.css
         (tokens scopés .adm + shell + primitives ui/). Ne restent ici que les
         classes héritées consommées par les écrans non encore refondus. --}}
    <style>
        [x-cloak] { display: none !important; }
        * { box-sizing: border-box; }

        /* ===== HÉRITAGE — badges de statut des écrans pré-refonte ===== */
        .badge-pending { background: #FEF3C7; color: #92400E; }
        .badge-processing { background: #DBEAFE; color: #1E40AF; }
        .badge-completed { background: #D1FAE5; color: #065F46; }
        .badge-failed { background: #FEE2E2; color: #991B1B; }
        .badge-cancelled { background: #F3F4F6; color: #374151; }
        .badge-active { background: #D1FAE5; color: #065F46; }
        .badge-used { background: #E5E7EB; color: #6B7280; }
        .badge-expired { background: #FEE2E2; color: #991B1B; }
        .badge-admin { background: #EDE9FE; color: #5B21B6; }
        .badge-moderator { background: #FEF3C7; color: #92400E; }
        .badge-user { background: #DBEAFE; color: #1E40AF; }
        .stat-card { transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    </style>
    {{-- Styles empilés par les écrans et composants (`@push('head')`). --}}
    @stack('head')
</head>
<body class="adm" style="margin:0;font-family:'Inter',sans-serif;">

    <!-- ============ RAIL D'ICÔNES (≥1024px) ============ -->
    <aside class="adm-rail" aria-label="Navigation principale">
        <a href="{{ route('admin.dashboard') }}" class="adm-rail-logo" title="Kardafrica">
            <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" alt="Kardafrica">
        </a>

        <a href="{{ route('admin.dashboard') }}" class="adm-rail-ic {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" title="Tableau de bord">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        </a>
        <a href="{{ route('admin.orders.index') }}" class="adm-rail-ic {{ request()->routeIs('admin.orders.*') ? 'is-active' : '' }}" title="Commandes">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </a>
        <a href="{{ route('admin.catalog.index') }}" class="adm-rail-ic {{ request()->routeIs('admin.catalog.*') || request()->routeIs('admin.daywatch.*') ? 'is-active' : '' }}" title="Catalogue">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        </a>
        <a href="{{ route('admin.cards.index') }}" class="adm-rail-ic {{ request()->routeIs('admin.cards.*') || request()->routeIs('admin.merchant-cards.*') ? 'is-active' : '' }}" title="Cartes">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
        </a>
        <a href="{{ route('admin.payments.index') }}" class="adm-rail-ic {{ request()->routeIs('admin.payments.*') || request()->routeIs('admin.versements.*') ? 'is-active' : '' }}" title="Finance">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </a>
        <a href="{{ route('admin.settings.index') }}" class="adm-rail-ic {{ request()->routeIs('admin.settings.*') ? 'is-active' : '' }}" title="Paramètres">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </a>

        <div class="adm-rail-spacer"></div>

        <a href="{{ route('home') }}" target="_blank" class="adm-rail-ic" title="Voir le site">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        </a>
    </aside>

    <!-- ============ ARBORESCENCE (≥1024px, repliable) ============ -->
    <aside class="adm-tree" id="adm-tree" aria-label="Navigation détaillée">
        <div class="adm-tree-head">
            <a href="{{ route('admin.dashboard') }}" class="adm-tree-title">
                <strong>Kard<em>africa</em></strong>
                <span>Console admin</span>
            </a>
        </div>
        <nav class="adm-tree-nav">
            @include('admin.layouts._nav')
        </nav>
        <div class="adm-tree-foot">
            <a href="{{ route('home') }}" target="_blank" class="adm-nav-item">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span class="adm-nav-txt">Voir le site</span>
            </a>
        </div>
    </aside>

    <!-- ============ DRAWER MOBILE (<1024px) ============ -->
    <div class="adm-overlay" id="adm-overlay">
        <div class="adm-overlay-bg" onclick="admCloseDrawer()"></div>
        <div class="adm-drawer">
            <div class="adm-tree-head" style="justify-content:space-between;">
                <a href="{{ route('admin.dashboard') }}" class="adm-tree-title">
                    <strong>Kard<em>africa</em></strong>
                    <span>Console admin</span>
                </a>
                <button onclick="admCloseDrawer()" class="adm-burger" style="color:rgb(255 255 255 / .6);" aria-label="Fermer le menu">
                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <nav class="adm-tree-nav">
                @include('admin.layouts._nav')
            </nav>
            <div class="adm-tree-foot">
                <a href="{{ route('home') }}" target="_blank" class="adm-nav-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    <span class="adm-nav-txt">Voir le site</span>
                </a>
            </div>
        </div>
    </div>

    <!-- ============ ZONE PRINCIPALE ============ -->
    <div class="adm-main" id="adm-main">

        <!-- Topbar flottante -->
        <header class="adm-topbar">
            <button onclick="admToggleNav()" class="adm-burger" aria-label="Ouvrir le menu">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            <h1 class="adm-page-title">@yield('page-title', 'Tableau de bord')</h1>

            {{-- Recherche globale : aucun endpoint n'existe encore côté admin —
                 la pill est rendue mais inerte, avec l'explication en tooltip.
                 À brancher quand l'endpoint existera (hors périmètre P1). --}}
            <div class="adm-search" title="Recherche globale — bientôt disponible" aria-disabled="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Rechercher une commande, un client…
            </div>

            <div style="flex:1;"></div>

            {{-- Période : soumet `date_from`/`date_to` à l'URL COURANTE — ce sont
                 les noms que consomment déjà Commandes et Paiements. Les autres
                 pages les ignorent sans dommage. --}}
            <div class="adm-period" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" class="adm-period-btn" @click="open = !open">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    @if (request('date_from') || request('date_to'))
                        {{ request('date_from') ?: '…' }} → {{ request('date_to') ?: '…' }}
                    @else
                        Période
                    @endif
                </button>
                <form x-show="open" x-cloak class="adm-period-pop" method="GET" action="{{ request()->url() }}">
                    @foreach (request()->except(['date_from', 'date_to', 'page']) as $k => $v)
                        @if (is_scalar($v))
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endif
                    @endforeach
                    <label for="adm-date-from">Du</label>
                    <input type="date" id="adm-date-from" name="date_from" value="{{ request('date_from') }}">
                    <label for="adm-date-to">Au</label>
                    <input type="date" id="adm-date-to" name="date_to" value="{{ request('date_to') }}">
                    <button type="submit" class="adm-period-apply">Appliquer</button>
                </form>
            </div>

            {{-- Export : visible seulement quand la page déclare son export réel
                 via @section('export-url') — jamais d'action inventée. --}}
            @hasSection('export-url')
                <a href="@yield('export-url')" class="adm-top-ic" title="Exporter">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                </a>
            @endif

            <div class="adm-avatar">
                <div class="adm-avatar-badge">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                <div class="adm-avatar-name">
                    <strong>{{ explode(' ', Auth::user()->name)[0] }}</strong>
                    <span>Admin</span>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.logout') }}" style="margin:0;">
                @csrf
                <button type="submit" class="adm-top-ic" title="Déconnexion" style="border:0;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </button>
            </form>

            {{-- FAB : uniquement les créations qui EXISTENT déjà dans l'admin. --}}
            <div style="position:relative;" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" class="adm-fab" @click="open = !open" aria-label="Actions rapides" :aria-expanded="open">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                </button>
                <div x-show="open" x-cloak class="adm-fab-menu">
                    <a href="{{ route('admin.merchant-cards.create') }}" class="adm-fab-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        Nouvelle carte locale
                    </a>
                    <a href="{{ route('admin.card-owners.create') }}" class="adm-fab-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Nouveau propriétaire
                    </a>
                    <a href="{{ route('admin.resellers.create') }}" class="adm-fab-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Nouveau vendeur
                    </a>
                    <a href="{{ route('admin.users.create') }}" class="adm-fab-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        Nouvel utilisateur
                    </a>
                    <a href="{{ route('admin.daywatch.create') }}" class="adm-fab-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Nouveau Daywatch
                    </a>
                </div>
            </div>
        </header>

        <!-- Flash Modal (style carte) -->
        @if(session('success') || session('error') || session('info'))
            @php
                $msgType = session('success') ? 'success' : (session('error') ? 'error' : 'info');
                $msg = session($msgType);
            @endphp
            <x-flash-modal :type="$msgType" :message="$msg" />
        @endif

        <!-- Page Content -->
        <main class="adm-content">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="adm-footer">
            Kardafrica Admin &copy; {{ date('Y') }}
        </footer>
    </div>

    <script>
        // Repli du volet : sur desktop on replie l'arborescence, sur mobile on
        // ouvre le drawer. Même bouton, geste attendu différent.
        var admTreeOpen = true;

        function admToggleNav() {
            if (window.innerWidth >= 1024) {
                admTreeOpen = !admTreeOpen;
                document.getElementById('adm-tree').classList.toggle('collapsed', !admTreeOpen);
                document.getElementById('adm-main').classList.toggle('wide', !admTreeOpen);
            } else {
                document.getElementById('adm-overlay').classList.add('open');
            }
        }

        function admCloseDrawer() {
            document.getElementById('adm-overlay').classList.remove('open');
        }
    </script>
</body>
</html>
