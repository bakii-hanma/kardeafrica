@extends('vendor.layouts.vendor')

@section('title', 'Paiement de la vente')

@section('content')
<div class="vch-wrap" x-data="vendorCheckout()">

    {{-- Top nav + steps --}}
    <a href="{{ route('vendor.sell') }}" class="vch-back">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Retour au panier
    </a>

    <div class="vch-head">
        <div>
            <div class="vch-eyebrow">Paiement</div>
            <h1 class="vch-title">Finaliser la vente</h1>
            <p class="vch-lead">Choisis le mode de paiement pour générer le QR code à donner au client.</p>
        </div>

        {{-- Steps indicator --}}
        <div class="vch-steps">
            <div class="vch-step vch-step--done">
                <span class="vch-step-dot"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span>
                <span class="vch-step-label">Panier</span>
            </div>
            <div class="vch-step-line vch-step-line--done"></div>
            <div class="vch-step vch-step--current">
                <span class="vch-step-dot">2</span>
                <span class="vch-step-label">Paiement</span>
            </div>
            <div class="vch-step-line"></div>
            <div class="vch-step">
                <span class="vch-step-dot">3</span>
                <span class="vch-step-label">QR client</span>
            </div>
        </div>
    </div>

    <div class="vch-grid">

        {{-- ============ COLONNE PRINCIPALE ============ --}}
        <div class="vch-main">

            {{-- Récap items --}}
            <section class="vch-card">
                <h2 class="vch-card-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    Récap de la vente
                    <span class="vch-card-pill">{{ count($cart) }} article{{ count($cart) > 1 ? 's' : '' }}</span>
                </h2>
                <div class="vch-items">
                    @foreach($cart as $item)
                        <div class="vch-item">
                            <div class="vch-item-visual" style="background-color:{{ $item['color'] ?? '#44A08D' }};">
                                @if(!empty($item['image_url']))
                                    <img src="{{ $item['image_url'] }}" alt="{{ $item['brand'] ?? '' }}" loading="lazy">
                                @else
                                    <span>{{ strtoupper(substr($item['brand'] ?? $item['name'], 0, 1)) }}</span>
                                @endif
                                <span class="vch-item-chip"></span>
                            </div>
                            <div class="vch-item-body">
                                <div class="vch-item-brand">{{ $item['brand'] ?? $item['name'] }}</div>
                                @if(!empty($item['brand']) && ($item['name'] ?? '') !== $item['brand'])
                                    <div class="vch-item-name">{{ $item['name'] }}</div>
                                @endif
                                <div class="vch-item-meta">{{ number_format($item['price'], 0, ',', ' ') }} FCFA × {{ $item['quantity'] }}</div>
                            </div>
                            <div class="vch-item-total">
                                {{ number_format($item['price'] * $item['quantity'], 0, ',', ' ') }}
                                <span>FCFA</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Infos client (purement informatives) --}}
            <section class="vch-card">
                <h2 class="vch-card-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Infos client <span class="vch-card-tag">facultatif</span>
                </h2>
                <p class="vch-card-help">Ces infos servent uniquement au suivi de la vente. Le payeur saisit ses propres coordonnées sur le portail E-Billing.</p>
                <div class="vch-fields">
                    <div class="vch-field">
                        <label>Nom du client</label>
                        <input type="text" x-model="customerName" placeholder="Ex : Mariam K.">
                    </div>
                    <div class="vch-field">
                        <label>Téléphone</label>
                        <input type="tel" x-model="customerPhone" placeholder="Ex : 07 41 23 45 67">
                    </div>
                </div>
            </section>

            {{-- Choix paiement --}}
            <section class="vch-card vch-card--pay">
                <h2 class="vch-card-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    Mode de paiement
                </h2>

                {{-- Erreur affichée --}}
                <div x-show="error" x-cloak class="vch-error">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span x-text="error"></span>
                </div>

                {{-- Bouton E-Billing --}}
                <button type="button" @click="payEbilling()" :disabled="loading" class="vch-pay vch-pay--ebilling">
                    <div class="vch-pay-l">
                        <div class="vch-pay-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                        <div>
                            <div class="vch-pay-title">Payer via E-Billing</div>
                            <div class="vch-pay-sub">Mobile Money &middot; Visa &middot; Mastercard</div>
                        </div>
                    </div>
                    <div class="vch-pay-r">
                        <svg x-show="loading === 'ebilling'" x-cloak class="vch-pay-spin" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none"/></svg>
                        <svg x-show="loading !== 'ebilling'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </div>
                </button>

                {{-- Bouton Encaisser cash physique --}}
                <button type="button" @click="payCash()" :disabled="loading" class="vch-pay vch-pay--cash">
                    <div class="vch-pay-l">
                        <div class="vch-pay-icon vch-pay-icon--cash">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
                        </div>
                        <div>
                            <div class="vch-pay-title">Encaisser en physique</div>
                            <div class="vch-pay-sub">Cash en main &middot; ton solde est verrouillé jusqu'à confirmation</div>
                        </div>
                    </div>
                    <div class="vch-pay-r">
                        <svg x-show="loading === 'cash'" x-cloak class="vch-pay-spin" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none"/></svg>
                        <svg x-show="loading !== 'cash'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </div>
                </button>

                {{-- Bouton simulation (DEV uniquement) --}}
                @if(config('app.debug'))
                    <div class="vch-divider">
                        <span>OU pour les tests</span>
                    </div>
                    <button type="button" @click="paySimulate()" :disabled="loading" class="vch-pay vch-pay--simulate">
                        <div class="vch-pay-l">
                            <div class="vch-pay-icon vch-pay-icon--ghost">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <div class="vch-pay-title">Simuler le paiement</div>
                                <div class="vch-pay-sub">DEV uniquement &middot; bypass E-Billing</div>
                            </div>
                        </div>
                        <div class="vch-pay-r">
                            <svg x-show="loading === 'simulate'" x-cloak class="vch-pay-spin" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none"/></svg>
                            <svg x-show="loading !== 'simulate'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </div>
                    </button>
                @endif
            </section>
        </div>

        {{-- ============ SIDEBAR : récap totaux + solde ============ --}}
        <aside class="vch-side">
            <div class="vch-summary">
                <div class="vch-summary-eyebrow">À encaisser</div>
                <div class="vch-summary-total">
                    {{ number_format($subtotal, 0, ',', ' ') }}
                    <span>FCFA</span>
                </div>

                <div class="vch-summary-rows">
                    <div class="vch-summary-row">
                        <span>Sous-total</span>
                        <span class="vch-summary-num">{{ number_format($subtotal, 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="vch-summary-row vch-summary-row--brand">
                        <span>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            Ta commission ({{ rtrim(rtrim(number_format($reseller->commission_rate, 2), '0'), '.') }}%)
                        </span>
                        <span class="vch-summary-num">+{{ number_format($commission, 0, ',', ' ') }} FCFA</span>
                    </div>
                </div>
            </div>

            {{-- Solde wallet --}}
            <div class="vch-wallet {{ $reseller->wallet_balance < $subtotal ? 'vch-wallet--low' : '' }}">
                <div class="vch-wallet-l">
                    <div class="vch-wallet-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <div>
                        <div class="vch-wallet-label">Solde wallet</div>
                        <div class="vch-wallet-value">{{ number_format($reseller->wallet_balance, 0, ',', ' ') }} <span>FCFA</span></div>
                    </div>
                </div>
                @if($reseller->wallet_balance < $subtotal)
                    <div class="vch-wallet-warn">
                        Manque <strong>{{ number_format($subtotal - $reseller->wallet_balance, 0, ',', ' ') }} FCFA</strong> — demande une recharge à ton gérant.
                    </div>
                @else
                    <div class="vch-wallet-ok">
                        ✓ Solde suffisant
                    </div>
                @endif
            </div>
        </aside>
    </div>
</div>

<style>
    .vch-wrap { max-width: 1100px; margin: 0 auto; }

    .vch-back {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 12px; font-weight: 700;
        color: #64748B;
        text-decoration: none;
        margin-bottom: 14px;
        padding: 7px 11px;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 9999px;
    }
    .vch-back:hover { color: #44A08D; border-color: #44A08D; }
    .vch-back svg { width: 13px; height: 13px; }

    /* HEAD */
    .vch-head {
        display: flex; align-items: flex-start; justify-content: space-between;
        gap: 18px; flex-wrap: wrap;
        margin-bottom: 16px;
    }
    .vch-eyebrow {
        font-size: 11px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.10em;
        color: #44A08D;
    }
    .vch-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 22px; font-weight: 800;
        color: #0F172A;
        margin: 4px 0 4px;
        letter-spacing: -0.01em;
    }
    .vch-lead { font-size: 12px; color: #64748B; margin: 0; }

    /* Steps indicator */
    .vch-steps {
        display: flex; align-items: center; gap: 6px;
    }
    .vch-step {
        display: flex; align-items: center; gap: 6px;
        color: #94A3B8;
    }
    .vch-step-dot {
        width: 26px; height: 26px;
        border-radius: 50%;
        background: white;
        border: 1.5px solid #E2E8F0;
        display: flex; align-items: center; justify-content: center;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800; font-size: 11px;
    }
    .vch-step-dot svg { width: 12px; height: 12px; }
    .vch-step-label {
        font-size: 11px; font-weight: 700;
        display: none;
    }
    @media (min-width: 540px) { .vch-step-label { display: inline; } }
    .vch-step--done {
        color: #047857;
    }
    .vch-step--done .vch-step-dot {
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        border-color: transparent;
        color: white;
        box-shadow: 0 4px 8px -3px rgba(78,205,196,0.45);
    }
    .vch-step--current { color: #44A08D; }
    .vch-step--current .vch-step-dot {
        border-color: #44A08D;
        color: #44A08D;
        background: #ECFDF5;
    }
    .vch-step-line {
        width: 32px; height: 1.5px;
        background: #E2E8F0;
        border-radius: 9999px;
    }
    .vch-step-line--done { background: #44A08D; }

    /* GRID */
    .vch-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
        align-items: start;
    }
    @media (min-width: 980px) {
        .vch-grid { grid-template-columns: 1fr 340px; }
        .vch-side { position: sticky; top: 90px; }
    }
    .vch-main { display: flex; flex-direction: column; gap: 14px; min-width: 0; }
    .vch-side { display: flex; flex-direction: column; gap: 12px; min-width: 0; }

    /* CARDS */
    .vch-card {
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 18px;
        padding: 20px;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }
    .vch-card--pay { padding-bottom: 16px; }
    .vch-card-title {
        display: flex; align-items: center; gap: 8px;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 14px; font-weight: 800;
        color: #0F172A;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin: 0 0 14px;
    }
    .vch-card-title svg { width: 15px; height: 15px; color: #44A08D; }
    .vch-card-pill {
        margin-left: auto;
        padding: 3px 10px;
        background: #F1F5F9;
        color: #475569;
        border-radius: 9999px;
        font-size: 10px; font-weight: 800;
        text-transform: none; letter-spacing: 0;
    }
    .vch-card-tag {
        font-family: 'Inter', sans-serif;
        font-size: 9px; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.06em;
        background: #FEF3C7;
        color: #B45309;
        padding: 2px 7px;
        border-radius: 9999px;
        margin-left: auto;
    }
    .vch-card-help {
        font-size: 12px; color: #64748B;
        margin: -6px 0 14px;
        line-height: 1.5;
    }

    /* ITEMS */
    .vch-items {
        display: flex; flex-direction: column;
        gap: 8px;
    }
    .vch-item {
        display: flex; align-items: center; gap: 12px;
        padding: 10px;
        background: #F8FAFC;
        border: 1px solid #F1F5F9;
        border-radius: 12px;
    }
    .vch-item-visual {
        position: relative;
        flex-shrink: 0;
        width: 48px; height: 48px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        overflow: hidden;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.20);
    }
    .vch-item-visual img { width: 100%; height: 100%; object-fit: cover; }
    .vch-item-visual span {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 18px; font-weight: 800;
        color: white;
    }
    .vch-item-chip {
        position: absolute; bottom: 4px; right: 4px;
        width: 12px; height: 8px;
        border-radius: 2px;
        background: linear-gradient(135deg, rgba(254,224,94,0.85), rgba(245,158,11,0.65));
        border: 1px solid rgba(252,211,77,0.40);
    }
    .vch-item-body { flex: 1; min-width: 0; }
    .vch-item-brand {
        font-family: 'Inter', sans-serif;
        font-size: 14px; font-weight: 800;
        color: #0F172A;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .vch-item-name {
        font-size: 11px; color: #64748B;
        margin-top: 2px;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .vch-item-meta {
        font-size: 11px; color: #94A3B8;
        margin-top: 3px;
        font-variant-numeric: tabular-nums;
    }
    .vch-item-total {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 15px; font-weight: 800;
        color: #0F172A;
        font-variant-numeric: tabular-nums;
        text-align: right;
        flex-shrink: 0;
    }
    .vch-item-total span {
        display: block;
        font-size: 9px; font-weight: 700;
        color: #94A3B8;
        margin-top: 2px;
    }

    /* FIELDS client */
    .vch-fields {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
    }
    @media (min-width: 540px) { .vch-fields { grid-template-columns: 1fr 1fr; } }
    .vch-field label {
        display: block;
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.06em;
        color: #64748B;
        margin-bottom: 5px;
    }
    .vch-field input {
        width: 100%; padding: 11px 14px;
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 11px;
        font-size: 13px; outline: none;
        font-family: inherit;
        color: #0F172A;
        transition: all .15s ease;
    }
    .vch-field input:focus {
        background: white;
        border-color: #44A08D;
        box-shadow: 0 0 0 3px rgba(68,160,141,0.12);
    }

    /* PAY BUTTONS */
    .vch-error {
        display: flex; align-items: center; gap: 8px;
        margin-bottom: 12px;
        padding: 10px 12px;
        background: #FEF2F2;
        border: 1px solid #FECACA;
        border-radius: 11px;
        font-size: 12px; color: #BE123C;
        font-weight: 600;
    }
    .vch-error svg { width: 14px; height: 14px; flex-shrink: 0; }

    .vch-pay {
        width: 100%;
        display: flex; align-items: center; justify-content: space-between;
        gap: 12px;
        padding: 14px 16px;
        border: 1.5px solid;
        border-radius: 14px;
        cursor: pointer;
        font-family: inherit;
        text-align: left;
        transition: all .15s ease;
    }
    .vch-pay:disabled { opacity: 0.6; cursor: not-allowed; }
    .vch-pay-l { display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1; }
    .vch-pay-icon {
        width: 42px; height: 42px;
        border-radius: 11px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        background: rgba(255,255,255,0.20);
        color: white;
        backdrop-filter: blur(8px);
    }
    .vch-pay-icon svg { width: 18px; height: 18px; }
    .vch-pay-icon--ghost {
        background: #F1F5F9;
        color: #64748B;
    }
    .vch-pay-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 15px; font-weight: 800;
        line-height: 1.1;
        letter-spacing: -0.01em;
    }
    .vch-pay-sub {
        font-size: 11px; font-weight: 600;
        margin-top: 3px;
        opacity: 0.85;
    }
    .vch-pay-r svg { width: 18px; height: 18px; }
    .vch-pay-spin {
        animation: vch-spin 1s linear infinite;
    }
    .vch-pay-spin circle {
        stroke-dasharray: 50 100;
        stroke-linecap: round;
    }
    @keyframes vch-spin { to { transform: rotate(360deg); } }

    .vch-pay--ebilling {
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        border-color: transparent;
        color: white;
        box-shadow: 0 14px 28px -10px rgba(78,205,196,0.55),
                    inset 0 1px 0 rgba(255,255,255,0.30);
    }
    .vch-pay--ebilling:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 18px 36px -10px rgba(78,205,196,0.65), inset 0 1px 0 rgba(255,255,255,0.30);
    }

    .vch-pay--cash {
        background: linear-gradient(135deg, #FFFBEB 0%, white 60%);
        border-color: #FCD34D;
        color: #92400E;
        box-shadow: 0 14px 28px -10px rgba(217,119,6,0.30), inset 0 1px 0 rgba(255,255,255,0.40);
        margin-top: 10px;
    }
    .vch-pay--cash:hover:not(:disabled) {
        transform: translateY(-2px);
        background: linear-gradient(135deg, #FEF3C7 0%, white 60%);
        border-color: #F59E0B;
    }
    .vch-pay-icon--cash {
        background: linear-gradient(135deg, #F59E0B, #FBBF24);
        color: white;
    }

    .vch-pay--simulate {
        background: white;
        border-color: #E2E8F0;
        color: #475569;
    }
    .vch-pay--simulate:hover:not(:disabled) {
        border-color: #94A3B8;
        background: #F8FAFC;
    }

    .vch-divider {
        position: relative;
        text-align: center;
        margin: 14px 0;
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.10em;
        color: #94A3B8;
    }
    .vch-divider::before, .vch-divider::after {
        content: '';
        position: absolute; top: 50%;
        width: calc(50% - 70px);
        height: 1px;
        background: #E2E8F0;
    }
    .vch-divider::before { left: 0; }
    .vch-divider::after  { right: 0; }
    .vch-divider span {
        background: white;
        padding: 0 8px;
    }

    /* SIDEBAR : Récap totaux */
    .vch-summary {
        background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
        border-radius: 18px;
        padding: 22px 20px;
        color: white;
        position: relative;
        overflow: hidden;
        box-shadow: 0 16px 36px -16px rgba(15,23,42,0.40);
    }
    .vch-summary::before {
        content: '';
        position: absolute; top: -60px; right: -60px;
        width: 200px; height: 200px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(78,205,196,0.20) 0%, transparent 70%);
    }
    .vch-summary-eyebrow {
        position: relative;
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.10em;
        color: #5EEAD4;
    }
    .vch-summary-total {
        position: relative;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 36px; font-weight: 800;
        font-variant-numeric: tabular-nums;
        line-height: 1.1;
        margin-top: 6px;
        letter-spacing: -0.02em;
    }
    .vch-summary-total span {
        font-size: 14px; font-weight: 600;
        color: #94A3B8;
        margin-left: 6px;
    }
    .vch-summary-rows {
        position: relative;
        margin-top: 14px;
        padding-top: 14px;
        border-top: 1px solid rgba(255,255,255,0.10);
    }
    .vch-summary-row {
        display: flex; align-items: center; justify-content: space-between;
        gap: 8px;
        padding: 5px 0;
        font-size: 12px;
        color: #94A3B8;
    }
    .vch-summary-row svg { width: 12px; height: 12px; }
    .vch-summary-row > span:first-child {
        display: inline-flex; align-items: center; gap: 5px;
    }
    .vch-summary-num {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800;
        color: white;
        font-variant-numeric: tabular-nums;
    }
    .vch-summary-row--brand { color: #5EEAD4; font-weight: 700; }
    .vch-summary-row--brand .vch-summary-num { color: #5EEAD4; }

    /* Wallet status */
    .vch-wallet {
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        padding: 14px;
    }
    .vch-wallet--low {
        background: linear-gradient(135deg, #FEF2F2, #FEE2E2);
        border-color: #FCA5A5;
    }
    .vch-wallet-l {
        display: flex; align-items: center; gap: 10px;
    }
    .vch-wallet-icon {
        width: 36px; height: 36px;
        border-radius: 10px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 4px 10px -3px rgba(78,205,196,0.40);
    }
    .vch-wallet--low .vch-wallet-icon {
        background: linear-gradient(135deg, #F87171, #BE123C);
        box-shadow: 0 4px 10px -3px rgba(244,63,94,0.40);
    }
    .vch-wallet-icon svg { width: 16px; height: 16px; }
    .vch-wallet-label {
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.06em;
        color: #94A3B8;
    }
    .vch-wallet-value {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 18px; font-weight: 800;
        color: #0F172A;
        font-variant-numeric: tabular-nums;
        line-height: 1;
        margin-top: 3px;
    }
    .vch-wallet-value span {
        font-size: 11px; font-weight: 600;
        color: #94A3B8;
    }
    .vch-wallet-warn {
        margin-top: 10px;
        font-size: 12px; color: #BE123C;
        font-weight: 600;
        line-height: 1.4;
    }
    .vch-wallet-warn strong {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
    }
    .vch-wallet-ok {
        margin-top: 10px;
        font-size: 12px; color: #047857;
        font-weight: 700;
    }
</style>

<script>
// Le panier a été capturé côté serveur (session) — on vide le localStorage
// pour que la page /vendor/sell réapparaisse vierge au prochain passage.
try { localStorage.removeItem('vendor.cart'); } catch (e) {}

function vendorCheckout() {
    return {
        customerName:  @json($customerName),
        customerPhone: @json($customerPhone),
        loading: null, // null | 'ebilling' | 'simulate'
        error: '',

        async post(url, body) {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify(body),
            });
            return { res, data: await res.json() };
        },

        async payEbilling() {
            this.error = '';
            this.loading = 'ebilling';
            try {
                const { res, data } = await this.post('{{ route('vendor.sell.store') }}', {
                    customer_name:  this.customerName || null,
                    customer_phone: this.customerPhone || null,
                });
                if (data.success && data.portal_url) {
                    window.location.href = data.portal_url;
                    return;
                }
                this.error = data.message || "Erreur lors de l'initialisation du paiement.";
            } catch (e) {
                this.error = 'Erreur réseau : ' + e.message;
            } finally {
                this.loading = null;
            }
        },

        async payCash() {
            this.error = '';
            this.loading = 'cash';
            try {
                const { res, data } = await this.post('{{ route('vendor.sell.cash') }}', {
                    customer_name:  this.customerName || null,
                    customer_phone: this.customerPhone || null,
                });
                if (data.success && data.redirect_url) {
                    window.location.href = data.redirect_url;
                    return;
                }
                this.error = data.message || 'Erreur lors de la création de la vente cash.';
            } catch (e) {
                this.error = 'Erreur réseau : ' + e.message;
            } finally {
                this.loading = null;
            }
        },

        async paySimulate() {
            this.error = '';
            if (!confirm('Simuler ce paiement ? (le wallet sera débité comme un vrai paiement)')) return;
            this.loading = 'simulate';
            try {
                const { res, data } = await this.post('{{ route('vendor.sell.simulate') }}', {
                    customer_name:  this.customerName || null,
                    customer_phone: this.customerPhone || null,
                });
                if (data.success && data.redirect_url) {
                    window.location.href = data.redirect_url;
                    return;
                }
                this.error = data.message || 'Erreur simulation.';
            } catch (e) {
                this.error = 'Erreur réseau : ' + e.message;
            } finally {
                this.loading = null;
            }
        },
    };
}
</script>
@endsection
