@extends('owner.layouts.owner')

@section('title', 'Historique')
@section('page-title', 'Historique des validations')
@section('page-subtitle', 'Toutes les cartes validées au comptoir')

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', ' ');
@endphp

@if($redemptions->count() === 0)
    <div style="background:white;border:2px dashed #CBD5E1;border-radius:14px;padding:48px 24px;text-align:center;">
        <div style="font-size:48px;margin-bottom:8px;">📋</div>
        <h3 style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:800;color:#0F172A;margin:0 0 4px;">Aucune validation pour le moment</h3>
        <p style="color:#64748B;font-size:13px;margin:0 0 14px;">Quand tu valideras une carte au comptoir via Scanner, elle apparaîtra ici.</p>
        <a href="{{ route('owner.scan') }}" style="display:inline-flex;align-items:center;gap:6px;padding:10px 16px;background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;">
            Scanner une carte →
        </a>
    </div>
@else
    <div style="background:white;border:1px solid #E2E8F0;border-radius:14px;overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead style="background:#F8FAFC;">
                <tr>
                    <th style="text-align:left;padding:12px 18px;font-size:10px;text-transform:uppercase;letter-spacing:.06em;font-weight:700;color:#64748B;">Date</th>
                    <th style="text-align:left;padding:12px 18px;font-size:10px;text-transform:uppercase;letter-spacing:.06em;font-weight:700;color:#64748B;">Carte</th>
                    <th style="text-align:right;padding:12px 18px;font-size:10px;text-transform:uppercase;letter-spacing:.06em;font-weight:700;color:#64748B;">Débité</th>
                    <th style="text-align:right;padding:12px 18px;font-size:10px;text-transform:uppercase;letter-spacing:.06em;font-weight:700;color:#64748B;">Solde après</th>
                    <th style="text-align:center;padding:12px 18px;font-size:10px;text-transform:uppercase;letter-spacing:.06em;font-weight:700;color:#64748B;">Méthode</th>
                </tr>
            </thead>
            <tbody>
                @foreach($redemptions as $r)
                    <tr style="border-top:1px solid #F1F5F9;">
                        <td style="padding:12px 18px;color:#475569;">{{ $r->redeemed_at?->translatedFormat('d M Y H:i') }}</td>
                        <td style="padding:12px 18px;color:#0F172A;font-weight:700;">{{ $r->purchase->merchantCard?->name ?? '—' }}</td>
                        <td style="padding:12px 18px;text-align:right;font-variant-numeric:tabular-nums;font-weight:700;color:#DC2626;">-{{ $fmt($r->amount_used) }} F</td>
                        <td style="padding:12px 18px;text-align:right;font-variant-numeric:tabular-nums;color:#0F172A;">{{ $fmt($r->balance_after) }} F</td>
                        <td style="padding:12px 18px;text-align:center;">
                            <span style="display:inline-block;padding:3px 10px;border-radius:9999px;background:#F1F5F9;color:#475569;font-size:10px;font-weight:700;">{{ $r->scan_method === 'qr' ? 'QR' : 'Code' }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:14px 18px;border-top:1px solid #F1F5F9;">{{ $redemptions->links() }}</div>
    </div>
@endif
@endsection
