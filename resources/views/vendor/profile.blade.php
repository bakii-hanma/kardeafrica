@extends('vendor.layouts.vendor')

@section('title', 'Mon compte')

@section('content')

{{-- ============ IDENTITÉ ============ --}}
<section class="vp-identity">
    <div class="vp-identity-glow"></div>

    <div class="vp-identity-row">
        <div class="vp-avatar">{{ strtoupper(substr($reseller->name, 0, 1)) }}</div>
        <div class="vp-id-body">
            <div class="vp-id-eyebrow">
                <span class="vp-id-pulse"></span>
                Vendeur {{ $reseller->is_active ? 'actif' : 'bloqué' }}
            </div>
            <h1 class="vp-id-name">{{ $reseller->name }}</h1>
            <div class="vp-id-code">{{ $reseller->vendor_code }}</div>
        </div>
    </div>

    @if($reseller->phone || $reseller->email)
    <div class="vp-id-contact">
        @if($reseller->phone)
            <div class="vp-id-contact-item">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                <span>{{ $reseller->phone }}</span>
            </div>
        @endif
        @if($reseller->email)
            <div class="vp-id-contact-item">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <span>{{ $reseller->email }}</span>
            </div>
        @endif
    </div>
    @endif
</section>

{{-- ============ FINANCES ============ --}}
<section style="margin-top:18px;">
    <h2 class="vp-section-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
        Finances
    </h2>

    {{-- Deux portefeuilles : Vente (brand teal) + Commission (gold) --}}
    <div class="vp-balances">
        <div class="vp-balance">
            <div class="vp-balance-glow"></div>
            <div class="vp-balance-eyebrow">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Solde de vente
            </div>
            <div class="vp-balance-num">
                <span>{{ number_format($reseller->wallet_balance, 0, ',', ' ') }}</span>
                <span class="vp-balance-unit">FCFA</span>
            </div>
            <div class="vp-balance-cap">
                sur <strong>{{ number_format($reseller->max_wallet, 0, ',', ' ') }}</strong> FCFA · plafond
            </div>
            <div class="vp-balance-bar">
                <div class="vp-balance-bar-fill" style="width:{{ min(100, $reseller->wallet_percentage) }}%;"></div>
            </div>
            <div class="vp-balance-pct">{{ $reseller->wallet_percentage }}% utilisé</div>
        </div>

        <div class="vp-balance vp-balance--gold">
            <div class="vp-balance-glow"></div>
            <div class="vp-balance-eyebrow">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                Solde commissions
            </div>
            <div class="vp-balance-num">
                <span>{{ number_format($reseller->commission_balance, 0, ',', ' ') }}</span>
                <span class="vp-balance-unit">FCFA</span>
            </div>
            <div class="vp-balance-cap">
                cumul depuis l'inscription · <strong>{{ number_format($reseller->total_commission_earned, 0, ',', ' ') }}</strong> FCFA
            </div>
            <div class="vp-balance-pct" style="margin-top:10px;">
                Gagnées sur tes ventes — séparées de ton solde de vente
            </div>
        </div>
    </div>

    {{-- ============= TRANSFERT DES COMMISSIONS =============
         Le solde de commissions n'avait aucune sortie : il s'accumulait sans
         que le vendeur puisse jamais en disposer. Il peut désormais le
         convertir en pouvoir de vente. --}}
    @if((float) $reseller->commission_balance > 0)
        <div class="vp-transfer" x-data="{ open: false, amount: {{ (int) $transferable }} }">
            <div class="vp-transfer-head">
                <div>
                    <div class="vp-transfer-title">Utiliser mes commissions</div>
                    <p class="vp-transfer-lead">
                        Transfère-les vers ton solde de vente pour acheter d'autres cartes.
                    </p>
                </div>
                @if($transferable > 0)
                    <button type="button" class="vp-transfer-toggle" @click="open = !open"
                            :aria-expanded="open ? 'true' : 'false'">
                        <span x-show="!open">Transférer</span>
                        <span x-show="open" x-cloak>Annuler</span>
                    </button>
                @endif
            </div>

            @if($transferable <= 0)
                <p class="vp-transfer-blocked">
                    Ta cagnotte est pleine ({{ number_format($reseller->wallet_balance, 0, ',', ' ') }} FCFA
                    sur {{ number_format($reseller->max_wallet, 0, ',', ' ') }} FCFA).
                    Vends quelques cartes pour faire de la place, puis reviens transférer tes commissions.
                </p>
            @else
                <form method="POST" action="{{ route('vendor.commissions.transfer') }}" x-show="open" x-cloak class="vp-transfer-form">
                    @csrf
                    <label class="vp-transfer-label" for="commission-amount">Montant à transférer</label>
                    <div class="vp-transfer-row">
                        <input id="commission-amount" type="number" name="amount" x-model="amount"
                               min="100" max="{{ (int) $transferable }}" step="100" required
                               class="vp-transfer-input" inputmode="numeric">
                        <button type="button" class="vp-transfer-max" @click="amount = {{ (int) $transferable }}">
                            Tout ({{ number_format($transferable, 0, ',', ' ') }})
                        </button>
                    </div>
                    <p class="vp-transfer-hint">
                        Maximum transférable : <strong>{{ number_format($transferable, 0, ',', ' ') }} FCFA</strong>
                        @if($transferable < (float) $reseller->commission_balance)
                            — limité par la place restante dans ta cagnotte.
                        @endif
                    </p>
                    <button type="submit" class="vp-transfer-submit">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        Transférer vers mon solde de vente
                    </button>
                </form>
            @endif
        </div>
    @endif

    {{-- Mini stats financières --}}
    <div class="vp-finance-grid">
        <div class="vp-fin-card vp-fin-card--brand">
            <div class="vp-fin-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
            <div class="vp-fin-body">
                <div class="vp-fin-label">Commission</div>
                <div class="vp-fin-value">{{ rtrim(rtrim(number_format($reseller->commission_rate, 2), '0'), '.') }}<span class="vp-fin-unit">%</span></div>
                <div class="vp-fin-meta">par vente</div>
            </div>
        </div>

        <div class="vp-fin-card">
            <div class="vp-fin-icon vp-fin-icon--blue">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2"/></svg>
            </div>
            <div class="vp-fin-body">
                <div class="vp-fin-label">Volume cumulé</div>
                <div class="vp-fin-value">{{ number_format($stats['volume_total'], 0, ',', ' ') }}<span class="vp-fin-unit">FCFA</span></div>
                <div class="vp-fin-meta">{{ $stats['orders_completed'] }} ventes livrées</div>
            </div>
        </div>

        <div class="vp-fin-card">
            <div class="vp-fin-icon vp-fin-icon--purple">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div class="vp-fin-body">
                <div class="vp-fin-label">Total commandes</div>
                <div class="vp-fin-value">{{ $stats['orders_total'] }}<span class="vp-fin-unit">vts</span></div>
                <div class="vp-fin-meta">tous statuts confondus</div>
            </div>
        </div>
    </div>
</section>

{{-- ============ MES INFORMATIONS ============
     L'écran était en lecture seule intégrale : corriger un numéro ou
     enregistrer un compte Mobile Money imposait de passer par un admin. --}}
<section style="margin-top:20px;">
    <h2 class="vp-section-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        Mes informations
    </h2>

    <form method="POST" action="{{ route('vendor.profile.update') }}" class="vp-form">
        @csrf @method('PUT')

        <div class="vp-form-grid">
            <div class="vp-field">
                <label class="vp-flabel" for="name">Nom complet</label>
                <input id="name" name="name" type="text" required maxlength="120"
                       value="{{ old('name', $reseller->name) }}"
                       class="vp-input {{ $errors->has('name') ? 'has-error' : '' }}">
                @error('name')<p class="vp-ferror">{{ $message }}</p>@enderror
            </div>

            <div class="vp-field">
                <label class="vp-flabel" for="phone">Téléphone</label>
                <input id="phone" name="phone" type="tel" required maxlength="30" inputmode="tel"
                       value="{{ old('phone', $reseller->phone) }}"
                       class="vp-input {{ $errors->has('phone') ? 'has-error' : '' }}">
                <p class="vp-fhint">Ce numéro reçoit le code si tu oublies ton mot de passe.</p>
                @error('phone')<p class="vp-ferror">{{ $message }}</p>@enderror
            </div>

            <div class="vp-field">
                <label class="vp-flabel" for="email">Email <span class="vp-opt">(facultatif)</span></label>
                <input id="email" name="email" type="email" maxlength="120"
                       value="{{ old('email', $reseller->email) }}"
                       class="vp-input {{ $errors->has('email') ? 'has-error' : '' }}">
                @error('email')<p class="vp-ferror">{{ $message }}</p>@enderror
            </div>

            <div class="vp-field">
                <label class="vp-flabel" for="whatsapp_number">WhatsApp <span class="vp-opt">(facultatif)</span></label>
                <input id="whatsapp_number" name="whatsapp_number" type="tel" maxlength="30" inputmode="tel"
                       value="{{ old('whatsapp_number', $reseller->whatsapp_number) }}"
                       class="vp-input {{ $errors->has('whatsapp_number') ? 'has-error' : '' }}">
                @error('whatsapp_number')<p class="vp-ferror">{{ $message }}</p>@enderror
            </div>

            <div class="vp-field">
                <label class="vp-flabel" for="mobile_money_provider">Opérateur Mobile Money</label>
                <select id="mobile_money_provider" name="mobile_money_provider" class="vp-input">
                    <option value="">— Aucun —</option>
                    <option value="airtel" @selected(old('mobile_money_provider', $reseller->mobile_money_provider) === 'airtel')>Airtel Money</option>
                    <option value="moov"   @selected(old('mobile_money_provider', $reseller->mobile_money_provider) === 'moov')>Moov Money</option>
                </select>
            </div>

            <div class="vp-field">
                <label class="vp-flabel" for="mobile_money_account">Numéro Mobile Money</label>
                <input id="mobile_money_account" name="mobile_money_account" type="tel" maxlength="30" inputmode="tel"
                       value="{{ old('mobile_money_account', $reseller->mobile_money_account) }}"
                       class="vp-input {{ $errors->has('mobile_money_account') ? 'has-error' : '' }}">
                @error('mobile_money_account')<p class="vp-ferror">{{ $message }}</p>@enderror
            </div>
        </div>

        <p class="vp-fhint" style="margin-top:12px;">
            Ton code vendeur, tes soldes, ton taux de commission et ton plafond sont fixés
            par KardAfrica et ne se modifient pas ici.
        </p>

        <button type="submit" class="vp-fsubmit">Enregistrer mes informations</button>
    </form>
</section>

{{-- ============ MOT DE PASSE ============ --}}
<section style="margin-top:20px;">
    <h2 class="vp-section-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        Mot de passe
    </h2>

    <form method="POST" action="{{ route('vendor.profile.password') }}" class="vp-form">
        @csrf @method('PUT')

        <div class="vp-form-grid">
            <div class="vp-field vp-field--full">
                <label class="vp-flabel" for="current_password">Mot de passe actuel</label>
                <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                       class="vp-input {{ $errors->has('current_password') ? 'has-error' : '' }}">
                @error('current_password')<p class="vp-ferror">{{ $message }}</p>@enderror
            </div>

            <div class="vp-field">
                <label class="vp-flabel" for="new_password">Nouveau mot de passe</label>
                <input id="new_password" name="password" type="password" required autocomplete="new-password" minlength="8"
                       class="vp-input {{ $errors->has('password') ? 'has-error' : '' }}">
                <p class="vp-fhint">8 caractères minimum.</p>
                @error('password')<p class="vp-ferror">{{ $message }}</p>@enderror
            </div>

            <div class="vp-field">
                <label class="vp-flabel" for="password_confirmation">Confirme le nouveau</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                       class="vp-input">
            </div>
        </div>

        <button type="submit" class="vp-fsubmit">Modifier mon mot de passe</button>
    </form>
</section>

{{-- ============ ACTIONS RAPIDES ============ --}}
<section style="margin-top:20px;">
    <h2 class="vp-section-title">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        Raccourcis
    </h2>
    <div class="vp-actions">
        <a href="{{ route('vendor.sell') }}" class="vp-action vp-action--primary">
            <div class="vp-action-icon"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg></div>
            <div class="vp-action-body">
                <div class="vp-action-title">Nouvelle vente</div>
                <div class="vp-action-sub">Encaisser un client</div>
            </div>
            <svg class="vp-action-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
        <a href="{{ route('vendor.orders') }}" class="vp-action">
            <div class="vp-action-icon vp-action-icon--alt"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/></svg></div>
            <div class="vp-action-body">
                <div class="vp-action-title">Mes ventes</div>
                <div class="vp-action-sub">Historique &amp; QR codes</div>
            </div>
            <svg class="vp-action-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
        <a href="{{ route('vendor.dashboard') }}" class="vp-action">
            <div class="vp-action-icon vp-action-icon--alt"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3"/></svg></div>
            <div class="vp-action-body">
                <div class="vp-action-title">Tableau de bord</div>
                <div class="vp-action-sub">Stats du jour</div>
            </div>
            <svg class="vp-action-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>
</section>

{{-- ============ DERNIERS MOUVEMENTS ============ --}}
<section style="margin-top:20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <h2 class="vp-section-title" style="margin:0;">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7"/></svg>
            Derniers mouvements
        </h2>
        {{-- Cette liste est tronquée à 8 : le relevé complet est paginé,
             filtrable et exportable. --}}
        <a href="{{ route('vendor.statement') }}"
           style="display:inline-flex;align-items:center;gap:5px;min-height:44px;padding:0 8px;color:#0F766E;font-size:13px;font-weight:700;text-decoration:none;">
            Voir tout mon relevé
            <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>
    @if($recentTransactions->count() > 0)
        <div class="vp-tx-list">
            @foreach($recentTransactions as $tx)
                @php
                    $txMap = [
                        'credit'     => ['#047857', '#D1FAE5', '↓', 'Recharge admin'],
                        'commission' => ['#B45309', '#FEF3C7', '+', 'Commission'],
                        'debit'      => ['#BE123C', '#FFE4E6', '↑', 'Vente'],
                        'adjustment' => ['#0369A1', '#E0F2FE', '±', 'Ajustement'],
                    ];
                    $tx_  = $txMap[$tx->type] ?? ['#475569','#F1F5F9','·',ucfirst($tx->type)];
                @endphp
                <div class="vp-tx">
                    <div class="vp-tx-icon" style="background:{{ $tx_[1] }};color:{{ $tx_[0] }};">{{ $tx_[2] }}</div>
                    <div class="vp-tx-body">
                        <div class="vp-tx-label">
                            {{ $tx_[3] }}
                            <span class="vp-tx-tag {{ ($tx->wallet ?? 'sales') === 'commission' ? 'vp-tx-tag--gold' : '' }}">
                                {{ ($tx->wallet ?? 'sales') === 'commission' ? 'Commissions' : 'Vente' }}
                            </span>
                        </div>
                        <div class="vp-tx-meta">{{ $tx->created_at->format('d/m/Y H:i') }}{{ $tx->reference ? ' · ' . $tx->reference : '' }}</div>
                    </div>
                    <div class="vp-tx-amount" style="color:{{ $tx_[0] }};">
                        {{ in_array($tx->type, ['debit']) ? '−' : '+' }}{{ number_format($tx->amount, 0, ',', ' ') }}
                        <span class="vp-tx-unit">FCFA</span>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="vp-empty">
            Aucun mouvement enregistré.
        </div>
    @endif
</section>

{{-- ============ DECONNEXION ============ --}}
<section style="margin-top:20px;">
    <form method="POST" action="{{ route('vendor.logout') }}" style="margin:0;">
        @csrf
        <button type="submit" class="vp-logout">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Se déconnecter
        </button>
    </form>
    <div class="vp-foot">
        Besoin d'aide ? <a href="mailto:hello@kardafrica.com">hello@kardafrica.com</a>
    </div>
</section>

@push('head')
<style>
    /* ===== Transfert des commissions vers le solde de vente ===== */
    .vp-transfer {
        background: #fff; border: 1px solid #E2E8F0; border-radius: 16px;
        padding: 16px 18px; margin-bottom: 16px;
    }
    .vp-transfer-head {
        display: flex; align-items: flex-start; justify-content: space-between;
        gap: 12px; flex-wrap: wrap;
    }
    .vp-transfer-title {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 15px; font-weight: 800; color: #0F172A;
    }
    .vp-transfer-lead { font-size: 12.5px; color: #64748B; margin: 3px 0 0; line-height: 1.45; }
    .vp-transfer-toggle {
        min-height: 44px; padding: 0 18px;
        background: #0F766E; color: #fff; border: 0; border-radius: 11px;
        font-family: inherit; font-size: 13.5px; font-weight: 700; cursor: pointer;
        flex-shrink: 0;
    }
    .vp-transfer-toggle:hover { background: #115E59; }
    .vp-transfer-blocked {
        font-size: 12.5px; color: #B45309; background: #FFFBEB;
        border: 1px solid #FDE68A; border-radius: 11px;
        padding: 10px 12px; margin: 12px 0 0; line-height: 1.5;
    }
    .vp-transfer-form { margin-top: 14px; border-top: 1px solid #F1F5F9; padding-top: 14px; }
    .vp-transfer-label {
        display: block; font-size: 11px; font-weight: 800;
        letter-spacing: .08em; text-transform: uppercase; color: #64748B; margin-bottom: 6px;
    }
    .vp-transfer-row { display: flex; gap: 8px; }
    .vp-transfer-input {
        flex: 1; min-width: 0; min-height: 48px; padding: 0 14px;
        border: 1px solid #CBD5E1; border-radius: 11px;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 17px; font-weight: 800; color: #0F172A;
        font-variant-numeric: tabular-nums;
    }
    .vp-transfer-input:focus { outline: 3px solid #4ECDC4; outline-offset: 1px; border-color: #44A08D; }
    .vp-transfer-max {
        min-height: 48px; padding: 0 14px; flex-shrink: 0;
        background: #F1F5F9; border: 1px solid #E2E8F0; border-radius: 11px;
        font-family: inherit; font-size: 12.5px; font-weight: 700; color: #334155;
        cursor: pointer; font-variant-numeric: tabular-nums;
    }
    .vp-transfer-max:hover { background: #E2E8F0; }
    .vp-transfer-hint { font-size: 12px; color: #64748B; margin: 8px 0 0; line-height: 1.45; }
    .vp-transfer-hint strong { color: #0F172A; }
    .vp-transfer-submit {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        width: 100%; min-height: 50px; margin-top: 12px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: #fff; border: 0; border-radius: 12px;
        font-family: inherit; font-size: 14.5px; font-weight: 800; cursor: pointer;
    }
    .vp-transfer-submit svg { width: 16px; height: 16px; }
    .vp-transfer-submit:hover { filter: brightness(1.05); }

    /* ===== Formulaires du profil (informations + mot de passe) ===== */
    .vp-form {
        background: #fff; border: 1px solid #E2E8F0; border-radius: 16px;
        padding: 16px 18px;
    }
    .vp-form-grid { display: grid; grid-template-columns: 1fr; gap: 14px; }
    @media (min-width: 640px) { .vp-form-grid { grid-template-columns: 1fr 1fr; } }
    .vp-field--full { grid-column: 1 / -1; }
    .vp-flabel {
        display: block; font-size: 11px; font-weight: 800;
        letter-spacing: .08em; text-transform: uppercase; color: #64748B; margin-bottom: 6px;
    }
    .vp-opt { font-weight: 600; text-transform: none; letter-spacing: 0; color: #94A3B8; }
    .vp-input {
        width: 100%; min-height: 48px; padding: 0 13px;
        border: 1px solid #CBD5E1; border-radius: 11px;
        font-family: inherit; font-size: 14.5px; color: #0F172A; background: #fff;
    }
    .vp-input:focus { outline: 3px solid #4ECDC4; outline-offset: 1px; border-color: #44A08D; }
    .vp-input.has-error { border-color: #FCA5A5; background: #FEF2F2; }
    select.vp-input { appearance: none; padding-right: 34px;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2.5'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 12px center; background-size: 15px; }
    .vp-fhint { font-size: 11.5px; color: #64748B; margin: 6px 0 0; line-height: 1.45; }
    .vp-ferror { font-size: 12px; color: #BE123C; margin: 6px 0 0; font-weight: 600; }
    .vp-fsubmit {
        min-height: 48px; margin-top: 14px; padding: 0 20px;
        background: #0F172A; color: #fff; border: 0; border-radius: 11px;
        font-family: inherit; font-size: 14px; font-weight: 700; cursor: pointer;
    }
    .vp-fsubmit:hover { background: #1E293B; }
    .vp-form :focus-visible { outline: 3px solid #4ECDC4; outline-offset: 2px; }
</style>
<style>
    /* ====================== IDENTITÉ ====================== */
    .vp-identity {
        position: relative;
        background: linear-gradient(135deg, #0F172A 0%, #1E293B 50%, #0F172A 100%);
        border-radius: 22px;
        padding: 22px 20px;
        color: white;
        overflow: hidden;
        box-shadow: 0 20px 40px -16px rgba(15,23,42,0.40);
    }
    .vp-identity-glow {
        position: absolute;
        top: -80px; right: -80px;
        width: 240px; height: 240px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(78,205,196,0.20) 0%, transparent 70%);
        pointer-events: none;
    }
    .vp-identity-row {
        position: relative;
        display: flex; align-items: center; gap: 14px;
    }
    .vp-avatar {
        width: 64px; height: 64px;
        border-radius: 18px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        display: flex; align-items: center; justify-content: center;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800; font-size: 28px;
        flex-shrink: 0;
        box-shadow: 0 14px 30px -8px rgba(78,205,196,0.50),
                    inset 0 1px 0 rgba(255,255,255,0.30);
    }
    .vp-id-body { flex: 1; min-width: 0; }
    .vp-id-eyebrow {
        display: inline-flex; align-items: center; gap: 7px;
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.10em;
        color: #5EEAD4;
        margin-bottom: 4px;
    }
    .vp-id-pulse {
        width: 6px; height: 6px; border-radius: 50%;
        background: #5EEAD4;
        box-shadow: 0 0 8px rgba(94,234,212,0.6);
    }
    .vp-id-name {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 22px; font-weight: 800;
        letter-spacing: -0.01em;
        line-height: 1.15;
        margin: 0;
        word-break: break-word;
    }
    .vp-id-code {
        font-family: 'JetBrains Mono','Fira Code',monospace;
        font-size: 12px; font-weight: 700;
        color: #94A3B8;
        letter-spacing: 0.05em;
        margin-top: 4px;
    }

    .vp-id-contact {
        position: relative;
        display: flex; flex-wrap: wrap; gap: 8px;
        margin-top: 14px; padding-top: 14px;
        border-top: 1px solid rgba(255,255,255,0.08);
    }
    .vp-id-contact-item {
        display: inline-flex; align-items: center; gap: 6px;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.08);
        padding: 6px 11px;
        border-radius: 9999px;
        font-size: 11px; color: #CBD5E1; font-weight: 600;
    }
    .vp-id-contact-item svg { width: 12px; height: 12px; color: #5EEAD4; }

    /* ====================== SECTION TITLE ====================== */
    .vp-section-title {
        display: flex; align-items: center; gap: 7px;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 13px; font-weight: 800;
        color: #0F172A;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin: 0 0 10px;
    }
    .vp-section-title svg { width: 14px; height: 14px; color: #44A08D; }

    /* ====================== SOLDES (Vente + Commission) ====================== */
    .vp-balances {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
        margin-bottom: 10px;
    }
    @media (min-width: 640px) {
        .vp-balances { grid-template-columns: 1fr 1fr; gap: 12px; }
    }
    .vp-balance {
        position: relative;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        border-radius: 18px;
        padding: 18px 20px;
        color: white;
        overflow: hidden;
        box-shadow: 0 16px 32px -12px rgba(68,160,141,0.45),
                    inset 0 1px 0 rgba(255,255,255,0.30);
    }
    .vp-balance--gold {
        background: linear-gradient(135deg, #D97706, #F59E0B 60%, #FBBF24);
        box-shadow: 0 16px 32px -12px rgba(217,119,6,0.45),
                    inset 0 1px 0 rgba(255,255,255,0.35);
    }
    .vp-balance-glow {
        position: absolute;
        top: -60px; right: -60px;
        width: 200px; height: 200px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255,255,255,0.20) 0%, transparent 70%);
        pointer-events: none;
    }
    .vp-balance-eyebrow {
        position: relative;
        display: inline-flex; align-items: center; gap: 7px;
        font-size: 10px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.10em;
        opacity: 0.90;
    }
    .vp-balance-eyebrow svg { width: 12px; height: 12px; }
    .vp-balance-num {
        position: relative;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 38px; font-weight: 800;
        line-height: 1;
        margin-top: 6px;
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.02em;
    }
    .vp-balance-unit {
        font-size: 13px; font-weight: 700;
        opacity: 0.75; margin-left: 6px;
    }
    .vp-balance-cap {
        position: relative;
        font-size: 11px;
        opacity: 0.90;
        margin-top: 4px;
        font-variant-numeric: tabular-nums;
    }
    .vp-balance-cap strong { font-weight: 800; }
    .vp-balance-bar {
        position: relative;
        height: 6px;
        background: rgba(255,255,255,0.20);
        border-radius: 9999px;
        overflow: hidden;
        margin-top: 12px;
    }
    .vp-balance-bar-fill {
        height: 100%;
        background: white;
        border-radius: 9999px;
        box-shadow: 0 0 12px rgba(255,255,255,0.50);
        transition: width .4s ease;
    }
    .vp-balance-pct {
        position: relative;
        font-size: 10px; font-weight: 700;
        opacity: 0.85;
        margin-top: 6px;
    }

    /* ====================== FINANCE GRID ====================== */
    .vp-finance-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
    .vp-fin-card {
        display: flex; align-items: flex-start; gap: 10px;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        padding: 12px;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }
    .vp-fin-card--brand {
        background: linear-gradient(135deg, #F0FDFA, #CCFBF1);
        border-color: #99F6E4;
    }
    .vp-fin-icon {
        width: 32px; height: 32px;
        border-radius: 10px;
        background: #F1F5F9;
        color: #0F766E;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .vp-fin-icon svg { width: 15px; height: 15px; }
    .vp-fin-icon--amber  { background: #FEF3C7; color: #B45309; }
    .vp-fin-icon--blue   { background: #DBEAFE; color: #1D4ED8; }
    .vp-fin-icon--purple { background: #EDE9FE; color: #6D28D9; }
    .vp-fin-card--brand .vp-fin-icon { background: white; color: #0F766E; box-shadow: 0 4px 10px -3px rgba(78,205,196,0.40); }

    .vp-fin-body { flex: 1; min-width: 0; }
    .vp-fin-label {
        font-size: 10px; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.06em;
        color: #64748B;
    }
    .vp-fin-value {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 17px; font-weight: 800;
        color: #0F172A;
        font-variant-numeric: tabular-nums;
        line-height: 1.1;
        margin-top: 3px;
    }
    .vp-fin-unit {
        font-size: 9px; font-weight: 700;
        color: #94A3B8;
        text-transform: uppercase; letter-spacing: 0.06em;
        margin-left: 3px;
    }
    .vp-fin-meta {
        font-size: 10px; color: #94A3B8;
        margin-top: 2px;
    }

    /* ====================== ACTIONS RAPIDES ====================== */
    .vp-actions {
        display: flex; flex-direction: column; gap: 8px;
    }
    .vp-action {
        display: flex; align-items: center; gap: 12px;
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        padding: 12px 14px;
        text-decoration: none;
        color: inherit;
        transition: all .15s ease;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }
    .vp-action:active { transform: scale(0.99); background: #F8FAFC; }
    .vp-action--primary {
        background: linear-gradient(135deg, #0F172A, #1E293B);
        border-color: #0F172A;
        color: white;
        box-shadow: 0 12px 24px -10px rgba(15,23,42,0.45);
    }
    .vp-action--primary .vp-action-sub { color: rgba(255,255,255,0.6); }
    .vp-action--primary .vp-action-arrow { color: #5EEAD4; }
    .vp-action-icon {
        width: 38px; height: 38px;
        border-radius: 11px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .vp-action-icon--alt {
        background: #F1F5F9;
        color: #44A08D;
    }
    .vp-action-icon svg { width: 17px; height: 17px; }
    .vp-action-body { flex: 1; min-width: 0; }
    .vp-action-title {
        font-family: 'Inter', sans-serif;
        font-size: 14px; font-weight: 800;
        line-height: 1.2;
    }
    .vp-action-sub {
        font-size: 11px;
        color: #64748B;
        margin-top: 2px;
    }
    .vp-action-arrow { width: 16px; height: 16px; color: #94A3B8; flex-shrink: 0; }

    /* ====================== TRANSACTIONS ====================== */
    .vp-tx-list {
        display: flex; flex-direction: column; gap: 6px;
    }
    .vp-tx {
        display: flex; align-items: center; gap: 10px;
        background: white;
        border: 1px solid #F1F5F9;
        border-radius: 12px;
        padding: 10px 12px;
    }
    .vp-tx-icon {
        width: 32px; height: 32px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800; font-size: 16px;
        flex-shrink: 0;
    }
    .vp-tx-body { flex: 1; min-width: 0; }
    .vp-tx-label {
        display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
        font-size: 12px; font-weight: 700;
        color: #0F172A;
    }
    .vp-tx-tag {
        display: inline-flex; align-items: center;
        font-size: 9px; font-weight: 800;
        text-transform: uppercase; letter-spacing: 0.06em;
        padding: 2px 6px; border-radius: 6px;
        background: #ECFDF5; color: #047857;
    }
    .vp-tx-tag--gold { background: #FEF3C7; color: #B45309; }
    .vp-tx-meta {
        font-size: 10px; color: #94A3B8;
        margin-top: 1px;
        font-family: 'JetBrains Mono','Fira Code',monospace;
    }
    .vp-tx-amount {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 13px; font-weight: 800;
        font-variant-numeric: tabular-nums;
        text-align: right;
        line-height: 1.1;
    }
    .vp-tx-unit {
        display: block;
        font-size: 9px; font-weight: 600;
        color: #94A3B8;
        margin-top: 1px;
    }

    .vp-empty {
        background: white;
        border: 1px dashed #CBD5E1;
        border-radius: 14px;
        padding: 24px;
        text-align: center;
        color: #94A3B8;
        font-size: 12px;
    }

    /* ====================== LOGOUT + FOOT ====================== */
    .vp-logout {
        width: 100%;
        display: flex; align-items: center; justify-content: center; gap: 8px;
        padding: 14px;
        background: #FEF2F2;
        border: 1px solid #FECACA;
        border-radius: 14px;
        color: #BE123C;
        font-family: 'Inter', sans-serif;
        font-size: 13px; font-weight: 800;
        cursor: pointer;
        transition: all .15s ease;
    }
    .vp-logout:hover { background: #FEE2E2; border-color: #FCA5A5; }
    .vp-logout:active { transform: scale(0.99); }
    .vp-logout svg { width: 14px; height: 14px; }

    .vp-foot {
        margin-top: 14px;
        text-align: center;
        font-size: 11px; color: #94A3B8;
    }
    .vp-foot a { color: #44A08D; font-weight: 700; text-decoration: none; }

    /* ====================== RESPONSIVE TWEAKS ====================== */
    @media (min-width: 480px) {
        .vp-balance-num { font-size: 44px; }
        .vp-finance-grid { gap: 10px; }
    }
    @media (min-width: 768px) {
        .vp-finance-grid { grid-template-columns: repeat(4, 1fr); }
        .vp-actions { flex-direction: row; }
        .vp-actions > .vp-action { flex: 1; }
    }
</style>
@endpush
@endsection
