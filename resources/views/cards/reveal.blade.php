{{--
    Révélation unique du code d'une Carte Gabon, sur le téléphone du client.
    Page volontairement autonome : pas de layout, pas de navigation, pas de
    ressource externe — elle s'ouvre sur un lien WhatsApp, souvent en 3G, et ne
    doit rien laisser derrière elle.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="no-referrer">
    <meta name="theme-color" content="#0B1220">
    <title>Ton code — KardAfrica</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 20px 16px 40px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0B1220; color: #E2E8F0;
            min-height: 100vh;
        }
        .wrap { max-width: 420px; margin: 0 auto; }
        .brand {
            font-size: 12px; font-weight: 800; letter-spacing: .12em;
            text-transform: uppercase; color: #4ECDC4; text-align: center;
        }
        h1 { font-size: 20px; font-weight: 800; color: #fff; text-align: center; margin: 6px 0 4px; }
        .sub { font-size: 13px; color: #94A3B8; text-align: center; margin: 0 0 20px; }

        /* L'avertissement passe AVANT le code : lu après, il est inutile. */
        .warn {
            background: #7C2D12; border: 1px solid #EA580C; border-radius: 14px;
            padding: 14px 16px; margin-bottom: 16px;
            font-size: 13.5px; font-weight: 700; color: #FFEDD5; line-height: 1.5;
        }
        .warn strong { color: #fff; }

        .card {
            background: #fff; border-radius: 18px; padding: 24px 20px;
            text-align: center; color: #0F172A;
        }
        .label {
            font-size: 11px; font-weight: 800; letter-spacing: .10em;
            text-transform: uppercase; color: #64748B;
        }
        .code {
            font-family: 'SFMono-Regular', Menlo, Consolas, monospace;
            font-size: 36px; font-weight: 800; letter-spacing: .10em;
            color: #0F172A; margin: 10px 0 2px; word-break: break-all;
        }
        .pin-row { margin-top: 18px; padding-top: 18px; border-top: 1px dashed #CBD5E1; }
        .pin {
            font-family: 'SFMono-Regular', Menlo, Consolas, monospace;
            font-size: 30px; font-weight: 800; letter-spacing: .22em; color: #0F172A;
            margin: 8px 0 0;
        }
        /* Ton apaisé quand la carte est déjà en sécurité dans le compte :
           l'alerte orange n'a plus lieu d'être, plus rien n'est perdu. */
        .warn--calme { background: #064E3B; border-color: #10B981; color: #D1FAE5; }
        .cta {
            display: block; margin-top: 16px; padding: 15px;
            background: #4ECDC4; color: #06281F; border-radius: 14px;
            font-size: 14.5px; font-weight: 800; text-align: center; text-decoration: none;
        }
        .meta { font-size: 12.5px; color: #64748B; margin-top: 18px; line-height: 1.6; }
        .meta strong { color: #0F172A; }

        .how {
            margin-top: 18px; background: #111C2E; border: 1px solid #1E293B;
            border-radius: 14px; padding: 16px;
        }
        .how h2 { font-size: 12px; font-weight: 800; letter-spacing: .08em;
                  text-transform: uppercase; color: #4ECDC4; margin: 0 0 10px; }
        .how ol { margin: 0; padding-left: 18px; font-size: 13.5px; color: #CBD5E1; line-height: 1.7; }
        .foot { font-size: 11.5px; color: #64748B; text-align: center; margin-top: 22px; line-height: 1.6; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="brand">KardAfrica</div>
    <h1>{{ $purchase->merchantCard?->name ?? 'Ta Carte Gabon' }}</h1>
    <p class="sub">{{ number_format((float) $purchase->amount, 0, ',', ' ') }} FCFA</p>

    @if (!empty($connecte))
        <div class="warn warn--calme">
            ✅ <strong>Cette carte est enregistrée dans ton compte.</strong>
            Tu peux revenir la consulter quand tu veux, avec ce même numéro WhatsApp.
        </div>
    @else
        <div class="warn">
            📸 <strong>Fais une capture d'écran maintenant.</strong>
            Ce lien ne s'ouvre qu'une seule fois.
        </div>
    @endif

    <div class="card">
        <div class="label">Code de la carte</div>
        <div class="code">{{ $code }}</div>

        <div class="pin-row">
            <div class="label">Code PIN</div>
            <div class="pin">{{ $pin }}</div>
        </div>

        <div class="meta">
            Valable jusqu'au <strong>{{ $purchase->expires_at?->format('d/m/Y') }}</strong><br>
            Solde : <strong>{{ number_format((float) $purchase->remaining_balance, 0, ',', ' ') }} FCFA</strong>
        </div>
    </div>

    @if (!empty($connecte))
        {{-- Le lien a prouvé la possession du numéro : le client est connecté,
             la carte est déjà dans son compte. Plus besoin d'une capture. --}}
        <a href="{{ route('cards.index') }}" class="cta">
            📇 Retrouver cette carte dans mon compte
        </a>
    @endif

    <div class="how">
        <h2>Comment l'utiliser</h2>
        <ol>
            <li>Rends-toi chez <strong>{{ $purchase->merchantCard?->name }}</strong>.</li>
            <li>Donne ton code à 8 chiffres.</li>
            <li>Saisis ou communique ton PIN pour valider le paiement.</li>
        </ol>
    </div>

    <p class="foot">
        Ne communique ton PIN à personne d'autre que le commerçant au moment de payer.<br>
        Aucun agent KardAfrica ne te le demandera.
    </p>
</div>
</body>
</html>
