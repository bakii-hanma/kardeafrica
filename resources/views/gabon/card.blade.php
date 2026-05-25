@extends('layouts.app')

@section('title', $card->name . ' — ' . ($merchant->business_name ?? $merchant->name))

@section('content')
<style>
    .cd-wrap { max-width: 1180px; margin: 0 auto; padding: 24px 20px 80px; }

    /* ====== Breadcrumb ====== */
    .cd-crumb {
        display: flex; gap: 6px; flex-wrap: wrap;
        font-size: 12px; color: #94A3B8;
        margin-bottom: 20px;
    }
    .cd-crumb a { color: #44A08D; text-decoration: none; font-weight: 600; }
    .cd-crumb a:hover { text-decoration: underline; }
    .cd-crumb svg { width: 11px; height: 11px; }

    /* ====== Layout 2 cols ====== */
    .cd-grid {
        display: grid; gap: 32px;
        grid-template-columns: 1fr;
    }
    @media (min-width: 960px) {
        .cd-grid { grid-template-columns: 1fr 1fr; gap: 48px; }
    }

    /* ====== Visual side ====== */
    .cd-visual-wrap { position: sticky; top: 80px; align-self: start; }
    .cd-visual-cont { max-width: 520px; }

    /* ====== Info side ====== */
    .cd-cat {
        display: inline-block;
        font-size: 11px; font-weight: 800; color: #44A08D;
        text-transform: uppercase; letter-spacing: .10em;
        margin-bottom: 8px;
    }
    .cd-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 30px; font-weight: 800; color: #0F172A;
        margin: 0 0 12px;
        letter-spacing: -0.02em; line-height: 1.15;
    }
    @media (min-width:768px) { .cd-title { font-size: 38px; } }

    .cd-merchant-link {
        display: inline-flex; align-items: center; gap: 10px;
        padding: 10px 14px;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        text-decoration: none;
        color: inherit;
        margin-bottom: 24px;
        transition: border-color .15s, transform .15s;
    }
    .cd-merchant-link:hover {
        border-color: #44A08D;
        transform: translateY(-1px);
    }
    .cd-merchant-av {
        width: 36px; height: 36px;
        border-radius: 10px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        display: inline-flex; align-items: center; justify-content: center;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800; font-size: 14px;
        flex-shrink: 0;
    }
    .cd-merchant-name {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 14px; font-weight: 800; color: #0F172A;
        line-height: 1.2;
    }
    .cd-merchant-meta {
        font-size: 11px; color: #64748B;
        display: inline-flex; align-items: center; gap: 5px;
        margin-top: 2px;
    }
    .cd-merchant-meta svg { width: 10px; height: 10px; color: #94A3B8; }
    .cd-merchant-arrow {
        margin-left: auto;
        color: #94A3B8;
    }
    .cd-merchant-arrow svg { width: 14px; height: 14px; }

    /* ====== Amount selector ====== */
    .cd-section { margin-bottom: 24px; }
    .cd-section-label {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 11px; font-weight: 800; color: #64748B;
        text-transform: uppercase; letter-spacing: .10em;
        margin: 0 0 10px;
    }
    .cd-amounts {
        display: grid; grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }
    @media (min-width:640px) { .cd-amounts { grid-template-columns: repeat(4, 1fr); } }
    .cd-amount {
        padding: 14px 8px;
        background: white;
        border: 2px solid #E2E8F0;
        border-radius: 12px;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 15px; font-weight: 800;
        color: #0F172A;
        cursor: pointer;
        transition: all .15s;
        font-variant-numeric: tabular-nums;
        text-align: center;
    }
    .cd-amount:hover { border-color: #44A08D; }
    .cd-amount--selected {
        border-color: #44A08D;
        background: linear-gradient(135deg, #ECFDF5, #D1FAE5);
        color: #0F4F44;
        box-shadow: 0 6px 14px -4px rgba(78,205,196,.30);
    }
    .cd-amount-label {
        display: block;
        font-size: 9px; font-weight: 700; color: #94A3B8;
        text-transform: uppercase; letter-spacing: .08em;
        margin-bottom: 2px;
    }
    .cd-custom-amount {
        display: flex; align-items: center; gap: 8px;
        padding: 12px 14px;
        background: #F8FAFC;
        border: 2px dashed #CBD5E1;
        border-radius: 12px;
        margin-top: 8px;
    }
    .cd-custom-amount label {
        font-size: 12px; font-weight: 700; color: #475569;
        white-space: nowrap;
    }
    .cd-custom-amount input {
        flex: 1; padding: 8px 10px;
        font-size: 16px; font-weight: 800;
        font-variant-numeric: tabular-nums;
        font-family: 'Space Grotesk','Inter',sans-serif;
        border: 1.5px solid #E2E8F0;
        border-radius: 8px;
        background: white;
    }
    .cd-custom-amount input:focus {
        outline: 0; border-color: #44A08D;
        box-shadow: 0 0 0 3px rgba(68,160,141,.15);
    }
    .cd-custom-amount span { color: #64748B; font-size: 12px; font-weight: 700; }

    /* ====== CTA ====== */
    .cd-cta-wrap {
        background: linear-gradient(135deg, #0F172A 0%, #0F4F44 100%);
        color: white;
        border-radius: 18px;
        padding: 22px 24px;
        margin-bottom: 24px;
        position: relative; overflow: hidden;
    }
    .cd-cta-wrap::before {
        content: ''; position: absolute;
        top: -40%; right: -10%;
        width: 280px; height: 280px;
        background: radial-gradient(circle, rgba(94,234,212,.25), transparent 60%);
        pointer-events: none;
    }
    .cd-cta-amount-row {
        position: relative; z-index: 1;
        display: flex; justify-content: space-between; align-items: baseline;
        margin-bottom: 16px;
    }
    .cd-cta-amount-label {
        font-size: 11px; font-weight: 700;
        color: rgba(255,255,255,.6);
        text-transform: uppercase; letter-spacing: .10em;
    }
    .cd-cta-amount-value {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 32px; font-weight: 800;
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.02em;
    }
    .cd-cta-amount-value small {
        font-size: 14px; font-weight: 700;
        color: rgba(255,255,255,.6);
        margin-left: 4px;
    }
    .cd-cta-btn {
        position: relative; z-index: 1;
        width: 100%;
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        padding: 16px 24px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        border: 0; border-radius: 14px;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 15px; font-weight: 800;
        cursor: pointer;
        box-shadow: 0 14px 28px -8px rgba(78,205,196,.50),
                    inset 0 1px 0 rgba(255,255,255,.30);
        transition: transform .15s, box-shadow .15s;
        text-decoration: none;
    }
    .cd-cta-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 18px 32px -8px rgba(78,205,196,.60);
    }
    .cd-cta-btn:disabled {
        background: #475569;
        cursor: not-allowed;
        opacity: .6;
        transform: none;
        box-shadow: none;
    }
    .cd-cta-btn svg { width: 18px; height: 18px; }
    .cd-cta-note {
        position: relative; z-index: 1;
        margin-top: 12px;
        font-size: 11px; color: rgba(255,255,255,.6);
        text-align: center;
    }

    /* ====== Description + Terms ====== */
    .cd-block {
        background: white;
        border-radius: 14px;
        padding: 20px;
        margin-bottom: 14px;
        border: 1px solid #E2E8F0;
    }
    .cd-block h3 {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 14px; font-weight: 800; color: #0F172A;
        margin: 0 0 10px;
        display: flex; align-items: center; gap: 8px;
    }
    .cd-block h3 svg {
        width: 16px; height: 16px;
        color: #44A08D;
    }
    .cd-block p, .cd-block li {
        font-size: 13px; line-height: 1.65;
        color: #475569;
        margin: 0;
        white-space: pre-line;
    }
    .cd-block ul { padding-left: 18px; margin: 0; }

    /* ====== Features list ====== */
    .cd-feats {
        display: grid; gap: 8px;
        grid-template-columns: 1fr 1fr;
        margin-bottom: 14px;
    }
    .cd-feat {
        display: flex; gap: 10px; align-items: flex-start;
        background: white;
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid #E2E8F0;
    }
    .cd-feat-ic {
        width: 32px; height: 32px;
        border-radius: 10px;
        background: linear-gradient(135deg, #ECFDF5, #D1FAE5);
        color: #0F4F44;
        display: inline-flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .cd-feat-ic svg { width: 16px; height: 16px; }
    .cd-feat-txt strong {
        display: block; font-size: 12px; font-weight: 800; color: #0F172A;
        margin-bottom: 2px;
    }
    .cd-feat-txt span { font-size: 11px; color: #64748B; line-height: 1.4; }

    /* ====== Other cards from this merchant ====== */
    .cd-other {
        margin-top: 48px;
        padding-top: 32px;
        border-top: 1px solid #E2E8F0;
    }
    .cd-other h2 {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 22px; font-weight: 800; color: #0F172A;
        margin: 0 0 16px;
        letter-spacing: -0.02em;
    }
    .cd-other-grid {
        display: grid; gap: 16px;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    }
    .cd-other-link { text-decoration: none; color: inherit; display: block; }
    .cd-other-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 13px; font-weight: 800; color: #0F172A;
        margin: 8px 0 0;
    }
</style>

<div class="cd-wrap" x-data="cardDetail({
    denoms: {{ json_encode($card->denominations ?? []) }},
    allowCustom: {{ $card->allow_custom_amount ? 'true' : 'false' }},
    minAmount: {{ (int) ($card->min_amount ?? 0) }},
    maxAmount: {{ (int) ($card->max_amount ?? 0) }},
})">

    {{-- ====== Breadcrumb ====== --}}
    <nav class="cd-crumb">
        <a href="{{ route('gabon.index') }}">Carte Gabon</a>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('gabon.merchant', $merchant->slug) }}">{{ $merchant->business_name ?? $merchant->name }}</a>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span>{{ $card->name }}</span>
    </nav>

    <div class="cd-grid">
        {{-- ============ LEFT : VISUAL ============ --}}
        <div class="cd-visual-wrap">
            <div class="cd-visual-cont">
                @include('partials._merchant-card-visual', ['card' => $card])
            </div>
        </div>

        {{-- ============ RIGHT : INFO + BUY ============ --}}
        <div>
            @if(isset($categories[$card->category]))
                <span class="cd-cat">{{ $categories[$card->category] }}</span>
            @endif
            <h1 class="cd-title">{{ $card->name }}</h1>

            <a href="{{ route('gabon.merchant', $merchant->slug) }}" class="cd-merchant-link">
                <div class="cd-merchant-av">
                    @if($merchant->logo_url)
                        <img src="{{ asset($merchant->logo_url) }}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:10px;">
                    @else
                        {{ strtoupper(substr($merchant->business_name ?? $merchant->name, 0, 1)) }}
                    @endif
                </div>
                <div>
                    <div class="cd-merchant-name">{{ $merchant->business_name ?? $merchant->name }}</div>
                    @if($merchant->city)
                        <span class="cd-merchant-meta">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ $merchant->city }}
                        </span>
                    @endif
                </div>
                <span class="cd-merchant-arrow">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </span>
            </a>

            {{-- ============ AMOUNT SELECTOR ============ --}}
            <div class="cd-section">
                <p class="cd-section-label">Choisis le montant</p>
                <div class="cd-amounts">
                    @foreach($card->denominations ?? [] as $d)
                        <button type="button"
                                class="cd-amount"
                                :class="selectedDenom === {{ $d }} ? 'cd-amount--selected' : ''"
                                @click="selectDenom({{ $d }})">
                            <span class="cd-amount-label">FCFA</span>
                            {{ number_format($d, 0, ',', ' ') }}
                        </button>
                    @endforeach
                </div>

                @if($card->allow_custom_amount)
                    <div class="cd-custom-amount">
                        <label for="custom_amount">Autre montant :</label>
                        <input type="number" id="custom_amount"
                               x-model.number="customAmount"
                               @input="onCustomInput()"
                               :min="minAmount" :max="maxAmount"
                               step="500"
                               placeholder="{{ number_format($card->min_amount ?? 1000, 0, ',', ' ') }}">
                        <span>FCFA</span>
                    </div>
                    <p style="font-size:11px;color:#94A3B8;margin-top:6px;">
                        Entre {{ number_format($card->min_amount ?? 0, 0, ',', ' ') }} et {{ number_format($card->max_amount ?? 0, 0, ',', ' ') }} FCFA
                    </p>
                @endif
            </div>

            {{-- ============ CTA ============ --}}
            <div class="cd-cta-wrap">
                <div class="cd-cta-amount-row">
                    <span class="cd-cta-amount-label">Total</span>
                    <span class="cd-cta-amount-value">
                        <span x-text="formatAmount(amount)"></span><small>FCFA</small>
                    </span>
                </div>
                <button type="button" class="cd-cta-btn" :disabled="!amount" @click="buy()">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    Acheter maintenant
                </button>
                <p class="cd-cta-note">Paiement sécurisé · Livraison instantanée par SMS · WhatsApp · email</p>
            </div>

            {{-- ============ FEATURES ============ --}}
            <div class="cd-feats">
                <div class="cd-feat">
                    <span class="cd-feat-ic">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    <div class="cd-feat-txt">
                        <strong>Validité {{ $card->validity_months }} mois</strong>
                        <span>À utiliser avant expiration</span>
                    </div>
                </div>
                <div class="cd-feat">
                    <span class="cd-feat-ic">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </span>
                    <div class="cd-feat-txt">
                        <strong>Envoi instantané</strong>
                        <span>SMS, WhatsApp ou email</span>
                    </div>
                </div>
                <div class="cd-feat">
                    <span class="cd-feat-ic">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </span>
                    <div class="cd-feat-txt">
                        <strong>Paiement sécurisé</strong>
                        <span>Carte, Mobile Money</span>
                    </div>
                </div>
                <div class="cd-feat">
                    <span class="cd-feat-ic">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </span>
                    <div class="cd-feat-txt">
                        <strong>Plusieurs utilisations</strong>
                        <span>Jusqu'à épuisement du solde</span>
                    </div>
                </div>
            </div>

            {{-- ============ DESCRIPTION ============ --}}
            @if($card->description)
                <div class="cd-block">
                    <h3>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Description
                    </h3>
                    <p>{{ $card->description }}</p>
                </div>
            @endif

            {{-- ============ TERMS ============ --}}
            @if($card->terms_conditions)
                <div class="cd-block">
                    <h3>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Conditions d'utilisation
                    </h3>
                    <p>{{ $card->terms_conditions }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ============ OTHER CARDS FROM MERCHANT ============ --}}
    @if($otherCards->isNotEmpty())
        <section class="cd-other">
            <h2>Autres cartes de {{ $merchant->business_name ?? $merchant->name }}</h2>
            <div class="cd-other-grid">
                @foreach($otherCards as $other)
                    <a href="{{ route('gabon.card', $other) }}" class="cd-other-link">
                        @include('partials._merchant-card-visual', ['card' => $other, 'compact' => true])
                        <h3 class="cd-other-title">{{ $other->name }}</h3>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

</div>

<script>
    function cardDetail(opts) {
        return {
            denoms: opts.denoms,
            allowCustom: opts.allowCustom,
            minAmount: opts.minAmount,
            maxAmount: opts.maxAmount,
            selectedDenom: opts.denoms[0] || null,
            customAmount: null,

            init() {
                this.amount = this.selectedDenom;
            },
            get amount() {
                return this.customAmount || this.selectedDenom || 0;
            },
            set amount(v) { /* derived */ },
            selectDenom(v) {
                this.selectedDenom = v;
                this.customAmount = null;
            },
            onCustomInput() {
                if (this.customAmount) this.selectedDenom = null;
            },
            formatAmount(n) {
                return Number(n || 0).toLocaleString('fr-FR', { useGrouping: true });
            },
            buy() {
                // Phase 4 — futursowax purchase flow. Pour l'instant on alerte.
                const amount = this.amount;
                if (!amount) return;
                alert("Achat de carte cadeau marchand : " + this.formatAmount(amount) + " FCFA.\n\nLe flux de paiement futursowax sera activé en Phase 4. Reviens bientôt !");
            },
        };
    }
</script>
@endsection
