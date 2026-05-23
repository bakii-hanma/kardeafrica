@extends('layouts.app')

@section('title', 'Mes commandes - KardAfrica')

@php
    $statusMap = [
        'pending'    => ['label' => 'En attente',    'tone' => 'amber'],
        'processing' => ['label' => 'En traitement', 'tone' => 'sky'],
        'completed'  => ['label' => 'Terminée',      'tone' => 'emerald'],
        'cancelled'  => ['label' => 'Annulée',       'tone' => 'rose'],
        'failed'     => ['label' => 'Échouée',       'tone' => 'rose'],
        'shipped'    => ['label' => 'Expédiée',      'tone' => 'sky'],
        'delivered'  => ['label' => 'Livrée',        'tone' => 'emerald'],
        'refunded'   => ['label' => 'Remboursée',    'tone' => 'slate'],
    ];
    $payMap = [
        'completed'  => ['label' => 'Payé',       'tone' => 'emerald'],
        'pending'    => ['label' => 'En attente', 'tone' => 'amber'],
        'processing' => ['label' => 'En cours',   'tone' => 'sky'],
        'failed'     => ['label' => 'Échoué',     'tone' => 'rose'],
        'cancelled'  => ['label' => 'Annulé',     'tone' => 'rose'],
        'refunded'   => ['label' => 'Remboursé',  'tone' => 'slate'],
        'paid'       => ['label' => 'Payé',       'tone' => 'emerald'],
    ];
    $toneClasses = [
        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'amber'   => 'bg-amber-50 text-amber-700 border-amber-200',
        'sky'     => 'bg-sky-50 text-sky-700 border-sky-200',
        'rose'    => 'bg-rose-50 text-rose-700 border-rose-200',
        'slate'   => 'bg-slate-50 text-slate-700 border-slate-200',
    ];

    $totalSpent = $orders->sum('total_amount');
    $countCompleted = $orders->where('status', 'completed')->count();
    $countPending = $orders->whereIn('status', ['pending', 'processing'])->count();
@endphp

@section('content')
<div class="min-h-screen bg-[#FAFAF7] pb-20">

    {{-- BREADCRUMB --}}
    <div class="bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between">
            <nav class="flex items-center gap-1.5 text-xs text-slate-500">
                <a href="{{ route('home') }}" class="hover:text-[#44A08D] transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Accueil
                </a>
                <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-900 font-medium">Mes commandes</span>
            </nav>
            <a href="{{ route('cards.index') }}" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-teal-50 text-[#44A08D] hover:bg-teal-100 text-xs font-semibold transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Mes cartes
            </a>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 md:pt-10">

        {{-- HEADER + STATS --}}
        <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-4 md:items-end mb-6">
            <div>
                <h1 class="font-display text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">Mes commandes</h1>
                <p class="text-sm text-slate-500 mt-1.5">Historique complet de vos achats sur KardAfrica.</p>
            </div>
            <a href="{{ route('boutique') }}"
               class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-[#44A08D] hover:bg-[#3d9180] text-white text-sm font-semibold shadow-lg shadow-[#44A08D]/25 active:scale-95 transition shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Nouvelle commande
            </a>
        </div>

        {{-- Stats compactes --}}
        @if($orders->total() > 0)
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-card p-4">
                    <div class="flex items-center gap-2.5 mb-1.5">
                        <div class="w-8 h-8 rounded-lg bg-teal-50 text-[#44A08D] flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Total</span>
                    </div>
                    <div class="font-display text-2xl font-bold text-slate-900 tabular-nums">{{ $orders->total() }}</div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 shadow-card p-4">
                    <div class="flex items-center gap-2.5 mb-1.5">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Terminées</span>
                    </div>
                    <div class="font-display text-2xl font-bold text-slate-900 tabular-nums">{{ $countCompleted }}</div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 shadow-card p-4 col-span-2 md:col-span-1">
                    <div class="flex items-center gap-2.5 mb-1.5">
                        <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
                        </div>
                        <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Total dépensé</span>
                    </div>
                    <div class="font-display text-base md:text-lg font-bold text-slate-900 tabular-nums leading-tight">
                        {{ number_format($totalSpent, 0, ',', ' ') }} <span class="text-xs text-slate-400 font-normal">FCFA</span>
                    </div>
                </div>
            </div>
        @endif

        {{-- LISTE DES COMMANDES --}}
        @if($orders->count() > 0)
            <div class="space-y-3">
                @foreach($orders as $i => $order)
                    @php
                        $statusInfo = $statusMap[$order->status] ?? ['label' => ucfirst($order->status), 'tone' => 'slate'];
                        $payInfo    = $payMap[$order->payment_status] ?? ['label' => ucfirst($order->payment_status), 'tone' => 'slate'];
                        $itemsCount = $order->orderItems->count();
                    @endphp

                    @php
                        $orderItemsCount = $order->orderItems->count();
                        $userCardsCount  = $order->userCards->count();
                        $needsRetry      = $order->payment_status === 'completed' && $userCardsCount === 0;
                    @endphp
                    <a href="{{ route('orders.show', $order) }}"
                       class="order-row block bg-white rounded-2xl border {{ $needsRetry ? 'border-amber-300 ring-1 ring-amber-100' : 'border-slate-200' }} shadow-card hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-300"
                       style="animation-delay: {{ ($i % 12) * 50 }}ms">

                        <div class="grid grid-cols-1 md:grid-cols-[auto_1fr_auto] gap-3 md:gap-5 items-center p-4 md:p-5">

                            {{-- Numéro + date (gauche) --}}
                            <div class="flex items-center gap-3 md:min-w-[200px]">
                                <div class="shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-mono text-xs text-slate-400 leading-none">
                                        Commande
                                    </div>
                                    <div class="font-mono font-bold text-sm text-slate-900 mt-1 truncate">
                                        #{{ $order->order_number ?? $order->id }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-1">
                                        {{ $order->created_at->translatedFormat('d M Y · H:i') }}
                                    </div>
                                </div>
                            </div>

                            {{-- Articles + status (centre) --}}
                            <div class="flex flex-col md:flex-row md:items-center gap-2 md:gap-4 min-w-0">
                                {{-- Mini avatar stack des items --}}
                                <div class="flex items-center -space-x-2 shrink-0">
                                    @foreach($order->orderItems->take(3) as $item)
                                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-slate-100 to-slate-200 border-2 border-white flex items-center justify-center overflow-hidden ring-1 ring-slate-200">
                                            @if($item->image_url)
                                                <img src="{{ $item->image_url }}" alt="" class="w-full h-full object-cover">
                                            @else
                                                <span class="text-[10px] font-bold text-slate-500">{{ strtoupper(substr($item->name, 0, 1)) }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                    @if($itemsCount > 3)
                                        <div class="w-8 h-8 rounded-lg bg-slate-100 border-2 border-white flex items-center justify-center text-[10px] font-bold text-slate-600">
                                            +{{ $itemsCount - 3 }}
                                        </div>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-semibold text-slate-900 truncate">
                                        {{ $order->orderItems->first()->name ?? 'Commande' }}
                                        @if($itemsCount > 1)
                                            <span class="text-slate-400 font-normal">+ {{ $itemsCount - 1 }} autre{{ $itemsCount - 1 > 1 ? 's' : '' }}</span>
                                        @endif
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md border text-[10px] font-bold {{ $toneClasses[$statusInfo['tone']] }}">
                                            <span class="w-1 h-1 rounded-full bg-current"></span>
                                            {{ $statusInfo['label'] }}
                                        </span>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md border text-[10px] font-bold {{ $toneClasses[$payInfo['tone']] }}">
                                            {{ $payInfo['label'] }}
                                        </span>
                                        <span class="text-[10px] text-slate-400">· {{ $itemsCount }} article{{ $itemsCount > 1 ? 's' : '' }}</span>
                                        @if($needsRetry)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 border border-amber-200 text-amber-700 text-[10px] font-bold">
                                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                À relancer
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Total + chevron (droite) --}}
                            <div class="flex items-center justify-between md:justify-end gap-4 shrink-0">
                                <div class="text-right">
                                    <div class="text-[10px] uppercase tracking-wider text-slate-400 font-bold leading-none">Total</div>
                                    <div class="font-display text-base md:text-lg font-black text-slate-900 tabular-nums mt-1 leading-none">
                                        {{ number_format($order->total_amount, 0, ',', ' ') }} <span class="text-xs font-normal text-slate-400">{{ $order->currency ?? 'FCFA' }}</span>
                                    </div>
                                </div>
                                @if($needsRetry)
                                    <button type="button"
                                            onclick="event.preventDefault(); event.stopPropagation(); document.getElementById('retry-form-{{ $order->id }}').submit();"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-[11px] font-bold transition active:scale-95 shrink-0">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        Relancer
                                    </button>
                                @endif
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-50 text-slate-400 group-hover:bg-[#44A08D] group-hover:text-white transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </span>
                            </div>
                        </div>
                    </a>
                    @if($needsRetry)
                        <form id="retry-form-{{ $order->id }}" action="{{ route('orders.retry-checkout', $order) }}" method="POST" class="hidden">
                            @csrf
                        </form>
                    @endif
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($orders->hasPages())
                <div class="mt-8">
                    {{ $orders->links() }}
                </div>
            @endif

        @else
            {{-- Empty state --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-card text-center py-16 px-6">
                <div class="w-20 h-20 mx-auto mb-5 rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center">
                    <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <h3 class="font-display text-xl font-bold text-slate-900 mb-2">Aucune commande pour le moment</h3>
                <p class="text-sm text-slate-500 max-w-md mx-auto mb-6">
                    Vous n'avez pas encore passé de commande. Découvrez plus de 300 marques de cartes cadeaux numériques.
                </p>
                <a href="{{ route('boutique') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#44A08D] hover:bg-[#3d9180] text-white text-sm font-semibold shadow-lg shadow-[#44A08D]/25 active:scale-95 transition">
                    Découvrir le catalogue
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        @endif
    </div>
</div>

<style>
    .order-row {
        opacity: 0;
        transform: translateY(12px);
        animation: order-row-in 400ms cubic-bezier(0.22, 1, 0.36, 1) forwards;
    }
    @keyframes order-row-in {
        to { opacity: 1; transform: translateY(0); }
    }
    @media (prefers-reduced-motion: reduce) {
        .order-row { animation: none; opacity: 1; transform: none; }
    }
</style>
@endsection
