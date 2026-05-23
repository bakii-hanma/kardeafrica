<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion admin — KardAfrica</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}">
    @vite(['resources/css/app.css'])
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh; min-height: 100dvh;
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
            color: #ffffff;
            display: flex;
            position: relative;
            overflow: hidden;
        }

        /* ============== Left visual ============== */
        .ka-side {
            display: none;
            flex: 1; min-width: 0;
            position: relative;
            background:
                radial-gradient(circle at 20% 0%, rgba(78,205,196,0.22) 0%, transparent 45%),
                radial-gradient(circle at 80% 100%, rgba(124,58,237,0.18) 0%, transparent 45%),
                radial-gradient(circle at 50% 60%, rgba(59,130,246,0.10) 0%, transparent 60%),
                linear-gradient(135deg, #060A14 0%, #0F172A 50%, #1E293B 100%);
            padding: 56px;
            flex-direction: column; justify-content: space-between;
            overflow: hidden;
        }
        @media (min-width: 1024px) { .ka-side { display: flex; } }

        .ka-side::before {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background-image:
                linear-gradient(to right, rgba(255,255,255,0.04) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255,255,255,0.04) 1px, transparent 1px);
            background-size: 48px 48px;
            -webkit-mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 30%, transparent 100%);
                    mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 30%, transparent 100%);
        }

        .ka-logo {
            position: relative;
            display: flex; align-items: center; gap: 12px;
        }
        .ka-logo img { width: 44px; height: 44px; }
        .ka-logo-text {
            font-family: 'Space Grotesk', 'Inter', sans-serif;
            font-size: 20px; font-weight: 700; letter-spacing: -0.01em;
        }

        /* Stage : zone propre dédiée aux cartes (n'écrase PLUS le texte) */
        .ka-cards-stage {
            position: relative;
            width: 100%;
            height: 280px;
            display: flex; align-items: center; justify-content: center;
            margin: 24px 0;
            perspective: 1200px;
        }

        @keyframes ka-card-floatA { 0%,100% { transform: rotate(-10deg) translate(-120px, 0); } 50% { transform: rotate(-10deg) translate(-120px, -10px); } }
        @keyframes ka-card-floatB { 0%,100% { transform: rotate(0deg)   translate(0, 0); }     50% { transform: rotate(0deg)   translate(0, -16px); } }
        @keyframes ka-card-floatC { 0%,100% { transform: rotate(10deg)  translate(120px, 20px); } 50% { transform: rotate(10deg)  translate(120px, 8px); } }

        .ka-deco-card {
            position: absolute;
            width: 220px; height: 140px;
            border-radius: 18px;
            padding: 18px;
            color: white;
            box-shadow: 0 30px 50px -15px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.18);
            display: flex; flex-direction: column; justify-content: space-between;
            cursor: pointer;
            transform-style: preserve-3d;
            will-change: transform, box-shadow;
            transition: box-shadow .35s ease, filter .35s ease;
            overflow: hidden;
        }
        /* Halo intérieur subtil */
        .ka-deco-card::before {
            content: '';
            position: absolute; top: -30px; right: -30px;
            width: 100px; height: 100px; border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.18) 0%, transparent 70%);
            pointer-events: none;
        }
        /* Effet shine sur hover */
        .ka-deco-card::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(105deg, transparent 30%, rgba(255,255,255,0.18) 50%, transparent 70%);
            transform: translateX(-100%);
            transition: transform .8s cubic-bezier(.22, 1, .36, 1);
            pointer-events: none;
        }
        .ka-deco-card:hover::after { transform: translateX(100%); }
        .ka-deco-card:hover {
            box-shadow: 0 40px 70px -15px rgba(0,0,0,0.65), inset 0 1px 0 rgba(255,255,255,0.25);
            filter: brightness(1.08);
            z-index: 10 !important;
        }
        .ka-deco-card.c1 {
            background: linear-gradient(135deg, #E50914 0%, #B8060F 100%);
            animation: ka-card-floatA 6.5s ease-in-out infinite;
            z-index: 1;
        }
        .ka-deco-card.c2 {
            background: linear-gradient(135deg, #1DB954 0%, #1AA44A 100%);
            animation: ka-card-floatB 7s ease-in-out infinite .2s;
            z-index: 3;
        }
        .ka-deco-card.c3 {
            background: linear-gradient(135deg, #003791 0%, #0058D6 100%);
            animation: ka-card-floatC 7.5s ease-in-out infinite .4s;
            z-index: 2;
        }
        /* Le hover/parallax/click est piloté en JS pour parallax mouse-tracking
           — pas de règle CSS ici, le JS gère tout via card.style.transform */

        .ka-deco-card .label {
            font-size: 9px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; opacity: 0.85;
        }
        .ka-deco-card .name {
            font-family: 'Space Grotesk', 'Inter', sans-serif;
            font-size: 18px; font-weight: 700; margin-top: 2px;
        }
        .ka-deco-card .price {
            font-family: 'Space Grotesk', 'Inter', sans-serif;
            font-size: 22px; font-weight: 800; font-variant-numeric: tabular-nums;
        }
        /* Puce gold de carte cadeau */
        .ka-deco-card .chip {
            position: absolute; bottom: 14px; right: 14px;
            width: 32px; height: 22px; border-radius: 5px;
            background: linear-gradient(135deg, #FCD34D, #F59E0B);
            border: 1px solid rgba(255,255,255,0.4);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.5);
        }

        .ka-side-info {
            position: relative;
            max-width: 420px;
        }
        .ka-side-info h2 {
            font-family: 'Space Grotesk', 'Inter', sans-serif;
            font-weight: 700;
            font-size: 30px; line-height: 1.15; letter-spacing: -0.02em;
            color: white; margin-bottom: 12px;
        }
        .ka-side-info h2 .highlight {
            background: linear-gradient(120deg, #4ECDC4 0%, #44A08D 50%, #5EEAD4 100%);
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .ka-side-info p { color: #94A3B8; font-size: 14px; line-height: 1.6; }

        .ka-side-foot {
            position: relative;
            display: flex; align-items: center; gap: 8px;
            font-size: 11px; color: #64748B;
        }
        .ka-side-foot .dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: #34D399;
            box-shadow: 0 0 8px rgba(52,211,153,0.6);
        }

        /* ============== Right form panel ============== */
        .ka-form-wrap {
            flex: 1; min-width: 0;
            background: #ffffff;
            display: flex; align-items: center; justify-content: center;
            padding: 32px 24px;
        }
        .ka-form {
            width: 100%; max-width: 420px;
        }

        .ka-mobile-logo {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            margin-bottom: 36px;
        }
        @media (min-width: 1024px) { .ka-mobile-logo { display: none; } }
        .ka-mobile-logo img { width: 36px; height: 36px; }
        .ka-mobile-logo-text {
            font-family: 'Space Grotesk', 'Inter', sans-serif;
            font-size: 18px; font-weight: 700; color: #0F172A;
        }

        .ka-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 12px; border-radius: 9999px;
            background: rgba(68,160,141,0.10); border: 1px solid rgba(68,160,141,0.25);
            margin-bottom: 16px;
        }
        .ka-badge-dot {
            width: 6px; height: 6px; border-radius: 50%; background: #44A08D;
        }
        .ka-badge-text {
            font-size: 10px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #44A08D;
        }

        .ka-form h1 {
            font-family: 'Space Grotesk', 'Inter', sans-serif;
            font-weight: 700;
            font-size: 32px; line-height: 1.1; letter-spacing: -0.02em;
            color: #0F172A; margin-bottom: 8px;
        }
        .ka-form .sub {
            font-size: 14px; color: #64748B; margin-bottom: 32px;
        }

        .ka-field { margin-bottom: 16px; }
        .ka-label {
            display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;
        }
        .ka-input-wrap { position: relative; }
        .ka-input-icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            width: 18px; height: 18px; color: #94A3B8; pointer-events: none;
        }
        .ka-input {
            width: 100%; padding: 12px 16px 12px 44px;
            background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px;
            font-size: 14px; color: #0F172A; outline: none;
            transition: all .15s ease;
        }
        .ka-input:focus { background: white; border-color: #44A08D; box-shadow: 0 0 0 3px rgba(68,160,141,0.15); }
        .ka-input.has-error { border-color: #F43F5E; background: #FEF2F2; }

        .ka-pw-toggle {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            width: 30px; height: 30px; border: 0; border-radius: 8px;
            background: transparent; color: #94A3B8; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }
        .ka-pw-toggle:hover { background: #F1F5F9; color: #475569; }

        .ka-row {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 24px;
        }
        .ka-remember {
            display: inline-flex; align-items: center; gap: 8px; cursor: pointer;
            font-size: 13px; color: #475569;
        }
        .ka-remember input { width: 16px; height: 16px; accent-color: #44A08D; }
        .ka-link { font-size: 13px; color: #44A08D; text-decoration: none; font-weight: 600; }
        .ka-link:hover { color: #3d9180; text-decoration: underline; }

        .ka-submit {
            width: 100%; padding: 13px 20px;
            border: 0; border-radius: 12px;
            background: linear-gradient(135deg, #44A08D, #4ECDC4);
            color: white; font-weight: 700; font-size: 14px;
            cursor: pointer;
            box-shadow: 0 14px 30px -8px rgba(78,205,196,0.55), inset 0 1px 0 rgba(255,255,255,0.3);
            transition: transform .15s ease;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        }
        .ka-submit:hover { transform: translateY(-1px); }
        .ka-submit:active { transform: translateY(0); }

        .ka-error {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 14px; margin-bottom: 18px;
            background: #FEF2F2; border: 1px solid #FECACA; border-radius: 12px;
            color: #991B1B; font-size: 13px;
        }
        .ka-error svg { width: 18px; height: 18px; flex-shrink: 0; color: #F43F5E; }

        .ka-foot {
            margin-top: 28px; padding-top: 24px;
            border-top: 1px solid #F1F5F9;
            font-size: 12px; color: #94A3B8;
            text-align: center; line-height: 1.6;
        }
        .ka-foot a { color: #44A08D; text-decoration: none; font-weight: 600; }
        .ka-foot a:hover { text-decoration: underline; }

        @if(\App\Models\AppSetting::isMaintenanceMode())
        .ka-maint {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 14px; margin-bottom: 16px;
            background: rgba(244,63,94,0.08);
            border: 1px solid rgba(244,63,94,0.25);
            border-radius: 12px;
            font-size: 12px; color: #991B1B; font-weight: 600;
        }
        .ka-maint .dot {
            position: relative; width: 8px; height: 8px; border-radius: 50%; background: #F43F5E;
            box-shadow: 0 0 0 0 rgba(244,63,94,0.5);
            animation: ka-maint-pulse 2s ease-out infinite;
        }
        @keyframes ka-maint-pulse {
            0%   { box-shadow: 0 0 0 0 rgba(244,63,94,0.5); }
            70%  { box-shadow: 0 0 0 8px rgba(244,63,94,0); }
            100% { box-shadow: 0 0 0 0 rgba(244,63,94,0); }
        }
        @endif
    </style>
</head>
<body>

    {{-- ============================== LEFT VISUAL ============================== --}}
    <aside class="ka-side">
        <div class="ka-logo">
            <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" alt="KardAfrica">
            <span class="ka-logo-text">KardAfrica · Admin</span>
        </div>

        {{-- Stage : zone propre dédiée aux cartes (parallax mouse + tilt 3D) --}}
        <div class="ka-cards-stage" id="kaCardsStage">
            <div class="ka-deco-card c1" data-tilt>
                <div>
                    <div class="label">Streaming</div>
                    <div class="name">Netflix</div>
                </div>
                <div class="price">25 000 <span style="font-size:10px;opacity:.7;">FCFA</span></div>
                <span class="chip"></span>
            </div>
            <div class="ka-deco-card c2" data-tilt>
                <div>
                    <div class="label">Music</div>
                    <div class="name">Spotify</div>
                </div>
                <div class="price">5 990 <span style="font-size:10px;opacity:.7;">FCFA</span></div>
                <span class="chip"></span>
            </div>
            <div class="ka-deco-card c3" data-tilt>
                <div>
                    <div class="label">Gaming</div>
                    <div class="name">PlayStation</div>
                </div>
                <div class="price">10 000 <span style="font-size:10px;opacity:.7;">FCFA</span></div>
                <span class="chip"></span>
            </div>
        </div>

        <div class="ka-side-info">
            <h2>Le back-office <span class="highlight">qui pilote l'Afrique</span> en un clic.</h2>
            <p>Catalogue, commandes, livraisons, abonnements Daywatch, paramètres système — tout depuis une seule interface.</p>
        </div>

        <div class="ka-side-foot">
            <span class="dot"></span>
            <span>v{{ config('app.name') }} · {{ ucfirst(config('app.env')) }} · {{ now()->format('Y') }}</span>
        </div>
    </aside>

    {{-- Interactions cartes : parallax mouse + tilt 3D + flip au click --}}
    <script>
        (function () {
            const stage = document.getElementById('kaCardsStage');
            if (!stage) return;
            const cards = Array.from(stage.querySelectorAll('[data-tilt]'));

            // Position de base de chaque carte (rotation/translate hérités du keyframe)
            // On override en pause d'animation pendant le hover.
            const baseTransforms = {
                c1: 'rotate(-10deg) translate(-120px, 0)',
                c2: 'rotate(0deg)   translate(0, 0)',
                c3: 'rotate(10deg)  translate(120px, 20px)',
            };
            const depth = { c1: 14, c2: 22, c3: 16 }; // parallax par carte

            let rafId = null;
            let lastMouse = { x: 0, y: 0 };

            stage.addEventListener('mouseenter', () => {
                cards.forEach(card => { card.style.animationPlayState = 'paused'; });
            });

            stage.addEventListener('mousemove', (e) => {
                const rect = stage.getBoundingClientRect();
                lastMouse.x = (e.clientX - rect.left - rect.width / 2) / (rect.width / 2);
                lastMouse.y = (e.clientY - rect.top  - rect.height / 2) / (rect.height / 2);

                if (rafId) return;
                rafId = requestAnimationFrame(() => {
                    cards.forEach((card) => {
                        const cls = card.classList.contains('c1') ? 'c1'
                                  : card.classList.contains('c3') ? 'c3' : 'c2';
                        const d = depth[cls];
                        const dx = -lastMouse.x * d;
                        const dy = -lastMouse.y * d;
                        const rotX = (-lastMouse.y * 7).toFixed(2);
                        const rotY = ( lastMouse.x * 7).toFixed(2);
                        card.style.transition = 'transform .12s ease-out';
                        card.style.transform = `${baseTransforms[cls]} translate(${dx}px, ${dy}px) rotateX(${rotX}deg) rotateY(${rotY}deg) scale(1.03)`;
                    });
                    rafId = null;
                });
            });

            stage.addEventListener('mouseleave', () => {
                cards.forEach((card) => {
                    card.style.transition = 'transform .6s cubic-bezier(.22, 1, .36, 1)';
                    card.style.transform = '';                       // → reprend le keyframe
                    setTimeout(() => { card.style.animationPlayState = ''; }, 50);
                });
            });

            // Click feedback : flip 360° rapide sur la carte cliquée
            cards.forEach((card) => {
                card.addEventListener('click', () => {
                    const cls = card.classList.contains('c1') ? 'c1'
                              : card.classList.contains('c3') ? 'c3' : 'c2';
                    card.style.animationPlayState = 'paused';
                    card.style.transition = 'transform .7s cubic-bezier(.22, 1, .36, 1)';
                    card.style.transform = `${baseTransforms[cls]} rotateY(360deg) scale(1.08)`;
                    setTimeout(() => {
                        card.style.transform = '';
                        card.style.animationPlayState = '';
                    }, 720);
                });
            });
        })();
    </script>

    {{-- ============================== RIGHT FORM ============================== --}}
    <div class="ka-form-wrap">
        <form method="POST" action="{{ route('admin.login.attempt') }}" class="ka-form" id="loginForm">
            @csrf

            <div class="ka-mobile-logo">
                <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" alt="KardAfrica">
                <span class="ka-mobile-logo-text">KardAfrica</span>
            </div>

            <div class="ka-badge">
                <span class="ka-badge-dot"></span>
                <span class="ka-badge-text">Espace administrateur</span>
            </div>

            <h1>Bonjour 👋</h1>
            <p class="sub">Connecte-toi pour accéder au back-office.</p>

            @if(\App\Models\AppSetting::isMaintenanceMode())
                <div class="ka-maint">
                    <span class="dot"></span>
                    <span>Site public en maintenance — l'accès admin reste actif.</span>
                </div>
            @endif

            @if($errors->any())
                <div class="ka-error">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <div class="ka-field">
                <label class="ka-label" for="email">E-mail</label>
                <div class="ka-input-wrap">
                    <svg class="ka-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <input id="email" name="email" type="email" required autocomplete="email" autofocus
                           value="{{ old('email') }}"
                           placeholder="vous@kardafrica.com"
                           class="ka-input {{ $errors->has('email') ? 'has-error' : '' }}">
                </div>
            </div>

            <div class="ka-field">
                <label class="ka-label" for="password">Mot de passe</label>
                <div class="ka-input-wrap">
                    <svg class="ka-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                           placeholder="••••••••"
                           class="ka-input {{ $errors->has('password') ? 'has-error' : '' }}">
                    <button type="button" class="ka-pw-toggle" onclick="togglePassword()" aria-label="Afficher / masquer le mot de passe">
                        <svg id="eyeOpen" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="width:18px;height:18px;"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg id="eyeClosed" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="width:18px;height:18px;display:none;"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                </div>
            </div>

            <div class="ka-row">
                <label class="ka-remember">
                    <input type="checkbox" name="remember" value="1">
                    Se souvenir de moi
                </label>
                <a href="{{ route('home') }}" class="ka-link">← Retour au site</a>
            </div>

            <button type="submit" class="ka-submit">
                Se connecter
                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </button>

            <div class="ka-foot">
                Accès réservé aux administrateurs.<br>
                Besoin d'aide ? <a href="mailto:hello@kardafrica.com">hello@kardafrica.com</a>
            </div>
        </form>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const open = document.getElementById('eyeOpen');
            const closed = document.getElementById('eyeClosed');
            if (input.type === 'password') {
                input.type = 'text';
                open.style.display = 'none';
                closed.style.display = '';
            } else {
                input.type = 'password';
                open.style.display = '';
                closed.style.display = 'none';
            }
        }
    </script>

</body>
</html>
