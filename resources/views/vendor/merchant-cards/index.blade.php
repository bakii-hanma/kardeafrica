@extends('vendor.layouts.vendor')

@section('title', 'Mes cartes-cadeau')

@section('content')
<style>
    .mc-wrap { max-width: 1100px; margin: 0 auto; padding: 20px 16px 80px; }

    /* ------- Header ------- */
    .mc-head {
        display: flex; align-items: flex-end; justify-content: space-between;
        gap: 16px; margin-bottom: 22px; flex-wrap: wrap;
    }
    .mc-head h1 {
        margin: 0;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 22px; font-weight: 800; color: #0F172A;
        letter-spacing: -0.02em;
    }
    .mc-head p {
        margin: 4px 0 0; color: #64748B; font-size: 13px;
    }
    .mc-btn-new {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 16px;
        background: linear-gradient(135deg,#44A08D,#4ECDC4);
        color: white; text-decoration: none;
        border-radius: 12px;
        font-weight: 700; font-size: 13px;
        box-shadow: 0 6px 16px -4px rgba(78,205,196,.45),
                    inset 0 1px 0 rgba(255,255,255,.3);
        transition: transform .15s, box-shadow .15s;
    }
    .mc-btn-new:hover { transform: translateY(-1px); }
    .mc-btn-new svg { width: 16px; height: 16px; }

    /* ------- KYC banner ------- */
    .mc-banner {
        display: flex; gap: 12px; align-items: flex-start;
        padding: 14px 16px;
        border-radius: 14px;
        margin-bottom: 18px;
    }
    .mc-banner--warn  { background: linear-gradient(135deg,#FEF3C7,#FDE68A); color: #92400E; }
    .mc-banner--info  { background: linear-gradient(135deg,#DBEAFE,#BFDBFE); color: #1E40AF; }
    .mc-banner svg { width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px; }
    .mc-banner strong { display: block; font-weight: 800; font-size: 13px; margin-bottom: 2px; }
    .mc-banner span { font-size: 12px; line-height: 1.5; }

    /* ------- Empty state ------- */
    .mc-empty {
        background: white;
        border: 2px dashed #E2E8F0;
        border-radius: 16px;
        padding: 48px 24px;
        text-align: center;
    }
    .mc-empty-icon {
        display: inline-flex; align-items: center; justify-content: center;
        width: 64px; height: 64px;
        border-radius: 50%;
        background: linear-gradient(135deg,#ECFDF5,#D1FAE5);
        color: #44A08D;
        margin-bottom: 16px;
    }
    .mc-empty-icon svg { width: 32px; height: 32px; }
    .mc-empty h3 {
        margin: 0 0 6px;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 18px; font-weight: 800; color: #0F172A;
    }
    .mc-empty p { margin: 0 0 18px; color: #64748B; font-size: 14px; }

    /* ------- Card grid ------- */
    .mc-grid {
        display: grid; gap: 16px;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
    .mc-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 14px -6px rgba(15,23,42,.08),
                    0 0 0 1px rgba(15,23,42,.04);
        transition: transform .15s, box-shadow .15s;
    }
    .mc-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 28px -8px rgba(15,23,42,.18),
                    0 0 0 1px rgba(15,23,42,.04);
    }
    .mc-card-visual {
        position: relative;
        aspect-ratio: 1.55;
        background: linear-gradient(135deg,#1E293B,#0F172A);
        display: flex; align-items: center; justify-content: center;
        color: rgba(255,255,255,.4);
        overflow: hidden;
    }
    .mc-card-visual img { width: 100%; height: 100%; object-fit: cover; }
    .mc-card-visual-placeholder { font-size: 12px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }

    .mc-card-status {
        position: absolute; top: 10px; left: 10px;
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 10px;
        border-radius: 9999px;
        font-size: 10px; font-weight: 800; text-transform: uppercase;
        letter-spacing: .06em;
        backdrop-filter: blur(8px);
    }
    .mc-card-status--active  { background: rgba(16,185,129,.95); color: white; }
    .mc-card-status--pending { background: rgba(245,158,11,.95); color: white; }
    .mc-card-status--rejected{ background: rgba(220,38,38,.95); color: white; }
    .mc-card-status svg { width: 10px; height: 10px; }

    .mc-card-body { padding: 14px 16px 16px; }
    .mc-card-cat {
        display: inline-block;
        font-size: 10px; font-weight: 700; text-transform: uppercase;
        color: #44A08D; letter-spacing: .08em;
        margin-bottom: 4px;
    }
    .mc-card-name {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 15px; font-weight: 800; color: #0F172A;
        margin: 0 0 6px;
        line-height: 1.25;
    }
    .mc-card-denoms {
        display: flex; flex-wrap: wrap; gap: 4px;
        margin-bottom: 10px;
    }
    .mc-card-denoms span {
        font-size: 10px; font-weight: 700; color: #475569;
        background: #F1F5F9; padding: 3px 7px;
        border-radius: 4px;
        font-variant-numeric: tabular-nums;
    }
    .mc-card-stats {
        display: flex; gap: 14px;
        padding: 10px 0 12px;
        border-top: 1px solid #F1F5F9;
        border-bottom: 1px solid #F1F5F9;
        margin-bottom: 12px;
    }
    .mc-card-stat-label {
        font-size: 9px; font-weight: 700; text-transform: uppercase;
        color: #94A3B8; letter-spacing: .08em;
    }
    .mc-card-stat-value {
        font-size: 14px; font-weight: 800; color: #0F172A;
        font-variant-numeric: tabular-nums;
        margin-top: 2px;
    }
    .mc-card-actions { display: flex; gap: 8px; }
    .mc-card-actions form { margin: 0; flex: 0 0 auto; }
    .mc-act {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 5px;
        padding: 8px 12px;
        font-size: 12px; font-weight: 700;
        border-radius: 9px;
        text-decoration: none; border: 0; cursor: pointer;
        transition: background .15s;
    }
    .mc-act svg { width: 14px; height: 14px; }
    .mc-act--edit { background: #F1F5F9; color: #0F172A; flex: 1; }
    .mc-act--edit:hover { background: #E2E8F0; }
    .mc-act--del  { background: #FEE2E2; color: #B91C1C; }
    .mc-act--del:hover  { background: #FECACA; }
</style>

<div class="mc-wrap">

    {{-- ============= HEAD ============= --}}
    <div class="mc-head">
        <div>
            <h1>Mes cartes-cadeau marchand</h1>
            <p>Cartes que tu vends sous ta propre marque, utilisables uniquement chez toi.</p>
        </div>
        <a href="{{ route('vendor.merchant-cards.create') }}" class="mc-btn-new">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Nouvelle carte
        </a>
    </div>

    {{-- ============= KYC GATE BANNER ============= --}}
    @php $kyc = $reseller->kyc_status ?? 'pending'; @endphp
    @if($kyc !== 'approved')
        <div class="mc-banner mc-banner--warn">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div>
                <strong>Validation KYC en attente</strong>
                <span>Tu peux préparer tes cartes dès maintenant, mais elles ne seront publiées sur Kardafrica qu'après validation de ton compte par notre équipe.</span>
            </div>
        </div>
    @endif

    {{-- ============= LIST ============= --}}
    @if($cards->isEmpty())
        <div class="mc-empty">
            <div class="mc-empty-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <h3>Aucune carte-cadeau pour l'instant</h3>
            <p>Crée ta première carte pour vendre des bons d'achat utilisables dans ton commerce.</p>
            <a href="{{ route('vendor.merchant-cards.create') }}" class="mc-btn-new">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Créer ma première carte
            </a>
        </div>
    @else
        <div class="mc-grid">
            @foreach($cards as $card)
                @php
                    $catLabel = \App\Http\Controllers\Vendor\MerchantCardController::CATEGORIES[$card->category] ?? $card->category;
                    $statusClass = $card->is_active ? 'active' : ($card->rejection_reason ? 'rejected' : 'pending');
                    $statusLabel = $card->is_active ? 'Active' : ($card->rejection_reason ? 'Refusée' : 'En attente');
                @endphp
                <div class="mc-card">
                    <div class="mc-card-visual">
                        @if($card->visual_url)
                            <img src="{{ asset($card->visual_url) }}" alt="{{ $card->name }}">
                        @else
                            <span class="mc-card-visual-placeholder">Aucun visuel</span>
                        @endif

                        <span class="mc-card-status mc-card-status--{{ $statusClass }}">
                            @if($card->is_active)
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @elseif($card->rejection_reason)
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            @else
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @endif
                            {{ $statusLabel }}
                        </span>
                    </div>

                    <div class="mc-card-body">
                        <div class="mc-card-cat">{{ $catLabel }}</div>
                        <h3 class="mc-card-name">{{ $card->name }}</h3>

                        <div class="mc-card-denoms">
                            @foreach(($card->denominations ?? []) as $d)
                                <span>{{ number_format($d, 0, ',', ' ') }}</span>
                            @endforeach
                            @if($card->allow_custom_amount)
                                <span style="background:#DBEAFE;color:#1E40AF;">+ libre</span>
                            @endif
                        </div>

                        <div class="mc-card-stats">
                            <div style="flex:1;">
                                <div class="mc-card-stat-label">Ventes</div>
                                <div class="mc-card-stat-value">{{ $card->purchases_count ?? 0 }}</div>
                            </div>
                            <div style="flex:1;">
                                <div class="mc-card-stat-label">Revenus</div>
                                <div class="mc-card-stat-value">{{ number_format($card->total_revenue ?? 0, 0, ',', ' ') }}</div>
                            </div>
                        </div>

                        @if($card->rejection_reason)
                            <div style="background:#FEE2E2;color:#991B1B;padding:8px 10px;border-radius:8px;font-size:11px;margin-bottom:10px;line-height:1.4;">
                                <strong style="display:block;margin-bottom:2px;">Motif du refus</strong>
                                {{ $card->rejection_reason }}
                            </div>
                        @endif

                        <div class="mc-card-actions">
                            <a href="{{ route('vendor.merchant-cards.edit', $card) }}" class="mc-act mc-act--edit">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Modifier
                            </a>
                            <form method="POST" action="{{ route('vendor.merchant-cards.destroy', $card) }}"
                                  onsubmit="return confirm('Supprimer définitivement cette carte ? Si des achats existent, elle sera juste désactivée.');">
                                @csrf @method('DELETE')
                                <button type="submit" class="mc-act mc-act--del" title="Supprimer">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
