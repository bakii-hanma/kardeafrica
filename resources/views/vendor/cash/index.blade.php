@extends('vendor.layouts.vendor')

@section('title', 'À encaisser')

@section('content')
<div class="vc-wrap">

    {{-- ============= HEADER ============= --}}
    <div class="vc-head">
        <div>
            <div class="vc-eyebrow">Encaissements physiques</div>
            <h1 class="vc-title">À encaisser</h1>
            <p class="vc-sub">Le client te donne un code à 6 chiffres + paie cash. Tu valides ici, tes cartes lui sont envoyées automatiquement.</p>
        </div>
    </div>

    {{-- ============= SOLDE STRIP ============= --}}
    <div class="vc-balance-strip">
        <div class="vc-balance-cell">
            <div class="vc-balance-label">Solde dispo</div>
            <div class="vc-balance-num">{{ number_format($reseller->available_balance, 0, ',', ' ') }} <span>FCFA</span></div>
        </div>
        <div class="vc-balance-divider"></div>
        <div class="vc-balance-cell">
            <div class="vc-balance-label">Bloqué (cash en attente)</div>
            <div class="vc-balance-num vc-balance-num--locked">{{ number_format($reseller->wallet_locked, 0, ',', ' ') }} <span>FCFA</span></div>
        </div>
        <div class="vc-balance-divider"></div>
        <div class="vc-balance-cell">
            <div class="vc-balance-label">Wallet total</div>
            <div class="vc-balance-num">{{ number_format($reseller->wallet_balance, 0, ',', ' ') }} <span>FCFA</span></div>
        </div>
    </div>

    {{-- ============= EN ATTENTE ============= --}}
    <h2 class="vc-section-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        En attente <span class="vc-section-count">{{ $pending->count() }}</span>
    </h2>

    @if($pending->count() === 0)
        <div class="vc-empty">
            <div class="vc-empty-ico">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div class="vc-empty-title">Aucune commande à encaisser</div>
            <div class="vc-empty-text">Quand un client choisit « Payer en espèces » sur la boutique avec ton code vendeur, sa commande apparaît ici.</div>
        </div>
    @else
        <div class="vc-list">
            @foreach($pending as $order)
                @php
                    $expired = $order->cash_lock_expires_at && $order->cash_lock_expires_at->isPast();
                    $name = data_get($order->billing_details, 'name') ?: ($order->user->name ?? 'Client');
                    $phone = data_get($order->billing_details, 'phone') ?: ($order->user->phone ?? null);
                @endphp
                <a href="{{ route('vendor.cash.show', $order) }}" class="vc-item {{ $expired ? 'vc-item--expired' : '' }}">
                    <div class="vc-item-left">
                        <div class="vc-item-avatar">{{ strtoupper(substr($name, 0, 1)) }}</div>
                    </div>
                    <div class="vc-item-body">
                        <div class="vc-item-name">{{ $name }}</div>
                        @if($phone)
                            <div class="vc-item-phone">{{ $phone }}</div>
                        @endif
                        <div class="vc-item-meta">
                            <span>{{ $order->orderItems->sum('quantity') }} carte{{ $order->orderItems->sum('quantity') > 1 ? 's' : '' }}</span>
                            <span>·</span>
                            @if($expired)
                                <span class="vc-item-badge vc-item-badge--red">Expirée</span>
                            @else
                                <span class="vc-item-badge vc-item-badge--amber">Expire dans {{ $order->cash_lock_expires_at?->diffForHumans(['parts' => 1, 'short' => true]) }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="vc-item-right">
                        <div class="vc-item-amount">{{ number_format($order->total_amount, 0, ',', ' ') }} <span>FCFA</span></div>
                        <svg class="vc-item-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    {{-- ============= RÉCENTES ============= --}}
    @if($recent->count() > 0)
        <h2 class="vc-section-title" style="margin-top:24px;">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7"/></svg>
            Historique récent
        </h2>
        <div class="vc-list">
            @foreach($recent as $order)
                @php
                    $name = data_get($order->billing_details, 'name') ?: ($order->user->name ?? 'Client');
                    $isCompleted = $order->payment_status === \App\Models\Order::PAYMENT_STATUS_COMPLETED;
                @endphp
                <a href="{{ route('vendor.cash.show', $order) }}" class="vc-item vc-item--past">
                    <div class="vc-item-left">
                        <div class="vc-item-status-dot {{ $isCompleted ? 'vc-dot-green' : 'vc-dot-rose' }}"></div>
                    </div>
                    <div class="vc-item-body">
                        <div class="vc-item-name">{{ $name }}</div>
                        <div class="vc-item-meta">
                            <span class="vc-item-badge {{ $isCompleted ? 'vc-item-badge--green' : 'vc-item-badge--rose' }}">
                                {{ $isCompleted ? 'Encaissé' : 'Annulé' }}
                            </span>
                            <span>·</span>
                            <span>{{ $order->updated_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    <div class="vc-item-right">
                        <div class="vc-item-amount vc-item-amount--past">{{ number_format($order->total_amount, 0, ',', ' ') }} <span>FCFA</span></div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

</div>

<style>
    .vc-wrap { max-width: 760px; margin: 0 auto; padding-bottom: 100px; }

    .vc-head { margin-bottom: 14px; }
    .vc-eyebrow { font-family: 'Inter', sans-serif; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.10em; color: #B45309; }
    .vc-title { font-family: 'Space Grotesk','Inter',sans-serif; font-size: 26px; font-weight: 800; color: #0F172A; margin: 4px 0 6px; }
    .vc-sub { font-size: 13px; color: #64748B; max-width: 520px; }

    .vc-balance-strip {
        display: grid;
        grid-template-columns: 1fr 1px 1fr 1px 1fr;
        gap: 10px;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        padding: 14px 16px;
        margin-bottom: 18px;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }
    .vc-balance-cell { min-width: 0; }
    .vc-balance-divider { background: #E2E8F0; }
    .vc-balance-label { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #94A3B8; }
    .vc-balance-num {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 16px; font-weight: 800; color: #0F172A;
        margin-top: 2px; font-variant-numeric: tabular-nums;
    }
    .vc-balance-num span { font-size: 10px; font-weight: 600; color: #94A3B8; margin-left: 2px; }
    .vc-balance-num--locked { color: #B45309; }

    .vc-section-title {
        display: flex; align-items: center; gap: 8px;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 14px; font-weight: 700;
        color: #0F172A;
        margin: 0 0 10px;
    }
    .vc-section-title svg { width: 14px; height: 14px; color: #B45309; }
    .vc-section-count {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 22px; height: 22px; padding: 0 7px;
        border-radius: 9999px;
        background: #FEF3C7; color: #B45309;
        font-size: 11px; font-weight: 800;
        font-variant-numeric: tabular-nums;
    }

    .vc-list { display: flex; flex-direction: column; gap: 8px; }
    .vc-item {
        display: flex; align-items: center; gap: 12px;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        padding: 12px 14px;
        text-decoration: none;
        transition: all .2s ease;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }
    .vc-item:hover { border-color: #44A08D; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(15,23,42,0.08); }
    .vc-item--expired { opacity: 0.55; background: #FFF7ED; }
    .vc-item--past { background: #F8FAFC; }

    .vc-item-left { flex-shrink: 0; }
    .vc-item-avatar {
        width: 44px; height: 44px;
        border-radius: 12px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        display: flex; align-items: center; justify-content: center;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800; font-size: 18px;
    }
    .vc-item-status-dot { width: 8px; height: 8px; border-radius: 50%; margin: 0 18px; }
    .vc-dot-green { background: #10B981; }
    .vc-dot-rose  { background: #F43F5E; }

    .vc-item-body { flex: 1; min-width: 0; }
    .vc-item-name { font-size: 14px; font-weight: 700; color: #0F172A; }
    .vc-item-phone { font-size: 12px; color: #64748B; font-family: 'JetBrains Mono','Fira Code',monospace; margin-top: 1px; }
    .vc-item-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; font-size: 11px; color: #94A3B8; margin-top: 4px; }

    .vc-item-badge {
        display: inline-flex; align-items: center;
        font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em;
        padding: 2px 7px; border-radius: 6px;
    }
    .vc-item-badge--amber { background: #FEF3C7; color: #B45309; }
    .vc-item-badge--red   { background: #FEE2E2; color: #B91C1C; }
    .vc-item-badge--green { background: #D1FAE5; color: #047857; }
    .vc-item-badge--rose  { background: #FFE4E6; color: #BE123C; }

    .vc-item-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
    .vc-item-amount {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 16px; font-weight: 800; color: #0F172A;
        font-variant-numeric: tabular-nums;
        text-align: right;
    }
    .vc-item-amount span { font-size: 10px; font-weight: 600; color: #94A3B8; margin-left: 2px; }
    .vc-item-amount--past { color: #64748B; }
    .vc-item-arrow { width: 14px; height: 14px; color: #94A3B8; }

    .vc-empty {
        background: white;
        border: 2px dashed #E2E8F0;
        border-radius: 16px;
        padding: 32px 24px;
        text-align: center;
        color: #64748B;
    }
    .vc-empty-ico {
        width: 56px; height: 56px;
        border-radius: 16px;
        background: #ECFDF5;
        color: #10B981;
        display: inline-flex; align-items: center; justify-content: center;
        margin-bottom: 10px;
    }
    .vc-empty-ico svg { width: 28px; height: 28px; }
    .vc-empty-title { font-family: 'Space Grotesk','Inter',sans-serif; font-size: 15px; font-weight: 700; color: #0F172A; }
    .vc-empty-text { font-size: 12px; max-width: 420px; margin: 4px auto 0; line-height: 1.5; }

    @media (max-width: 540px) {
        .vc-balance-strip { grid-template-columns: 1fr; }
        .vc-balance-divider { display: none; }
    }
</style>
@endsection
