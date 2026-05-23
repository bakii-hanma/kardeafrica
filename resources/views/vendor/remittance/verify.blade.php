@extends('vendor.layouts.vendor')

@section('title', 'Vérification de la remise')

@section('content')
<div class="vrv-wrap" x-data="remitVerify({ ref: @json($ref) })" x-init="start()">

    {{-- Verifying --}}
    <div x-show="state === 'verifying'" class="vrv-card">
        <div class="vrv-spinner">
            <svg class="vrv-spin" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none"/></svg>
        </div>
        <h1 class="vrv-title">Vérification du paiement…</h1>
        <p class="vrv-sub">On confirme ta remise auprès d'E-Billing. Ne ferme pas cette page.</p>
        <div class="vrv-meta">Tentative <strong x-text="attempts"></strong>/12</div>
    </div>

    {{-- Success --}}
    <div x-show="state === 'success'" x-cloak class="vrv-card vrv-card--success">
        <div class="vrv-ico vrv-ico--success">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="vrv-title">Remise confirmée !</h1>
        <p class="vrv-sub">Ton solde de vente a été reconstitué. Tu peux repartir vendre.</p>
        <a :href="redirectUrl" class="vrv-btn">
            Voir mes remises
        </a>
    </div>

    {{-- Error --}}
    <div x-show="state === 'error'" x-cloak class="vrv-card vrv-card--error">
        <div class="vrv-ico vrv-ico--error">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <h1 class="vrv-title">Paiement non confirmé</h1>
        <p class="vrv-sub" x-text="errorMessage"></p>
        <div class="vrv-actions">
            <button type="button" @click="restart()" class="vrv-btn vrv-btn--ghost">Réessayer</button>
            <a href="{{ route('vendor.remittance.index') }}" class="vrv-btn">Retour</a>
        </div>
    </div>

</div>

<style>
    .vrv-wrap {
        max-width: 480px;
        margin: 40px auto;
        padding: 0 16px;
    }
    [x-cloak] { display: none !important; }

    .vrv-card {
        background: white;
        border-radius: 22px;
        padding: 32px 24px;
        text-align: center;
        box-shadow: 0 20px 40px -16px rgba(15,23,42,0.20);
    }
    .vrv-card--success { background: linear-gradient(180deg, #ECFDF5, white); border: 1px solid #A7F3D0; }
    .vrv-card--error   { background: linear-gradient(180deg, #FEF2F2, white); border: 1px solid #FECACA; }

    .vrv-spinner {
        display: inline-flex;
        width: 64px; height: 64px;
        border-radius: 50%;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        align-items: center; justify-content: center;
        margin-bottom: 18px;
        box-shadow: 0 12px 28px -8px rgba(68,160,141,0.50);
    }
    .vrv-spinner svg { width: 32px; height: 32px; }
    .vrv-spin { animation: vrv-spin 1s linear infinite; }
    .vrv-spin circle { stroke-dasharray: 50 100; stroke-linecap: round; }
    @keyframes vrv-spin { to { transform: rotate(360deg); } }

    .vrv-ico {
        display: inline-flex;
        width: 64px; height: 64px;
        border-radius: 50%;
        align-items: center; justify-content: center;
        margin-bottom: 18px;
    }
    .vrv-ico--success { background: #10B981; color: white; box-shadow: 0 12px 28px -8px rgba(16,185,129,0.45); }
    .vrv-ico--error   { background: #F43F5E; color: white; box-shadow: 0 12px 28px -8px rgba(244,63,94,0.45); }
    .vrv-ico svg { width: 30px; height: 30px; }

    .vrv-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 22px; font-weight: 800;
        color: #0F172A;
        margin: 0 0 8px;
    }
    .vrv-sub { font-size: 14px; color: #64748B; line-height: 1.5; margin: 0 0 18px; }
    .vrv-meta { font-size: 11px; color: #94A3B8; font-variant-numeric: tabular-nums; }
    .vrv-meta strong { color: #44A08D; font-weight: 800; }

    .vrv-actions { display: flex; gap: 8px; justify-content: center; }
    .vrv-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        padding: 12px 22px;
        border-radius: 12px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        font-size: 13px; font-weight: 800;
        text-decoration: none;
        border: 0;
        cursor: pointer;
        box-shadow: 0 8px 20px -6px rgba(68,160,141,0.50);
    }
    .vrv-btn:hover { transform: translateY(-1px); }
    .vrv-btn--ghost {
        background: white;
        color: #475569;
        border: 1px solid #E2E8F0;
        box-shadow: none;
    }
</style>

<script>
window.remitVerify = function ({ ref }) {
    return {
        ref,
        state: 'verifying', // verifying | success | error
        attempts: 0,
        maxAttempts: 12,    // 12 × 5s = 1 minute
        errorMessage: 'Paiement non confirmé après 1 minute. Vérifie auprès d\'E-Billing.',
        redirectUrl: '{{ route('vendor.remittance.index') }}',

        async start() {
            this.attempts = 0;
            await this.poll();
        },

        async poll() {
            this.attempts++;
            try {
                const res = await fetch('{{ route('vendor.remittance.finalize') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ ref: this.ref }),
                });
                const data = await res.json();
                if (data.success) {
                    this.state = 'success';
                    this.redirectUrl = data.redirect_url || this.redirectUrl;
                    setTimeout(() => { window.location.href = this.redirectUrl; }, 2000);
                    return;
                }
                if (this.attempts >= this.maxAttempts) {
                    this.errorMessage = data.message || this.errorMessage;
                    this.state = 'error';
                    return;
                }
                setTimeout(() => this.poll(), 5000);
            } catch (e) {
                if (this.attempts >= this.maxAttempts) {
                    this.errorMessage = 'Erreur réseau : ' + e.message;
                    this.state = 'error';
                    return;
                }
                setTimeout(() => this.poll(), 5000);
            }
        },

        restart() {
            this.state = 'verifying';
            this.start();
        },
    };
};
</script>
@endsection
