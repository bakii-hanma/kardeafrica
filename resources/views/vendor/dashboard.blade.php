@extends('vendor.layouts.vendor')

@section('title', 'Tableau de bord')

@php
    $brandPalette = [
        'Netflix' => '#E50914', 'Spotify' => '#1DB954', 'Apple' => '#000000',
        'iTunes' => '#D60017', 'PlayStation' => '#003791', 'Xbox' => '#107C10',
        'Amazon' => '#FF9900', 'Google' => '#01875F', 'Steam' => '#171A21',
        'Roblox' => '#00A2FF', 'Nintendo' => '#E60012', 'Disney' => '#0E47A1',
        'Daywatch' => '#44A08D',
    ];
    $brandColorFor = function ($name) use ($brandPalette) {
        if (!$name) return '#1F2937';
        foreach ($brandPalette as $k => $c) if (stripos($name, $k) !== false) return $c;
        $palette = ['#0F172A', '#44A08D', '#0EA5E9', '#7C3AED', '#DC2626', '#EA580C', '#059669'];
        $hash = 0;
        for ($i = 0; $i < strlen($name); $i++) $hash = ord($name[$i]) + (($hash << 5) - $hash);
        return $palette[(($hash % count($palette)) + count($palette)) % count($palette)];
    };
@endphp

@section('content')
<div class="vd-wrap">

    {{-- ============= WELCOME STRIP (admin-style) ============= --}}
    <div class="vd-welcome">
        <div class="vd-welcome-glow"></div>
        <div class="vd-welcome-body">
            <div class="vd-welcome-eyebrow">Bonjour, {{ explode(' ', $reseller->name)[0] }}</div>
            <h2 class="vd-welcome-title">Vue d'ensemble · {{ now()->translatedFormat('d F Y') }}</h2>
        </div>
        <div class="vd-welcome-actions">
            <a href="{{ route('vendor.orders') }}" class="vd-welcome-btn vd-welcome-btn--ghost">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/></svg>
                Mes ventes
            </a>
            <a href="{{ route('vendor.sell') }}" class="vd-welcome-btn vd-welcome-btn--brand">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Nouvelle vente
            </a>
        </div>
    </div>

    {{-- ============= STATS GRID (admin-style cards) ============= --}}
    <div class="vd-stats-row">
        {{-- Carte solde — deux portefeuilles séparés (vente + commission) --}}
        <div class="vd-stat vd-stat--brand vd-stat--dual">
            <div class="vd-dual">
                <div class="vd-dual-side">
                    <div class="vd-dual-label">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        Solde de vente
                    </div>
                    <div class="vd-dual-value">{{ number_format($reseller->wallet_balance, 0, ',', ' ') }} <span class="vd-dual-unit">FCFA</span></div>
                    <div class="vd-dual-meta">sur {{ number_format($reseller->max_wallet, 0, ',', ' ') }} dispo</div>
                    <div class="vd-stat-bar"><div class="vd-stat-bar-fill" style="width:{{ min(100, $reseller->wallet_percentage) }}%;"></div></div>
                </div>
                <div class="vd-dual-divider"></div>
                <div class="vd-dual-side">
                    <div class="vd-dual-label vd-dual-label--accent">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        Mes commissions
                    </div>
                    <div class="vd-dual-value">{{ number_format($reseller->commission_balance, 0, ',', ' ') }} <span class="vd-dual-unit">FCFA</span></div>
                    <div class="vd-dual-meta">{{ rtrim(rtrim(number_format($reseller->commission_rate, 2), '0'), '.') }}% par vente · cumul {{ number_format($reseller->total_commission_earned, 0, ',', ' ') }} FCFA</div>
                </div>
            </div>
        </div>

        {{-- Volume jour — palette unifiée (icône slate neutre) --}}
        <div class="vd-stat">
            <div class="vd-stat-head">
                <span class="vd-stat-label">Volume du jour</span>
                <div class="vd-stat-icon" style="background:#F1F5F9;color:#475569;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
            </div>
            <div class="vd-stat-value">
                {{ number_format($stats['volume_today'], 0, ',', ' ') }}
                <span class="vd-stat-unit">FCFA</span>
            </div>
            <div class="vd-stat-meta">{{ $stats['orders_today'] }} vente{{ $stats['orders_today'] > 1 ? 's' : '' }} aujourd'hui</div>
        </div>

        {{-- Total ventes — même palette neutre --}}
        <div class="vd-stat">
            <div class="vd-stat-head">
                <span class="vd-stat-label">Total ventes</span>
                <div class="vd-stat-icon" style="background:#F1F5F9;color:#475569;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/></svg>
                </div>
            </div>
            <div class="vd-stat-value">{{ $stats['orders_total'] }}</div>
            <div class="vd-stat-meta">{{ $stats['orders_pending'] }} en attente</div>
        </div>
    </div>

    {{-- ============= ROW 2 : Volume cumulé + En attente + CTA dark ============= --}}
    {{-- Bandeau urgent : cash à reverser --}}
    @if((float) $reseller->cash_to_remit > 0)
        <a href="{{ route('vendor.remittance.index') }}" class="vd-cashremit-banner">
            <div class="vd-cashremit-glow"></div>
            <div class="vd-cashremit-l">
                <div class="vd-cashremit-ico">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <div class="vd-cashremit-eyebrow">Cash à remettre à KardAfrica</div>
                    <div class="vd-cashremit-amount">{{ number_format($reseller->cash_to_remit, 0, ',', ' ') }} <span>FCFA</span></div>
                    <div class="vd-cashremit-sub">Reverse via E-Billing pour reconstituer ton solde de vente</div>
                </div>
            </div>
            <span class="vd-cashremit-cta">
                Remettre maintenant
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </span>
        </a>
    @endif

    <div class="vd-stats-row vd-stats-row--3">
        <div class="vd-stat">
            <div class="vd-stat-flex">
                <div>
                    <span class="vd-stat-label">Volume cumulé</span>
                    <div class="vd-stat-value vd-stat-value--lg">{{ number_format($stats['volume_total'], 0, ',', ' ') }} <span class="vd-stat-unit">FCFA</span></div>
                    <div class="vd-stat-meta">depuis l'inscription</div>
                </div>
                <div class="vd-stat-icon vd-stat-icon--lg" style="background:#ECFDF5;color:#059669;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                </div>
            </div>
        </div>

        <div class="vd-stat {{ $stats['orders_pending'] > 0 ? 'vd-stat--warn' : '' }}">
            <div class="vd-stat-flex">
                <div>
                    <span class="vd-stat-label">En livraison</span>
                    <div class="vd-stat-value vd-stat-value--lg" style="{{ $stats['orders_pending'] > 0 ? 'color:#D97706;' : '' }}">{{ $stats['orders_pending'] }}</div>
                    <div class="vd-stat-meta">commandes en cours</div>
                </div>
                <div class="vd-stat-icon vd-stat-icon--lg" style="background:{{ $stats['orders_pending'] > 0 ? '#FEF3C7' : '#F1F5F9' }};color:{{ $stats['orders_pending'] > 0 ? '#D97706' : '#94A3B8' }};">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>

        <a href="{{ route('vendor.sell') }}" class="vd-stat vd-stat--cta">
            <div class="vd-stat-cta-glow"></div>
            <div class="vd-stat-flex">
                <div style="position:relative;">
                    <span class="vd-stat-label" style="color:#94A3B8;">Démarrer</span>
                    <div class="vd-stat-value" style="color:white;font-size:18px;line-height:1.2;margin-top:6px;">une nouvelle vente</div>
                </div>
                <svg style="width:24px;height:24px;color:#5EEAD4;position:relative;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </div>
        </a>
    </div>

    {{-- ============= CARTES RÉCEMMENT VENDUES (admin-style gift card grid) ============= --}}
    @php
        $recentCards = $reseller->orders()
            ->where('status', 'completed')
            ->with(['cards' => fn($q) => $q->latest()->limit(2)])
            ->latest()
            ->limit(4)
            ->get()
            ->flatMap->cards
            ->take(8);
    @endphp
    @if($recentCards->count() > 0)
        <section class="vd-section">
            <div class="vd-section-head">
                <h2 class="vd-section-title">
                    <span class="vd-section-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </span>
                    Cartes récemment vendues
                </h2>
                <a href="{{ route('vendor.orders') }}" class="vd-section-link">
                    Voir toutes
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>

            {{-- Palette unifiée : une seule couleur (dark slate + accent teal) pour
                 TOUTES les cartes au lieu d'un arc-en-ciel d'une couleur par marque. --}}
            <div class="vd-cards-grid">
                @foreach($recentCards as $card)
                    @php
                        $brand = $card->brand ?? $card->name ?? 'Carte';
                    @endphp
                    <a href="{{ route('vendor.orders.show', $card->reseller_order_id) }}" class="vd-gcard">
                        <div class="vd-gcard-visual" style="background:linear-gradient(135deg,#0F172A 0%,#1E293B 60%,#0F4F44 100%);">
                            <div class="vd-gcard-glow" style="background:radial-gradient(circle at 20% 20%, rgba(94,234,212,0.18), transparent 60%);"></div>
                            <div class="vd-gcard-visual-body">
                                <div class="vd-gcard-top">
                                    <div>
                                        <div class="vd-gcard-tag" style="color:rgba(255,255,255,0.65);">Gift Card</div>
                                        <div class="vd-gcard-brand">{{ explode(' ', $brand)[0] }}</div>
                                    </div>
                                    <span class="vd-gcard-status" style="background:rgba(94,234,212,0.18);color:#5EEAD4;border:1px solid rgba(94,234,212,0.30);">
                                        <span class="vd-gcard-status-dot" style="background:#5EEAD4;"></span>Vendue
                                    </span>
                                </div>
                                <div class="vd-gcard-price">
                                    {{ number_format($card->face_value, 0, ',', ' ') }} <span>FCFA</span>
                                </div>
                            </div>
                        </div>
                        <div class="vd-gcard-foot">
                            <div class="vd-gcard-num">#{{ $card->serial_number ?: substr($card->card_code, 0, 8) }}</div>
                            <div class="vd-gcard-when">{{ $card->created_at->diffForHumans() }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ============= COMMANDES RÉCENTES (table desktop / cards mobile) ============= --}}
    <section class="vd-section">
        <div class="vd-section-head">
            <h2 class="vd-section-title">
                <span class="vd-section-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </span>
                Commandes récentes
            </h2>
            @if($recentOrders->count() > 0)
                <a href="{{ route('vendor.orders') }}" class="vd-section-link">
                    Voir tout
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            @endif
        </div>

        @if($recentOrders->count() > 0)
            {{-- Desktop : table --}}
            <div class="vd-table-wrap">
                <table class="vd-table">
                    <thead>
                        <tr>
                            <th>Commande</th>
                            <th>Client</th>
                            <th>Total</th>
                            <th>Commission</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $o)
                            @php
                                $statusMap = [
                                    'pending'    => ['#B45309','#FEF3C7','En attente'],
                                    'processing' => ['#1D4ED8','#DBEAFE','En cours'],
                                    'completed'  => ['#047857','#D1FAE5','Livrée'],
                                    'cancelled'  => ['#475569','#E2E8F0','Annulée'],
                                    'failed'     => ['#BE123C','#FEE2E2','Échec'],
                                    'refunded'   => ['#7C3AED','#EDE9FE','Remboursée'],
                                ];
                                $st = $statusMap[$o->status] ?? ['#475569','#E2E8F0',ucfirst($o->status)];
                            @endphp
                            <tr onclick="window.location='{{ route('vendor.orders.show', $o) }}'">
                                <td><span class="vd-table-num">#{{ $o->order_number }}</span></td>
                                <td>{{ $o->customer_name ?: 'Client' }}</td>
                                <td class="vd-table-amount">{{ number_format($o->total_amount, 0, ',', ' ') }} <span>FCFA</span></td>
                                <td class="vd-table-comm">+{{ number_format($o->commission_earned, 0, ',', ' ') }}</td>
                                <td><span class="vd-status-pill" style="background:{{ $st[1] }};color:{{ $st[0] }};">{{ $st[2] }}</span></td>
                                <td class="vd-table-date">{{ $o->created_at->format('d/m H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile : cards list --}}
            <div class="vd-mobile-orders">
                @foreach($recentOrders as $o)
                    @php
                        $statusMap = [
                            'pending'    => ['#B45309','#FEF3C7','En attente'],
                            'processing' => ['#1D4ED8','#DBEAFE','En cours'],
                            'completed'  => ['#047857','#D1FAE5','Livrée'],
                            'cancelled'  => ['#475569','#E2E8F0','Annulée'],
                            'failed'     => ['#BE123C','#FEE2E2','Échec'],
                            'refunded'   => ['#7C3AED','#EDE9FE','Remboursée'],
                        ];
                        $st = $statusMap[$o->status] ?? ['#475569','#E2E8F0',ucfirst($o->status)];
                    @endphp
                    <a href="{{ route('vendor.orders.show', $o) }}" class="vd-mob-order">
                        <div class="vd-mob-order-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                        <div class="vd-mob-order-body">
                            <div class="vd-mob-order-line1">
                                <span class="vd-mob-order-customer">{{ $o->customer_name ?: 'Client' }}</span>
                                <span class="vd-status-pill" style="background:{{ $st[1] }};color:{{ $st[0] }};">{{ $st[2] }}</span>
                            </div>
                            <div class="vd-mob-order-line2">
                                <span class="vd-mob-order-num">#{{ $o->order_number }}</span>
                                <span>·</span>
                                <span>{{ $o->items->count() }} art.</span>
                                <span>·</span>
                                <span>{{ $o->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        <div class="vd-mob-order-amount">
                            <div class="vd-mob-order-total">{{ number_format($o->total_amount, 0, ',', ' ') }}</div>
                            <div class="vd-mob-order-comm">+{{ number_format($o->commission_earned, 0, ',', ' ') }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="vd-empty">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                <h3>Aucune commande pour le moment</h3>
                <p>Démarre ta première vente pour voir l'activité ici.</p>
                <a href="{{ route('vendor.sell') }}" class="vd-empty-cta">
                    Commencer une vente
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
            </div>
        @endif
    </section>
</div>

@push('head')
<style>
    .vd-wrap { max-width: 1200px; margin: 0 auto; }

    /* ====================== WELCOME STRIP ====================== */
    .vd-welcome {
        background: linear-gradient(135deg, #1F2937 0%, #0F172A 100%);
        border-radius: 16px;
        padding: 18px 20px;
        margin-bottom: 18px;
        color: white;
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 14px;
        position: relative; overflow: hidden;
    }
    .vd-welcome-glow {
        position: absolute; top: -40px; right: -40px;
        width: 200px; height: 200px;
        border-radius: 50%;
        background: rgba(78,205,196,0.15);
        filter: blur(40px);
        pointer-events: none;
    }
    .vd-welcome-body { position: relative; flex: 1; min-width: 200px; }
    .vd-welcome-eyebrow {
        font-size: 10px; text-transform: uppercase; letter-spacing: 0.10em;
        color: #94A3B8; font-weight: 700;
    }
    .vd-welcome-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 18px; font-weight: 800;
        letter-spacing: -0.01em; margin: 4px 0 0;
        line-height: 1.2;
    }
    .vd-welcome-actions {
        position: relative;
        display: flex; align-items: center; gap: 8px;
    }
    .vd-welcome-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 14px;
        border-radius: 10px;
        text-decoration: none;
        font-family: 'Inter', sans-serif;
        font-size: 12px; font-weight: 700;
        line-height: 1;
        transition: all .15s ease;
    }
    .vd-welcome-btn svg { width: 13px; height: 13px; }
    .vd-welcome-btn--ghost {
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.12);
        color: white;
    }
    .vd-welcome-btn--ghost:hover { background: rgba(255,255,255,0.14); }
    .vd-welcome-btn--brand {
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        font-weight: 800;
        box-shadow: 0 8px 18px -6px rgba(78,205,196,0.55),
                    inset 0 1px 0 rgba(255,255,255,0.30);
    }
    .vd-welcome-btn--brand:hover { transform: translateY(-1px); }

    @media (min-width: 480px) {
        .vd-welcome { padding: 22px 24px; }
        .vd-welcome-title { font-size: 22px; }
    }

    /* ====================== STATS GRID ====================== */
    .vd-stats-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 16px;
    }
    @media (min-width: 768px) {
        .vd-stats-row {
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }
    }
    .vd-stats-row--3 {
        grid-template-columns: 1fr;
    }
    @media (min-width: 540px) {
        .vd-stats-row--3 { grid-template-columns: 1fr 1fr; }
    }
    @media (min-width: 768px) {
        .vd-stats-row--3 { grid-template-columns: 1fr 1fr 1fr; }
    }

    .vd-stat {
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        padding: 14px;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
        transition: all .25s ease;
        text-decoration: none; color: inherit;
        position: relative; overflow: hidden;
    }
    .vd-stat:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(15,23,42,0.10); }

    .vd-stat--brand {
        background: linear-gradient(135deg, #44A08D 0%, #4ECDC4 100%);
        border-color: transparent;
        color: white;
        box-shadow: 0 12px 28px -10px rgba(68,160,141,0.45),
                    inset 0 1px 0 rgba(255,255,255,0.30);
    }
    .vd-stat--warn { border-left: 3px solid #F59E0B; }

    /* Dual-balance card : Solde de vente + Mes commissions */
    .vd-stat--dual { grid-column: 1 / -1; padding: 16px; }
    @media (min-width: 768px) { .vd-stat--dual { grid-column: span 2; } }
    .vd-dual {
        display: grid;
        grid-template-columns: 1fr;
        gap: 14px;
    }
    @media (min-width: 480px) {
        .vd-dual {
            grid-template-columns: 1fr 1px 1fr;
            gap: 16px;
            align-items: stretch;
        }
    }
    .vd-dual-side { min-width: 0; display: flex; flex-direction: column; gap: 6px; }
    .vd-dual-divider {
        background: rgba(255,255,255,0.25);
        border-radius: 1px;
        display: none;
    }
    @media (min-width: 480px) { .vd-dual-divider { display: block; } }
    .vd-dual-label {
        display: inline-flex; align-items: center; gap: 6px;
        font-family: 'Inter', sans-serif;
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.08em;
        color: rgba(255,255,255,0.85);
    }
    .vd-dual-label svg { width: 12px; height: 12px; }
    .vd-dual-label--accent { color: #FFEDB0; }
    .vd-dual-value {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 24px; font-weight: 800;
        color: white;
        line-height: 1.05;
        letter-spacing: -0.01em;
        font-variant-numeric: tabular-nums;
    }
    .vd-dual-unit { font-size: 12px; font-weight: 700; opacity: 0.85; }
    .vd-dual-meta {
        font-size: 11px; color: rgba(255,255,255,0.78);
        font-variant-numeric: tabular-nums;
    }

    /* Bandeau cash à remettre */
    .vd-cashremit-banner {
        position: relative;
        display: flex; align-items: center; justify-content: space-between;
        gap: 14px;
        background: linear-gradient(135deg, #BE123C 0%, #F43F5E 60%, #FB7185 100%);
        border-radius: 16px;
        padding: 16px 18px;
        color: white;
        margin-bottom: 14px;
        text-decoration: none;
        overflow: hidden;
        box-shadow: 0 16px 32px -12px rgba(244,63,94,0.50),
                    inset 0 1px 0 rgba(255,255,255,0.30);
        transition: all .25s ease;
    }
    .vd-cashremit-banner:hover {
        transform: translateY(-2px);
        box-shadow: 0 22px 40px -12px rgba(244,63,94,0.60), inset 0 1px 0 rgba(255,255,255,0.30);
    }
    .vd-cashremit-glow {
        position: absolute; top: -80px; right: -80px;
        width: 240px; height: 240px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255,255,255,0.30) 0%, transparent 70%);
        pointer-events: none;
    }
    .vd-cashremit-l {
        position: relative;
        display: flex; align-items: center; gap: 14px;
        min-width: 0;
    }
    .vd-cashremit-ico {
        width: 44px; height: 44px;
        border-radius: 12px;
        background: rgba(255,255,255,0.22);
        backdrop-filter: blur(6px);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.40);
    }
    .vd-cashremit-ico svg { width: 20px; height: 20px; }
    .vd-cashremit-eyebrow {
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.10em;
        opacity: 0.95;
    }
    .vd-cashremit-amount {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 24px; font-weight: 800;
        line-height: 1.05;
        margin-top: 2px;
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.01em;
    }
    .vd-cashremit-amount span { font-size: 11px; font-weight: 700; opacity: 0.80; margin-left: 4px; }
    .vd-cashremit-sub { font-size: 11px; opacity: 0.92; margin-top: 3px; }
    .vd-cashremit-cta {
        position: relative;
        display: inline-flex; align-items: center; gap: 6px;
        padding: 10px 14px;
        background: white;
        color: #BE123C;
        border-radius: 10px;
        font-size: 12px; font-weight: 800;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .vd-cashremit-cta svg { width: 14px; height: 14px; }
    @media (max-width: 540px) {
        .vd-cashremit-banner { flex-direction: column; align-items: flex-start; }
        .vd-cashremit-cta { width: 100%; justify-content: center; }
    }

    .vd-stat--cta {
        background: linear-gradient(135deg, #1F2937, #0F172A);
        border-color: transparent;
        color: white;
        text-decoration: none;
    }
    .vd-stat-cta-glow {
        position: absolute; top: -30px; right: -30px;
        width: 120px; height: 120px;
        border-radius: 50%;
        background: rgba(78,205,196,0.20);
        filter: blur(30px);
        pointer-events: none;
    }

    .vd-stat-head {
        display: flex; align-items: flex-start; justify-content: space-between;
        gap: 8px; margin-bottom: 6px;
    }
    .vd-stat-flex {
        display: flex; align-items: center; justify-content: space-between;
        gap: 10px;
    }
    .vd-stat-label {
        font-family: 'Inter', sans-serif;
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.08em;
        color: #64748B;
    }
    .vd-stat-label--inv { color: rgba(255,255,255,0.85); }

    .vd-stat-icon {
        width: 32px; height: 32px;
        border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .vd-stat-icon svg { width: 14px; height: 14px; }
    .vd-stat-icon--inv {
        background: rgba(255,255,255,0.20);
        color: white;
        backdrop-filter: blur(8px);
    }
    .vd-stat-icon--lg { width: 44px; height: 44px; border-radius: 13px; }
    .vd-stat-icon--lg svg { width: 20px; height: 20px; }

    .vd-stat-value {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 22px; font-weight: 800;
        color: #0F172A;
        line-height: 1.1;
        letter-spacing: -0.01em;
        font-variant-numeric: tabular-nums;
    }
    .vd-stat-value--inv { color: white; }
    .vd-stat-value--lg { font-size: 24px; margin-top: 4px; }
    .vd-stat-unit {
        font-size: 11px; font-weight: 600;
        color: #94A3B8;
        margin-left: 4px;
    }
    .vd-stat-unit--inv { color: rgba(255,255,255,0.70); }
    .vd-stat-meta {
        font-size: 11px; color: #64748B;
        margin-top: 4px;
    }
    .vd-stat-meta--inv { color: rgba(255,255,255,0.75); }

    .vd-stat-bar {
        height: 4px;
        background: rgba(255,255,255,0.20);
        border-radius: 9999px;
        overflow: hidden;
        margin-top: 8px;
    }
    .vd-stat-bar-fill {
        height: 100%;
        background: white;
        box-shadow: 0 0 8px rgba(255,255,255,0.5);
        transition: width .3s ease;
    }

    /* ====================== SECTION ====================== */
    .vd-section { margin-bottom: 18px; }
    .vd-section-head {
        display: flex; align-items: center; justify-content: space-between;
        gap: 10px; margin-bottom: 12px;
    }
    .vd-section-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 16px; font-weight: 800;
        color: #0F172A;
        margin: 0;
        display: flex; align-items: center; gap: 8px;
        letter-spacing: -0.01em;
    }
    .vd-section-icon {
        display: inline-flex; align-items: center; justify-content: center;
        width: 28px; height: 28px;
        border-radius: 9px;
        background: linear-gradient(135deg, #4ECDC4, #44A08D);
        color: white;
        flex-shrink: 0;
    }
    .vd-section-icon svg { width: 13px; height: 13px; }
    .vd-section-link {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 12px; font-weight: 700;
        color: #44A08D;
        text-decoration: none;
        background: rgba(78,205,196,0.08);
        padding: 6px 11px;
        border-radius: 9999px;
    }
    .vd-section-link svg { width: 11px; height: 11px; }
    .vd-section-link:active { transform: scale(0.97); }

    /* ====================== GIFT CARDS GRID (admin-style) ====================== */
    .vd-cards-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    @media (min-width: 540px) { .vd-cards-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (min-width: 768px) { .vd-cards-grid { grid-template-columns: repeat(4, 1fr); } }

    .vd-gcard {
        display: block;
        background: white;
        border-radius: 14px;
        border: 1px solid #E2E8F0;
        overflow: hidden;
        text-decoration: none;
        color: inherit;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
        transition: all .2s ease;
    }
    .vd-gcard:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(15,23,42,0.08);
    }
    .vd-gcard-visual {
        position: relative;
        padding: 12px;
        height: 92px;
        overflow: hidden;
    }
    .vd-gcard-pattern {
        position: absolute; inset: 0;
        width: 100%; height: 100%;
        opacity: 0.08;
        pointer-events: none;
    }
    .vd-gcard-glow {
        position: absolute; top: -20px; right: -20px;
        width: 80px; height: 80px;
        border-radius: 50%;
        background: rgba(255,255,255,0.12);
        filter: blur(12px);
    }
    .vd-gcard-visual-body {
        position: relative;
        display: flex; flex-direction: column; height: 100%;
        justify-content: space-between;
    }
    .vd-gcard-top {
        display: flex; align-items: flex-start; justify-content: space-between;
        gap: 6px;
    }
    .vd-gcard-tag {
        font-size: 9px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.15em;
        color: rgba(255,255,255,0.75);
    }
    .vd-gcard-brand {
        font-family: 'Space Grotesk','Inter',sans-serif;
        color: white;
        font-size: 17px; font-weight: 800;
        line-height: 1.05;
        margin-top: 1px;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .vd-gcard-status {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 7px;
        border-radius: 9999px;
        font-size: 9px; font-weight: 800;
        color: white;
        background: rgba(16,185,129,0.30);
        border: 1px solid rgba(134,239,172,0.30);
        flex-shrink: 0;
    }
    .vd-gcard-status-dot {
        width: 5px; height: 5px;
        border-radius: 50%;
        background: #86EFAC;
    }
    .vd-gcard-price {
        font-family: 'Space Grotesk','Inter',sans-serif;
        color: white;
        font-size: 14px; font-weight: 800;
        font-variant-numeric: tabular-nums;
    }
    .vd-gcard-price span {
        font-size: 10px; font-weight: 600;
        color: rgba(255,255,255,0.70);
    }
    .vd-gcard-foot {
        display: flex; align-items: center; justify-content: space-between;
        gap: 6px;
        padding: 9px 12px;
    }
    .vd-gcard-num {
        font-family: 'JetBrains Mono','Fira Code',monospace;
        font-size: 10px; font-weight: 700;
        color: #475569;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .vd-gcard-when {
        font-size: 10px; color: #94A3B8;
        flex-shrink: 0;
    }

    /* ====================== TABLE (desktop) ====================== */
    .vd-table-wrap {
        display: none;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }
    @media (min-width: 768px) { .vd-table-wrap { display: block; } }
    .vd-table {
        width: 100%; border-collapse: collapse; font-size: 13px;
    }
    .vd-table thead {
        background: #FAFBFC;
        border-bottom: 1px solid #F1F5F9;
    }
    .vd-table th {
        padding: 11px 16px; text-align: left;
        font-weight: 800; color: #64748B;
        text-transform: uppercase; font-size: 10px; letter-spacing: 0.08em;
    }
    .vd-table td {
        padding: 14px 16px;
        border-bottom: 1px solid #F1F5F9;
        color: #334155;
    }
    .vd-table tbody tr {
        cursor: pointer;
        transition: background .15s ease;
    }
    .vd-table tbody tr:hover { background: #FAFBFC; }
    .vd-table-num {
        font-family: 'JetBrains Mono','Fira Code',monospace;
        font-weight: 700;
        color: #0F172A;
        font-size: 12px;
    }
    .vd-table-amount {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800; color: #0F172A;
        font-variant-numeric: tabular-nums;
    }
    .vd-table-amount span { font-size: 10px; font-weight: 600; color: #94A3B8; }
    .vd-table-comm {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 700; color: #44A08D;
        font-variant-numeric: tabular-nums;
    }
    .vd-table-date {
        color: #64748B; font-size: 12px;
    }

    .vd-status-pill {
        display: inline-block;
        padding: 3px 8px; border-radius: 9999px;
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.04em;
    }

    /* ====================== MOBILE ORDERS LIST ====================== */
    .vd-mobile-orders {
        display: flex; flex-direction: column; gap: 8px;
    }
    @media (min-width: 768px) { .vd-mobile-orders { display: none; } }

    .vd-mob-order {
        display: flex; align-items: center; gap: 12px;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        padding: 12px;
        text-decoration: none; color: inherit;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
        transition: all .15s ease;
    }
    .vd-mob-order:active { transform: scale(0.99); background: #F8FAFC; }
    .vd-mob-order-icon {
        width: 38px; height: 38px;
        border-radius: 11px;
        background: linear-gradient(135deg, #F0FDFA, #CCFBF1);
        color: #0F766E;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .vd-mob-order-icon svg { width: 16px; height: 16px; }
    .vd-mob-order-body { flex: 1; min-width: 0; }
    .vd-mob-order-line1 {
        display: flex; align-items: center; gap: 8px;
        margin-bottom: 3px;
    }
    .vd-mob-order-customer {
        font-size: 13px; font-weight: 700; color: #0F172A;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .vd-mob-order-line2 {
        display: flex; align-items: center; gap: 5px;
        font-size: 11px; color: #64748B;
    }
    .vd-mob-order-num {
        font-family: 'JetBrains Mono','Fira Code',monospace;
        font-weight: 700; color: #475569;
    }
    .vd-mob-order-amount { text-align: right; flex-shrink: 0; }
    .vd-mob-order-total {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 14px; font-weight: 800; color: #0F172A;
        font-variant-numeric: tabular-nums;
    }
    .vd-mob-order-comm {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 11px; font-weight: 700; color: #44A08D;
        font-variant-numeric: tabular-nums;
        margin-top: 2px;
    }

    /* ====================== EMPTY ====================== */
    .vd-empty {
        background: white;
        border: 1px dashed #CBD5E1;
        border-radius: 14px;
        padding: 36px 20px;
        text-align: center;
        color: #64748B;
    }
    .vd-empty > svg {
        width: 38px; height: 38px;
        color: #CBD5E1;
        margin: 0 auto 10px;
        display: block;
    }
    .vd-empty h3 {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 16px; font-weight: 700;
        color: #0F172A; margin: 0 0 4px;
    }
    .vd-empty p {
        font-size: 12px; color: #94A3B8;
        margin: 0 0 16px;
    }
    .vd-empty-cta {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 11px 18px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        border-radius: 11px;
        font-size: 13px; font-weight: 800;
        text-decoration: none;
        box-shadow: 0 10px 20px -8px rgba(78,205,196,0.50);
    }
    .vd-empty-cta svg { width: 13px; height: 13px; }
</style>
@endpush
@endsection
