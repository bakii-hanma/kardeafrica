@extends('vendor.layouts.vendor')

@section('title', 'Remettre le cash')

@section('content')
@php
    use App\Models\ResellerCashRemittance;
    $cashToRemit = (float) $reseller->cash_to_remit;
    $headroom    = max(0, (float) $reseller->max_wallet - (float) $reseller->wallet_balance);
    $maxRemit    = min($cashToRemit, $headroom);
@endphp
<div class="vrm-wrap" x-data="vendorRemittance({
    maxRemit: {{ (int) $maxRemit }},
    cashToRemit: {{ (int) $cashToRemit }},
    headroom: {{ (int) $headroom }},
})">

    {{-- ============= HEADER ============= --}}
    <div class="vrm-head">
        <div>
            <div class="vrm-eyebrow">Restitution</div>
            <h1 class="vrm-title">Remettre le cash à KardAfrica</h1>
            <p class="vrm-sub">Tu as encaissé du cash physique pour le compte de KardAfrica. Reverse-le ici via E-Billing pour reconstituer ton solde de vente.</p>
        </div>
    </div>

    @php
        $totalCollected = (float) $reseller->total_cash_collected;
        $totalRemitted  = (float) $reseller->total_cash_remitted;
    @endphp

    {{-- ============= CASH À REMETTRE — CARTE PRINCIPALE ============= --}}
    @if($cashToRemit > 0)
        <div class="vrm-amount-card">
            <div class="vrm-amount-glow"></div>
            <div class="vrm-amount-eyebrow">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Cash à reverser à KardAfrica
            </div>
            <div class="vrm-amount-value">
                {{ number_format($cashToRemit, 0, ',', ' ') }}<span>FCFA</span>
            </div>
            <div class="vrm-amount-meta">
                Cet argent ne t'appartient pas — il représente les cartes vendues.<br>
                Ton solde de vente sera reconstitué dès la remise validée.
            </div>
        </div>
    @else
        <div class="vrm-empty-card">
            <div class="vrm-empty-ico">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div class="vrm-empty-title">Tout est en règle</div>
            <div class="vrm-empty-text">Tu n'as aucun cash à reverser pour le moment.</div>
            <a href="{{ route('vendor.sell') }}" class="vrm-empty-cta">Aller vendre</a>
        </div>
    @endif

    {{-- ============= STRIP CUMULS (toujours visible) ============= --}}
    @if($totalCollected > 0)
        <div class="vrm-stats">
            <div class="vrm-stats-cell">
                <div class="vrm-stats-label">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
                    Total cash encaissé
                </div>
                <div class="vrm-stats-value">{{ number_format($totalCollected, 0, ',', ' ') }} <span>FCFA</span></div>
                <div class="vrm-stats-meta">depuis l'inscription</div>
            </div>
            <div class="vrm-stats-divider"></div>
            <div class="vrm-stats-cell">
                <div class="vrm-stats-label vrm-stats-label--green">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Déjà reversé
                </div>
                <div class="vrm-stats-value vrm-stats-value--green">{{ number_format($totalRemitted, 0, ',', ' ') }} <span>FCFA</span></div>
                <div class="vrm-stats-meta">via E-Billing</div>
            </div>
            <div class="vrm-stats-divider"></div>
            <div class="vrm-stats-cell">
                <div class="vrm-stats-label vrm-stats-label--rose">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Reste à remettre
                </div>
                <div class="vrm-stats-value vrm-stats-value--rose">{{ number_format($cashToRemit, 0, ',', ' ') }} <span>FCFA</span></div>
                <div class="vrm-stats-meta">solde courant</div>
            </div>
        </div>
    @endif

    {{-- ============= FORMULAIRE REMISE — TOUT D'UN COUP ============= --}}
    @if($cashToRemit > 0 && $headroom > 0)
        <div class="vrm-card">
            <h2 class="vrm-card-title">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Reverse l'intégralité du cash
            </h2>

            {{-- Montant fixe : pas de saisie, pas de preset. Le vendeur DOIT remettre tout. --}}
            <div class="vrm-fixed-amount">
                <div class="vrm-fixed-label">Montant à reverser</div>
                <div class="vrm-fixed-value">
                    {{ number_format($maxRemit, 0, ',', ' ') }}<span>FCFA</span>
                </div>
            </div>

            <div class="vrm-impact">
                <div class="vrm-impact-row">
                    <span>Cash restant après cette remise</span>
                    <strong>0 FCFA</strong>
                </div>
                <div class="vrm-impact-row vrm-impact-row--brand">
                    <span>Nouveau solde de vente</span>
                    <strong>{{ number_format((int) $reseller->wallet_balance + (int) $maxRemit, 0, ',', ' ') }} FCFA</strong>
                </div>
            </div>

            {{-- Erreur --}}
            <div x-show="error" x-cloak class="vrm-error">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span x-text="error"></span>
            </div>

            <button type="button" @click="submit()"
                    :disabled="loading"
                    class="vrm-submit">
                <svg x-show="!loading" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <svg x-show="loading" x-cloak class="vrm-spin" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none"/></svg>
                <span x-show="!loading">Remettre {{ number_format($maxRemit, 0, ',', ' ') }} FCFA via E-Billing</span>
                <span x-show="loading" x-cloak>Initialisation E-Billing…</span>
            </button>

            <p class="vrm-note">
                Tu seras redirigé vers le portail E-Billing pour payer <strong>l'intégralité</strong> du cash en Mobile Money ou par carte. Une fois validé, ton solde de vente est reconstitué automatiquement.
            </p>
        </div>
    @elseif($cashToRemit > 0 && $headroom > 0 && $cashToRemit > $headroom)
        {{-- Cas où on ne peut pas tout remettre d'un coup à cause du plafond --}}
        <div class="vrm-warn-card">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div>
                <strong>Plafond wallet trop bas pour tout reverser</strong>
                <p>Tu as {{ number_format($cashToRemit, 0, ',', ' ') }} FCFA de cash mais ton wallet ne peut accueillir que {{ number_format($headroom, 0, ',', ' ') }} FCFA supplémentaires. Vends quelques cartes pour libérer de la place avant de pouvoir tout reverser.</p>
            </div>
        </div>
    @elseif($cashToRemit > 0 && $headroom === 0)
        {{-- wallet plein : impossible de remettre tant qu'il n'a pas vendu --}}
        <div class="vrm-warn-card">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div>
                <strong>Wallet déjà au plafond</strong>
                <p>Ton solde de vente est déjà à {{ number_format($reseller->wallet_balance, 0, ',', ' ') }} FCFA / {{ number_format($reseller->max_wallet, 0, ',', ' ') }} FCFA. Vends quelques cartes pour libérer de la place avant de remettre.</p>
            </div>
        </div>
    @endif

    {{-- ============= PENDING ============= --}}
    @if($pending->count() > 0)
        <h2 class="vrm-section-title">En cours</h2>
        @foreach($pending as $r)
            <a href="{{ $r->portal_url ?: '#' }}" class="vrm-pending">
                <div class="vrm-pending-l">
                    <div class="vrm-pending-ico"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                    <div>
                        <div class="vrm-pending-amount">{{ number_format($r->amount, 0, ',', ' ') }} FCFA</div>
                        <div class="vrm-pending-meta">{{ $r->external_reference }} · {{ $r->created_at->diffForHumans() }}</div>
                    </div>
                </div>
                <span class="vrm-pending-badge">En attente</span>
            </a>
        @endforeach
    @endif

    {{-- ============= HISTORIQUE ============= --}}
    @if($history->count() > 0)
        <h2 class="vrm-section-title">Historique des remises</h2>
        <div class="vrm-history">
            @foreach($history as $r)
                @php
                    $isOk = $r->status === ResellerCashRemittance::STATUS_COMPLETED;
                    $statusLabel = match($r->status) {
                        ResellerCashRemittance::STATUS_COMPLETED => 'Validée',
                        ResellerCashRemittance::STATUS_FAILED    => 'Échouée',
                        ResellerCashRemittance::STATUS_CANCELLED => 'Annulée',
                        ResellerCashRemittance::STATUS_PENDING   => 'En attente',
                        default                                  => ucfirst($r->status),
                    };
                @endphp
                <div class="vrm-history-row">
                    <div class="vrm-history-dot {{ $isOk ? 'vrm-dot-green' : 'vrm-dot-rose' }}"></div>
                    <div class="vrm-history-body">
                        <div class="vrm-history-amount">{{ number_format($r->amount, 0, ',', ' ') }} <span>FCFA</span></div>
                        <div class="vrm-history-meta">
                            {{ $r->external_reference }} ·
                            @if($isOk)
                                Validée {{ optional($r->processed_at)->format('d/m/Y H:i') }}
                            @else
                                {{ $statusLabel }} {{ $r->updated_at->format('d/m/Y H:i') }}
                            @endif
                        </div>
                    </div>
                    <span class="vrm-history-badge {{ $isOk ? 'vrm-badge-green' : 'vrm-badge-rose' }}">
                        {{ $isOk ? '✓ OK' : $statusLabel }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif

</div>

<style>
    .vrm-wrap { max-width: 720px; margin: 0 auto; padding-bottom: 100px; }
    [x-cloak] { display: none !important; }

    .vrm-head { margin-bottom: 14px; }
    .vrm-eyebrow { font-family: 'Inter', sans-serif; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.10em; color: #B45309; }
    .vrm-title { font-family: 'Space Grotesk','Inter',sans-serif; font-size: 26px; font-weight: 800; color: #0F172A; margin: 4px 0 6px; }
    .vrm-sub { font-size: 13px; color: #64748B; max-width: 540px; line-height: 1.5; }

    /* Carte montant à remettre — gradient rouge proéminent */
    .vrm-amount-card {
        position: relative;
        background: linear-gradient(135deg, #BE123C 0%, #F43F5E 60%, #FB7185 100%);
        border-radius: 22px;
        padding: 22px 22px 20px;
        color: white;
        margin-bottom: 14px;
        overflow: hidden;
        box-shadow: 0 20px 40px -16px rgba(244,63,94,0.50),
                    inset 0 1px 0 rgba(255,255,255,0.30);
    }
    .vrm-amount-glow {
        position: absolute; top: -100px; right: -100px;
        width: 280px; height: 280px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255,255,255,0.30) 0%, transparent 70%);
        pointer-events: none;
    }
    .vrm-amount-eyebrow {
        position: relative;
        display: inline-flex; align-items: center; gap: 7px;
        font-size: 11px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.10em;
        opacity: 0.95;
    }
    .vrm-amount-eyebrow svg { width: 14px; height: 14px; }
    .vrm-amount-value {
        position: relative;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 44px; font-weight: 800;
        line-height: 1;
        margin-top: 8px;
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.02em;
    }
    .vrm-amount-value span {
        font-size: 14px; font-weight: 700;
        opacity: 0.80;
        margin-left: 6px;
    }
    .vrm-amount-meta {
        position: relative;
        margin-top: 10px;
        font-size: 12px;
        opacity: 0.92;
        line-height: 1.5;
    }

    /* Carte vide (cash à remettre = 0) */
    .vrm-empty-card {
        background: white;
        border: 2px dashed #E2E8F0;
        border-radius: 20px;
        padding: 32px 22px;
        text-align: center;
        margin-bottom: 14px;
    }
    .vrm-empty-ico {
        display: inline-flex;
        width: 56px; height: 56px;
        border-radius: 16px;
        background: #ECFDF5;
        color: #10B981;
        align-items: center; justify-content: center;
        margin-bottom: 10px;
    }
    .vrm-empty-ico svg { width: 28px; height: 28px; }
    .vrm-empty-title { font-family: 'Space Grotesk','Inter',sans-serif; font-size: 16px; font-weight: 800; color: #0F172A; }
    .vrm-empty-text { font-size: 12px; color: #64748B; margin-top: 4px; }
    .vrm-empty-cta {
        display: inline-block;
        margin-top: 12px;
        padding: 10px 18px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        border-radius: 10px;
        font-size: 12px; font-weight: 700;
        text-decoration: none;
        box-shadow: 0 6px 16px -6px rgba(68,160,141,0.40);
    }

    /* Strip cumuls cash collected / remitted / to_remit */
    .vrm-stats {
        display: grid;
        grid-template-columns: 1fr 1px 1fr 1px 1fr;
        gap: 12px;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        padding: 16px 18px;
        margin-bottom: 14px;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }
    .vrm-stats-cell { min-width: 0; }
    .vrm-stats-divider { background: #E2E8F0; }
    .vrm-stats-label {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.08em;
        color: #94A3B8;
    }
    .vrm-stats-label svg { width: 12px; height: 12px; }
    .vrm-stats-label--green { color: #047857; }
    .vrm-stats-label--rose  { color: #BE123C; }
    .vrm-stats-value {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 18px; font-weight: 800;
        color: #0F172A;
        font-variant-numeric: tabular-nums;
        line-height: 1.1;
        margin-top: 4px;
    }
    .vrm-stats-value span { font-size: 11px; font-weight: 600; color: #94A3B8; margin-left: 2px; }
    .vrm-stats-value--green { color: #047857; }
    .vrm-stats-value--rose  { color: #BE123C; }
    .vrm-stats-meta { font-size: 10px; color: #94A3B8; margin-top: 2px; }
    @media (max-width: 540px) {
        .vrm-stats { grid-template-columns: 1fr; gap: 10px; }
        .vrm-stats-divider { display: none; }
        .vrm-stats-cell:not(:last-child) { padding-bottom: 10px; border-bottom: 1px solid #F1F5F9; }
    }

    /* Form */
    .vrm-card {
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 18px;
        padding: 18px 18px 16px;
        margin-bottom: 14px;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }
    .vrm-card-title {
        display: flex; align-items: center; gap: 8px;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 14px; font-weight: 800; color: #0F172A;
        margin: 0 0 12px;
    }
    .vrm-card-title svg { width: 14px; height: 14px; color: #44A08D; }

    .vrm-amount-input-wrap {
        position: relative;
        display: flex; align-items: center;
        background: #F8FAFC;
        border: 2px solid #E2E8F0;
        border-radius: 14px;
        padding: 14px 16px;
        margin-bottom: 12px;
        transition: border-color .2s ease;
    }
    .vrm-amount-input-wrap:focus-within { border-color: #44A08D; background: white; }
    .vrm-amount-input {
        flex: 1; min-width: 0;
        background: transparent; border: 0;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 28px; font-weight: 800;
        color: #0F172A;
        font-variant-numeric: tabular-nums;
        outline: none;
        padding: 0;
    }
    /* hide spinner */
    .vrm-amount-input::-webkit-outer-spin-button,
    .vrm-amount-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .vrm-amount-input { -moz-appearance: textfield; }
    .vrm-amount-input-unit { font-size: 13px; font-weight: 700; color: #94A3B8; }

    .vrm-presets {
        display: flex; flex-wrap: wrap; gap: 6px;
        margin-bottom: 14px;
    }
    .vrm-preset {
        flex: 1; min-width: 70px;
        padding: 8px 10px;
        border-radius: 10px;
        border: 1px solid #E2E8F0;
        background: white;
        font-size: 12px; font-weight: 700;
        color: #475569;
        cursor: pointer;
        transition: all .15s ease;
    }
    .vrm-preset:hover { border-color: #44A08D; color: #44A08D; }
    .vrm-preset--active {
        border-color: #44A08D !important;
        background: #F0FDFA !important;
        color: #044E47 !important;
    }
    .vrm-preset--max {
        flex: 1 1 100%;
        background: #FFFBEB;
        border-color: #FDE68A;
        color: #92400E;
    }
    .vrm-preset--max:hover { border-color: #F59E0B; color: #B45309; }

    .vrm-impact {
        background: #F8FAFC;
        border-radius: 10px;
        padding: 10px 12px;
        margin-bottom: 12px;
    }
    .vrm-impact-row {
        display: flex; justify-content: space-between; align-items: baseline;
        font-size: 12px;
        padding: 3px 0;
        font-variant-numeric: tabular-nums;
        color: #64748B;
    }
    .vrm-impact-row strong { color: #0F172A; font-weight: 800; }
    .vrm-impact-row--brand { color: #44A08D; }
    .vrm-impact-row--brand strong { color: #44A08D; }

    /* Bloc montant fixe (tout d'un coup) */
    .vrm-fixed-amount {
        background: linear-gradient(135deg, #FFFBEB, white);
        border: 2px dashed #FCD34D;
        border-radius: 14px;
        padding: 16px 18px;
        text-align: center;
        margin-bottom: 12px;
    }
    .vrm-fixed-label {
        font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.10em;
        color: #B45309;
    }
    .vrm-fixed-value {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 32px; font-weight: 800;
        color: #0F172A;
        font-variant-numeric: tabular-nums;
        line-height: 1.1;
        letter-spacing: -0.02em;
        margin-top: 4px;
    }
    .vrm-fixed-value span {
        font-size: 12px; font-weight: 700;
        color: #94A3B8;
        margin-left: 4px;
    }

    .vrm-error {
        display: flex; gap: 8px; align-items: flex-start;
        background: #FEF2F2;
        border: 1px solid #FECACA;
        color: #BE123C;
        border-radius: 10px;
        padding: 10px 12px;
        margin-bottom: 12px;
        font-size: 12px;
        line-height: 1.4;
    }
    .vrm-error svg { width: 14px; height: 14px; flex-shrink: 0; margin-top: 2px; }

    .vrm-submit {
        width: 100%;
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        padding: 14px 18px;
        border-radius: 14px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        font-family: 'Inter', sans-serif;
        font-size: 14px; font-weight: 800;
        cursor: pointer;
        border: 0;
        transition: all .2s ease;
        box-shadow: 0 12px 24px -8px rgba(68,160,141,0.55),
                    inset 0 1px 0 rgba(255,255,255,0.30);
    }
    .vrm-submit svg { width: 16px; height: 16px; }
    .vrm-submit:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 18px 32px -8px rgba(68,160,141,0.65), inset 0 1px 0 rgba(255,255,255,0.30);
    }
    .vrm-submit:disabled { opacity: 0.50; cursor: not-allowed; }
    .vrm-spin { animation: vrm-spin 1s linear infinite; }
    .vrm-spin circle { stroke-dasharray: 50 100; stroke-linecap: round; }
    @keyframes vrm-spin { to { transform: rotate(360deg); } }

    .vrm-note { font-size: 11px; color: #64748B; line-height: 1.5; margin: 10px 0 0; }

    .vrm-warn-card {
        display: flex; gap: 12px; align-items: flex-start;
        background: #FFFBEB;
        border: 1px solid #FDE68A;
        border-radius: 14px;
        padding: 14px 16px;
        margin-bottom: 14px;
    }
    .vrm-warn-card svg { width: 18px; height: 18px; color: #B45309; flex-shrink: 0; margin-top: 2px; }
    .vrm-warn-card strong { display: block; color: #92400E; font-size: 13px; margin-bottom: 2px; }
    .vrm-warn-card p { font-size: 12px; color: #78350F; line-height: 1.5; margin: 0; }

    /* Pending + history */
    .vrm-section-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 13px; font-weight: 800;
        color: #0F172A;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin: 18px 0 8px;
    }
    .vrm-pending {
        display: flex; align-items: center; justify-content: space-between;
        background: #FFFBEB;
        border: 1px solid #FDE68A;
        border-radius: 12px;
        padding: 12px 14px;
        margin-bottom: 6px;
        text-decoration: none;
        color: inherit;
    }
    .vrm-pending:hover { background: #FEF3C7; }
    .vrm-pending-l { display: flex; align-items: center; gap: 10px; }
    .vrm-pending-ico {
        width: 32px; height: 32px;
        border-radius: 9px;
        background: #F59E0B;
        color: white;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .vrm-pending-ico svg { width: 14px; height: 14px; }
    .vrm-pending-amount { font-family: 'Space Grotesk','Inter',sans-serif; font-size: 14px; font-weight: 800; color: #0F172A; font-variant-numeric: tabular-nums; }
    .vrm-pending-meta { font-size: 11px; color: #B45309; font-family: 'JetBrains Mono', monospace; margin-top: 1px; }
    .vrm-pending-badge { font-size: 10px; font-weight: 800; color: #B45309; text-transform: uppercase; letter-spacing: 0.06em; }

    .vrm-history { display: flex; flex-direction: column; gap: 4px; }
    .vrm-history-row {
        display: flex; align-items: center; gap: 12px;
        padding: 10px 12px;
        background: white;
        border: 1px solid #F1F5F9;
        border-radius: 10px;
    }
    .vrm-history-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .vrm-dot-green { background: #10B981; }
    .vrm-dot-rose  { background: #F43F5E; }
    .vrm-history-body { flex: 1; min-width: 0; }
    .vrm-history-amount {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 13px; font-weight: 800; color: #0F172A;
        font-variant-numeric: tabular-nums;
    }
    .vrm-history-amount span { font-size: 9px; color: #94A3B8; font-weight: 600; margin-left: 2px; }
    .vrm-history-meta { font-size: 10px; color: #94A3B8; font-family: 'JetBrains Mono', monospace; margin-top: 1px; }
    .vrm-history-badge {
        font-size: 9px; font-weight: 800;
        padding: 3px 7px; border-radius: 6px;
        flex-shrink: 0;
    }
    .vrm-badge-green { background: #D1FAE5; color: #047857; }
    .vrm-badge-rose  { background: #FFE4E6; color: #BE123C; }
</style>

<script>
window.vendorRemittance = function ({ maxRemit, cashToRemit, headroom }) {
    return {
        maxRemit, cashToRemit, headroom,
        loading: false,
        error: null,

        async submit() {
            this.error = null;
            this.loading = true;
            try {
                const res = await fetch('{{ route('vendor.remittance.init') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    // Toujours tout reverser — pas de saisie partielle
                    body: JSON.stringify({ amount: this.maxRemit }),
                });
                const data = await res.json();
                if (data.success && data.portal_url) {
                    window.location.href = data.portal_url;
                    return;
                }
                this.error = data.message || 'Impossible d\'initialiser la remise.';
            } catch (e) {
                this.error = 'Erreur réseau : ' + e.message;
            } finally {
                this.loading = false;
            }
        },
    };
};
</script>
@endsection
