@extends('vendor.layouts.vendor')

@section('title', 'Vérification du paiement')

@section('content')
<div x-data="vendorPayVerify({ ref: @js($ref), finalizeUrl: @js(route('vendor.payment.finalize')) })" x-init="init()" class="vpv-wrap">

    {{-- ===== VERIFYING ===== --}}
    <div x-show="state === 'verifying'" class="vpv-card">
        <div class="vpv-spin">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2.5" fill="none"/></svg>
        </div>
        <h1 class="vpv-title">Vérification du paiement…</h1>
        <p class="vpv-lead">On confirme la transaction avec E-Billing. Ne ferme pas cette page.</p>
        <div class="vpv-progress">
            <div class="vpv-progress-bar"></div>
        </div>
        <div class="vpv-meta">
            <span>Tentative <strong x-text="attempt"></strong>/<strong x-text="maxAttempts"></strong></span>
            <span x-show="lastStatus">· <span x-text="lastStatus"></span></span>
        </div>
    </div>

    {{-- ===== SUCCESS ===== --}}
    <div x-show="state === 'success'" x-cloak class="vpv-card vpv-card--success">
        <div class="vpv-ico vpv-ico--ok">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="vpv-title">Paiement validé !</h1>
        <p class="vpv-lead">Les cartes sont en cours de génération. Tu vas être redirigé vers la commande pour récupérer le QR code à donner au client.</p>
        <a :href="redirectUrl" class="vpv-btn vpv-btn--primary">
            Voir la commande
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </a>
    </div>

    {{-- ===== ERROR ===== --}}
    <div x-show="state === 'error'" x-cloak class="vpv-card vpv-card--error">
        <div class="vpv-ico vpv-ico--ko">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <h1 class="vpv-title">Paiement non confirmé</h1>
        <p class="vpv-lead" x-text="errorMessage || 'Le paiement n\'a pas été confirmé par E-Billing. Réessaie dans un instant ou retourne au panier.'"></p>
        <div class="vpv-meta" x-show="lastStatus">Dernier statut : <strong x-text="lastStatus"></strong></div>
        <div class="vpv-actions">
            <button type="button" @click="reset()" class="vpv-btn vpv-btn--primary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Réessayer
            </button>
            <a href="{{ route('vendor.sell') }}" class="vpv-btn vpv-btn--ghost">Retour au catalogue</a>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }

    .vpv-wrap {
        max-width: 480px; margin: 32px auto;
        padding: 0 16px;
    }
    .vpv-card {
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 22px;
        padding: 36px 24px;
        text-align: center;
        box-shadow: 0 16px 36px -16px rgba(15,23,42,0.20);
    }
    .vpv-card--success {
        background: linear-gradient(180deg, #ECFDF5 0%, white 50%);
        border-color: #6EE7B7;
    }
    .vpv-card--error {
        background: linear-gradient(180deg, #FEF2F2 0%, white 50%);
        border-color: #FCA5A5;
    }

    /* Loader */
    .vpv-spin {
        width: 64px; height: 64px;
        margin: 0 auto 18px;
        display: flex; align-items: center; justify-content: center;
        position: relative;
    }
    .vpv-spin svg {
        width: 64px; height: 64px;
        color: #44A08D;
        animation: vpv-spin 1s linear infinite;
    }
    .vpv-spin svg circle {
        stroke: #E2E8F0;
        stroke-dasharray: 60 100;
        stroke-linecap: round;
    }
    @keyframes vpv-spin {
        to { transform: rotate(360deg); }
    }
    .vpv-spin::before {
        content: '';
        position: absolute; inset: -8px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(78,205,196,0.20) 0%, transparent 70%);
        z-index: -1;
        animation: vpv-pulse 2s ease-out infinite;
    }
    @keyframes vpv-pulse {
        0%   { transform: scale(0.85); opacity: 0.8; }
        50%  { transform: scale(1.15); opacity: 0.3; }
        100% { transform: scale(0.85); opacity: 0.8; }
    }

    /* Icons OK / KO */
    .vpv-ico {
        width: 64px; height: 64px;
        margin: 0 auto 18px;
        border-radius: 18px;
        display: flex; align-items: center; justify-content: center;
    }
    .vpv-ico svg { width: 28px; height: 28px; }
    .vpv-ico--ok {
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        box-shadow: 0 12px 24px -8px rgba(78,205,196,0.55);
    }
    .vpv-ico--ko {
        background: linear-gradient(135deg, #F43F5E, #BE123C);
        color: white;
        box-shadow: 0 12px 24px -8px rgba(244,63,94,0.55);
    }

    .vpv-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 22px; font-weight: 800;
        color: #0F172A;
        margin: 0 0 8px;
        letter-spacing: -0.01em;
        line-height: 1.2;
    }
    .vpv-lead {
        font-size: 13px; color: #475569;
        line-height: 1.6;
        margin: 0 0 20px;
    }

    /* Progress bar (indeterminate) */
    .vpv-progress {
        height: 4px;
        background: #F1F5F9;
        border-radius: 9999px;
        overflow: hidden;
        margin: 18px auto;
        max-width: 240px;
        position: relative;
    }
    .vpv-progress-bar {
        position: absolute;
        height: 100%;
        width: 40%;
        background: linear-gradient(90deg, transparent, #44A08D, transparent);
        animation: vpv-slide 1.6s ease-in-out infinite;
    }
    @keyframes vpv-slide {
        0%   { left: -40%; }
        100% { left: 100%; }
    }

    .vpv-meta {
        font-size: 11px; color: #94A3B8;
        margin-top: 8px;
    }
    .vpv-meta strong { color: #475569; font-weight: 700; }

    .vpv-actions {
        display: flex; flex-direction: column; gap: 8px;
        margin-top: 22px;
    }
    .vpv-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        padding: 13px 18px;
        border-radius: 12px;
        font-family: inherit;
        font-size: 13px; font-weight: 800;
        text-decoration: none;
        cursor: pointer;
        border: 0;
        transition: all .15s ease;
    }
    .vpv-btn svg { width: 14px; height: 14px; }
    .vpv-btn--primary {
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        box-shadow: 0 12px 24px -10px rgba(78,205,196,0.55),
                    inset 0 1px 0 rgba(255,255,255,0.30);
    }
    .vpv-btn--primary:hover { transform: translateY(-1px); }
    .vpv-btn--ghost {
        background: white;
        color: #475569;
        border: 1px solid #E2E8F0;
    }
    .vpv-btn--ghost:hover { border-color: #44A08D; color: #44A08D; }
</style>

<script>
function vendorPayVerify({ ref, finalizeUrl }) {
    return {
        ref, finalizeUrl,
        state: 'verifying', // verifying | success | error
        attempt: 0,
        maxAttempts: 12, // 12 essais à 5s = 60s
        lastStatus: '',
        errorMessage: '',
        redirectUrl: '',

        async init() {
            await this.poll();
        },

        async poll() {
            for (this.attempt = 1; this.attempt <= this.maxAttempts; this.attempt++) {
                try {
                    const res = await fetch(this.finalizeUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ ref: this.ref }),
                    });
                    const data = await res.json();
                    this.lastStatus = data.status || (data.success ? 'completed' : 'pending');

                    if (data.success && data.redirect_url) {
                        this.state = 'success';
                        this.redirectUrl = data.redirect_url;
                        // redirige automatiquement après 1.8s
                        setTimeout(() => { window.location.href = data.redirect_url; }, 1800);
                        return;
                    }

                    // Erreur fatale (401, 422, 500…) → pas la peine de re-poller
                    if (res.status >= 400) {
                        this.state = 'error';
                        this.errorMessage = data.message || 'Erreur lors de la vérification.';
                        return;
                    }
                    // Pending → attendre 5s et retry
                } catch (e) {
                    this.lastStatus = 'erreur réseau';
                }
                await new Promise(r => setTimeout(r, 5000));
            }
            // Timeout
            this.state = 'error';
            this.errorMessage = "Le paiement n'a pas été confirmé dans le délai imparti. Si tu as bien payé, ouvre la commande depuis 'Mes ventes' dans quelques minutes.";
        },

        reset() {
            this.state = 'verifying';
            this.attempt = 0;
            this.lastStatus = '';
            this.errorMessage = '';
            this.poll();
        },
    };
}
</script>

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush
@endsection
