@extends('vendor.layouts.vendor')

@section('title', 'Commande #' . $order->order_number)

@push('head')
@endpush

@section('content')
@php
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

<div class="vo-detail-wrap">

    {{-- ============= TOP NAV ============= --}}
    <a href="{{ route('vendor.orders') }}" class="vod-back">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Mes ventes
    </a>

    <div class="vod-grid">

        {{-- ====================== COLONNE PRINCIPALE ====================== --}}
        <div class="vod-main">

            {{-- HERO COMMANDE --}}
            <div class="vod-hero">
                <div class="vod-hero-glow"></div>
                <div class="vod-hero-grain"></div>

                <div class="vod-hero-top">
                    <div class="vod-hero-info">
                        <div class="vod-hero-eyebrow">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/></svg>
                            Commande
                        </div>
                        <div class="vod-hero-num">#{{ $order->order_number }}</div>
                        <div class="vod-hero-date">{{ $order->created_at->translatedFormat('d F Y · H\\hi') }}</div>
                    </div>
                    <span class="vod-status" style="background:{{ $st[1] }};color:{{ $st[0] }};">
                        @if($order->status === 'completed')
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        @elseif(in_array($order->status, ['processing','pending']))
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @else
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        @endif
                        {{ $st[2] }}
                    </span>
                </div>

                <div class="vod-hero-stats">
                    <div class="vod-hs">
                        <div class="vod-hs-label">Total encaissé</div>
                        <div class="vod-hs-value">
                            {{ number_format($order->total_amount, 0, ',', ' ') }}
                            <span>FCFA</span>
                        </div>
                    </div>
                    <div class="vod-hs vod-hs--brand">
                        <div class="vod-hs-label">Commission</div>
                        <div class="vod-hs-value">
                            +{{ number_format($order->commission_earned, 0, ',', ' ') }}
                            <span>FCFA</span>
                        </div>
                    </div>
                    <div class="vod-hs">
                        <div class="vod-hs-label">Articles</div>
                        <div class="vod-hs-value">{{ $order->items->sum('quantity') }}</div>
                    </div>
                </div>
            </div>

            {{-- INFOS CLIENT --}}
            @if($order->customer_name || $order->customer_phone)
            <section class="vod-card">
                <h2 class="vod-card-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Client
                </h2>
                <div class="vod-customer">
                    @if($order->customer_name)
                        <div class="vod-customer-item">
                            <div class="vod-customer-label">Nom</div>
                            <div class="vod-customer-value">{{ $order->customer_name }}</div>
                        </div>
                    @endif
                    @if($order->customer_phone)
                        <div class="vod-customer-item">
                            <div class="vod-customer-label">Téléphone</div>
                            <div class="vod-customer-value">
                                <a href="tel:{{ $order->customer_phone }}">{{ $order->customer_phone }}</a>
                            </div>
                        </div>
                    @endif
                </div>
            </section>
            @endif

            {{-- TIMELINE PAIEMENT --}}
            <section class="vod-card">
                <h2 class="vod-card-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    Suivi du paiement
                </h2>
                <div class="vod-timeline">
                    @php
                        $steps = [
                            ['Commande créée', $order->created_at, true],
                            ['Paiement E-Billing', $order->payment_status === 'completed' ? $order->updated_at : null, $order->payment_status === 'completed'],
                            ['Cartes générées', $order->cards->count() > 0 ? $order->cards->first()->created_at : null, $order->cards->count() > 0],
                            ['Récupéré par le client', $order->claimed_at, (bool) $order->claimed_at],
                        ];
                    @endphp
                    @foreach($steps as $i => [$label, $when, $done])
                        <div class="vod-tl-item {{ $done ? 'vod-tl-item--done' : '' }} {{ $i === count($steps) - 1 ? 'vod-tl-item--last' : '' }}">
                            <div class="vod-tl-dot">
                                @if($done)
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                @endif
                            </div>
                            <div class="vod-tl-body">
                                <div class="vod-tl-label">{{ $label }}</div>
                                <div class="vod-tl-when">
                                    @if($when)
                                        {{ $when->translatedFormat('d/m/Y · H\\hi') }}
                                    @else
                                        En attente
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($order->external_reference)
                    <div class="vod-ref">
                        Référence E-Billing : <code>{{ $order->external_reference }}</code>
                    </div>
                @endif
            </section>

            {{-- ARTICLES --}}
            <section class="vod-card">
                <h2 class="vod-card-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    Articles vendus
                </h2>
                <div class="vod-items">
                    @foreach($order->items as $item)
                        <div class="vod-item">
                            <div class="vod-item-visual" style="background-color:{{ $item->color ?: '#44A08D' }};">
                                @if($item->image_url)
                                    <img src="{{ $item->image_url }}" alt="{{ $item->brand }}" loading="lazy">
                                @else
                                    <span>{{ strtoupper(substr($item->brand ?: $item->name, 0, 1)) }}</span>
                                @endif
                                <span class="vod-item-chip"></span>
                            </div>
                            <div class="vod-item-body">
                                <div class="vod-item-brand">{{ $item->brand ?: $item->name }}</div>
                                @if($item->brand && $item->name !== $item->brand)
                                    <div class="vod-item-name">{{ $item->name }}</div>
                                @endif
                                <div class="vod-item-meta">{{ number_format($item->unit_price, 0, ',', ' ') }} FCFA × {{ $item->quantity }}</div>
                            </div>
                            <div class="vod-item-total">
                                {{ number_format($item->total_price, 0, ',', ' ') }}
                                <span>FCFA</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Récap totaux --}}
                <div class="vod-totals">
                    <div class="vod-totals-row">
                        <span>Sous-total</span>
                        <span class="vod-totals-num">{{ number_format($order->subtotal, 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="vod-totals-row vod-totals-row--brand">
                        <span>Ta commission</span>
                        <span class="vod-totals-num">+{{ number_format($order->commission_earned, 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="vod-totals-divider"></div>
                    <div class="vod-totals-row vod-totals-row--total">
                        <span>Total</span>
                        <span class="vod-totals-num">{{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</span>
                    </div>
                </div>
            </section>

            {{-- LIVRAISON / NOTE --}}
            @if($order->cards->count() === 0 && $order->payment_status === 'completed' && $order->status !== 'cancelled')
                @php
                    // Erreur détectée si la commande date de plus de 30s ET pas de cartes
                    // (sinon ça veut dire que la livraison est juste en cours d'arrivée).
                    // notes peut contenir le message d'erreur posé par tryDeliver.
                    $hasError = !empty($order->notes)
                        || ($order->updated_at && $order->updated_at->lt(now()->subSeconds(30)));
                @endphp
                <section class="vod-card {{ $hasError ? 'vod-card--error' : 'vod-card--warn' }}">
                    <div class="vod-warn-row">
                        <div class="{{ $hasError ? 'vod-warn-error-ico' : 'vod-warn-spin' }}">
                            @if($hasError)
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            @else
                                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="3"/></svg>
                            @endif
                        </div>
                        <div>
                            <div class="vod-warn-title">
                                {{ $hasError ? 'Livraison des cartes échouée' : 'Génération des cartes en cours…' }}
                            </div>
                            <div class="vod-warn-text">
                                {{ $hasError
                                    ? 'Le paiement est bien reçu mais les cartes n\'ont pas pu être générées. Relance la livraison, ou rembourse le client si le problème persiste.'
                                    : 'Les codes seront prêts dans quelques instants. Le client pourra les récupérer via le QR code.' }}
                            </div>
                        </div>
                    </div>

                    @if($hasError)
                        <div class="vod-actions" x-data="{ refundModal: false }">
                            <form method="POST" action="{{ route('vendor.orders.retry-delivery', $order) }}" data-no-loader style="margin:0;flex:1;">
                                @csrf
                                <button type="submit" class="vod-retry-btn">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Relancer la livraison
                                </button>
                            </form>
                            <button type="button" @click="refundModal = true" class="vod-refund-btn">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z"/></svg>
                                Rembourser
                            </button>

                            {{-- Modal remboursement --}}
                            <div x-show="refundModal" x-cloak
                                 class="vod-modal-backdrop"
                                 @click.self="refundModal = false"
                                 @keydown.escape.window="refundModal = false"
                                 x-transition.opacity>
                                <div x-show="refundModal" x-cloak
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     class="vod-modal">
                                    <div class="vod-modal-banner">
                                        <div class="vod-modal-glow"></div>
                                        <div class="vod-modal-icon">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6z"/></svg>
                                        </div>
                                        <div>
                                            <div class="vod-modal-eyebrow">Remboursement</div>
                                            <div class="vod-modal-title">Rembourser le client&nbsp;?</div>
                                        </div>
                                    </div>
                                    <div class="vod-modal-body">
                                        <p class="vod-modal-text">
                                            Montant à rembourser : <strong>{{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</strong>
                                            @if($order->payment_method === 'ebilling')
                                                <br><span class="vod-modal-hint">Sera renvoyé via E-Billing au moyen de paiement initial du client.</span>
                                            @elseif($order->payment_method === 'cash')
                                                <br><span class="vod-modal-hint">Tu dois rendre l'argent au client en cash. Coche ci-dessous pour confirmer.</span>
                                            @endif
                                        </p>

                                        <form method="POST" action="{{ route('vendor.orders.refund', $order) }}" id="vodRefundForm">
                                            @csrf
                                            @if($order->payment_method === 'cash')
                                                <label class="vod-modal-check">
                                                    <input type="checkbox" name="cash_returned_to_client" value="1" required>
                                                    <span>Je confirme avoir rendu <strong>{{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</strong> en cash au client.</span>
                                                </label>
                                            @endif
                                            <p class="vod-modal-impact">
                                                Ton wallet sera restauré de +{{ number_format($order->subtotal, 0, ',', ' ') }} FCFA et la commission de {{ number_format($order->commission_earned, 0, ',', ' ') }} FCFA sera retirée.
                                            </p>
                                        </form>
                                    </div>
                                    <div class="vod-modal-footer">
                                        <button type="button" @click="refundModal = false" class="vod-modal-cancel">Annuler</button>
                                        <button type="submit" form="vodRefundForm" class="vod-modal-confirm">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            Confirmer le remboursement
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </section>
            @elseif($order->cards->count() > 0)
                <section class="vod-card vod-card--success">
                    <div class="vod-warn-row">
                        <div class="vod-success-ico">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <div class="vod-warn-title">{{ $order->cards->count() }} carte{{ $order->cards->count() > 1 ? 's' : '' }} prête{{ $order->cards->count() > 1 ? 's' : '' }}</div>
                            <div class="vod-warn-text">Le client peut scanner le QR code pour récupérer ses codes.</div>
                        </div>
                    </div>
                    <div class="vod-warn-note vod-warn-note--info">
                        ⓘ Les codes restent visibles côté client uniquement — ne les communique pas autrement pour éviter la double-utilisation.
                    </div>
                </section>
            @endif
        </div>

        {{-- ====================== COLONNE QR CODE ====================== --}}
        <div class="vod-side">
            <div class="vod-qr-card">
                {{-- Le QR affichant le lien a été retiré : il était sous les yeux
                     du revendeur, qui pouvait le scanner lui-même et récupérer
                     les codes avant son client. Un QR sur SON écran ne prouve
                     rien ; un lien reçu sur le WhatsApp du client, si. --}}
                <div class="vod-qr-eyebrow">Remise des cartes</div>

                @if($order->claimed_at)
                    <h3 class="vod-qr-title">✅ Cartes remises</h3>
                    <p class="vod-claim-note">
                        Récupérées le {{ $order->claimed_at->format('d/m/Y à H:i') }}
                        @if($order->claim_channel === 'comptoir')
                            <strong>au comptoir</strong>.
                        @else
                            depuis le téléphone du client.
                        @endif
                        Les codes sont dans son compte KardAfrica — tu n'y as pas accès.
                    </p>
                @else
                    <h3 class="vod-qr-title">Envoyer au client</h3>
                    <p class="vod-claim-note">
                        Tu ne vois pas les codes : ils partent sur le téléphone du client,
                        par un lien qui expire en {{ \App\Models\ResellerOrder::CLAIM_TTL_MINUTES }} minutes
                        et ne s'ouvre qu'une seule fois.
                    </p>

                    @if($order->claim_sent_at)
                        <div class="vod-claim-sent">
                            Lien envoyé au <strong>{{ $order->claim_sent_to }}</strong>
                            {{ $order->claim_sent_at->diffForHumans() }}
                            @if($order->claimLinkIsLive())
                                · expire {{ $order->claim_expires_at->diffForHumans() }}
                            @else
                                · <strong>expiré</strong>, tu peux le renvoyer
                            @endif
                        </div>
                    @endif

                    <form method="POST" action="{{ route('vendor.orders.send-cards', $order) }}" class="vod-claim-form">
                        @csrf
                        <x-phone-input name="customer_phone" required
                                       label="WhatsApp du client"
                                       :value="$order->claim_sent_to ?: $order->customer_phone" />
                        <button type="submit" class="vod-qr-btn vod-qr-btn--wa">
                            📲 Envoyer les cartes sur WhatsApp
                        </button>
                        @if($order->claim_sends > 0)
                            <p class="vod-claim-count">
                                {{ $order->claim_sends }} / {{ \App\Models\ResellerOrder::CLAIM_MAX_SENDS }} envois utilisés
                            </p>
                        @endif
                    </form>

                    <form method="POST" action="{{ route('vendor.orders.reveal-cards', $order) }}"
                          onsubmit="return confirm('Les codes s\'afficheront UNE SEULE FOIS sur cet écran, et cet affichage est enregistré à ton nom. Continuer ?');">
                        @csrf
                        <button type="submit" class="vod-qr-btn vod-qr-btn--ghost vod-claim-fallback">
                            Le client n'a pas WhatsApp — afficher une fois ici
                        </button>
                    </form>
                @endif
            </div>

            @if($order->expires_at)
                <div class="vod-qr-foot">
                    @if($order->claimed_at)
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Récupéré le <strong>{{ $order->claimed_at->format('d/m/Y H:i') }}</strong>
                    @else
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Lien valable jusqu'au <strong>{{ $order->expires_at->format('d/m/Y') }}</strong>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .vo-detail-wrap { max-width: 1100px; margin: 0 auto; }

    .vod-back {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 12px; font-weight: 700;
        color: #64748B;
        text-decoration: none;
        margin-bottom: 14px;
        padding: 7px 11px;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 9999px;
        transition: all .15s ease;
    }
    .vod-back:hover { color: #44A08D; border-color: #44A08D; }
    .vod-back svg { width: 13px; height: 13px; }

    .vod-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
        align-items: start;
    }
    @media (min-width: 980px) {
        .vod-grid { grid-template-columns: 1fr 360px; }
        .vod-side { position: sticky; top: 90px; }
    }
    .vod-main { display: flex; flex-direction: column; gap: 14px; min-width: 0; }
    .vod-side { display: flex; flex-direction: column; gap: 12px; min-width: 0; }

    /* ====================== HERO ====================== */
    .vod-hero {
        position: relative;
        background: linear-gradient(135deg, #0F172A 0%, #1E293B 50%, #0F172A 100%);
        border-radius: 22px;
        padding: 22px 20px;
        color: white;
        overflow: hidden;
        box-shadow: 0 16px 36px -16px rgba(15,23,42,0.40);
    }
    .vod-hero-glow {
        position: absolute; top: -80px; right: -80px;
        width: 280px; height: 280px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(78,205,196,0.20) 0%, transparent 70%);
    }
    .vod-hero-grain {
        position: absolute; inset: 0; opacity: 0.4; pointer-events: none;
        background-image:
            linear-gradient(to right, rgba(255,255,255,0.04) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(255,255,255,0.04) 1px, transparent 1px);
        background-size: 32px 32px;
        -webkit-mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 30%, transparent 100%);
                mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 30%, transparent 100%);
    }

    .vod-hero-top {
        position: relative;
        display: flex; align-items: flex-start; justify-content: space-between;
        gap: 12px; flex-wrap: wrap;
        margin-bottom: 16px;
    }
    .vod-hero-info { min-width: 0; }
    .vod-hero-eyebrow {
        display: inline-flex; align-items: center; gap: 6px;
        font-family: 'JetBrains Mono','Fira Code',monospace;
        font-size: 10px; font-weight: 800;
        letter-spacing: 0.10em;
        color: #5EEAD4;
        text-transform: uppercase;
    }
    .vod-hero-eyebrow svg { width: 12px; height: 12px; }
    .vod-hero-num {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 26px; font-weight: 800;
        margin-top: 4px;
        letter-spacing: -0.01em;
        line-height: 1.1;
    }
    .vod-hero-date {
        font-size: 12px; color: #94A3B8;
        margin-top: 4px;
    }

    .vod-status {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 6px 12px;
        border-radius: 9999px;
        font-size: 11px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.06em;
        flex-shrink: 0;
    }
    .vod-status svg { width: 12px; height: 12px; }

    .vod-hero-stats {
        position: relative;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        padding-top: 16px;
        border-top: 1px solid rgba(255,255,255,0.10);
    }
    .vod-hs {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 12px;
        padding: 10px 12px;
    }
    .vod-hs--brand {
        background: linear-gradient(135deg, rgba(78,205,196,0.15), rgba(94,234,212,0.05));
        border-color: rgba(78,205,196,0.30);
    }
    .vod-hs-label {
        font-size: 10px; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.06em;
        color: #94A3B8;
    }
    .vod-hs--brand .vod-hs-label { color: #5EEAD4; }
    .vod-hs-value {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 18px; font-weight: 800;
        font-variant-numeric: tabular-nums;
        line-height: 1.1;
        margin-top: 4px;
    }
    .vod-hs--brand .vod-hs-value { color: #5EEAD4; }
    .vod-hs-value span {
        font-size: 10px; font-weight: 600;
        color: #94A3B8;
        margin-left: 3px;
    }

    /* ====================== CARDS ====================== */
    .vod-card {
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 18px;
        padding: 20px;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }
    .vod-card--warn {
        background: linear-gradient(135deg, #FFFBEB, #FEF3C7);
        border-color: #FDE68A;
    }
    .vod-card--success {
        background: linear-gradient(135deg, #ECFDF5, #D1FAE5);
        border-color: #6EE7B7;
    }
    .vod-card--error {
        background: linear-gradient(135deg, #FEF2F2, #FEE2E2);
        border-color: #FCA5A5;
    }
    .vod-warn-error-ico {
        width: 36px; height: 36px;
        border-radius: 11px;
        background: linear-gradient(135deg, #F43F5E, #BE123C);
        color: white;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 4px 10px -3px rgba(244,63,94,0.45);
    }
    .vod-warn-error-ico svg { width: 18px; height: 18px; }
    .vod-retry-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 18px;
        background: white;
        border: 1.5px solid #BE123C;
        color: #BE123C;
        border-radius: 11px;
        font-family: inherit;
        font-size: 12px; font-weight: 800;
        cursor: pointer;
        transition: all .15s ease;
    }
    .vod-retry-btn:hover {
        background: #BE123C;
        color: white;
    }
    .vod-retry-btn svg { width: 13px; height: 13px; }
    .vod-card-title {
        display: flex; align-items: center; gap: 8px;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 14px; font-weight: 800;
        color: #0F172A;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin: 0 0 14px;
    }
    .vod-card-title svg { width: 15px; height: 15px; color: #44A08D; }

    /* CLIENT */
    .vod-customer {
        display: grid;
        grid-template-columns: 1fr;
        gap: 12px;
    }
    @media (min-width: 540px) { .vod-customer { grid-template-columns: 1fr 1fr; } }
    .vod-customer-item {
        background: #F8FAFC;
        border: 1px solid #F1F5F9;
        border-radius: 11px;
        padding: 10px 14px;
    }
    .vod-customer-label {
        font-size: 10px; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.06em;
        color: #94A3B8;
    }
    .vod-customer-value {
        font-size: 14px; font-weight: 700;
        color: #0F172A;
        margin-top: 3px;
    }
    .vod-customer-value a { color: #0F172A; text-decoration: none; }
    .vod-customer-value a:hover { color: #44A08D; }

    /* TIMELINE */
    .vod-timeline {
        display: flex; flex-direction: column;
        gap: 0;
    }
    .vod-tl-item {
        display: flex; align-items: flex-start; gap: 12px;
        position: relative;
        padding-bottom: 18px;
    }
    .vod-tl-item::before {
        content: '';
        position: absolute;
        left: 11px; top: 24px; bottom: -6px;
        width: 2px;
        background: #E2E8F0;
    }
    .vod-tl-item--last::before { display: none; }
    .vod-tl-item--done::before { background: #44A08D; }
    .vod-tl-dot {
        width: 24px; height: 24px;
        border-radius: 50%;
        background: white;
        border: 2px solid #E2E8F0;
        flex-shrink: 0;
        position: relative;
        z-index: 1;
        display: flex; align-items: center; justify-content: center;
    }
    .vod-tl-item--done .vod-tl-dot {
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        border-color: transparent;
        color: white;
        box-shadow: 0 4px 10px -3px rgba(78,205,196,0.45);
    }
    .vod-tl-dot svg { width: 12px; height: 12px; }
    .vod-tl-body { flex: 1; min-width: 0; padding-top: 1px; }
    .vod-tl-label {
        font-size: 13px; font-weight: 700;
        color: #0F172A;
        line-height: 1.3;
    }
    .vod-tl-item:not(.vod-tl-item--done) .vod-tl-label { color: #94A3B8; font-weight: 600; }
    .vod-tl-when {
        font-size: 11px; color: #64748B;
        margin-top: 2px;
        font-variant-numeric: tabular-nums;
    }

    .vod-ref {
        margin-top: 12px;
        padding: 8px 12px;
        background: #F8FAFC;
        border-radius: 9px;
        font-size: 11px; color: #64748B;
    }
    .vod-ref code {
        font-family: 'JetBrains Mono','Fira Code',monospace;
        font-weight: 700;
        color: #0F172A;
        background: white;
        padding: 2px 6px;
        border-radius: 5px;
        border: 1px solid #E2E8F0;
        margin-left: 4px;
    }

    /* ARTICLES */
    .vod-items {
        display: flex; flex-direction: column;
        gap: 8px;
    }
    .vod-item {
        display: flex; align-items: center; gap: 12px;
        padding: 10px;
        background: #F8FAFC;
        border: 1px solid #F1F5F9;
        border-radius: 12px;
    }
    .vod-item-visual {
        position: relative;
        flex-shrink: 0;
        width: 48px; height: 48px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        overflow: hidden;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.20);
    }
    .vod-item-visual img { width: 100%; height: 100%; object-fit: cover; }
    .vod-item-visual span {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 18px; font-weight: 800;
        color: white;
    }
    .vod-item-chip {
        position: absolute; bottom: 4px; right: 4px;
        width: 12px; height: 8px;
        border-radius: 2px;
        background: linear-gradient(135deg, rgba(254,224,94,0.85), rgba(245,158,11,0.65));
        border: 1px solid rgba(252,211,77,0.40);
    }
    .vod-item-body { flex: 1; min-width: 0; }
    .vod-item-brand {
        font-size: 14px; font-weight: 800;
        color: #0F172A;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .vod-item-name {
        font-size: 11px; color: #64748B;
        margin-top: 2px;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .vod-item-meta {
        font-size: 11px; color: #94A3B8;
        margin-top: 3px;
        font-variant-numeric: tabular-nums;
    }
    .vod-item-total {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 15px; font-weight: 800;
        color: #0F172A;
        font-variant-numeric: tabular-nums;
        text-align: right;
        flex-shrink: 0;
        line-height: 1.1;
    }
    .vod-item-total span {
        font-size: 9px; font-weight: 700;
        color: #94A3B8;
        display: block;
        margin-top: 2px;
    }

    /* TOTAUX */
    .vod-totals {
        margin-top: 14px;
        padding: 12px 14px;
        background: linear-gradient(135deg, #FAFBFC, #F8FAFC);
        border: 1px solid #E2E8F0;
        border-radius: 12px;
    }
    .vod-totals-row {
        display: flex; align-items: center; justify-content: space-between;
        gap: 8px;
        padding: 4px 0;
        font-size: 12px; color: #64748B;
        font-weight: 500;
    }
    .vod-totals-row--brand { color: #44A08D; font-weight: 700; }
    .vod-totals-num {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800;
        color: #0F172A;
        font-variant-numeric: tabular-nums;
    }
    .vod-totals-row--brand .vod-totals-num { color: #44A08D; }
    .vod-totals-divider {
        height: 1px;
        background: linear-gradient(90deg, transparent, #E2E8F0 30%, #E2E8F0 70%, transparent);
        margin: 8px 0;
    }
    .vod-totals-row--total { font-size: 13px; color: #0F172A; font-weight: 800; }
    .vod-totals-row--total .vod-totals-num { font-size: 20px; line-height: 1; }

    /* WARN / SUCCESS BLOCK */
    .vod-warn-row {
        display: flex; align-items: center; gap: 12px;
    }
    .vod-warn-spin {
        width: 36px; height: 36px;
        border-radius: 11px;
        background: rgba(180,83,9,0.10);
        color: #B45309;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .vod-warn-spin svg {
        width: 18px; height: 18px;
        animation: vod-spin 1.2s linear infinite;
    }
    .vod-warn-spin svg circle {
        stroke-dasharray: 40 100;
        stroke-linecap: round;
    }
    @keyframes vod-spin { to { transform: rotate(360deg); } }

    .vod-success-ico {
        width: 36px; height: 36px;
        border-radius: 11px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 4px 10px -3px rgba(78,205,196,0.45);
    }
    .vod-success-ico svg { width: 18px; height: 18px; }

    .vod-warn-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 14px; font-weight: 800;
        color: #0F172A;
        line-height: 1.2;
    }
    .vod-warn-text {
        font-size: 12px; color: #475569;
        margin-top: 3px;
        line-height: 1.4;
    }
    .vod-warn-note {
        margin-top: 10px;
        padding: 9px 12px;
        background: rgba(255,255,255,0.6);
        border-radius: 9px;
        font-family: 'JetBrains Mono','Fira Code',monospace;
        font-size: 11px; color: #78350F;
    }
    .vod-warn-note--info {
        font-family: 'Inter', sans-serif;
        color: #065F46;
    }

    /* ====================== QR CODE ====================== */
    .vod-qr-card {
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 22px;
        padding: 22px 20px;
        text-align: center;
        box-shadow: 0 12px 28px -12px rgba(15,23,42,0.14);
        position: relative;
        overflow: hidden;
    }
    .vod-qr-card::before {
        content: '';
        position: absolute; top: -60px; right: -60px;
        width: 200px; height: 200px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(78,205,196,0.12) 0%, transparent 70%);
    }
    .vod-qr-eyebrow {
        position: relative;
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.10em;
        color: #44A08D;
    }
    .vod-qr-title {
        position: relative;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 18px; font-weight: 800;
        color: #0F172A;
        margin: 4px 0 16px;
        letter-spacing: -0.01em;
    }
    .vod-qr-box {
        display: inline-block;
        padding: 16px;
        background: white;
        border: 2px solid #0F172A;
        border-radius: 16px;
        box-shadow: 0 8px 20px -8px rgba(15,23,42,0.20);
        position: relative;
    }
    .vod-qr-link {
        margin-top: 14px;
        padding: 10px 12px;
        background: #F8FAFC;
        border: 1px solid #F1F5F9;
        border-radius: 10px;
        text-align: left;
        position: relative;
    }
    .vod-qr-link-label {
        display: block;
        font-size: 9px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.06em;
        color: #94A3B8;
        margin-bottom: 4px;
    }
    .vod-qr-link-url {
        font-family: 'JetBrains Mono','Fira Code',monospace;
        font-size: 11px;
        color: #0F172A;
        word-break: break-all;
        line-height: 1.4;
    }
    .vod-qr-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-top: 12px;
        position: relative;
    }
    .vod-claim-note { font-size: 12.5px; color: #64748B; line-height: 1.6; margin: 8px 0 14px; }
    .vod-claim-sent {
        background: #F0FDF9; border: 1px solid #99F6E4; border-radius: 10px;
        padding: 10px 12px; margin-bottom: 12px; font-size: 12px; color: #0F766E;
    }
    .vod-claim-form { margin-bottom: 10px; }
    .vod-claim-form > button { width: 100%; margin-top: 10px; }
    .vod-claim-count { font-size: 11px; color: #94A3B8; text-align: center; margin: 8px 0 0; }
    .vod-claim-fallback { width: 100%; margin-top: 4px; }
    .vod-qr-btn--wa { background: #25D366; color: #fff; border-color: #25D366; }

    .vod-qr-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        padding: 11px;
        border: 0; border-radius: 11px;
        font-family: inherit;
        font-size: 12px; font-weight: 800;
        cursor: pointer;
        transition: all .15s ease;
    }
    .vod-qr-btn svg { width: 13px; height: 13px; }
    .vod-qr-btn--ghost {
        background: #F1F5F9;
        color: #475569;
    }
    .vod-qr-btn--ghost:hover { background: #0F172A; color: white; }
    .vod-qr-btn--brand {
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        box-shadow: 0 8px 18px -8px rgba(78,205,196,0.55), inset 0 1px 0 rgba(255,255,255,0.30);
    }
    .vod-qr-btn--brand:hover { transform: translateY(-1px); }

    .vod-qr-share {
        position: relative;
        display: flex; align-items: center; justify-content: center; gap: 6px;
        margin-top: 10px;
        padding: 11px;
        background: #25D366;
        color: white;
        border-radius: 11px;
        text-decoration: none;
        font-size: 12px; font-weight: 800;
        box-shadow: 0 8px 18px -8px rgba(37,211,102,0.50);
        transition: all .15s ease;
    }
    .vod-qr-share:hover { background: #1ebe5b; transform: translateY(-1px); }
    .vod-qr-share svg { width: 14px; height: 14px; }

    .vod-qr-foot {
        display: flex; align-items: center; justify-content: center; gap: 6px;
        padding: 10px 12px;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 11px;
        font-size: 11px; color: #64748B;
        text-align: center;
    }
    .vod-qr-foot svg { width: 13px; height: 13px; color: #44A08D; }
    .vod-qr-foot strong { color: #0F172A; font-weight: 800; }

    /* Actions row : retry + refund */
    .vod-actions {
        display: flex; gap: 8px; margin-top: 12px;
        flex-wrap: wrap;
    }
    .vod-refund-btn {
        flex: 1; min-width: 140px;
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        padding: 10px 16px;
        background: white;
        color: #BE123C;
        border: 1px solid #FECACA;
        border-radius: 10px;
        font-family: 'Inter', sans-serif;
        font-size: 13px; font-weight: 700;
        cursor: pointer;
        transition: all .2s ease;
    }
    .vod-refund-btn svg { width: 14px; height: 14px; }
    .vod-refund-btn:hover { background: #FEF2F2; border-color: #FCA5A5; }

    /* Modal remboursement */
    .vod-modal-backdrop {
        position: fixed; inset: 0; z-index: 100;
        background: rgba(15,23,42,0.55);
        backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
        display: flex; align-items: center; justify-content: center;
        padding: 16px;
    }
    .vod-modal {
        position: relative;
        width: 100%; max-width: 460px;
        background: white;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 28px 60px -16px rgba(15,23,42,0.50);
    }
    .vod-modal-banner {
        position: relative;
        padding: 22px 22px 18px;
        background: linear-gradient(135deg, #BE123C 0%, #F43F5E 60%, #FB7185 100%);
        color: white;
        display: flex; align-items: center; gap: 14px;
        overflow: hidden;
    }
    .vod-modal-glow {
        position: absolute; top: -60px; right: -60px;
        width: 220px; height: 220px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255,255,255,0.30) 0%, transparent 70%);
        pointer-events: none;
    }
    .vod-modal-icon {
        position: relative;
        width: 48px; height: 48px;
        border-radius: 14px;
        background: rgba(255,255,255,0.22);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        backdrop-filter: blur(6px);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.40);
    }
    .vod-modal-icon svg { width: 22px; height: 22px; }
    .vod-modal-eyebrow {
        position: relative;
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.10em;
        opacity: 0.95;
    }
    .vod-modal-title {
        position: relative;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 19px; font-weight: 800; line-height: 1.15;
        margin-top: 2px;
        letter-spacing: -0.01em;
    }
    .vod-modal-body { padding: 18px 22px; }
    .vod-modal-text { font-size: 14px; color: #475569; line-height: 1.5; margin: 0 0 12px; }
    .vod-modal-text strong { color: #0F172A; font-weight: 800; }
    .vod-modal-hint { font-size: 12px; color: #64748B; }
    .vod-modal-check {
        display: flex; align-items: flex-start; gap: 10px;
        padding: 10px 12px;
        background: #FEF3C7;
        border: 1px solid #FCD34D;
        border-radius: 10px;
        margin-bottom: 12px;
        cursor: pointer;
        font-size: 12px; color: #92400E; line-height: 1.4;
    }
    .vod-modal-check input { margin-top: 2px; flex-shrink: 0; }
    .vod-modal-check strong { color: #78350F; font-weight: 800; }
    .vod-modal-impact {
        font-size: 11px; color: #64748B;
        background: #F8FAFC; border: 1px solid #E2E8F0;
        padding: 10px 12px;
        border-radius: 10px;
        margin: 0;
        line-height: 1.5;
    }
    .vod-modal-footer {
        display: flex; gap: 8px;
        padding: 14px 18px 18px;
        background: linear-gradient(180deg, white, #F8FAFC);
    }
    .vod-modal-cancel, .vod-modal-confirm {
        flex: 1;
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        padding: 12px 14px;
        border-radius: 12px;
        font-family: 'Inter', sans-serif;
        font-size: 13px; font-weight: 700;
        cursor: pointer;
        border: 1px solid transparent;
        transition: all .2s ease;
    }
    .vod-modal-cancel {
        background: white; border-color: #E2E8F0; color: #475569;
    }
    .vod-modal-cancel:hover { background: #F8FAFC; border-color: #94A3B8; }
    .vod-modal-confirm {
        background: linear-gradient(135deg, #BE123C, #F43F5E);
        color: white;
        box-shadow: 0 8px 18px -6px rgba(244,63,94,0.50), inset 0 1px 0 rgba(255,255,255,0.30);
    }
    .vod-modal-confirm svg { width: 14px; height: 14px; }
    .vod-modal-confirm:hover { transform: translateY(-1px); }
    @media (max-width: 480px) {
        .vod-modal-footer { flex-direction: column-reverse; }
    }
</style>

@endsection
