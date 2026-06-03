@extends('owner.layouts.owner')

@section('title', 'Scanner / Valider')
@section('page-title', 'Valider une carte au comptoir')
@section('page-subtitle', 'Scanne le QR de la carte du client ou saisis le code + PIN manuellement')

@push('head')
    <script src="https://unpkg.com/html5-qrcode@2.3.10/html5-qrcode.min.js"></script>
@endpush

@section('content')
<div x-data="ownerScan()" x-init="init()" style="max-width:720px;margin:0 auto;">

    {{-- Tabs --}}
    <div style="display:flex;gap:6px;background:white;padding:6px;border:1px solid #E2E8F0;border-radius:14px;margin-bottom:18px;">
        <button type="button" @click="setMode('manual')"
                :style="mode==='manual' ? 'background:#0F172A;color:white;' : 'background:transparent;color:#475569;'"
                style="flex:1;padding:11px;border:0;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:7px;">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h2L4 14m5-4h6m-3-4v8m4-4v4M9 10v4m-6 7h18a2 2 0 002-2V5a2 2 0 00-2-2H3a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            Saisie code + PIN
        </button>
        <button type="button" @click="setMode('qr')"
                :style="mode==='qr' ? 'background:#0F172A;color:white;' : 'background:transparent;color:#475569;'"
                style="flex:1;padding:11px;border:0;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:7px;">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            Scan QR caméra
        </button>
    </div>

    {{-- =================== Step 1 : LOOKUP =================== --}}
    <template x-if="!purchase">
        <div>
            {{-- Manual --}}
            <div x-show="mode==='manual'" style="background:white;border:1px solid #E2E8F0;border-radius:14px;padding:22px;box-shadow:0 1px 2px rgba(15,23,42,.04);">
                <h3 style="margin:0 0 14px;font-family:'Space Grotesk','Inter',sans-serif;font-size:15px;font-weight:800;color:#0F172A;">Saisis le code + le PIN</h3>
                <div style="display:grid;grid-template-columns:2fr 1fr;gap:10px;">
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#334155;margin-bottom:6px;">Code (8 chiffres)</label>
                        <input type="text" x-model="form.code" maxlength="8" inputmode="numeric" pattern="[0-9]{8}"
                               @input="form.code = form.code.replace(/\D/g,'').slice(0,8)"
                               placeholder="••••••••"
                               style="width:100%;padding:12px 14px;border:1.5px solid #E2E8F0;border-radius:10px;font-size:18px;font-weight:700;letter-spacing:.18em;font-family:ui-monospace,monospace;text-align:center;outline:none;">
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#334155;margin-bottom:6px;">PIN (4 chiffres)</label>
                        <input type="text" x-model="form.pin" maxlength="4" inputmode="numeric" pattern="[0-9]{4}"
                               @input="form.pin = form.pin.replace(/\D/g,'').slice(0,4)"
                               placeholder="••••"
                               style="width:100%;padding:12px 14px;border:1.5px solid #E2E8F0;border-radius:10px;font-size:18px;font-weight:700;letter-spacing:.18em;font-family:ui-monospace,monospace;text-align:center;outline:none;">
                    </div>
                </div>
                <button type="button" @click="lookupManual()" :disabled="loading"
                        style="margin-top:14px;width:100%;padding:14px;background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;border:0;border-radius:11px;font-size:14px;font-weight:800;cursor:pointer;"
                        :style="loading && 'opacity:.5;cursor:wait;'">
                    <span x-show="!loading">Vérifier la carte →</span>
                    <span x-show="loading" x-cloak>Recherche…</span>
                </button>
            </div>

            {{-- QR --}}
            <div x-show="mode==='qr'" x-cloak style="background:white;border:1px solid #E2E8F0;border-radius:14px;padding:22px;box-shadow:0 1px 2px rgba(15,23,42,.04);">
                <h3 style="margin:0 0 8px;font-family:'Space Grotesk','Inter',sans-serif;font-size:15px;font-weight:800;color:#0F172A;">Scanne le QR de la carte</h3>
                <p style="font-size:12px;color:#64748B;margin:0 0 12px;">Place le QR code dans le viseur. La caméra se ferme automatiquement dès la détection.</p>
                <div id="qr-reader" style="border-radius:12px;overflow:hidden;background:#0F172A;min-height:260px;"></div>
                <button type="button" x-show="!qrActive" @click="startQr()"
                        style="margin-top:12px;width:100%;padding:12px;background:#0F172A;color:white;border:0;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">
                    Activer la caméra
                </button>
                <button type="button" x-show="qrActive" @click="stopQr()" x-cloak
                        style="margin-top:12px;width:100%;padding:12px;background:#F1F5F9;color:#475569;border:0;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">
                    Arrêter la caméra
                </button>
            </div>

            {{-- Erreur lookup --}}
            <template x-if="lookupError">
                <div style="margin-top:14px;padding:12px 14px;background:#FEE2E2;color:#991B1B;border:1px solid #FCA5A5;border-radius:11px;font-size:13px;font-weight:600;" x-text="lookupError"></div>
            </template>
        </div>
    </template>

    {{-- =================== Step 2 : CONFIRMATION + REDEEM =================== --}}
    <template x-if="purchase">
        <div style="background:white;border:1px solid #E2E8F0;border-radius:14px;overflow:hidden;box-shadow:0 1px 2px rgba(15,23,42,.04);">

            {{-- Hero carte --}}
            <div style="background:linear-gradient(135deg,#0F172A,#0F4F44);color:white;padding:22px 24px;display:flex;align-items:center;gap:16px;">
                <template x-if="purchase.card_visual">
                    <img :src="purchase.card_visual" style="width:64px;height:64px;border-radius:12px;object-fit:cover;flex-shrink:0;">
                </template>
                <template x-if="!purchase.card_visual">
                    <div style="width:64px;height:64px;border-radius:12px;background:linear-gradient(135deg,#44A08D,#4ECDC4);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:24px;">🎴</div>
                </template>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:700;color:rgba(255,255,255,.55);">Carte trouvée</div>
                    <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:800;margin-top:2px;" x-text="purchase.card_name"></div>
                    <div style="font-size:12px;color:rgba(255,255,255,.7);margin-top:2px;" x-text="'Client : ' + purchase.buyer_name + (purchase.buyer_phone ? ' · ' + purchase.buyer_phone : '')"></div>
                </div>
            </div>

            {{-- Solde --}}
            <div style="padding:20px 24px;border-bottom:1px solid #F1F5F9;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div>
                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:700;color:#94A3B8;">Solde restant</div>
                        <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:28px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:4px;">
                            <span x-text="fmt(purchase.remaining_balance)"></span> <span style="font-size:13px;color:#94A3B8;">FCFA</span>
                        </div>
                        <div style="font-size:11px;color:#64748B;margin-top:2px;" x-text="'sur ' + fmt(purchase.amount) + ' FCFA achetés'"></div>
                    </div>
                    <div>
                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:700;color:#94A3B8;">Valable jusqu'au</div>
                        <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:15px;font-weight:700;color:#0F172A;margin-top:6px;" x-text="purchase.expires_at"></div>
                    </div>
                </div>
            </div>

            {{-- Form redeem --}}
            <div style="padding:20px 24px;">
                <h4 style="margin:0 0 12px;font-size:13px;font-weight:800;color:#0F172A;">Combien débiter ?</h4>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
                    <template x-for="amt in quickAmounts()" :key="amt">
                        <button type="button" @click="form.amount = amt"
                                :style="form.amount === amt ? 'background:#0F172A;color:white;border-color:#0F172A;' : 'background:white;color:#0F172A;border-color:#E2E8F0;'"
                                style="padding:8px 14px;border:1.5px solid;border-radius:9999px;font-size:12px;font-weight:700;font-variant-numeric:tabular-nums;cursor:pointer;"
                                x-text="fmt(amt) + ' F'"></button>
                    </template>
                </div>
                <div style="display:grid;grid-template-columns:2fr 1fr;gap:10px;">
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#334155;margin-bottom:6px;">Montant à débiter (FCFA)</label>
                        <input type="number" x-model.number="form.amount" :max="purchase.remaining_balance" min="1" step="500"
                               style="width:100%;padding:12px 14px;border:1.5px solid #E2E8F0;border-radius:10px;font-size:18px;font-weight:700;font-variant-numeric:tabular-nums;text-align:right;outline:none;">
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#334155;margin-bottom:6px;">PIN client</label>
                        <input type="text" x-model="form.confirmPin" maxlength="4" inputmode="numeric"
                               @input="form.confirmPin = form.confirmPin.replace(/\D/g,'').slice(0,4)"
                               placeholder="••••"
                               style="width:100%;padding:12px 14px;border:1.5px solid #E2E8F0;border-radius:10px;font-size:18px;font-weight:700;letter-spacing:.18em;font-family:ui-monospace,monospace;text-align:center;outline:none;">
                    </div>
                </div>
                <div style="margin-top:10px;">
                    <label style="display:block;font-size:11px;font-weight:700;color:#334155;margin-bottom:6px;">Note (optionnel)</label>
                    <input type="text" x-model="form.notes" maxlength="500" placeholder="Référence interne, table, etc."
                           style="width:100%;padding:10px 14px;border:1.5px solid #E2E8F0;border-radius:10px;font-size:13px;outline:none;">
                </div>

                <template x-if="redeemError">
                    <div style="margin-top:12px;padding:10px 12px;background:#FEE2E2;color:#991B1B;border:1px solid #FCA5A5;border-radius:10px;font-size:12px;font-weight:600;" x-text="redeemError"></div>
                </template>

                <div style="display:flex;gap:10px;margin-top:16px;">
                    <button type="button" @click="reset()"
                            style="padding:13px 18px;background:#F1F5F9;color:#334155;border:0;border-radius:11px;font-size:13px;font-weight:700;cursor:pointer;">
                        Annuler
                    </button>
                    <button type="button" @click="redeem()" :disabled="submitting || !form.amount || !form.confirmPin"
                            style="flex:1;padding:13px;background:linear-gradient(135deg,#10B981,#059669);color:white;border:0;border-radius:11px;font-size:14px;font-weight:800;cursor:pointer;box-shadow:0 10px 24px -10px rgba(16,185,129,.5);"
                            :style="(submitting || !form.amount || !form.confirmPin) && 'opacity:.5;cursor:not-allowed;box-shadow:none;'">
                        <span x-show="!submitting">✓ Confirmer la validation</span>
                        <span x-show="submitting" x-cloak>Validation…</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- =================== Step 3 : SUCCESS =================== --}}
    <template x-if="success">
        <div x-cloak style="margin-top:18px;background:linear-gradient(135deg,#10B981,#059669);color:white;border-radius:14px;padding:24px;text-align:center;box-shadow:0 12px 28px -10px rgba(16,185,129,.5);">
            <div style="width:64px;height:64px;border-radius:50%;background:rgba(255,255,255,.18);margin:0 auto 12px;display:flex;align-items:center;justify-content:center;">
                <svg width="36" height="36" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h3 style="margin:0;font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;">Validation enregistrée</h3>
            <p style="margin:6px 0 14px;font-size:14px;opacity:.9;">
                <span style="font-weight:800;" x-text="fmt(success.amount_used) + ' FCFA'"></span> débités —
                solde restant : <span style="font-weight:800;" x-text="fmt(success.balance_after) + ' FCFA'"></span>
            </p>
            <button type="button" @click="newScan()"
                    style="padding:12px 22px;background:white;color:#059669;border:0;border-radius:11px;font-size:13px;font-weight:800;cursor:pointer;">
                Scanner une autre carte
            </button>
        </div>
    </template>
</div>

@push('scripts')
<script>
function ownerScan() {
    return {
        mode: 'manual',
        loading: false,
        submitting: false,
        purchase: null,
        success: null,
        lookupError: '',
        redeemError: '',
        form: { code: '', pin: '', amount: 0, confirmPin: '', notes: '' },
        qrActive: false,
        html5Qr: null,
        scanMethod: 'code',

        init() {
            window.addEventListener('beforeunload', () => this.stopQr());
        },

        setMode(m) {
            this.mode = m;
            this.lookupError = '';
            if (m !== 'qr') this.stopQr();
        },

        fmt(n) { return Number(n || 0).toLocaleString('fr-FR'); },

        quickAmounts() {
            if (!this.purchase) return [];
            const rem = this.purchase.remaining_balance;
            const opts = [1000, 5000, 10000, 25000, 50000].filter(v => v <= rem);
            if (!opts.includes(rem) && rem > 0) opts.push(rem);
            return opts;
        },

        async lookupManual() {
            this.lookupError = '';
            if (this.form.code.length !== 8 || this.form.pin.length !== 4) {
                this.lookupError = 'Code (8 chiffres) et PIN (4 chiffres) requis.';
                return;
            }
            this.loading = true;
            try {
                const res = await fetch('{{ route('owner.scan.lookup') }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ mode: 'manual', code: this.form.code, pin: this.form.pin }),
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    this.lookupError = data.message || 'Carte introuvable.';
                    return;
                }
                this.purchase = data.purchase;
                this.scanMethod = 'code';
                this.form.amount = data.purchase.remaining_balance;
                this.form.confirmPin = this.form.pin; // pré-rempli puisqu'il vient de le saisir
            } catch (e) {
                this.lookupError = 'Erreur réseau : ' + e.message;
            } finally {
                this.loading = false;
            }
        },

        async lookupQr(decoded) {
            this.lookupError = '';
            this.loading = true;
            try {
                const res = await fetch('{{ route('owner.scan.lookup') }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ mode: 'qr', qr: decoded }),
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    this.lookupError = data.message || 'QR invalide.';
                    return;
                }
                this.purchase = data.purchase;
                this.scanMethod = 'qr';
                this.form.amount = data.purchase.remaining_balance;
            } catch (e) {
                this.lookupError = 'Erreur réseau : ' + e.message;
            } finally {
                this.loading = false;
            }
        },

        async startQr() {
            if (!window.Html5Qrcode) {
                this.lookupError = 'Librairie QR non chargée.';
                return;
            }
            try {
                this.html5Qr = new Html5Qrcode('qr-reader');
                this.qrActive = true;
                await this.html5Qr.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 240, height: 240 } },
                    async (decodedText) => {
                        // Pause pour éviter scans multiples
                        await this.stopQr();
                        await this.lookupQr(decodedText);
                    },
                    () => { /* ignore non-match frames */ }
                );
            } catch (e) {
                this.qrActive = false;
                this.lookupError = 'Caméra inaccessible : ' + (e.message || e);
            }
        },

        async stopQr() {
            if (!this.html5Qr || !this.qrActive) return;
            try { await this.html5Qr.stop(); await this.html5Qr.clear(); } catch (e) {}
            this.qrActive = false;
            this.html5Qr = null;
        },

        async redeem() {
            this.redeemError = '';
            this.submitting = true;
            try {
                const res = await fetch('{{ route('owner.scan.redeem') }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        purchase_id: this.purchase.id,
                        amount: this.form.amount,
                        pin: this.form.confirmPin,
                        scan_method: this.scanMethod,
                        notes: this.form.notes || null,
                    }),
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    this.redeemError = data.message || 'Erreur de validation.';
                    return;
                }
                this.success = data.redemption;
                this.purchase = null;
            } catch (e) {
                this.redeemError = 'Erreur réseau : ' + e.message;
            } finally {
                this.submitting = false;
            }
        },

        reset() {
            this.purchase = null;
            this.form = { code: '', pin: '', amount: 0, confirmPin: '', notes: '' };
            this.redeemError = '';
        },

        newScan() {
            this.success = null;
            this.reset();
        },
    };
}
</script>
@endpush
@endsection
