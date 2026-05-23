@extends('admin.layouts.admin')

@section('title', 'Commande ' . $order->order_number)

@section('content')
@php
    use App\Models\ResellerOrder;
    $isPaid       = $order->payment_status === ResellerOrder::PAYMENT_COMPLETED;
    $hasCards     = $order->cards->count() > 0;
    $isRefunded   = $order->status === ResellerOrder::STATUS_REFUNDED;
    $needsAction  = $isPaid && !$hasCards && !$isRefunded;

    $statusMap = [
        'pending'    => ['#B45309','#FEF3C7','En attente'],
        'processing' => ['#1D4ED8','#DBEAFE','En cours'],
        'completed'  => ['#047857','#D1FAE5','Livrée'],
        'cancelled'  => ['#475569','#E2E8F0','Annulée'],
        'failed'     => ['#BE123C','#FEE2E2','Échec'],
        'refunded'   => ['#7C3AED','#EDE9FE','Remboursée'],
    ];
    $st = $statusMap[$order->status] ?? ['#475569','#E2E8F0',ucfirst($order->status)];
@endphp
<div style="max-width:1100px;margin:0 auto;padding:0 4px;">

    {{-- Breadcrumb --}}
    <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:#64748B;margin-bottom:12px;">
        <a href="{{ route('admin.resellers.index') }}" style="color:#64748B;text-decoration:none;">Vendeurs</a>
        <span>›</span>
        <a href="{{ route('admin.resellers.show', $reseller) }}" style="color:#64748B;text-decoration:none;">{{ $reseller->name }}</a>
        <span>›</span>
        <span style="color:#0F172A;font-weight:600;">#{{ $order->order_number }}</span>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div style="background:#ECFDF5;border:1px solid #A7F3D0;color:#047857;padding:10px 14px;border-radius:10px;margin-bottom:12px;font-size:13px;">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#FEF2F2;border:1px solid #FECACA;color:#BE123C;padding:10px 14px;border-radius:10px;margin-bottom:12px;font-size:13px;">
            ⚠ {{ session('error') }}
        </div>
    @endif

    {{-- ========== HEADER ========== --}}
    <div style="background:white;border-radius:16px;border:1px solid #E2E8F0;padding:20px 22px;margin-bottom:14px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.10em;color:#3B82F6;">Commande vendeur</div>
                <h1 style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#0F172A;margin:4px 0 6px;font-variant-numeric:tabular-nums;">#{{ $order->order_number }}</h1>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span style="display:inline-flex;align-items:center;padding:4px 10px;border-radius:8px;background:{{ $st[1] }};color:{{ $st[0] }};font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;">{{ $st[2] }}</span>
                    <span style="font-size:12px;color:#64748B;">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                    <span style="font-size:12px;color:#64748B;">·</span>
                    <span style="font-size:12px;color:#64748B;">Méthode : <strong style="color:#0F172A;">{{ $order->payment_method }}</strong></span>
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;color:#94A3B8;">Total</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:26px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;line-height:1;margin-top:4px;">
                    {{ number_format($order->total_amount, 0, ',', ' ') }} <span style="font-size:13px;color:#94A3B8;font-weight:600;">FCFA</span>
                </div>
                <div style="font-size:11px;color:#64748B;margin-top:3px;">commission +{{ number_format($order->commission_earned, 0, ',', ' ') }} FCFA</div>
            </div>
        </div>

        @if($order->notes)
            <div style="margin-top:12px;padding:10px 12px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;font-size:12px;color:#92400E;line-height:1.5;">
                <strong style="display:block;color:#78350F;margin-bottom:2px;">Notes</strong>
                {{ $order->notes }}
            </div>
        @endif
    </div>

    <div style="display:grid;grid-template-columns:1fr 320px;gap:14px;align-items:flex-start;">
        <div style="display:flex;flex-direction:column;gap:14px;min-width:0;">

            {{-- ========== INFOS CLIENT ========== --}}
            <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:16px 18px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
                <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;color:#94A3B8;margin-bottom:8px;">Client</div>
                <div style="font-size:14px;font-weight:700;color:#0F172A;">{{ $order->customer_name ?: 'Client anonyme' }}</div>
                @if($order->customer_phone)
                    <div style="font-size:13px;color:#64748B;font-family:monospace;margin-top:2px;">{{ $order->customer_phone }}</div>
                @endif
            </div>

            {{-- ========== ITEMS ========== --}}
            <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:16px 18px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
                <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;color:#94A3B8;margin-bottom:10px;">Cartes commandées · {{ $order->items->sum('quantity') }} unité(s)</div>
                <div style="display:flex;flex-direction:column;gap:6px;">
                    @foreach($order->items as $item)
                        <div style="display:flex;align-items:center;gap:10px;padding:8px 10px;background:#F8FAFC;border-radius:10px;">
                            @if($item->image_url)
                                <img src="{{ $item->image_url }}" alt="" style="width:36px;height:36px;border-radius:8px;object-fit:contain;background:white;border:1px solid #E2E8F0;">
                            @else
                                <div style="width:36px;height:36px;border-radius:8px;background:{{ $item->color ?? '#44A08D' }};color:white;display:flex;align-items:center;justify-content:center;font-weight:800;">
                                    {{ strtoupper(substr($item->brand ?? $item->name, 0, 1)) }}
                                </div>
                            @endif
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:13px;font-weight:700;color:#0F172A;">{{ $item->name }}</div>
                                <div style="font-size:11px;color:#64748B;margin-top:1px;">ProductId: <span style="font-family:monospace;">{{ $item->product_id }}</span></div>
                            </div>
                            <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:14px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;">
                                {{ number_format($item->total_price, 0, ',', ' ') }} <span style="font-size:9px;color:#94A3B8;">×{{ $item->quantity }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ========== CARTES LIVRÉES ========== --}}
            @if($hasCards)
                <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:16px 18px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
                    <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;color:#047857;margin-bottom:10px;">✓ Cartes livrées · {{ $order->cards->count() }}</div>
                    <div style="display:flex;flex-direction:column;gap:6px;">
                        @foreach($order->cards as $card)
                            <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:#ECFDF5;border:1px solid #A7F3D0;border-radius:10px;">
                                <div style="flex:1;min-width:0;">
                                    <div style="font-size:13px;font-weight:700;color:#0F172A;">{{ $card->name }}</div>
                                    <div style="font-size:11px;color:#475569;margin-top:2px;font-family:monospace;">
                                        Code : <strong>{{ $card->card_code }}</strong>
                                        @if($card->pin) · PIN : <strong>{{ $card->pin }}</strong>@endif
                                    </div>
                                </div>
                                <span style="font-family:'Space Grotesk','Inter',sans-serif;font-size:13px;font-weight:800;color:#047857;font-variant-numeric:tabular-nums;">
                                    {{ number_format($card->face_value, 0, ',', ' ') }}<span style="font-size:9px;color:#94A3B8;font-weight:600;margin-left:2px;">{{ $card->currency }}</span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ========== INJECTION MANUELLE ========== --}}
            @if($needsAction)
                <details style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:0;box-shadow:0 1px 2px rgba(15,23,42,0.04);overflow:hidden;">
                    <summary style="cursor:pointer;padding:14px 18px;font-size:13px;font-weight:700;color:#0F172A;background:linear-gradient(180deg,#FEF3C7,#FFFBEB);border-bottom:1px solid #FDE68A;list-style:none;display:flex;align-items:center;gap:8px;">
                        <svg style="width:14px;height:14px;color:#B45309;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Injection manuelle de codes (afrikard HS)
                        <span style="margin-left:auto;font-size:10px;font-weight:600;color:#92400E;">▼ ouvrir</span>
                    </summary>
                    <form method="POST" action="{{ route('admin.resellers.orders.inject-cards', [$reseller, $order]) }}" style="padding:16px 18px;">
                        @csrf
                        <p style="font-size:12px;color:#64748B;line-height:1.5;margin:0 0 14px;">
                            Si afrikard est définitivement HS, tu peux entrer manuellement les codes de cartes que tu as récupérés ailleurs (stock interne, contact direct fournisseur). Une fois injectées, le client pourra les récupérer via le QR code de la commande.
                        </p>

                        @php $cardIndex = 0; @endphp
                        @foreach($order->items as $item)
                            <div style="border:1px solid #E2E8F0;border-radius:10px;padding:12px;margin-bottom:10px;background:#F8FAFC;">
                                <div style="font-size:12px;font-weight:700;color:#0F172A;margin-bottom:8px;">
                                    {{ $item->name }} · {{ $item->quantity }} carte(s) à injecter
                                </div>
                                @for($i = 0; $i < $item->quantity; $i++)
                                    <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:6px;margin-bottom:6px;">
                                        <input type="hidden" name="cards[{{ $cardIndex }}][reseller_order_item_id]" value="{{ $item->id }}">
                                        <input type="text" name="cards[{{ $cardIndex }}][card_code]" placeholder="Code carte"
                                               required maxlength="255"
                                               style="padding:8px 10px;border:1px solid #CBD5E1;border-radius:8px;font-family:monospace;font-size:12px;">
                                        <input type="text" name="cards[{{ $cardIndex }}][pin]" placeholder="PIN (optionnel)"
                                               maxlength="64"
                                               style="padding:8px 10px;border:1px solid #CBD5E1;border-radius:8px;font-family:monospace;font-size:12px;">
                                        <input type="date" name="cards[{{ $cardIndex }}][expiration_date]"
                                               style="padding:8px 10px;border:1px solid #CBD5E1;border-radius:8px;font-size:12px;">
                                    </div>
                                    @php $cardIndex++; @endphp
                                @endfor
                            </div>
                        @endforeach

                        <button type="submit"
                                onclick="return confirm('Confirmer l\'injection manuelle ? La commande sera marquée comme livrée et le client pourra récupérer ses cartes.');"
                                style="width:100%;padding:12px 18px;background:linear-gradient(135deg,#B45309,#F59E0B);color:white;border:0;border-radius:10px;font-size:13px;font-weight:800;cursor:pointer;box-shadow:0 8px 18px -6px rgba(245,158,11,0.50);">
                            Injecter les cartes & livrer la commande
                        </button>
                    </form>
                </details>
            @endif

        </div>

        {{-- ========== ASIDE : ACTIONS ========== --}}
        <aside style="display:flex;flex-direction:column;gap:10px;">

            @if($needsAction)
                <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:14px 16px;">
                    <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;color:#94A3B8;margin-bottom:10px;">Actions disponibles</div>

                    <form method="POST" action="{{ route('admin.resellers.orders.retry-delivery', [$reseller, $order]) }}" style="margin:0 0 8px;">
                        @csrf
                        <button type="submit" style="width:100%;display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:11px 14px;background:#F59E0B;color:white;border:0;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">
                            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Relancer livraison
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.resellers.orders.refund', [$reseller, $order]) }}" style="margin:0;"
                          onsubmit="return confirm('Rembourser cette commande ? Le wallet du vendeur sera restauré et la commande passera en remboursée.');">
                        @csrf
                        <button type="submit" style="width:100%;display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:11px 14px;background:white;color:#BE123C;border:1px solid #FECACA;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">
                            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z"/></svg>
                            Rembourser
                        </button>
                    </form>

                    <p style="font-size:11px;color:#94A3B8;line-height:1.5;margin:10px 0 0;">
                        En dernier recours, ouvre le panneau « Injection manuelle » pour entrer des codes carte d'une autre source.
                    </p>
                </div>
            @endif

            {{-- Lien vers la commande côté vendeur (claim_token public) --}}
            <a href="{{ route('claim.show', $order->claim_token) }}" target="_blank"
               style="display:flex;align-items:center;gap:8px;padding:12px 14px;background:white;border:1px solid #E2E8F0;border-radius:12px;text-decoration:none;color:inherit;">
                <svg style="width:14px;height:14px;color:#44A08D;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:12px;font-weight:700;color:#0F172A;">Lien client (claim)</div>
                    <div style="font-size:10px;color:#94A3B8;font-family:monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $order->claim_token }}</div>
                </div>
            </a>

            {{-- Lien retour --}}
            <a href="{{ route('admin.resellers.show', $reseller) }}"
               style="display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 14px;color:#64748B;text-decoration:none;font-size:12px;border:1px solid transparent;border-radius:10px;">
                <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Retour fiche vendeur
            </a>
        </aside>
    </div>
</div>

<style>
    @media (max-width: 900px) {
        div[style*="grid-template-columns:1fr 320px"] { grid-template-columns: 1fr !important; }
    }
    summary::-webkit-details-marker { display: none; }
</style>
@endsection
