@extends('vendor.layouts.vendor')

@section('title', 'Encaisser ' . $order->order_number)

@section('content')
@php
    use App\Models\Order;
    $isPending   = $order->payment_status === Order::PAYMENT_STATUS_PENDING;
    $isCompleted = $order->payment_status === Order::PAYMENT_STATUS_COMPLETED;
    $isCancelled = in_array($order->payment_status, [Order::PAYMENT_STATUS_CANCELLED, Order::PAYMENT_STATUS_FAILED], true);
    $expired     = $order->cash_lock_expires_at && $order->cash_lock_expires_at->isPast() && $isPending;
    $name        = data_get($order->billing_details, 'name') ?: ($order->user->name ?? 'Client');
    $phone       = data_get($order->billing_details, 'phone') ?: ($order->user->phone ?? null);
    $rate        = (float) $reseller->commission_rate;
    $commission  = round((float) $order->subtotal * ($rate / 100), 2);
@endphp
<div class="vcs-wrap">

    <a href="{{ route('vendor.cash.index') }}" class="vcs-back">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Retour à la liste
    </a>

    {{-- ============= STATUS HEADER ============= --}}
    <div class="vcs-status-card vcs-status-card--{{ $isCompleted ? 'success' : ($isCancelled || $expired ? 'cancel' : 'pending') }}">
        @if($isCompleted)
            <div class="vcs-status-ico">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <h1 class="vcs-status-title">Encaissée — cartes envoyées au client</h1>
                <p class="vcs-status-sub">Wallet débité de {{ number_format($order->subtotal, 0, ',', ' ') }} FCFA · commission +{{ number_format($commission, 0, ',', ' ') }} FCFA versée.</p>
            </div>
        @elseif($expired)
            <div class="vcs-status-ico">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h1 class="vcs-status-title">Commande expirée</h1>
                <p class="vcs-status-sub">Le délai de 2h est dépassé. Les fonds sont libérés automatiquement.</p>
            </div>
        @elseif($isCancelled)
            <div class="vcs-status-ico">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <div>
                <h1 class="vcs-status-title">Commande annulée</h1>
                <p class="vcs-status-sub">Les fonds ont été libérés sur ton wallet de vente.</p>
            </div>
        @else
            <div class="vcs-status-ico">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
            </div>
            <div>
                <h1 class="vcs-status-title">À encaisser cash</h1>
                <p class="vcs-status-sub">Demande au client le code à 6 chiffres puis valide. Expire dans <strong>{{ $order->cash_lock_expires_at?->diffForHumans() }}</strong>.</p>
            </div>
        @endif
    </div>

    {{-- ============= CLIENT INFO ============= --}}
    <div class="vcs-card">
        <div class="vcs-card-head">
            <span class="vcs-card-eyebrow">Client</span>
            <h2 class="vcs-card-title">{{ $name }}</h2>
        </div>
        <div class="vcs-info-grid">
            @if($phone)
            <div class="vcs-info-row">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                <span class="vcs-info-label">Téléphone</span>
                <a href="tel:{{ $phone }}" class="vcs-info-value vcs-link">{{ $phone }}</a>
            </div>
            @endif
            <div class="vcs-info-row">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="vcs-info-label">N° commande</span>
                <span class="vcs-info-value vcs-mono">{{ $order->order_number }}</span>
            </div>
            <div class="vcs-info-row">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span class="vcs-info-label">Créée</span>
                <span class="vcs-info-value">{{ $order->created_at->format('d/m/Y H:i') }}</span>
            </div>
        </div>
    </div>

    {{-- ============= ITEMS ============= --}}
    <div class="vcs-card">
        <div class="vcs-card-head">
            <span class="vcs-card-eyebrow">Articles</span>
            <h2 class="vcs-card-title">{{ $order->orderItems->sum('quantity') }} carte{{ $order->orderItems->sum('quantity') > 1 ? 's' : '' }}</h2>
        </div>
        <div class="vcs-items">
            @foreach($order->orderItems as $item)
                <div class="vcs-item">
                    @if($item->image_url)
                        <img src="{{ $item->image_url }}" alt="" class="vcs-item-img">
                    @else
                        <div class="vcs-item-img-fallback">{{ strtoupper(substr($item->name, 0, 1)) }}</div>
                    @endif
                    <div class="vcs-item-body">
                        <div class="vcs-item-name">{{ $item->name }}</div>
                        <div class="vcs-item-meta">{{ number_format($item->unit_price, 0, ',', ' ') }} FCFA × {{ $item->quantity }}</div>
                    </div>
                    <div class="vcs-item-total">
                        {{ number_format($item->total_price, 0, ',', ' ') }} <span>FCFA</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="vcs-totals">
            <div class="vcs-totals-row">
                <span>Sous-total</span>
                <strong>{{ number_format($order->subtotal, 0, ',', ' ') }} FCFA</strong>
            </div>
            <div class="vcs-totals-row vcs-totals-row--brand">
                <span>Ta commission ({{ rtrim(rtrim(number_format($rate, 2), '0'), '.') }}%)</span>
                <strong>+{{ number_format($commission, 0, ',', ' ') }} FCFA</strong>
            </div>
            <div class="vcs-totals-divider"></div>
            <div class="vcs-totals-row vcs-totals-row--final">
                <span>À encaisser cash</span>
                <strong>{{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</strong>
            </div>
        </div>
    </div>

    {{-- ============= ACTIONS ============= --}}
    @if($isPending && !$expired)
        <div class="vcs-card vcs-confirm-card">
            <div class="vcs-confirm-head">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <h2>Confirmer l'encaissement</h2>
            </div>
            <p class="vcs-confirm-text">Demande au client le <strong>code à 6 chiffres</strong> qu'il voit sur son écran, puis encaisse <strong>{{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</strong> en espèces.</p>

            <form method="POST" action="{{ route('vendor.cash.confirm', $order) }}" class="vcs-form">
                @csrf
                <label class="vcs-label">Code de confirmation</label>
                <input type="text" name="confirmation_code" required autocomplete="off" inputmode="numeric"
                       pattern="[0-9]{6}" maxlength="6" placeholder="000000"
                       class="vcs-code-input">
                <button type="submit" class="vcs-btn vcs-btn--primary">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Confirmer & livrer les cartes
                </button>
            </form>

            <div x-data="{ showReject: false }" class="vcs-form vcs-form--reject">
                <button type="button" @click="showReject = true" class="vcs-btn vcs-btn--ghost">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    Refuser la commande
                </button>

                {{-- Modal carte : refuser commande --}}
                <div x-show="showReject" x-cloak
                     class="vcs-modal-backdrop"
                     @click.self="showReject = false"
                     @keydown.escape.window="showReject = false"
                     x-transition.opacity>
                    <div x-show="showReject" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="vcs-modal">
                        <div class="vcs-modal-banner">
                            <div class="vcs-modal-banner-glow"></div>
                            <div class="vcs-modal-icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                            <div class="vcs-modal-banner-text">
                                <div class="vcs-modal-eyebrow">Refus</div>
                                <div class="vcs-modal-title">Refuser cette commande&nbsp;?</div>
                            </div>
                        </div>
                        <div class="vcs-modal-body">
                            <p class="vcs-modal-question">
                                Le verrou de <strong>{{ number_format($order->subtotal, 0, ',', ' ') }} FCFA</strong> sera libéré sur ton solde de vente. Le client devra repasser une commande s'il revient.
                            </p>
                        </div>
                        <div class="vcs-modal-footer">
                            <button type="button" @click="showReject = false" class="vcs-modal-btn vcs-modal-btn--secondary">
                                Garder la commande
                            </button>
                            <form method="POST" action="{{ route('vendor.cash.reject', $order) }}" style="margin:0;flex:1;">
                                @csrf
                                <button type="submit" class="vcs-modal-btn vcs-modal-btn--danger">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Oui, refuser
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<style>
    .vcs-wrap { max-width: 720px; margin: 0 auto; padding-bottom: 100px; }

    .vcs-back {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 12px; color: #64748B;
        text-decoration: none;
        margin-bottom: 12px;
    }
    .vcs-back:hover { color: #0F172A; }
    .vcs-back svg { width: 14px; height: 14px; }

    .vcs-status-card {
        display: flex; align-items: flex-start; gap: 14px;
        border-radius: 16px;
        padding: 18px 20px;
        margin-bottom: 14px;
        color: white;
        box-shadow: 0 12px 28px -10px rgba(15,23,42,0.30);
    }
    .vcs-status-card--pending { background: linear-gradient(135deg, #F59E0B, #FBBF24); }
    .vcs-status-card--success { background: linear-gradient(135deg, #059669, #10B981); }
    .vcs-status-card--cancel  { background: linear-gradient(135deg, #BE123C, #EF4444); }
    .vcs-status-ico {
        width: 44px; height: 44px;
        border-radius: 12px;
        background: rgba(255,255,255,0.20);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .vcs-status-ico svg { width: 22px; height: 22px; }
    .vcs-status-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 18px; font-weight: 800;
        line-height: 1.15;
        margin: 2px 0 4px;
    }
    .vcs-status-sub { font-size: 12px; opacity: 0.92; line-height: 1.5; }
    .vcs-status-sub strong { font-weight: 800; }

    .vcs-card {
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        padding: 16px 18px;
        margin-bottom: 12px;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }
    .vcs-card-head { margin-bottom: 12px; }
    .vcs-card-eyebrow { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.10em; color: #B45309; }
    .vcs-card-title { font-family: 'Space Grotesk','Inter',sans-serif; font-size: 16px; font-weight: 700; color: #0F172A; margin: 2px 0 0; }

    .vcs-info-grid { display: flex; flex-direction: column; gap: 8px; }
    .vcs-info-row {
        display: grid;
        grid-template-columns: 16px 90px 1fr;
        align-items: center;
        gap: 10px;
        padding: 8px 10px;
        border-radius: 10px;
        background: #F8FAFC;
    }
    .vcs-info-row svg { width: 14px; height: 14px; color: #94A3B8; }
    .vcs-info-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #94A3B8; }
    .vcs-info-value { font-size: 13px; font-weight: 600; color: #0F172A; }
    .vcs-mono { font-family: 'JetBrains Mono','Fira Code',monospace; }
    .vcs-link { color: #44A08D; text-decoration: none; }
    .vcs-link:hover { text-decoration: underline; }

    .vcs-items { display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; }
    .vcs-item {
        display: flex; align-items: center; gap: 10px;
        padding: 8px 10px; border-radius: 10px;
        background: #F8FAFC;
    }
    .vcs-item-img, .vcs-item-img-fallback {
        width: 36px; height: 36px;
        border-radius: 8px;
        flex-shrink: 0;
        object-fit: contain;
        background: white;
        border: 1px solid #E2E8F0;
    }
    .vcs-item-img-fallback {
        display: flex; align-items: center; justify-content: center;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800;
        color: #44A08D;
    }
    .vcs-item-body { flex: 1; min-width: 0; }
    .vcs-item-name { font-size: 13px; font-weight: 700; color: #0F172A; }
    .vcs-item-meta { font-size: 11px; color: #64748B; margin-top: 1px; font-variant-numeric: tabular-nums; }
    .vcs-item-total {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 14px; font-weight: 800;
        color: #0F172A;
        font-variant-numeric: tabular-nums;
        flex-shrink: 0;
    }
    .vcs-item-total span { font-size: 9px; font-weight: 600; color: #94A3B8; margin-left: 2px; }

    .vcs-totals { padding: 12px 0 4px; border-top: 1px solid #F1F5F9; }
    .vcs-totals-row {
        display: flex; justify-content: space-between; align-items: baseline;
        font-size: 13px;
        padding: 4px 0;
        font-variant-numeric: tabular-nums;
    }
    .vcs-totals-row span { color: #64748B; }
    .vcs-totals-row strong { color: #0F172A; font-weight: 700; }
    .vcs-totals-row--brand { color: #44A08D; }
    .vcs-totals-row--brand span, .vcs-totals-row--brand strong { color: #44A08D; font-weight: 700; }
    .vcs-totals-divider { height: 1px; background: #E2E8F0; margin: 6px 0; }
    .vcs-totals-row--final { font-size: 16px; }
    .vcs-totals-row--final strong { font-family: 'Space Grotesk','Inter',sans-serif; font-size: 18px; font-weight: 800; }

    .vcs-confirm-card {
        background: linear-gradient(135deg, #FFFBEB 0%, white 60%);
        border: 1px solid #FCD34D;
        box-shadow: 0 12px 28px -10px rgba(245,158,11,0.30);
    }
    .vcs-confirm-head { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
    .vcs-confirm-head svg { width: 22px; height: 22px; color: #B45309; }
    .vcs-confirm-head h2 {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 18px; font-weight: 800; color: #0F172A; margin: 0;
    }
    .vcs-confirm-text { font-size: 13px; color: #475569; margin: 0 0 14px; line-height: 1.55; }
    .vcs-confirm-text strong { color: #0F172A; font-weight: 700; }

    .vcs-form { margin: 0; }
    .vcs-form--reject { margin-top: 10px; }
    .vcs-label {
        display: block;
        font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em;
        color: #475569;
        margin-bottom: 6px;
    }
    .vcs-code-input {
        width: 100%;
        font-family: 'JetBrains Mono','Fira Code',monospace;
        font-size: 28px; font-weight: 800;
        text-align: center; letter-spacing: 0.5em;
        padding: 14px;
        border: 2px dashed #FCD34D;
        border-radius: 12px;
        background: white;
        color: #0F172A;
        margin-bottom: 12px;
        outline: none;
        transition: border-color .2s ease;
    }
    .vcs-code-input:focus { border-color: #F59E0B; border-style: solid; }

    .vcs-btn {
        width: 100%;
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        padding: 13px 18px;
        border-radius: 12px;
        font-family: 'Inter', sans-serif;
        font-size: 14px; font-weight: 700;
        cursor: pointer;
        border: none;
        transition: all .2s ease;
    }
    .vcs-btn svg { width: 16px; height: 16px; }
    .vcs-btn--primary {
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        box-shadow: 0 8px 20px -6px rgba(68,160,141,0.45);
    }
    .vcs-btn--primary:hover { transform: translateY(-1px); box-shadow: 0 12px 28px -8px rgba(68,160,141,0.55); }
    .vcs-btn--ghost {
        background: transparent;
        color: #BE123C;
        border: 1px solid #FECACA;
    }
    .vcs-btn--ghost:hover { background: #FEF2F2; border-color: #FCA5A5; }

    /* ====================== MODAL REFUS ====================== */
    .vcs-modal-backdrop {
        position: fixed; inset: 0; z-index: 100;
        background: rgba(15, 23, 42, 0.55);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        display: flex; align-items: center; justify-content: center;
        padding: 16px;
    }
    .vcs-modal {
        position: relative;
        width: 100%; max-width: 440px;
        background: white;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 28px 60px -16px rgba(15,23,42,0.50), 0 6px 18px -6px rgba(15,23,42,0.30);
    }
    .vcs-modal-banner {
        position: relative;
        padding: 22px 22px 18px;
        background: linear-gradient(135deg, #BE123C 0%, #F43F5E 60%, #FB7185 100%);
        color: white;
        display: flex; align-items: center; gap: 14px;
        overflow: hidden;
    }
    .vcs-modal-banner-glow {
        position: absolute; top: -60px; right: -60px;
        width: 220px; height: 220px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255,255,255,0.30) 0%, transparent 70%);
        pointer-events: none;
    }
    .vcs-modal-icon {
        position: relative;
        width: 48px; height: 48px;
        border-radius: 14px;
        background: rgba(255,255,255,0.22);
        backdrop-filter: blur(6px);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.40);
    }
    .vcs-modal-icon svg { width: 22px; height: 22px; }
    .vcs-modal-banner-text { position: relative; min-width: 0; }
    .vcs-modal-eyebrow { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.10em; opacity: 0.92; }
    .vcs-modal-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 19px; font-weight: 800;
        line-height: 1.15;
        margin-top: 2px;
        letter-spacing: -0.01em;
    }
    .vcs-modal-body { padding: 20px 22px 18px; text-align: center; }
    .vcs-modal-question { font-size: 14px; color: #475569; line-height: 1.5; margin: 0; }
    .vcs-modal-question strong { color: #0F172A; font-weight: 800; }
    .vcs-modal-footer {
        display: flex; gap: 8px;
        padding: 14px 18px 18px;
        background: linear-gradient(180deg, white, #F8FAFC);
    }
    .vcs-modal-btn {
        flex: 1;
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        padding: 12px 14px;
        border-radius: 12px;
        font-family: 'Inter', sans-serif;
        font-size: 13px; font-weight: 700;
        cursor: pointer;
        border: 1px solid transparent;
        transition: all .2s ease;
        white-space: nowrap;
    }
    .vcs-modal-btn svg { width: 14px; height: 14px; }
    .vcs-modal-btn--secondary { background: white; border-color: #E2E8F0; color: #475569; }
    .vcs-modal-btn--secondary:hover { background: #F8FAFC; border-color: #94A3B8; }
    .vcs-modal-btn--danger {
        width: 100%;
        background: linear-gradient(135deg, #BE123C, #F43F5E);
        color: white;
        box-shadow: 0 8px 18px -6px rgba(244,63,94,0.50), inset 0 1px 0 rgba(255,255,255,0.30);
    }
    .vcs-modal-btn--danger:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 26px -8px rgba(244,63,94,0.60), inset 0 1px 0 rgba(255,255,255,0.30);
    }
    @media (max-width: 480px) {
        .vcs-modal { max-width: 100%; border-radius: 18px; }
        .vcs-modal-banner { padding: 18px 18px 16px; }
        .vcs-modal-icon { width: 42px; height: 42px; }
        .vcs-modal-title { font-size: 17px; }
        .vcs-modal-footer { flex-direction: column-reverse; }
    }
</style>
@endsection
