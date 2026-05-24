@extends('layouts.app')

@section('title', 'Centre d\'aide — KardAfrica')

@php
    $faqs = [
        'commande' => [
            'label' => 'Commandes & livraison',
            'icon'  => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
            'color' => '#44A08D',
            'items' => [
                ['q' => 'Combien de temps pour recevoir ma carte ?',     'a' => 'Les codes sont livrés en moins de 60 secondes après confirmation du paiement, directement sur votre adresse e-mail et dans la section « Mes cartes » de votre compte.'],
                ['q' => 'Que faire si je n\'ai pas reçu mon code ?',     'a' => 'Vérifiez d\'abord votre dossier spam. Si rien n\'apparaît dans les 5 minutes, allez dans « Mes commandes » et cliquez sur « Relancer la livraison ». Notre support est aussi joignable 24/7.'],
                ['q' => 'Puis-je annuler une commande ?',                  'a' => 'Une fois le code généré, l\'annulation n\'est pas possible (le code est définitivement attribué). Avant validation du paiement, contactez-nous immédiatement.'],
            ],
        ],
        'paiement' => [
            'label' => 'Paiements',
            'icon'  => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'color' => '#3B82F6',
            'items' => [
                ['q' => 'Quels sont les moyens de paiement ?', 'a' => 'Airtel Money, Moov Money et Visa via notre partenaire Futursowax. Toutes les transactions sont chiffrées de bout en bout.'],
                ['q' => 'Mon paiement a échoué, que faire ?',  'a' => 'Vérifiez votre solde Mobile Money et la limite de plafond. Si le débit a eu lieu sans validation de la commande, le montant est remboursé automatiquement sous 24h.'],
                ['q' => 'Puis-je payer en cryptomonnaie ?',     'a' => 'Pas encore — c\'est sur la roadmap pour 2027.'],
            ],
        ],
        'compte' => [
            'label' => 'Compte & sécurité',
            'icon'  => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
            'color' => '#7C3AED',
            'items' => [
                ['q' => 'Comment réinitialiser mon mot de passe ?',     'a' => 'Sur la page de connexion, cliquez sur « Mot de passe oublié » et suivez les instructions envoyées par e-mail.'],
                ['q' => 'Mes données sont-elles en sécurité ?',          'a' => 'Oui : nous chiffrons toutes les communications en TLS, ne stockons jamais vos identifiants Mobile Money et nos serveurs sont conformes aux normes de l\'industrie.'],
                ['q' => 'Comment supprimer mon compte ?',                 'a' => 'Écrivez-nous à hello@kardafrica.com depuis l\'adresse de votre compte. Suppression effective sous 72h.'],
            ],
        ],
        'cartes' => [
            'label' => 'Utilisation des cartes',
            'icon'  => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
            'color' => '#EA580C',
            'items' => [
                ['q' => 'Comment activer ma carte Netflix ?',     'a' => 'Connectez-vous sur netflix.com/redeem, entrez le code à 16 caractères reçu et le crédit s\'ajoute instantanément à votre compte.'],
                ['q' => 'Le code est-il valable à l\'international ?', 'a' => 'Chaque carte est liée à une région spécifique (indiquée sur la fiche produit). Vérifiez la région avant l\'achat.'],
                ['q' => 'La carte a une date d\'expiration ?',         'a' => 'En général 12 à 24 mois selon la marque. Cette information est affichée sur chaque carte dans « Mes cartes ».'],
            ],
        ],
    ];
@endphp

@section('content')
<div class="bg-white">

    {{-- ============================================================
         HERO + SEARCH
       ============================================================ --}}
    <section style="position: relative; overflow: hidden;
                    background:
                      radial-gradient(circle at 20% 0%, rgba(78,205,196,0.18) 0%, transparent 45%),
                      radial-gradient(circle at 80% 100%, rgba(124,58,237,0.12) 0%, transparent 45%),
                      linear-gradient(135deg, #060A14 0%, #0F172A 50%, #1E293B 100%);
                    padding: 80px 16px 140px;">

        {{-- grid pattern --}}
        <div style="position: absolute; inset: 0; pointer-events: none;
                    background-image: linear-gradient(to right, rgba(255,255,255,0.04) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,0.04) 1px, transparent 1px);
                    background-size: 48px 48px;
                    -webkit-mask-image: radial-gradient(ellipse 80% 60% at 50% 30%, black 40%, transparent 100%);
                            mask-image: radial-gradient(ellipse 80% 60% at 50% 30%, black 40%, transparent 100%);"></div>

        {{-- ambient glows --}}
        <div style="position: absolute; top: -160px; left: 50%; transform: translateX(-50%);
                    width: 800px; height: 400px; border-radius: 50%; pointer-events: none;
                    background: radial-gradient(circle, rgba(78,205,196,0.20) 0%, transparent 70%); filter: blur(60px);"></div>

        <div style="position: relative; max-width: 880px; margin: 0 auto; text-align: center;">
            <div style="display: inline-flex; align-items: center; gap: 8px;
                        padding: 6px 14px; border-radius: 9999px;
                        background: rgba(78,205,196,0.08); border: 1px solid rgba(78,205,196,0.28);
                        margin-bottom: 28px;">
                <svg style="width: 12px; height: 12px;" fill="none" stroke="#5EEAD4" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span style="font-size: 11px; font-weight: 700; letter-spacing: 0.18em; text-transform: uppercase; color: #5EEAD4;">Centre d'aide</span>
            </div>
            <h1 style="font-family: 'Space Grotesk','Inter',sans-serif; font-weight: 700;
                       font-size: clamp(36px, 6vw, 72px); line-height: 1.05; letter-spacing: -0.02em;
                       color: #ffffff; margin: 0;">
                Comment pouvons-nous <br/>
                <span style="background: linear-gradient(120deg, #4ECDC4 0%, #44A08D 50%, #5EEAD4 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">vous aider ?</span>
            </h1>
            <p style="margin: 24px auto 0; max-width: 560px;
                      font-size: clamp(15px, 1.4vw, 18px); line-height: 1.65; color: #94A3B8;">
                Trouvez rapidement une réponse à votre question parmi notre base de connaissances.
            </p>

            {{-- search --}}
            <div style="margin: 40px auto 0; max-width: 560px;" x-data="faqSearch()">
                <div style="position: relative;">
                    <svg style="position: absolute; left: 18px; top: 50%; transform: translateY(-50%); width: 20px; height: 20px; pointer-events: none; z-index: 2;"
                         fill="none" stroke="#94A3B8" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="query" @input="filter()"
                           placeholder="Rechercher dans la FAQ…"
                           style="width: 100%; padding: 16px 16px 16px 50px;
                                  background: #ffffff; color: #0F172A;
                                  font-size: 16px; font-weight: 500;
                                  border: 0; border-radius: 16px; outline: none;
                                  box-shadow: 0 30px 60px -15px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.08);">
                </div>
                <div style="margin-top: 16px; display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 8px; font-size: 12px;">
                    <span style="color: #64748B;">Suggestions :</span>
                    @foreach (['livraison', 'paiement', 'mot de passe', 'remboursement'] as $tag)
                        <button @click="query='{{ $tag }}'; filter();"
                                style="padding: 6px 12px; border-radius: 9999px; font-weight: 500;
                                       background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.10); color: #CBD5E1;
                                       cursor: pointer; transition: all .2s;"
                                onmouseover="this.style.background='rgba(78,205,196,0.15)';this.style.borderColor='rgba(78,205,196,0.30)';this.style.color='#5EEAD4';"
                                onmouseout="this.style.background='rgba(255,255,255,0.06)';this.style.borderColor='rgba(255,255,255,0.10)';this.style.color='#CBD5E1';">{{ $tag }}</button>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================
         CATEGORIES
       ============================================================ --}}
    <section style="position: relative; margin-top: -80px; padding: 0 16px 64px;">
        <div style="max-width: 1200px; margin: 0 auto;
                    display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
            @foreach ($faqs as $key => $cat)
                <a href="#cat-{{ $key }}"
                   style="position: relative; overflow: hidden; display: block;
                          background: #ffffff; border: 1px solid #E2E8F0; border-radius: 18px;
                          padding: 22px; text-decoration: none;
                          box-shadow: 0 14px 30px -12px rgba(15,23,42,0.08), 0 0 0 1px rgba(15,23,42,0.02);
                          transition: transform .3s ease, box-shadow .3s ease, border-color .3s ease;"
                   onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 30px 60px -20px {{ $cat['color'] }}40, 0 0 0 1px {{ $cat['color'] }}30';this.style.borderColor='transparent';"
                   onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 14px 30px -12px rgba(15,23,42,0.08), 0 0 0 1px rgba(15,23,42,0.02)';this.style.borderColor='#E2E8F0';">

                    <div style="position: absolute; top: -50px; right: -50px; width: 140px; height: 140px;
                                border-radius: 50%; opacity: 0.15;
                                background: radial-gradient(circle, {{ $cat['color'] }} 0%, transparent 70%);
                                filter: blur(20px); pointer-events: none;"></div>

                    <div style="position: relative;">
                        <div style="width: 44px; height: 44px; border-radius: 14px;
                                    background: linear-gradient(135deg, {{ $cat['color'] }} 0%, {{ $cat['color'] }}cc 100%);
                                    box-shadow: 0 10px 20px -6px {{ $cat['color'] }}66;
                                    display: flex; align-items: center; justify-content: center;
                                    margin-bottom: 18px;">
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="#ffffff" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $cat['icon'] }}"/></svg>
                        </div>
                        <div style="font-family: 'Space Grotesk','Inter',sans-serif; font-size: 16px; font-weight: 700; color: #0F172A;">{{ $cat['label'] }}</div>
                        <div style="font-size: 12px; color: #64748B; margin-top: 4px;">{{ count($cat['items']) }} articles</div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ============================================================
         FAQ SECTIONS
       ============================================================ --}}
    <section style="max-width: 880px; margin: 0 auto; padding: 0 16px 80px;" x-data="{ openItem: null }">
        @foreach ($faqs as $key => $cat)
            <div id="cat-{{ $key }}" style="margin-bottom: 48px; scroll-margin-top: 128px;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                    <div style="width: 36px; height: 36px; border-radius: 12px;
                                background: linear-gradient(135deg, {{ $cat['color'] }} 0%, {{ $cat['color'] }}cc 100%);
                                box-shadow: 0 8px 16px -6px {{ $cat['color'] }}66;
                                display: flex; align-items: center; justify-content: center;
                                flex-shrink: 0;">
                        <svg style="width: 16px; height: 16px;" fill="none" stroke="#ffffff" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $cat['icon'] }}"/></svg>
                    </div>
                    <h2 style="font-family: 'Space Grotesk','Inter',sans-serif; font-size: clamp(20px, 2.4vw, 30px); font-weight: 700; color: #0F172A; letter-spacing: -0.01em; margin: 0;">{{ $cat['label'] }}</h2>
                </div>

                <div class="faq-section" data-category="{{ $cat['label'] }}" style="display: flex; flex-direction: column; gap: 8px;">
                    @foreach ($cat['items'] as $idx => $item)
                        @php $itemKey = $key . '-' . $idx; @endphp
                        <div class="faq-item"
                             style="background: #ffffff; border: 1px solid #E2E8F0; border-radius: 16px; overflow: hidden;
                                    transition: border-color .2s ease, box-shadow .2s ease;"
                             onmouseover="this.style.borderColor='#CBD5E1';this.style.boxShadow='0 4px 12px -4px rgba(15,23,42,0.08)';"
                             onmouseout="this.style.borderColor='#E2E8F0';this.style.boxShadow='none';"
                             data-question="{{ Str::lower($item['q']) }}"
                             data-answer="{{ Str::lower($item['a']) }}">
                            <button type="button" @click="openItem = (openItem === '{{ $itemKey }}') ? null : '{{ $itemKey }}'"
                                    style="width: 100%; display: flex; align-items: center; justify-content: space-between;
                                           gap: 16px; padding: 16px 20px; text-align: left;
                                           background: transparent; border: 0; cursor: pointer;">
                                <span style="font-weight: 600; color: #0F172A; font-size: 15px;">{{ $item['q'] }}</span>
                                <div style="width: 28px; height: 28px; border-radius: 10px;
                                            display: flex; align-items: center; justify-content: center;
                                            transition: all .2s; flex-shrink: 0;"
                                     :style="openItem === '{{ $itemKey }}' ? 'background: #44A08D; color: #fff; transform: rotate(180deg);' : 'background: #F1F5F9; color: #64748B;'">
                                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </button>
                            <div x-show="openItem === '{{ $itemKey }}'"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-cloak>
                                <div style="padding: 4px 20px 20px; font-size: 14px; line-height: 1.65; color: #475569; border-top: 1px solid #F1F5F9;">
                                    {{ $item['a'] }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- No results state --}}
        <div id="faqEmpty" style="display: none; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 18px; padding: 48px 32px; text-align: center;">
            <div style="width: 48px; height: 48px; border-radius: 14px; background: #fff; border: 1px solid #E2E8F0;
                        margin: 0 auto 12px; display: flex; align-items: center; justify-content: center;">
                <svg style="width: 22px; height: 22px;" fill="none" stroke="#94A3B8" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div style="font-family: 'Space Grotesk','Inter',sans-serif; font-size: 17px; font-weight: 700; color: #0F172A;">Aucun résultat trouvé</div>
            <div style="font-size: 13px; color: #64748B; margin-top: 6px;">Essayez avec d'autres mots-clés ou contactez notre support.</div>
        </div>
    </section>

    {{-- ============================================================
         CTA — STILL NEED HELP
       ============================================================ --}}
    <section style="padding: 0 16px 80px;">
        <div style="max-width: 1100px; margin: 0 auto;
                    background: #ffffff; border: 1px solid #E2E8F0; border-radius: 28px;
                    padding: clamp(28px, 4vw, 56px);
                    box-shadow: 0 14px 30px -12px rgba(15,23,42,0.08);">
            <div style="display: flex; flex-wrap: wrap; gap: 32px; align-items: center;">
                <div style="flex: 1 1 320px; min-width: 0;">
                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: #44A08D; margin-bottom: 10px;">Pas trouvé ?</div>
                    <h2 style="font-family: 'Space Grotesk','Inter',sans-serif; font-weight: 700;
                               font-size: clamp(22px, 3vw, 36px); line-height: 1.15; letter-spacing: -0.02em;
                               color: #0F172A; margin: 0;">
                        Notre équipe est là pour vous aider.
                    </h2>
                    <p style="font-size: 15px; color: #64748B; margin-top: 16px; line-height: 1.65;">
                        Contactez-nous par e-mail, WhatsApp ou téléphone — réponse en moins d'1h en heures ouvrées.
                    </p>
                </div>
                <div style="flex: 1 1 320px; min-width: 0;
                            display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px;">
                    <a href="{{ route('contact') }}"
                       style="display: flex; align-items: flex-start; gap: 12px;
                              padding: 16px; border-radius: 16px;
                              background: #F8FAFC; text-decoration: none; transition: all .2s;"
                       onmouseover="this.style.background='#44A08D';this.querySelector('.cta-icon').style.background='rgba(255,255,255,0.18)';this.querySelector('.cta-icon svg').style.stroke='#fff';this.querySelector('.cta-title').style.color='#fff';this.querySelector('.cta-sub').style.color='rgba(255,255,255,0.85)';"
                       onmouseout="this.style.background='#F8FAFC';this.querySelector('.cta-icon').style.background='#fff';this.querySelector('.cta-icon svg').style.stroke='#44A08D';this.querySelector('.cta-title').style.color='#0F172A';this.querySelector('.cta-sub').style.color='#64748B';">
                        <div class="cta-icon" style="width: 40px; height: 40px; border-radius: 12px;
                                                     background: #ffffff; display: flex; align-items: center; justify-content: center;
                                                     flex-shrink: 0; transition: background .2s;">
                            <svg style="width: 20px; height: 20px; transition: stroke .2s;" fill="none" stroke="#44A08D" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <div class="cta-title" style="font-family: 'Space Grotesk','Inter',sans-serif; font-weight: 700; font-size: 14px; color: #0F172A;">Formulaire</div>
                            <div class="cta-sub" style="font-size: 12px; color: #64748B; margin-top: 2px;">Réponse sous 1h</div>
                        </div>
                    </a>
                    <a href="https://wa.me/24106871309" target="_blank" rel="noopener"
                       style="display: flex; align-items: flex-start; gap: 12px;
                              padding: 16px; border-radius: 16px;
                              background: #F8FAFC; text-decoration: none; transition: all .2s;"
                       onmouseover="this.style.background='#25D366';this.querySelector('.cta-icon').style.background='rgba(255,255,255,0.18)';this.querySelector('.cta-icon svg').style.stroke='#fff';this.querySelector('.cta-title').style.color='#fff';this.querySelector('.cta-sub').style.color='rgba(255,255,255,0.85)';"
                       onmouseout="this.style.background='#F8FAFC';this.querySelector('.cta-icon').style.background='#fff';this.querySelector('.cta-icon svg').style.stroke='#25D366';this.querySelector('.cta-title').style.color='#0F172A';this.querySelector('.cta-sub').style.color='#64748B';">
                        <div class="cta-icon" style="width: 40px; height: 40px; border-radius: 12px;
                                                     background: #ffffff; display: flex; align-items: center; justify-content: center;
                                                     flex-shrink: 0; transition: background .2s;">
                            <svg style="width: 20px; height: 20px; transition: stroke .2s;" fill="none" stroke="#25D366" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <div>
                            <div class="cta-title" style="font-family: 'Space Grotesk','Inter',sans-serif; font-weight: 700; font-size: 14px; color: #0F172A;">WhatsApp</div>
                            <div class="cta-sub" style="font-size: 12px; color: #64748B; margin-top: 2px;">Le plus rapide</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

</div>

@push('scripts')
<script>
    function faqSearch() {
        return {
            query: '',
            filter() {
                const q = this.query.trim().toLowerCase();
                const items = document.querySelectorAll('.faq-item');
                const sections = document.querySelectorAll('.faq-section');
                let totalVisible = 0;

                items.forEach(item => {
                    if (!q) { item.style.display = ''; totalVisible++; return; }
                    const haystack = (item.dataset.question || '') + ' ' + (item.dataset.answer || '');
                    const match = haystack.includes(q);
                    item.style.display = match ? '' : 'none';
                    if (match) totalVisible++;
                });

                // Hide empty section headers
                sections.forEach(section => {
                    const visible = section.querySelectorAll('.faq-item:not([style*="display: none"])').length;
                    section.parentElement.style.display = (q && visible === 0) ? 'none' : '';
                });

                document.getElementById('faqEmpty').style.display = totalVisible === 0 ? '' : 'none';
            }
        }
    }
</script>
@endpush
@endsection
