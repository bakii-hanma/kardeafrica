<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace propriétaire — KardAfrica</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}">
    @vite(['resources/css/app.css'])
    <style>
        *,*::before,*::after { box-sizing:border-box; margin:0; padding:0; }
        body {
            min-height:100vh; min-height:100dvh;
            font-family:'Inter',system-ui,-apple-system,sans-serif;
            color:#fff;
            display:flex; position:relative; overflow:hidden;
            background:
                radial-gradient(circle at 20% 0%, rgba(78,205,196,0.20) 0%, transparent 50%),
                radial-gradient(circle at 80% 100%, rgba(68,160,141,0.18) 0%, transparent 50%),
                linear-gradient(135deg,#060A14 0%,#0F172A 50%,#1E293B 100%);
        }
        body::before {
            content:''; position:absolute; inset:0; pointer-events:none;
            background-image:
                linear-gradient(to right, rgba(255,255,255,0.04) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255,255,255,0.04) 1px, transparent 1px);
            background-size: 48px 48px;
            -webkit-mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 30%, transparent 100%);
                    mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 30%, transparent 100%);
        }

        .ka-form {
            position:relative; z-index:1;
            margin:auto;
            width:100%; max-width:440px;
            padding:32px;
        }
        .ka-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 22px;
            padding: 36px 32px;
            box-shadow: 0 30px 80px -25px rgba(0,0,0,0.6);
        }
        .ka-brand { display:flex; align-items:center; gap:10px; margin-bottom:24px; }
        .ka-brand img { width:38px; height:38px; }
        .ka-brand-text { font-family:'Space Grotesk','Inter',sans-serif; font-size:17px; font-weight:700; letter-spacing:-0.01em; }
        .ka-brand-tag { font-size:8px; font-weight:700; letter-spacing:.18em; text-transform:uppercase; color:#5EEAD4; margin-top:1px; }

        h1 { font-family:'Space Grotesk','Inter',sans-serif; font-size:24px; font-weight:800; letter-spacing:-0.02em; margin-bottom:6px; }
        .ka-sub { color:rgba(255,255,255,.62); font-size:13px; margin-bottom:24px; line-height:1.5; }

        .ka-field { margin-bottom:14px; }
        .ka-label { display:block; font-size:11px; font-weight:700; color:rgba(255,255,255,.7); text-transform:uppercase; letter-spacing:.06em; margin-bottom:6px; }
        .ka-input {
            width:100%; padding:12px 14px;
            background: rgba(255,255,255,0.06);
            border: 1.5px solid rgba(255,255,255,0.12);
            border-radius: 11px;
            color:#fff; font-size:14px; font-family:inherit;
            outline:none; transition: border-color .15s, background .15s;
        }
        .ka-input::placeholder { color: rgba(255,255,255,0.30); }
        .ka-input:focus { border-color:#5EEAD4; background: rgba(255,255,255,0.08); }
        .ka-error { background:rgba(220,38,38,.16); border:1px solid rgba(220,38,38,.32); color:#FCA5A5; padding:10px 12px; border-radius:10px; font-size:12px; margin-bottom:14px; font-weight:600; }

        .ka-row { display:flex; align-items:center; justify-content:space-between; margin: 6px 0 18px; font-size:12px; color:rgba(255,255,255,0.6); }
        .ka-check { display:inline-flex; align-items:center; gap:7px; cursor:pointer; }
        .ka-check input { accent-color:#44A08D; }

        .ka-btn {
            width:100%; padding:14px;
            background: linear-gradient(135deg,#44A08D,#4ECDC4);
            color:white; font-size:14px; font-weight:800;
            border:0; border-radius:12px; cursor:pointer;
            box-shadow: 0 10px 28px -8px rgba(78,205,196,.45);
            font-family:inherit;
        }
        .ka-btn:hover { filter: brightness(1.05); }

        .ka-hint { margin-top:18px; text-align:center; font-size:12px; color:rgba(255,255,255,.45); }
        .ka-hint a { color:#5EEAD4; text-decoration:none; font-weight:700; }
    </style>
</head>
<body>
    <div class="ka-form">
        <div class="ka-card">
            <div class="ka-brand">
                <img src="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}" alt="">
                <div>
                    <div class="ka-brand-text">KardAfrica</div>
                    <div class="ka-brand-tag">Espace propriétaire</div>
                </div>
            </div>

            <h1>Connexion</h1>
            <p class="ka-sub">Accède à ton dashboard pour suivre tes ventes et valider les cartes au comptoir.</p>

            @if($errors->any())
                <div class="ka-error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('owner.login.submit') }}">
                @csrf
                <div class="ka-field">
                    <label class="ka-label" for="email">Email</label>
                    <input id="email" type="email" name="email" class="ka-input"
                           value="{{ old('email') }}" placeholder="contact@…" required autofocus>
                </div>

                <div class="ka-field">
                    <label class="ka-label" for="password">Mot de passe</label>
                    <input id="password" type="password" name="password" class="ka-input"
                           placeholder="••••••••" required>
                </div>

                <div class="ka-row">
                    <label class="ka-check">
                        <input type="checkbox" name="remember" value="1">
                        Se souvenir de moi
                    </label>
                </div>

                <button type="submit" class="ka-btn">Se connecter →</button>
            </form>

            <p class="ka-hint">
                Pas encore de compte ? Contacte l'admin Kardafrica pour qu'il te crée un accès propriétaire.
            </p>
        </div>
    </div>
</body>
</html>
