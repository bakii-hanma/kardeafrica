@extends('vendor.layouts.vendor')

@section('title', 'Recharger ma cagnotte')

@section('content')
<style>
    .rch-wrap { max-width: 880px; margin: 0 auto; padding: 20px 16px 60px; font-family: 'Inter','Figtree',sans-serif; }

    /* Hero */
    .rch-hero {
        background: linear-gradient(135deg, #0F172A 0%, #1E293B 60%, #0F4F44 100%);
        color: white;
        border-radius: 18px; padding: 24px 26px;
        margin-bottom: 18px; position: relative; overflow: hidden;
    }
    .rch-hero-glow {
        position: absolute; top: -40%; right: -10%;
        width: 320px; height: 320px;
        background: radial-gradient(circle, rgba(94,234,212,.20), transparent 60%);
        pointer-events: none;
    }
    .rch-hero-tag {
        display: inline-block; position: relative;
        font-size: 10px; font-weight: 800;
        letter-spacing: .12em; text-transform: uppercase;
        color: #5EEAD4;
        background: rgba(94,234,212,.15);
        border: 1px solid rgba(94,234,212,.25);
        padding: 4px 10px; border-radius: 9999px;
        margin-bottom: 12px;
    }
    .rch-hero h1 {
        position: relative; margin: 0;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 22px; font-weight: 800; letter-spacing: -0.02em;
    }
    .rch-hero p { position: relative; margin: 6px 0 16px; color: rgba(255,255,255,.7); font-size: 13px; }

    .rch-balance {
        position: relative;
        display: grid; grid-template-columns: 1fr 1fr; gap: 18px;
        padding-top: 14px;
        border-top: 1px solid rgba(255,255,255,.10);
    }
    @media (max-width: 540px) { .rch-balance { grid-template-columns: 1fr; gap: 12px; } }
    .rch-balance-side .label {
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: .08em;
        color: rgba(255,255,255,.55);
    }
    .rch-balance-side .value {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 26px; font-weight: 800;
        font-variant-numeric: tabular-nums; line-height: 1;
        margin-top: 6px;
    }
    .rch-balance-side .value small { font-size: 12px; font-weight: 700; color: rgba(255,255,255,.55); margin-left: 4px; }
    .rch-balance-bar {
        height: 6px; background: rgba(255,255,255,.10);
        border-radius: 9999px; margin-top: 10px; overflow: hidden;
    }
    .rch-balance-bar-fill {
        height: 100%; background: linear-gradient(90deg, #44A08D, #4ECDC4);
        border-radius: 9999px;
    }

    /* Form card */
    .rch-card {
        background: white; border: 1px solid #E2E8F0;
        border-radius: 16px; padding: 22px;
        margin-bottom: 14px;
        box-shadow: 0 1px 2px rgba(15,23,42,.04);
    }
    .rch-card h2 {
        margin: 0 0 14px;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 14px; font-weight: 800; color: #0F172A;
        text-transform: uppercase; letter-spacing: .06em;
        display: flex; align-items: center; gap: 8px;
    }
    .rch-card h2 svg { color: #44A08D; }

    .rch-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    @media (max-width: 540px) { .rch-row { grid-template-columns: 1fr; } }

    /* Quick amounts pills */
    .rch-quick {
        display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;
        margin-bottom: 12px;
    }
    @media (max-width: 540px) { .rch-quick { grid-template-columns: repeat(2, 1fr); } }
    .rch-quick button {
        padding: 12px;
        background: white; border: 2px solid #E2E8F0;
        border-radius: 12px;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 14px; font-weight: 800; color: #0F172A;
        font-variant-numeric: tabular-nums;
        cursor: pointer; transition: all .15s;
    }
    .rch-quick button:hover { border-color: #44A08D; }
    .rch-quick button.rch-quick--active {
        border-color: #44A08D;
        background: linear-gradient(135deg, #ECFDF5, #D1FAE5);
        color: #0F4F44;
    }
    .rch-quick small { font-size: 10px; font-weight: 700; color: #94A3B8; }

    /* Inputs */
    .rch-field { margin-bottom: 12px; }
    .rch-label {
        display: block; font-size: 12px; font-weight: 700;
        color: #334155; margin-bottom: 6px;
    }
    .rch-input {
        width: 100%; padding: 11px 13px;
        font-size: 14px; font-family: inherit; color: #0F172A;
        background: white; border: 1.5px solid #E2E8F0; border-radius: 10px;
        outline: none;
    }
    .rch-input:focus { border-color: #44A08D; box-shadow: 0 0 0 3px rgba(68,160,141,.15); }
    .rch-hint { font-size: 11px; color: #94A3B8; margin-top: 4px; }

    /* Payment method choice */
    .rch-methods {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;
        margin-bottom: 14px;
    }
    @media (max-width: 540px) { .rch-methods { grid-template-columns: 1fr; } }
    .rch-method {
        display: flex; align-items: center; gap: 10px;
        padding: 12px;
        background: white; border: 2px solid #E2E8F0;
        border-radius: 12px;
        cursor: pointer; transition: border-color .15s;
    }
    .rch-method:hover { border-color: #44A08D; }
    .rch-method-ic {
        width: 36px; height: 36px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        color: white; flex-shrink: 0;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 12px; font-weight: 800;
    }
    .rch-method-ic--airtel { background: linear-gradient(135deg, #E10000, #FF3030); }
    .rch-method-ic--moov   { background: linear-gradient(135deg, #FCC100, #FFA500); }
    .rch-method-ic--card   { background: linear-gradient(135deg, #1E40AF, #3B82F6); }
    .rch-method-label {
        font-size: 12px; font-weight: 800; color: #0F172A; line-height: 1.2;
    }
    .rch-method-sub { font-size: 10px; color: #94A3B8; }

    /* Submit */
    .rch-submit {
        width: 100%; padding: 14px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white; border: 0; border-radius: 12px;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 15px; font-weight: 800;
        cursor: pointer; display: inline-flex;
        align-items: center; justify-content: center; gap: 8px;
        box-shadow: 0 10px 24px -8px rgba(68,160,141,.5),
                    inset 0 1px 0 rgba(255,255,255,.25);
        transition: transform .15s;
    }
    .rch-submit:hover { transform: translateY(-1px); }
    .rch-submit:disabled { opacity: .5; cursor: not-allowed; transform: none; }

    /* Pending banner */
    .rch-pending {
        background: linear-gradient(135deg, #FEF3C7, #FDE68A);
        border: 1px solid #F59E0B;
        border-radius: 12px;
        padding: 14px 16px; margin-bottom: 14px;
        display: flex; gap: 12px; align-items: center;
    }
    .rch-pending strong { display: block; color: #92400E; font-size: 13px; }
    .rch-pending p { margin: 2px 0 0; color: #B45309; font-size: 12px; }

    /* History */
    .rch-history-row {
        display: flex; align-items: center; gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #F1F5F9;
        font-size: 13px;
    }
    .rch-history-row:last-child { border-bottom: 0; }
    .rch-history-ic {
        width: 34px; height: 34px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .rch-history-ic--ok { background: #D1FAE5; color: #047857; }
    .rch-history-ic--ko { background: #FEE2E2; color: #B91C1C; }
    .rch-history-ic--p  { background: #FEF3C7; color: #B45309; }
    .rch-history-amt {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800; color: #0F172A;
        font-variant-numeric: tabular-nums;
    }
    .rch-history-meta { font-size: 11px; color: #64748B; }

    .rch-error {
        background: #FEE2E2; color: #991B1B;
        padding: 10px 14px; border-radius: 10px;
        font-size: 12px; margin-bottom: 12px;
    }
</style>

<div class="rch-wrap" x-data="rechargeForm({
    quickAmounts: {{ json_encode($quickAmounts) }},
    headroom: {{ (int) $headroom }},
})">

    {{-- Hero --}}
    <div class="rch-hero">
        <div class="rch-hero-glow"></div>
        <span class="rch-hero-tag">Cagnotte marchand</span>
        <h1>Recharger ma cagnotte</h1>
        <p>Recharge via Airtel Money, Moov Money ou carte. Crédité immédiatement après confirmation du paiement.</p>

        <div class="rch-balance">
            <div class="rch-balance-side">
                <div class="label">Solde disponible</div>
                <div class="value">{{ number_format($reseller->wallet_balance, 0, ',', ' ') }} <small>FCFA</small></div>
                <div class="rch-balance-bar">
                    <div class="rch-balance-bar-fill" style="width:{{ min(100, $reseller->wallet_percentage) }}%;"></div>
                </div>
            </div>
            <div class="rch-balance-side">
                <div class="label">Recharge max possible</div>
                <div class="value">{{ number_format($headroom, 0, ',', ' ') }} <small>FCFA</small></div>
                <div class="rch-hint" style="color:rgba(255,255,255,.55);margin-top:8px;">Plafond : {{ number_format($reseller->max_wallet, 0, ',', ' ') }} FCFA</div>
            </div>
        </div>
    </div>

    {{-- Pending recharges --}}
    @foreach($pending as $p)
        <div class="rch-pending">
            <div style="width:36px;height:36px;border-radius:12px;background:#F59E0B;color:white;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div style="flex:1;min-width:0;">
                <strong>Recharge de {{ number_format($p->amount, 0, ',', ' ') }} FCFA en attente</strong>
                <p>Lancée {{ $p->created_at->diffForHumans() }}. Termine le paiement sur le portail.</p>
            </div>
            @if($p->portal_url)
                <a href="{{ $p->portal_url }}" target="_blank" style="padding:9px 14px;background:white;color:#92400E;border:1px solid #F59E0B;border-radius:10px;font-size:12px;font-weight:700;text-decoration:none;">
                    Reprendre →
                </a>
            @endif
        </div>
    @endforeach

    {{-- Form --}}
    <div class="rch-card">
        <h2>
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
            Montant à recharger
        </h2>

        <div class="rch-quick">
            <template x-for="amt in quickAmounts" :key="amt">
                <button type="button" :class="amount == amt ? 'rch-quick--active' : ''"
                        @click="amount = amt; customAmount = null;"
                        :disabled="amt > headroom"
                        :style="amt > headroom ? 'opacity:.4;cursor:not-allowed;' : ''">
                    <span x-text="formatXAF(amt)"></span>
                    <small style="display:block;">FCFA</small>
                </button>
            </template>
        </div>

        <div class="rch-field">
            <label class="rch-label" for="custom_amount">Ou montant personnalisé (FCFA)</label>
            <input type="number" id="custom_amount" class="rch-input"
                   x-model.number="customAmount"
                   @input="amount = customAmount"
                   min="1000" :max="headroom" step="500"
                   placeholder="Ex : 25 000">
            <p class="rch-hint">Min 1 000 · Max <span x-text="formatXAF(headroom)"></span> FCFA</p>
        </div>

        <h2 style="margin-top:18px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            Méthode de paiement
        </h2>
        <div class="rch-methods">
            <div class="rch-method"><div class="rch-method-ic rch-method-ic--airtel">A</div><div><div class="rch-method-label">Airtel Money</div><div class="rch-method-sub">Compte marchand</div></div></div>
            <div class="rch-method"><div class="rch-method-ic rch-method-ic--moov">M</div><div><div class="rch-method-label">Moov Money</div><div class="rch-method-sub">Mobile Money</div></div></div>
            <div class="rch-method"><div class="rch-method-ic rch-method-ic--card">V</div><div><div class="rch-method-label">Carte bancaire</div><div class="rch-method-sub">Visa, MasterCard</div></div></div>
        </div>
        <p class="rch-hint" style="margin-top:-6px;margin-bottom:12px;">Le choix exact se fait sur le portail E-Billing après validation.</p>

        <h2 style="margin-top:14px;">Tes coordonnées</h2>
        <div class="rch-row">
            <div class="rch-field">
                <label class="rch-label">Téléphone</label>
                <input type="tel" x-model="phone" class="rch-input" placeholder="+241 06 87 13 09" value="{{ $reseller->phone }}">
            </div>
            <div class="rch-field">
                <label class="rch-label">Email</label>
                <input type="email" x-model="email" class="rch-input" placeholder="exemple@email.com" value="{{ $reseller->email }}">
            </div>
        </div>

        <div x-show="errorMsg" x-cloak class="rch-error" x-text="errorMsg"></div>

        <button type="button" class="rch-submit" @click="submit()" :disabled="!amount || loading">
            <template x-if="!loading">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </template>
            <template x-if="loading">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="animate-spin" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </template>
            <span x-text="loading ? 'Initialisation…' : ('Recharger ' + formatXAF(amount || 0) + ' FCFA')"></span>
        </button>
    </div>

    {{-- History --}}
    @if($history->isNotEmpty())
        <div class="rch-card">
            <h2>
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                Historique
            </h2>
            @foreach($history as $h)
                <div class="rch-history-row">
                    <div class="rch-history-ic {{ $h->status === 'completed' ? 'rch-history-ic--ok' : ($h->status === 'failed' ? 'rch-history-ic--ko' : 'rch-history-ic--p') }}">
                        @if($h->status === 'completed')
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        @elseif($h->status === 'failed')
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        @else
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3"/></svg>
                        @endif
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="rch-history-amt">{{ number_format($h->amount, 0, ',', ' ') }} <span style="font-size:11px;font-weight:600;color:#94A3B8;">FCFA</span></div>
                        <div class="rch-history-meta">{{ $h->created_at->translatedFormat('d M Y à H:i') }} · {{ ucfirst($h->status) }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<script>
    function rechargeForm(opts) {
        return {
            quickAmounts: opts.quickAmounts,
            headroom:     opts.headroom,
            amount:       null,
            customAmount: null,
            phone:        @js($reseller->phone ?? ''),
            email:        @js($reseller->email ?? ''),
            loading:      false,
            errorMsg:     null,

            formatXAF(n) { return Number(n || 0).toLocaleString('fr-FR'); },

            async submit() {
                this.errorMsg = null;
                if (!this.amount || this.amount < 1000) {
                    this.errorMsg = 'Montant minimum : 1 000 FCFA';
                    return;
                }
                if (this.amount > this.headroom) {
                    this.errorMsg = 'Dépasse le plafond wallet (' + this.formatXAF(this.headroom) + ' max).';
                    return;
                }

                this.loading = true;
                try {
                    const res = await fetch("{{ route('vendor.wallet.recharge.init') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: JSON.stringify({
                            amount: this.amount,
                            phone:  this.phone,
                            email:  this.email,
                        }),
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        this.errorMsg = data.message || 'Erreur lors de l\'initialisation.';
                        this.loading = false;
                        return;
                    }
                    // Redirection vers portail E-Billing
                    window.location.href = data.portal_url;
                } catch (e) {
                    this.errorMsg = 'Erreur réseau : ' + e.message;
                    this.loading = false;
                }
            },
        };
    }
</script>
@endsection
