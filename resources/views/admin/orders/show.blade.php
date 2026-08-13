@extends('admin.layouts.admin')

@section('title', 'Commande ' . $order->order_number)
@section('page-title', 'Commande #' . $order->order_number)

@php
    $brandPalette = [
        'Netflix' => '#E50914', 'Spotify' => '#1DB954', 'Apple' => '#000000',
        'iTunes' => '#D60017', 'PlayStation' => '#003791', 'Xbox' => '#107C10',
        'Amazon' => '#FF9900', 'Google' => '#01875F', 'Steam' => '#171A21',
        'Roblox' => '#00A2FF', 'Nintendo' => '#E60012', 'Disney' => '#0E47A1',
        'StarzPlay' => '#7C3AED', 'Talabat' => '#FF5A00', 'HUAWEI' => '#C7000B', 'IKEA' => '#0058A3',
    ];
    $brandColorFor = function ($name) use ($brandPalette) {
        if (!$name) return '#1F2937';
        foreach ($brandPalette as $key => $color) {
            if (stripos($name, $key) !== false) return $color;
        }
        $palette = ['#0F172A', '#44A08D', '#0EA5E9', '#7C3AED', '#DC2626', '#EA580C', '#059669'];
        $hash = 0;
        for ($i = 0; $i < strlen($name); $i++) $hash = (ord($name[$i]) + (($hash << 5) - $hash)) & 0x7FFFFFFF;
        return $palette[(($hash % count($palette)) + count($palette)) % count($palette)];
    };

    $statusInfo = [
        'pending' => ['label' => 'En attente', 'bg' => '#FEF3C7', 'text' => '#B45309', 'border' => '#FDE68A'],
        'processing' => ['label' => 'En traitement', 'bg' => '#E0F2FE', 'text' => '#0369A1', 'border' => '#BAE6FD'],
        'completed' => ['label' => 'Terminée', 'bg' => '#D1FAE5', 'text' => '#047857', 'border' => '#A7F3D0'],
        'cancelled' => ['label' => 'Annulée', 'bg' => '#FFE4E6', 'text' => '#BE123C', 'border' => '#FECDD3'],
        'failed' => ['label' => 'Échouée', 'bg' => '#FFE4E6', 'text' => '#BE123C', 'border' => '#FECDD3'],
    ];
    $payInfo = [
        'completed' => ['label' => 'Payé', 'bg' => '#D1FAE5', 'text' => '#047857', 'border' => '#A7F3D0'],
        'paid' => ['label' => 'Payé', 'bg' => '#D1FAE5', 'text' => '#047857', 'border' => '#A7F3D0'],
        'pending' => ['label' => 'En attente', 'bg' => '#FEF3C7', 'text' => '#B45309', 'border' => '#FDE68A'],
        'failed' => ['label' => 'Échoué', 'bg' => '#FFE4E6', 'text' => '#BE123C', 'border' => '#FECDD3'],
    ];
    $sInfo = $statusInfo[$order->status] ?? ['label' => $order->status, 'bg' => '#F1F5F9', 'text' => '#475569', 'border' => '#E2E8F0'];
    $pInfo = $payInfo[$order->payment_status] ?? ['label' => $order->payment_status, 'bg' => '#F1F5F9', 'text' => '#475569', 'border' => '#E2E8F0'];
    $showRetry = $order->payment_status === 'completed' && $order->userCards->isEmpty();
@endphp

@section('content')
<div style="padding:24px;font-family:'Inter','Figtree',sans-serif;">

    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:18px;">
        <a href="{{ route('admin.orders.index') }}"
           style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:white;border:1px solid #E2E8F0;border-radius:9px;color:#475569;text-decoration:none;font-size:13px;font-weight:600;">
            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Toutes les commandes
        </a>
        <div style="display:flex;align-items:center;gap:6px;">
            <span style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border-radius:9999px;font-size:12px;font-weight:700;background:{{ $sInfo['bg'] }};color:{{ $sInfo['text'] }};border:1px solid {{ $sInfo['border'] }};">
                <span style="width:6px;height:6px;border-radius:50%;background:{{ $sInfo['text'] }};"></span>
                {{ $sInfo['label'] }}
            </span>
            <span style="display:inline-flex;padding:5px 10px;border-radius:9999px;font-size:12px;font-weight:700;background:{{ $pInfo['bg'] }};color:{{ $pInfo['text'] }};border:1px solid {{ $pInfo['border'] }};">
                {{ $pInfo['label'] }}
            </span>
        </div>
    </div>

    {{-- Flash messages : gérés en modal global via le layout --}}

    @if($showRetry)
        <div style="background:linear-gradient(135deg,#FEF3C7,#FDE68A);border:1px solid #FBBF24;border-radius:14px;padding:14px 18px;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:200px;">
                <div style="width:36px;height:36px;border-radius:10px;background:#F59E0B;color:white;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <div style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:14px;font-weight:700;color:#78350F;">Cartes non livrées</div>
                    <div style="font-size:12px;color:#92400E;margin-top:2px;">Le paiement est confirmé mais l'API afrikard n'a pas livré les cartes.</div>
                </div>
            </div>
            <div x-data="{ refundModal: false }" style="display:flex;gap:8px;flex-wrap:wrap;">
                <form action="{{ route('admin.orders.retry', $order) }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit"
                            style="display:inline-flex;align-items:center;gap:6px;padding:9px 14px;background:#F59E0B;color:white;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;"
                            onmouseover="this.style.background='#D97706';" onmouseout="this.style.background='#F59E0B';">
                        <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Relancer
                    </button>
                </form>
                <button type="button" @click="refundModal = true"
                        style="display:inline-flex;align-items:center;gap:6px;padding:9px 14px;background:white;color:#BE123C;border:1px solid #FECACA;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;"
                        onmouseover="this.style.background='#FEF2F2';" onmouseout="this.style.background='white';">
                    <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z"/></svg>
                    Rembourser
                </button>

                {{-- Modal remboursement --}}
                <div x-show="refundModal" x-cloak
                     style="position:fixed;inset:0;z-index:100;background:rgba(15,23,42,0.55);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;padding:16px;"
                     @click.self="refundModal = false"
                     @keydown.escape.window="refundModal = false">
                    <div x-show="refundModal" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         style="position:relative;width:100%;max-width:460px;background:white;border-radius:22px;overflow:hidden;box-shadow:0 28px 60px -16px rgba(15,23,42,0.50);">
                        <div style="position:relative;padding:22px;background:linear-gradient(135deg,#BE123C,#F43F5E,#FB7185);color:white;display:flex;align-items:center;gap:14px;overflow:hidden;">
                            <div style="position:absolute;top:-60px;right:-60px;width:220px;height:220px;border-radius:50%;background:radial-gradient(circle,rgba(255,255,255,0.30) 0%,transparent 70%);pointer-events:none;"></div>
                            <div style="position:relative;width:48px;height:48px;border-radius:14px;background:rgba(255,255,255,0.22);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg style="width:22px;height:22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z"/></svg>
                            </div>
                            <div style="position:relative;">
                                <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.10em;opacity:0.95;">Remboursement admin</div>
                                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:800;line-height:1.15;margin-top:2px;">Rembourser cette commande&nbsp;?</div>
                            </div>
                        </div>
                        <div style="padding:18px 22px;font-size:14px;color:#475569;line-height:1.5;">
                            <p style="margin:0 0 10px;">Montant : <strong style="color:#0F172A;font-weight:800;">{{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</strong></p>
                            <p style="margin:0;font-size:12px;color:#64748B;">
                                @if($order->payment_method === 'ebilling')
                                    Appel API E-Billing transfer.php → renvoie le montant au moyen de paiement initial.
                                @elseif($order->payment_method === \App\Models\Order::PAYMENT_METHOD_CASH_RESELLER)
                                    Le wallet du vendeur ({{ optional($order->cashReseller)->vendor_code ?: 'inconnu' }}) sera restauré. Le vendeur devra rendre l'argent au client en cash.
                                @else
                                    Statut commande passera à "remboursée" sans appel API.
                                @endif
                            </p>
                        </div>
                        <div style="display:flex;gap:8px;padding:14px 18px 18px;background:linear-gradient(180deg,white,#F8FAFC);">
                            <button type="button" @click="refundModal = false"
                                    style="flex:1;padding:12px 14px;border-radius:12px;background:white;border:1px solid #E2E8F0;color:#475569;font-size:13px;font-weight:700;cursor:pointer;">Annuler</button>
                            <form method="POST" action="{{ route('admin.orders.refund', $order) }}" style="flex:1;margin:0;">
                                @csrf
                                <button type="submit"
                                        style="width:100%;display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:12px 14px;border-radius:12px;background:linear-gradient(135deg,#BE123C,#F43F5E);color:white;border:0;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 8px 18px -6px rgba(244,63,94,0.50);">
                                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    Confirmer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div style="display:grid;grid-template-columns:1fr 320px;gap:16px;align-items:flex-start;">
        <div style="display:flex;flex-direction:column;gap:14px;min-width:0;">

            {{-- Infos commande --}}
            <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);overflow:hidden;">
                <div style="padding:14px 18px;border-bottom:1px solid #F1F5F9;background:linear-gradient(180deg,#F8FAFC,white);">
                    <h2 style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:14px;font-weight:700;color:#0F172A;margin:0;">Informations</h2>
                </div>
                <div style="padding:16px 18px;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;">
                    <div>
                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;margin-bottom:4px;">Numéro</div>
                        <div style="font-family:monospace;font-size:14px;font-weight:700;color:#0F172A;">#{{ $order->order_number }}</div>
                    </div>
                    @if($order->external_reference)
                        <div>
                            <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;margin-bottom:4px;">Référence externe</div>
                            <div style="font-family:monospace;font-size:11px;color:#475569;overflow:hidden;text-overflow:ellipsis;">{{ $order->external_reference }}</div>
                        </div>
                    @endif
                    <div>
                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;margin-bottom:4px;">Date de commande</div>
                        <div style="font-size:13px;color:#0F172A;font-weight:500;">{{ $order->created_at->format('d/m/Y · H:i') }}</div>
                    </div>
                    @if($order->completed_at)
                        <div>
                            <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;margin-bottom:4px;">Complétée</div>
                            <div style="font-size:13px;color:#0F172A;font-weight:500;">{{ $order->completed_at->format('d/m/Y · H:i') }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Client --}}
            @if($order->user)
                <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);overflow:hidden;">
                    <div style="padding:14px 18px;border-bottom:1px solid #F1F5F9;background:linear-gradient(180deg,#F8FAFC,white);">
                        <h2 style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:14px;font-weight:700;color:#0F172A;margin:0;">Client</h2>
                    </div>
                    <div style="padding:16px 18px;display:flex;align-items:center;gap:12px;">
                        <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#4ECDC4,#44A08D);display:flex;align-items:center;justify-content:center;color:white;font-family:'Space Grotesk', 'Inter', sans-serif;font-weight:700;font-size:18px;">
                            {{ strtoupper(substr($order->user->name, 0, 1)) }}
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:14px;font-weight:600;color:#0F172A;">{{ $order->user->name }}</div>
                            <div style="font-size:12px;color:#64748B;font-family:monospace;">{{ $order->user->email }}</div>
                            @if($order->user->phone)
                                <div style="font-size:12px;color:#64748B;margin-top:2px;">{{ $order->user->phone }}</div>
                            @endif
                        </div>
                        <a href="{{ route('admin.users.show', $order->user) }}"
                           style="padding:6px 12px;background:#F1F5F9;color:#475569;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;flex-shrink:0;">
                            Voir profil
                        </a>
                    </div>
                </div>
            @endif

            {{-- Cartes livrees --}}
            @if($order->userCards && $order->userCards->count() > 0)
                <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);overflow:hidden;">
                    <div style="padding:14px 18px;border-bottom:1px solid #F1F5F9;background:linear-gradient(180deg,#F8FAFC,white);">
                        <h2 style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:14px;font-weight:700;color:#0F172A;margin:0;display:flex;align-items:center;gap:8px;">
                            Cartes livrées
                            <span style="font-size:11px;color:#94A3B8;font-weight:500;">· {{ $order->userCards->count() }}</span>
                        </h2>
                    </div>
                    <div style="padding:14px;display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;">
                        @foreach($order->userCards as $card)
                            @php
                                $cardCode = $card->card_code ?? '';
                                $cardPin = $card->pin;
                                $brandColor = $brandColorFor($card->brand ?? $card->name ?? '');
                                $pricePaid = (float) ($card->orderItem?->unit_price ?? 0);
                            @endphp
                            <div x-data="{ codeShown: false, pinShown: false, copied: null,
                                           copy(text, field) { navigator.clipboard.writeText(text).then(() => { this.copied = field; setTimeout(() => this.copied = null, 1500); }); } }"
                                 style="background:white;border-radius:12px;border:1px solid #E2E8F0;overflow:hidden;">
                                <div style="background-color:{{ $brandColor }};padding:12px;position:relative;overflow:hidden;height:90px;">
                                    <svg style="position:absolute;inset:0;width:100%;height:100%;opacity:0.08;" aria-hidden="true">
                                        <defs><pattern id="adm-{{ $card->id }}" width="28" height="28" patternUnits="userSpaceOnUse"><circle cx="14" cy="14" r="11" fill="none" stroke="white" stroke-width="1"/></pattern></defs>
                                        <rect width="100%" height="100%" fill="url(#adm-{{ $card->id }})"/>
                                    </svg>
                                    <div style="position:relative;display:flex;flex-direction:column;height:100%;justify-content:space-between;">
                                        <div>
                                            <div style="color:rgba(255,255,255,0.75);font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:0.15em;">Gift Card</div>
                                            <div style="font-family:'Space Grotesk', 'Inter', sans-serif;color:white;font-size:18px;font-weight:700;margin-top:1px;line-height:1.05;">
                                                {{ $card->brand ?: explode(' ', $card->name)[0] }}
                                            </div>
                                        </div>
                                        <div style="color:white;font-family:'Space Grotesk', 'Inter', sans-serif;font-size:14px;font-weight:700;font-variant-numeric:tabular-nums;">
                                            {{ number_format($pricePaid, 0, ',', ' ') }} <span style="font-size:10px;font-weight:400;color:rgba(255,255,255,0.7);">FCFA</span>
                                        </div>
                                    </div>
                                </div>
                                <div style="padding:10px 12px;">
                                    <div style="font-size:12px;font-weight:600;color:#0F172A;margin-bottom:8px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $card->name }}</div>
                                    <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:7px;padding:6px 8px;margin-bottom:6px;">
                                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2px;">
                                            <span style="font-size:9px;text-transform:uppercase;letter-spacing:0.05em;font-weight:700;color:#94A3B8;">Code</span>
                                            <div style="display:flex;gap:2px;">
                                                <button type="button" @click="codeShown = !codeShown" style="background:none;border:none;cursor:pointer;padding:2px;color:#94A3B8;">
                                                    <svg x-show="!codeShown" style="width:11px;height:11px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    <svg x-show="codeShown" x-cloak style="width:11px;height:11px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                                </button>
                                                <button type="button" @click="copy('{{ $cardCode }}', 'c')" style="background:none;border:none;cursor:pointer;padding:2px;color:#94A3B8;">
                                                    <svg x-show="copied !== 'c'" style="width:11px;height:11px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                    <svg x-show="copied === 'c'" x-cloak style="width:11px;height:11px;color:#059669;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                        <div style="font-family:monospace;font-size:10px;font-weight:700;color:#0F172A;letter-spacing:0.5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                             x-text="codeShown ? '{{ $cardCode }}' : '{{ str_repeat('•', max(8, strlen($cardCode))) }}'"></div>
                                    </div>
                                    @if($cardPin || $card->expiration_date)
                                        <div style="display:grid;grid-template-columns:{{ $cardPin && $card->expiration_date ? '1fr 1fr' : '1fr' }};gap:4px;">
                                            @if($cardPin)
                                                <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:7px;padding:6px 8px;">
                                                    <div style="display:flex;align-items:center;justify-content:space-between;">
                                                        <span style="font-size:9px;text-transform:uppercase;letter-spacing:0.05em;font-weight:700;color:#94A3B8;">PIN</span>
                                                        <button type="button" @click="pinShown = !pinShown" style="background:none;border:none;cursor:pointer;padding:1px;color:#94A3B8;">
                                                            <svg x-show="!pinShown" style="width:9px;height:9px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                            <svg x-show="pinShown" x-cloak style="width:9px;height:9px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                                        </button>
                                                    </div>
                                                    <div style="font-family:monospace;font-size:10px;font-weight:700;color:#0F172A;margin-top:2px;"
                                                         x-text="pinShown ? '{{ $cardPin }}' : '{{ str_repeat('•', strlen($cardPin)) }}'"></div>
                                                </div>
                                            @endif
                                            @if($card->expiration_date)
                                                <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:7px;padding:6px 8px;">
                                                    <div style="font-size:9px;text-transform:uppercase;letter-spacing:0.05em;font-weight:700;color:#94A3B8;">Expire</div>
                                                    <div style="font-size:10px;font-weight:700;color:#0F172A;margin-top:2px;">{{ \Carbon\Carbon::parse($card->expiration_date)->format('d/m/Y') }}</div>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Articles --}}
            <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);overflow:hidden;">
                <div style="padding:14px 18px;border-bottom:1px solid #F1F5F9;background:linear-gradient(180deg,#F8FAFC,white);">
                    <h2 style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:14px;font-weight:700;color:#0F172A;margin:0;">Articles ({{ $order->orderItems->count() }})</h2>
                </div>
                @foreach($order->orderItems as $item)
                    <div style="padding:12px 18px;display:flex;align-items:center;gap:12px;{{ !$loop->last ? 'border-bottom:1px solid #F1F5F9;' : '' }}">
                        <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#F8FAFC,#F1F5F9);border:1px solid #E2E8F0;display:flex;align-items:center;justify-content:center;color:#475569;font-weight:700;flex-shrink:0;">
                            @if($item->image_url)
                                <img src="{{ $item->image_url }}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:9px;">
                            @else
                                {{ strtoupper(substr($item->name, 0, 1)) }}
                            @endif
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:6px;margin-bottom:2px;">
                                <span style="padding:2px 6px;border-radius:5px;background:#F1F5F9;font-size:10px;font-weight:700;color:#475569;">{{ $item->quantity }}×</span>
                                <span style="font-size:13px;font-weight:600;color:#0F172A;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $item->name }}</span>
                            </div>
                            <div style="font-size:11px;color:#64748B;font-family:monospace;">Product ID: {{ $item->product_id ?? '—' }}</div>
                        </div>
                        <div style="text-align:right;flex-shrink:0;">
                            <div style="font-size:11px;color:#94A3B8;">{{ number_format($item->unit_price, 0, ',', ' ') }} FCFA / unité</div>
                            <div style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:14px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;">
                                {{ number_format($item->total_price, 0, ',', ' ') }} FCFA
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Paiements --}}
            @if($order->payments && $order->payments->count() > 0)
                <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);overflow:hidden;">
                    <div style="padding:14px 18px;border-bottom:1px solid #F1F5F9;background:linear-gradient(180deg,#F8FAFC,white);">
                        <h2 style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:14px;font-weight:700;color:#0F172A;margin:0;">Paiements ({{ $order->payments->count() }})</h2>
                    </div>
                    @foreach($order->payments as $payment)
                        <div style="padding:12px 18px;display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;{{ !$loop->last ? 'border-bottom:1px solid #F1F5F9;' : '' }}">
                            <div>
                                <div style="font-size:9px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Provider</div>
                                <div style="font-size:13px;font-weight:600;color:#0F172A;text-transform:capitalize;margin-top:2px;">{{ $payment->provider ?? $payment->payment_method }}</div>
                            </div>
                            <div>
                                <div style="font-size:9px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Montant</div>
                                <div style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:13px;font-weight:700;color:#0F172A;margin-top:2px;">{{ number_format($payment->amount, 0, ',', ' ') }} {{ $payment->currency }}</div>
                            </div>
                            <div>
                                <div style="font-size:9px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Statut</div>
                                <div style="margin-top:2px;">
                                    @php $pPay = $payInfo[$payment->status] ?? ['label' => $payment->status, 'bg' => '#F1F5F9', 'text' => '#475569']; @endphp
                                    <span style="display:inline-flex;padding:2px 7px;border-radius:9999px;font-size:11px;font-weight:700;background:{{ $pPay['bg'] }};color:{{ $pPay['text'] }};">{{ $pPay['label'] }}</span>
                                </div>
                            </div>
                            <div>
                                <div style="font-size:9px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Transaction</div>
                                <div style="font-family:monospace;font-size:11px;color:#475569;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $payment->transaction_id ?? '—' }}</div>
                            </div>
                            <div>
                                <div style="font-size:9px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Date</div>
                                <div style="font-size:12px;color:#475569;margin-top:2px;">{{ $payment->processed_at?->format('d/m/Y H:i') ?? $payment->created_at->format('d/m/Y H:i') }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <aside style="display:flex;flex-direction:column;gap:14px;">
            <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);overflow:hidden;">
                <div style="padding:14px 18px;border-bottom:1px solid #F1F5F9;background:linear-gradient(180deg,#F8FAFC,white);">
                    <h2 style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:14px;font-weight:700;color:#0F172A;margin:0;">Récapitulatif</h2>
                </div>
                <div style="padding:16px 18px;">
                    <div style="display:flex;justify-content:space-between;font-size:13px;color:#64748B;margin-bottom:8px;">
                        <span>Sous-total</span>
                        <span style="color:#0F172A;font-weight:600;font-variant-numeric:tabular-nums;">{{ number_format($order->subtotal, 0, ',', ' ') }} FCFA</span>
                    </div>
                    @if($order->tax_amount > 0)
                        <div style="display:flex;justify-content:space-between;font-size:13px;color:#64748B;margin-bottom:8px;">
                            <span>TVA</span>
                            <span style="color:#0F172A;font-weight:600;font-variant-numeric:tabular-nums;">{{ number_format($order->tax_amount, 0, ',', ' ') }} FCFA</span>
                        </div>
                    @endif
                    <div style="display:flex;justify-content:space-between;font-size:13px;color:#64748B;margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid #F1F5F9;">
                        <span>Frais de service</span>
                        <span style="color:#059669;font-weight:600;">Offerts</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:flex-end;">
                        <div>
                            <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Total</div>
                            <div style="font-size:10px;color:#94A3B8;margin-top:2px;">{{ $order->orderItems->count() }} article{{ $order->orderItems->count() > 1 ? 's' : '' }}</div>
                        </div>
                        <div style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:22px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;line-height:1;">
                            {{ number_format($order->total_amount, 0, ',', ' ') }}
                            <span style="font-size:12px;font-weight:500;color:#94A3B8;">FCFA</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($order->payment_method)
                <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);padding:14px 16px;">
                    <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;margin-bottom:6px;">Mode de paiement</div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#4ECDC4,#44A08D);color:white;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                        <div style="min-width:0;">
                            <div style="font-size:13px;font-weight:600;color:#0F172A;">
                                @if($order->payment_method === 'simulated') Paiement simulé
                                @elseif($order->payment_method === 'ebilling') E-Billing
                                @else {{ ucfirst($order->payment_method) }}
                                @endif
                            </div>
                            @if($order->external_reference)
                                <div style="font-family:monospace;font-size:10px;color:#94A3B8;overflow:hidden;text-overflow:ellipsis;">{{ $order->external_reference }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if($order->notes)
                <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);padding:14px 16px;">
                    <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;margin-bottom:6px;">Notes internes</div>
                    <div style="font-size:12px;color:#475569;line-height:1.5;white-space:pre-wrap;">{{ $order->notes }}</div>
                </div>
            @endif
        </aside>
    </div>
</div>
@endsection
