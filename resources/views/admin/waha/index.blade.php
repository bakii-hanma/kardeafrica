@extends('admin.layouts.admin')

@section('title', 'WhatsApp — Gestion')

@section('content')
<div class="max-w-5xl mx-auto" x-data="wahaPanel()" x-init="init()">

    <div style="margin-bottom:20px;">
        <h1 style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:700;color:var(--text,#0F172A);">WhatsApp — Gestion</h1>
        <p style="color:var(--text-faint,#64748B);font-size:13px;margin-top:4px;">Numéro WhatsApp KardAfrica (WAHA) : connexion, chat, envoi groupé et messages d'excuses.</p>
    </div>

    @if(!$configured)
        <div style="padding:16px;border-radius:12px;background:#FEF3C7;color:#92400E;font-size:14px;font-weight:600;">
            ⚠️ WAHA n'est pas configuré (clé API manquante). Ajoute <code>WAHA_API_KEY</code> au .env.
        </div>
    @else

    <div style="background:#fff;border:1px solid #E2E8F0;border-radius:16px;padding:24px;">

        {{-- État --}}
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
            <span style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748B;">État</span>
            <span x-show="loading" style="font-size:13px;color:#94A3B8;">Vérification…</span>
            <span x-show="!loading" :style="badgeStyle()" x-text="label()"></span>
            <span x-show="me" style="font-size:13px;color:#0F172A;font-weight:600;" x-text="me ? ('· ' + me) : ''"></span>
        </div>

        {{-- QR à scanner --}}
        <div x-show="status === 'SCAN_QR_CODE'" style="text-align:center;">
            <p style="font-size:14px;color:#475569;margin-bottom:12px;">Ouvre <b>WhatsApp</b> sur le téléphone KardAfrica → <b>Appareils connectés</b> → <b>Connecter un appareil</b>, puis scanne :</p>
            <img :src="qrSrc" alt="QR WhatsApp" style="width:280px;height:280px;border:1px solid #E2E8F0;border-radius:12px;background:#fff;"
                 x-on:error="qrError = true">
            <p x-show="qrError" style="color:#B91C1C;font-size:13px;margin-top:8px;">QR pas encore prêt… (rafraîchissement auto)</p>
            <p style="color:#94A3B8;font-size:12px;margin-top:8px;">Le QR se rafraîchit automatiquement.</p>
        </div>

        {{-- Arrêté / autre --}}
        <div x-show="status !== 'SCAN_QR_CODE' && status !== 'WORKING'" style="text-align:center;padding:12px 0;">
            <p style="font-size:14px;color:#64748B;margin-bottom:16px;" x-text="status === 'STARTING' ? 'Démarrage en cours…' : 'Session non démarrée.'"></p>
            <button @click="doStart()" :disabled="busy"
                    style="padding:12px 24px;border-radius:12px;background:#0F172A;color:#fff;font-weight:700;font-size:14px;border:none;cursor:pointer;">
                Démarrer la connexion
            </button>
        </div>

        {{-- ================= PANNEAU DE GESTION (connecté) ================= --}}
        <div x-show="status === 'WORKING'">

            {{-- Onglets --}}
            <div style="display:flex;gap:4px;border-bottom:1px solid #E2E8F0;margin-bottom:18px;flex-wrap:wrap;">
                <template x-for="t in tabs" :key="t.id">
                    <button @click="tab = t.id; onTab()"
                            :style="tabStyle(t.id)" x-text="t.label"></button>
                </template>
            </div>

            {{-- ---------- CHAT ---------- --}}
            <div x-show="tab === 'chat'" style="display:flex;gap:16px;min-height:420px;flex-wrap:wrap;">
                <div style="flex:0 0 240px;border:1px solid #E2E8F0;border-radius:12px;overflow:hidden;max-height:460px;overflow-y:auto;">
                    <div style="padding:10px 12px;border-bottom:1px solid #F1F5F9;display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:12px;font-weight:700;color:#64748B;text-transform:uppercase;">Conversations</span>
                        <button @click="loadChats()" style="font-size:12px;color:#44A08D;background:none;border:none;cursor:pointer;">↻</button>
                    </div>
                    <template x-if="chats.length === 0">
                        <p style="padding:16px;font-size:13px;color:#94A3B8;" x-text="chatsLoading ? 'Chargement…' : 'Aucune conversation.'"></p>
                    </template>
                    <template x-for="c in chats" :key="c.id">
                        <button @click="openChat(c)"
                                :style="'width:100%;text-align:left;padding:10px 12px;border:none;border-bottom:1px solid #F8FAFC;cursor:pointer;background:'+(activeChat && activeChat.id===c.id?'#ECFDF5':'#fff')+';'">
                            <span style="display:block;font-size:13px;font-weight:600;color:#0F172A;" x-text="(c.isGroup?'👥 ':'')+c.name"></span>
                            <span style="display:block;font-size:12px;color:#94A3B8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="c.preview"></span>
                        </button>
                    </template>
                </div>

                <div style="flex:1 1 320px;border:1px solid #E2E8F0;border-radius:12px;display:flex;flex-direction:column;max-height:460px;min-width:280px;">
                    <template x-if="!activeChat">
                        <div style="flex:1;display:flex;align-items:center;justify-content:center;color:#94A3B8;font-size:14px;">Sélectionne une conversation</div>
                    </template>
                    <template x-if="activeChat">
                        <div style="display:flex;flex-direction:column;height:100%;">
                            <div style="padding:10px 14px;border-bottom:1px solid #F1F5F9;font-size:14px;font-weight:700;color:#0F172A;" x-text="activeChat.name"></div>
                            <div id="waChatScroll" style="flex:1;overflow-y:auto;padding:14px;background:#F8FAFC;display:flex;flex-direction:column;gap:8px;">
                                <template x-for="m in messages" :key="m.id || m.ts">
                                    <div :style="'max-width:78%;padding:8px 11px;border-radius:12px;font-size:13px;line-height:1.4;'+(m.fromMe?'align-self:flex-end;background:#D1FAE5;color:#065F46;':'align-self:flex-start;background:#fff;border:1px solid #E2E8F0;color:#0F172A;')">
                                        <span x-text="m.body || (m.hasMedia ? '📎 média' : '')"></span>
                                    </div>
                                </template>
                                <p x-show="messages.length===0" style="color:#94A3B8;font-size:13px;text-align:center;">Pas de messages.</p>
                            </div>
                            <div style="padding:10px;border-top:1px solid #F1F5F9;display:flex;gap:8px;">
                                <input type="text" x-model="reply" @keydown.enter="sendReply()" placeholder="Écrire un message…"
                                       style="flex:1;padding:9px 12px;border:1px solid #CBD5E1;border-radius:10px;font-size:14px;">
                                <button @click="sendReply()" :disabled="busy || !reply"
                                        style="padding:9px 16px;border-radius:10px;background:#44A08D;color:#fff;font-weight:600;border:none;cursor:pointer;">Envoyer</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- ---------- ENVOI GROUPÉ ---------- --}}
            <div x-show="tab === 'broadcast'" style="max-width:640px;">
                <label style="display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:6px;">Destinataires</label>
                <textarea x-model="bcNumbers" rows="3" placeholder="077047861, 066123456 …  (séparés par des virgules, espaces ou retours à la ligne)"
                          style="width:100%;padding:10px 12px;border:1px solid #CBD5E1;border-radius:10px;font-size:14px;"></textarea>
                <label style="display:block;font-size:13px;font-weight:700;color:#334155;margin:14px 0 6px;">Message</label>
                <textarea x-model="bcText" rows="5" placeholder="Votre message…"
                          style="width:100%;padding:10px 12px;border:1px solid #CBD5E1;border-radius:10px;font-size:14px;"></textarea>
                <div style="margin-top:14px;display:flex;align-items:center;gap:12px;">
                    <button @click="doBroadcast()" :disabled="busy || !bcText || !bcNumbers"
                            style="padding:11px 20px;border-radius:10px;background:#0F172A;color:#fff;font-weight:700;border:none;cursor:pointer;">Envoyer à tous</button>
                    <span x-show="bcResult" x-text="bcResult" :style="'font-size:13px;font-weight:600;color:'+(bcOk?'#059669':'#B91C1C')"></span>
                </div>
                <p style="font-size:12px;color:#94A3B8;margin-top:10px;">Un délai est appliqué entre chaque envoi pour éviter un blocage WhatsApp.</p>
            </div>

            {{-- ---------- EXCUSES ---------- --}}
            <div x-show="tab === 'apology'" style="max-width:640px;">
                <div style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:12px;padding:14px;font-size:13px;color:#9A3412;line-height:1.5;">
                    Envoie le message d'excuses aux clients dont une commande est restée <b>en attente / en cours</b> ces 10 derniers jours (incident de livraison), avec une invitation à réessayer.
                </div>

                <div style="margin:16px 0;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                        <span style="font-size:13px;font-weight:700;color:#334155;">Destinataires concernés</span>
                        <button @click="loadPending()" style="font-size:12px;color:#44A08D;background:none;border:none;cursor:pointer;">↻ Rafraîchir</button>
                    </div>
                    <template x-if="pending.length === 0">
                        <p style="font-size:13px;color:#94A3B8;" x-text="pendingLoading ? 'Chargement…' : 'Aucun client concerné (ou numéros manquants).'"></p>
                    </template>
                    <div style="display:flex;flex-direction:column;gap:6px;">
                        <template x-for="p in pending" :key="p.id">
                            <div style="display:flex;justify-content:space-between;font-size:13px;padding:8px 12px;border:1px solid #E2E8F0;border-radius:8px;">
                                <span style="font-weight:600;color:#0F172A;" x-text="p.name"></span>
                                <span style="color:#64748B;font-family:monospace;" x-text="p.phone"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <details style="margin-bottom:16px;">
                    <summary style="font-size:13px;color:#64748B;cursor:pointer;">Aperçu du message</summary>
                    <pre style="white-space:pre-wrap;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:12px;font-size:13px;color:#334155;margin-top:8px;">Bonjour 👋

Nous te présentons nos excuses : un incident technique a empêché la livraison de ta commande récente sur KardAfrica.

C'est désormais corrigé ✅. Tu peux relancer ton achat en quelques secondes sur kardafrica.com.

Merci de ta confiance 🙏 — l'équipe KardAfrica.</pre>
                </details>

                <div style="display:flex;align-items:center;gap:12px;">
                    <button @click="sendApology()" :disabled="busy || pending.length === 0"
                            style="padding:11px 20px;border-radius:10px;background:#EA580C;color:#fff;font-weight:700;border:none;cursor:pointer;">
                        Envoyer les excuses (<span x-text="pending.length"></span>)
                    </button>
                    <span x-show="apoResult" x-text="apoResult" :style="'font-size:13px;font-weight:600;color:'+(apoOk?'#059669':'#B91C1C')"></span>
                </div>
            </div>

            {{-- ---------- PARAMÈTRES ---------- --}}
            <div x-show="tab === 'settings'" style="max-width:520px;">
                <div style="width:56px;height:56px;border-radius:50%;background:#D1FAE5;display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
                    <svg style="width:28px;height:28px;color:#059669;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
                <p style="font-size:15px;font-weight:700;color:#0F172A;">WhatsApp connecté</p>
                <p style="font-size:13px;color:#64748B;margin-top:2px;" x-text="me ? ('Numéro : ' + me) : ''"></p>

                <div style="margin-top:18px;display:flex;gap:10px;flex-wrap:wrap;">
                    <input type="tel" x-model="testPhone" placeholder="Numéro de test (ex: 077047861)"
                           style="padding:10px 12px;border:1px solid #CBD5E1;border-radius:10px;font-size:14px;width:220px;">
                    <button @click="sendTest()" :disabled="busy"
                            style="padding:10px 16px;border-radius:10px;background:#44A08D;color:#fff;font-weight:600;font-size:14px;border:none;cursor:pointer;">Test</button>
                    <button @click="doRestart()" :disabled="busy"
                            style="padding:10px 16px;border-radius:10px;background:#F1F5F9;color:#334155;font-weight:600;font-size:14px;border:none;cursor:pointer;">↻ Redémarrer</button>
                    <button @click="doLogout()" :disabled="busy"
                            style="padding:10px 16px;border-radius:10px;background:#FEE2E2;color:#991B1B;font-weight:600;font-size:14px;border:none;cursor:pointer;">Déconnecter</button>
                </div>
                <p x-show="msg" x-text="msg" :style="'margin-top:12px;font-size:13px;font-weight:600;color:'+(msgOk?'#059669':'#B91C1C')"></p>
            </div>

        </div>
    </div>
    @endif
</div>

@push('head')
<script>
function wahaPanel() {
    return {
        status: null, me: null, loading: true, busy: false,
        qrSrc: '', qrError: false, testPhone: '', msg: '', msgOk: false,
        _timer: null,
        // onglets
        tab: 'chat',
        tabs: [
            { id:'chat', label:'Chat' },
            { id:'broadcast', label:'Envoi groupé' },
            { id:'apology', label:'Messages d\'excuses' },
            { id:'settings', label:'Paramètres' },
        ],
        // chat
        chats: [], chatsLoading: false, activeChat: null, messages: [], reply: '', _msgTimer: null,
        // broadcast
        bcNumbers: '', bcText: '', bcResult: '', bcOk: false,
        // apology
        pending: [], pendingLoading: false, apoResult: '', apoOk: false,

        init() { this.refresh(); this._timer = setInterval(() => this.refresh(), 4000); },

        async refresh() {
            try {
                const r = await fetch('{{ route('admin.waha.status') }}', { headers: { 'Accept': 'application/json' } });
                const d = await r.json();
                const prev = this.status;
                this.status = d.status || 'UNKNOWN';
                this.me = d.me && (d.me.pushName || d.me.id) ? (d.me.pushName || String(d.me.id).replace('@c.us','')) : null;
                if (this.status === 'SCAN_QR_CODE') {
                    this.qrError = false;
                    this.qrSrc = '{{ route('admin.waha.qr') }}?t=' + Date.now();
                }
                // à la première connexion, charge le chat
                if (this.status === 'WORKING' && prev !== 'WORKING' && this.tab === 'chat') this.loadChats();
            } catch (e) { this.status = 'UNREACHABLE'; }
            this.loading = false;
        },

        label() {
            return ({ WORKING:'Connecté', SCAN_QR_CODE:'À scanner', STARTING:'Démarrage', STOPPED:'Arrêté', FAILED:'Échec', UNREACHABLE:'Injoignable' })[this.status] || this.status || '—';
        },
        badgeStyle() {
            const c = ({ WORKING:['#D1FAE5','#065F46'], SCAN_QR_CODE:['#DBEAFE','#1E40AF'], STARTING:['#FEF3C7','#92400E'] })[this.status] || ['#F1F5F9','#475569'];
            return `display:inline-block;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:700;background:${c[0]};color:${c[1]};`;
        },
        tabStyle(id) {
            const active = this.tab === id;
            return `padding:10px 16px;border:none;background:none;cursor:pointer;font-size:14px;font-weight:${active?'700':'500'};color:${active?'#0F172A':'#94A3B8'};border-bottom:2px solid ${active?'#44A08D':'transparent'};margin-bottom:-1px;`;
        },
        onTab() {
            if (this.tab === 'chat' && this.chats.length === 0) this.loadChats();
            if (this.tab === 'apology' && this.pending.length === 0) this.loadPending();
            if (this.tab !== 'chat' && this._msgTimer) { clearInterval(this._msgTimer); this._msgTimer = null; }
        },

        _csrf() { return document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || ''; },
        async _post(url, body) {
            this.busy = true;
            try {
                const r = await fetch(url, { method:'POST', headers:{ 'X-CSRF-TOKEN': this._csrf(), 'Accept':'application/json', 'Content-Type':'application/json' }, body: body ? JSON.stringify(body) : null });
                return await r.json();
            } finally { this.busy = false; }
        },

        async doStart() { await this._post('{{ route('admin.waha.start') }}'); setTimeout(() => this.refresh(), 1500); },
        async doRestart() { this.msg=''; await this._post('{{ route('admin.waha.restart') }}'); setTimeout(() => this.refresh(), 1500); },
        async doLogout() { this.msg=''; await this._post('{{ route('admin.waha.logout') }}'); setTimeout(() => this.refresh(), 1500); },
        async sendTest() {
            this.msg=''; if (!this.testPhone) return;
            const d = await this._post('{{ route('admin.waha.test') }}', { phone: this.testPhone });
            this.msgOk = !!d.ok; this.msg = d.ok ? 'Message de test envoyé ✅' : (d.message || 'Échec de l\'envoi.');
        },

        // ----- Chat -----
        async loadChats() {
            this.chatsLoading = true;
            try { const r = await fetch('{{ route('admin.waha.chats') }}', { headers:{'Accept':'application/json'} }); const d = await r.json(); this.chats = d.chats || []; }
            catch(e) {} finally { this.chatsLoading = false; }
        },
        async openChat(c) {
            this.activeChat = c; this.messages = [];
            await this.loadMessages();
            if (this._msgTimer) clearInterval(this._msgTimer);
            this._msgTimer = setInterval(() => { if (this.tab==='chat' && this.activeChat) this.loadMessages(); }, 6000);
        },
        async loadMessages() {
            if (!this.activeChat) return;
            try {
                const r = await fetch('{{ route('admin.waha.messages') }}?chatId=' + encodeURIComponent(this.activeChat.id), { headers:{'Accept':'application/json'} });
                const d = await r.json();
                this.messages = d.messages || [];
                this.$nextTick(() => { const el = document.getElementById('waChatScroll'); if (el) el.scrollTop = el.scrollHeight; });
            } catch(e) {}
        },
        async sendReply() {
            if (!this.reply || !this.activeChat) return;
            const txt = this.reply; this.reply = '';
            const d = await this._post('{{ route('admin.waha.send') }}', { chatId: this.activeChat.id, text: txt });
            if (d.ok) { this.messages.push({ id:'local'+Date.now(), fromMe:true, body:txt }); this.$nextTick(()=>{ const el=document.getElementById('waChatScroll'); if(el) el.scrollTop=el.scrollHeight; }); }
            else { this.reply = txt; }
        },

        // ----- Broadcast -----
        async doBroadcast() {
            this.bcResult=''; if (!this.bcText || !this.bcNumbers) return;
            const d = await this._post('{{ route('admin.waha.broadcast') }}', { recipients: this.bcNumbers, text: this.bcText });
            this.bcOk = !!d.ok;
            this.bcResult = d.ok ? (`Envoyé : ${d.sent}` + (d.failed?`, échecs : ${d.failed}`:'') + (d.invalid && d.invalid.length?`, invalides : ${d.invalid.length}`:'')) : 'Aucun envoi (vérifie les numéros).';
        },

        // ----- Apology -----
        async loadPending() {
            this.pendingLoading = true;
            try { const r = await fetch('{{ route('admin.waha.pending') }}', { headers:{'Accept':'application/json'} }); const d = await r.json(); this.pending = d.contacts || []; }
            catch(e) {} finally { this.pendingLoading = false; }
        },
        async sendApology() {
            this.apoResult=''; if (this.pending.length === 0) return;
            if (!confirm('Envoyer le message d\'excuses à ' + this.pending.length + ' client(s) ?')) return;
            const d = await this._post('{{ route('admin.waha.apology') }}', {});
            this.apoOk = !!d.ok;
            this.apoResult = d.ok ? (`Envoyé à ${d.sent} client(s)` + (d.failed?`, échecs : ${d.failed}`:'')) : 'Aucun envoi.';
        },
    };
}
</script>
@endpush
@endsection
