@extends('layouts.app')

@section('title', 'Kara — Assistante IA · KardAfrica')

@section('content')
<style>
    @keyframes ka-page-pulse { 0% { box-shadow: 0 0 0 0 rgba(52,211,153,0.55); } 70% { box-shadow: 0 0 0 8px rgba(52,211,153,0); } 100% { box-shadow: 0 0 0 0 rgba(52,211,153,0); } }
    @keyframes ka-page-msg-in { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes ka-page-typing { 0%,80%,100% { transform: scale(0.7); opacity: 0.45; } 40% { transform: scale(1); opacity: 1; } }

    .ka-page-online::before {
        content: ''; width: 7px; height: 7px; border-radius: 50%;
        background: #34D399; display: inline-block; margin-right: 6px;
        animation: ka-page-pulse 1.8s ease-out infinite;
    }
    .ka-page-msg { animation: ka-page-msg-in .25s cubic-bezier(.22,1,.36,1); }
    .ka-page-typing-dot {
        width: 6px; height: 6px; border-radius: 50%; background: #94A3B8; display: inline-block;
        animation: ka-page-typing 1.4s infinite ease-in-out;
    }
    .ka-page-typing-dot:nth-child(2) { animation-delay: .15s; }
    .ka-page-typing-dot:nth-child(3) { animation-delay: .3s; }

    .ka-page-chip {
        display:inline-flex; align-items:center; gap:6px;
        padding: 7px 13px; border-radius: 999px;
        background: white; border: 1px solid #E2E8F0;
        font-size: 12px; font-weight: 600; color: #475569;
        cursor: pointer; transition: all .15s;
        white-space: nowrap;
    }
    .ka-page-chip:hover { border-color: #44A08D; color: #44A08D; transform: translateY(-1px); }
</style>

<div style="background:#FAFAF7;min-height:calc(100vh - 80px);font-family:'Inter','Figtree',sans-serif;padding:0;">

    {{-- ============================================================
         HERO sombre avec avatar Kara
       ============================================================ --}}
    <section style="position:relative;overflow:hidden;
                    background:
                       radial-gradient(circle at 20% 0%, rgba(78,205,196,0.30) 0%, transparent 45%),
                       radial-gradient(circle at 90% 100%, rgba(124,58,237,0.18) 0%, transparent 50%),
                       linear-gradient(135deg, #060A14 0%, #0F172A 50%, #1E293B 100%);
                    padding: 24px 16px 80px;">

        <div style="max-width:760px;margin:0 auto;">
            <div style="display:flex;align-items:center;gap:14px;">
                {{-- Avatar avec étincelle + dot online --}}
                <div style="position:relative;width:62px;height:62px;border-radius:18px;
                            background:linear-gradient(135deg, #44A08D 0%, #4ECDC4 100%);
                            display:flex;align-items:center;justify-content:center;flex-shrink:0;
                            box-shadow:0 16px 32px -10px rgba(78,205,196,0.55), inset 0 1px 0 rgba(255,255,255,0.30);">
                    <svg style="width:30px;height:30px;color:white;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/>
                    </svg>
                    <span style="position:absolute;bottom:-2px;right:-2px;width:14px;height:14px;border-radius:50%;
                                 background:#34D399;border:3px solid #0F172A;"></span>
                </div>

                <div style="flex:1;min-width:0;color:white;">
                    <div style="display:inline-flex;align-items:center;gap:6px;
                                padding: 3px 9px;border-radius:999px;
                                background:rgba(78,205,196,0.18);border:1px solid rgba(78,205,196,0.30);
                                font-size:10px;font-weight:700;letter-spacing:0.10em;text-transform:uppercase;color:#5EEAD4;
                                margin-bottom:6px;">
                        <span class="ka-page-online"></span> Assistante IA · KardAfrica
                    </div>
                    <h1 style="font-family:'Space Grotesk','Inter',sans-serif;font-size:28px;font-weight:800;letter-spacing:-0.02em;line-height:1.1;margin:0;color:white;">
                        Kara
                    </h1>
                    <p style="font-size:13px;color:#94A3B8;margin:6px 0 0;">
                        Pose tes questions sur les cartes, ton compte, le paiement…
                    </p>
                </div>

                {{-- Bouton retour --}}
                <a href="{{ route('profile.show') }}"
                   style="display:inline-flex;align-items:center;justify-content:center;
                          width:38px;height:38px;border-radius:11px;
                          background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);
                          color:white;text-decoration:none;flex-shrink:0;"
                   aria-label="Retour au profil">
                    <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </a>
            </div>
        </div>
    </section>

    {{-- ============================================================
         CHAT FULL-PAGE (= mêmes intentions que le widget Kara)
       ============================================================ --}}
    <section style="max-width:760px;margin:-58px auto 0;padding:0 16px 24px;">
        <div style="background:white;border-radius:20px;overflow:hidden;
                    box-shadow:0 30px 80px -15px rgba(15,23,42,0.18), 0 0 0 1px rgba(15,23,42,0.05);
                    display:flex;flex-direction:column;height:calc(100vh - 240px);min-height:500px;">

            {{-- Messages --}}
            <div id="kaPageMessages" style="flex:1;overflow-y:auto;padding:18px 16px;background:#F8FAFC;
                                            display:flex;flex-direction:column;gap:10px;scroll-behavior:smooth;">
                {{-- Les messages sont injectés en JS, identiques au widget --}}
            </div>

            {{-- Suggestion chips --}}
            <div id="kaPageChips" style="display:flex;gap:6px;padding:10px 14px;background:white;
                                          overflow-x:auto;border-top:1px solid #F1F5F9;
                                          scrollbar-width:none;-ms-overflow-style:none;">
                {{-- chips injectées en JS --}}
            </div>

            {{-- Composer --}}
            <div style="display:flex;gap:8px;padding:12px 14px 14px;background:white;border-top:1px solid #F1F5F9;">
                <input type="text" id="kaPageInput" placeholder="Pose ta question…" autocomplete="off"
                       style="flex:1;padding:11px 16px;border:1px solid #E2E8F0;border-radius:999px;
                              font-size:14px;color:#0F172A;background:#F8FAFC;outline:none;
                              transition:border-color .15s, background .15s;"
                       onfocus="this.style.borderColor='#44A08D';this.style.background='white';"
                       onblur="this.style.borderColor='#E2E8F0';this.style.background='#F8FAFC';" />
                <button id="kaPageSend" type="button" aria-label="Envoyer"
                        style="width:42px;height:42px;border:0;border-radius:50%;
                               background:linear-gradient(135deg, #44A08D 0%, #4ECDC4 100%);color:white;
                               cursor:pointer;display:flex;align-items:center;justify-content:center;
                               box-shadow:0 8px 18px -6px rgba(78,205,196,0.55);">
                    <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                </button>
            </div>
        </div>

        <p style="text-align:center;font-size:11px;color:#94A3B8;margin:14px 0 0;">
            🔒 Conversations privées — Kara ne stocke pas tes infos sensibles.
        </p>
    </section>
</div>

@push('scripts')
<script>
// ============================================================
//  Vue dédiée Kara — réutilise les mêmes intentions que le widget
//  flottant. Les routes Laravel sont injectées par PHP.
// ============================================================
(function () {
    const messages = document.getElementById('kaPageMessages');
    const chips    = document.getElementById('kaPageChips');
    const input    = document.getElementById('kaPageInput');
    const sendBtn  = document.getElementById('kaPageSend');
    if (!messages || !input) return;

    const routes = {
        boutique:    @json(route('boutique')),
        contact:     @json(route('contact')),
        support:     @json(route('support')),
        about:       @json(route('about')),
        cart:        @json(route('cart.index')),
        orders:      @json(route('orders.index')),
        cards:       @json(route('cards.index')),
        profile:     @json(route('profile.show')),
        daywatch:    @json(url('/category/5')),
        netflix:     @json(url('/boutique?search=Netflix')),
        spotify:     @json(url('/boutique?search=Spotify')),
        psn:         @json(url('/boutique?search=Playstation')),
        steam:       @json(url('/boutique?search=Steam')),
        apple:       @json(url('/boutique?search=Apple')),
    };

    const STORAGE_KEY = 'kaPageHistory';

    const DEFAULT_CHIPS = [
        { label: 'Voir le catalogue',    intent: 'voir le catalogue' },
        { label: 'Comment payer ?',      intent: 'comment payer' },
        { label: 'Mes commandes',        intent: 'mes commandes' },
        { label: 'Délai de livraison',   intent: 'délai livraison' },
        { label: 'Mes cartes',           intent: 'mes cartes' },
        { label: 'Daywatch',             intent: 'daywatch' },
    ];

    // Catalogue d'intentions — même routage que le widget (resté simple)
    const intents = [
        { match: /(catalog|boutique|cartes? disponibles|liste)/i,
          reply: () => `Voici le catalogue complet — plus de 300 marques disponibles.`,
          links: [{ label: 'Ouvrir la boutique', url: routes.boutique }] },
        { match: /(payer|paiement|mobile money|carte bancaire|airtel|moov)/i,
          reply: () => `On accepte Mobile Money (Airtel, Moov) et carte bancaire via E-Billing. Paiement instantané et 100% sécurisé.`,
          links: [{ label: 'Voir mon panier', url: routes.cart }] },
        { match: /(commande|order|achat)/i,
          reply: () => `Tu peux retrouver toutes tes commandes dans ton espace.`,
          links: [{ label: 'Mes commandes', url: routes.orders }] },
        { match: /(carte|cards|reçue)/i,
          reply: () => `Tes cartes achetées sont dans « Mes cartes » avec leurs codes/PIN.`,
          links: [{ label: 'Mes cartes', url: routes.cards }] },
        { match: /(profile|profil|compte)/i,
          reply: () => `Mets à jour ton profil ici.`,
          links: [{ label: 'Mon profil', url: routes.profile }] },
        { match: /(livraison|délai|delai|reçoi|recevra)/i,
          reply: () => `Livraison instantanée par email après le paiement, en moins de 60 secondes dans 99% des cas.` },
        { match: /(daywatch)/i,
          reply: () => `Daywatch — streaming local africain.`,
          links: [{ label: 'Voir Daywatch', url: routes.daywatch }] },
        { match: /(netflix)/i, reply: () => `Netflix — abonnement streaming international.`, links: [{ label: 'Voir Netflix', url: routes.netflix }] },
        { match: /(spotify)/i, reply: () => `Spotify Premium — musique sans pub.`, links: [{ label: 'Voir Spotify', url: routes.spotify }] },
        { match: /(playstation|psn)/i, reply: () => `PlayStation Store — recharger un compte PSN.`, links: [{ label: 'Voir PSN', url: routes.psn }] },
        { match: /(steam)/i, reply: () => `Steam Wallet — acheter des jeux PC.`, links: [{ label: 'Voir Steam', url: routes.steam }] },
        { match: /(apple|itunes|app store)/i, reply: () => `Apple Gift Card — App Store, iTunes, iCloud.`, links: [{ label: 'Voir Apple', url: routes.apple }] },
        { match: /(merci|thanks|thank|cool|super|génial)/i, reply: () => `Avec plaisir ! 🙌 N'hésite pas si tu as d'autres questions.` },
        { match: /(salut|bonjour|hello|coucou|hi|hey)/i, reply: () => `Bonjour ! 👋 Je suis Kara, l'assistante KardAfrica. Comment puis-je t'aider ?` },
    ];

    function detect(text) {
        for (const it of intents) if (it.match.test(text)) return it;
        return {
            reply: () => `Pas sûre d'avoir compris ! 🤔 Essaie l'une des suggestions ci-dessous, ou contacte le support pour être aidé(e) directement.`,
            links: [{ label: 'Contacter le support', url: routes.support }],
        };
    }

    function renderMessage(text, isUser, links = [], skipPersist = false) {
        const wrap = document.createElement('div');
        wrap.className = 'ka-page-msg';
        wrap.style.cssText = `display:flex;gap:8px;align-items:flex-end;max-width:85%;${isUser ? 'align-self:flex-end;flex-direction:row-reverse;' : 'align-self:flex-start;'}`;

        if (!isUser) {
            const avatar = document.createElement('div');
            avatar.style.cssText = 'flex-shrink:0;width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#4ECDC4,#44A08D);display:flex;align-items:center;justify-content:center;color:white;';
            avatar.innerHTML = '<svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>';
            wrap.appendChild(avatar);
        }

        const bubble = document.createElement('div');
        bubble.style.cssText = `padding:10px 14px;border-radius:16px;font-size:14px;line-height:1.5;word-wrap:break-word;${isUser ? 'background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;border-bottom-right-radius:4px;box-shadow:0 4px 10px -2px rgba(68,160,141,0.30);' : 'background:white;color:#0F172A;border-bottom-left-radius:4px;box-shadow:0 1px 2px rgba(15,23,42,0.05);'}`;
        bubble.textContent = text;

        if (links.length > 0 && !isUser) {
            const linksWrap = document.createElement('div');
            linksWrap.style.cssText = 'margin-top:10px;display:flex;flex-direction:column;gap:6px;';
            links.forEach(l => {
                const a = document.createElement('a');
                a.href = l.url; a.textContent = l.label + ' →';
                a.style.cssText = 'display:inline-block;padding:6px 12px;border-radius:9px;background:#F0FDFA;color:#44A08D;font-size:12px;font-weight:700;text-decoration:none;border:1px solid #99F6E4;width:fit-content;';
                linksWrap.appendChild(a);
            });
            bubble.appendChild(linksWrap);
        }

        wrap.appendChild(bubble);
        messages.appendChild(wrap);
        messages.scrollTop = messages.scrollHeight;

        if (!skipPersist) {
            try {
                const hist = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]');
                hist.push({ text, isUser, links });
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify(hist.slice(-100)));
            } catch (_) {}
        }
    }

    function renderTyping() {
        const wrap = document.createElement('div');
        wrap.id = 'kaPageTyping';
        wrap.style.cssText = 'display:flex;gap:8px;align-items:center;align-self:flex-start;';
        wrap.innerHTML = `
            <div style="flex-shrink:0;width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#4ECDC4,#44A08D);"></div>
            <div style="background:white;border-radius:16px;border-bottom-left-radius:4px;padding:12px 14px;display:flex;gap:4px;box-shadow:0 1px 2px rgba(15,23,42,0.05);">
                <span class="ka-page-typing-dot"></span>
                <span class="ka-page-typing-dot"></span>
                <span class="ka-page-typing-dot"></span>
            </div>`;
        messages.appendChild(wrap);
        messages.scrollTop = messages.scrollHeight;
    }
    function removeTyping() {
        const t = document.getElementById('kaPageTyping');
        if (t) t.remove();
    }

    function renderChips(list) {
        chips.innerHTML = '';
        list.forEach(c => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'ka-page-chip';
            btn.textContent = c.label;
            btn.addEventListener('click', () => {
                input.value = c.intent || c.label;
                handleSend();
            });
            chips.appendChild(btn);
        });
    }

    function handleSend() {
        const text = (input.value || '').trim();
        if (!text) return;

        renderMessage(text, true);
        input.value = '';
        sendBtn.disabled = true;
        renderTyping();

        setTimeout(() => {
            removeTyping();
            const intent = detect(text);
            renderMessage(intent.reply(), false, intent.links || []);
            renderChips(DEFAULT_CHIPS);
            sendBtn.disabled = false;
            input.focus();
        }, 600 + Math.random() * 400);
    }

    // Restaure l'historique de session ou greeting
    let restored = false;
    try {
        const hist = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]');
        if (hist.length > 0) {
            hist.forEach(m => renderMessage(m.text, m.isUser, m.links || [], true));
            restored = true;
        }
    } catch (_) {}

    if (!restored) {
        setTimeout(() => {
            renderMessage(`Bonjour 👋 Je suis Kara, l'assistante KardAfrica. Comment puis-je t'aider aujourd'hui ?`, false);
        }, 100);
    }
    renderChips(DEFAULT_CHIPS);

    sendBtn.addEventListener('click', handleSend);
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSend();
        }
    });
})();
</script>
@endpush

@endsection
