@extends('vendor.layouts.vendor')

@section('title', 'Vérification du paiement')

@section('content')
<div style="max-width:520px;margin:60px auto;padding:0 20px;font-family:'Inter','Figtree',sans-serif;"
     x-data="verifyRecharge('{{ $ref }}')">

    <div style="background:white;border-radius:18px;padding:32px 24px;text-align:center;box-shadow:0 14px 30px -10px rgba(15,23,42,.10);border:1px solid #E2E8F0;">

        {{-- Loader --}}
        <div x-show="status === 'loading'" x-cloak>
            <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;margin-bottom:16px;animation:spin 1s linear infinite;">
                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </div>
            <h2 style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:800;color:#0F172A;margin:0 0 6px;">Vérification du paiement…</h2>
            <p style="font-size:13px;color:#64748B;margin:0;">Tentative <span x-text="attempts"></span> sur <span x-text="maxAttempts"></span></p>
            <p style="font-size:11px;color:#94A3B8;margin-top:14px;">Si tu as déjà payé sur le portail, ce sera confirmé dans quelques secondes.</p>
        </div>

        {{-- Success --}}
        <div x-show="status === 'success'" x-cloak>
            <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#10B981,#059669);color:white;margin-bottom:16px;box-shadow:0 14px 30px -10px rgba(16,185,129,.50);">
                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h2 style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:800;color:#065F46;margin:0 0 6px;">Recharge confirmée !</h2>
            <p style="font-size:13px;color:#047857;margin:0 0 18px;" x-text="message"></p>
            <a :href="redirectUrl" style="display:inline-flex;align-items:center;gap:6px;padding:11px 22px;background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;border-radius:11px;font-size:13px;font-weight:700;text-decoration:none;">Voir mon wallet</a>
        </div>

        {{-- Error --}}
        <div x-show="status === 'error'" x-cloak>
            <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#DC2626,#EF4444);color:white;margin-bottom:16px;">
                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <h2 style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:800;color:#0F172A;margin:0 0 6px;">Paiement non confirmé</h2>
            <p style="font-size:13px;color:#64748B;margin:0 0 18px;" x-text="message"></p>
            <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
                <button @click="retry()" type="button" style="padding:10px 18px;background:#F1F5F9;color:#334155;border:0;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;">Vérifier à nouveau</button>
                <a href="{{ route('vendor.wallet.recharge') }}" style="padding:10px 18px;background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;border-radius:10px;font-size:12px;font-weight:700;text-decoration:none;">Retour</a>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes spin { from { transform: rotate(0); } to { transform: rotate(360deg); } }
</style>

<script>
    function verifyRecharge(ref) {
        return {
            ref:         ref,
            status:      'loading',
            attempts:    0,
            maxAttempts: 12, // 12 * 5s = 60s
            message:     '',
            redirectUrl: "{{ route('vendor.wallet.recharge') }}",

            init() { this.poll(); },

            async poll() {
                if (this.attempts >= this.maxAttempts) {
                    this.status = 'error';
                    this.message = 'Le paiement n\'a pas été confirmé après une minute. Vérifie ton SMS Mobile Money puis recharge la page.';
                    return;
                }
                this.attempts++;

                try {
                    const res = await fetch("{{ route('vendor.wallet.recharge.finalize') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: JSON.stringify({ ref: this.ref }),
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.status = 'success';
                        this.message = data.message || 'Wallet crédité !';
                        this.redirectUrl = data.redirect_url || this.redirectUrl;
                        setTimeout(() => { window.location.href = this.redirectUrl; }, 2500);
                        return;
                    }
                    // Pas encore confirmé → retry
                    setTimeout(() => this.poll(), 5000);
                } catch (e) {
                    setTimeout(() => this.poll(), 5000);
                }
            },

            retry() {
                this.status = 'loading';
                this.attempts = 0;
                this.poll();
            },
        };
    }
</script>
@endsection
