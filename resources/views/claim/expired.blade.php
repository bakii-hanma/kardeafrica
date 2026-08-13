<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lien expiré — KardAfrica</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo/FAVCON-KARDAFRICA-.png') }}">
    @vite(['resources/css/app.css'])
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh; min-height: 100dvh;
            font-family: 'Inter', system-ui, sans-serif;
            display: flex; align-items: center; justify-content: center;
            padding: 24px 16px;
            background:
              radial-gradient(circle at 20% 0%, rgba(244,63,94,0.18) 0%, transparent 45%),
              linear-gradient(135deg, #060A14 0%, #0F172A 50%, #1E293B 100%);
            color: white;
        }
        .card { max-width: 440px; width: 100%;
                background: rgba(15,23,42,0.65); backdrop-filter: blur(18px);
                border: 1px solid rgba(255,255,255,0.10); border-radius: 22px;
                padding: 36px 28px; text-align: center; }
        .ico { width: 64px; height: 64px; border-radius: 18px;
               background: rgba(244,63,94,0.12); border: 1px solid rgba(244,63,94,0.30);
               display: inline-flex; align-items: center; justify-content: center;
               color: #FCA5A5; margin-bottom: 16px; }
        h1 { font-family: 'Space Grotesk','Inter',sans-serif; font-size: 24px; font-weight: 800;
             letter-spacing: -0.01em; margin-bottom: 8px; }
        p { color: #94A3B8; font-size: 14px; line-height: 1.6; }
        .ref { font-family: monospace; font-size: 12px; color: #64748B;
               background: rgba(255,255,255,0.05); padding: 8px 12px;
               border-radius: 9px; display: inline-block; margin-top: 16px; }
        a { color: #5EEAD4; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <div class="ico">
            <svg style="width:30px;height:30px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        @php $raison = $raison ?? ($legacy ?? false ? 'legacy' : 'expire'); @endphp

        @if ($raison === 'deja_ouvert')
            <h1>Ce lien a déjà été ouvert</h1>
            <p>
                Pour ta sécurité, un lien de récupération ne s'ouvre qu'une fois.
                Tes cartes restent dans ton compte KardAfrica, au numéro qui a reçu
                ce message.
            </p>
        @elseif ($raison === 'legacy')
            <h1>Ce lien n'est plus valable</h1>
            <p>
                Les anciens liens de récupération ont été remplacés par des liens
                à usage unique. Demande à ton vendeur de te renvoyer tes cartes,
                ou connecte-toi avec ton numéro WhatsApp.
            </p>
        @elseif ($raison === 'invalide')
            <h1>Lien invalide</h1>
            <p>Ce lien ne correspond à aucune commande en attente de remise.</p>
        @else
            <h1>Ce lien a expiré</h1>
            <p>Le délai de récupération de cette commande est dépassé. Contacte le vendeur ou notre support pour récupérer tes cartes.</p>
        @endif

        @if (!empty($order))
            <div class="ref">#{{ $order->order_number }}</div>
        @endif

        <p style="margin-top:16px;">
            <a href="{{ route('client.whatsapp.login') }}"
               style="display:inline-block;padding:12px 20px;background:#25D366;color:#06281F;border-radius:12px;font-weight:800;text-decoration:none;font-size:14px;">
                💬 Retrouver mes cartes avec WhatsApp
            </a>
        </p>
        <p style="margin-top:18px;font-size:13px;">
            Besoin d'aide ? <a href="mailto:hello@kardafrica.com">hello@kardafrica.com</a>
        </p>
    </div>
</body>
</html>
