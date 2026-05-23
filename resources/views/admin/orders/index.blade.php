@extends('admin.layouts.admin')

@section('title', 'Commandes')
@section('page-title', 'Commandes')

@php
    $statusInfo = [
        'pending'    => ['label' => 'En attente',    'bg' => '#FEF3C7', 'text' => '#B45309', 'border' => '#FDE68A'],
        'processing' => ['label' => 'En traitement', 'bg' => '#E0F2FE', 'text' => '#0369A1', 'border' => '#BAE6FD'],
        'completed'  => ['label' => 'Terminée',      'bg' => '#D1FAE5', 'text' => '#047857', 'border' => '#A7F3D0'],
        'cancelled'  => ['label' => 'Annulée',       'bg' => '#FFE4E6', 'text' => '#BE123C', 'border' => '#FECDD3'],
        'failed'     => ['label' => 'Échouée',       'bg' => '#FFE4E6', 'text' => '#BE123C', 'border' => '#FECDD3'],
        'refunded'   => ['label' => 'Remboursée',    'bg' => '#F1F5F9', 'text' => '#475569', 'border' => '#E2E8F0'],
    ];
    $payInfo = [
        'completed'  => ['label' => 'Payé',       'bg' => '#D1FAE5', 'text' => '#047857', 'border' => '#A7F3D0'],
        'paid'       => ['label' => 'Payé',       'bg' => '#D1FAE5', 'text' => '#047857', 'border' => '#A7F3D0'],
        'pending'    => ['label' => 'En attente', 'bg' => '#FEF3C7', 'text' => '#B45309', 'border' => '#FDE68A'],
        'processing' => ['label' => 'En cours',   'bg' => '#E0F2FE', 'text' => '#0369A1', 'border' => '#BAE6FD'],
        'failed'     => ['label' => 'Échoué',     'bg' => '#FFE4E6', 'text' => '#BE123C', 'border' => '#FECDD3'],
    ];
    $activeFilters = (int)!empty(request('search')) + (int)!empty(request('status'))
        + (int)!empty(request('payment_status')) + (int)!empty(request('date_from')) + (int)!empty(request('date_to'));
@endphp

@section('content')
<div style="padding:24px;font-family:'Inter','Figtree',sans-serif;">

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.orders.index') }}"
          style="background:white;border-radius:14px;padding:12px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);margin-bottom:18px;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:8px;align-items:center;">

        <div style="position:relative;grid-column:span 2;min-width:200px;">
            <svg style="position:absolute;left:14px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#94A3B8;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="N° commande, nom, email…"
                   style="width:100%;padding:10px 14px 10px 40px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;outline:none;">
        </div>

        <select name="status" onchange="this.form.submit()"
                style="padding:10px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:13px;font-weight:500;color:#334155;outline:none;cursor:pointer;">
            <option value="">Tous statuts</option>
            @foreach ($statusInfo as $key => $info)
                <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $info['label'] }}</option>
            @endforeach
        </select>

        <select name="payment_status" onchange="this.form.submit()"
                style="padding:10px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:13px;font-weight:500;color:#334155;outline:none;cursor:pointer;">
            <option value="">Tous paiements</option>
            <option value="completed"  {{ request('payment_status') === 'completed'  ? 'selected' : '' }}>Payé</option>
            <option value="pending"    {{ request('payment_status') === 'pending'    ? 'selected' : '' }}>En attente</option>
            <option value="processing" {{ request('payment_status') === 'processing' ? 'selected' : '' }}>En cours</option>
            <option value="failed"     {{ request('payment_status') === 'failed'     ? 'selected' : '' }}>Échoué</option>
        </select>

        <input type="date" name="date_from" value="{{ request('date_from') }}"
               style="padding:10px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:13px;color:#334155;outline:none;">
        <input type="date" name="date_to" value="{{ request('date_to') }}"
               style="padding:10px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:13px;color:#334155;outline:none;">

        <div style="display:flex;gap:6px;">
            <button type="submit" style="flex:1;padding:10px 18px;background:#44A08D;color:white;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;">Filtrer</button>
            @if($activeFilters > 0)
                <a href="{{ route('admin.orders.index') }}" style="padding:10px 14px;color:#64748B;font-size:12px;font-weight:600;text-decoration:none;background:#F1F5F9;border-radius:10px;display:inline-flex;align-items:center;">✕</a>
            @endif
        </div>
    </form>

    {{-- Liste commandes en cards --}}
    @if($orders->count() > 0)
        <div style="display:flex;flex-direction:column;gap:10px;">
            @foreach($orders as $order)
                @php
                    $sInfo = $statusInfo[$order->status] ?? ['label' => $order->status, 'bg' => '#F1F5F9', 'text' => '#475569', 'border' => '#E2E8F0'];
                    $pInfo = $payInfo[$order->payment_status] ?? ['label' => $order->payment_status, 'bg' => '#F1F5F9', 'text' => '#475569', 'border' => '#E2E8F0'];
                    $itemsCount = $order->orderItems_count ?? $order->orderItems->count();
                @endphp
                <a href="{{ route('admin.orders.show', $order) }}"
                   style="background:white;border-radius:12px;border:1px solid #E2E8F0;padding:14px 16px;text-decoration:none;color:inherit;display:flex;align-items:center;gap:14px;flex-wrap:wrap;transition:all 0.15s;box-shadow:0 1px 2px rgba(15,23,42,0.04);"
                   onmouseover="this.style.borderColor='#CBD5E1';this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 12px rgba(15,23,42,0.06)';"
                   onmouseout="this.style.borderColor='#E2E8F0';this.style.transform='none';this.style.boxShadow='0 1px 2px rgba(15,23,42,0.04)';">

                    <div style="width:42px;height:42px;border-radius:11px;background:linear-gradient(135deg,#F8FAFC,#F1F5F9);border:1px solid #E2E8F0;display:flex;align-items:center;justify-content:center;color:#94A3B8;flex-shrink:0;">
                        <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>

                    <div style="flex:1;min-width:200px;">
                        <div style="display:flex;align-items:center;gap:6px;margin-bottom:2px;">
                            <span style="font-family:monospace;font-size:13px;font-weight:700;color:#0F172A;">#{{ $order->order_number }}</span>
                            <span style="font-size:11px;color:#94A3B8;">·</span>
                            <span style="font-size:11px;color:#64748B;">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div style="font-size:12px;color:#475569;display:flex;align-items:center;gap:6px;">
                            @if($order->user)
                                <span style="font-weight:600;color:#334155;">{{ $order->user->name }}</span>
                                <span style="color:#94A3B8;">·</span>
                                <span style="color:#94A3B8;font-family:monospace;font-size:11px;">{{ $order->user->email }}</span>
                            @else
                                <span style="color:#94A3B8;">N/A</span>
                            @endif
                        </div>
                    </div>

                    <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                        <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:9999px;font-size:11px;font-weight:700;background:{{ $sInfo['bg'] }};color:{{ $sInfo['text'] }};border:1px solid {{ $sInfo['border'] }};">
                            <span style="width:5px;height:5px;border-radius:50%;background:{{ $sInfo['text'] }};"></span>
                            {{ $sInfo['label'] }}
                        </span>
                        <span style="display:inline-flex;padding:3px 8px;border-radius:9999px;font-size:11px;font-weight:700;background:{{ $pInfo['bg'] }};color:{{ $pInfo['text'] }};border:1px solid {{ $pInfo['border'] }};">
                            {{ $pInfo['label'] }}
                        </span>
                    </div>

                    <div style="text-align:right;flex-shrink:0;">
                        <div style="font-size:9px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;line-height:1;">Total</div>
                        <div style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:16px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;margin-top:2px;line-height:1;">
                            {{ number_format($order->total_amount, 0, ',', ' ') }}
                            <span style="font-size:11px;font-weight:500;color:#94A3B8;">FCFA</span>
                        </div>
                    </div>

                    <div style="width:32px;height:32px;border-radius:8px;background:#F8FAFC;display:flex;align-items:center;justify-content:center;color:#94A3B8;flex-shrink:0;">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>
            @endforeach
        </div>

        <div style="margin-top:18px;">
            {{ $orders->links() }}
        </div>
    @else
        <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:60px 40px;text-align:center;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="width:60px;height:60px;border-radius:14px;background:#F1F5F9;margin:0 auto 14px;display:flex;align-items:center;justify-content:center;">
                <svg style="width:26px;height:26px;color:#CBD5E1;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:16px;font-weight:700;color:#0F172A;margin-bottom:4px;">Aucune commande trouvée</div>
            <div style="font-size:13px;color:#64748B;">{{ $activeFilters > 0 ? 'Aucune commande ne correspond à vos filtres' : 'Aucune commande passée pour le moment.' }}</div>
        </div>
    @endif
</div>
@endsection
