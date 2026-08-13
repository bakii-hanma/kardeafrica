@extends('admin.layouts.admin')

@section('title', 'Ventes revendeurs — cartes locales')
@section('page-title', 'Ventes revendeurs de cartes locales')

@section('content')
<div style="padding:24px;font-family:'Inter','Figtree',sans-serif;">

    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
        <p style="font-size:13px;color:#64748B;margin:0;">
            Une vente revendeur naît <strong>inactive</strong> (code inerte). « Récupérée » = le revendeur a été débité
            (montant − commission 4,5 %) et le code est actif — <strong>garantie que la carte a été vendue</strong>.
        </p>
        <a href="{{ route('admin.merchant-cards.index') }}" style="font-size:13px;font-weight:700;color:#44A08D;text-decoration:none;white-space:nowrap;">← Cartes locales</a>
    </div>

    {{-- ============ STATS / FILTRES ============ --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:18px;">
        <a href="{{ route('admin.merchant-cards.reseller-sales') }}"
           style="text-decoration:none;background:white;border-radius:14px;border:1px solid {{ $status === '' ? '#44A08D' : '#E2E8F0' }};padding:16px 18px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Total ventes</div>
            <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#0F172A;margin-top:2px;">{{ $stats['total'] }}</div>
        </a>

        <a href="{{ route('admin.merchant-cards.reseller-sales', ['status' => 'pending']) }}"
           style="text-decoration:none;background:white;border-radius:14px;border:1px solid {{ $status === 'pending' ? '#F59E0B' : '#E2E8F0' }};padding:16px 18px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">À récupérer</div>
            <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#B45309;margin-top:2px;">{{ $stats['pending'] }}</div>
        </a>

        <a href="{{ route('admin.merchant-cards.reseller-sales', ['status' => 'claimed']) }}"
           style="text-decoration:none;background:white;border-radius:14px;border:1px solid {{ $status === 'claimed' ? '#10B981' : '#E2E8F0' }};padding:16px 18px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Récupérées</div>
            <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#047857;margin-top:2px;">{{ $stats['claimed'] }}</div>
        </a>

        <a href="{{ route('admin.merchant-cards.reseller-sales', ['status' => 'cancelled']) }}"
           style="text-decoration:none;background:white;border-radius:14px;border:1px solid {{ $status === 'cancelled' ? '#EF4444' : '#E2E8F0' }};padding:16px 18px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Annulées</div>
            <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#B91C1C;margin-top:2px;">{{ $stats['cancelled'] }}</div>
        </a>

        <div style="background:linear-gradient(135deg,#0F172A,#134E4A);border-radius:14px;padding:16px 18px;">
            <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:rgba(255,255,255,0.6);">Volume encaissé</div>
            <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:800;color:white;margin-top:2px;">{{ number_format($stats['volume'], 0, ',', ' ') }} FCFA</div>
            <div style="font-size:11px;color:#5EEAD4;margin-top:2px;">dont commissions revendeurs : {{ number_format($stats['commission'], 0, ',', ' ') }} FCFA</div>
        </div>
    </div>

    {{-- ============ TABLE ============ --}}
    @if ($sales->isEmpty())
        <div style="background:white;border:1px solid #E2E8F0;border-radius:14px;padding:40px;text-align:center;color:#64748B;font-size:14px;">
            Aucune vente revendeur {{ $status ? 'dans ce statut' : 'pour le moment' }}.
        </div>
    @else
        <div style="background:white;border:1px solid #E2E8F0;border-radius:14px;overflow:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;min-width:900px;">
                <thead>
                    <tr style="background:#F8FAFC;color:#64748B;text-align:left;">
                        <th style="padding:11px 14px;">#</th>
                        <th style="padding:11px 14px;">Carte</th>
                        <th style="padding:11px 14px;">Propriétaire</th>
                        <th style="padding:11px 14px;">Revendeur</th>
                        <th style="padding:11px 14px;text-align:right;">Montant</th>
                        <th style="padding:11px 14px;text-align:right;">Comm. rev.</th>
                        <th style="padding:11px 14px;text-align:right;">Encaissé KA</th>
                        <th style="padding:11px 14px;">Statut</th>
                        <th style="padding:11px 14px;">Réservée</th>
                        <th style="padding:11px 14px;">Récupérée</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sales as $sale)
                        @php
                            $claimed = $sale->sold_by_reseller_at !== null;
                            [$label, $bg, $fg] = match (true) {
                                $sale->status === 'cancelled' => ['Annulée', '#FEE2E2', '#B91C1C'],
                                $claimed && $sale->status === 'fully_used' => ['Récupérée · épuisée', '#E2E8F0', '#475569'],
                                $claimed && $sale->status === 'partially_used' => ['Récupérée · en cours', '#DBEAFE', '#1D4ED8'],
                                $claimed => ['Récupérée · active', '#D1FAE5', '#047857'],
                                default => ['À récupérer', '#FEF3C7', '#B45309'],
                            };
                            $due = round((float) $sale->amount - (float) $sale->vendor_commission_amount, 2);
                        @endphp
                        <tr style="border-top:1px solid #F1F5F9;">
                            <td style="padding:10px 14px;color:#94A3B8;">{{ $sale->id }}</td>
                            <td style="padding:10px 14px;font-weight:600;color:#0F172A;">{{ $sale->merchantCard?->name ?? '—' }}</td>
                            <td style="padding:10px 14px;color:#475569;">{{ $sale->merchantCard?->owner?->business_name ?? '—' }}</td>
                            <td style="padding:10px 14px;color:#475569;">
                                {{ $sale->reseller?->name ?? '—' }}
                                @if ($sale->reseller?->vendor_code)<span style="color:#94A3B8;font-size:11px;"> · {{ $sale->reseller->vendor_code }}</span>@endif
                            </td>
                            <td style="padding:10px 14px;text-align:right;font-variant-numeric:tabular-nums;font-weight:700;">{{ number_format((float) $sale->amount, 0, ',', ' ') }}</td>
                            <td style="padding:10px 14px;text-align:right;font-variant-numeric:tabular-nums;color:#047857;">{{ number_format((float) $sale->vendor_commission_amount, 0, ',', ' ') }}</td>
                            <td style="padding:10px 14px;text-align:right;font-variant-numeric:tabular-nums;color:{{ $claimed ? '#0F172A' : '#94A3B8' }};">
                                {{ $claimed ? number_format($due, 0, ',', ' ') : '—' }}
                            </td>
                            <td style="padding:10px 14px;">
                                <span style="background:{{ $bg }};color:{{ $fg }};border-radius:999px;padding:3px 10px;font-size:11px;font-weight:700;white-space:nowrap;">{{ $label }}</span>
                            </td>
                            <td style="padding:10px 14px;color:#64748B;white-space:nowrap;">{{ $sale->created_at->format('d/m H:i') }}</td>
                            <td style="padding:10px 14px;color:#64748B;white-space:nowrap;">{{ $sale->sold_by_reseller_at?->format('d/m H:i') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top:16px;">{{ $sales->links() }}</div>
    @endif
</div>
@endsection
