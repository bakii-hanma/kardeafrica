@extends('vendor.layouts.vendor')

@section('title', 'Vente carte locale #' . $purchase->id)

@section('content')
@php
    $isInactive = $purchase->status === \App\Models\MerchantCardPurchase::STATUS_INACTIVE
        && $purchase->payment_status === \App\Models\MerchantCardPurchase::PAYMENT_PENDING;
    $isUsable = in_array($purchase->status, [
        \App\Models\MerchantCardPurchase::STATUS_ACTIVE,
        \App\Models\MerchantCardPurchase::STATUS_PARTIALLY_USED,
        \App\Models\MerchantCardPurchase::STATUS_FULLY_USED,
    ], true) && $purchase->payment_status === \App\Models\MerchantCardPurchase::PAYMENT_PAID;
@endphp

<div style="max-width:560px;margin:0 auto;">

    <a href="{{ route('vendor.local-cards.index') }}" style="font-size:13px;color:#64748B;text-decoration:none;">← Cartes locales</a>

    <h1 style="font-size:20px;font-weight:800;color:#0F172A;margin:10px 0 4px;">
        {{ $purchase->merchantCard?->name }}
    </h1>
    <p style="font-size:13px;color:#64748B;margin:0 0 16px;">
        Vente #{{ $purchase->id }} · {{ number_format((float) $purchase->amount, 0, ',', ' ') }} FCFA
        @if ($purchase->buyer_name && $purchase->buyer_name !== 'Client comptoir') · {{ $purchase->buyer_name }} @endif
    </p>

    @if (session('success'))
        <div style="background:#D1FAE5;border:1px solid #6EE7B7;color:#047857;border-radius:12px;padding:12px 16px;font-size:13px;font-weight:600;margin-bottom:14px;">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div style="background:#FEE2E2;border:1px solid #FCA5A5;color:#B91C1C;border-radius:12px;padding:12px 16px;font-size:13px;margin-bottom:14px;">
            {{ $errors->first() }}
        </div>
    @endif

    @if ($isInactive)
        {{-- ============ À récupérer ============ --}}
        <div style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:16px;padding:18px;margin-bottom:16px;">
            <div style="font-weight:800;color:#B45309;font-size:14px;margin-bottom:6px;">⚠️ Carte réservée — code INACTIF</div>
            <p style="font-size:13px;color:#92400E;margin:0;">
                Encaissez le client (<strong>{{ number_format((float) $purchase->amount, 0, ',', ' ') }} FCFA</strong>),
                puis récupérez la carte. Tant qu'elle n'est pas récupérée, le code ne peut pas être utilisé chez le commerçant.
            </p>
        </div>

        <div style="background:white;border:1px solid #E2E8F0;border-radius:16px;padding:18px;margin-bottom:16px;font-size:13px;">
            <div style="display:flex;justify-content:space-between;padding:6px 0;">
                <span style="color:#64748B;">Montant encaissé au client</span>
                <strong>{{ number_format((float) $purchase->amount, 0, ',', ' ') }} FCFA</strong>
            </div>
            <div style="display:flex;justify-content:space-between;padding:6px 0;">
                <span style="color:#64748B;">Votre commission</span>
                <strong style="color:#047857;">− {{ number_format((float) $purchase->vendor_commission_amount, 0, ',', ' ') }} FCFA</strong>
            </div>
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-top:1px dashed #E2E8F0;margin-top:4px;">
                <span style="color:#0F172A;font-weight:700;">Débité de votre wallet</span>
                <strong style="font-size:15px;">{{ number_format($due, 0, ',', ' ') }} FCFA</strong>
            </div>
            <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:12px;">
                <span style="color:#94A3B8;">Solde wallet actuel</span>
                <span style="color:#94A3B8;">{{ number_format((float) $reseller->wallet_balance, 0, ',', ' ') }} FCFA</span>
            </div>
        </div>

        <form method="POST" action="{{ route('vendor.local-cards.claim', $purchase) }}" style="margin:0 0 10px;">
            @csrf
            <button type="submit"
                    style="width:100%;background:#0F766E;color:white;border:none;border-radius:12px;padding:14px;font-size:15px;font-weight:800;cursor:pointer;">
                ✅ Récupérer la carte (activer le code)
            </button>
        </form>

        <form method="POST" action="{{ route('vendor.local-cards.cancel', $purchase) }}" style="margin:0;"
              onsubmit="return confirm('Annuler cette réservation ?');">
            @csrf
            <button type="submit"
                    style="width:100%;background:white;color:#B91C1C;border:1px solid #FCA5A5;border-radius:12px;padding:11px;font-size:13px;font-weight:700;cursor:pointer;">
                Annuler la réservation
            </button>
        </form>

    @elseif ($isUsable)
        {{-- ============ Carte active : remise du code au client ============ --}}
        {{-- Le code et le PIN ne sont PLUS affichés ici. Un revendeur qui les
             voyait pouvait vider la carte avant son client, sans laisser de
             trace exploitable. Ils partent désormais sur le téléphone du
             client, par un lien à usage unique. --}}

        @if (session('secret_once'))
            {{-- Canal de repli : affichage unique, à tourner vers le client.
                 Ce bloc ne vit que le temps d'un flash de session — il n'est
                 jamais réaffiché, même en rechargeant la page. --}}
            <div style="background:#F0FDF9;border:2px solid #14B8A6;border-radius:16px;padding:20px;text-align:center;margin-bottom:16px;">
                <div style="font-size:12px;font-weight:800;color:#B45309;background:#FEF3C7;border-radius:8px;padding:8px;margin-bottom:14px;">
                    ⚠️ Affichage unique — tournez l'écran vers le client. Il ne réapparaîtra pas.
                </div>
                <div style="font-size:12px;font-weight:700;color:#0F766E;letter-spacing:0.08em;text-transform:uppercase;">Code de la carte</div>
                <div style="font-family:monospace;font-size:34px;font-weight:800;letter-spacing:0.14em;color:#0F172A;">
                    {{ session('secret_once')['code'] }}
                </div>
                <div style="margin-top:10px;font-size:14px;color:#334155;">
                    PIN : <strong style="font-family:monospace;font-size:18px;">{{ session('secret_once')['pin'] }}</strong>
                </div>
            </div>
        @endif

        @if ($purchase->isRevealed())
            <div style="background:white;border:1px solid #E2E8F0;border-radius:16px;padding:18px;margin-bottom:16px;">
                <div style="font-weight:800;color:#047857;font-size:14px;margin-bottom:6px;">✅ Code remis au client</div>
                <p style="font-size:13px;color:#475569;margin:0;line-height:1.6;">
                    Consulté le {{ $purchase->revealed_at?->format('d/m/Y à H:i') }}
                    @if ($purchase->reveal_channel === 'comptoir')
                        <strong>au comptoir</strong> (le client n'avait pas WhatsApp).
                    @else
                        depuis le téléphone du client.
                    @endif
                    <br>
                    Le code n'est plus consultable — ni par toi, ni par le support. Le client
                    l'utilise chez « {{ $purchase->merchantCard?->name }} ».
                </p>
            </div>
        @else
            <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:16px;padding:16px;margin-bottom:16px;">
                <div style="font-weight:800;color:#1D4ED8;font-size:14px;margin-bottom:6px;">🔐 Le code appartient au client</div>
                <p style="font-size:13px;color:#1E3A8A;margin:0;line-height:1.6;">
                    Tu ne vois ni le code ni le PIN : ils partent directement sur le téléphone du client,
                    par un lien qui expire en {{ \App\Models\MerchantCardPurchase::REVEAL_TTL_MINUTES }} minutes
                    et ne s'ouvre qu'une seule fois.
                </p>
            </div>

            @if ($purchase->reveal_sent_at)
                <div style="background:#F0FDF9;border:1px solid #99F6E4;border-radius:12px;padding:12px 14px;margin-bottom:14px;font-size:12.5px;color:#0F766E;">
                    Lien envoyé au <strong>{{ $purchase->reveal_sent_to }}</strong>
                    {{ $purchase->reveal_sent_at->diffForHumans() }}
                    @if ($purchase->revealLinkIsLive())
                        · expire {{ $purchase->reveal_expires_at->diffForHumans() }}
                    @else
                        · <strong>expiré</strong>, tu peux le renvoyer
                    @endif
                </div>
            @endif

            <form method="POST" action="{{ route('vendor.local-cards.send-code', $purchase) }}" style="margin:0 0 14px;">
                @csrf
                <x-phone-input name="buyer_phone" required
                               label="WhatsApp du client"
                               :value="$purchase->reveal_sent_to ?: $purchase->buyer_phone"
                               hint="Choisis l'indicatif du client : un numéro étranger saisi sans indicatif ne peut pas être deviné." />
                <div style="height:10px;"></div>
                <button type="submit"
                        style="width:100%;background:#25D366;color:white;border:none;border-radius:12px;padding:14px;font-size:15px;font-weight:800;cursor:pointer;">
                    📲 Envoyer le code au client sur WhatsApp
                </button>
                @if ($purchase->reveal_sends > 0)
                    <p style="font-size:11.5px;color:#94A3B8;margin:8px 0 0;text-align:center;">
                        {{ $purchase->reveal_sends }} / {{ \App\Models\MerchantCardPurchase::REVEAL_MAX_SENDS }} envois utilisés
                    </p>
                @endif
            </form>

            <form method="POST" action="{{ route('vendor.local-cards.reveal-here', $purchase) }}" style="margin:0;"
                  onsubmit="return confirm('Le code s\'affichera UNE SEULE FOIS sur cet écran, et cet affichage est enregistré à ton nom. Continuer ?');">
                @csrf
                <button type="submit"
                        style="width:100%;background:white;color:#475569;border:1px solid #CBD5E1;border-radius:12px;padding:12px;font-size:13px;font-weight:700;cursor:pointer;">
                    Le client n'a pas WhatsApp — afficher une fois ici
                </button>
                <p style="font-size:11.5px;color:#94A3B8;margin:8px 0 0;text-align:center;line-height:1.5;">
                    Cet affichage est journalisé. Tourne l'écran vers le client.
                </p>
            </form>
        @endif

    @else
        <div style="background:#FEE2E2;border:1px solid #FCA5A5;border-radius:16px;padding:16px;font-size:13px;color:#B91C1C;">
            Cette vente est {{ $purchase->status === 'cancelled' ? 'annulée' : $purchase->status }}.
        </div>
    @endif
</div>
@endsection
