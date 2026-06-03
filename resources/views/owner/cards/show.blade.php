@extends('owner.layouts.owner')

@section('title', $card->name)
@section('page-title', $card->name)
@section('page-subtitle', 'Détail de la carte + acheteurs')

@section('topbar-actions')
    <div style="display:inline-flex;gap:8px;">
        <a href="{{ route('owner.card.edit', $card) }}" style="display:inline-flex;align-items:center;gap:5px;padding:8px 14px;background:white;color:#0F172A;border:1px solid #E2E8F0;border-radius:10px;font-size:12px;font-weight:700;text-decoration:none;">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Modifier
        </a>
        <a href="{{ route('owner.cards') }}" style="display:inline-flex;align-items:center;gap:5px;padding:8px 14px;background:#F1F5F9;color:#475569;border-radius:10px;font-size:12px;font-weight:700;text-decoration:none;">← Retour</a>
    </div>
@endsection

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', ' ');
    $hasCommissions = (float) $card->admin_commission_rate > 0 || (float) $card->vendor_commission_rate > 0;
    $ownerShareRate = max(0, 100 - (float) $card->admin_commission_rate - (float) $card->vendor_commission_rate);
@endphp

@if(!$card->is_active && empty($card->rejection_reason))
    <div style="background:linear-gradient(135deg,#FEF3C7,#FDE68A);border:1px solid #F59E0B;border-radius:14px;padding:14px 18px;margin-bottom:14px;display:flex;align-items:center;gap:12px;">
        <div style="width:36px;height:36px;border-radius:50%;background:rgba(245,158,11,.25);display:flex;align-items:center;justify-content:center;flex-shrink:0;">⏳</div>
        <div style="flex:1;">
            <div style="font-family:'Space Grotesk','Inter',sans-serif;font-weight:800;color:#92400E;font-size:13px;">En attente de validation par l'admin</div>
            <div style="font-size:12px;color:#78350F;margin-top:2px;">Cette carte est en brouillon. Elle sera visible sur Kardafrica dès que notre équipe l'aura validée et fixé les commissions.</div>
        </div>
    </div>
@elseif(!$card->is_active && !empty($card->rejection_reason))
    <div style="background:#FEE2E2;border:1px solid #FCA5A5;border-radius:14px;padding:14px 18px;margin-bottom:14px;">
        <div style="font-family:'Space Grotesk','Inter',sans-serif;font-weight:800;color:#991B1B;font-size:13px;">❌ Carte refusée</div>
        <div style="font-size:12px;color:#7F1D1D;margin-top:4px;">Motif : {{ $card->rejection_reason }}</div>
    </div>
@endif

<div style="display:grid;grid-template-columns:1fr;gap:18px;@media (min-width:1024px){grid-template-columns:340px 1fr;}">

    {{-- Carte visuel + meta --}}
    <div>
        <div style="position:relative;aspect-ratio:1.55;background:linear-gradient(135deg,#1E293B,#0F4F44);border-radius:14px;overflow:hidden;box-shadow:0 8px 24px -8px rgba(15,23,42,.25);">
            @if($card->visual_url)
                <img src="{{ asset($card->visual_url) }}" style="width:100%;height:100%;object-fit:cover;" alt="">
            @endif
            <span style="position:absolute;top:10px;right:10px;padding:4px 11px;border-radius:9999px;background:{{ $card->is_active ? '#10B981' : '#64748B' }};color:white;font-size:10px;font-weight:800;letter-spacing:.06em;">
                {{ $card->is_active ? 'ACTIVE' : 'BROUILLON' }}
            </span>
        </div>

        <div style="background:white;border:1px solid #E2E8F0;border-radius:14px;padding:18px;margin-top:14px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;font-size:13px;">
                <div>
                    <div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:700;color:#94A3B8;">Validité</div>
                    <div style="color:#0F172A;font-weight:700;margin-top:2px;">{{ $card->validity_months }} mois</div>
                </div>
                <div>
                    <div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:700;color:#94A3B8;">Vendues</div>
                    <div style="color:#0F172A;font-weight:700;margin-top:2px;font-variant-numeric:tabular-nums;">{{ $card->total_sold }}</div>
                </div>
                <div>
                    <div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:700;color:#94A3B8;">Revenus</div>
                    <div style="color:#0F172A;font-weight:700;margin-top:2px;font-variant-numeric:tabular-nums;">{{ $fmt($card->total_revenue ?? 0) }} F</div>
                </div>
                <div>
                    <div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:700;color:#94A3B8;">Catégorie</div>
                    <div style="color:#0F172A;font-weight:700;margin-top:2px;">{{ \App\Models\MerchantCard::CATEGORIES[$card->category] ?? '—' }}</div>
                </div>
            </div>
            @if(!empty($card->denominations))
                <div style="margin-top:14px;">
                    <div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:700;color:#94A3B8;margin-bottom:6px;">Montants</div>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;">
                        @foreach($card->denominations as $d)
                            <span style="padding:4px 10px;background:#F1F5F9;color:#0F172A;border-radius:9999px;font-size:11px;font-weight:700;font-variant-numeric:tabular-nums;">{{ $fmt($d) }} F</span>
                        @endforeach
                    </div>
                </div>
            @endif
            @if($card->description)
                <div style="margin-top:14px;padding-top:14px;border-top:1px solid #F1F5F9;font-size:12px;color:#475569;line-height:1.5;">{{ $card->description }}</div>
            @endif
        </div>

        {{-- Commissions ----------------------------------------------------- --}}
        <div style="background:linear-gradient(135deg,#0F172A,#0F4F44);color:white;border-radius:14px;padding:18px;margin-top:14px;box-shadow:0 8px 24px -10px rgba(15,79,68,.4);">
            <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">Répartition des ventes</div>
            <div style="display:flex;flex-direction:column;gap:8px;font-size:13px;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="color:rgba(255,255,255,.7);">Commission admin</span>
                    <span style="font-weight:800;font-variant-numeric:tabular-nums;">{{ rtrim(rtrim(number_format((float) $card->admin_commission_rate, 2, '.', ''), '0'), '.') ?: 0 }} %</span>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="color:rgba(255,255,255,.7);">Commission vendeur</span>
                    <span style="font-weight:800;font-variant-numeric:tabular-nums;">{{ rtrim(rtrim(number_format((float) $card->vendor_commission_rate, 2, '.', ''), '0'), '.') ?: 0 }} %</span>
                </div>
                <div style="height:1px;background:rgba(255,255,255,.1);margin:4px 0;"></div>
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="color:#5EEAD4;font-weight:800;">Ta part</span>
                    <span style="font-weight:800;color:#5EEAD4;font-variant-numeric:tabular-nums;font-size:16px;">{{ rtrim(rtrim(number_format($ownerShareRate, 2, '.', ''), '0'), '.') ?: 0 }} %</span>
                </div>
            </div>
            @if(!$hasCommissions)
                <p style="margin:10px 0 0;font-size:11px;color:rgba(255,255,255,.5);">Tarifs définis par l'admin. Tant qu'aucune commission n'est appliquée, tu reçois 100 % de chaque vente.</p>
            @endif
        </div>
    </div>

    {{-- Acheteurs --}}
    <div style="background:white;border:1px solid #E2E8F0;border-radius:14px;overflow:hidden;">
        <div style="padding:16px 18px;border-bottom:1px solid #F1F5F9;">
            <h2 style="margin:0;font-family:'Space Grotesk','Inter',sans-serif;font-size:14px;font-weight:800;color:#0F172A;text-transform:uppercase;letter-spacing:.06em;">
                Acheteurs <span style="color:#94A3B8;font-weight:600;">({{ $purchases->total() }})</span>
            </h2>
        </div>

        @if($purchases->count() === 0)
            <div style="padding:40px 18px;text-align:center;color:#64748B;font-size:13px;">
                Aucun achat enregistré pour cette carte.
            </div>
        @else
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead style="background:#F8FAFC;">
                    <tr>
                        <th style="text-align:left;padding:10px 18px;font-size:10px;text-transform:uppercase;letter-spacing:.06em;font-weight:700;color:#64748B;">Acheteur</th>
                        <th style="text-align:left;padding:10px 18px;font-size:10px;text-transform:uppercase;letter-spacing:.06em;font-weight:700;color:#64748B;">Date</th>
                        <th style="text-align:right;padding:10px 18px;font-size:10px;text-transform:uppercase;letter-spacing:.06em;font-weight:700;color:#64748B;">Montant</th>
                        <th style="text-align:right;padding:10px 18px;font-size:10px;text-transform:uppercase;letter-spacing:.06em;font-weight:700;color:#64748B;">Net</th>
                        <th style="text-align:right;padding:10px 18px;font-size:10px;text-transform:uppercase;letter-spacing:.06em;font-weight:700;color:#64748B;">Solde</th>
                        <th style="text-align:center;padding:10px 18px;font-size:10px;text-transform:uppercase;letter-spacing:.06em;font-weight:700;color:#64748B;">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchases as $p)
                        @php
                            $statusColor = match ($p->status) {
                                'active'         => ['#D1FAE5','#065F46','Active'],
                                'partially_used' => ['#FEF3C7','#92400E','Partielle'],
                                'fully_used'     => ['#F1F5F9','#475569','Épuisée'],
                                'expired'        => ['#FEE2E2','#991B1B','Expirée'],
                                default          => ['#F1F5F9','#475569', ucfirst($p->status)],
                            };
                        @endphp
                        <tr style="border-top:1px solid #F1F5F9;">
                            <td style="padding:12px 18px;">
                                <div style="color:#0F172A;font-weight:700;">{{ $p->buyer_name }}</div>
                                <div style="font-size:11px;color:#94A3B8;">{{ $p->buyer_phone ?? $p->buyer_email ?? '—' }}</div>
                            </td>
                            <td style="padding:12px 18px;color:#475569;">{{ $p->paid_at?->translatedFormat('d M Y') }}<div style="font-size:11px;color:#94A3B8;">exp. {{ $p->expires_at?->translatedFormat('d M Y') }}</div></td>
                            <td style="padding:12px 18px;text-align:right;font-variant-numeric:tabular-nums;font-weight:700;color:#0F172A;">{{ $fmt($p->amount) }} F</td>
                            <td style="padding:12px 18px;text-align:right;font-variant-numeric:tabular-nums;font-weight:800;color:#059669;">{{ $fmt($p->owner_net_amount ?? $p->amount) }} F</td>
                            <td style="padding:12px 18px;text-align:right;font-variant-numeric:tabular-nums;color:#0F172A;font-weight:700;">{{ $fmt($p->remaining_balance) }} F</td>
                            <td style="padding:12px 18px;text-align:center;">
                                <span style="display:inline-block;padding:3px 10px;border-radius:9999px;background:{{ $statusColor[0] }};color:{{ $statusColor[1] }};font-size:10px;font-weight:700;">{{ $statusColor[2] }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="padding:14px 18px;border-top:1px solid #F1F5F9;">{{ $purchases->links() }}</div>
        @endif
    </div>
</div>
@endsection
