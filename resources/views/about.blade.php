@extends('layouts.app')

@section('title', 'À propos — KardAfrica')
@section('meta_description', 'KardAfrica est la marketplace gabonaise des cartes cadeaux numériques : plus de 300 marques et des cartes de commerçants locaux, payables en Mobile Money, code reçu en 30 secondes.')

@section('content')
<style>
    /* ============ Animations ============ */
    @keyframes ka-floatA  { 0%,100% { transform: translateY(0)     rotate(-10deg); } 50% { transform: translateY(-14px) rotate(-10deg); } }
    @keyframes ka-floatB  { 0%,100% { transform: translateY(0)     rotate(-4deg);  } 50% { transform: translateY(-22px) rotate(-4deg);  } }
    @keyframes ka-floatC  { 0%,100% { transform: translateY(0)     rotate(2deg);   } 50% { transform: translateY(-10px) rotate(2deg);   } }
    @keyframes ka-floatD  { 0%,100% { transform: translateY(0)     rotate(8deg);   } 50% { transform: translateY(-18px) rotate(8deg);   } }
    @keyframes ka-floatE  { 0%,100% { transform: translateY(0)     rotate(12deg);  } 50% { transform: translateY(-12px) rotate(12deg);  } }
    @keyframes ka-pulse   { 0% { box-shadow: 0 0 0 0 rgba(78,205,196,0.6); } 100% { box-shadow: 0 0 0 18px rgba(78,205,196,0); } }
    @keyframes ka-scroll  { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
    @keyframes ka-shine   { 0% { transform: translateX(-100%) skewX(-20deg); } 100% { transform: translateX(300%) skewX(-20deg); } }

    .ka-float-a { animation: ka-floatA 6.5s ease-in-out infinite; }
    .ka-float-b { animation: ka-floatB 7.0s ease-in-out infinite .2s; }
    .ka-float-c { animation: ka-floatC 5.5s ease-in-out infinite .4s; }
    .ka-float-d { animation: ka-floatD 7.5s ease-in-out infinite .6s; }
    .ka-float-e { animation: ka-floatE 6.0s ease-in-out infinite .8s; }
    .ka-pulse-dot { animation: ka-pulse 2.4s cubic-bezier(0.4, 0, 0.2, 1) infinite; }
    .ka-marquee { display: flex; gap: 20px; width: max-content; animation: ka-scroll 40s linear infinite; }
    .ka-marquee:hover { animation-play-state: paused; }

    /* ============ Gift card visual ============ */
    .ka-card { position: relative; overflow: hidden; transition: transform .3s ease, box-shadow .3s ease; }
    .ka-card::after {
        content: ''; position: absolute; top: 0; left: 0; bottom: 0; width: 40%;
        background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.18) 50%, transparent 100%);
        animation: ka-shine 3.5s ease-in-out infinite;
        pointer-events: none;
    }
    .ka-card:hover { transform: translateY(-6px) scale(1.02); }

    /* ============ Cards reveal on hover ============ */
    .ka-tile { transition: transform .35s cubic-bezier(.22,1,.36,1), box-shadow .35s ease, border-color .35s ease; }
    .ka-tile:hover { transform: translateY(-6px); box-shadow: 0 30px 60px -25px rgba(15,23,42,0.25); }

    /* ============ Hero text gradient ============ */
    .ka-text-grad {
        background: linear-gradient(120deg, #4ECDC4 0%, #44A08D 50%, #5EEAD4 100%);
        -webkit-background-clip: text; background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* ============ Grid overlay ============ */
    .ka-grid-bg {
        background-image:
            linear-gradient(to right, rgba(255,255,255,0.04) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(255,255,255,0.04) 1px, transparent 1px);
        background-size: 48px 48px;
        -webkit-mask-image: radial-gradient(ellipse 80% 60% at 50% 30%, black 30%, transparent 100%);
                mask-image: radial-gradient(ellipse 80% 60% at 50% 30%, black 30%, transparent 100%);
    }

    /* ============ Stats counter underline ============ */
    .ka-stat-num { font-variant-numeric: tabular-nums; }
</style>

<div style="background: #ffffff; font-family: 'Inter','Figtree',sans-serif;">

    {{-- ============================================================
         1. HERO — Bandeau dégradé sombre + cartes cadeaux animées
       ============================================================ --}}
    <section style="position: relative; overflow: hidden;
                    background:
                      radial-gradient(circle at 18% 0%, rgba(78,205,196,0.22) 0%, transparent 45%),
                      radial-gradient(circle at 82% 100%, rgba(124,58,237,0.18) 0%, transparent 45%),
                      radial-gradient(circle at 50% 60%, rgba(59,130,246,0.10) 0%, transparent 60%),
                      linear-gradient(135deg, #060A14 0%, #0F172A 50%, #1E293B 100%);
                    padding: 96px 16px 64px;">

        {{-- Grid pattern --}}
        <div class="ka-grid-bg" style="position: absolute; inset: 0; pointer-events: none;"></div>

        {{-- Glow orb --}}
        <div style="position: absolute; top: -100px; left: 50%; transform: translateX(-50%);
                    width: 700px; height: 400px;
                    background: radial-gradient(circle, rgba(78,205,196,0.30) 0%, transparent 70%);
                    filter: blur(60px); pointer-events: none;"></div>

        <div style="position: relative; max-width: 1200px; margin: 0 auto; text-align: center;">

            {{-- Badge --}}
            <div style="display: inline-flex; align-items: center; gap: 8px;
                        padding: 6px 14px; border-radius: 9999px;
                        background: rgba(78,205,196,0.10); border: 1px solid rgba(78,205,196,0.28);
                        margin-bottom: 28px;">
                <span style="position: relative; display: flex; width: 8px; height: 8px;">
                    <span style="position: absolute; inset: 0; border-radius: 50%; background: #4ECDC4; opacity: 0.6;" class="animate-ping"></span>
                    <span style="position: relative; width: 8px; height: 8px; border-radius: 50%; background: #4ECDC4;"></span>
                </span>
                <span style="font-size: 11px; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; color: #5EEAD4;">Notre histoire</span>
            </div>

            {{-- Title --}}
            <h1 style="font-family: 'Space Grotesk','Inter',sans-serif; font-weight: 700;
                       font-size: clamp(36px, 6vw, 76px); line-height: 1.02; letter-spacing: -0.02em;
                       color: #ffffff; margin: 0 auto; max-width: 900px;">
                Le futur des cartes <br/>
                <span class="ka-text-grad">numériques en Afrique.</span>
            </h1>

            <p style="margin: 24px auto 0; max-width: 640px;
                      font-size: clamp(15px, 1.4vw, 18px); line-height: 1.65;
                      color: #94A3B8;">
                KardAfrica connecte des millions d'Africains aux meilleures plateformes mondiales :
                streaming, gaming, shopping. Une seule app, un paiement local, livraison instantanée.
            </p>

            {{-- CTAs --}}
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: center;
                        gap: 12px; margin-top: 36px;">
                <a href="{{ route('boutique') }}"
                   style="display: inline-flex; align-items: center; gap: 8px;
                          padding: 14px 26px; border-radius: 12px;
                          background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%);
                          color: #0F172A; font-weight: 600; font-size: 15px; text-decoration: none;
                          box-shadow: 0 14px 30px -8px rgba(78,205,196,0.55), inset 0 1px 0 rgba(255,255,255,0.45);
                          transition: transform .2s ease;"
                   onmouseover="this.style.transform='translateY(-2px)';"
                   onmouseout="this.style.transform='translateY(0)';">
                    Découvrir la boutique
                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
                <a href="{{ route('contact') }}"
                   style="display: inline-flex; align-items: center; gap: 8px;
                          padding: 14px 26px; border-radius: 12px;
                          background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.18);
                          color: #ffffff; font-weight: 600; font-size: 15px; text-decoration: none;
                          backdrop-filter: blur(10px);"
                   onmouseover="this.style.background='rgba(255,255,255,0.10)';this.style.borderColor='rgba(255,255,255,0.28)';"
                   onmouseout="this.style.background='rgba(255,255,255,0.06)';this.style.borderColor='rgba(255,255,255,0.18)';">
                    Nous contacter
                </a>
            </div>

            {{-- Trust row --}}
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: center;
                        gap: 28px 16px; margin-top: 40px;">
                {{-- Preuves VÉRIFIABLES uniquement (audit SEO : pas de faux compteurs) --}}
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="#4ECDC4" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span style="font-size: 12px; font-weight: 600; color: #CBD5E1;">Plus de 300 marques disponibles</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="#FBBF24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span style="font-size: 12px; font-weight: 600; color: #CBD5E1;">Code livré en 30 secondes · remboursé sous 24 h si invalide</span>
                </div>
            </div>
        </div>

        {{-- Bandeau de cartes cadeau animées (marquee infini) --}}
        <div style="position: relative; margin-top: 64px; padding: 0; overflow: hidden;
                    -webkit-mask-image: linear-gradient(90deg, transparent 0%, black 8%, black 92%, transparent 100%);
                            mask-image: linear-gradient(90deg, transparent 0%, black 8%, black 92%, transparent 100%);">
            <div class="ka-marquee" style="padding: 30px 0;">
                @php
                    $heroCards = [
                        ['name' => 'Netflix',    'sub' => 'Streaming',  'price' => '25 000', 'gradient' => 'linear-gradient(135deg, #E50914 0%, #B8060F 100%)',  'shadow' => 'rgba(229,9,20,0.45)'],
                        ['name' => 'Spotify',    'sub' => 'Music',      'price' => '5 990',  'gradient' => 'linear-gradient(135deg, #1DB954 0%, #1AA44A 100%)',  'shadow' => 'rgba(29,185,84,0.45)'],
                        ['name' => 'PlayStation','sub' => 'Gaming',     'price' => '10 000', 'gradient' => 'linear-gradient(135deg, #003791 0%, #0058D6 100%)',  'shadow' => 'rgba(0,55,145,0.45)'],
                        ['name' => 'Apple',      'sub' => 'iTunes',     'price' => '15 000', 'gradient' => 'linear-gradient(135deg, #1C1C1E 0%, #000000 100%)',  'shadow' => 'rgba(0,0,0,0.55)'],
                        ['name' => 'Steam',      'sub' => 'Gaming',     'price' => '20 000', 'gradient' => 'linear-gradient(135deg, #1B2838 0%, #2A475E 100%)',  'shadow' => 'rgba(27,40,56,0.55)'],
                        ['name' => 'Amazon',     'sub' => 'Shopping',   'price' => '25 000', 'gradient' => 'linear-gradient(135deg, #FF9900 0%, #E68A00 100%)',  'shadow' => 'rgba(255,153,0,0.45)'],
                        ['name' => 'Xbox',       'sub' => 'Gaming',     'price' => '15 000', 'gradient' => 'linear-gradient(135deg, #107C10 0%, #0B5E0B 100%)',  'shadow' => 'rgba(16,124,16,0.45)'],
                        ['name' => 'Roblox',     'sub' => 'Gaming',     'price' => '5 000',  'gradient' => 'linear-gradient(135deg, #00A2FF 0%, #0078BF 100%)',  'shadow' => 'rgba(0,162,255,0.45)'],
                    ];
                @endphp
                {{-- duplicate set for seamless loop --}}
                @foreach (array_merge($heroCards, $heroCards) as $card)
                    <div class="ka-card" style="flex-shrink: 0; width: 220px; height: 140px;
                                                 border-radius: 16px; padding: 18px;
                                                 background: {{ $card['gradient'] }};
                                                 box-shadow: 0 25px 40px -15px {{ $card['shadow'] }}, inset 0 1px 0 rgba(255,255,255,0.18);
                                                 color: #ffffff; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; opacity: 0.75;">{{ $card['sub'] }}</div>
                            <div style="font-family: 'Space Grotesk','Inter',sans-serif; font-size: 18px; font-weight: 700; margin-top: 2px;">{{ $card['name'] }}</div>
                        </div>
                        <div style="display: flex; align-items: end; justify-content: space-between;">
                            <div style="font-family: 'Space Grotesk','Inter',sans-serif; font-size: 20px; font-weight: 800; font-variant-numeric: tabular-nums;">
                                {{ $card['price'] }} <span style="font-size: 10px; font-weight: 500; opacity: 0.7;">FCFA</span>
                            </div>
                            <div style="font-size: 9px; font-family: monospace; opacity: 0.6;">**** {{ rand(1000, 9999) }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================================================
         2. STATS BAND — flottant
       ============================================================ --}}
    <section style="position: relative; margin-top: -32px; padding: 0 16px;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 8px;
                    background: linear-gradient(180deg, #ffffff 0%, #F8FAFC 100%);
                    border-radius: 24px;
                    box-shadow: 0 30px 80px -20px rgba(15,23,42,0.18), 0 0 0 1px rgba(15,23,42,0.04);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                @foreach ([
                    ['v' => '50K+', 'l' => 'Cartes livrées',     'c' => '#44A08D'],
                    ['v' => '15+',  'l' => 'Pays africains',     'c' => '#3B82F6'],
                    ['v' => '120+', 'l' => 'Marques partenaires','c' => '#7C3AED'],
                    ['v' => '24/7', 'l' => 'Support client',     'c' => '#EA580C'],
                ] as $i => $s)
                    <div style="text-align: center; padding: 28px 20px; position: relative;
                                {{ !$loop->last ? 'border-right: 1px solid #F1F5F9;' : '' }}">
                        <div class="ka-stat-num"
                             style="font-family: 'Space Grotesk','Inter',sans-serif; font-weight: 800;
                                    font-size: clamp(28px, 4vw, 48px); line-height: 1; letter-spacing: -0.02em;
                                    background: linear-gradient(135deg, {{ $s['c'] }} 0%, {{ $s['c'] }}AA 100%);
                                    -webkit-background-clip: text; background-clip: text;
                                    -webkit-text-fill-color: transparent;">
                            {{ $s['v'] }}
                        </div>
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em;
                                    color: #64748B; margin-top: 8px;">{{ $s['l'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================================================
         3. MISSION — 2 colonnes flex (texte + grille de cartes)
       ============================================================ --}}
    <section style="max-width: 1200px; margin: 0 auto; padding: 96px 16px;">
        <div style="display: flex; flex-wrap: wrap; gap: 60px; align-items: center;">

            {{-- LEFT : Texte --}}
            <div style="flex: 1 1 380px; min-width: 0;">
                <div style="display: inline-flex; align-items: center; gap: 8px;
                            padding: 4px 12px; border-radius: 9999px;
                            background: rgba(68,160,141,0.10); border: 1px solid rgba(68,160,141,0.22);
                            margin-bottom: 16px;">
                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #44A08D;"></span>
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: #44A08D;">Notre mission</span>
                </div>
                <h2 style="font-family: 'Space Grotesk','Inter',sans-serif; font-weight: 700;
                           font-size: clamp(28px, 3.5vw, 48px); line-height: 1.1; letter-spacing: -0.02em;
                           color: #0F172A; margin: 0;">
                    Démocratiser l'accès au <span class="ka-text-grad">divertissement digital</span>.
                </h2>
                <p style="margin-top: 24px; font-size: clamp(15px, 1.3vw, 17px); line-height: 1.7;
                          color: #475569;">
                    Sans carte bancaire internationale, sans VPN, sans casse-tête. Nous éliminons les frictions
                    pour que vous puissiez profiter de Netflix, Spotify, PlayStation et bien plus, payés en
                    Mobile Money local en quelques secondes.
                </p>
                <div style="margin-top: 28px; display: flex; flex-direction: column; gap: 12px;">
                    @foreach ([
                        ['icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'text' => 'Paiement Mobile Money (Airtel, Moov)', 'color' => '#44A08D'],
                        ['icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'text' => 'Livraison instantanée par e-mail', 'color' => '#EA580C'],
                        ['icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'text' => 'Support en français 24/7', 'color' => '#3B82F6'],
                    ] as $point)
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 36px; height: 36px; border-radius: 12px;
                                        background: linear-gradient(135deg, {{ $point['color'] }} 0%, {{ $point['color'] }}cc 100%);
                                        box-shadow: 0 8px 16px -6px {{ $point['color'] }}66;
                                        display: flex; align-items: center; justify-content: center;
                                        flex-shrink: 0;">
                                <svg style="width: 16px; height: 16px;" fill="none" stroke="#ffffff" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $point['icon'] }}"/></svg>
                            </div>
                            <span style="font-size: 15px; font-weight: 500; color: #1E293B;">{{ $point['text'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- RIGHT : grille de cartes animées --}}
            <div style="flex: 1 1 380px; min-width: 0; position: relative; min-height: 460px;">
                {{-- Glow décoratif --}}
                <div style="position: absolute; inset: 0;
                            background: radial-gradient(circle at 50% 50%, rgba(78,205,196,0.18) 0%, transparent 60%);
                            filter: blur(40px); pointer-events: none;"></div>

                <div style="position: relative; display: grid; grid-template-columns: repeat(2, 1fr);
                            grid-template-rows: repeat(2, 1fr); gap: 16px; min-height: 460px;">

                    {{-- Big card Netflix (span 2 columns) --}}
                    <div class="ka-card ka-float-b" style="grid-column: span 2; min-height: 200px;
                                                           padding: 22px; border-radius: 20px;
                                                           background: linear-gradient(135deg, #E50914 0%, #B8060F 100%);
                                                           box-shadow: 0 30px 50px -15px rgba(229,9,20,0.5), inset 0 1px 0 rgba(255,255,255,0.2);
                                                           color: #ffffff; display: flex; flex-direction: column; justify-content: space-between;">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div>
                                <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; opacity: 0.8;">Carte numérique</div>
                                <div style="font-family: 'Space Grotesk','Inter',sans-serif; font-size: 28px; font-weight: 700; margin-top: 4px;">Netflix</div>
                            </div>
                            <div style="width: 44px; height: 44px; border-radius: 14px;
                                        background: rgba(0,0,0,0.25);
                                        display: flex; align-items: center; justify-content: center;
                                        font-family: 'Space Grotesk','Inter',sans-serif; font-weight: 800; font-size: 22px;">N</div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: end;">
                            <div>
                                <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; opacity: 0.7;">Solde</div>
                                <div style="font-family: 'Space Grotesk','Inter',sans-serif; font-weight: 800;
                                            font-size: 32px; font-variant-numeric: tabular-nums; line-height: 1; margin-top: 4px;">
                                    25 000 <span style="font-size: 13px; font-weight: 500; opacity: 0.8;">FCFA</span>
                                </div>
                            </div>
                            <div style="font-size: 11px; font-family: monospace; opacity: 0.7;">**** 4829</div>
                        </div>
                    </div>

                    {{-- Spotify --}}
                    <div class="ka-card ka-float-c" style="padding: 18px; border-radius: 18px;
                                                            background: linear-gradient(135deg, #1DB954 0%, #1AA44A 100%);
                                                            box-shadow: 0 25px 40px -15px rgba(29,185,84,0.5), inset 0 1px 0 rgba(255,255,255,0.2);
                                                            color: #ffffff; display: flex; flex-direction: column; justify-content: space-between; min-height: 160px;">
                        <div>
                            <div style="font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; opacity: 0.8;">Music</div>
                            <div style="font-family: 'Space Grotesk','Inter',sans-serif; font-size: 22px; font-weight: 700; margin-top: 2px;">Spotify</div>
                        </div>
                        <div style="font-family: 'Space Grotesk','Inter',sans-serif; font-size: 22px; font-weight: 800; font-variant-numeric: tabular-nums;">
                            5 990 <span style="font-size: 11px; font-weight: 500; opacity: 0.7;">FCFA</span>
                        </div>
                    </div>

                    {{-- PlayStation --}}
                    <div class="ka-card ka-float-d" style="padding: 18px; border-radius: 18px;
                                                            background: linear-gradient(135deg, #003791 0%, #0058D6 100%);
                                                            box-shadow: 0 25px 40px -15px rgba(0,55,145,0.5), inset 0 1px 0 rgba(255,255,255,0.2);
                                                            color: #ffffff; display: flex; flex-direction: column; justify-content: space-between; min-height: 160px;">
                        <div>
                            <div style="font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; opacity: 0.8;">Gaming</div>
                            <div style="font-family: 'Space Grotesk','Inter',sans-serif; font-size: 18px; font-weight: 700; margin-top: 2px;">PlayStation</div>
                        </div>
                        <div style="font-family: 'Space Grotesk','Inter',sans-serif; font-size: 22px; font-weight: 800; font-variant-numeric: tabular-nums;">
                            10 000 <span style="font-size: 11px; font-weight: 500; opacity: 0.7;">FCFA</span>
                        </div>
                    </div>

                    {{-- Notification flottante --}}
                    <div class="ka-float-a" style="position: absolute; top: -16px; right: -8px;
                                                    padding: 10px 14px; border-radius: 14px;
                                                    background: rgba(255,255,255,0.96);
                                                    box-shadow: 0 20px 40px -10px rgba(15,23,42,0.25), 0 0 0 1px rgba(15,23,42,0.04);
                                                    backdrop-filter: blur(10px);
                                                    display: flex; align-items: center; gap: 10px;
                                                    z-index: 5;">
                        <div style="width: 32px; height: 32px; border-radius: 50%;
                                    background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%);
                                    display: flex; align-items: center; justify-content: center;">
                            <svg style="width: 14px; height: 14px;" fill="none" stroke="#fff" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <div style="font-size: 11px; font-weight: 700; color: #0F172A;">Carte livrée</div>
                            <div style="font-size: 10px; color: #64748B;">Il y a 2 secondes</div>
                        </div>
                    </div>

                    {{-- Chip "Live" --}}
                    <div class="ka-float-e" style="position: absolute; bottom: -14px; left: -10px;
                                                    padding: 8px 12px; border-radius: 9999px;
                                                    background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
                                                    box-shadow: 0 20px 40px -15px rgba(15,23,42,0.5);
                                                    display: flex; align-items: center; gap: 8px;
                                                    z-index: 5;">
                        <span class="ka-pulse-dot" style="width: 7px; height: 7px; border-radius: 50%; background: #5EEAD4;"></span>
                        <span style="font-size: 11px; font-weight: 700; color: #ffffff;">Code livré</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================
         4. VALEURS — premium grid
       ============================================================ --}}
    <section style="background: linear-gradient(180deg, #F8FAFC 0%, #ffffff 100%);
                    border-top: 1px solid #F1F5F9; border-bottom: 1px solid #F1F5F9;
                    padding: 80px 16px;">
        <div style="max-width: 1200px; margin: 0 auto;">
            <div style="text-align: center; max-width: 600px; margin: 0 auto 56px;">
                <div style="display: inline-flex; align-items: center; gap: 8px;
                            padding: 4px 12px; border-radius: 9999px;
                            background: rgba(68,160,141,0.10); border: 1px solid rgba(68,160,141,0.22);
                            margin-bottom: 12px;">
                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #44A08D;"></span>
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: #44A08D;">Nos valeurs</span>
                </div>
                <h2 style="font-family: 'Space Grotesk','Inter',sans-serif; font-weight: 700;
                           font-size: clamp(28px, 3.5vw, 48px); line-height: 1.1; letter-spacing: -0.02em;
                           color: #0F172A; margin: 0;">
                    Ce qui nous fait avancer
                </h2>
                <p style="margin-top: 16px; font-size: clamp(14px, 1.2vw, 17px); color: #64748B;">
                    Trois principes simples qui guident chacune de nos décisions.
                </p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                @foreach ([
                    ['title' => 'Confiance', 'desc' => 'Transactions sécurisées, codes 100% authentiques, garantie de remboursement.', 'color' => '#44A08D', 'color2' => '#4ECDC4', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'metric' => '99.8%', 'label' => 'Codes authentiques'],
                    ['title' => 'Rapidité',  'desc' => 'Livraison instantanée — votre code arrive dans votre boîte mail en quelques secondes.', 'color' => '#3B82F6', 'color2' => '#0EA5E9', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'metric' => '< 60s', 'label' => 'Délai moyen'],
                    ['title' => 'Local',     'desc' => 'Paiement en Mobile Money, support en français, équipe basée en Afrique.', 'color' => '#7C3AED', 'color2' => '#A78BFA', 'icon' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'metric' => '15+', 'label' => 'Pays couverts'],
                ] as $value)
                    <div class="ka-tile" style="position: relative; overflow: hidden; border-radius: 20px;
                                                background: #ffffff; border: 1px solid #E2E8F0;">
                        {{-- top gradient bar --}}
                        <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px;
                                    background: linear-gradient(90deg, {{ $value['color'] }} 0%, {{ $value['color2'] }} 100%);"></div>
                        {{-- glow blob --}}
                        <div style="position: absolute; top: -80px; right: -80px; width: 200px; height: 200px;
                                    border-radius: 50%; opacity: 0.18;
                                    background: radial-gradient(circle, {{ $value['color'] }} 0%, transparent 70%);
                                    filter: blur(30px);"></div>

                        <div style="position: relative; padding: 32px;">
                            <div style="width: 56px; height: 56px; border-radius: 16px;
                                        background: linear-gradient(135deg, {{ $value['color'] }} 0%, {{ $value['color2'] }} 100%);
                                        box-shadow: 0 14px 30px -8px {{ $value['color'] }}66;
                                        display: flex; align-items: center; justify-content: center;
                                        margin-bottom: 24px;">
                                <svg style="width: 24px; height: 24px;" fill="none" stroke="#ffffff" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $value['icon'] }}"/></svg>
                            </div>
                            <h3 style="font-family: 'Space Grotesk','Inter',sans-serif; font-size: 22px; font-weight: 700; color: #0F172A; margin: 0;">{{ $value['title'] }}</h3>
                            <p style="font-size: 14px; line-height: 1.65; color: #64748B; margin-top: 10px;">{{ $value['desc'] }}</p>

                            <div style="margin-top: 28px; padding-top: 20px; border-top: 1px solid #F1F5F9;
                                        display: flex; align-items: baseline; gap: 10px;">
                                <div style="font-family: 'Space Grotesk','Inter',sans-serif; font-size: 28px; font-weight: 800; font-variant-numeric: tabular-nums; color: {{ $value['color'] }};">{{ $value['metric'] }}</div>
                                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94A3B8;">{{ $value['label'] }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================================================
         5. TIMELINE — 4 cards
       ============================================================ --}}
    <section style="max-width: 1200px; margin: 0 auto; padding: 80px 16px;">
        <div style="text-align: center; max-width: 600px; margin: 0 auto 56px;">
            <div style="display: inline-flex; align-items: center; gap: 8px;
                        padding: 4px 12px; border-radius: 9999px;
                        background: rgba(68,160,141,0.10); border: 1px solid rgba(68,160,141,0.22);
                        margin-bottom: 12px;">
                <span style="width: 6px; height: 6px; border-radius: 50%; background: #44A08D;"></span>
                <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: #44A08D;">Notre parcours</span>
            </div>
            <h2 style="font-family: 'Space Grotesk','Inter',sans-serif; font-weight: 700;
                       font-size: clamp(28px, 3.5vw, 48px); line-height: 1.1; letter-spacing: -0.02em;
                       color: #0F172A; margin: 0;">
                D'une idée à un mouvement
            </h2>
            <p style="margin-top: 16px; font-size: clamp(14px, 1.2vw, 17px); color: #64748B;">
                Quatre étapes clés qui ont façonné KardAfrica.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px;">
            @foreach ([
                ['year' => '2024', 'title' => "L'idée",            'desc' => "Frustrés par l'absence de moyens d'achat de cartes Netflix/Spotify en Afrique, on lance le projet.",  'color' => '#94A3B8', 'icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z'],
                ['year' => '2025', 'title' => 'Premier prototype', 'desc' => 'Lancement de la beta avec 10 marques. 1 000 premiers utilisateurs en 3 mois.',                          'color' => '#3B82F6', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                ['year' => '2026', 'title' => 'Expansion',         'desc' => "KardAfrica devient l'app de référence. 50K+ cartes livrées dans 15 pays.",                            'color' => '#44A08D', 'icon' => 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9'],
                ['year' => '2027', 'title' => 'Le futur',          'desc' => 'Crypto, virements internationaux, marketplace de cartes-cadeaux. On ne fait que commencer.',          'color' => '#7C3AED', 'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'],
            ] as $i => $item)
                @php $isCurrent = $item['year'] === '2026'; @endphp
                <div class="ka-tile" style="position: relative; padding: 24px; border-radius: 20px;
                                            background: {{ $isCurrent ? 'linear-gradient(180deg, #0F172A 0%, #1E293B 100%)' : '#ffffff' }};
                                            border: 1px solid {{ $isCurrent ? 'rgba(255,255,255,0.10)' : '#E2E8F0' }};
                                            box-shadow: 0 4px 14px -4px rgba(15,23,42,0.06);">

                    <div style="display: inline-flex; align-items: center; gap: 6px;
                                padding: 4px 10px; border-radius: 9999px; margin-bottom: 18px;
                                background: {{ $isCurrent ? 'rgba(78,205,196,0.18)' : $item['color'] . '15' }};
                                color: {{ $isCurrent ? '#5EEAD4' : $item['color'] }};">
                        <span class="{{ $isCurrent ? 'ka-pulse-dot' : '' }}"
                              style="width: 6px; height: 6px; border-radius: 50%;
                                     background: {{ $isCurrent ? '#5EEAD4' : $item['color'] }};"></span>
                        <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em;">{{ $isCurrent ? 'En cours' : $item['year'] }}</span>
                    </div>

                    <div style="font-family: 'Space Grotesk','Inter',sans-serif; font-weight: 800;
                                font-size: 36px; font-variant-numeric: tabular-nums; line-height: 1;
                                color: {{ $isCurrent ? '#ffffff' : '#0F172A' }};">{{ $item['year'] }}</div>

                    <div style="width: 40px; height: 40px; border-radius: 12px; margin-top: 20px;
                                background: linear-gradient(135deg, {{ $item['color'] }} 0%, {{ $item['color'] }}cc 100%);
                                box-shadow: 0 8px 16px -6px {{ $item['color'] }}66;
                                display: flex; align-items: center; justify-content: center;">
                        <svg style="width: 18px; height: 18px;" fill="none" stroke="#ffffff" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                    </div>

                    <h3 style="font-family: 'Space Grotesk','Inter',sans-serif; font-size: 18px; font-weight: 700;
                               color: {{ $isCurrent ? '#ffffff' : '#0F172A' }}; margin: 16px 0 0;">{{ $item['title'] }}</h3>
                    <p style="font-size: 13px; line-height: 1.6; margin-top: 8px;
                              color: {{ $isCurrent ? '#94A3B8' : '#64748B' }};">{{ $item['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============================================================
         6. CTA — premium animated mesh
       ============================================================ --}}
    <section style="padding: 0 16px 96px;">
        <div style="position: relative; max-width: 1200px; margin: 0 auto; overflow: hidden;
                    border-radius: 32px;
                    background:
                        radial-gradient(circle at 0% 0%, rgba(78,205,196,0.30) 0%, transparent 40%),
                        radial-gradient(circle at 100% 100%, rgba(124,58,237,0.25) 0%, transparent 40%),
                        radial-gradient(circle at 50% 50%, rgba(59,130,246,0.10) 0%, transparent 60%),
                        linear-gradient(135deg, #0A0F1E 0%, #0F172A 50%, #1E293B 100%);
                    box-shadow: 0 40px 80px -20px rgba(15,23,42,0.4);">

            {{-- grid overlay --}}
            <div style="position: absolute; inset: 0; opacity: 0.05;
                        background-image: linear-gradient(to right, white 1px, transparent 1px), linear-gradient(to bottom, white 1px, transparent 1px);
                        background-size: 56px 56px;"></div>

            {{-- Floating blobs --}}
            <div class="ka-float-b" style="position: absolute; top: -80px; left: -80px; width: 256px; height: 256px;
                                            border-radius: 50%; background: radial-gradient(circle, rgba(78,205,196,0.4) 0%, transparent 70%);
                                            filter: blur(40px);"></div>
            <div class="ka-float-d" style="position: absolute; bottom: -80px; right: -80px; width: 288px; height: 288px;
                                            border-radius: 50%; background: radial-gradient(circle, rgba(124,58,237,0.3) 0%, transparent 70%);
                                            filter: blur(40px);"></div>

            <div style="position: relative; padding: 56px 32px; text-align: center;">
                <div style="display: inline-flex; align-items: center; gap: 8px;
                            padding: 6px 14px; border-radius: 9999px; margin-bottom: 28px;
                            background: rgba(78,205,196,0.10); border: 1px solid rgba(78,205,196,0.28);
                            backdrop-filter: blur(10px);">
                    <svg style="width: 12px; height: 12px;" fill="none" stroke="#5EEAD4" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.18em; color: #5EEAD4;">Démarrage en 2 min</span>
                </div>

                <h2 style="font-family: 'Space Grotesk','Inter',sans-serif; font-weight: 700;
                           font-size: clamp(28px, 4.5vw, 56px); line-height: 1.05; letter-spacing: -0.02em;
                           color: #ffffff; margin: 0;">
                    Prêt à commencer ?
                </h2>
                <p style="margin: 20px auto 0; max-width: 560px;
                          font-size: clamp(15px, 1.3vw, 18px); line-height: 1.65; color: #94A3B8;">
                    Découvrez la boutique, choisissez votre carte et payez en Mobile Money. Le code arrive en moins d'une minute.
                </p>

                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: center;
                            gap: 12px; margin-top: 36px;">
                    <a href="{{ route('boutique') }}"
                       style="display: inline-flex; align-items: center; gap: 8px;
                              padding: 14px 28px; border-radius: 12px;
                              background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%);
                              color: #0F172A; font-weight: 600; font-size: 15px; text-decoration: none;
                              box-shadow: 0 14px 30px -8px rgba(78,205,196,0.55), inset 0 1px 0 rgba(255,255,255,0.45);"
                       onmouseover="this.style.transform='translateY(-2px)';"
                       onmouseout="this.style.transform='translateY(0)';">
                        Voir les cartes
                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </a>
                    <a href="{{ route('support') }}"
                       style="display: inline-flex; align-items: center; gap: 8px;
                              padding: 14px 28px; border-radius: 12px;
                              background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.18);
                              color: #ffffff; font-weight: 600; font-size: 15px; text-decoration: none;
                              backdrop-filter: blur(10px);"
                       onmouseover="this.style.background='rgba(255,255,255,0.10)';"
                       onmouseout="this.style.background='rgba(255,255,255,0.06)';">
                        Centre d'aide
                    </a>
                </div>

                {{-- Brand row --}}
                <div style="margin-top: 48px; padding-top: 32px; border-top: 1px solid rgba(255,255,255,0.08);">
                    <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.2em;
                                color: #64748B; margin-bottom: 20px;">Plus de 120 marques disponibles</div>
                    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: center;
                                gap: 12px 32px;">
                        @foreach ([
                            ['name' => 'Netflix',     'color' => '#E50914'],
                            ['name' => 'Spotify',     'color' => '#1DB954'],
                            ['name' => 'PlayStation', 'color' => '#5EEAD4'],
                            ['name' => 'Steam',       'color' => '#94A3B8'],
                            ['name' => 'Apple',       'color' => '#ffffff'],
                            ['name' => 'Amazon',      'color' => '#FF9900'],
                        ] as $brand)
                            <span style="font-family: 'Space Grotesk','Inter',sans-serif; font-size: 16px; font-weight: 700;
                                         color: {{ $brand['color'] }}; opacity: 0.6; transition: opacity .2s;"
                                  onmouseover="this.style.opacity='1';"
                                  onmouseout="this.style.opacity='0.6';">{{ $brand['name'] }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>
@endsection
