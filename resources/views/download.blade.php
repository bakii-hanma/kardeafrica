@extends('layouts.app')

@section('title', 'Télécharger l\'app Kardafrica — Android')

@section('content')
<style>
    /* ===== Animations (alignées sur la page About) ===== */
    @keyframes dl-floatA { 0%,100% { transform: translateY(0) rotate(-9deg); } 50% { transform: translateY(-16px) rotate(-9deg); } }
    @keyframes dl-floatB { 0%,100% { transform: translateY(0) rotate(6deg);  } 50% { transform: translateY(-22px) rotate(6deg);  } }
    @keyframes dl-floatC { 0%,100% { transform: translateY(0) rotate(-3deg); } 50% { transform: translateY(-12px) rotate(-3deg); } }
    @keyframes dl-floatD { 0%,100% { transform: translateY(0) rotate(11deg); } 50% { transform: translateY(-18px) rotate(11deg); } }
    @keyframes dl-shine  { 0% { transform: translateX(-120%) skewX(-20deg); } 100% { transform: translateX(320%) skewX(-20deg); } }
    @keyframes dl-pulse  { 0% { box-shadow: 0 0 0 0 rgba(78,205,196,0.55); } 100% { box-shadow: 0 0 0 16px rgba(78,205,196,0); } }
    @keyframes dl-rise   { from { opacity: 0; transform: translateY(22px); } to { opacity: 1; transform: translateY(0); } }

    .dl-float-a { animation: dl-floatA 6.5s ease-in-out infinite; }
    .dl-float-b { animation: dl-floatB 7.2s ease-in-out infinite .25s; }
    .dl-float-c { animation: dl-floatC 5.8s ease-in-out infinite .45s; }
    .dl-float-d { animation: dl-floatD 7.6s ease-in-out infinite .65s; }
    .dl-rise    { animation: dl-rise .7s cubic-bezier(.22,1,.36,1) both; }
    .dl-rise-2  { animation: dl-rise .7s .12s cubic-bezier(.22,1,.36,1) both; }
    .dl-rise-3  { animation: dl-rise .7s .24s cubic-bezier(.22,1,.36,1) both; }

    .dl-card { position: relative; overflow: hidden; }
    .dl-card::after {
        content: ''; position: absolute; inset: 0;
        background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.20) 50%, transparent 100%);
        animation: dl-shine 3.6s ease-in-out infinite; pointer-events: none;
    }
    .dl-text-grad {
        background: linear-gradient(120deg, #4ECDC4 0%, #44A08D 50%, #5EEAD4 100%);
        -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
    }
    .dl-grid-bg {
        background-image:
            linear-gradient(to right, rgba(255,255,255,0.04) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(255,255,255,0.04) 1px, transparent 1px);
        background-size: 42px 42px;
        -webkit-mask-image: radial-gradient(ellipse 75% 60% at 50% 35%, black 30%, transparent 100%);
                mask-image: radial-gradient(ellipse 75% 60% at 50% 35%, black 30%, transparent 100%);
    }
    .dl-dlbtn { transition: transform .2s ease, box-shadow .2s ease; }
    .dl-dlbtn:hover { transform: translateY(-3px); box-shadow: 0 22px 44px -12px rgba(78,205,196,0.65), inset 0 1px 0 rgba(255,255,255,0.45); }
    .dl-step { transition: transform .3s cubic-bezier(.22,1,.36,1), box-shadow .3s ease, border-color .3s ease; }
    .dl-step:hover { transform: translateY(-6px); box-shadow: 0 24px 48px -20px rgba(15,23,42,0.22); border-color: rgba(78,205,196,0.5); }
</style>

<div style="background: #ffffff; font-family: 'Inter','Figtree',sans-serif;">

    {{-- ============================================================
         HERO sombre — texte + CTA à gauche, téléphone animé à droite
       ============================================================ --}}
    <section style="position: relative; overflow: hidden;
                    background:
                      radial-gradient(circle at 15% 0%, rgba(78,205,196,0.22) 0%, transparent 45%),
                      radial-gradient(circle at 88% 90%, rgba(124,58,237,0.18) 0%, transparent 45%),
                      radial-gradient(circle at 50% 55%, rgba(59,130,246,0.10) 0%, transparent 60%),
                      linear-gradient(135deg, #060A14 0%, #0F172A 50%, #1E293B 100%);
                    padding: 88px 16px 72px;">

        <div class="dl-grid-bg" style="position: absolute; inset: 0; pointer-events: none;"></div>
        <div style="position: absolute; top: -120px; left: 50%; transform: translateX(-50%);
                    width: 720px; height: 420px;
                    background: radial-gradient(circle, rgba(78,205,196,0.28) 0%, transparent 70%);
                    filter: blur(60px); pointer-events: none;"></div>

        <div style="position: relative; max-width: 1150px; margin: 0 auto;
                    display: flex; flex-wrap: wrap; align-items: center; gap: 48px 40px;">

            {{-- LEFT : copy + CTA --}}
            <div class="dl-rise" style="flex: 1 1 440px; min-width: 0;">
                <div style="display: inline-flex; align-items: center; gap: 8px;
                            padding: 6px 14px; border-radius: 9999px;
                            background: rgba(78,205,196,0.10); border: 1px solid rgba(78,205,196,0.28); margin-bottom: 26px;">
                    <span style="position: relative; display: flex; width: 8px; height: 8px;">
                        <span class="animate-ping" style="position: absolute; inset: 0; border-radius: 50%; background: #4ECDC4; opacity: 0.6;"></span>
                        <span style="position: relative; width: 8px; height: 8px; border-radius: 50%; background: #4ECDC4;"></span>
                    </span>
                    <span style="font-size: 11px; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; color: #5EEAD4;">Application Android</span>
                </div>

                <h1 style="font-family: 'Space Grotesk','Inter',sans-serif; font-weight: 700;
                           font-size: clamp(34px, 5vw, 62px); line-height: 1.03; letter-spacing: -0.02em; color: #fff; margin: 0;">
                    Kardafrica,<br/><span class="dl-text-grad">dans ta poche.</span>
                </h1>

                <p style="margin: 22px 0 0; max-width: 480px; font-size: clamp(15px,1.4vw,18px); line-height: 1.65; color: #94A3B8;">
                    Achète et gère tes cartes cadeaux Netflix, Steam, PlayStation, Nintendo et 300+ marques —
                    paiement Mobile Money, livraison instantanée. Directement depuis ton téléphone.
                </p>

                {{-- Gros bouton de téléchargement (style app-landing) --}}
                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 14px; margin-top: 34px;">
                    <a href="{{ $apkUrl }}" download class="dl-dlbtn"
                       style="display: inline-flex; align-items: center; gap: 14px;
                              padding: 15px 26px; border-radius: 16px;
                              background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%);
                              color: #ffffff; text-decoration: none;
                              box-shadow: 0 16px 34px -10px rgba(78,205,196,0.55), inset 0 1px 0 rgba(255,255,255,0.45);">
                        <svg style="width: 30px; height: 30px;" viewBox="0 0 24 24" fill="#ffffff"><path d="M17.6 9.48l1.84-3.18a.4.4 0 00-.15-.55.4.4 0 00-.54.15l-1.86 3.22a11.4 11.4 0 00-8.78 0L6.25 5.9a.4.4 0 00-.54-.15.4.4 0 00-.15.55L7.4 9.48A10.8 10.8 0 002 18h20a10.8 10.8 0 00-5.4-8.52zM7 15.25a1 1 0 110-2 1 1 0 010 2zm10 0a1 1 0 110-2 1 1 0 010 2z"/></svg>
                        <span style="display: flex; flex-direction: column; line-height: 1.15;">
                            <span style="font-size: 11px; font-weight: 600; opacity: 0.7;">Télécharger pour</span>
                            <span style="font-family: 'Space Grotesk','Inter',sans-serif; font-size: 20px; font-weight: 800;">Android · APK</span>
                        </span>
                    </a>
                    <div style="display: flex; flex-direction: column; gap: 3px;">
                        <span style="font-size: 12px; font-weight: 600; color: #CBD5E1;">Version {{ $version }} · ~75 Mo</span>
                        <span style="font-size: 11px; color: #64748B;">Android 6.0+ · Installation directe</span>
                    </div>
                </div>

                {{-- Trust row --}}
                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 24px 16px; margin-top: 34px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="display: flex;">
                            @foreach (['#44A08D', '#7C3AED', '#EA580C', '#3B82F6'] as $i => $c)
                                <div style="width: 26px; height: 26px; border-radius: 50%; background: {{ $c }}; border: 2px solid #0F172A; margin-left: {{ $i === 0 ? '0' : '-8px' }};"></div>
                            @endforeach
                        </div>
                        <span style="font-size: 12px; font-weight: 600; color: #CBD5E1;">+50 000 utilisateurs</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        @for ($i = 0; $i < 5; $i++)
                            <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="#FBBF24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        @endfor
                        <span style="margin-left: 6px; font-size: 12px; font-weight: 600; color: #CBD5E1;">4.9/5</span>
                    </div>
                </div>
            </div>

            {{-- RIGHT : téléphone + cartes flottantes --}}
            <div class="dl-rise-2" style="flex: 1 1 360px; min-width: 0; display: flex; justify-content: center;">
                <div style="position: relative; width: 300px; height: 420px;">

                    {{-- Cartes cadeaux flottantes autour du téléphone --}}
                    @php
                        $floatCards = [
                            ['n' => 'Netflix',     'g' => 'linear-gradient(135deg,#E50914,#B8060F)', 's' => 'rgba(229,9,20,.5)',  'cls' => 'dl-float-a', 'pos' => 'top:6px;left:-46px;',    'z' => 3],
                            ['n' => 'Spotify',     'g' => 'linear-gradient(135deg,#1DB954,#1AA44A)', 's' => 'rgba(29,185,84,.5)', 'cls' => 'dl-float-b', 'pos' => 'top:150px;right:-52px;', 'z' => 3],
                            ['n' => 'PlayStation', 'g' => 'linear-gradient(135deg,#003791,#0058D6)', 's' => 'rgba(0,55,145,.5)',  'cls' => 'dl-float-c', 'pos' => 'bottom:34px;left:-40px;', 'z' => 3],
                            ['n' => 'Steam',       'g' => 'linear-gradient(135deg,#1B2838,#2A475E)', 's' => 'rgba(27,40,56,.55)', 'cls' => 'dl-float-d', 'pos' => 'bottom:-6px;right:-30px;','z' => 3],
                        ];
                    @endphp
                    @foreach ($floatCards as $fc)
                        <div class="dl-card {{ $fc['cls'] }}" style="position: absolute; {{ $fc['pos'] }} z-index: {{ $fc['z'] }};
                                    width: 128px; height: 80px; border-radius: 13px; padding: 11px;
                                    background: {{ $fc['g'] }}; color: #fff;
                                    box-shadow: 0 20px 34px -14px {{ $fc['s'] }}, inset 0 1px 0 rgba(255,255,255,0.18);
                                    display: flex; flex-direction: column; justify-content: space-between;">
                            <div style="font-family:'Space Grotesk','Inter',sans-serif; font-size: 13px; font-weight: 700;">{{ $fc['n'] }}</div>
                            <div style="font-size: 8px; font-family: monospace; opacity: 0.65;">**** {{ 1000 + $loop->index * 1111 }}</div>
                        </div>
                    @endforeach

                    {{-- Le téléphone --}}
                    <div style="position: absolute; inset: 0; z-index: 2; margin: 0 auto; width: 210px; height: 420px;
                                left: 0; right: 0;
                                background: linear-gradient(160deg, #1a2233, #0b1120); border-radius: 34px;
                                padding: 11px; box-shadow: 0 40px 80px -24px rgba(0,0,0,0.6), inset 0 0 0 1px rgba(255,255,255,0.06);">
                        {{-- écran --}}
                        <div style="width: 100%; height: 100%; border-radius: 24px; overflow: hidden;
                                    background: #FAFAF7; position: relative; display: flex; flex-direction: column;">
                            {{-- encoche --}}
                            <div style="position: absolute; top: 0; left: 50%; transform: translateX(-50%);
                                        width: 96px; height: 20px; background: #0b1120; border-radius: 0 0 14px 14px; z-index: 5;"></div>
                            {{-- header app --}}
                            <div style="padding: 26px 14px 12px; background: linear-gradient(135deg,#44A08D,#4ECDC4);">
                                <div style="color: #fff; font-family:'Space Grotesk','Inter',sans-serif; font-weight: 800; font-size: 15px;">Kardafrica</div>
                                <div style="color: rgba(255,255,255,0.85); font-size: 9px; margin-top: 2px;">Tes cartes cadeaux préférées</div>
                            </div>
                            {{-- mini cartes dans l'app --}}
                            <div style="padding: 12px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                @foreach ([
                                    ['Apple','linear-gradient(135deg,#1C1C1E,#000)'],
                                    ['Xbox','linear-gradient(135deg,#107C10,#0B5E0B)'],
                                    ['Nintendo','linear-gradient(135deg,#E60012,#B00010)'],
                                    ['Roblox','linear-gradient(135deg,#00A2FF,#0078BF)'],
                                ] as $mc)
                                    <div style="height: 52px; border-radius: 10px; background: {{ $mc[1] }};
                                                box-shadow: 0 6px 12px -6px rgba(0,0,0,0.4); padding: 8px; color: #fff;
                                                display: flex; align-items: flex-end; font-size: 10px; font-weight: 700;
                                                font-family:'Space Grotesk','Inter',sans-serif;">{{ $mc[0] }}</div>
                                @endforeach
                            </div>
                            {{-- barre du bas --}}
                            <div style="margin-top: auto; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #eee;">
                                @foreach (['#44A08D','#cbd5e1','#cbd5e1','#cbd5e1'] as $dot)
                                    <div style="width: 20px; height: 20px; border-radius: 7px; background: {{ $dot }}22; display:flex;align-items:center;justify-content:center;">
                                        <div style="width: 10px; height: 10px; border-radius: 3px; background: {{ $dot }};"></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================
         COMMENT INSTALLER — 4 étapes en cartes
       ============================================================ --}}
    <section style="max-width: 1000px; margin: 0 auto; padding: 72px 16px 40px;">
        <div style="text-align: center; margin-bottom: 40px;">
            <h2 style="font-family:'Space Grotesk','Inter',sans-serif; font-weight: 700; font-size: clamp(24px,3vw,38px); color: #0F172A; margin: 0; letter-spacing: -0.02em;">
                Installation en <span class="dl-text-grad">4 étapes</span>
            </h2>
            <p style="margin-top: 10px; color: #64748B; font-size: 15px;">Moins d'une minute, aucune inscription requise pour installer.</p>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px;">
            @foreach ([
                ['1', 'Télécharger', 'Appuie sur « Télécharger pour Android » en haut de page.', '#44A08D'],
                ['2', 'Ouvrir le fichier', 'Ouvre le fichier .apk depuis tes notifications ou tes téléchargements.', '#3B82F6'],
                ['3', 'Autoriser', 'Si demandé, autorise l\'installation depuis « sources inconnues ».', '#7C3AED'],
                ['4', 'Installer', 'Appuie sur Installer, puis ouvre l\'app et connecte-toi.', '#EA580C'],
            ] as $step)
                <div class="dl-step" style="background: #fff; border: 1px solid #EEF2F6; border-radius: 18px; padding: 22px;
                            box-shadow: 0 10px 30px -18px rgba(15,23,42,0.15);">
                    <div style="width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
                                background: linear-gradient(135deg, {{ $step[3] }}, {{ $step[3] }}cc);
                                color: #fff; font-family:'Space Grotesk','Inter',sans-serif; font-weight: 800; font-size: 18px;
                                box-shadow: 0 10px 20px -8px {{ $step[3] }}88;">{{ $step[0] }}</div>
                    <div style="margin-top: 16px; font-family:'Space Grotesk','Inter',sans-serif; font-weight: 700; font-size: 16px; color: #0F172A;">{{ $step[1] }}</div>
                    <div style="margin-top: 6px; font-size: 13px; line-height: 1.55; color: #64748B;">{{ $step[2] }}</div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============================================================
         ATOUTS
       ============================================================ --}}
    <section style="max-width: 1000px; margin: 0 auto; padding: 20px 16px 88px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 16px;">
            @foreach ([
                ['M13 10V3L4 14h7v7l9-11h-7z', 'Livraison instantanée', 'Reçois tes codes en quelques secondes après le paiement.', '#EA580C'],
                ['M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'Mobile Money', 'Paie avec Airtel ou Moov, sans carte bancaire internationale.', '#44A08D'],
                ['M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z', '300+ marques', 'Netflix, Steam, PlayStation, Nintendo, Apple, Spotify…', '#7C3AED'],
            ] as $f)
                <div style="background: linear-gradient(180deg,#fff,#F8FAFC); border: 1px solid #EEF2F6; border-radius: 18px; padding: 24px;">
                    <div style="width: 44px; height: 44px; border-radius: 13px; display: flex; align-items: center; justify-content: center;
                                background: linear-gradient(135deg, {{ $f[3] }}, {{ $f[3] }}cc); box-shadow: 0 10px 20px -8px {{ $f[3] }}88;">
                        <svg style="width: 22px; height: 22px; color: #fff;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $f[0] }}"/></svg>
                    </div>
                    <div style="margin-top: 16px; font-family:'Space Grotesk','Inter',sans-serif; font-weight: 700; font-size: 17px; color: #0F172A;">{{ $f[1] }}</div>
                    <div style="margin-top: 6px; font-size: 13.5px; line-height: 1.6; color: #64748B;">{{ $f[2] }}</div>
                </div>
            @endforeach
        </div>

        {{-- CTA final --}}
        <div style="margin-top: 40px; text-align: center;">
            <a href="{{ $apkUrl }}" download class="dl-dlbtn"
               style="display: inline-flex; align-items: center; gap: 12px; padding: 15px 30px; border-radius: 16px;
                      background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%); color: #ffffff; text-decoration: none; font-weight: 800;
                      font-family:'Space Grotesk','Inter',sans-serif; font-size: 17px;
                      box-shadow: 0 16px 34px -10px rgba(78,205,196,0.55), inset 0 1px 0 rgba(255,255,255,0.45);">
                <svg style="width: 22px; height: 22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                Télécharger l'app Android
            </a>
        </div>
    </section>
</div>
@endsection
