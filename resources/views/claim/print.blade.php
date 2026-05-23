<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Cartes #{{ $order->order_number }} — KardAfrica</title>
    @vite(['resources/css/app.css'])
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', system-ui, sans-serif; padding: 20mm; background: white; color: black; }
        h1 { font-family: 'Space Grotesk','Inter',sans-serif; font-size: 20px; margin-bottom: 4px; }
        .meta { font-size: 12px; color: #64748B; margin-bottom: 24px; }
        .gift-card {
            aspect-ratio: 1.586/1; max-width: 380px;
            border-radius: 14px; padding: 18px; color: white;
            display: flex; flex-direction: column; justify-content: space-between;
            margin-bottom: 16px;
            background: linear-gradient(135deg, var(--c1, #44A08D), var(--c2, #4ECDC4));
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            break-inside: avoid; page-break-inside: avoid;
        }
        .gift-card .gc-top { display: flex; justify-content: space-between; align-items: flex-start; }
        .gc-brand { font-size: 16px; font-weight: 800; }
        .gc-value { text-align: right; }
        .gc-value .v { font-size: 20px; font-weight: 800; }
        .gc-value .u { font-size: 10px; opacity: 0.85; }
        .gc-codes .lbl { font-size: 9px; font-weight: 700; letter-spacing: 0.10em; text-transform: uppercase; opacity: 0.75; }
        .gc-codes .val { font-family: monospace; font-size: 14px; font-weight: 700; word-break: break-all; }
        .gc-bottom { display: flex; justify-content: space-between; gap: 12px; }
        .gc-meta { font-size: 9px; opacity: 0.7; margin-top: 6px; }
    </style>
</head>
<body onload="window.print()">
    <h1>Cartes cadeaux KardAfrica</h1>
    <div class="meta">Commande #{{ $order->order_number }} · {{ $order->created_at->format('d/m/Y') }}</div>

    @php
        $palette = [
            ['#44A08D', '#4ECDC4'],
            ['#7C3AED', '#C026D3'],
            ['#F59E0B', '#EF4444'],
            ['#3B82F6', '#06B6D4'],
        ];
    @endphp
    @foreach($order->cards as $i => $card)
        @php $c = $palette[$i % count($palette)]; @endphp
        <div class="gift-card" style="--c1: {{ $c[0] }}; --c2: {{ $c[1] }};">
            <div class="gc-top">
                <div class="gc-brand">{{ $card->brand ?: $card->name }}</div>
                <div class="gc-value">
                    <div class="v">{{ number_format($card->face_value, 0, ',', ' ') }}</div>
                    <div class="u">{{ $card->currency ?: 'XAF' }}</div>
                </div>
            </div>
            <div class="gc-bottom">
                <div class="gc-codes">
                    <div class="lbl">Code</div>
                    <div class="val">{{ $card->card_code }}</div>
                </div>
                @if($card->pin)
                    <div class="gc-codes" style="text-align:right;">
                        <div class="lbl">PIN</div>
                        <div class="val">{{ $card->pin }}</div>
                    </div>
                @endif
            </div>
            <div class="gc-meta">
                @if($card->serial_number)S/N : {{ $card->serial_number }} · @endif
                @if($card->expiration_date)Exp : {{ $card->expiration_date->format('m/Y') }}@endif
            </div>
        </div>
    @endforeach
</body>
</html>
