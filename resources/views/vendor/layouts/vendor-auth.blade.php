<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0F172A">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Espace vendeur') — KardAfrica</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900|space-grotesk:500,600,700&display=swap" rel="stylesheet" />

    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body {
            margin: 0; min-height: 100vh; min-height: 100dvh;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, #0F172A 0%, #1E293B 60%, #0F172A 100%);
            color: #0F172A;
            display: flex; align-items: center; justify-content: center;
            padding: 24px 16px;
            padding-top: max(24px, env(safe-area-inset-top));
            padding-bottom: max(24px, env(safe-area-inset-bottom));
        }
        .va-wrap { width: 100%; max-width: 420px; }
        .va-brand {
            display: flex; align-items: center; justify-content: center; gap: 10px;
            margin-bottom: 20px; text-decoration: none;
        }
        .va-brand img { width: 34px; height: 34px; }
        .va-brand-tag {
            font-size: 8px; font-weight: 700; letter-spacing: .18em;
            color: #5EEAD4; text-transform: uppercase; line-height: 1;
        }
        .va-brand-name {
            font-family: 'Space Grotesk', 'Inter', sans-serif;
            font-size: 15px; font-weight: 800; color: #fff; margin-top: 1px;
        }
        .va-card {
            background: #fff; border-radius: 20px;
            padding: 26px 22px;
            box-shadow: 0 24px 48px -12px rgba(0,0,0,.45);
            text-align: center;
        }
        .va-ico {
            width: 54px; height: 54px; margin: 0 auto 14px;
            border-radius: 16px;
            background: linear-gradient(135deg, #44A08D, #4ECDC4);
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            box-shadow: 0 10px 20px -8px rgba(78,205,196,.6);
        }
        .va-ico svg { width: 26px; height: 26px; }
        .va-title {
            font-family: 'Space Grotesk', 'Inter', sans-serif;
            font-size: 21px; font-weight: 800; color: #0F172A; margin: 0;
        }
        .va-lead { font-size: 13.5px; color: #64748B; line-height: 1.5; margin: 8px 0 0; }
        .va-lead strong { color: #334155; font-weight: 700; }
        .va-status {
            background: #ECFDF5; border: 1px solid #6EE7B7; color: #047857;
            border-radius: 12px; padding: 11px 14px;
            font-size: 12.5px; font-weight: 600; margin-top: 16px; text-align: left;
        }
        .va-form { margin-top: 20px; text-align: left; }
        .va-label {
            display: block; font-size: 11px; font-weight: 800;
            letter-spacing: .08em; text-transform: uppercase; color: #64748B; margin-bottom: 6px;
        }
        .va-input {
            width: 100%; min-height: 50px; padding: 0 14px;
            border: 1px solid #CBD5E1; border-radius: 12px;
            font-family: inherit; font-size: 15px; color: #0F172A;
            background: #fff;
        }
        .va-input:focus { outline: 3px solid #4ECDC4; outline-offset: 1px; border-color: #44A08D; }
        .va-input.has-error { border-color: #FCA5A5; background: #FEF2F2; }
        .va-input + .va-label { margin-top: 14px; }
        .va-error { color: #BE123C; font-size: 12px; margin: 6px 0 0; }
        .va-hint { font-size: 12px; color: #64748B; margin: 6px 0 0; line-height: 1.45; }
        .va-submit {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; min-height: 52px; margin-top: 18px;
            background: linear-gradient(135deg, #44A08D, #4ECDC4);
            color: #fff; border: 0; border-radius: 13px;
            font-family: inherit; font-size: 14.5px; font-weight: 800; cursor: pointer;
        }
        .va-submit svg { width: 17px; height: 17px; }
        .va-submit:hover { filter: brightness(1.05); }
        .va-back {
            display: inline-block; margin-top: 16px;
            font-size: 13px; font-weight: 600; color: #64748B; text-decoration: none;
        }
        .va-back:hover { color: #0F172A; }
        a:focus-visible, button:focus-visible { outline: 3px solid #4ECDC4; outline-offset: 2px; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="va-wrap">
        <a href="{{ route('vendor.login') }}" class="va-brand">
            <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" alt="">
            <span>
                <span class="va-brand-tag">Vendeur</span>
                <span class="va-brand-name" style="display:block;">KardAfrica</span>
            </span>
        </a>

        @yield('content')
    </div>
</body>
</html>
