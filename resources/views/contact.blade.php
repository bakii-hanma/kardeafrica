@extends('layouts.app')

@section('title', 'Contact — KardAfrica')

@section('content')
<style>
    @keyframes ka-spin { to { transform: rotate(360deg); } }
</style>
<div style="background: #ffffff; font-family: 'Inter','Figtree',sans-serif;">

    {{-- ============================================================
         HERO
       ============================================================ --}}
    <section style="position: relative; overflow: hidden;
                    background:
                      radial-gradient(circle at 25% 0%, rgba(78,205,196,0.18) 0%, transparent 45%),
                      radial-gradient(circle at 75% 100%, rgba(124,58,237,0.12) 0%, transparent 45%),
                      linear-gradient(135deg, #060A14 0%, #0F172A 50%, #1E293B 100%);
                    padding: 80px 16px 140px;">

        {{-- grid pattern --}}
        <div style="position: absolute; inset: 0; pointer-events: none;
                    background-image: linear-gradient(to right, rgba(255,255,255,0.04) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,0.04) 1px, transparent 1px);
                    background-size: 48px 48px;
                    -webkit-mask-image: radial-gradient(ellipse 80% 60% at 50% 30%, black 40%, transparent 100%);
                            mask-image: radial-gradient(ellipse 80% 60% at 50% 30%, black 40%, transparent 100%);"></div>

        {{-- ambient glow --}}
        <div style="position: absolute; top: -120px; left: 50%; transform: translateX(-50%);
                    width: 700px; height: 400px; border-radius: 50%; pointer-events: none;
                    background: radial-gradient(circle, rgba(78,205,196,0.20) 0%, transparent 70%); filter: blur(60px);"></div>

        <div style="position: relative; max-width: 900px; margin: 0 auto; text-align: center;">
            <div style="display: inline-flex; align-items: center; gap: 8px;
                        padding: 6px 14px; border-radius: 9999px;
                        background: rgba(16,185,129,0.10); border: 1px solid rgba(16,185,129,0.28);
                        margin-bottom: 28px;">
                <span style="position: relative; display: flex; width: 8px; height: 8px;">
                    <span style="position: absolute; inset: 0; border-radius: 50%; background: #34D399; opacity: 0.6;" class="animate-ping"></span>
                    <span style="position: relative; width: 8px; height: 8px; border-radius: 50%; background: #34D399;"></span>
                </span>
                <span style="font-size: 11px; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; color: #6EE7B7;">On vous répond en moins d'1h</span>
            </div>
            <h1 style="font-family: 'Space Grotesk','Inter',sans-serif; font-weight: 700;
                       font-size: clamp(36px, 6vw, 72px); line-height: 1.05; letter-spacing: -0.02em;
                       color: #ffffff; margin: 0;">
                Discutons de votre <br/>
                <span style="background: linear-gradient(120deg, #4ECDC4 0%, #44A08D 50%, #5EEAD4 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">prochaine carte.</span>
            </h1>
            <p style="margin: 24px auto 0; max-width: 560px;
                      font-size: clamp(15px, 1.4vw, 18px); line-height: 1.65; color: #94A3B8;">
                Une question, un partenariat, une remarque ? L'équipe est là — choisissez le canal qui vous convient.
            </p>
        </div>
    </section>

    {{-- ============================================================
         CHANNEL CARDS — overlap léger sur le bas du hero
       ============================================================ --}}
    <section style="position: relative; margin-top: -80px; padding: 0 16px 80px;">
        <div style="max-width: 1200px; margin: 0 auto;
                    display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
            @foreach ([
                ['title' => 'Email',     'value' => 'hello@kardafrica.com', 'desc' => 'Réponse sous 1h en heures ouvrées', 'href' => 'mailto:hello@kardafrica.com', 'color' => '#44A08D', 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                ['title' => 'Téléphone', 'value' => '+241 00 00 00 00',     'desc' => '7 jours / 7, 8h — 22h',           'href' => 'tel:+24100000000',           'color' => '#3B82F6', 'icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'],
                ['title' => 'WhatsApp',  'value' => 'Chat instantané',      'desc' => "Le canal le plus rapide",          'href' => 'https://wa.me/24100000000',  'color' => '#25D366', 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
                ['title' => 'Bureau',    'value' => 'Libreville, Gabon',    'desc' => 'Sur rendez-vous uniquement',       'href' => '#',                          'color' => '#7C3AED', 'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z'],
            ] as $ch)
                <a href="{{ $ch['href'] }}"
                   style="position: relative; overflow: hidden; display: block;
                          background: #ffffff; border: 1px solid #E2E8F0; border-radius: 18px;
                          padding: 22px; text-decoration: none;
                          box-shadow: 0 14px 30px -12px rgba(15,23,42,0.08), 0 0 0 1px rgba(15,23,42,0.02);
                          transition: transform .3s ease, box-shadow .3s ease, border-color .3s ease;"
                   onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 30px 60px -20px {{ $ch['color'] }}40, 0 0 0 1px {{ $ch['color'] }}30';this.style.borderColor='transparent';"
                   onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 14px 30px -12px rgba(15,23,42,0.08), 0 0 0 1px rgba(15,23,42,0.02)';this.style.borderColor='#E2E8F0';">

                    {{-- Glow accent --}}
                    <div style="position: absolute; top: -50px; right: -50px; width: 140px; height: 140px;
                                border-radius: 50%; opacity: 0.15;
                                background: radial-gradient(circle, {{ $ch['color'] }} 0%, transparent 70%);
                                filter: blur(20px); pointer-events: none;"></div>

                    <div style="position: relative;">
                        <div style="width: 44px; height: 44px; border-radius: 14px;
                                    background: linear-gradient(135deg, {{ $ch['color'] }} 0%, {{ $ch['color'] }}cc 100%);
                                    box-shadow: 0 10px 20px -6px {{ $ch['color'] }}66;
                                    display: flex; align-items: center; justify-content: center;
                                    margin-bottom: 18px;">
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="#ffffff" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ch['icon'] }}"/></svg>
                        </div>
                        <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: #94A3B8;">{{ $ch['title'] }}</div>
                        <div style="font-family: 'Space Grotesk','Inter',sans-serif; font-size: 16px; font-weight: 700; color: #0F172A; margin-top: 4px;">{{ $ch['value'] }}</div>
                        <div style="font-size: 12px; color: #64748B; margin-top: 8px; line-height: 1.5;">{{ $ch['desc'] }}</div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ============================================================
         FORM + SIDE PANEL
       ============================================================ --}}
    <section style="max-width: 1200px; margin: 0 auto; padding: 0 16px 80px;">
        <div style="display: flex; flex-wrap: wrap; gap: 24px; align-items: flex-start;">

            {{-- FORM --}}
            <div style="flex: 2 1 480px; min-width: 0;
                        background: #ffffff; border: 1px solid #E2E8F0; border-radius: 20px;
                        padding: clamp(24px, 3vw, 36px);
                        box-shadow: 0 4px 12px -4px rgba(15,23,42,0.06);">
                <div style="margin-bottom: 24px;">
                    <div style="display: inline-flex; align-items: center; gap: 6px;
                                padding: 4px 10px; border-radius: 9999px; margin-bottom: 12px;
                                background: rgba(68,160,141,0.10); border: 1px solid rgba(68,160,141,0.22);">
                        <span style="width: 5px; height: 5px; border-radius: 50%; background: #44A08D;"></span>
                        <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: #44A08D;">Formulaire</span>
                    </div>
                    <h2 style="font-family: 'Space Grotesk','Inter',sans-serif; font-weight: 700;
                               font-size: clamp(22px, 2.5vw, 30px); letter-spacing: -0.01em; color: #0F172A; margin: 0;">
                        Envoyez-nous un message
                    </h2>
                    <p style="font-size: 13px; color: #64748B; margin-top: 4px;">Tous les champs marqués d'un <span style="color: #F43F5E;">*</span> sont requis.</p>
                </div>

                <form x-data="contactForm()" @submit.prevent="submit()" data-no-loader style="display: flex; flex-direction: column; gap: 16px;">
                    @csrf

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">
                                Nom complet <span style="color: #F43F5E;">*</span>
                            </label>
                            <input type="text" x-model="form.name" required placeholder="Votre nom"
                                   style="width: 100%; padding: 11px 16px; font-size: 14px;
                                          background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px;
                                          color: #0F172A; outline: none; transition: all .2s;"
                                   onfocus="this.style.background='#fff';this.style.borderColor='#44A08D';this.style.boxShadow='0 0 0 3px rgba(68,160,141,0.15)';"
                                   onblur="this.style.background='#F8FAFC';this.style.borderColor='#E2E8F0';this.style.boxShadow='none';">
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">
                                E-mail <span style="color: #F43F5E;">*</span>
                            </label>
                            <input type="email" x-model="form.email" required placeholder="vous@exemple.com"
                                   style="width: 100%; padding: 11px 16px; font-size: 14px;
                                          background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px;
                                          color: #0F172A; outline: none; transition: all .2s;"
                                   onfocus="this.style.background='#fff';this.style.borderColor='#44A08D';this.style.boxShadow='0 0 0 3px rgba(68,160,141,0.15)';"
                                   onblur="this.style.background='#F8FAFC';this.style.borderColor='#E2E8F0';this.style.boxShadow='none';">
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">Sujet</label>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 8px;">
                            @foreach (['Question', 'Support', 'Partenariat', 'Autre'] as $subject)
                                <label style="cursor: pointer;">
                                    <input type="radio" name="subject" value="{{ $subject }}" x-model="form.subject"
                                           style="position: absolute; opacity: 0; pointer-events: none;">
                                    <div :style="{
                                            padding: '10px 12px',
                                            borderRadius: '12px',
                                            textAlign: 'center',
                                            fontSize: '14px',
                                            fontWeight: '500',
                                            transition: 'all .15s',
                                            background: form.subject === '{{ $subject }}' ? '#44A08D' : '#F8FAFC',
                                            color:      form.subject === '{{ $subject }}' ? '#ffffff' : '#475569',
                                            border:    '1px solid ' + (form.subject === '{{ $subject }}' ? '#44A08D' : '#E2E8F0'),
                                            boxShadow:  form.subject === '{{ $subject }}' ? '0 8px 16px -8px rgba(68,160,141,0.45)' : 'none'
                                         }">
                                        {{ $subject }}
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">
                            Message <span style="color: #F43F5E;">*</span>
                        </label>
                        <textarea x-model="form.message" rows="6" required placeholder="Dites-nous ce qui vous amène…"
                                  style="width: 100%; padding: 14px 16px; font-size: 14px;
                                         background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px;
                                         color: #0F172A; outline: none; resize: none; transition: all .2s; font-family: inherit;"
                                  onfocus="this.style.background='#fff';this.style.borderColor='#44A08D';this.style.boxShadow='0 0 0 3px rgba(68,160,141,0.15)';"
                                  onblur="this.style.background='#F8FAFC';this.style.borderColor='#E2E8F0';this.style.boxShadow='none';"></textarea>
                        <div style="display: flex; justify-content: space-between; margin-top: 6px;">
                            <span style="font-size: 10px; color: #94A3B8;">Min. 10 caractères</span>
                            <span style="font-size: 10px; color: #94A3B8; font-variant-numeric: tabular-nums;" x-text="form.message.length + ' / 1000'"></span>
                        </div>
                    </div>

                    <div style="display: flex; align-items: flex-start; gap: 8px; padding-top: 4px;">
                        <input type="checkbox" x-model="form.consent" id="consent"
                               style="margin-top: 4px; width: 16px; height: 16px; accent-color: #44A08D;">
                        <label for="consent" style="font-size: 12px; color: #475569; line-height: 1.6;">
                            J'accepte que mes données soient utilisées pour le traitement de ma demande,
                            conformément à la <a href="#" style="color: #44A08D; text-decoration: underline;">politique de confidentialité</a>.
                        </label>
                    </div>

                    <div style="padding-top: 8px;">
                        <button type="submit" :disabled="loading || success"
                                :style="{
                                    display: 'inline-flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    gap: '8px',
                                    padding: '13px 28px',
                                    border: '0',
                                    borderRadius: '12px',
                                    cursor: (loading || success) ? 'not-allowed' : 'pointer',
                                    background: success ? '#10B981' : '#44A08D',
                                    color: '#ffffff',
                                    fontWeight: '600',
                                    fontSize: '15px',
                                    boxShadow: '0 14px 30px -10px rgba(68,160,141,0.45)',
                                    opacity: (loading || success) ? '0.85' : '1',
                                    transition: 'all .2s ease'
                                }">
                            <template x-if="loading">
                                <svg style="width: 16px; height: 16px; animation: ka-spin 0.8s linear infinite;" fill="none" viewBox="0 0 24 24"><circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </template>
                            <template x-if="!loading && !success">
                                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            </template>
                            <template x-if="success">
                                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
                            <span x-text="success ? 'Message envoyé' : (loading ? 'Envoi en cours…' : 'Envoyer le message')"></span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- SIDEBAR --}}
            <aside style="flex: 1 1 280px; min-width: 0; display: flex; flex-direction: column; gap: 16px;">

                {{-- "Quick" card (dark) --}}
                <div style="position: relative; overflow: hidden;
                            background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
                            border-radius: 20px; padding: 24px; color: #ffffff;
                            box-shadow: 0 20px 40px -15px rgba(15,23,42,0.4);">
                    <div style="position: absolute; top: -40px; right: -40px; width: 160px; height: 160px;
                                border-radius: 50%; background: rgba(78,205,196,0.25); filter: blur(40px);"></div>
                    <div style="position: relative;">
                        <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: #5EEAD4;">Réponse rapide</div>
                        <h3 style="font-family: 'Space Grotesk','Inter',sans-serif; font-size: 20px; font-weight: 700; margin: 4px 0 0;">Avant d'écrire…</h3>
                        <p style="font-size: 14px; color: #CBD5E1; margin-top: 8px; line-height: 1.6;">
                            La plupart des questions ont déjà une réponse dans notre centre d'aide. Jetez-y un œil — ça va plus vite !
                        </p>
                        <a href="{{ route('support') }}"
                           style="margin-top: 16px; display: inline-flex; align-items: center; gap: 8px;
                                  font-size: 14px; font-weight: 600; color: #5EEAD4; text-decoration: none;"
                           onmouseover="this.style.color='#fff';" onmouseout="this.style.color='#5EEAD4';">
                            Voir le centre d'aide
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </a>
                    </div>
                </div>

                {{-- Hours --}}
                <div style="background: #ffffff; border: 1px solid #E2E8F0; border-radius: 20px; padding: 24px;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                        <div style="width: 36px; height: 36px; border-radius: 12px;
                                    background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
                                    color: #B45309; display: flex; align-items: center; justify-content: center;">
                            <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 style="font-family: 'Space Grotesk','Inter',sans-serif; font-size: 18px; font-weight: 700; color: #0F172A; margin: 0;">Horaires</h3>
                    </div>
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column;">
                        @foreach ([
                            ['Lun — Ven', '8h — 22h'],
                            ['Samedi',    '9h — 20h'],
                            ['Dimanche',  '10h — 18h'],
                        ] as $row)
                            <li style="display: flex; align-items: center; justify-content: space-between;
                                       padding: 8px 0; font-size: 14px;
                                       {{ !$loop->last ? 'border-bottom: 1px solid #F1F5F9;' : '' }}">
                                <span style="color: #475569;">{{ $row[0] }}</span>
                                <span style="font-weight: 600; color: #0F172A; font-variant-numeric: tabular-nums;">{{ $row[1] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Social --}}
                <div style="background: #ffffff; border: 1px solid #E2E8F0; border-radius: 20px; padding: 24px;">
                    <h3 style="font-family: 'Space Grotesk','Inter',sans-serif; font-size: 18px; font-weight: 700; color: #0F172A; margin: 0 0 16px;">Suivez-nous</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 8px;">
                        @foreach ([
                            ['name' => 'X',         'href' => '#', 'svg' => 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z'],
                            ['name' => 'Facebook',  'href' => '#', 'svg' => 'M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z'],
                            ['name' => 'Instagram', 'href' => '#', 'svg' => 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z'],
                            ['name' => 'LinkedIn',  'href' => '#', 'svg' => 'M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z'],
                        ] as $s)
                            <a href="{{ $s['href'] }}" target="_blank" rel="noopener"
                               style="display: flex; align-items: center; gap: 10px;
                                      padding: 10px 12px; border-radius: 12px;
                                      background: #F8FAFC; text-decoration: none; transition: background .2s;"
                               onmouseover="this.style.background='#F1F5F9';"
                               onmouseout="this.style.background='#F8FAFC';">
                                <div style="width: 28px; height: 28px; border-radius: 10px;
                                            background: #ffffff; border: 1px solid #E2E8F0;
                                            display: flex; align-items: center; justify-content: center;">
                                    <svg style="width: 14px; height: 14px;" fill="#475569" viewBox="0 0 24 24"><path d="{{ $s['svg'] }}"/></svg>
                                </div>
                                <span style="font-size: 13px; font-weight: 600; color: #475569;">{{ $s['name'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </aside>
        </div>
    </section>

</div>

@push('scripts')
<script>
    function contactForm() {
        return {
            form: { name: '', email: '', subject: 'Question', message: '', consent: false },
            loading: false, success: false, error: null,
            submit() {
                if (this.loading) return;
                if (!this.form.consent) { window.dispatchEvent(new CustomEvent('flash', { detail: { type: 'error', message: "Merci d'accepter la politique de confidentialité." } })); return; }
                if (this.form.message.length < 10) { window.dispatchEvent(new CustomEvent('flash', { detail: { type: 'error', message: 'Votre message est trop court.' } })); return; }
                this.loading = true;
                // No backend endpoint yet — simulate the network call so the UX stays consistent.
                setTimeout(() => {
                    this.loading = false;
                    this.success = true;
                    this.form = { name: '', email: '', subject: 'Question', message: '', consent: false };
                    setTimeout(() => { this.success = false; }, 5000);
                }, 900);
            }
        }
    }
</script>
@endpush
@endsection
