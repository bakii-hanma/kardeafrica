@extends('vendor.layouts.vendor')

@section('title', 'Mon relevé')

@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', ' ');
    // Sens du mouvement : ce qui entre, ce qui sort, ce qui ne fait que se réserver.
    $tone = fn (string $type) => match ($type) {
        'credit', 'refund', 'commission', 'transfer_in', 'cash_remittance' => 'in',
        'debit', 'transfer_out'                                            => 'out',
        default                                                            => 'neutral',
    };
@endphp

@section('content')
<div class="vst-wrap">

    <div class="vst-top">
        <div>
            <div class="vst-eyebrow">Comptabilité</div>
            <h1 class="vst-title">Mon relevé</h1>
            <p class="vst-lead">
                Tous les mouvements de tes deux portefeuilles, du plus récent au plus ancien.
            </p>
        </div>
        <a href="{{ route('vendor.statement.export', request()->query()) }}" class="vst-export">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Exporter en CSV
        </a>
    </div>

    {{-- ============= FILTRES ============= --}}
    <form method="GET" class="vst-filters">
        <div class="vst-filter">
            <label class="vst-flabel" for="from">Du</label>
            <input id="from" type="date" name="from" value="{{ $filters['from'] }}" class="vst-input">
        </div>
        <div class="vst-filter">
            <label class="vst-flabel" for="to">Au</label>
            <input id="to" type="date" name="to" value="{{ $filters['to'] }}" class="vst-input">
        </div>
        <div class="vst-filter">
            <label class="vst-flabel" for="wallet">Portefeuille</label>
            <select id="wallet" name="wallet" class="vst-input">
                <option value="">Les deux</option>
                <option value="sales" @selected($filters['wallet'] === 'sales')>Vente</option>
                <option value="commission" @selected($filters['wallet'] === 'commission')>Commissions</option>
            </select>
        </div>
        <div class="vst-filter">
            <label class="vst-flabel" for="type">Type</label>
            <select id="type" name="type" class="vst-input">
                <option value="">Tous</option>
                @foreach($typeLabels as $key => $label)
                    <option value="{{ $key }}" @selected($filters['type'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="vst-filter vst-filter--actions">
            <button type="submit" class="vst-apply">Filtrer</button>
            @if(array_filter($filters))
                <a href="{{ route('vendor.statement') }}" class="vst-reset">Tout effacer</a>
            @endif
        </div>
    </form>

    {{-- ============= MOUVEMENTS ============= --}}
    @if($transactions->total() === 0)
        <div class="vst-empty">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
            <h3>Aucun mouvement</h3>
            <p>{{ array_filter($filters) ? 'Aucun mouvement ne correspond à ces filtres.' : 'Tes mouvements apparaîtront ici dès ta première opération.' }}</p>
        </div>
    @else
        <p class="vst-count">{{ $transactions->total() }} mouvement{{ $transactions->total() > 1 ? 's' : '' }}</p>

        <div class="vst-list">
            @foreach($transactions as $t)
                @php $dir = $tone($t->type); @endphp
                <div class="vst-row">
                    <div class="vst-main">
                        <div class="vst-l1">
                            <span class="vst-type">{{ $typeLabels[$t->type] ?? $t->type }}</span>
                            <span class="vst-wallet vst-wallet--{{ $t->wallet }}">
                                {{ $t->wallet === 'commission' ? 'Commissions' : 'Vente' }}
                            </span>
                        </div>
                        <div class="vst-l2">
                            {{ $t->description }}
                            @if($t->reference) · <span class="vst-ref">{{ $t->reference }}</span> @endif
                        </div>
                        <div class="vst-l3">{{ $t->created_at?->format('d/m/Y à H:i') }}</div>
                    </div>
                    <div class="vst-right">
                        <span class="vst-amount vst-amount--{{ $dir }}">
                            @if($dir === 'in'){{ '+' }}@elseif($dir === 'out'){{ '−' }}@endif{{ $fmt($t->amount) }}
                            <span>FCFA</span>
                        </span>
                        @if($dir !== 'neutral')
                            <span class="vst-after">solde {{ $fmt($t->balance_after) }}</span>
                        @else
                            <span class="vst-after">solde inchangé</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="vst-pagination">{{ $transactions->links() }}</div>
    @endif
</div>
@endsection

@push('head')
<style>
    .vst-wrap { max-width: 900px; margin: 0 auto; }
    .vst-top {
        display: flex; align-items: flex-start; justify-content: space-between;
        gap: 12px; flex-wrap: wrap; margin-bottom: 16px;
    }
    .vst-eyebrow {
        font-size: 10.5px; font-weight: 800; letter-spacing: .12em;
        text-transform: uppercase; color: #0F766E;
    }
    .vst-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 22px; font-weight: 800; color: #0F172A; margin: 3px 0 0;
    }
    .vst-lead { font-size: 13px; color: #64748B; margin: 4px 0 0; }
    .vst-export {
        display: inline-flex; align-items: center; gap: 8px;
        min-height: 44px; padding: 0 16px; flex-shrink: 0;
        background: #0F172A; color: #fff; border-radius: 11px;
        font-size: 13.5px; font-weight: 700; text-decoration: none;
    }
    .vst-export svg { width: 15px; height: 15px; }
    .vst-export:hover { background: #1E293B; }

    .vst-filters {
        display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
        background: #fff; border: 1px solid #E2E8F0; border-radius: 14px;
        padding: 14px; margin-bottom: 14px;
    }
    @media (min-width: 768px) { .vst-filters { grid-template-columns: repeat(5, 1fr); align-items: end; } }
    .vst-flabel {
        display: block; font-size: 10.5px; font-weight: 800;
        letter-spacing: .07em; text-transform: uppercase; color: #64748B; margin-bottom: 5px;
    }
    .vst-input {
        width: 100%; min-height: 44px; padding: 0 11px;
        border: 1px solid #CBD5E1; border-radius: 10px;
        font-family: inherit; font-size: 13.5px; color: #0F172A; background: #fff;
    }
    .vst-input:focus { outline: 3px solid #4ECDC4; outline-offset: 1px; border-color: #44A08D; }
    .vst-filter--actions { display: flex; gap: 8px; align-items: center; grid-column: 1 / -1; }
    @media (min-width: 768px) { .vst-filter--actions { grid-column: auto; } }
    .vst-apply {
        min-height: 44px; padding: 0 18px; flex: 1;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: #fff; border: 0; border-radius: 10px;
        font-family: inherit; font-size: 13.5px; font-weight: 700; cursor: pointer;
    }
    .vst-reset {
        display: inline-flex; align-items: center; min-height: 44px; padding: 0 10px;
        font-size: 12.5px; font-weight: 600; color: #64748B; text-decoration: none; white-space: nowrap;
    }
    .vst-reset:hover { color: #0F172A; }

    .vst-count { font-size: 12.5px; color: #64748B; margin: 0 0 10px; }
    .vst-list { display: flex; flex-direction: column; gap: 8px; }
    .vst-row {
        display: flex; align-items: flex-start; gap: 12px;
        padding: 13px 15px;
        background: #fff; border: 1px solid #E2E8F0; border-radius: 13px;
    }
    .vst-main { flex: 1; min-width: 0; }
    .vst-l1 { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .vst-type { font-size: 14px; font-weight: 700; color: #0F172A; }
    .vst-wallet {
        font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
        padding: 3px 8px; border-radius: 9999px;
    }
    .vst-wallet--sales { background: #CCFBF1; color: #0F766E; }
    .vst-wallet--commission { background: #FEF3C7; color: #B45309; }
    .vst-l2 { font-size: 12.5px; color: #64748B; margin-top: 3px; line-height: 1.4; }
    .vst-ref { font-family: 'JetBrains Mono','Fira Code',monospace; font-size: 11px; }
    .vst-l3 { font-size: 11.5px; color: #94A3B8; margin-top: 3px; font-variant-numeric: tabular-nums; }
    .vst-right { text-align: right; flex-shrink: 0; }
    .vst-amount {
        display: block;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 15px; font-weight: 800; font-variant-numeric: tabular-nums;
    }
    .vst-amount span { font-size: 10px; font-weight: 700; color: #64748B; }
    .vst-amount--in { color: #047857; }
    .vst-amount--out { color: #BE123C; }
    .vst-amount--neutral { color: #475569; }
    .vst-after { display: block; font-size: 11px; color: #94A3B8; margin-top: 2px; font-variant-numeric: tabular-nums; }

    .vst-empty {
        background: #fff; border: 1px solid #E2E8F0; border-radius: 16px;
        padding: 40px 20px; text-align: center;
    }
    .vst-empty svg { width: 40px; height: 40px; color: #CBD5E1; margin: 0 auto 12px; display: block; }
    .vst-empty h3 { font-family: 'Space Grotesk','Inter',sans-serif; font-size: 15px; font-weight: 800; color: #0F172A; margin: 0 0 6px; }
    .vst-empty p { font-size: 13px; color: #64748B; margin: 0; }

    .vst-pagination { margin-top: 16px; }
    .vst-pagination svg { width: 16px; height: 16px; }
    .vst-wrap a:focus-visible, .vst-wrap button:focus-visible { outline: 3px solid #4ECDC4; outline-offset: 2px; border-radius: 10px; }
</style>
@endpush
