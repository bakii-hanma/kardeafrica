@extends('owner.layouts.owner')

@section('title', 'Tableau de bord')
@section('page-title', 'Bonjour, ' . $owner->contact_name . ' 👋')
@section('page-subtitle', $owner->business_name . ($owner->city ? ' · ' . $owner->city : ''))

@section('topbar-actions')
    <a href="{{ route('owner.scan') }}" style="display:inline-flex;align-items:center;gap:6px;padding:10px 16px;background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;border-radius:11px;font-size:13px;font-weight:700;text-decoration:none;box-shadow:0 10px 24px -10px rgba(68,160,141,.5);">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
        Scanner une carte
    </a>
@endsection

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', ' ');
@endphp

{{-- ============ STATS ============ --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-bottom:22px;">
    <div style="background:white;border:1px solid #E2E8F0;border-radius:14px;padding:16px 18px;box-shadow:0 1px 2px rgba(15,23,42,.04);">
        <div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:700;color:#94A3B8;">Cartes publiées</div>
        <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:26px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:4px;">{{ $stats['cards_active'] }}<span style="font-size:13px;font-weight:600;color:#94A3B8;margin-left:6px;">/ {{ $stats['cards_total'] }} total</span></div>
    </div>
    <div style="background:white;border:1px solid #E2E8F0;border-radius:14px;padding:16px 18px;box-shadow:0 1px 2px rgba(15,23,42,.04);">
        <div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:700;color:#94A3B8;">Cartes vendues</div>
        <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:26px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:4px;">{{ $stats['purchases_total'] }}</div>
    </div>
    <div style="background:linear-gradient(135deg,#0F172A,#0F4F44);color:white;border-radius:14px;padding:16px 18px;box-shadow:0 8px 24px -10px rgba(68,160,141,.4);">
        <div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:700;color:rgba(255,255,255,.7);">Revenus générés</div>
        <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:24px;font-weight:800;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:4px;">{{ $fmt($stats['revenue_total']) }} <span style="font-size:13px;opacity:.8;">FCFA</span></div>
        <div style="font-size:11px;color:rgba(255,255,255,.55);margin-top:4px;">paiements clients bruts</div>
    </div>
    <div style="background:linear-gradient(135deg,#10B981,#059669);color:white;border-radius:14px;padding:16px 18px;box-shadow:0 8px 24px -10px rgba(16,185,129,.4);">
        <div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:700;color:rgba(255,255,255,.7);">Net après commissions</div>
        <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:24px;font-weight:800;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:4px;">{{ $fmt($stats['net_total']) }} <span style="font-size:13px;opacity:.8;">FCFA</span></div>
        <div style="font-size:11px;color:rgba(255,255,255,.55);margin-top:4px;">ta part nette</div>
    </div>
    <div style="background:white;border:1px solid #E2E8F0;border-radius:14px;padding:16px 18px;box-shadow:0 1px 2px rgba(15,23,42,.04);">
        <div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:700;color:#94A3B8;">Solde restant en circulation</div>
        <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:24px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:4px;">{{ $fmt($stats['balance_outstanding']) }} <span style="font-size:13px;color:#94A3B8;">FCFA</span></div>
        <div style="font-size:11px;color:#64748B;margin-top:4px;">à valider au comptoir</div>
    </div>
    <div style="background:white;border:1px solid #E2E8F0;border-radius:14px;padding:16px 18px;box-shadow:0 1px 2px rgba(15,23,42,.04);">
        <div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:700;color:#94A3B8;">Validations au comptoir</div>
        <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:26px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:4px;">{{ $stats['redemptions_total'] }}</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr;gap:14px;@media (min-width:1024px){grid-template-columns:1.4fr 1fr;}">
    {{-- ============ ACHATS RÉCENTS ============ --}}
    <div style="background:white;border:1px solid #E2E8F0;border-radius:14px;padding:18px;box-shadow:0 1px 2px rgba(15,23,42,.04);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <h2 style="margin:0;font-family:'Space Grotesk','Inter',sans-serif;font-size:14px;font-weight:800;color:#0F172A;text-transform:uppercase;letter-spacing:.06em;">Achats récents</h2>
            <a href="{{ route('owner.cards') }}" style="font-size:11px;font-weight:700;color:#44A08D;text-decoration:none;">Tout voir →</a>
        </div>
        @if($recentPurchases->isEmpty())
            <div style="text-align:center;padding:32px 18px;color:#64748B;font-size:13px;">
                <p style="margin:0;">Aucun achat enregistré pour le moment.</p>
                <p style="margin-top:6px;font-size:12px;color:#94A3B8;">Les achats des clients apparaîtront ici dès qu'une vente sera réalisée.</p>
            </div>
        @else
            <div style="display:flex;flex-direction:column;gap:10px;">
                @foreach($recentPurchases as $p)
                    <div style="display:flex;align-items:center;gap:12px;padding:10px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:11px;">
                        <div style="width:42px;height:42px;border-radius:10px;background:linear-gradient(135deg,#0F172A,#0F4F44);position:relative;overflow:hidden;flex-shrink:0;">
                            @if($p->merchantCard?->visual_url)
                                <img src="{{ asset($p->merchantCard->visual_url) }}" style="width:100%;height:100%;object-fit:cover;" alt="">
                            @endif
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:13px;font-weight:700;color:#0F172A;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $p->merchantCard?->name ?? 'Carte supprimée' }}</div>
                            <div style="font-size:11px;color:#64748B;">{{ $p->buyer_name }} · {{ $p->paid_at?->translatedFormat('d M Y H:i') }}</div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:14px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;">{{ $fmt($p->amount) }} <span style="font-size:10px;color:#94A3B8;">FCFA</span></div>
                            @php
                                $statusColor = match ($p->status) {
                                    'active'         => ['#D1FAE5','#065F46','Active'],
                                    'partially_used' => ['#FEF3C7','#92400E','Partielle'],
                                    'fully_used'     => ['#F1F5F9','#475569','Épuisée'],
                                    'expired'        => ['#FEE2E2','#991B1B','Expirée'],
                                    default          => ['#F1F5F9','#475569',$p->status],
                                };
                            @endphp
                            <span style="display:inline-block;padding:2px 8px;border-radius:9999px;background:{{ $statusColor[0] }};color:{{ $statusColor[1] }};font-size:10px;font-weight:700;margin-top:2px;">{{ $statusColor[2] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ============ HISTORIQUE COMPTOIR ============ --}}
    <div style="background:white;border:1px solid #E2E8F0;border-radius:14px;padding:18px;box-shadow:0 1px 2px rgba(15,23,42,.04);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <h2 style="margin:0;font-family:'Space Grotesk','Inter',sans-serif;font-size:14px;font-weight:800;color:#0F172A;text-transform:uppercase;letter-spacing:.06em;">Validations récentes</h2>
            <a href="{{ route('owner.history') }}" style="font-size:11px;font-weight:700;color:#44A08D;text-decoration:none;">Historique →</a>
        </div>
        @if($recentRedemptions->isEmpty())
            <div style="text-align:center;padding:32px 18px;color:#64748B;font-size:13px;">
                <p style="margin:0;">Aucune validation pour le moment.</p>
                <p style="margin-top:6px;font-size:12px;color:#94A3B8;">Quand tu valideras une carte au comptoir, elle apparaîtra ici.</p>
            </div>
        @else
            <div style="display:flex;flex-direction:column;gap:8px;">
                @foreach($recentRedemptions as $r)
                    <div style="padding:10px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <div style="font-size:12px;font-weight:700;color:#0F172A;">{{ $r->purchase->merchantCard?->name ?? '—' }}</div>
                            <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:13px;font-weight:800;color:#DC2626;font-variant-numeric:tabular-nums;">-{{ $fmt($r->amount_used) }} F</div>
                        </div>
                        <div style="font-size:11px;color:#64748B;margin-top:2px;">{{ $r->redeemed_at?->translatedFormat('d M Y H:i') }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
