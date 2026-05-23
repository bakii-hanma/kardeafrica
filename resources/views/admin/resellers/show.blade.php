@extends('admin.layouts.admin')

@section('title', $reseller->name)
@section('page-title', $reseller->name)

@section('content')
<div style="padding:24px;font-family:'Inter','Figtree',sans-serif;max-width:1200px;margin:0 auto;">

    <div style="margin-bottom:18px;">
        <a href="{{ route('admin.resellers.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#64748B;text-decoration:none;">
            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Retour aux vendeurs
        </a>
    </div>

    {{-- ========== HERO + WALLET ========== --}}
    <div style="background:linear-gradient(135deg,#0F172A,#1E293B);border-radius:18px;padding:24px;color:white;margin-bottom:18px;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-50px;right:-50px;width:220px;height:220px;border-radius:110px;background:radial-gradient(circle,rgba(78,205,196,0.20) 0%,transparent 70%);"></div>

        <div style="position:relative;display:flex;align-items:center;gap:18px;flex-wrap:wrap;margin-bottom:24px;">
            <div style="width:64px;height:64px;border-radius:18px;background:linear-gradient(135deg,#44A08D,#4ECDC4);display:flex;align-items:center;justify-content:center;font-family:'Space Grotesk','Inter',sans-serif;font-weight:800;font-size:28px;flex-shrink:0;box-shadow:0 14px 30px -8px rgba(78,205,196,0.5);">
                {{ strtoupper(substr($reseller->name, 0, 1)) }}
            </div>
            <div style="flex:1;min-width:240px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:9999px;font-size:10px;font-weight:700;background:{{ $reseller->is_active ? 'rgba(16,185,129,0.20)' : 'rgba(255,255,255,0.10)' }};color:{{ $reseller->is_active ? '#6EE7B7' : '#94A3B8' }};border:1px solid {{ $reseller->is_active ? 'rgba(16,185,129,0.40)' : 'rgba(255,255,255,0.15)' }};">
                        <span style="width:5px;height:5px;border-radius:50%;background:{{ $reseller->is_active ? '#10B981' : '#94A3B8' }};"></span>
                        {{ $reseller->is_active ? 'Actif' : 'Inactif' }}
                    </span>
                </div>
                <h1 style="font-family:'Space Grotesk','Inter',sans-serif;font-size:28px;font-weight:800;margin:0;letter-spacing:-0.01em;">{{ $reseller->name }}</h1>
                <div style="display:flex;align-items:center;gap:14px;font-size:13px;color:#94A3B8;margin-top:6px;flex-wrap:wrap;">
                    <span style="font-family:monospace;color:#5EEAD4;font-weight:700;">{{ $reseller->vendor_code }}</span>
                    @if($reseller->phone)<span>· {{ $reseller->phone }}</span>@endif
                    @if($reseller->email)<span>· <span style="font-family:monospace;">{{ $reseller->email }}</span></span>@endif
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <form method="POST" action="{{ route('admin.resellers.toggle', $reseller) }}" data-no-loader onsubmit="return confirm('{{ $reseller->is_active ? 'Désactiver ce vendeur ?' : 'Activer ce vendeur ?' }}');">
                    @csrf @method('PATCH')
                    <button type="submit" style="padding:10px 16px;border-radius:11px;border:1px solid rgba(255,255,255,0.15);background:rgba(255,255,255,0.06);color:white;font-size:12px;font-weight:700;cursor:pointer;">
                        {{ $reseller->is_active ? 'Désactiver' : 'Activer' }}
                    </button>
                </form>
            </div>
        </div>

        {{-- Wallet bar : Vente + Commission (séparés) --}}
        <div style="display:grid;grid-template-columns:1fr;gap:12px;">
            {{-- Solde de vente --}}
            <div style="position:relative;padding:18px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.10);border-radius:14px;">
                <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:8px;">
                    <div>
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.10em;color:#5EEAD4;">Solde de vente</div>
                        <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:32px;font-weight:800;margin-top:4px;">
                            {{ number_format($reseller->wallet_balance, 0, ',', ' ') }}
                            <span style="font-size:14px;font-weight:600;color:#94A3B8;">/ {{ number_format($reseller->max_wallet, 0, ',', ' ') }} FCFA</span>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.10em;color:#94A3B8;">Taux commission</div>
                        <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:800;color:#5EEAD4;">{{ rtrim(rtrim(number_format($reseller->commission_rate, 2), '0'), '.') }}%</div>
                    </div>
                </div>
                <div style="height:8px;background:rgba(255,255,255,0.08);border-radius:9999px;overflow:hidden;">
                    <div style="height:100%;background:linear-gradient(90deg,#44A08D,#4ECDC4);width:{{ min(100, $reseller->wallet_percentage) }}%;transition:width .3s;"></div>
                </div>
                <div style="font-size:11px;color:#64748B;margin-top:6px;">{{ $reseller->wallet_percentage }}% du plafond utilisé · sert à acheter le stock pour les clients</div>
            </div>

            {{-- Solde commissions (séparé) --}}
            <div style="position:relative;padding:18px;background:linear-gradient(135deg,rgba(217,119,6,0.18),rgba(245,158,11,0.10));border:1px solid rgba(245,158,11,0.30);border-radius:14px;">
                <div style="display:flex;align-items:baseline;justify-content:space-between;">
                    <div>
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.10em;color:#FBBF24;">Solde commissions</div>
                        <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:32px;font-weight:800;margin-top:4px;">
                            {{ number_format($reseller->commission_balance, 0, ',', ' ') }}
                            <span style="font-size:14px;font-weight:600;color:#94A3B8;">FCFA</span>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.10em;color:#94A3B8;">Cumul gagné</div>
                        <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:800;color:#FBBF24;">{{ number_format($reseller->total_commission_earned, 0, ',', ' ') }} FCFA</div>
                    </div>
                </div>
                <div style="font-size:11px;color:#94A3B8;margin-top:6px;">Crédité automatiquement à chaque vente — séparé du solde de vente.</div>
            </div>
        </div>
    </div>

    {{-- ========== STATS ========== --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:18px;">
        @foreach ([
            ['label' => 'Commandes',          'value' => $stats['orders_count'],     'color' => '#0F172A', 'unit' => ''],
            ['label' => 'Terminées',          'value' => $stats['orders_completed'], 'color' => '#047857', 'unit' => ''],
            ['label' => 'Volume cumulé',      'value' => number_format($stats['total_volume'], 0, ',', ' '), 'color' => '#3B82F6', 'unit' => 'FCFA'],
            ['label' => 'Commissions gagnées','value' => number_format($reseller->total_commission_earned, 0, ',', ' '), 'color' => '#B45309', 'unit' => 'FCFA'],
        ] as $s)
            <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:14px 16px;">
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">{{ $s['label'] }}</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:20px;font-weight:800;color:{{ $s['color'] }};font-variant-numeric:tabular-nums;line-height:1.05;margin-top:4px;">
                    {{ $s['value'] }}
                    @if($s['unit'])<span style="font-size:10px;font-weight:600;color:#94A3B8;">{{ $s['unit'] }}</span>@endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- ========== RECHARGE + RÉGLAGES (sliders/pills, design create-like) ========== --}}
    <div x-data="resellerSettings({
            balance:    @js((float) $reseller->wallet_balance),
            maxWallet:  @js((int) $reseller->max_wallet),
            commission: @js((float) $reseller->commission_rate),
         })"
         style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px;">

        {{-- Crédit du portefeuille --}}
        <form method="POST" action="{{ route('admin.resellers.credit', $reseller) }}" data-no-loader
              style="background:white;border-radius:18px;border:1px solid #E2E8F0;padding:22px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            @csrf
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
                <div style="width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#44A08D,#4ECDC4);display:flex;align-items:center;justify-content:center;color:white;flex-shrink:0;">
                    <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
                </div>
                <div style="flex:1;">
                    <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:16px;font-weight:700;color:#0F172A;">Recharger le portefeuille</div>
                    <div style="font-size:11px;color:#64748B;">Solde actuel : <strong x-text="fmt(balance) + ' FCFA'"></strong> — disponible : <strong x-text="fmt(rechargeMax) + ' FCFA'"></strong></div>
                </div>
            </div>

            <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:8px;">
                <label style="font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.06em;">Montant à créditer</label>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#44A08D;font-variant-numeric:tabular-nums;line-height:1;">
                    +<span x-text="fmt(rechargeAmount)"></span><span style="font-size:11px;color:#94A3B8;font-weight:600;margin-left:4px;">FCFA</span>
                </div>
            </div>
            <input type="range" min="0" :max="rechargeMax" step="1000" x-model.number="rechargeAmount"
                   style="width:100%;accent-color:#44A08D;cursor:pointer;">
            <input type="hidden" name="amount" :value="rechargeAmount">

            <div style="margin-top:14px;display:flex;flex-wrap:wrap;gap:8px;">
                <template x-for="preset in rechargePresets" :key="preset">
                    <button type="button" @click="rechargeAmount = preset"
                            :class="rechargeAmount === preset ? 'pill-active-brand' : 'pill-inactive'"
                            class="pill-base">
                        <span class="pill-value" x-text="(preset/1000) + 'k'"></span>
                        <span class="pill-suffix">FCFA</span>
                    </button>
                </template>
                <button type="button" @click="rechargeAmount = rechargeMax" :disabled="rechargeMax === 0"
                        :class="rechargeAmount === rechargeMax && rechargeMax > 0 ? 'pill-active-dark' : 'pill-inactive'"
                        class="pill-base">
                    <span class="pill-value">Max</span>
                </button>
            </div>

            <div style="margin-top:14px;">
                <label style="display:block;font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px;">Description <span style="color:#94A3B8;font-weight:500;text-transform:none;letter-spacing:0;">(optionnel)</span></label>
                <input type="text" name="description" maxlength="200" placeholder="Recharge mensuelle, virement reçu…"
                       style="width:100%;padding:11px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:13px;outline:none;transition:all .15s;"
                       onfocus="this.style.background='white';this.style.borderColor='#44A08D';this.style.boxShadow='0 0 0 3px rgba(68,160,141,0.12)';"
                       onblur="this.style.background='#F8FAFC';this.style.borderColor='#E2E8F0';this.style.boxShadow='none';">
            </div>

            {{-- Aperçu nouveau solde --}}
            <div x-show="rechargeAmount > 0" x-cloak class="recharge-preview">
                <div class="recharge-preview-row">
                    <div>
                        <div class="recharge-preview-label">Nouveau solde après crédit</div>
                        <div class="recharge-preview-value">
                            <span x-text="fmt(balance + rechargeAmount)"></span><span class="recharge-preview-unit">FCFA</span>
                        </div>
                    </div>
                    <div class="recharge-preview-meta">
                        <div class="recharge-preview-max">
                            sur <strong x-text="fmt(maxWallet)"></strong> FCFA
                        </div>
                        <div class="recharge-preview-pct">
                            <span x-text="Math.round(((balance + rechargeAmount) / maxWallet) * 100)"></span>% utilisé
                        </div>
                    </div>
                </div>
                <div class="recharge-preview-bar">
                    <div class="recharge-preview-bar-current"
                         :style="`width:${(balance / maxWallet) * 100}%;`"
                         :title="`Solde actuel : ${fmt(balance)} FCFA`"></div>
                    <div class="recharge-preview-bar-add"
                         :style="`width:${(rechargeAmount / maxWallet) * 100}%;`"
                         :title="`+ ${fmt(rechargeAmount)} FCFA`"></div>
                </div>
                <div class="recharge-preview-legend">
                    <span class="rl">
                        <span class="rl-dot rl-dot--current"></span>
                        Actuel <strong x-text="fmt(balance)"></strong>
                    </span>
                    <span class="rl">
                        <span class="rl-dot rl-dot--add"></span>
                        Ajout <strong x-text="'+ ' + fmt(rechargeAmount)"></strong>
                    </span>
                    <span x-show="balance + rechargeAmount === maxWallet" class="rl-cap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Plafond atteint
                    </span>
                </div>
            </div>

            {{-- Hint si rien sélectionné --}}
            <div x-show="rechargeAmount === 0" class="recharge-hint">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Choisis un montant à créditer pour activer le bouton.
            </div>

            <button type="submit" :disabled="rechargeAmount <= 0"
                    :class="rechargeAmount > 0 ? 'btn-submit' : 'btn-submit btn-submit--disabled'"
                    style="margin-top:14px;width:100%;justify-content:center;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span>Créditer le portefeuille</span>
            </button>
        </form>

        {{-- Réglages financiers (commission + plafond) --}}
        <form method="POST" action="{{ route('admin.resellers.settings', $reseller) }}" data-no-loader
              style="background:white;border-radius:18px;border:1px solid #E2E8F0;padding:22px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            @csrf @method('PATCH')
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
                <div style="width:32px;height:32px;border-radius:9px;background:#0F172A;display:flex;align-items:center;justify-content:center;color:#5EEAD4;flex-shrink:0;">
                    <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div style="flex:1;">
                    <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:16px;font-weight:700;color:#0F172A;">Réglages financiers</div>
                    <div style="font-size:11px;color:#64748B;">Modifie la commission et le plafond du wallet.</div>
                </div>
            </div>

            {{-- Commission --}}
            <div style="margin-bottom:18px;">
                <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:8px;">
                    <label style="font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.06em;">Commission par vente</label>
                    <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#44A08D;font-variant-numeric:tabular-nums;line-height:1;">
                        <span x-text="commission.toFixed(1)"></span><span style="font-size:14px;">%</span>
                    </div>
                </div>
                <input type="range" min="0" max="20" step="0.5" x-model.number="commission"
                       style="width:100%;accent-color:#44A08D;cursor:pointer;">
                <input type="hidden" name="commission_rate" :value="commission">
                <div style="margin-top:12px;display:flex;flex-wrap:wrap;gap:8px;">
                    <template x-for="preset in [{v:1,l:'Éco'},{v:3,l:'Standard'},{v:5,l:'Premium'},{v:10,l:'VIP'}]" :key="preset.v">
                        <button type="button" @click="commission = preset.v"
                                :class="commission === preset.v ? 'pill-active-brand' : 'pill-inactive'"
                                class="pill-base">
                            <span class="pill-value" x-text="preset.v + '%'"></span>
                            <span class="pill-tag" x-text="preset.l"></span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Plafond --}}
            <div style="margin-bottom:14px;">
                <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:8px;">
                    <label style="font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.06em;">Plafond du portefeuille</label>
                    <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:20px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;line-height:1;">
                        <span x-text="fmt(maxWallet)"></span><span style="font-size:11px;color:#94A3B8;font-weight:600;margin-left:4px;">FCFA</span>
                    </div>
                </div>
                <input type="range" :min="Math.max(1000, balance)" max="150000" step="1000" x-model.number="maxWallet"
                       style="width:100%;accent-color:#44A08D;cursor:pointer;">
                <input type="hidden" name="max_wallet" :value="maxWallet">
                <div style="margin-top:12px;display:flex;flex-wrap:wrap;gap:8px;">
                    <template x-for="preset in [25000, 50000, 100000, 150000]" :key="preset">
                        <button type="button" @click="maxWallet = preset" :disabled="preset < balance"
                                :class="maxWallet === preset ? 'pill-active-dark' : 'pill-inactive'"
                                class="pill-base">
                            <span class="pill-value" x-text="(preset/1000) + 'k'"></span>
                            <span class="pill-suffix">FCFA</span>
                        </button>
                    </template>
                </div>
                <div x-show="balance > 0" style="font-size:11px;color:#94A3B8;margin-top:8px;line-height:1.5;">
                    Le plafond ne peut pas être inférieur au solde actuel (<strong x-text="fmt(balance) + ' FCFA'"></strong>).
                </div>
            </div>

            {{-- Aperçu des changements (diff visuel old → new) --}}
            <div x-show="hasChanges" x-cloak class="settings-diff">
                <div class="settings-diff-head">
                    <span class="settings-diff-eyebrow">
                        <span class="settings-diff-pulse"></span>
                        Changements non enregistrés
                    </span>
                    <span class="settings-diff-count" x-text="diffCount + ' modif' + (diffCount > 1 ? 's' : '')"></span>
                </div>

                <div class="settings-diff-rows">
                    <div x-show="commission !== initial.commission" class="settings-diff-row">
                        <span class="settings-diff-label">Commission</span>
                        <div class="settings-diff-flow">
                            <span class="settings-diff-old" x-text="initial.commission.toFixed(1) + '%'"></span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            <span class="settings-diff-new" x-text="commission.toFixed(1) + '%'"></span>
                        </div>
                    </div>
                    <div x-show="maxWallet !== initial.maxWallet" class="settings-diff-row">
                        <span class="settings-diff-label">Plafond wallet</span>
                        <div class="settings-diff-flow">
                            <span class="settings-diff-old"><span x-text="fmt(initial.maxWallet)"></span> FCFA</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            <span class="settings-diff-new"><span x-text="fmt(maxWallet)"></span> FCFA</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Hint si aucun changement --}}
            <div x-show="!hasChanges" class="recharge-hint">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Modifie la commission ou le plafond pour activer la sauvegarde.
            </div>

            <button type="submit" :disabled="!hasChanges"
                    :class="hasChanges ? 'btn-submit' : 'btn-submit btn-submit--disabled'"
                    style="margin-top:14px;width:100%;justify-content:center;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <span>Enregistrer les réglages</span>
            </button>
        </form>
    </div>

    {{-- ========== RESET PASSWORD (full width, structuré en 2 colonnes) ========== --}}
    <form method="POST" action="{{ route('admin.resellers.reset-password', $reseller) }}" data-no-loader
          x-data="resetPwd()"
          @submit.prevent="if (confirm('Réinitialiser le mot de passe ? Le vendeur ne pourra plus se connecter avec l\'ancien.')) $el.submit()"
          class="pwd-card">
        @csrf

        <div class="pwd-grid">
            {{-- Colonne info --}}
            <div class="pwd-info">
                <div class="pwd-header">
                    <div class="pwd-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    </div>
                    <span class="pwd-eyebrow">Sécurité</span>
                </div>
                <h3 class="pwd-title">Réinitialiser le mot de passe</h3>
                <p class="pwd-lead">Génère un nouveau code à transmettre au vendeur. L'ancien sera immédiatement invalidé.</p>

                <ul class="pwd-checklist">
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Minimum 6 caractères
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Sans caractères ambigus (0/O, 1/l)
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Communiqué une seule fois au vendeur
                    </li>
                </ul>
            </div>

            {{-- Colonne action --}}
            <div class="pwd-action">
                <label class="pwd-label">Nouveau mot de passe</label>
                <div class="pwd-input-wrap">
                    <svg class="pwd-input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <input :type="showPwd ? 'text' : 'password'" name="password" required minlength="6" x-model="pwd"
                           placeholder="Saisis ou génère un nouveau…" class="pwd-input">
                    <button type="button" @click="showPwd = !showPwd" class="pwd-eye" :title="showPwd ? 'Masquer' : 'Afficher'">
                        <svg x-show="showPwd" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        <svg x-show="!showPwd" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>

                {{-- Force du mot de passe --}}
                <div class="pwd-strength">
                    <div class="pwd-strength-bar">
                        <div class="pwd-strength-fill" :style="`width:${strength.pct}%;background:${strength.color};`"></div>
                    </div>
                    <span class="pwd-strength-label" :style="`color:${strength.color};`" x-text="strength.label"></span>
                </div>

                {{-- Actions secondaires --}}
                <div class="pwd-tools">
                    <button type="button" @click="generate()" class="pwd-tool pwd-tool--gen">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Générer
                    </button>
                    <button type="button" @click="copyPwd()" :disabled="!pwd" class="pwd-tool" :class="copied ? 'pwd-tool--ok' : ''">
                        <svg x-show="!copied" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <svg x-show="copied" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span x-text="copied ? 'Copié' : 'Copier'"></span>
                    </button>
                    <button type="submit" :disabled="!pwd || pwd.length < 6" class="pwd-submit" :class="(!pwd || pwd.length < 6) ? 'pwd-submit--disabled' : ''">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Réinitialiser
                    </button>
                </div>
            </div>
        </div>
    </form>

    <script>
        function resetPwd() {
            return {
                pwd: '',
                showPwd: true,
                copied: false,

                get strength() {
                    const p = this.pwd || '';
                    let s = 0;
                    if (p.length >= 6) s++;
                    if (p.length >= 10) s++;
                    if (/[A-Z]/.test(p) && /[a-z]/.test(p)) s++;
                    if (/[0-9]/.test(p)) s++;
                    if (/[^A-Za-z0-9]/.test(p)) s++;
                    const m = [
                        { pct: 0,   color: '#CBD5E1', label: '—' },
                        { pct: 20,  color: '#F43F5E', label: 'Très faible' },
                        { pct: 40,  color: '#F59E0B', label: 'Faible' },
                        { pct: 60,  color: '#EAB308', label: 'Correct' },
                        { pct: 80,  color: '#10B981', label: 'Bon' },
                        { pct: 100, color: '#0F766E', label: 'Excellent' },
                    ];
                    return p.length === 0 ? m[0] : m[Math.min(s, 5) || 1];
                },

                generate() {
                    const charset = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
                    let out = 'KA-';
                    for (let i = 0; i < 6; i++) out += charset[Math.floor(Math.random() * charset.length)];
                    this.pwd = out;
                    this.showPwd = true;
                },

                async copyPwd() {
                    if (!this.pwd) return;
                    await navigator.clipboard.writeText(this.pwd);
                    this.copied = true;
                    setTimeout(() => { this.copied = false; }, 1600);
                },
            };
        }
    </script>

    {{-- ========== HISTORIQUE WALLET ========== --}}
    <div style="background:white;border-radius:16px;border:1px solid #E2E8F0;padding:22px;margin-bottom:18px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <div>
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.10em;color:#44A08D;">Historique</div>
                <h2 style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:700;color:#0F172A;margin:4px 0 0;">Mouvements du portefeuille</h2>
            </div>
        </div>

        @if($reseller->walletTransactions->count() > 0)
            <div style="display:flex;flex-direction:column;gap:6px;">
                @foreach($reseller->walletTransactions as $tx)
                    @php
                        $colors = [
                            'credit'     => ['bg' => '#D1FAE5', 'text' => '#047857', 'sign' => '+', 'label' => 'Crédit'],
                            'commission' => ['bg' => '#FEF3C7', 'text' => '#B45309', 'sign' => '+', 'label' => 'Commission'],
                            'debit'      => ['bg' => '#FFE4E6', 'text' => '#BE123C', 'sign' => '−', 'label' => 'Débit'],
                            'adjustment' => ['bg' => '#E0F2FE', 'text' => '#0369A1', 'sign' => '±', 'label' => 'Ajustement'],
                        ];
                        $c = $colors[$tx->type] ?? ['bg' => '#F1F5F9', 'text' => '#475569', 'sign' => '·', 'label' => $tx->type];
                    @endphp
                    @php
                        $walletKey = $tx->wallet ?? 'sales';
                        $walletTagBg = $walletKey === 'commission' ? '#FEF3C7' : '#ECFDF5';
                        $walletTagFg = $walletKey === 'commission' ? '#B45309' : '#047857';
                        $walletTagLabel = $walletKey === 'commission' ? 'Commissions' : 'Vente';
                    @endphp
                    <div style="display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:10px;background:#F8FAFC;border:1px solid #F1F5F9;">
                        <div style="width:36px;height:36px;border-radius:9px;background:{{ $c['bg'] }};color:{{ $c['text'] }};display:flex;align-items:center;justify-content:center;font-family:'Space Grotesk','Inter',sans-serif;font-weight:800;font-size:18px;flex-shrink:0;">
                            {{ $c['sign'] }}
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;font-size:13px;font-weight:700;color:#0F172A;">
                                {{ $c['label'] }}
                                <span style="display:inline-flex;align-items:center;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:0.06em;padding:2px 6px;border-radius:6px;background:{{ $walletTagBg }};color:{{ $walletTagFg }};">{{ $walletTagLabel }}</span>
                            </div>
                            <div style="font-size:11px;color:#64748B;margin-top:1px;">
                                {{ $tx->description }}
                                @if($tx->reference) · <span style="font-family:monospace;color:#94A3B8;">{{ $tx->reference }}</span>@endif
                                @if($tx->admin) · par {{ $tx->admin->name }}@endif
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:14px;font-weight:800;color:{{ $c['text'] }};font-variant-numeric:tabular-nums;">
                                {{ $c['sign'] }} {{ number_format($tx->amount, 0, ',', ' ') }}
                                <span style="font-size:10px;font-weight:500;color:#94A3B8;">FCFA</span>
                            </div>
                            <div style="font-size:10px;color:#94A3B8;margin-top:1px;">{{ $tx->created_at->format('d/m H:i') }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="text-align:center;padding:32px;color:#94A3B8;font-size:13px;">Aucun mouvement encore.</div>
        @endif
    </div>

    {{-- =========== Alpine factory + styles partagés (pills & boutons) =========== --}}
    <script>
    function resellerSettings(init) {
        return {
            balance:    init.balance,
            maxWallet:  init.maxWallet,
            commission: init.commission,
            initial: { maxWallet: init.maxWallet, commission: init.commission },
            rechargeAmount: 0,

            get rechargeMax()      { return Math.max(0, this.maxWallet - this.balance); },
            get rechargePresets()  {
                const max = this.rechargeMax;
                return [5000, 10000, 25000, 50000, 100000].filter(v => v <= max);
            },
            get hasChanges() {
                return this.maxWallet  !== this.initial.maxWallet
                    || this.commission !== this.initial.commission;
            },
            get diffCount() {
                let n = 0;
                if (this.maxWallet  !== this.initial.maxWallet)  n++;
                if (this.commission !== this.initial.commission) n++;
                return n;
            },
            fmt(n) { return new Intl.NumberFormat('fr-FR').format(Math.round(n || 0)); },

            init() {
                this.$watch('maxWallet', () => {
                    if (this.rechargeAmount > this.rechargeMax) this.rechargeAmount = this.rechargeMax;
                });
            },
        };
    }
    </script>

    <style>
        /* ===== Pills sélecteur (recharge / commission / plafond) ===== */
        .pill-base {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: 9999px;
            border: 1.5px solid transparent;
            cursor: pointer;
            font-family: 'Space Grotesk','Inter',sans-serif;
            font-size: 13px;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            line-height: 1;
            transition: all .2s ease;
            appearance: none;
            -webkit-appearance: none;
            margin: 0;
        }
        .pill-base:disabled { opacity: 0.4; cursor: not-allowed; }
        .pill-base:focus    { outline: 2px solid rgba(78,205,196,0.40); outline-offset: 2px; }

        .pill-inactive {
            background: #F1F5F9;
            color: #475569;
            border-color: #E2E8F0;
        }
        .pill-inactive:hover:not(:disabled) {
            background: white;
            border-color: #94A3B8;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px -4px rgba(15,23,42,0.10);
        }
        .pill-inactive .pill-suffix { color: #94A3B8; }
        .pill-inactive .pill-tag    { background: white; color: #64748B; }

        .pill-active-dark {
            background: linear-gradient(135deg, #0F172A, #1E293B);
            color: white;
            border-color: #0F172A;
            box-shadow: 0 10px 22px -8px rgba(15,23,42,0.50),
                        inset 0 1px 0 rgba(255,255,255,0.10);
        }
        .pill-active-dark .pill-suffix { color: #5EEAD4; }

        .pill-active-brand {
            background: linear-gradient(135deg, #44A08D, #4ECDC4);
            color: white;
            border-color: transparent;
            box-shadow: 0 12px 26px -8px rgba(68,160,141,0.55),
                        inset 0 1px 0 rgba(255,255,255,0.30);
        }
        .pill-active-brand .pill-tag {
            background: rgba(255,255,255,0.22);
            color: white;
        }

        .pill-value  { font-family:'Space Grotesk','Inter',sans-serif; font-size:13px; font-weight:800; line-height:1; }
        .pill-suffix { font-family:'Inter',sans-serif; font-size:9px; font-weight:800; letter-spacing:0.10em; line-height:1; }
        .pill-tag    { font-family:'Inter',sans-serif; font-size:9px; font-weight:800; letter-spacing:0.08em; text-transform:uppercase; padding:4px 8px; border-radius:9999px; line-height:1; }

        /* Submit buttons */
        .btn-submit {
            display: inline-flex !important;
            align-items: center; gap: 10px;
            padding: 12px 22px;
            background: linear-gradient(135deg, #44A08D, #4ECDC4);
            color: white;
            border: 0; border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 13px; font-weight: 800;
            line-height: 1;
            cursor: pointer; white-space: nowrap;
            box-shadow: 0 14px 30px -10px rgba(68,160,141,0.55), inset 0 1px 0 rgba(255,255,255,0.30);
            transition: all .18s ease;
            appearance: none; -webkit-appearance: none;
        }
        .btn-submit svg { width: 14px; height: 14px; flex-shrink: 0; }
        .btn-submit:hover:not(.btn-submit--disabled) {
            transform: translateY(-2px);
            box-shadow: 0 18px 36px -10px rgba(68,160,141,0.65), inset 0 1px 0 rgba(255,255,255,0.30);
        }
        .btn-submit--disabled {
            background: #E2E8F0 !important; color: #94A3B8 !important;
            cursor: not-allowed; box-shadow: none !important; transform: none !important;
        }

        input[type="range"] {
            -webkit-appearance: none; appearance: none;
            height: 6px; background: #E2E8F0; border-radius: 9999px; outline: none;
        }
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none; appearance: none;
            width: 18px; height: 18px; border-radius: 50%;
            background: linear-gradient(135deg, #44A08D, #4ECDC4);
            border: 2px solid white; box-shadow: 0 2px 6px rgba(15,23,42,0.18); cursor: pointer;
        }
        input[type="range"]::-moz-range-thumb {
            width: 18px; height: 18px; border-radius: 50%;
            background: linear-gradient(135deg, #44A08D, #4ECDC4);
            border: 2px solid white; box-shadow: 0 2px 6px rgba(15,23,42,0.18); cursor: pointer;
        }

        @media (max-width: 900px) {
            div[style*="grid-template-columns:1fr 1fr"][x-data*="resellerSettings"] {
                grid-template-columns: 1fr !important;
            }
        }

        /* ===== Aperçu nouveau solde après recharge ===== */
        [x-cloak] { display: none !important; }

        .recharge-preview {
            margin-top: 14px;
            padding: 14px 16px;
            background: linear-gradient(135deg, #0F172A, #1E293B);
            border-radius: 14px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 12px 28px -14px rgba(15,23,42,0.50),
                        inset 0 1px 0 rgba(255,255,255,0.06);
        }
        .recharge-preview::before {
            content: '';
            position: absolute; top: -50px; right: -50px;
            width: 160px; height: 160px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(78,205,196,0.18) 0%, transparent 70%);
            pointer-events: none;
        }
        .recharge-preview-row {
            position: relative;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .recharge-preview-label {
            font-family: 'Inter', sans-serif;
            font-size: 10px; font-weight: 800;
            text-transform: uppercase; letter-spacing: 0.10em;
            color: #5EEAD4;
            margin-bottom: 4px;
        }
        .recharge-preview-value {
            font-family: 'Space Grotesk','Inter',sans-serif;
            font-size: 26px; font-weight: 800;
            color: white;
            font-variant-numeric: tabular-nums;
            line-height: 1;
        }
        .recharge-preview-unit {
            font-size: 12px; font-weight: 600;
            color: #94A3B8;
            margin-left: 6px;
        }
        .recharge-preview-meta {
            text-align: right;
            line-height: 1.4;
        }
        .recharge-preview-max {
            font-size: 11px; color: #94A3B8;
            font-variant-numeric: tabular-nums;
        }
        .recharge-preview-max strong { color: white; font-weight: 700; }
        .recharge-preview-pct {
            font-family: 'Space Grotesk','Inter',sans-serif;
            font-size: 13px; font-weight: 800;
            color: #5EEAD4;
            font-variant-numeric: tabular-nums;
            margin-top: 2px;
        }

        .recharge-preview-bar {
            position: relative;
            display: flex;
            height: 8px;
            background: rgba(255,255,255,0.08);
            border-radius: 9999px;
            overflow: hidden;
        }
        .recharge-preview-bar-current {
            height: 100%;
            background: linear-gradient(90deg, #94A3B8, #CBD5E1);
            transition: width .3s ease;
        }
        .recharge-preview-bar-add {
            height: 100%;
            background: linear-gradient(90deg, #44A08D, #4ECDC4);
            box-shadow: 0 0 10px rgba(78,205,196,0.50);
            transition: width .3s ease;
            position: relative;
        }
        .recharge-preview-bar-add::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(90deg,
                transparent 0%,
                rgba(255,255,255,0.20) 50%,
                transparent 100%);
            animation: recharge-shine 2s linear infinite;
        }
        @keyframes recharge-shine {
            0%   { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .recharge-preview-legend {
            position: relative;
            display: flex; align-items: center;
            gap: 14px; flex-wrap: wrap;
            margin-top: 10px;
            font-size: 11px;
            color: #CBD5E1;
        }
        .rl { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; }
        .rl strong {
            font-family: 'Space Grotesk','Inter',sans-serif;
            font-weight: 800; color: white;
            font-variant-numeric: tabular-nums;
        }
        .rl-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .rl-dot--current { background: linear-gradient(135deg, #94A3B8, #CBD5E1); }
        .rl-dot--add     { background: linear-gradient(135deg, #44A08D, #4ECDC4); box-shadow: 0 0 8px rgba(78,205,196,0.50); }

        .rl-cap {
            margin-left: auto;
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(78,205,196,0.15);
            color: #5EEAD4;
            padding: 4px 9px;
            border-radius: 9999px;
            font-size: 10px; font-weight: 800;
            text-transform: uppercase; letter-spacing: 0.08em;
        }
        .rl-cap svg { width: 11px; height: 11px; }

        .recharge-hint {
            margin-top: 12px;
            padding: 10px 14px;
            background: #F8FAFC;
            border: 1px dashed #CBD5E1;
            border-radius: 11px;
            font-size: 11px;
            color: #64748B;
            font-weight: 500;
            display: flex; align-items: center; gap: 8px;
        }
        .recharge-hint svg { width: 14px; height: 14px; color: #94A3B8; flex-shrink: 0; }

        /* ===== Aperçu diff réglages financiers ===== */
        .settings-diff {
            margin-top: 12px;
            padding: 14px 16px;
            background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
            border: 1px solid #FDE68A;
            border-radius: 13px;
            position: relative;
            overflow: hidden;
        }
        .settings-diff::before {
            content: '';
            position: absolute; top: -40px; right: -40px;
            width: 130px; height: 130px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(245,158,11,0.20) 0%, transparent 70%);
            pointer-events: none;
        }
        .settings-diff-head {
            position: relative;
            display: flex; align-items: center; justify-content: space-between;
            gap: 10px; margin-bottom: 10px;
        }
        .settings-diff-eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 11px; font-weight: 800;
            text-transform: uppercase; letter-spacing: 0.08em;
            color: #92400E;
        }
        .settings-diff-pulse {
            position: relative;
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #F59E0B;
            box-shadow: 0 0 0 0 rgba(245,158,11,0.5);
            animation: settings-diff-ping 1.6s ease-out infinite;
        }
        @keyframes settings-diff-ping {
            0%   { box-shadow: 0 0 0 0 rgba(245,158,11,0.55); }
            70%  { box-shadow: 0 0 0 10px rgba(245,158,11,0); }
            100% { box-shadow: 0 0 0 0 rgba(245,158,11,0); }
        }
        .settings-diff-count {
            font-family: 'Space Grotesk','Inter',sans-serif;
            font-size: 10px; font-weight: 800;
            background: rgba(146,64,14,0.10);
            color: #92400E;
            padding: 4px 9px;
            border-radius: 9999px;
            line-height: 1;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .settings-diff-rows {
            position: relative;
            display: flex; flex-direction: column; gap: 6px;
        }
        .settings-diff-row {
            display: flex; align-items: center; justify-content: space-between;
            gap: 10px;
            background: rgba(255,255,255,0.55);
            border: 1px solid rgba(253,230,138,0.50);
            border-radius: 10px;
            padding: 8px 12px;
        }
        .settings-diff-label {
            font-family: 'Inter', sans-serif;
            font-size: 11px; font-weight: 700;
            color: #78350F;
            text-transform: uppercase; letter-spacing: 0.06em;
        }
        .settings-diff-flow {
            display: inline-flex; align-items: center; gap: 8px;
        }
        .settings-diff-flow svg {
            width: 12px; height: 12px;
            color: #B45309;
            flex-shrink: 0;
            animation: settings-diff-arrow 1.4s ease-in-out infinite;
        }
        @keyframes settings-diff-arrow {
            0%, 100% { transform: translateX(0); opacity: 0.7; }
            50%      { transform: translateX(2px); opacity: 1; }
        }
        .settings-diff-old {
            font-family: 'Space Grotesk','Inter',sans-serif;
            font-size: 12px; font-weight: 700;
            color: #94A3B8;
            text-decoration: line-through;
            text-decoration-color: rgba(148,163,184,0.6);
            font-variant-numeric: tabular-nums;
        }
        .settings-diff-new {
            font-family: 'Space Grotesk','Inter',sans-serif;
            font-size: 13px; font-weight: 800;
            color: #0F172A;
            background: white;
            padding: 3px 9px;
            border-radius: 7px;
            border: 1px solid #FDE68A;
            font-variant-numeric: tabular-nums;
            box-shadow: 0 2px 6px -2px rgba(245,158,11,0.30);
        }

        /* ===== Carte "Réinitialiser le mot de passe" ===== */
        .pwd-card {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 18px;
            padding: 0;
            margin-bottom: 18px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(15,23,42,0.04);
        }
        .pwd-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100%;
        }
        @media (max-width: 720px) {
            .pwd-grid { grid-template-columns: 1fr; }
        }

        /* Colonne info (gauche) */
        .pwd-info {
            background: linear-gradient(160deg, #FEF2F2 0%, #FFF1F2 100%);
            border-right: 1px solid #FEE2E2;
            padding: 22px;
        }
        @media (max-width: 720px) {
            .pwd-info { border-right: 0; border-bottom: 1px solid #FEE2E2; }
        }
        .pwd-header {
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 12px;
        }
        .pwd-icon {
            width: 36px; height: 36px;
            border-radius: 11px;
            background: white;
            color: #BE123C;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 6px 14px -4px rgba(244,63,94,0.30);
        }
        .pwd-icon svg { width: 18px; height: 18px; }
        .pwd-eyebrow {
            font-family: 'Inter', sans-serif;
            font-size: 10px; font-weight: 800;
            text-transform: uppercase; letter-spacing: 0.12em;
            color: #BE123C;
        }
        .pwd-title {
            font-family: 'Space Grotesk','Inter',sans-serif;
            font-size: 17px; font-weight: 700;
            color: #0F172A;
            letter-spacing: -0.01em;
            margin: 0 0 6px;
            line-height: 1.25;
        }
        .pwd-lead {
            font-size: 12px; color: #64748B;
            line-height: 1.55;
            margin: 0 0 14px;
        }
        .pwd-checklist {
            list-style: none; padding: 0; margin: 0;
            display: flex; flex-direction: column; gap: 6px;
        }
        .pwd-checklist li {
            display: flex; align-items: center; gap: 8px;
            font-size: 11px; font-weight: 600; color: #475569;
        }
        .pwd-checklist svg {
            width: 12px; height: 12px;
            color: #047857; flex-shrink: 0;
        }

        /* Colonne action (droite) */
        .pwd-action { padding: 22px; }

        .pwd-label {
            display: block;
            font-family: 'Inter', sans-serif;
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.06em;
            color: #475569;
            margin-bottom: 8px;
        }
        .pwd-input-wrap {
            position: relative;
            display: block;
        }
        .pwd-input-icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            width: 16px; height: 16px;
            color: #94A3B8;
            pointer-events: none;
        }
        .pwd-input {
            width: 100%;
            padding: 13px 50px 13px 40px;
            background: #F8FAFC;
            border: 1.5px solid #E2E8F0;
            border-radius: 11px;
            font-size: 14px;
            font-family: 'JetBrains Mono','Fira Code',monospace;
            font-weight: 700;
            color: #0F172A;
            outline: none;
            transition: all .15s ease;
            letter-spacing: 0.04em;
        }
        .pwd-input:focus {
            background: white;
            border-color: #44A08D;
            box-shadow: 0 0 0 3px rgba(68,160,141,0.15);
        }
        .pwd-input::placeholder { font-family: 'Inter', sans-serif; font-weight: 500; color: #94A3B8; letter-spacing: 0; }
        .pwd-eye {
            position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
            background: transparent; border: 0;
            color: #94A3B8;
            padding: 7px; border-radius: 8px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: all .15s ease;
        }
        .pwd-eye svg { width: 16px; height: 16px; }
        .pwd-eye:hover { background: #F1F5F9; color: #475569; }

        .pwd-strength {
            display: flex; align-items: center; gap: 10px;
            margin-top: 10px; margin-bottom: 16px;
        }
        .pwd-strength-bar {
            flex: 1; height: 5px;
            background: #F1F5F9; border-radius: 9999px;
            overflow: hidden;
        }
        .pwd-strength-fill {
            height: 100%; transition: width .25s ease, background .25s ease;
            border-radius: 9999px;
        }
        .pwd-strength-label {
            font-family: 'Inter', sans-serif;
            font-size: 10px; font-weight: 800;
            letter-spacing: 0.06em; text-transform: uppercase;
            min-width: 80px; text-align: right;
            transition: color .25s ease;
        }

        .pwd-tools {
            display: flex; gap: 8px; flex-wrap: wrap;
        }
        .pwd-tool {
            display: inline-flex !important;
            align-items: center; gap: 6px;
            padding: 9px 14px;
            background: white;
            border: 1.5px solid #E2E8F0;
            border-radius: 10px;
            color: #475569;
            font-family: 'Inter', sans-serif;
            font-size: 12px; font-weight: 700;
            line-height: 1;
            cursor: pointer;
            transition: all .15s ease;
            appearance: none; -webkit-appearance: none;
        }
        .pwd-tool svg { width: 12px; height: 12px; }
        .pwd-tool:hover:not(:disabled) {
            border-color: #94A3B8;
            background: #F8FAFC;
        }
        .pwd-tool:disabled { opacity: 0.5; cursor: not-allowed; }
        .pwd-tool--gen {
            background: linear-gradient(135deg, #0F172A, #1E293B);
            color: #5EEAD4;
            border-color: #0F172A;
        }
        .pwd-tool--gen:hover:not(:disabled) {
            background: linear-gradient(135deg, #1E293B, #334155);
            transform: translateY(-1px);
            box-shadow: 0 6px 14px -4px rgba(15,23,42,0.30);
        }
        .pwd-tool--ok {
            background: #D1FAE5 !important;
            color: #047857 !important;
            border-color: #6EE7B7 !important;
        }

        .pwd-submit {
            margin-left: auto;
            display: inline-flex !important;
            align-items: center; gap: 7px;
            padding: 9px 18px;
            background: linear-gradient(135deg, #BE123C, #E11D48);
            color: white;
            border: 0; border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 12px; font-weight: 800;
            line-height: 1;
            cursor: pointer;
            box-shadow: 0 10px 22px -8px rgba(225,29,72,0.45),
                        inset 0 1px 0 rgba(255,255,255,0.20);
            transition: all .15s ease;
            appearance: none; -webkit-appearance: none;
            white-space: nowrap;
        }
        .pwd-submit svg { width: 13px; height: 13px; }
        .pwd-submit:hover:not(.pwd-submit--disabled) {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px -8px rgba(225,29,72,0.55),
                        inset 0 1px 0 rgba(255,255,255,0.20);
        }
        .pwd-submit--disabled {
            background: #E2E8F0 !important;
            color: #94A3B8 !important;
            cursor: not-allowed;
            box-shadow: none !important;
            transform: none !important;
        }
    </style>

    {{-- ========== COMMANDES RÉCENTES ========== --}}
    @if($recentOrders->count() > 0)
        <div style="background:white;border-radius:16px;border:1px solid #E2E8F0;padding:22px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <div>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.10em;color:#3B82F6;">Activité</div>
                    <h2 style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:700;color:#0F172A;margin:4px 0 0;">Commandes récentes</h2>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:6px;">
                @php
                    $rsoStatusMap = [
                        'pending'    => ['#B45309','#FEF3C7','En attente'],
                        'processing' => ['#1D4ED8','#DBEAFE','En cours'],
                        'completed'  => ['#047857','#D1FAE5','Livrée'],
                        'cancelled'  => ['#475569','#E2E8F0','Annulée'],
                        'failed'     => ['#BE123C','#FEE2E2','Échec'],
                        'refunded'   => ['#7C3AED','#EDE9FE','Remboursée'],
                    ];
                @endphp
                @foreach($recentOrders as $o)
                    @php
                        $st = $rsoStatusMap[$o->status] ?? ['#475569','#E2E8F0',ucfirst($o->status)];
                        $needsAction = ($o->payment_status === 'completed' && $o->status !== 'completed' && $o->status !== 'refunded');
                    @endphp
                    <a href="{{ route('admin.resellers.orders.show', [$reseller, $o]) }}"
                       style="display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:10px;background:{{ $needsAction ? '#FFFBEB' : '#F8FAFC' }};border:1px solid {{ $needsAction ? '#FCD34D' : '#F1F5F9' }};text-decoration:none;color:inherit;transition:all .15s;"
                       onmouseover="this.style.borderColor='#44A08D';" onmouseout="this.style.borderColor='{{ $needsAction ? '#FCD34D' : '#F1F5F9' }}';">
                        <div style="font-family:monospace;font-size:12px;font-weight:700;color:#0F172A;">#{{ $o->order_number }}</div>
                        <div style="flex:1;font-size:12px;color:#475569;min-width:0;">
                            <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:inline-block;max-width:100%;">{{ $o->customer_name ?: 'Client anonyme' }}</span>
                            · {{ $o->created_at->diffForHumans() }}
                        </div>
                        <span style="display:inline-flex;align-items:center;padding:3px 8px;border-radius:6px;background:{{ $st[1] }};color:{{ $st[0] }};font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;flex-shrink:0;">{{ $st[2] }}</span>
                        <span style="font-family:'Space Grotesk','Inter',sans-serif;font-size:14px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;flex-shrink:0;">
                            {{ number_format($o->total_amount, 0, ',', ' ') }} <span style="font-size:10px;color:#94A3B8;">FCFA</span>
                        </span>
                        <svg style="width:14px;height:14px;color:#94A3B8;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
