<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - Kardafrica</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900|space-grotesk:500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        * { box-sizing: border-box; }

        .sidebar-link { display: flex; align-items: center; padding: 10px 12px; border-radius: 8px; font-size: 14px; font-weight: 500; color: #D1D5DB; white-space: nowrap; transition: all 0.2s ease; text-decoration: none; }
        .sidebar-link:hover, .sidebar-link.active { background: rgba(78, 205, 196, 0.15); color: #4ECDC4; }
        .sidebar-link.active { border-right: 3px solid #4ECDC4; }
        .sidebar-link svg { width: 20px; height: 20px; margin-right: 12px; flex-shrink: 0; }

        .stat-card { transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }

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

        /* ===== DESKTOP SIDEBAR ===== */
        #desktop-sidebar {
            display: none;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: 256px;
            background: #111827;
            color: white;
            z-index: 40;
            flex-direction: column;
            transition: transform 0.3s ease;
        }
        @media (min-width: 1024px) {
            #desktop-sidebar { display: flex; }
            #desktop-sidebar.collapsed { transform: translateX(-256px); }
        }

        /* ===== MAIN CONTENT ===== */
        #main-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin-left: 0;
            transition: margin-left 0.3s ease;
        }
        @media (min-width: 1024px) {
            #main-wrapper { margin-left: 256px; }
            #main-wrapper.pushed-off { margin-left: 0; }
        }

        /* ===== MOBILE OVERLAY ===== */
        #mobile-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 50;
        }
        #mobile-overlay.open { display: block; }
        #mobile-overlay .overlay-bg { position: absolute; inset: 0; background: rgba(0,0,0,0.5); }
        #mobile-sidebar {
            position: absolute;
            top: 0; left: 0; bottom: 0;
            width: 280px;
            background: #111827;
            color: white;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        #mobile-overlay.open #mobile-sidebar { transform: translateX(0); }

        /* ===== TOP BAR ===== */
        .admin-topbar {
            background: white;
            border-bottom: 1px solid #E5E7EB;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            position: sticky;
            top: 0;
            z-index: 30;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        @media (min-width: 640px) { .admin-topbar { padding: 0 24px; } }

        .sidebar-logo { display: flex; align-items: center; height: 64px; padding: 0 24px; border-bottom: 1px solid #1F2937; flex-shrink: 0; }
        .sidebar-logo img { width: 32px; height: 32px; margin-right: 8px; }
        .sidebar-logo span { font-size: 18px; font-weight: 700; }

        .sidebar-section-label { padding: 12px 16px 8px 28px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #6B7280; font-weight: 600; }
        .sidebar-nav { flex: 1; padding: 0 12px; overflow-y: auto; display: flex; flex-direction: column; gap: 2px; }
        .sidebar-bottom { padding: 16px; border-top: 1px solid #1F2937; flex-shrink: 0; }
    </style>
</head>
<body style="background: #FAFAF7; margin: 0; font-family: 'Inter', 'Figtree', sans-serif;">

    <!-- ============ MOBILE OVERLAY ============ -->
    <div id="mobile-overlay">
        <div class="overlay-bg" onclick="closeMobileSidebar()"></div>
        <div id="mobile-sidebar">
            <div class="sidebar-logo" style="justify-content: space-between;">
                <a href="{{ route('admin.dashboard') }}" style="display:flex;align-items:center;text-decoration:none;color:white;gap:10px;">
                    <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" alt="Logo" style="width:32px;height:32px;">
                    <div style="display:flex;flex-direction:column;line-height:1;">
                        <span style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:17px;font-weight:700;letter-spacing:-0.01em;"><span style="color:white;">Kard</span><span style="color:#4ECDC4;">Africa</span></span>
                        <span style="font-size:9px;color:#64748B;letter-spacing:0.15em;text-transform:uppercase;font-weight:600;margin-top:2px;">Console admin</span>
                    </div>
                </a>
                <button onclick="closeMobileSidebar()" style="background:none;border:none;color:#9CA3AF;cursor:pointer;padding:4px;">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="sidebar-section-label">Administration</div>
            <nav class="sidebar-nav">
                @include('admin.layouts._nav')
            </nav>
            <div class="sidebar-bottom">
                <a href="{{ route('home') }}" class="sidebar-link" style="color:#9CA3AF;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    Voir le site
                </a>
            </div>
        </div>
    </div>

    <!-- ============ DESKTOP SIDEBAR ============ -->
    <aside id="desktop-sidebar">
        <div class="sidebar-logo">
            <a href="{{ route('admin.dashboard') }}" style="display:flex;align-items:center;text-decoration:none;color:white;">
                <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" alt="Logo">
                <span><span style="color:white;">Kard</span><span style="color:#4ECDC4;">africa</span></span>
            </a>
        </div>
        <div class="sidebar-section-label">Administration</div>
        <nav class="sidebar-nav">
            @include('admin.layouts._nav')
        </nav>
        <div class="sidebar-bottom">
            <a href="{{ route('home') }}" class="sidebar-link" style="color:#9CA3AF;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                Voir le site
            </a>
        </div>
    </aside>

    <!-- ============ MAIN CONTENT ============ -->
    <div id="main-wrapper">
        <!-- Top Bar -->
        <header class="admin-topbar">
            <div style="display:flex;align-items:center;gap:8px;">
                <button onclick="toggleSidebar()" style="background:none;border:none;cursor:pointer;padding:8px;border-radius:10px;color:#64748B;transition:all 0.15s;" onmouseover="this.style.background='#F1F5F9';this.style.color='#0F172A';" onmouseout="this.style.background='none';this.style.color='#64748B';">
                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:18px;font-weight:700;color:#0F172A;margin:0;letter-spacing:-0.01em;">@yield('page-title', 'Tableau de bord')</h1>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <a href="{{ route('home') }}" target="_blank" style="display:none;align-items:center;gap:6px;padding:7px 12px;border-radius:8px;background:#F1F5F9;color:#475569;font-size:12px;font-weight:600;text-decoration:none;transition:all 0.15s;" class="topbar-view-site" onmouseover="this.style.background='#E2E8F0';" onmouseout="this.style.background='#F1F5F9';">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    Voir le site
                </a>
                <div style="display:flex;align-items:center;gap:8px;padding:6px 10px;border-radius:10px;background:#F8FAFC;border:1px solid #E2E8F0;">
                    <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#4ECDC4,#44A08D);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:12px;">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div style="display:flex;flex-direction:column;line-height:1.1;">
                        <span style="font-size:12px;font-weight:600;color:#0F172A;">{{ explode(' ', Auth::user()->name)[0] }}</span>
                        <span style="font-size:10px;color:#64748B;text-transform:uppercase;letter-spacing:0.05em;font-weight:600;">Admin</span>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}" style="margin:0;">
                    @csrf
                    <button type="submit" style="background:none;border:1px solid #E2E8F0;cursor:pointer;padding:8px;border-radius:10px;color:#64748B;transition:all 0.15s;" title="Déconnexion" onmouseover="this.style.color='#DC2626';this.style.borderColor='#FECACA';this.style.background='#FEF2F2';" onmouseout="this.style.color='#64748B';this.style.borderColor='#E2E8F0';this.style.background='none';">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </header>

        <style>
            @media (min-width: 640px) { .topbar-view-site { display:inline-flex !important; } }
        </style>

        <!-- Flash Modal (style carte) -->
        @if(session('success') || session('error') || session('info'))
            @php
                $msgType = session('success') ? 'success' : (session('error') ? 'error' : 'info');
                $msg = session($msgType);
            @endphp
            <x-flash-modal :type="$msgType" :message="$msg" />
        @endif

        <!-- Page Content -->
        <main style="flex:1;padding:16px;">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer style="background:white;border-top:1px solid #E5E7EB;padding:12px 24px;text-align:center;font-size:12px;color:#9CA3AF;">
            Kardafrica Admin &copy; {{ date('Y') }}
        </footer>
    </div>

    <script>
        // Sidebar state
        var desktopOpen = true;

        function toggleSidebar() {
            if (window.innerWidth >= 1024) {
                desktopOpen = !desktopOpen;
                document.getElementById('desktop-sidebar').classList.toggle('collapsed', !desktopOpen);
                document.getElementById('main-wrapper').classList.toggle('pushed-off', !desktopOpen);
            } else {
                openMobileSidebar();
            }
        }

        function openMobileSidebar() {
            var el = document.getElementById('mobile-overlay');
            el.classList.add('open');
        }

        function closeMobileSidebar() {
            var el = document.getElementById('mobile-overlay');
            el.classList.remove('open');
        }
    </script>
</body>
</html>
