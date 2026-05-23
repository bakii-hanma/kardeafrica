<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Pause technique — KardAfrica</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}">
    {{-- IMPORTANT : pas d'auto-redirect ici (créait une boucle infinie quand le whitelist
         admin tombait en cache OPCache). On limite au seul refresh public toutes les 60s. --}}
    <meta http-equiv="refresh" content="60">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
            color: #ffffff;
            display: flex; align-items: center; justify-content: center;
            padding: 24px 16px;
            overflow: hidden;
            position: relative;
            background:
                radial-gradient(circle at 20% 0%, rgba(78,205,196,0.18) 0%, transparent 45%),
                radial-gradient(circle at 80% 100%, rgba(124,58,237,0.20) 0%, transparent 45%),
                radial-gradient(circle at 50% 50%, rgba(59,130,246,0.10) 0%, transparent 60%),
                linear-gradient(135deg, #060A14 0%, #0F172A 50%, #1E293B 100%);
        }

        /* ============== Background grid ============== */
        body::before {
            content: ''; position: fixed; inset: 0; pointer-events: none;
            background-image:
                linear-gradient(to right, rgba(255,255,255,0.04) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255,255,255,0.04) 1px, transparent 1px);
            background-size: 48px 48px;
            -webkit-mask-image: radial-gradient(ellipse 70% 60% at 50% 50%, black 30%, transparent 100%);
                    mask-image: radial-gradient(ellipse 70% 60% at 50% 50%, black 30%, transparent 100%);
        }

        /* ============== Falling raindrops ============== */
        .rain {
            position: fixed; inset: 0; pointer-events: none; z-index: 0;
        }
        .drop {
            position: absolute; top: -10%;
            width: 1.5px; height: 24px;
            background: linear-gradient(to bottom, transparent, rgba(78,205,196,0.6));
            border-radius: 9999px;
            animation: rain-fall linear infinite;
        }
        @keyframes rain-fall {
            from { transform: translateY(-50px); opacity: 0; }
            10%  { opacity: 1; }
            to   { transform: translateY(110vh); opacity: 0; }
        }

        /* ============== Container ============== */
        main {
            position: relative; z-index: 2;
            max-width: 540px; width: 100%;
            text-align: center;
        }

        /* ============== Logo strip ============== */
        .brand {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 6px 14px; border-radius: 9999px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.10);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            font-size: 11px; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase;
            color: #5EEAD4; margin-bottom: 32px;
        }
        .brand .pulse {
            position: relative; display: flex; width: 8px; height: 8px;
        }
        .brand .pulse::before {
            content: ''; position: absolute; inset: 0; border-radius: 50%;
            background: #F43F5E; opacity: 0.6;
            animation: brand-pulse 2s ease-out infinite;
        }
        .brand .pulse::after {
            content: ''; position: absolute; inset: 0; border-radius: 50%;
            background: #F43F5E;
        }
        @keyframes brand-pulse {
            0%   { transform: scale(1);   opacity: 0.6; }
            70%  { transform: scale(2.5); opacity: 0; }
            100% { transform: scale(2.5); opacity: 0; }
        }

        /* ============== Sad gift card ============== */
        .card-stage {
            position: relative;
            margin: 0 auto 36px;
            width: 240px; height: 160px;
            perspective: 1000px;
        }
        .card-shadow {
            position: absolute;
            bottom: -30px; left: 50%;
            width: 80%; height: 16px;
            background: radial-gradient(ellipse at center, rgba(0,0,0,0.45) 0%, transparent 70%);
            transform: translateX(-50%);
            filter: blur(6px);
            animation: shadow-sway 3.5s ease-in-out infinite;
        }
        @keyframes shadow-sway {
            0%, 100% { transform: translateX(-50%) scale(1); opacity: 0.5; }
            50%      { transform: translateX(-50%) scale(0.85); opacity: 0.35; }
        }

        .sad-card {
            position: relative;
            width: 100%; height: 100%;
            border-radius: 22px;
            background:
                radial-gradient(circle at 80% 0%, rgba(255,255,255,0.18) 0%, transparent 45%),
                linear-gradient(135deg, #44A08D 0%, #4ECDC4 100%);
            box-shadow: 0 30px 60px -15px rgba(78,205,196,0.45),
                        inset 0 1px 0 rgba(255,255,255,0.25);
            transform-style: preserve-3d;
            animation: card-sway 3.5s ease-in-out infinite;
            transform-origin: center center;
        }
        @keyframes card-sway {
            0%, 100% { transform: rotate(-2deg) translateY(0); }
            50%      { transform: rotate(2deg)  translateY(-6px); }
        }

        /* Card pattern */
        .sad-card::before {
            content: ''; position: absolute; inset: 0; border-radius: 22px;
            background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.15) 1.2px, transparent 0);
            background-size: 18px 18px;
            opacity: 0.4;
        }

        /* Eyes */
        .eyes {
            position: absolute; top: 38px; left: 0; right: 0;
            display: flex; align-items: center; justify-content: center; gap: 32px;
            z-index: 2;
        }
        .eye {
            position: relative;
            width: 18px; height: 26px;
            background: #0F172A;
            border-radius: 50%;
            overflow: hidden;
        }
        .eye::after {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(to bottom, transparent 0%, transparent 95%, #0F172A 95%, #0F172A 100%);
            animation: blink 4.5s infinite;
        }
        @keyframes blink {
            0%, 92%, 100% { transform: scaleY(1); }
            95%           { transform: scaleY(0); }
        }
        /* Tear */
        .tear {
            position: absolute;
            top: 30px; left: 50%; transform: translateX(-50%) translateX(-26px);
            width: 6px; height: 9px;
            background: linear-gradient(180deg, rgba(78,205,196,0.95), rgba(255,255,255,0.95));
            border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
            opacity: 0;
            animation: tear-fall 3.8s ease-in-out infinite;
            animation-delay: 1s;
            z-index: 1;
        }
        @keyframes tear-fall {
            0%, 100% { transform: translateX(-26px) translateY(0);  opacity: 0; }
            10%      { opacity: 1; }
            70%      { transform: translateX(-26px) translateY(60px); opacity: 1; }
            90%      { transform: translateX(-26px) translateY(80px); opacity: 0; }
        }

        /* Mouth (sad) */
        .mouth {
            position: absolute;
            top: 90px; left: 50%; transform: translateX(-50%);
            width: 32px; height: 16px;
            border: 3px solid #0F172A;
            border-top: none;
            border-right: none;
            border-left: none;
            border-radius: 0 0 50% 50% / 0 0 100% 100%;
            transform: translateX(-50%) rotate(180deg);
            z-index: 2;
        }

        /* Card strip / bottom number */
        .card-bottom {
            position: absolute;
            bottom: 12px; left: 14px; right: 14px;
            display: flex; align-items: center; justify-content: space-between;
            font-family: ui-monospace, monospace;
            font-size: 9px;
            color: rgba(15,23,42,0.55);
            letter-spacing: 0.15em;
            z-index: 2;
        }
        .card-bottom .label {
            font-family: 'Space Grotesk', 'Inter', sans-serif;
            font-weight: 800; font-size: 11px;
            color: rgba(15,23,42,0.7);
        }

        /* ============== Text ============== */
        h1 {
            font-family: 'Space Grotesk', 'Inter', sans-serif;
            font-weight: 700;
            font-size: clamp(36px, 6vw, 56px);
            letter-spacing: -0.02em;
            line-height: 1.05;
            margin-bottom: 16px;
        }
        .gradient-text {
            background: linear-gradient(120deg, #4ECDC4 0%, #44A08D 50%, #5EEAD4 100%);
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .desc {
            color: #94A3B8;
            font-size: clamp(15px, 1.4vw, 17px);
            line-height: 1.6;
            margin: 0 auto 32px;
            max-width: 420px;
        }

        /* ============== Status pill ============== */
        .status {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 12px 20px; border-radius: 9999px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            font-size: 13px; font-weight: 600;
            color: #CBD5E1;
            margin-bottom: 24px;
        }
        .status .spinner {
            width: 14px; height: 14px;
            border: 2px solid rgba(78,205,196,0.25);
            border-top-color: #4ECDC4;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ============== Hint card ============== */
        .hint {
            margin-top: 16px;
            padding: 14px 18px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px;
            font-size: 12px;
            color: #94A3B8;
            line-height: 1.55;
        }
        .hint strong { color: #ffffff; font-weight: 600; }

        /* ============== Retry button ============== */
        .retry {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 24px; border-radius: 12px;
            background: linear-gradient(135deg, #4ECDC4, #44A08D);
            color: #0F172A; font-weight: 700; font-size: 14px;
            text-decoration: none; cursor: pointer; border: 0;
            box-shadow: 0 14px 30px -8px rgba(78,205,196,0.55), inset 0 1px 0 rgba(255,255,255,0.4);
            transition: transform .2s ease;
            margin-top: 4px;
        }
        .retry:hover { transform: translateY(-2px); }
        .retry:active { transform: translateY(0); }

        /* ============== Floating support ============== */
        .support-row {
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid rgba(255,255,255,0.08);
            font-size: 12px;
            color: #64748B;
        }
        .support-row a { color: #5EEAD4; text-decoration: none; font-weight: 600; }
        .support-row a:hover { color: #ffffff; }

        @media (max-width: 540px) {
            .card-stage { width: 200px; height: 134px; }
            .eyes { top: 32px; gap: 26px; }
            .eye { width: 15px; height: 22px; }
            .mouth { top: 75px; width: 26px; height: 13px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .sad-card, .card-shadow, .tear, .eye::after, .drop { animation: none; }
        }
    </style>
</head>
<body>

    {{-- Raindrops decor --}}
    <div class="rain" aria-hidden="true">
        @for ($i = 0; $i < 25; $i++)
            <span class="drop" style="
                left: {{ rand(0, 100) }}%;
                animation-duration: {{ (rand(15, 35) / 10) }}s;
                animation-delay: -{{ (rand(0, 50) / 10) }}s;
                opacity: {{ rand(3, 8) / 10 }};
            "></span>
        @endfor
    </div>

    <main>
        <div class="brand">
            <span class="pulse"></span>
            Maintenance
        </div>

        <div class="card-stage">
            <div class="card-shadow"></div>
            <div class="sad-card">
                <div class="eyes">
                    <div class="eye"></div>
                    <div class="eye"></div>
                </div>
                <div class="tear"></div>
                <div class="mouth"></div>
                <div class="card-bottom">
                    <span class="label">KARDAFRICA</span>
                    <span>**** ****</span>
                </div>
            </div>
        </div>

        <h1>
            Pause technique <br/>
            <span class="gradient-text">on revient vite.</span>
        </h1>

        <p class="desc">
            Notre équipe procède à une mise à jour du site. Toutes vos données et commandes sont en sécurité — on revient en pleine forme dans quelques minutes.
        </p>

        <div class="status">
            <span class="spinner"></span>
            <span>Maintenance en cours · Réessai automatique dans 60 s</span>
        </div>

        <div>
            <a href="#" class="retry" onclick="event.preventDefault(); location.reload();">
                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Réessayer maintenant
            </a>
        </div>

        <div class="hint">
            💡 <strong>Astuce :</strong> Si tu as déjà passé une commande, son traitement reprendra automatiquement à la remise en ligne.
        </div>

        <div class="support-row">
            Une urgence ? Écris-nous à <a href="mailto:hello@kardafrica.com">hello@kardafrica.com</a>
        </div>
    </main>

</body>
</html>
