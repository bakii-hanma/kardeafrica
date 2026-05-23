@extends('admin.layouts.admin')

@section('title', 'Paiements')
@section('page-title', 'Gestion des paiements')

@php
    $statusInfo = [
        'pending'    => ['label' => 'En attente',  'bg' => '#FEF3C7', 'text' => '#B45309', 'border' => '#FDE68A'],
        'processing' => ['label' => 'En cours',    'bg' => '#E0F2FE', 'text' => '#0369A1', 'border' => '#BAE6FD'],
        'completed'  => ['label' => 'Complété',    'bg' => '#D1FAE5', 'text' => '#047857', 'border' => '#A7F3D0'],
        'failed'     => ['label' => 'Échoué',      'bg' => '#FFE4E6', 'text' => '#BE123C', 'border' => '#FECDD3'],
        'cancelled'  => ['label' => 'Annulé',      'bg' => '#FFE4E6', 'text' => '#BE123C', 'border' => '#FECDD3'],
        'refunded'   => ['label' => 'Remboursé',   'bg' => '#F1F5F9', 'text' => '#475569', 'border' => '#E2E8F0'],
    ];

    // Couleurs / icônes par provider/methode
    $providerStyles = [
        'mobile_money' => ['gradient' => 'linear-gradient(135deg,#FF8008,#FFC837)', 'label' => 'Mobile Money'],
        'mobilemoney'  => ['gradient' => 'linear-gradient(135deg,#FF8008,#FFC837)', 'label' => 'Mobile Money'],
        'card'         => ['gradient' => 'linear-gradient(135deg,#3B82F6,#1D4ED8)', 'label' => 'Carte bancaire'],
        'visa'         => ['gradient' => 'linear-gradient(135deg,#1A1F71,#3B82F6)', 'label' => 'Visa'],
        'mastercard'   => ['gradient' => 'linear-gradient(135deg,#EB001B,#F79E1B)', 'label' => 'Mastercard'],
        'futursowax'   => ['gradient' => 'linear-gradient(135deg,#44A08D,#4ECDC4)', 'label' => 'Futursowax'],
        'ebilling'     => ['gradient' => 'linear-gradient(135deg,#6366F1,#8B5CF6)', 'label' => 'E-Billing'],
        'simulation'   => ['gradient' => 'linear-gradient(135deg,#94A3B8,#64748B)', 'label' => 'Simulation'],
    ];

    $activeFilters = (int)!empty(request('search')) + (int)!empty(request('status'))
        + (int)!empty(request('date_from')) + (int)!empty(request('date_to'));
@endphp

@section('content')
<div style="padding:24px;font-family:'Inter','Figtree',sans-serif;">

    {{-- Stats grid --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:18px;">
        {{-- Total --}}
        <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#F8FAFC,#F1F5F9);border:1px solid #E2E8F0;display:flex;align-items:center;justify-content:center;color:#475569;flex-shrink:0;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <div style="min-width:0;">
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Total transactions</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:2px;">
                    {{ number_format($stats['total'], 0, ',', ' ') }}
                </div>
            </div>
        </div>

        {{-- Complétés --}}
        <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#D1FAE5,#A7F3D0);display:flex;align-items:center;justify-content:center;color:#047857;flex-shrink:0;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div style="min-width:0;">
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Complétés</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#047857;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:2px;">
                    {{ number_format($stats['completed'], 0, ',', ' ') }}
                </div>
            </div>
        </div>

        {{-- En attente --}}
        <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#FEF3C7,#FDE68A);display:flex;align-items:center;justify-content:center;color:#B45309;flex-shrink:0;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div style="min-width:0;">
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">En attente</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#B45309;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:2px;">
                    {{ number_format($stats['pending'], 0, ',', ' ') }}
                </div>
            </div>
        </div>

        {{-- Échoués --}}
        <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#FFE4E6,#FECDD3);display:flex;align-items:center;justify-content:center;color:#BE123C;flex-shrink:0;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <div style="min-width:0;">
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Échoués</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#BE123C;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:2px;">
                    {{ number_format($stats['failed'], 0, ',', ' ') }}
                </div>
            </div>
        </div>

        {{-- Revenu --}}
        <div style="background:linear-gradient(135deg,#0F172A,#1E293B);border-radius:14px;padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 4px 12px rgba(15,23,42,0.15);position:relative;overflow:hidden;">
            <div style="position:absolute;top:-20px;right:-20px;width:90px;height:90px;border-radius:50%;background:radial-gradient(circle,rgba(78,205,196,0.18) 0%,transparent 70%);"></div>
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#44A08D,#4ECDC4);display:flex;align-items:center;justify-content:center;color:white;flex-shrink:0;position:relative;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div style="min-width:0;position:relative;">
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:rgba(255,255,255,0.55);">Revenu encaissé</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:20px;font-weight:800;color:white;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:2px;">
                    {{ number_format($stats['revenue'], 0, ',', ' ') }}
                    <span style="font-size:11px;font-weight:600;color:rgba(255,255,255,0.55);">FCFA</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.payments.index') }}"
          style="background:white;border-radius:14px;padding:12px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);margin-bottom:18px;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:8px;align-items:center;">

        <div style="position:relative;grid-column:span 2;min-width:200px;">
            <svg style="position:absolute;left:14px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#94A3B8;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Transaction ID, client…"
                   style="width:100%;padding:10px 14px 10px 40px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;outline:none;">
        </div>

        <select name="status" onchange="this.form.submit()"
                style="padding:10px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:13px;font-weight:500;color:#334155;outline:none;cursor:pointer;">
            <option value="">Tous statuts</option>
            @foreach ($statusInfo as $key => $info)
                <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $info['label'] }}</option>
            @endforeach
        </select>

        <input type="date" name="date_from" value="{{ request('date_from') }}"
               style="padding:10px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:13px;color:#334155;outline:none;">
        <input type="date" name="date_to" value="{{ request('date_to') }}"
               style="padding:10px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:13px;color:#334155;outline:none;">

        <div style="display:flex;gap:6px;">
            <button type="submit" style="flex:1;padding:10px 18px;background:#44A08D;color:white;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;">Filtrer</button>
            @if($activeFilters > 0)
                <a href="{{ route('admin.payments.index') }}" style="padding:10px 14px;color:#64748B;font-size:12px;font-weight:600;text-decoration:none;background:#F1F5F9;border-radius:10px;display:inline-flex;align-items:center;">✕</a>
            @endif
        </div>
    </form>

    {{-- Liste paiements en cards --}}
    @if($payments->count() > 0)
        <div style="display:flex;flex-direction:column;gap:10px;">
            @foreach($payments as $payment)
                @php
                    $sInfo = $statusInfo[$payment->status] ?? ['label' => ucfirst($payment->status), 'bg' => '#F1F5F9', 'text' => '#475569', 'border' => '#E2E8F0'];

                    $providerKey = strtolower(str_replace([' ', '-'], '_', $payment->provider ?? $payment->payment_method ?? ''));
                    $pStyle = $providerStyles[$providerKey] ?? ['gradient' => 'linear-gradient(135deg,#64748B,#94A3B8)', 'label' => $payment->provider ?? $payment->payment_method ?? 'Inconnu'];

                    $txnShort = $payment->transaction_id ? Str::limit($payment->transaction_id, 22) : '—';
                @endphp

                @if($payment->order)
                    <a href="{{ route('admin.orders.show', $payment->order) }}"
                       style="background:white;border-radius:12px;border:1px solid #E2E8F0;padding:14px 16px;text-decoration:none;color:inherit;display:flex;align-items:center;gap:14px;flex-wrap:wrap;transition:all 0.15s;box-shadow:0 1px 2px rgba(15,23,42,0.04);"
                       onmouseover="this.style.borderColor='#CBD5E1';this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 12px rgba(15,23,42,0.06)';"
                       onmouseout="this.style.borderColor='#E2E8F0';this.style.transform='none';this.style.boxShadow='0 1px 2px rgba(15,23,42,0.04)';">
                @else
                    <div style="background:white;border-radius:12px;border:1px solid #E2E8F0;padding:14px 16px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
                @endif

                    {{-- Avatar provider (mini-carte) --}}
                    <div style="width:54px;height:38px;border-radius:8px;background:{{ $pStyle['gradient'] }};display:flex;align-items:center;justify-content:center;color:white;flex-shrink:0;position:relative;overflow:hidden;box-shadow:inset 0 1px 0 rgba(255,255,255,0.25);">
                        <div style="position:absolute;inset:0;background:radial-gradient(circle at 20% 20%, rgba(255,255,255,0.15) 0%, transparent 60%);"></div>
                        <svg style="width:18px;height:18px;position:relative;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>

                    {{-- Identifiants + client --}}
                    <div style="flex:1;min-width:220px;">
                        <div style="display:flex;align-items:center;gap:6px;margin-bottom:3px;flex-wrap:wrap;">
                            <span style="font-family:monospace;font-size:12px;font-weight:700;color:#0F172A;">{{ $txnShort }}</span>
                            <span style="font-size:11px;color:#94A3B8;">·</span>
                            <span style="font-size:11px;color:#64748B;">{{ $payment->created_at->format('d/m/Y H:i') }}</span>
                            @if($payment->order)
                                <span style="font-size:11px;color:#94A3B8;">·</span>
                                <span style="font-size:11px;font-weight:700;color:#44A08D;">#{{ $payment->order->order_number }}</span>
                            @endif
                        </div>
                        <div style="font-size:12px;color:#475569;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                            @if($payment->user)
                                <span style="font-weight:600;color:#334155;">{{ $payment->user->name }}</span>
                                <span style="color:#94A3B8;">·</span>
                                <span style="color:#94A3B8;font-family:monospace;font-size:11px;">{{ $payment->user->email }}</span>
                            @else
                                <span style="color:#94A3B8;">Client supprimé</span>
                            @endif
                        </div>
                        @if($payment->external_transaction_id && $payment->external_transaction_id !== $payment->transaction_id)
                            <div style="font-size:10px;color:#94A3B8;font-family:monospace;margin-top:3px;">
                                Ext : {{ Str::limit($payment->external_transaction_id, 28) }}
                            </div>
                        @endif
                    </div>

                    {{-- Provider + statut --}}
                    <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;flex-wrap:wrap;">
                        <span style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:9999px;font-size:11px;font-weight:700;background:#F1F5F9;color:#334155;border:1px solid #E2E8F0;">
                            {{ $pStyle['label'] }}
                        </span>
                        <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:9999px;font-size:11px;font-weight:700;background:{{ $sInfo['bg'] }};color:{{ $sInfo['text'] }};border:1px solid {{ $sInfo['border'] }};">
                            <span style="width:5px;height:5px;border-radius:50%;background:{{ $sInfo['text'] }};"></span>
                            {{ $sInfo['label'] }}
                        </span>
                    </div>

                    {{-- Montant --}}
                    <div style="text-align:right;flex-shrink:0;">
                        <div style="font-size:9px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;line-height:1;">Montant</div>
                        <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:16px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;margin-top:2px;line-height:1;">
                            {{ number_format((float)$payment->amount, 0, ',', ' ') }}
                            <span style="font-size:11px;font-weight:500;color:#94A3B8;">{{ $payment->currency ?? 'XAF' }}</span>
                        </div>
                    </div>

                    @if($payment->order)
                        <div style="width:32px;height:32px;border-radius:8px;background:#F8FAFC;display:flex;align-items:center;justify-content:center;color:#94A3B8;flex-shrink:0;">
                            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </a>
                @else
                    </div>
                @endif
            @endforeach
        </div>

        <div style="margin-top:18px;">
            {{ $payments->links() }}
        </div>
    @else
        <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:60px 40px;text-align:center;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="width:60px;height:60px;border-radius:14px;background:#F1F5F9;margin:0 auto 14px;display:flex;align-items:center;justify-content:center;">
                <svg style="width:26px;height:26px;color:#CBD5E1;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:16px;font-weight:700;color:#0F172A;margin-bottom:4px;">Aucun paiement trouvé</div>
            <div style="font-size:13px;color:#64748B;">{{ $activeFilters > 0 ? 'Aucun paiement ne correspond à vos filtres' : 'Aucun paiement enregistré pour le moment.' }}</div>
        </div>
    @endif
</div>
@endsection
