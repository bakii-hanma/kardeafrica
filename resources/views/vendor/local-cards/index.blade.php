@extends('vendor.layouts.vendor')

@section('title', 'Cartes locales — Carte Gabon')

@section('content')
<div style="max-width:1080px;margin:0 auto;">

    @include('vendor.partials._sell-mode-switch', ['mode' => 'local'])

    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
        <div>
            <h1 style="font-size:22px;font-weight:800;color:#0F172A;margin:0;">Cartes locales · Carte Gabon</h1>
            <p style="font-size:13px;color:#64748B;margin:4px 0 0;">
                Réservez une carte, encaissez le client, puis <strong>« Récupérer la carte »</strong> pour activer le code.
                Votre commission : <strong>{{ number_format($defaultRate, 1, ',', ' ') }}%</strong> conservée sur chaque vente.
            </p>
        </div>
        <div style="background:#F0FDF9;border:1px solid #99F6E4;border-radius:12px;padding:10px 16px;font-size:13px;color:#0F766E;">
            Solde wallet : <strong>{{ number_format((float) $reseller->wallet_balance, 0, ',', ' ') }} FCFA</strong>
        </div>
    </div>

    @if (session('success'))
        <div style="background:#D1FAE5;border:1px solid #6EE7B7;color:#047857;border-radius:12px;padding:12px 16px;font-size:13px;font-weight:600;margin-bottom:14px;">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div style="background:#FEE2E2;border:1px solid #FCA5A5;color:#B91C1C;border-radius:12px;padding:12px 16px;font-size:13px;font-weight:600;margin-bottom:14px;">
            {{ session('error') }}
        </div>
    @endif
    @if ($errors->any())
        <div style="background:#FEE2E2;border:1px solid #FCA5A5;color:#B91C1C;border-radius:12px;padding:12px 16px;font-size:13px;margin-bottom:14px;">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- ============ Catalogue ============ --}}
    <div class="vlc-cat-head">
        <h2 class="vlc-h2">
            Catalogue disponible
            <span class="vlc-count">{{ $cards->total() }}</span>
        </h2>
        @if($activeFiltersCount > 0)
            <a href="{{ route('vendor.local-cards.index') }}" class="vlc-clear">Tout effacer</a>
        @endif
    </div>

    {{-- Mêmes filtres que la vitrine publique /gabon --}}
    <form method="GET" class="vlc-filters">
        <div class="vlc-search">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="search" name="search" value="{{ $search }}"
                   placeholder="Rechercher un commerçant…" class="vlc-search-input" aria-label="Rechercher une carte locale">
        </div>

        <select name="category" onchange="this.form.submit()" class="vlc-select" aria-label="Catégorie">
            <option value="">Toutes les catégories</option>
            @foreach($categories as $slug => $label)
                <option value="{{ $slug }}" @selected($categorySlug === $slug)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="sort" onchange="this.form.submit()" class="vlc-select" aria-label="Trier">
            @foreach($sortOptions as $value => $label)
                <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
            @endforeach
        </select>

        <button type="submit" class="vlc-search-btn">Filtrer</button>

        <div class="vlc-ranges">
            @foreach($priceRangeLabels as $key => $label)
                @php $on = in_array($key, $priceRanges, true); @endphp
                <label class="vlc-range {{ $on ? 'is-on' : '' }}">
                    <input type="checkbox" name="price_range[]" value="{{ $key }}"
                           onchange="this.form.submit()" {{ $on ? 'checked' : '' }} hidden>
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </form>

    @if ($cards->isEmpty())
        <p class="vlc-none">
            @if($activeFiltersCount > 0)
                Aucune carte ne correspond à ces filtres.
            @else
                Aucune carte locale n'est publiée pour le moment.
            @endif
        </p>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;">
            @foreach ($cards as $card)
                <div class="vlc-tile">
                    {{-- Photo de la carte, comme sur la vitrine publique. --}}
                    <div class="vlc-tile-visual">
                        @if ($card->visual_url)
                            <img src="{{ asset($card->visual_url) }}" alt="" loading="lazy">
                        @else
                            <span class="vlc-tile-initial">{{ mb_strtoupper(mb_substr($card->name, 0, 1)) }}</span>
                        @endif
                        <span class="vlc-tile-cat">{{ $categories[$card->category] ?? 'Carte' }}</span>
                    </div>

                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                        <div style="min-width:0;">
                            <div style="font-weight:700;color:#0F172A;font-size:14px;line-height:1.2;">{{ $card->name }}</div>
                            <div style="font-size:11px;color:#94A3B8;">{{ $card->owner?->business_name }}@if($card->owner?->city) · {{ $card->owner->city }}@endif</div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('vendor.local-cards.store') }}" style="margin:0;">
                        @csrf
                        <input type="hidden" name="merchant_card_id" value="{{ $card->id }}">

                        <label class="vlc-label" for="amount-{{ $card->id }}">Montant (FCFA)</label>
                        @php $denoms = collect($card->denominations ?? [])->filter(fn ($d) => (float) $d > 0)->values(); @endphp
                        @if ($denoms->isNotEmpty())
                            <select id="amount-{{ $card->id }}" name="amount" required class="vlc-input">
                                @foreach ($denoms as $d)
                                    <option value="{{ (int) $d }}">{{ number_format((float) $d, 0, ',', ' ') }} FCFA</option>
                                @endforeach
                            </select>
                        @else
                            <input id="amount-{{ $card->id }}" type="number" name="amount" required inputmode="numeric"
                                   min="{{ (int) ($card->min_amount ?? 1) }}" max="{{ (int) ($card->max_amount ?? 1000000) }}"
                                   placeholder="Montant libre" class="vlc-input">
                        @endif

                        <label class="vlc-label" for="buyer-{{ $card->id }}">Client <span style="font-weight:600;color:#94A3B8;">(facultatif)</span></label>
                        <input id="buyer-{{ $card->id }}" type="text" name="buyer_name" maxlength="120"
                               placeholder="Nom du client" class="vlc-input">

                        {{-- buyer_phone était validé côté serveur mais n'existait
                             sur aucun écran : le champ n'était jamais rempli. --}}
                        <label class="vlc-label" for="buyerphone-{{ $card->id }}">Téléphone <span style="font-weight:600;color:#94A3B8;">(facultatif)</span></label>
                        <input id="buyerphone-{{ $card->id }}" type="tel" name="buyer_phone" maxlength="30" inputmode="tel"
                               placeholder="06 12 34 56" class="vlc-input">

                        <button type="submit" class="vlc-submit">Vendre cette carte</button>
                    </form>
                </div>
            @endforeach
        </div>

        <div style="margin-top:16px;">{{ $cards->links() }}</div>
    @endif

    {{-- L'historique des ventes vit désormais dans « Mes ventes », fusionné avec
         celui des cartes digitales : deux listes séparées obligeaient le vendeur
         à additionner sa journée de tête. --}}
    @if ($salesCount > 0)
        <a href="{{ route('vendor.orders', ['type' => 'local']) }}" class="vlc-history">
            <span>
                <strong>{{ $salesCount }}</strong> vente{{ $salesCount > 1 ? 's' : '' }} de Cartes Gabon
                <span class="vlc-history-sub">Historique complet dans « Mes ventes »</span>
            </span>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
    @endif
</div>

@push('head')
<style>
    .vlc-history {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        margin-top: 22px; padding: 14px 16px;
        background: #fff; border: 1px solid #E7EBF0; border-radius: 14px;
        color: #0F172A; font-size: 13.5px; font-weight: 700; text-decoration: none;
    }
    .vlc-history:hover { border-color: #CBD5E1; }
    .vlc-history strong { color: #0B7F72; }
    .vlc-history-sub { display: block; font-size: 11.5px; font-weight: 600; color: #64748B; margin-top: 2px; }
    .vlc-history svg { width: 15px; height: 15px; color: #94A3B8; flex: none; }

    /* ===== Barre de filtres, alignée sur la vitrine publique ===== */
    .vlc-cat-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 22px 0 10px; }
    .vlc-h2 { font-size: 15px; font-weight: 800; color: #0F172A; margin: 0; display: flex; align-items: center; gap: 8px; }
    .vlc-count { background: #F1F5F9; color: #475569; font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 9999px; }
    .vlc-clear { font-size: 12.5px; font-weight: 700; color: #0F766E; text-decoration: none; }
    .vlc-filters {
        display: grid; grid-template-columns: 1fr; gap: 8px;
        background: #fff; border: 1px solid #E2E8F0; border-radius: 14px;
        padding: 12px; margin-bottom: 14px;
    }
    @media (min-width: 768px) { .vlc-filters { grid-template-columns: 2fr 1fr 1fr auto; align-items: center; } }
    .vlc-search { position: relative; display: flex; align-items: center; }
    .vlc-search svg { position: absolute; left: 11px; width: 15px; height: 15px; color: #94A3B8; pointer-events: none; }
    .vlc-search-input {
        width: 100%; min-height: 44px; padding: 0 12px 0 34px;
        border: 1px solid #CBD5E1; border-radius: 10px;
        font-family: inherit; font-size: 14px; color: #0F172A;
    }
    .vlc-select {
        width: 100%; min-height: 44px; padding: 0 30px 0 11px;
        border: 1px solid #CBD5E1; border-radius: 10px;
        font-family: inherit; font-size: 13.5px; color: #0F172A; background-color: #fff;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2.5'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 10px center; background-size: 14px;
    }
    .vlc-search-input:focus, .vlc-select:focus { outline: 3px solid #4ECDC4; outline-offset: 1px; border-color: #44A08D; }
    .vlc-search-btn {
        min-height: 44px; padding: 0 18px;
        background: #0F172A; color: #fff; border: 0; border-radius: 10px;
        font-family: inherit; font-size: 13.5px; font-weight: 700; cursor: pointer;
    }
    .vlc-ranges { display: flex; flex-wrap: wrap; gap: 6px; grid-column: 1 / -1; }
    .vlc-range {
        min-height: 36px; display: inline-flex; align-items: center; padding: 0 12px;
        border: 1px solid #E2E8F0; border-radius: 9999px; background: #F8FAFC;
        font-size: 12px; font-weight: 700; color: #475569; cursor: pointer;
    }
    .vlc-range.is-on { background: #0F172A; border-color: #0F172A; color: #fff; }
    .vlc-none { font-size: 13px; color: #64748B; }

    /* ===== Tuile de carte avec sa photo ===== */
    .vlc-tile { background: #fff; border: 1px solid #E2E8F0; border-radius: 16px; padding: 16px; }
    .vlc-tile-visual {
        position: relative; aspect-ratio: 16 / 9; margin: -16px -16px 12px;
        border-radius: 16px 16px 0 0; overflow: hidden;
        background: linear-gradient(135deg, #0F172A, #1E293B);
        display: flex; align-items: center; justify-content: center;
    }
    .vlc-tile-visual img { width: 100%; height: 100%; object-fit: cover; }
    .vlc-tile-initial { font-family: 'Space Grotesk','Inter',sans-serif; font-size: 34px; font-weight: 800; color: rgba(255,255,255,.85); }
    .vlc-tile-cat {
        position: absolute; left: 10px; bottom: 10px;
        background: rgba(15,23,42,.72); color: #fff;
        font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
        padding: 4px 9px; border-radius: 9999px;
    }

    .vlc-list { display: flex; flex-direction: column; gap: 8px; }
    .vlc-row {
        display: flex; align-items: center; gap: 12px;
        min-height: 64px; padding: 12px 14px;
        background: #fff; border: 1px solid #E2E8F0; border-radius: 14px;
        text-decoration: none; transition: border-color .15s ease;
    }
    .vlc-row:hover { border-color: #CBD5E1; }
    /* Une carte réservée non récupérée est de l'argent en attente : on la marque. */
    .vlc-row--todo { border-left: 4px solid #D97706; }
    .vlc-main { flex: 1; min-width: 0; }
    .vlc-l1 { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .vlc-card {
        font-size: 14px; font-weight: 700; color: #0F172A;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%;
    }
    .vlc-badge {
        border-radius: 9999px; padding: 3px 9px;
        font-size: 10px; font-weight: 800; text-transform: uppercase;
        letter-spacing: .03em; white-space: nowrap;
    }
    .vlc-l2 { font-size: 12px; color: #64748B; margin-top: 3px; font-variant-numeric: tabular-nums; }
    .vlc-right { text-align: right; flex-shrink: 0; }
    .vlc-amount {
        display: block;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 14.5px; font-weight: 800; color: #0F172A; font-variant-numeric: tabular-nums;
    }
    .vlc-amount span { font-size: 10px; font-weight: 700; color: #64748B; }
    .vlc-action { display: block; font-size: 11.5px; font-weight: 700; color: #0F766E; margin-top: 2px; }
    .vlc-row:focus-visible { outline: 3px solid #4ECDC4; outline-offset: 2px; }

    /* Contrôles du formulaire de vente — 48px de haut (cible tactile) */
    .vlc-label {
        display: block; font-size: 11px; font-weight: 800;
        letter-spacing: .05em; text-transform: uppercase;
        color: #64748B; margin: 10px 0 5px;
    }
    .vlc-input {
        width: 100%; min-height: 48px; padding: 0 12px;
        border: 1px solid #CBD5E1; border-radius: 11px;
        font-family: inherit; font-size: 14.5px; color: #0F172A; background: #fff;
    }
    .vlc-input:focus { outline: 3px solid #4ECDC4; outline-offset: 1px; border-color: #44A08D; }
    select.vlc-input { appearance: none; padding-right: 34px;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2.5'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 12px center; background-size: 15px; }
    .vlc-submit {
        width: 100%; min-height: 48px; margin-top: 14px;
        background: #0F172A; color: #fff; border: 0; border-radius: 11px;
        font-family: inherit; font-size: 14px; font-weight: 700; cursor: pointer;
    }
    .vlc-submit:hover { background: #1E293B; }
    .vlc-submit:focus-visible { outline: 3px solid #4ECDC4; outline-offset: 2px; }
</style>
@endpush
@endsection
