@extends('vendor.layouts.vendor')

@section('title', 'Mes ventes')

@section('content')
@php
    $statusOptions = [
        'all'        => ['label' => 'Toutes',     'color' => '#0F172A', 'bg' => '#F1F5F9'],
        'completed'  => ['label' => 'Livrées',    'color' => '#047857', 'bg' => '#D1FAE5'],
        'processing' => ['label' => 'En cours',   'color' => '#1D4ED8', 'bg' => '#DBEAFE'],
        'pending'    => ['label' => 'En attente', 'color' => '#B45309', 'bg' => '#FEF3C7'],
        'cancelled'  => ['label' => 'Annulées',   'color' => '#475569', 'bg' => '#E2E8F0'],
        'failed'     => ['label' => 'Échec',      'color' => '#BE123C', 'bg' => '#FEE2E2'],
    ];
    $currentStatus = request('status', 'all');
    $currentSearch = request('search', '');
@endphp

<div class="vo-wrap">

    {{-- ============= TOP STRIP ============= --}}
    <div class="vo-top">
        <div>
            <div class="vo-eyebrow">Historique</div>
            <h1 class="vo-title">Mes ventes</h1>
            <p class="vo-lead">{{ $stats['total'] }} vente{{ $stats['total'] > 1 ? 's' : '' }} au total · <strong>{{ number_format($stats['commission'], 0, ',', ' ') }} FCFA</strong> de commissions</p>
        </div>
        <a href="{{ route('vendor.sell') }}" class="vo-cta">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Nouvelle vente
        </a>
    </div>

    {{-- ============= STATS STRIP ============= --}}
    <div class="vo-stats">
        <div class="vo-stat vo-stat--brand">
            <div class="vo-stat-icon vo-stat-icon--inv">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/></svg>
            </div>
            <div class="vo-stat-body">
                <div class="vo-stat-label vo-stat-label--inv">Total ventes</div>
                <div class="vo-stat-value vo-stat-value--inv">{{ $stats['total'] }}</div>
            </div>
        </div>
        <div class="vo-stat">
            <div class="vo-stat-icon" style="background:#D1FAE5;color:#047857;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div class="vo-stat-body">
                <div class="vo-stat-label">Livrées</div>
                <div class="vo-stat-value">{{ $stats['completed'] }}</div>
            </div>
        </div>
        <div class="vo-stat">
            <div class="vo-stat-icon" style="background:#FEF3C7;color:#B45309;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="vo-stat-body">
                <div class="vo-stat-label">En cours</div>
                <div class="vo-stat-value vo-stat-value--warn">{{ $stats['pending'] }}</div>
            </div>
        </div>
        <div class="vo-stat">
            <div class="vo-stat-icon" style="background:#EFF6FF;color:#1D4ED8;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
            <div class="vo-stat-body">
                <div class="vo-stat-label">Volume cumulé</div>
                <div class="vo-stat-value vo-stat-value--xs">{{ number_format($stats['volume'], 0, ',', ' ') }} <span>FCFA</span></div>
            </div>
        </div>
    </div>

    {{-- ============= TOOLBAR ============= --}}
    <div class="vo-toolbar">
        <form action="{{ route('vendor.orders') }}" method="GET" class="vo-search" data-no-loader>
            <input type="hidden" name="status" value="{{ $currentStatus !== 'all' ? $currentStatus : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="search" value="{{ $currentSearch }}" placeholder="Rechercher #commande, client…">
            @if($currentSearch)
                <a href="{{ route('vendor.orders', request()->except(['search', 'page'])) }}" class="vo-search-clear">×</a>
            @endif
            <button type="submit" class="vo-search-btn">OK</button>
        </form>
    </div>

    {{-- ============= STATUS PILLS ============= --}}
    <div class="vo-pills">
        @foreach($statusOptions as $key => $opt)
            @php
                $url = $key === 'all'
                    ? route('vendor.orders', request()->except(['status', 'page']))
                    : route('vendor.orders', array_merge(request()->except('page'), ['status' => $key]));
                $isActive = $currentStatus === $key;
            @endphp
            <a href="{{ $url }}"
               class="vo-pill {{ $isActive ? 'vo-pill--active' : '' }}"
               style="{{ $isActive ? "background:{$opt['color']};color:white;border-color:{$opt['color']};" : '' }}">
                {{ $opt['label'] }}
            </a>
        @endforeach
    </div>

    {{-- ============= ORDERS ============= --}}
    @if($orders->count() > 0)
        {{-- Desktop : table --}}
        <div class="vo-table-wrap">
            <table class="vo-table">
                <thead>
                    <tr>
                        <th>Commande</th>
                        <th>Client</th>
                        <th>Articles</th>
                        <th class="vo-th-r">Total</th>
                        <th class="vo-th-r">Commission</th>
                        <th class="vo-th-c">Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $o)
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
                            <td>
                                <div class="vo-table-num">#{{ $o->order_number }}</div>
                                <div class="vo-table-date">{{ $o->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td>
                                <div class="vo-table-customer">{{ $o->customer_name ?: '—' }}</div>
                                @if($o->customer_phone)<div class="vo-table-phone">{{ $o->customer_phone }}</div>@endif
                            </td>
                            <td class="vo-table-items">{{ $o->items->sum('quantity') }} carte{{ $o->items->sum('quantity') > 1 ? 's' : '' }}</td>
                            <td class="vo-th-r vo-table-amount">{{ number_format($o->total_amount, 0, ',', ' ') }} <span>FCFA</span></td>
                            <td class="vo-th-r vo-table-comm">+{{ number_format($o->commission_earned, 0, ',', ' ') }}</td>
                            <td class="vo-th-c"><span class="vo-status" style="background:{{ $st[1] }};color:{{ $st[0] }};">{{ $st[2] }}</span></td>
                            <td class="vo-th-r">
                                <a href="{{ route('vendor.orders.show', $o) }}" class="vo-table-action" onclick="event.stopPropagation();">
                                    Voir
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile : cards --}}
        <div class="vo-mobile">
            @foreach($orders as $o)
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
                <a href="{{ route('vendor.orders.show', $o) }}" class="vo-mob">
                    <div class="vo-mob-head">
                        <div class="vo-mob-num">#{{ $o->order_number }}</div>
                        <span class="vo-status" style="background:{{ $st[1] }};color:{{ $st[0] }};">{{ $st[2] }}</span>
                    </div>
                    <div class="vo-mob-customer">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        {{ $o->customer_name ?: 'Client anonyme' }}
                        @if($o->customer_phone)<span class="vo-mob-phone"> · {{ $o->customer_phone }}</span>@endif
                    </div>
                    <div class="vo-mob-foot">
                        <div class="vo-mob-meta">
                            <span>{{ $o->items->sum('quantity') }} carte{{ $o->items->sum('quantity') > 1 ? 's' : '' }}</span>
                            <span>·</span>
                            <span>{{ $o->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="vo-mob-amounts">
                            <div class="vo-mob-total">{{ number_format($o->total_amount, 0, ',', ' ') }} <span>FCFA</span></div>
                            <div class="vo-mob-comm">+{{ number_format($o->commission_earned, 0, ',', ' ') }} commission</div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($orders->hasPages())
            <div class="vo-pagination">{{ $orders->onEachSide(1)->links() }}</div>
        @endif
    @else
        {{-- Empty state --}}
        <div class="vo-empty">
            <div class="vo-empty-ico">
                @if($currentSearch || $currentStatus !== 'all')
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                @else
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                @endif
            </div>
            @if($currentSearch || $currentStatus !== 'all')
                <h3>Aucun résultat</h3>
                <p>Aucune vente ne correspond à tes filtres. Essaie d'ajuster la recherche ou le statut.</p>
                <a href="{{ route('vendor.orders') }}" class="vo-empty-cta vo-empty-cta--ghost">Effacer les filtres</a>
            @else
                <h3>Aucune vente pour le moment</h3>
                <p>Démarre ta première vente pour gagner ta commission et voir l'activité ici.</p>
                <a href="{{ route('vendor.sell') }}" class="vo-empty-cta">
                    Démarrer une vente
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
            @endif
        </div>
    @endif
</div>

<style>
    .vo-wrap { max-width: 1200px; margin: 0 auto; }

    /* ====================== TOP STRIP ====================== */
    .vo-top {
        display: flex; align-items: flex-start; justify-content: space-between;
        gap: 14px; flex-wrap: wrap;
        margin-bottom: 16px;
    }
    .vo-eyebrow {
        font-family: 'Inter', sans-serif;
        font-size: 11px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.10em;
        color: #44A08D;
    }
    .vo-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 22px; font-weight: 800;
        color: #0F172A; margin: 4px 0 4px;
        letter-spacing: -0.01em; line-height: 1.15;
    }
    .vo-lead { font-size: 12px; color: #64748B; margin: 0; }
    .vo-lead strong { color: #44A08D; font-weight: 800; }
    .vo-cta {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 11px 18px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white; border-radius: 11px;
        font-family: 'Inter', sans-serif;
        font-size: 13px; font-weight: 800;
        text-decoration: none;
        box-shadow: 0 10px 24px -8px rgba(78,205,196,0.45);
        white-space: nowrap;
    }
    .vo-cta:active { transform: scale(0.98); }
    .vo-cta svg { width: 13px; height: 13px; }

    /* ====================== STATS ====================== */
    .vo-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-bottom: 16px;
    }
    @media (min-width: 540px) { .vo-stats { grid-template-columns: repeat(4, 1fr); gap: 10px; } }

    .vo-stat {
        display: flex; align-items: center; gap: 10px;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 13px;
        padding: 12px;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
        transition: all .2s ease;
    }
    .vo-stat:hover { transform: translateY(-1px); box-shadow: 0 6px 16px -6px rgba(15,23,42,0.10); }
    .vo-stat--brand {
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        border-color: transparent;
        color: white;
        box-shadow: 0 12px 28px -10px rgba(68,160,141,0.45),
                    inset 0 1px 0 rgba(255,255,255,0.30);
    }
    .vo-stat-icon {
        width: 36px; height: 36px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .vo-stat-icon svg { width: 16px; height: 16px; }
    .vo-stat-icon--inv { background: rgba(255,255,255,0.20); color: white; backdrop-filter: blur(8px); }
    .vo-stat-body { flex: 1; min-width: 0; }
    .vo-stat-label {
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.06em;
        color: #64748B;
        line-height: 1;
    }
    .vo-stat-label--inv { color: rgba(255,255,255,0.85); }
    .vo-stat-value {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 19px; font-weight: 800;
        color: #0F172A;
        font-variant-numeric: tabular-nums;
        line-height: 1.1;
        margin-top: 4px;
    }
    .vo-stat-value--inv  { color: white; }
    .vo-stat-value--warn { color: #B45309; }
    .vo-stat-value--xs   { font-size: 14px; }
    .vo-stat-value--xs span { font-size: 9px; font-weight: 600; color: #94A3B8; margin-left: 2px; }

    /* ====================== TOOLBAR ====================== */
    .vo-toolbar {
        margin-bottom: 10px;
    }
    .vo-search {
        display: flex; align-items: center; gap: 8px;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        padding: 4px 4px 4px 12px;
        transition: all .15s ease;
    }
    .vo-search:focus-within {
        border-color: #44A08D;
        box-shadow: 0 0 0 3px rgba(68,160,141,0.12);
    }
    .vo-search > svg { width: 15px; height: 15px; color: #94A3B8; flex-shrink: 0; }
    .vo-search input {
        flex: 1; padding: 9px 0;
        background: transparent; border: 0;
        font-size: 13px; outline: none;
        font-family: inherit; color: #0F172A;
        min-width: 0;
    }
    .vo-search input::placeholder { color: #94A3B8; }
    .vo-search-clear {
        width: 26px; height: 26px;
        border-radius: 7px;
        background: #F1F5F9; color: #64748B;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 16px; line-height: 1; font-weight: 700;
        text-decoration: none;
        flex-shrink: 0;
    }
    .vo-search-btn {
        padding: 8px 14px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white; border: 0; border-radius: 9px;
        font-family: inherit;
        font-size: 12px; font-weight: 800;
        cursor: pointer;
        flex-shrink: 0;
    }

    /* ====================== STATUS PILLS ====================== */
    .vo-pills {
        display: flex; gap: 6px;
        margin-bottom: 14px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 4px;
        scrollbar-width: none;
    }
    .vo-pills::-webkit-scrollbar { display: none; }
    .vo-pill {
        display: inline-flex; align-items: center;
        padding: 7px 14px;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 9999px;
        color: #475569;
        font-family: 'Inter', sans-serif;
        font-size: 12px; font-weight: 700;
        text-decoration: none;
        white-space: nowrap;
        flex-shrink: 0;
        transition: all .15s ease;
    }
    .vo-pill:hover { border-color: #94A3B8; }
    .vo-pill--active { font-weight: 800; }

    /* ====================== TABLE (desktop) ====================== */
    .vo-table-wrap {
        display: none;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }
    @media (min-width: 768px) { .vo-table-wrap { display: block; } }

    .vo-table {
        width: 100%; border-collapse: collapse;
        font-size: 13px;
    }
    .vo-table thead {
        background: #FAFBFC;
        border-bottom: 1px solid #F1F5F9;
    }
    .vo-table th {
        padding: 11px 14px;
        text-align: left;
        font-weight: 800; color: #64748B;
        text-transform: uppercase;
        font-size: 10px; letter-spacing: 0.08em;
    }
    .vo-th-r { text-align: right; }
    .vo-th-c { text-align: center; }
    .vo-table td {
        padding: 12px 14px;
        border-bottom: 1px solid #F1F5F9;
        color: #475569;
    }
    .vo-table tbody tr {
        cursor: pointer;
        transition: background .15s ease;
    }
    .vo-table tbody tr:hover { background: #FAFBFC; }
    .vo-table tbody tr:last-child td { border-bottom: 0; }

    .vo-table-num {
        font-family: 'JetBrains Mono','Fira Code',monospace;
        font-size: 12px; font-weight: 700;
        color: #0F172A;
    }
    .vo-table-date {
        font-size: 11px; color: #94A3B8; margin-top: 2px;
    }
    .vo-table-customer {
        font-size: 13px; font-weight: 700; color: #0F172A;
    }
    .vo-table-phone {
        font-size: 11px; color: #64748B; margin-top: 2px;
    }
    .vo-table-items { font-size: 13px; color: #475569; }
    .vo-table-amount {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 14px; font-weight: 800;
        color: #0F172A;
        font-variant-numeric: tabular-nums;
    }
    .vo-table-amount span { font-size: 10px; font-weight: 600; color: #94A3B8; }
    .vo-table-comm {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 13px; font-weight: 700;
        color: #44A08D;
        font-variant-numeric: tabular-nums;
    }
    .vo-table-action {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 6px 12px;
        background: #F1F5F9;
        color: #0F172A;
        border-radius: 8px;
        font-size: 11px; font-weight: 700;
        text-decoration: none;
        transition: all .15s ease;
    }
    .vo-table-action:hover { background: #0F172A; color: white; }
    .vo-table-action svg { width: 11px; height: 11px; }

    .vo-status {
        display: inline-block;
        padding: 3px 9px;
        border-radius: 9999px;
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.04em;
    }

    /* ====================== MOBILE CARDS ====================== */
    .vo-mobile {
        display: flex; flex-direction: column; gap: 10px;
    }
    @media (min-width: 768px) { .vo-mobile { display: none; } }

    .vo-mob {
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        padding: 14px;
        text-decoration: none; color: inherit;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
        transition: all .15s ease;
        display: block;
    }
    .vo-mob:active { transform: scale(0.99); background: #F8FAFC; }

    .vo-mob-head {
        display: flex; align-items: center; justify-content: space-between;
        gap: 10px;
        margin-bottom: 8px;
    }
    .vo-mob-num {
        font-family: 'JetBrains Mono','Fira Code',monospace;
        font-size: 13px; font-weight: 700;
        color: #0F172A;
    }
    .vo-mob-customer {
        display: flex; align-items: center; gap: 6px;
        font-size: 13px; font-weight: 600;
        color: #0F172A;
        margin-bottom: 10px;
    }
    .vo-mob-customer svg { width: 13px; height: 13px; color: #44A08D; flex-shrink: 0; }
    .vo-mob-phone { color: #94A3B8; font-weight: 500; }

    .vo-mob-foot {
        display: flex; align-items: flex-end; justify-content: space-between;
        gap: 10px;
        padding-top: 10px;
        border-top: 1px dashed #E2E8F0;
    }
    .vo-mob-meta {
        display: flex; align-items: center; gap: 5px;
        font-size: 11px; color: #94A3B8;
        flex-wrap: wrap;
    }
    .vo-mob-amounts { text-align: right; flex-shrink: 0; }
    .vo-mob-total {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 16px; font-weight: 800;
        color: #0F172A;
        font-variant-numeric: tabular-nums;
        line-height: 1.1;
    }
    .vo-mob-total span {
        font-size: 10px; font-weight: 600; color: #94A3B8;
    }
    .vo-mob-comm {
        font-size: 10px; font-weight: 700;
        color: #44A08D;
        font-variant-numeric: tabular-nums;
        margin-top: 3px;
    }

    /* ====================== PAGINATION ====================== */
    .vo-pagination {
        margin-top: 18px;
        display: flex; justify-content: center;
    }
    .vo-pagination nav { display: inline-flex; gap: 4px; align-items: center; }
    .vo-pagination a, .vo-pagination span {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 36px; height: 36px; padding: 0 10px;
        border-radius: 9px;
        background: white;
        border: 1px solid #E2E8F0;
        color: #475569;
        font-size: 13px; font-weight: 700;
        font-variant-numeric: tabular-nums;
        text-decoration: none;
    }
    .vo-pagination a:hover { border-color: #44A08D; color: #44A08D; }
    .vo-pagination [aria-current="page"] {
        background: #44A08D !important; color: white !important;
        border-color: #44A08D !important;
    }
    .vo-pagination [aria-disabled="true"] {
        background: #F8FAFC; color: #CBD5E1;
        cursor: not-allowed;
    }

    /* ====================== EMPTY ====================== */
    .vo-empty {
        background: white;
        border: 1px dashed #CBD5E1;
        border-radius: 16px;
        padding: 48px 24px;
        text-align: center;
    }
    .vo-empty-ico {
        display: inline-flex; align-items: center; justify-content: center;
        width: 64px; height: 64px;
        border-radius: 18px;
        background: #F1F5F9;
        color: #94A3B8;
        margin-bottom: 14px;
    }
    .vo-empty-ico svg { width: 30px; height: 30px; }
    .vo-empty h3 {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 17px; font-weight: 700;
        color: #0F172A; margin: 0 0 6px;
    }
    .vo-empty p {
        font-size: 13px; color: #64748B;
        margin: 0 0 18px;
        line-height: 1.5;
        max-width: 360px;
        margin-left: auto; margin-right: auto;
    }
    .vo-empty-cta {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 11px 20px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white; border-radius: 11px;
        font-size: 13px; font-weight: 800;
        text-decoration: none;
        box-shadow: 0 10px 22px -8px rgba(78,205,196,0.50);
    }
    .vo-empty-cta--ghost {
        background: white;
        color: #475569;
        border: 1px solid #E2E8F0;
        box-shadow: none;
    }
    .vo-empty-cta--ghost:hover { border-color: #44A08D; color: #44A08D; }
    .vo-empty-cta svg { width: 13px; height: 13px; }
</style>
@endsection
