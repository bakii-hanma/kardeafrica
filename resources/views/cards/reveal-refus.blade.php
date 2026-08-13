{{--
    Lien de révélation inutilisable : déjà ouvert, expiré, ou invalide.
    Le message ne distingue jamais « invalide » de « inexistant » côté secret,
    mais reste explicite sur ce que le client doit faire ensuite.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0B1220">
    <title>Lien expiré — KardAfrica</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 40px 16px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0B1220; color: #E2E8F0;
        }
        .wrap { max-width: 420px; margin: 0 auto; text-align: center; }
        .brand { font-size: 12px; font-weight: 800; letter-spacing: .12em;
                 text-transform: uppercase; color: #4ECDC4; }
        .ico { font-size: 44px; margin: 18px 0 8px; }
        h1 { font-size: 19px; font-weight: 800; color: #fff; margin: 0 0 10px; }
        p { font-size: 14px; color: #94A3B8; line-height: 1.6; margin: 0 0 14px; }
        .box { background: #111C2E; border: 1px solid #1E293B; border-radius: 14px;
               padding: 16px; margin-top: 20px; font-size: 13.5px; color: #CBD5E1;
               line-height: 1.6; text-align: left; }
        .box strong { color: #fff; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="brand">KardAfrica</div>

    @if ($raison === 'deja_vu')
        <div class="ico">🔒</div>
        <h1>Ce lien a déjà été ouvert</h1>
        <p>
            Pour ta sécurité, un lien de remise ne s'ouvre qu'une fois. Ta carte,
            elle, reste valable chez le commerçant et rattachée à ton compte
            KardAfrica.
        </p>
    @elseif ($raison === 'expire')
        <div class="ico">⏳</div>
        <h1>Ce lien a expiré</h1>
        <p>
            Les liens ne restent ouvrables que {{ \App\Models\MerchantCardPurchase::REVEAL_TTL_MINUTES }} minutes,
            pour éviter qu'ils traînent dans une conversation.
        </p>
    @else
        <div class="ico">⚠️</div>
        <h1>Lien invalide</h1>
        <p>Ce lien ne correspond à aucune carte en attente de remise.</p>
    @endif

    <div class="box">
        <strong>Que faire ?</strong><br>
        Retourne voir le revendeur chez qui tu as acheté la carte
        @if ($purchase->merchantCard?->name)
            « {{ $purchase->merchantCard->name }} »
        @endif
        — il peut faire renvoyer un nouveau lien, ou contacter le support KardAfrica.
    </div>
</div>
</body>
</html>
