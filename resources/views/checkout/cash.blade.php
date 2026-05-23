@extends('layouts.app')

@section('title', 'Commande à encaisser - ' . $order->order_number)

@section('content')
@php
    use App\Models\Order;
    $isPending   = $order->payment_status === Order::PAYMENT_STATUS_PENDING;
    $isCompleted = $order->payment_status === Order::PAYMENT_STATUS_COMPLETED;
    $isCancelled = in_array($order->payment_status, [Order::PAYMENT_STATUS_CANCELLED, Order::PAYMENT_STATUS_FAILED], true);
    $expired     = $order->cash_lock_expires_at && $order->cash_lock_expires_at->isPast() && $isPending;
@endphp
<div class="min-h-screen bg-gradient-to-br from-amber-50 via-white to-slate-50 py-10 px-4"
     x-data="cashPoll({
        orderId: {{ $order->id }},
        initialStatus: @json($order->payment_status),
        ordersUrl: @json(route('orders.show', $order)),
     })" x-init="init()">

    <div class="max-w-2xl mx-auto">

        {{-- Lien retour --}}
        <a href="{{ route('orders.index') }}" class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-900 mb-4">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Mes commandes
        </a>

        {{-- ============= HEADER STATUS ============= --}}
        <div class="bg-white rounded-3xl border border-slate-200 shadow-card overflow-hidden">

            @if($isCompleted)
                {{-- État : payé --}}
                <div class="p-6 bg-gradient-to-br from-emerald-50 to-white border-b border-emerald-100 text-center">
                    <div class="inline-flex w-16 h-16 rounded-full bg-emerald-500 items-center justify-center text-white shadow-lg shadow-emerald-500/30 mb-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h1 class="font-display text-2xl font-bold text-emerald-900">Encaissement confirmé</h1>
                    <p class="text-sm text-emerald-700 mt-1">Le vendeur a validé ta commande. Les cartes te sont envoyées dans quelques secondes.</p>
                    <a href="{{ route('orders.show', $order) }}" class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold">
                        Voir mes cartes
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

            @elseif($isCancelled || $expired)
                {{-- État : annulée / expirée --}}
                <div class="p-6 bg-gradient-to-br from-rose-50 to-white border-b border-rose-100 text-center">
                    <div class="inline-flex w-16 h-16 rounded-full bg-rose-500 items-center justify-center text-white shadow-lg shadow-rose-500/30 mb-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <h1 class="font-display text-2xl font-bold text-rose-900">{{ $expired ? 'Commande expirée' : 'Commande annulée' }}</h1>
                    <p class="text-sm text-rose-700 mt-1">
                        @if($expired)
                            Le délai de 2h est dépassé. Recommence ton achat depuis le panier.
                        @else
                            Cette commande a été annulée. Le solde du vendeur a été libéré.
                        @endif
                    </p>
                    <a href="{{ route('cart.index') }}" class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-bold">
                        Repartir d'un panier
                    </a>
                </div>

            @else
                {{-- État : en attente du vendeur --}}
                <div class="p-6 bg-gradient-to-br from-amber-50 to-white border-b border-amber-100 text-center">
                    <div class="inline-flex w-16 h-16 rounded-full bg-amber-500 items-center justify-center text-white shadow-lg shadow-amber-500/30 mb-3 animate-pulse">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h1 class="font-display text-2xl font-bold text-amber-900">En attente d'encaissement</h1>
                    <p class="text-sm text-amber-800 mt-1">Va voir <strong>{{ $order->cashReseller->name ?? 'le vendeur' }}</strong>, paie cash, donne le code ci-dessous.</p>

                    {{-- Code à 6 chiffres --}}
                    <div class="mt-5 inline-block">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-amber-700 mb-1.5">Code à donner au vendeur</div>
                        <div class="px-6 py-4 rounded-2xl bg-white border-2 border-dashed border-amber-400 shadow-md">
                            <div class="font-mono text-4xl font-black tabular-nums text-slate-900 tracking-[0.3em]">{{ $order->cash_confirmation_code }}</div>
                        </div>
                    </div>

                    {{-- Compte à rebours --}}
                    <div class="mt-4 text-xs text-amber-700">
                        Expire dans <strong x-text="countdown" class="tabular-nums">{{ $order->cash_lock_expires_at?->diffForHumans() }}</strong>
                    </div>
                </div>
            @endif

            {{-- ============= INFOS VENDEUR ============= --}}
            @if($order->cashReseller)
            <div class="p-5 border-b border-slate-100 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#44A08D] to-[#4ECDC4] flex items-center justify-center text-white font-bold text-lg shrink-0">
                    {{ strtoupper(substr($order->cashReseller->name, 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Vendeur Kardafrica</div>
                    <div class="text-sm font-bold text-slate-900 mt-0.5">{{ $order->cashReseller->name }}</div>
                    <div class="text-xs text-slate-500 font-mono mt-0.5">{{ $order->cashReseller->vendor_code }}</div>
                </div>
                @if($order->cashReseller->phone)
                <a href="tel:{{ $order->cashReseller->phone }}" class="px-3 py-2 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-bold inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    Appeler
                </a>
                @endif
            </div>
            @endif

            {{-- ============= ARTICLES ============= --}}
            <div class="p-5">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Récapitulatif</h2>
                <div class="space-y-2">
                    @foreach($order->orderItems as $item)
                        <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50">
                            @if($item->image_url)
                                <img src="{{ $item->image_url }}" alt="" class="w-10 h-10 rounded-lg object-contain bg-white shrink-0">
                            @else
                                <div class="w-10 h-10 rounded-lg bg-slate-200 shrink-0"></div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold text-slate-900 truncate">{{ $item->name }}</div>
                                <div class="text-[11px] text-slate-500">×{{ $item->quantity }}</div>
                            </div>
                            <div class="font-display font-bold text-sm tabular-nums text-slate-900 shrink-0">
                                {{ number_format($item->total_price, 0, ',', ' ') }} <span class="text-[10px] text-slate-500">FCFA</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 pt-4 border-t border-slate-200 flex items-baseline justify-between">
                    <span class="text-xs uppercase tracking-wider font-bold text-slate-500">Total à payer cash</span>
                    <span class="font-display text-2xl font-black tabular-nums text-slate-900">
                        {{ number_format($order->total_amount, 0, ',', ' ') }} <span class="text-xs text-slate-500">FCFA</span>
                    </span>
                </div>
            </div>

            {{-- ============= INFO BOX ============= --}}
            @if($isPending && !$expired)
            <div class="p-5 bg-slate-50 border-t border-slate-100">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div class="text-xs text-slate-600 space-y-1.5">
                        <div><strong class="text-slate-900">1.</strong> Va voir physiquement <strong>{{ $order->cashReseller->name ?? 'le vendeur' }}</strong>.</div>
                        <div><strong class="text-slate-900">2.</strong> Donne-lui le code <span class="font-mono font-bold text-amber-700">{{ $order->cash_confirmation_code }}</span>.</div>
                        <div><strong class="text-slate-900">3.</strong> Paie <strong>{{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</strong> cash.</div>
                        <div><strong class="text-slate-900">4.</strong> Tes cartes apparaissent dans <a href="{{ route('orders.index') }}" class="text-[#44A08D] underline font-semibold">Mes commandes</a> dès qu'il valide.</div>
                    </div>
                </div>
            </div>
            @endif

        </div>

        @if($isPending && !$expired)
            <p class="text-center text-[11px] text-slate-500 mt-4">
                Cette page se met à jour automatiquement dès que le vendeur valide ton paiement.
            </p>
        @endif

    </div>
</div>

@push('scripts')
<script>
window.cashPoll = function ({ orderId, initialStatus, ordersUrl }) {
    return {
        status: initialStatus,
        countdown: '',
        timer: null,
        deadline: @json(optional($order->cash_lock_expires_at)?->toIso8601String()),

        init() {
            this.tickClock();
            setInterval(() => this.tickClock(), 1000);
            // Si pending, on poll le serveur pour détecter la confirmation du vendeur
            if (this.status === 'pending') {
                this.timer = setInterval(() => this.poll(), 5000);
            }
        },
        tickClock() {
            if (!this.deadline) { this.countdown = ''; return; }
            const ms = (new Date(this.deadline)).getTime() - Date.now();
            if (ms <= 0) { this.countdown = 'expirée'; return; }
            const m = Math.floor(ms / 60000);
            const s = Math.floor((ms % 60000) / 1000);
            this.countdown = `${m}m ${String(s).padStart(2,'0')}s`;
        },
        async poll() {
            try {
                // Pas d'endpoint dédié — on recharge la page si on détecte une transition
                // (simple : un fetch HEAD ne nous donne pas le statut, donc on fait un reload conditionnel)
                const res = await fetch(window.location.href, { headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' } });
                if (res.ok) {
                    const html = await res.text();
                    // Détecte la transition completed → on recharge
                    if (html.includes('Encaissement confirmé') || html.includes('Commande annulée') || html.includes('Commande expirée')) {
                        clearInterval(this.timer);
                        window.location.reload();
                    }
                }
            } catch (e) { /* ignore */ }
        },
    };
};
</script>
@endpush
@endsection
