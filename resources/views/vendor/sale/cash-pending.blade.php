@extends('vendor.layouts.vendor')

@section('title', 'Encaissement cash en attente')

@section('content')
@php
    use App\Models\ResellerOrder;
    $expired = $order->expires_at && $order->expires_at->isPast();
@endphp
<div class="vcp-wrap">

    <a href="{{ route('vendor.sell') }}" class="vcp-back">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Retour à la boutique
    </a>

    {{-- ============= HEADER STATUS ============= --}}
    <div class="vcp-status">
        @if($expired)
            <div class="vcp-status-ico vcp-status-ico--expired">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h1 class="vcp-status-title">Délai expiré</h1>
                <p class="vcp-status-sub">Cette vente a été annulée automatiquement, ton solde a été libéré. Crée une nouvelle vente.</p>
            </div>
        @else
            <div class="vcp-status-ico">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
            </div>
            <div>
                <h1 class="vcp-status-title">Vente cash en attente</h1>
                <p class="vcp-status-sub">
                    <strong>{{ number_format($order->subtotal, 0, ',', ' ') }} FCFA</strong> ont été <strong>verrouillés</strong> sur ton solde.
                    Confirme dès que tu as reçu l'argent du client.
                </p>
            </div>
        @endif
    </div>

    {{-- ============= MONTANT À ENCAISSER ============= --}}
    <div class="vcp-amount-card">
        <div class="vcp-amount-label">Montant à encaisser cash</div>
        <div class="vcp-amount-value">{{ number_format($order->subtotal, 0, ',', ' ') }} <span>FCFA</span></div>
        @if(!$expired && $order->expires_at)
            <div class="vcp-deadline" x-data="vcpCountdown(@json($order->expires_at->toIso8601String()))" x-init="tick(); setInterval(tick, 1000)">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Expire dans <strong x-text="display"></strong>
            </div>
        @endif
    </div>

    {{-- ============= INFO CLIENT ============= --}}
    @if($order->customer_name || $order->customer_phone)
    <div class="vcp-card">
        <div class="vcp-card-eyebrow">Client</div>
        <div class="vcp-info-grid">
            @if($order->customer_name)
                <div class="vcp-info-row">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span class="vcp-info-label">Nom</span>
                    <span class="vcp-info-value">{{ $order->customer_name }}</span>
                </div>
            @endif
            @if($order->customer_phone)
                <div class="vcp-info-row">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    <span class="vcp-info-label">Téléphone</span>
                    <a href="tel:{{ $order->customer_phone }}" class="vcp-info-value vcp-link">{{ $order->customer_phone }}</a>
                </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ============= ITEMS ============= --}}
    <div class="vcp-card">
        <div class="vcp-card-eyebrow">{{ $order->items->sum('quantity') }} carte{{ $order->items->sum('quantity') > 1 ? 's' : '' }}</div>
        <div class="vcp-items">
            @foreach($order->items as $item)
                <div class="vcp-item">
                    @if(!empty($item->image_url))
                        <img src="{{ $item->image_url }}" alt="" class="vcp-item-img">
                    @else
                        <div class="vcp-item-img-fallback" style="background:{{ $item->color ?? '#44A08D' }};">
                            {{ strtoupper(substr($item->brand ?? $item->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="vcp-item-body">
                        <div class="vcp-item-brand">{{ $item->brand ?? '' }}</div>
                        <div class="vcp-item-name">{{ $item->name }}</div>
                    </div>
                    <div class="vcp-item-total">
                        {{ number_format($item->total_price, 0, ',', ' ') }}<span>×{{ $item->quantity }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ============= ACTIONS ============= --}}
    @if(!$expired && $order->payment_status === ResellerOrder::PAYMENT_PENDING)
    <div class="vcp-actions" x-data="{ modal: null }">
        <button type="button" @click="modal = 'confirm'" class="vcp-btn vcp-btn--primary">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            J'ai reçu l'argent — livrer les cartes
        </button>

        <button type="button" @click="modal = 'cancel'" class="vcp-btn vcp-btn--ghost">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            Annuler — libérer le solde
        </button>

        {{-- ===== Modal CONFIRMATION encaissement ===== --}}
        <div x-show="modal !== null" x-cloak
             class="vcp-modal-backdrop"
             @click.self="modal = null"
             @keydown.escape.window="modal = null"
             x-transition.opacity>

            {{-- Modal : confirmer --}}
            <div x-show="modal === 'confirm'" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="vcp-modal vcp-modal--confirm">
                {{-- Bandeau gradient gold --}}
                <div class="vcp-modal-banner">
                    <div class="vcp-modal-banner-glow"></div>
                    <div class="vcp-modal-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
                    </div>
                    <div class="vcp-modal-banner-text">
                        <div class="vcp-modal-eyebrow">Encaissement cash</div>
                        <div class="vcp-modal-title">Confirmer la réception</div>
                    </div>
                </div>

                <div class="vcp-modal-body">
                    <p class="vcp-modal-question">As-tu bien reçu <strong>en main</strong> du client&nbsp;?</p>
                    <div class="vcp-modal-amount">
                        <span class="vcp-modal-amount-value">{{ number_format($order->subtotal, 0, ',', ' ') }}</span>
                        <span class="vcp-modal-amount-unit">FCFA</span>
                    </div>
                    @if($order->customer_name)
                        <div class="vcp-modal-meta">de <strong>{{ $order->customer_name }}</strong></div>
                    @endif

                    {{-- Conséquences listées --}}
                    <ul class="vcp-modal-list">
                        <li>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <span>Ton solde de vente sera <strong>débité de {{ number_format($order->subtotal, 0, ',', ' ') }} FCFA</strong></span>
                        </li>
                        <li>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <span>Ta commission de <strong>+{{ number_format($order->commission_earned, 0, ',', ' ') }} FCFA</strong> sera créditée</span>
                        </li>
                        <li>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <span>Les <strong>{{ $order->items->sum('quantity') }} carte{{ $order->items->sum('quantity') > 1 ? 's' : '' }}</strong> sero{{ $order->items->sum('quantity') > 1 ? 'nt' : 'nt' }} livrée{{ $order->items->sum('quantity') > 1 ? 's' : '' }} immédiatement</span>
                        </li>
                    </ul>
                </div>

                <div class="vcp-modal-footer">
                    <button type="button" @click="modal = null" class="vcp-modal-btn vcp-modal-btn--secondary">
                        Pas encore
                    </button>
                    <form method="POST" action="{{ route('vendor.sell.cash.confirm', $order) }}" style="margin:0;flex:1;">
                        @csrf
                        <button type="submit" class="vcp-modal-btn vcp-modal-btn--primary">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Oui, j'ai reçu l'argent
                        </button>
                    </form>
                </div>
            </div>

            {{-- Modal : annuler --}}
            <div x-show="modal === 'cancel'" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="vcp-modal vcp-modal--cancel">
                <div class="vcp-modal-banner vcp-modal-banner--rose">
                    <div class="vcp-modal-banner-glow"></div>
                    <div class="vcp-modal-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div class="vcp-modal-banner-text">
                        <div class="vcp-modal-eyebrow">Annulation</div>
                        <div class="vcp-modal-title">Annuler cette vente&nbsp;?</div>
                    </div>
                </div>

                <div class="vcp-modal-body">
                    <p class="vcp-modal-question">
                        Le verrou de <strong>{{ number_format($order->subtotal, 0, ',', ' ') }} FCFA</strong> sera libéré sur ton solde de vente. Cette action est définitive.
                    </p>
                </div>

                <div class="vcp-modal-footer">
                    <button type="button" @click="modal = null" class="vcp-modal-btn vcp-modal-btn--secondary">
                        Garder la vente
                    </button>
                    <form method="POST" action="{{ route('vendor.sell.cash.cancel', $order) }}" style="margin:0;flex:1;">
                        @csrf
                        <button type="submit" class="vcp-modal-btn vcp-modal-btn--danger">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            Oui, annuler
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Sécurité info --}}
    <div class="vcp-info-box">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        <div>
            <strong>Comment ça fonctionne</strong>
            <p>Tant que tu n'as pas confirmé, ton solde reste verrouillé pour cette commande mais N'EST PAS débité. Tu peux annuler à tout moment. Si tu n'agis pas dans les 30 minutes, la commande s'annule automatiquement et ton solde est libéré.</p>
        </div>
    </div>
    @elseif($expired)
        <a href="{{ route('vendor.sell') }}" class="vcp-btn vcp-btn--primary" style="display:inline-flex;text-decoration:none;">
            Créer une nouvelle vente
        </a>
    @endif

</div>

<style>
    .vcp-wrap { max-width: 720px; margin: 0 auto; padding-bottom: 100px; }

    .vcp-back {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 12px; color: #64748B; text-decoration: none; margin-bottom: 12px;
    }
    .vcp-back:hover { color: #0F172A; }
    .vcp-back svg { width: 14px; height: 14px; }

    .vcp-status {
        display: flex; align-items: flex-start; gap: 14px;
        background: linear-gradient(135deg, #F59E0B, #FBBF24);
        border-radius: 16px;
        padding: 18px 20px;
        color: white;
        margin-bottom: 14px;
        box-shadow: 0 12px 28px -10px rgba(217,119,6,0.45);
    }
    .vcp-status-ico {
        width: 44px; height: 44px;
        border-radius: 12px;
        background: rgba(255,255,255,0.20);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .vcp-status-ico--expired { background: rgba(0,0,0,0.20); }
    .vcp-status-ico svg { width: 22px; height: 22px; }
    .vcp-status-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 18px; font-weight: 800;
        margin: 2px 0 4px;
    }
    .vcp-status-sub { font-size: 12px; opacity: 0.95; line-height: 1.5; }
    .vcp-status-sub strong { font-weight: 800; }

    .vcp-amount-card {
        background: white;
        border: 2px dashed #FCD34D;
        border-radius: 16px;
        padding: 22px 18px;
        text-align: center;
        margin-bottom: 14px;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }
    .vcp-amount-label {
        font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.10em;
        color: #B45309;
    }
    .vcp-amount-value {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 38px; font-weight: 800;
        color: #0F172A;
        margin-top: 4px;
        font-variant-numeric: tabular-nums;
        line-height: 1.1;
    }
    .vcp-amount-value span { font-size: 14px; color: #94A3B8; margin-left: 4px; font-weight: 600; }
    .vcp-deadline {
        display: inline-flex; align-items: center; gap: 5px;
        margin-top: 8px;
        font-size: 11px; color: #B45309;
        font-variant-numeric: tabular-nums;
    }
    .vcp-deadline svg { width: 12px; height: 12px; }
    .vcp-deadline strong { font-weight: 800; }

    .vcp-card {
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        padding: 14px 16px;
        margin-bottom: 10px;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }
    .vcp-card-eyebrow {
        font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em;
        color: #94A3B8; margin-bottom: 8px;
    }

    .vcp-info-grid { display: flex; flex-direction: column; gap: 6px; }
    .vcp-info-row {
        display: grid;
        grid-template-columns: 14px 80px 1fr;
        align-items: center;
        gap: 10px;
        padding: 6px 8px;
        border-radius: 8px;
        background: #F8FAFC;
    }
    .vcp-info-row svg { width: 12px; height: 12px; color: #94A3B8; }
    .vcp-info-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #94A3B8; }
    .vcp-info-value { font-size: 13px; font-weight: 600; color: #0F172A; }
    .vcp-link { color: #44A08D; text-decoration: none; }
    .vcp-link:hover { text-decoration: underline; }

    .vcp-items { display: flex; flex-direction: column; gap: 6px; }
    .vcp-item {
        display: flex; align-items: center; gap: 10px;
        padding: 8px 10px; border-radius: 10px;
        background: #F8FAFC;
    }
    .vcp-item-img, .vcp-item-img-fallback {
        width: 36px; height: 36px;
        border-radius: 8px;
        flex-shrink: 0;
        object-fit: contain;
        background: white;
        border: 1px solid #E2E8F0;
    }
    .vcp-item-img-fallback {
        display: flex; align-items: center; justify-content: center;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800; color: white;
        border: none;
    }
    .vcp-item-body { flex: 1; min-width: 0; }
    .vcp-item-brand { font-size: 10px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.06em; }
    .vcp-item-name { font-size: 13px; font-weight: 600; color: #0F172A; }
    .vcp-item-total {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 14px; font-weight: 800;
        color: #0F172A;
        font-variant-numeric: tabular-nums;
        flex-shrink: 0;
        text-align: right;
    }
    .vcp-item-total span { display: block; font-size: 9px; color: #94A3B8; font-weight: 600; }

    .vcp-actions {
        display: flex; flex-direction: column; gap: 8px;
        margin-top: 14px;
    }
    .vcp-actions form { margin: 0; }

    .vcp-btn {
        width: 100%;
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        padding: 14px 18px;
        border-radius: 12px;
        font-family: 'Inter', sans-serif;
        font-size: 14px; font-weight: 700;
        cursor: pointer;
        border: 1px solid transparent;
        transition: all .2s ease;
    }
    .vcp-btn svg { width: 16px; height: 16px; }
    .vcp-btn--primary {
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        box-shadow: 0 10px 24px -6px rgba(68,160,141,0.45),
                    inset 0 1px 0 rgba(255,255,255,0.30);
    }
    .vcp-btn--primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 14px 32px -8px rgba(68,160,141,0.55), inset 0 1px 0 rgba(255,255,255,0.30);
    }
    .vcp-btn--ghost {
        background: white;
        color: #BE123C;
        border-color: #FECACA;
    }
    .vcp-btn--ghost:hover { background: #FEF2F2; border-color: #FCA5A5; }

    .vcp-info-box {
        display: flex; gap: 10px; align-items: flex-start;
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        padding: 12px 14px;
        margin-top: 12px;
    }
    .vcp-info-box svg { width: 16px; height: 16px; color: #44A08D; flex-shrink: 0; margin-top: 2px; }
    .vcp-info-box strong { font-size: 12px; font-weight: 700; color: #0F172A; display: block; margin-bottom: 2px; }
    .vcp-info-box p { font-size: 11px; color: #64748B; line-height: 1.5; margin: 0; }

    /* ====================== MODAL CONFIRMATION ====================== */
    .vcp-modal-backdrop {
        position: fixed; inset: 0;
        z-index: 100;
        background: rgba(15, 23, 42, 0.55);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        display: flex; align-items: center; justify-content: center;
        padding: 16px;
    }
    .vcp-modal {
        position: relative;
        width: 100%;
        max-width: 440px;
        background: white;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 28px 60px -16px rgba(15, 23, 42, 0.50),
                    0 6px 18px -6px rgba(15, 23, 42, 0.30);
    }

    .vcp-modal-banner {
        position: relative;
        padding: 22px 22px 18px;
        background: linear-gradient(135deg, #F59E0B 0%, #FBBF24 60%, #FDE68A 100%);
        color: white;
        display: flex; align-items: center; gap: 14px;
        overflow: hidden;
    }
    .vcp-modal-banner--rose {
        background: linear-gradient(135deg, #BE123C 0%, #F43F5E 60%, #FB7185 100%);
    }
    .vcp-modal-banner-glow {
        position: absolute;
        top: -60px; right: -60px;
        width: 220px; height: 220px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255,255,255,0.30) 0%, transparent 70%);
        pointer-events: none;
    }
    .vcp-modal-icon {
        position: relative;
        width: 48px; height: 48px;
        border-radius: 14px;
        background: rgba(255,255,255,0.22);
        backdrop-filter: blur(6px);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.40);
    }
    .vcp-modal-icon svg { width: 22px; height: 22px; }
    .vcp-modal-banner-text { position: relative; min-width: 0; }
    .vcp-modal-eyebrow {
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.10em;
        opacity: 0.92;
    }
    .vcp-modal-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 19px; font-weight: 800;
        line-height: 1.15;
        margin-top: 2px;
        letter-spacing: -0.01em;
    }

    .vcp-modal-body {
        padding: 20px 22px 18px;
        text-align: center;
    }
    .vcp-modal-question {
        font-size: 14px;
        color: #475569;
        line-height: 1.5;
        margin: 0 0 14px;
    }
    .vcp-modal-question strong { color: #0F172A; font-weight: 800; }

    .vcp-modal-amount {
        display: inline-flex; align-items: baseline; gap: 6px;
        padding: 10px 18px;
        border-radius: 14px;
        background: linear-gradient(135deg, #FEF3C7, #FFFBEB);
        border: 2px dashed #FCD34D;
    }
    .vcp-modal-amount-value {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 32px; font-weight: 800;
        color: #0F172A;
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.02em;
        line-height: 1;
    }
    .vcp-modal-amount-unit {
        font-size: 12px; font-weight: 700;
        color: #B45309;
        letter-spacing: 0.05em;
    }
    .vcp-modal-meta {
        font-size: 12px; color: #64748B;
        margin-top: 10px;
    }
    .vcp-modal-meta strong { color: #0F172A; font-weight: 700; }

    .vcp-modal-list {
        list-style: none;
        padding: 0;
        margin: 16px 0 0;
        text-align: left;
        display: flex; flex-direction: column; gap: 8px;
    }
    .vcp-modal-list li {
        display: flex; align-items: flex-start; gap: 10px;
        padding: 10px 12px;
        background: #F8FAFC;
        border-radius: 10px;
        font-size: 12px;
        color: #475569;
        line-height: 1.4;
    }
    .vcp-modal-list svg {
        width: 14px; height: 14px;
        color: #44A08D;
        flex-shrink: 0;
        margin-top: 1px;
    }
    .vcp-modal-list strong { color: #0F172A; font-weight: 700; }

    .vcp-modal-footer {
        display: flex; gap: 8px;
        padding: 14px 18px 18px;
        background: linear-gradient(180deg, white, #F8FAFC);
    }
    .vcp-modal-btn {
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
    .vcp-modal-btn svg { width: 14px; height: 14px; }
    .vcp-modal-btn--secondary {
        background: white;
        border-color: #E2E8F0;
        color: #475569;
    }
    .vcp-modal-btn--secondary:hover {
        background: #F8FAFC;
        border-color: #94A3B8;
    }
    .vcp-modal-btn--primary {
        width: 100%;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        box-shadow: 0 8px 18px -6px rgba(68,160,141,0.50),
                    inset 0 1px 0 rgba(255,255,255,0.30);
    }
    .vcp-modal-btn--primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 26px -8px rgba(68,160,141,0.60), inset 0 1px 0 rgba(255,255,255,0.30);
    }
    .vcp-modal-btn--danger {
        width: 100%;
        background: linear-gradient(135deg, #BE123C, #F43F5E);
        color: white;
        box-shadow: 0 8px 18px -6px rgba(244,63,94,0.50),
                    inset 0 1px 0 rgba(255,255,255,0.30);
    }
    .vcp-modal-btn--danger:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 26px -8px rgba(244,63,94,0.60), inset 0 1px 0 rgba(255,255,255,0.30);
    }

    @media (max-width: 480px) {
        .vcp-modal { max-width: 100%; border-radius: 18px; }
        .vcp-modal-banner { padding: 18px 18px 16px; }
        .vcp-modal-icon { width: 42px; height: 42px; }
        .vcp-modal-title { font-size: 17px; }
        .vcp-modal-amount-value { font-size: 28px; }
        .vcp-modal-footer { flex-direction: column-reverse; }
        .vcp-modal-btn--secondary { padding: 10px 14px; }
    }
</style>

<script>
window.vcpCountdown = function (deadlineISO) {
    return {
        display: '',
        tick() {
            const ms = (new Date(deadlineISO)).getTime() - Date.now();
            if (ms <= 0) { this.display = 'expirée'; return; }
            const m = Math.floor(ms / 60000);
            const s = Math.floor((ms % 60000) / 1000);
            this.display = `${m}m ${String(s).padStart(2,'0')}s`;
        },
    };
};
</script>
@endsection
