@extends('owner.layouts.owner')

@section('title', 'Scanner / Valider')
@section('page-title', 'Valider une carte au comptoir')
@section('page-subtitle', 'Scanne le QR du client ou saisis le code + PIN manuellement')

@push('head')
    <script src="https://unpkg.com/html5-qrcode@2.3.10/html5-qrcode.min.js"></script>
    <style>
        .scan-wrap { max-width: 720px; margin: 0 auto; }

        /* ===== Tabs ===== */
        .scan-tabs {
            display: flex; gap: 6px;
            background: white; padding: 6px;
            border: 1px solid #E2E8F0; border-radius: 14px;
            margin-bottom: 18px;
            box-shadow: 0 1px 2px rgba(15,23,42,.04);
        }
        .scan-tab {
            flex: 1;
            padding: 12px 14px;
            border: 0; border-radius: 10px;
            background: transparent; color: #475569;
            font-family: inherit;
            font-size: 13px; font-weight: 700;
            cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            transition: background .15s, color .15s;
        }
        .scan-tab:hover { background: #F8FAFC; }
        .scan-tab.is-active {
            background: linear-gradient(135deg, #0F172A, #1E293B);
            color: white;
            box-shadow: 0 6px 14px -6px rgba(15,23,42,.4);
        }
        .scan-tab svg { width: 15px; height: 15px; }

        /* ===== Card ===== */
        .scan-card {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 1px 2px rgba(15,23,42,.04);
        }
        .scan-card-title {
            margin: 0 0 6px;
            font-family: 'Space Grotesk','Inter',sans-serif;
            font-size: 16px; font-weight: 800; color: #0F172A;
        }
        .scan-card-sub {
            margin: 0 0 16px;
            font-size: 13px; color: #64748B;
        }

        /* ===== Labels & generic grids ===== */
        .scan-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 12px; }
        @media (max-width: 540px) { .scan-grid { grid-template-columns: 1fr; } }
        .scan-field-label {
            display: block;
            font-size: 11px; font-weight: 700; color: #334155;
            text-transform: uppercase; letter-spacing: .06em;
            margin-bottom: 10px;
            text-align: center;
        }

        /* ===== OTP inputs (soft & modern) ===== */
        .otp-section { margin-bottom: 22px; }
        .otp-section:last-of-type { margin-bottom: 6px; }
        .otp-row {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            flex-wrap: nowrap;
        }
        .otp-box {
            width: 52px; height: 60px;
            border: 1.5px solid #E2E8F0; border-radius: 14px;
            background: #F8FAFC;
            color: #0F172A;
            font-family: ui-monospace, 'JetBrains Mono', 'SF Mono', monospace;
            font-size: 24px; font-weight: 800;
            text-align: center;
            outline: none;
            padding: 0;
            transition: all .18s ease;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.4);
            -moz-appearance: textfield;
        }
        .otp-box::-webkit-outer-spin-button,
        .otp-box::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .otp-box.is-filled {
            background: white;
            border-color: #94A3B8;
        }
        .otp-box:focus {
            border-color: #44A08D;
            background: white;
            box-shadow: 0 0 0 4px rgba(68,160,141,.16), inset 0 1px 0 rgba(255,255,255,.4);
            transform: translateY(-2px);
        }
        .otp-sep {
            display: inline-flex;
            width: 8px; align-items: center; justify-content: center;
            color: #CBD5E1; font-weight: 700; font-size: 18px;
            user-select: none;
        }
        @media (max-width: 540px) {
            .otp-box { width: 38px; height: 50px; font-size: 19px; border-radius: 11px; }
            .otp-row { gap: 6px; }
            .otp-sep { width: 4px; font-size: 14px; }
        }
        @media (max-width: 360px) {
            .otp-box { width: 32px; height: 44px; font-size: 16px; border-radius: 10px; }
            .otp-row { gap: 4px; }
        }

        /* ===== Plain inputs (montant, notes) ===== */
        .scan-input {
            width: 100%;
            padding: 14px 16px;
            border: 1.5px solid #E2E8F0; border-radius: 12px;
            font-size: 22px; font-weight: 800;
            letter-spacing: .22em;
            font-family: ui-monospace, 'JetBrains Mono', monospace;
            text-align: center;
            color: #0F172A;
            background: #F8FAFC;
            outline: none;
            transition: border-color .15s, background .15s, box-shadow .15s;
        }
        .scan-input:focus {
            border-color: #44A08D;
            background: white;
            box-shadow: 0 0 0 4px rgba(68,160,141,.12);
        }
        .scan-input::placeholder { color: #CBD5E1; letter-spacing: .22em; }

        /* ===== Primary CTA ===== */
        .scan-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%;
            padding: 15px 20px;
            border: 0; border-radius: 12px;
            font-family: inherit; font-size: 14px; font-weight: 800;
            cursor: pointer;
            transition: transform .12s ease, box-shadow .15s, filter .15s;
        }
        .scan-btn--primary {
            background: linear-gradient(135deg, #44A08D, #4ECDC4);
            color: white;
            box-shadow: 0 14px 28px -10px rgba(78,205,196,.55), inset 0 1px 0 rgba(255,255,255,.25);
        }
        .scan-btn--primary:hover:not(:disabled) { transform: translateY(-1px); filter: brightness(1.05); }
        .scan-btn--primary:active { transform: translateY(0); }
        .scan-btn--success {
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            box-shadow: 0 14px 28px -10px rgba(16,185,129,.55), inset 0 1px 0 rgba(255,255,255,.25);
        }
        .scan-btn--success:hover:not(:disabled) { transform: translateY(-1px); filter: brightness(1.05); }
        .scan-btn--dark {
            background: #0F172A; color: white;
        }
        .scan-btn--ghost {
            background: #F1F5F9; color: #334155;
        }
        .scan-btn:disabled { opacity: .5; cursor: not-allowed; box-shadow: none; transform: none; }
        .scan-btn-mt { margin-top: 16px; }

        /* ===== QR camera ===== */
        .scan-qr-frame {
            background: #0F172A;
            border-radius: 14px;
            overflow: hidden;
            min-height: 280px;
            position: relative;
        }
        .scan-qr-frame #qr-reader {
            min-height: 280px;
            display: flex; align-items: center; justify-content: center;
            color: rgba(255,255,255,.5); font-size: 13px;
        }
        .scan-qr-frame #qr-reader > div { width: 100%; }
        .scan-qr-frame #qr-reader video { border-radius: 14px 14px 0 0; max-width: 100%; }
        .scan-qr-frame #qr-reader__dashboard_section_csr button {
            background: white !important; color: #0F172A !important;
            border-radius: 8px !important; padding: 6px 12px !important;
            font-size: 12px !important; font-weight: 700 !important;
        }

        /* ===== Alerts ===== */
        .scan-alert {
            display: flex; align-items: flex-start; gap: 10px;
            margin-top: 14px;
            padding: 12px 14px;
            border-radius: 11px;
            font-size: 13px; font-weight: 600;
        }
        .scan-alert--error {
            background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA;
        }
        .scan-alert svg { width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px; }

        /* ===== Confirmation card ===== */
        .scan-confirm {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 12px 32px -12px rgba(15,23,42,.18);
        }
        .scan-confirm-hero {
            background: linear-gradient(135deg, #0F172A 0%, #0F4F44 100%);
            color: white;
            padding: 22px 24px;
            display: flex; align-items: center; gap: 16px;
        }
        .scan-confirm-visual {
            width: 64px; height: 64px;
            border-radius: 14px;
            object-fit: cover;
            flex-shrink: 0;
            background: linear-gradient(135deg, #44A08D, #4ECDC4);
            display: flex; align-items: center; justify-content: center;
            font-size: 26px;
        }
        .scan-confirm-hero-label {
            font-size: 10px; text-transform: uppercase; letter-spacing: .08em;
            font-weight: 700; color: rgba(255,255,255,.55);
        }
        .scan-confirm-hero-name {
            font-family: 'Space Grotesk','Inter',sans-serif;
            font-size: 18px; font-weight: 800; margin-top: 2px;
        }
        .scan-confirm-hero-buyer { font-size: 12px; color: rgba(255,255,255,.72); margin-top: 2px; }

        .scan-balance {
            padding: 20px 24px;
            border-bottom: 1px solid #F1F5F9;
            display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
        }
        @media (max-width: 540px) { .scan-balance { grid-template-columns: 1fr; } }
        .scan-balance-label { font-size: 10px; text-transform: uppercase; letter-spacing: .08em; font-weight: 700; color: #94A3B8; }
        .scan-balance-value {
            font-family: 'Space Grotesk','Inter',sans-serif;
            font-size: 28px; font-weight: 800; color: #0F172A;
            font-variant-numeric: tabular-nums; line-height: 1.1;
            margin-top: 4px;
        }
        .scan-balance-value small { font-size: 13px; color: #94A3B8; font-weight: 600; margin-left: 4px; }
        .scan-balance-meta { font-size: 11px; color: #64748B; margin-top: 2px; }
        .scan-balance-date {
            font-family: 'Space Grotesk','Inter',sans-serif;
            font-size: 16px; font-weight: 700; color: #0F172A; margin-top: 6px;
        }

        .scan-body { padding: 22px 24px; }
        .scan-body h4 { margin: 0 0 12px; font-size: 13px; font-weight: 800; color: #0F172A; text-transform: uppercase; letter-spacing: .06em; }

        .scan-quick { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
        .scan-quick-btn {
            padding: 8px 14px;
            border: 1.5px solid #E2E8F0; border-radius: 9999px;
            background: white; color: #0F172A;
            font-size: 12px; font-weight: 700; font-family: inherit;
            font-variant-numeric: tabular-nums;
            cursor: pointer; transition: all .15s;
        }
        .scan-quick-btn:hover { border-color: #94A3B8; }
        .scan-quick-btn.is-active {
            background: #0F172A; color: white; border-color: #0F172A;
        }

        .scan-amount-input {
            width: 100%; padding: 14px 16px;
            border: 1.5px solid #E2E8F0; border-radius: 12px;
            font-size: 22px; font-weight: 800;
            font-variant-numeric: tabular-nums;
            text-align: right;
            color: #0F172A; background: #F8FAFC;
            outline: none; transition: border-color .15s, background .15s, box-shadow .15s;
            font-family: inherit;
        }
        .scan-amount-input:focus {
            border-color: #44A08D; background: white;
            box-shadow: 0 0 0 4px rgba(68,160,141,.12);
        }

        .scan-notes-input {
            width: 100%; padding: 11px 14px;
            border: 1.5px solid #E2E8F0; border-radius: 11px;
            font-size: 13px; font-family: inherit; color: #0F172A;
            background: white; outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        .scan-notes-input:focus { border-color: #44A08D; box-shadow: 0 0 0 3px rgba(68,160,141,.12); }

        .scan-actions { display: flex; gap: 10px; margin-top: 18px; }
        .scan-actions .scan-btn { flex: 1; }
        .scan-actions .scan-btn--cancel { flex: 0 0 auto; padding-left: 22px; padding-right: 22px; }

        /* ===== Success panel ===== */
        .scan-success {
            margin-top: 18px;
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            border-radius: 18px;
            padding: 28px 24px;
            text-align: center;
            box-shadow: 0 16px 36px -12px rgba(16,185,129,.5);
        }
        .scan-success-icon {
            width: 68px; height: 68px;
            border-radius: 50%; background: rgba(255,255,255,.20);
            margin: 0 auto 14px;
            display: flex; align-items: center; justify-content: center;
        }
        .scan-success h3 {
            margin: 0; font-family: 'Space Grotesk','Inter',sans-serif;
            font-size: 24px; font-weight: 800; letter-spacing: -0.01em;
        }
        .scan-success p { margin: 8px 0 16px; font-size: 14px; opacity: .92; }
        .scan-success strong { font-weight: 800; }
        .scan-success-btn {
            display: inline-block;
            padding: 13px 26px;
            background: white; color: #059669;
            border: 0; border-radius: 12px;
            font-size: 13px; font-weight: 800; font-family: inherit;
            cursor: pointer;
        }

        [x-cloak] { display: none !important; }
    </style>
@endpush

@section('content')
<div class="scan-wrap" x-data="ownerScan()" x-init="init()">

    {{-- Tabs --}}
    <div class="scan-tabs" x-show="!purchase && !success">
        <button type="button" class="scan-tab" :class="{ 'is-active': mode === 'manual' }" @click="setMode('manual')">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h2L4 14m5-4h6m-3-4v8m4-4v4M9 10v4m-6 7h18a2 2 0 002-2V5a2 2 0 00-2-2H3a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            Saisie code + PIN
        </button>
        <button type="button" class="scan-tab" :class="{ 'is-active': mode === 'qr' }" @click="setMode('qr')">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            Scan QR caméra
        </button>
    </div>

    {{-- ===== Step 1 : LOOKUP ===== --}}
    <template x-if="!purchase && !success">
        <div>
            {{-- Manual --}}
            <div x-show="mode === 'manual'" class="scan-card">
                <h3 class="scan-card-title">Saisis le code + le PIN</h3>
                <p class="scan-card-sub">Le code à 8 chiffres et le PIN à 4 chiffres figurent sur la carte du client.</p>

                <div class="otp-section">
                    <label class="scan-field-label">Code de la carte</label>
                    <div class="otp-row">
                        @for ($i = 0; $i < 4; $i++)
                            <input type="tel" inputmode="numeric" maxlength="1" pattern="[0-9]*"
                                   :value="form.code[{{ $i }}] || ''"
                                   :class="{ 'is-filled': form.code[{{ $i }}] }"
                                   @input="otpInput($event, 'code', {{ $i }}, 8)"
                                   @keydown="otpKey($event, {{ $i }})"
                                   @paste="otpPaste($event, 'code', 8)"
                                   class="otp-box">
                        @endfor
                        <span class="otp-sep">·</span>
                        @for ($i = 4; $i < 8; $i++)
                            <input type="tel" inputmode="numeric" maxlength="1" pattern="[0-9]*"
                                   :value="form.code[{{ $i }}] || ''"
                                   :class="{ 'is-filled': form.code[{{ $i }}] }"
                                   @input="otpInput($event, 'code', {{ $i }}, 8)"
                                   @keydown="otpKey($event, {{ $i }})"
                                   @paste="otpPaste($event, 'code', 8)"
                                   class="otp-box">
                        @endfor
                    </div>
                </div>

                <div class="otp-section">
                    <label class="scan-field-label">PIN à 4 chiffres</label>
                    <div class="otp-row">
                        @for ($i = 0; $i < 4; $i++)
                            <input type="tel" inputmode="numeric" maxlength="1" pattern="[0-9]*"
                                   :value="form.pin[{{ $i }}] || ''"
                                   :class="{ 'is-filled': form.pin[{{ $i }}] }"
                                   @input="otpInput($event, 'pin', {{ $i }}, 4)"
                                   @keydown="otpKey($event, {{ $i }})"
                                   @paste="otpPaste($event, 'pin', 4)"
                                   class="otp-box">
                        @endfor
                    </div>
                </div>

                <button type="button" class="scan-btn scan-btn--primary scan-btn-mt"
                        @click="lookupManual()" :disabled="loading || !isComplete('code', 8) || !isComplete('pin', 4)">
                    <span x-show="!loading">Vérifier la carte</span>
                    <span x-show="loading" x-cloak>Recherche…</span>
                    <svg x-show="!loading" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </button>
            </div>

            {{-- QR --}}
            <div x-show="mode === 'qr'" x-cloak class="scan-card">
                <h3 class="scan-card-title">Scanne le QR de la carte</h3>
                <p class="scan-card-sub">Place le QR code dans le viseur. La caméra se ferme automatiquement à la détection.</p>

                <div class="scan-qr-frame">
                    <div id="qr-reader">
                        <span x-show="!qrActive">Caméra inactive — clique « Activer » ci-dessous.</span>
                    </div>
                </div>

                <button type="button" class="scan-btn scan-btn--dark scan-btn-mt"
                        x-show="!qrActive" @click="startQr()">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    Activer la caméra
                </button>
                <button type="button" class="scan-btn scan-btn--ghost scan-btn-mt"
                        x-show="qrActive" x-cloak @click="stopQr()">
                    Arrêter la caméra
                </button>
            </div>

            {{-- Erreur lookup --}}
            <template x-if="lookupError">
                <div class="scan-alert scan-alert--error">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span x-text="lookupError"></span>
                </div>
            </template>
        </div>
    </template>

    {{-- ===== Step 2 : CONFIRMATION + REDEEM ===== --}}
    <template x-if="purchase">
        <div class="scan-confirm">
            <div class="scan-confirm-hero">
                <template x-if="purchase.card_visual">
                    <img :src="purchase.card_visual" class="scan-confirm-visual" alt="">
                </template>
                <template x-if="!purchase.card_visual">
                    <div class="scan-confirm-visual">🎴</div>
                </template>
                <div style="flex:1;min-width:0;">
                    <div class="scan-confirm-hero-label">Carte trouvée</div>
                    <div class="scan-confirm-hero-name" x-text="purchase.card_name"></div>
                    <div class="scan-confirm-hero-buyer" x-text="'Client : ' + purchase.buyer_name + (purchase.buyer_phone ? ' · ' + purchase.buyer_phone : '')"></div>
                </div>
            </div>

            <div class="scan-balance">
                <div>
                    <div class="scan-balance-label">Solde restant</div>
                    <div class="scan-balance-value">
                        <span x-text="fmt(purchase.remaining_balance)"></span><small>FCFA</small>
                    </div>
                    <div class="scan-balance-meta" x-text="'sur ' + fmt(purchase.amount) + ' FCFA achetés'"></div>
                </div>
                <div>
                    <div class="scan-balance-label">Valable jusqu'au</div>
                    <div class="scan-balance-date" x-text="purchase.expires_at"></div>
                </div>
            </div>

            <div class="scan-body">
                <h4>Combien débiter ?</h4>

                <div class="scan-quick">
                    <template x-for="amt in quickAmounts()" :key="amt">
                        <button type="button" class="scan-quick-btn"
                                :class="{ 'is-active': form.amount === amt }"
                                @click="form.amount = amt"
                                x-text="fmt(amt) + ' F'"></button>
                    </template>
                </div>

                <div>
                    <label class="scan-field-label" style="text-align:left;">Montant à débiter (FCFA)</label>
                    <input type="number" x-model.number="form.amount"
                           :max="purchase.remaining_balance" min="1" step="500"
                           class="scan-amount-input">
                </div>

                <div class="otp-section" style="margin-top:16px;">
                    <label class="scan-field-label">PIN du client (4 chiffres)</label>
                    <div class="otp-row">
                        @for ($i = 0; $i < 4; $i++)
                            <input type="tel" inputmode="numeric" maxlength="1" pattern="[0-9]*"
                                   :value="form.confirmPin[{{ $i }}] || ''"
                                   :class="{ 'is-filled': form.confirmPin[{{ $i }}] }"
                                   @input="otpInput($event, 'confirmPin', {{ $i }}, 4)"
                                   @keydown="otpKey($event, {{ $i }})"
                                   @paste="otpPaste($event, 'confirmPin', 4)"
                                   class="otp-box">
                        @endfor
                    </div>
                </div>

                <div style="margin-top:14px;">
                    <label class="scan-field-label">Note (optionnel)</label>
                    <input type="text" x-model="form.notes" maxlength="500"
                           placeholder="Référence interne, table, etc."
                           class="scan-notes-input">
                </div>

                <template x-if="redeemError">
                    <div class="scan-alert scan-alert--error">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span x-text="redeemError"></span>
                    </div>
                </template>

                <div class="scan-actions">
                    <button type="button" class="scan-btn scan-btn--ghost scan-btn--cancel" @click="reset()">
                        Annuler
                    </button>
                    <button type="button" class="scan-btn scan-btn--success"
                            @click="redeem()" :disabled="submitting || !form.amount || !isComplete('confirmPin', 4)">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span x-show="!submitting">Confirmer la validation</span>
                        <span x-show="submitting" x-cloak>Validation…</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- ===== Step 3 : SUCCESS ===== --}}
    <template x-if="success">
        <div class="scan-success" x-cloak>
            <div class="scan-success-icon">
                <svg width="36" height="36" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h3>Validation enregistrée</h3>
            <p>
                <strong x-text="fmt(success.amount_used) + ' FCFA'"></strong> débités —
                solde restant : <strong x-text="fmt(success.balance_after) + ' FCFA'"></strong>
            </p>
            <button type="button" class="scan-success-btn" @click="newScan()">
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
        form: {
            code:        ['','','','','','','',''],
            pin:         ['','','',''],
            confirmPin:  ['','','',''],
            amount:      0,
            notes:       '',
        },
        qrActive: false,
        html5Qr: null,
        scanMethod: 'code',

        init() {
            window.addEventListener('beforeunload', () => this.stopQr());
        },

        /* ===== OTP handlers (modèle array : chaque slot = '' ou un chiffre) ===== */
        joined(key) { return this.form[key].join(''); },
        isComplete(key, len) {
            return this.form[key].length === len
                && this.form[key].every(d => d !== '');
        },

        otpInput(e, key, idx, len) {
            const raw = (e.target.value || '').replace(/\D/g, '');

            // Multi-chiffres (autofill SMS / paste partiel) : étale à partir de idx
            if (raw.length > 1) {
                for (let k = 0; k < raw.length && idx + k < len; k++) {
                    this.form[key][idx + k] = raw[k];
                }
                const target = Math.min(idx + raw.length, len - 1);
                this.$nextTick(() => {
                    e.target.parentElement.querySelectorAll('input.otp-box')[target]?.focus();
                });
                return;
            }

            // Single digit (ou vide après backspace)
            this.form[key][idx] = raw; // '' ou un chiffre

            if (raw) {
                this.$nextTick(() => {
                    e.target.parentElement.querySelectorAll('input.otp-box')[idx + 1]?.focus();
                });
            }
        },

        otpKey(e, idx) {
            if (e.key === 'Backspace') {
                if (!e.target.value) {
                    const prev = e.target.parentElement.querySelectorAll('input.otp-box')[idx - 1];
                    if (prev) { prev.focus(); e.preventDefault(); }
                }
            } else if (e.key === 'ArrowLeft') {
                const prev = e.target.parentElement.querySelectorAll('input.otp-box')[idx - 1];
                if (prev) { prev.focus(); e.preventDefault(); }
            } else if (e.key === 'ArrowRight') {
                const next = e.target.parentElement.querySelectorAll('input.otp-box')[idx + 1];
                if (next) { next.focus(); e.preventDefault(); }
            }
        },

        otpPaste(e, key, len) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            const clean = text.replace(/\D/g, '').slice(0, len);
            if (!clean) return;
            for (let i = 0; i < len; i++) {
                this.form[key][i] = clean[i] || '';
            }
            const target = Math.min(clean.length, len - 1);
            this.$nextTick(() => {
                e.target.parentElement.querySelectorAll('input.otp-box')[target]?.focus();
            });
        },

        resetSlots(key, len) {
            this.form[key] = Array(len).fill('');
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
            if (!this.isComplete('code', 8) || !this.isComplete('pin', 4)) {
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
                    body: JSON.stringify({ mode: 'manual', code: this.joined('code'), pin: this.joined('pin') }),
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    this.lookupError = data.message || 'Carte introuvable.';
                    return;
                }
                this.purchase = data.purchase;
                this.scanMethod = 'code';
                this.form.amount = data.purchase.remaining_balance;
                // Pré-remplit confirmPin avec le PIN saisi
                this.form.confirmPin = [...this.form.pin];
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
                        pin: this.joined('confirmPin'),
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
            this.form = {
                code:       ['','','','','','','',''],
                pin:        ['','','',''],
                confirmPin: ['','','',''],
                amount:     0,
                notes:      '',
            };
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
