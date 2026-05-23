@extends('admin.layouts.admin')

@section('title', 'Nouveau vendeur')
@section('page-title', 'Créer un vendeur')

@section('content')
<div style="padding:24px;font-family:'Inter','Figtree',sans-serif;max-width:1200px;margin:0 auto;"
     x-data="resellerForm({
        suggestedCode: @js($suggestedCode),
        suggestedPass: @js($suggestedPass),
        oldName:        @js(old('name', '')),
        oldPhone:       @js(old('phone', '')),
        oldEmail:       @js(old('email', '')),
        oldCode:        @js(old('vendor_code', $suggestedCode)),
        oldPass:        @js(old('password', $suggestedPass)),
        oldMaxWallet:   @js((int) old('max_wallet', 150000)),
        oldInitial:     @js((int) old('initial_credit', 0)),
        oldRate:        @js((float) old('commission_rate', 3)),
        oldActive:      @js(old('is_active', true) ? true : false),
     })">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
        <div>
            <a href="{{ route('admin.resellers.index') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#64748B;text-decoration:none;font-weight:600;margin-bottom:6px;">
                <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Tous les vendeurs
            </a>
            <h1 style="font-family:'Space Grotesk','Inter',sans-serif;font-size:24px;font-weight:800;color:#0F172A;margin:0;letter-spacing:-0.01em;">Nouveau vendeur</h1>
            <p style="font-size:13px;color:#64748B;margin:4px 0 0;">Crée un compte de revendeur et son identifiant de connexion.</p>
        </div>
    </div>

    @if($errors->any())
        <div style="background:#FEF2F2;border:1px solid #FECACA;color:#BE123C;padding:12px 14px;border-radius:11px;margin-bottom:14px;font-size:13px;">
            <strong>⚠ Corrige les erreurs ci-dessous :</strong>
            <ul style="margin:6px 0 0 18px;padding:0;">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.resellers.store') }}" data-no-loader
          style="display:grid;grid-template-columns:1fr 380px;gap:20px;align-items:start;">
        @csrf

        {{-- =========================================================== --}}
        {{-- COLONNE FORMULAIRE                                          --}}
        {{-- =========================================================== --}}
        <div style="display:flex;flex-direction:column;gap:16px;">

            {{-- ÉTAPE 1 : Identité --}}
            <div style="background:white;border-radius:16px;border:1px solid #E2E8F0;padding:22px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
                    <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;display:flex;align-items:center;justify-content:center;font-family:'Space Grotesk','Inter',sans-serif;font-weight:800;font-size:13px;">1</div>
                    <div>
                        <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:15px;font-weight:700;color:#0F172A;">Identité du vendeur</div>
                        <div style="font-size:11px;color:#64748B;">Qui est cette personne ou cette boutique ?</div>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:12px;">
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:5px;text-transform:uppercase;letter-spacing:0.06em;">
                            Nom complet <span style="color:#F43F5E;">*</span>
                        </label>
                        <input type="text" name="name" required maxlength="120" x-model="form.name"
                               placeholder="Ex : Boutique Mali — Bamako"
                               style="width:100%;padding:12px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;outline:none;transition:all .15s;font-weight:500;"
                               onfocus="this.style.background='white';this.style.borderColor='#44A08D';this.style.boxShadow='0 0 0 3px rgba(68,160,141,0.12)';"
                               onblur="this.style.background='#F8FAFC';this.style.borderColor='#E2E8F0';this.style.boxShadow='none';">
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <div>
                            <label style="display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:5px;text-transform:uppercase;letter-spacing:0.06em;">Téléphone</label>
                            <input type="text" name="phone" maxlength="20" x-model="form.phone" placeholder="+241..."
                                   style="width:100%;padding:12px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;outline:none;transition:all .15s;"
                                   onfocus="this.style.background='white';this.style.borderColor='#44A08D';this.style.boxShadow='0 0 0 3px rgba(68,160,141,0.12)';"
                                   onblur="this.style.background='#F8FAFC';this.style.borderColor='#E2E8F0';this.style.boxShadow='none';">
                        </div>
                        <div>
                            <label style="display:block;font-size:11px;font-weight:700;color:#475569;margin-bottom:5px;text-transform:uppercase;letter-spacing:0.06em;">
                                Email <span style="color:#94A3B8;font-weight:500;text-transform:none;letter-spacing:0;">(optionnel)</span>
                            </label>
                            <input type="email" name="email" maxlength="120" x-model="form.email" placeholder="vendeur@..."
                                   style="width:100%;padding:12px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;outline:none;transition:all .15s;"
                                   onfocus="this.style.background='white';this.style.borderColor='#44A08D';this.style.boxShadow='0 0 0 3px rgba(68,160,141,0.12)';"
                                   onblur="this.style.background='#F8FAFC';this.style.borderColor='#E2E8F0';this.style.boxShadow='none';">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ÉTAPE 2 : Identifiants --}}
            <div style="background:linear-gradient(135deg,#0F172A,#1E293B);border-radius:16px;padding:22px;color:white;position:relative;overflow:hidden;">
                <div style="position:absolute;top:-40px;right:-40px;width:180px;height:180px;border-radius:50%;background:radial-gradient(circle,rgba(78,205,196,0.20) 0%,transparent 70%);"></div>

                <div style="position:relative;display:flex;align-items:center;gap:10px;margin-bottom:16px;">
                    <div style="width:28px;height:28px;border-radius:8px;background:rgba(78,205,196,0.20);border:1px solid rgba(78,205,196,0.35);color:#5EEAD4;display:flex;align-items:center;justify-content:center;font-family:'Space Grotesk','Inter',sans-serif;font-weight:800;font-size:13px;">2</div>
                    <div>
                        <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:15px;font-weight:700;">Identifiants de connexion</div>
                        <div style="font-size:11px;color:#94A3B8;">À donner au vendeur — il s'en servira sur <span style="font-family:monospace;color:#5EEAD4;">/vendor/login</span></div>
                    </div>
                </div>

                <div style="position:relative;display:flex;flex-direction:column;gap:12px;">
                    {{-- Code vendeur --}}
                    <div>
                        <label style="display:flex;align-items:center;justify-content:space-between;font-size:11px;font-weight:700;color:#94A3B8;margin-bottom:5px;text-transform:uppercase;letter-spacing:0.06em;">
                            <span>Code vendeur <span style="color:#F87171;">*</span></span>
                            <button type="button" @click="regenCode()"
                                    style="display:inline-flex;align-items:center;gap:4px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.10);color:#5EEAD4;padding:3px 8px;border-radius:6px;font-size:10px;font-weight:700;cursor:pointer;text-transform:none;letter-spacing:0;">
                                <svg style="width:10px;height:10px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Régénérer
                            </button>
                        </label>
                        <div style="position:relative;">
                            <input type="text" name="vendor_code" required maxlength="20" x-model="form.vendor_code"
                                   style="width:100%;padding:12px 70px 12px 14px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);border-radius:10px;font-size:15px;color:white;font-family:monospace;font-weight:700;letter-spacing:0.08em;outline:none;text-transform:uppercase;">
                            <button type="button" @click="copy(form.vendor_code, 'code')"
                                    style="position:absolute;right:6px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.10);border:0;color:#5EEAD4;padding:6px 10px;border-radius:7px;font-size:10px;font-weight:700;cursor:pointer;">
                                <span x-text="copyState.code ? '✓' : 'Copier'"></span>
                            </button>
                        </div>
                    </div>

                    {{-- Mot de passe --}}
                    <div>
                        <label style="display:flex;align-items:center;justify-content:space-between;font-size:11px;font-weight:700;color:#94A3B8;margin-bottom:5px;text-transform:uppercase;letter-spacing:0.06em;">
                            <span>Mot de passe <span style="color:#F87171;">*</span></span>
                            <button type="button" @click="regenPass()"
                                    style="display:inline-flex;align-items:center;gap:4px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.10);color:#5EEAD4;padding:3px 8px;border-radius:6px;font-size:10px;font-weight:700;cursor:pointer;text-transform:none;letter-spacing:0;">
                                <svg style="width:10px;height:10px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Régénérer
                            </button>
                        </label>
                        <div style="position:relative;">
                            <input :type="showPass ? 'text' : 'password'" name="password" required minlength="6" x-model="form.password"
                                   style="width:100%;padding:12px 110px 12px 14px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);border-radius:10px;font-size:15px;color:white;font-family:monospace;font-weight:700;outline:none;">
                            <div style="position:absolute;right:6px;top:50%;transform:translateY(-50%);display:flex;gap:4px;">
                                <button type="button" @click="showPass = !showPass" :title="showPass ? 'Masquer' : 'Afficher'"
                                        style="background:rgba(255,255,255,0.10);border:0;color:#94A3B8;padding:6px 8px;border-radius:7px;cursor:pointer;display:flex;align-items:center;">
                                    <svg x-show="!showPass" style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="showPass" style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                                <button type="button" @click="copy(form.password, 'pass')"
                                        style="background:rgba(255,255,255,0.10);border:0;color:#5EEAD4;padding:6px 10px;border-radius:7px;font-size:10px;font-weight:700;cursor:pointer;">
                                    <span x-text="copyState.pass ? '✓' : 'Copier'"></span>
                                </button>
                            </div>
                        </div>
                        {{-- Force du mot de passe --}}
                        <div style="display:flex;align-items:center;gap:8px;margin-top:8px;">
                            <div style="flex:1;height:4px;background:rgba(255,255,255,0.08);border-radius:9999px;overflow:hidden;">
                                <div :style="`width:${passStrength.pct}%;background:${passStrength.color};`" style="height:100%;transition:all .2s;"></div>
                            </div>
                            <div style="font-size:10px;font-weight:700;color:#94A3B8;min-width:60px;text-align:right;" x-text="passStrength.label"></div>
                        </div>
                    </div>

                    {{-- Copier tout --}}
                    <button type="button" @click="copyAll()"
                            style="margin-top:4px;display:flex;align-items:center;justify-content:center;gap:8px;padding:11px;background:rgba(78,205,196,0.10);border:1px dashed rgba(78,205,196,0.40);color:#5EEAD4;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;transition:background .15s;"
                            onmouseover="this.style.background='rgba(78,205,196,0.18)';"
                            onmouseout="this.style.background='rgba(78,205,196,0.10)';">
                        <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <span x-text="copyState.all ? 'Identifiants copiés ✓' : 'Copier tout (code + mot de passe)'"></span>
                    </button>
                </div>
            </div>

            {{-- ÉTAPE 3 : Limites & commission --}}
            <div style="background:white;border-radius:16px;border:1px solid #E2E8F0;padding:22px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
                    <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;display:flex;align-items:center;justify-content:center;font-family:'Space Grotesk','Inter',sans-serif;font-weight:800;font-size:13px;">3</div>
                    <div>
                        <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:15px;font-weight:700;color:#0F172A;">Plafond &amp; commission</div>
                        <div style="font-size:11px;color:#64748B;">Le vendeur sera limité à ces réglages.</div>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:18px;">
                    {{-- Plafond --}}
                    <div>
                        <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:10px;">
                            <label style="font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.06em;">Plafond du portefeuille <span style="color:#F43F5E;">*</span></label>
                            <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:20px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;line-height:1;">
                                <span x-text="fmt(form.max_wallet)"></span><span style="font-size:11px;color:#94A3B8;font-weight:600;margin-left:4px;">FCFA</span>
                            </div>
                        </div>
                        <input type="range" min="1000" max="150000" step="1000" x-model.number="form.max_wallet"
                               style="width:100%;accent-color:#44A08D;cursor:pointer;">
                        <input type="hidden" name="max_wallet" :value="form.max_wallet">

                        {{-- Pills plafond --}}
                        <div style="margin-top:14px;display:flex;flex-wrap:wrap;gap:8px;">
                            <template x-for="preset in [25000, 50000, 100000, 150000]" :key="preset">
                                <button type="button" @click="form.max_wallet = preset"
                                        :class="form.max_wallet === preset ? 'pill-active-dark' : 'pill-inactive'"
                                        class="pill-base">
                                    <span class="pill-value" x-text="(preset/1000) + 'k'"></span>
                                    <span class="pill-suffix" x-text="'FCFA'"></span>
                                </button>
                            </template>
                        </div>
                        <div style="font-size:11px;color:#94A3B8;margin-top:10px;line-height:1.5;">Plage 1 000 → 150 000 FCFA — utilise les raccourcis ou le curseur.</div>
                    </div>

                    {{-- Solde initial --}}
                    <div>
                        <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:10px;gap:10px;">
                            <label style="font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.06em;">Solde initial à charger</label>
                            <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:20px;font-weight:800;color:#44A08D;font-variant-numeric:tabular-nums;line-height:1;">
                                <span x-text="fmt(form.initial_credit)"></span><span style="font-size:11px;color:#94A3B8;font-weight:600;margin-left:4px;">FCFA</span>
                            </div>
                        </div>
                        <input type="range" min="0" :max="form.max_wallet" step="1000" x-model.number="form.initial_credit"
                               style="width:100%;accent-color:#44A08D;cursor:pointer;">
                        <input type="hidden" name="initial_credit" :value="form.initial_credit">

                        {{-- Pills solde initial --}}
                        <div style="margin-top:14px;display:flex;flex-wrap:wrap;gap:8px;">
                            <button type="button" @click="form.initial_credit = 0"
                                    :class="form.initial_credit === 0 ? 'pill-active-dark' : 'pill-inactive'"
                                    class="pill-base">
                                <span class="pill-value">Aucun</span>
                            </button>
                            <template x-for="preset in initialPresets" :key="preset">
                                <button type="button" @click="form.initial_credit = preset"
                                        :class="form.initial_credit === preset ? 'pill-active-brand' : 'pill-inactive'"
                                        class="pill-base">
                                    <span class="pill-value" x-text="(preset/1000) + 'k'"></span>
                                    <span class="pill-suffix">FCFA</span>
                                </button>
                            </template>
                            <button type="button" @click="form.initial_credit = form.max_wallet"
                                    :class="form.initial_credit === form.max_wallet && form.max_wallet > 0 ? 'pill-active-brand' : 'pill-inactive'"
                                    class="pill-base">
                                <span class="pill-value">Plein</span>
                                <span class="pill-suffix">MAX</span>
                            </button>
                        </div>

                        {{-- Bandeau d'info contextuelle --}}
                        <div :style="form.initial_credit > 0
                                ? 'background:linear-gradient(135deg,#ECFDF5,#D1FAE5);border-color:#6EE7B7;color:#065F46;'
                                : 'background:#F8FAFC;border-color:#E2E8F0;color:#64748B;'"
                             style="margin-top:10px;border:1px solid;border-radius:10px;padding:9px 12px;font-size:11px;line-height:1.5;font-weight:500;display:flex;align-items:center;gap:8px;transition:all .2s;">
                            <svg x-show="form.initial_credit > 0" style="width:13px;height:13px;flex-shrink:0;color:#059669;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <svg x-show="form.initial_credit === 0" style="width:13px;height:13px;flex-shrink:0;color:#94A3B8;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span x-show="form.initial_credit > 0" x-text="`Le wallet sera crédité de ${fmt(form.initial_credit)} FCFA dès la création.`"></span>
                            <span x-show="form.initial_credit === 0">Le wallet démarrera à 0 — tu pourras le recharger plus tard depuis la fiche du vendeur.</span>
                        </div>
                    </div>

                    {{-- Commission --}}
                    <div>
                        <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:10px;">
                            <label style="font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.06em;">Commission par vente <span style="color:#F43F5E;">*</span></label>
                            <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:20px;font-weight:800;color:#44A08D;font-variant-numeric:tabular-nums;line-height:1;">
                                <span x-text="form.commission_rate.toFixed(1)"></span><span style="font-size:14px;">%</span>
                            </div>
                        </div>
                        <input type="range" min="0" max="20" step="0.5" x-model.number="form.commission_rate"
                               style="width:100%;accent-color:#44A08D;cursor:pointer;">
                        <input type="hidden" name="commission_rate" :value="form.commission_rate">

                        {{-- Pills commission --}}
                        <div style="margin-top:14px;display:flex;flex-wrap:wrap;gap:10px;">
                            <template x-for="preset in [{v:1,l:'Éco'},{v:3,l:'Standard'},{v:5,l:'Premium'},{v:10,l:'VIP'}]" :key="preset.v">
                                <button type="button" @click="form.commission_rate = preset.v"
                                        :class="form.commission_rate === preset.v ? 'pill-active-brand' : 'pill-inactive'"
                                        class="pill-base">
                                    <span class="pill-value" x-text="preset.v + '%'"></span>
                                    <span class="pill-tag" x-text="preset.l"></span>
                                    <template x-if="preset.v === 3 && form.commission_rate !== 3">
                                        <span class="pill-flag">Conseillé</span>
                                    </template>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Simulateur --}}
                    <div style="background:linear-gradient(135deg,#F0FDFA,#ECFDF5);border:1px dashed #5EEAD4;border-radius:12px;padding:14px;">
                        <div style="display:flex;align-items:center;gap:6px;font-size:11px;font-weight:700;color:#0F766E;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:8px;">
                            <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            Simulateur de gain
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                            <div style="flex:1;min-width:140px;">
                                <label style="font-size:10px;color:#64748B;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;">Sur une vente de</label>
                                <div style="position:relative;margin-top:4px;">
                                    <input type="number" min="0" step="500" x-model.number="simulator"
                                           style="width:100%;padding:8px 50px 8px 12px;background:white;border:1px solid #A7F3D0;border-radius:8px;font-size:14px;font-weight:700;outline:none;font-variant-numeric:tabular-nums;">
                                    <span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:10px;color:#94A3B8;font-weight:700;">FCFA</span>
                                </div>
                            </div>
                            <div style="font-size:24px;color:#94A3B8;align-self:flex-end;padding-bottom:6px;">→</div>
                            <div style="flex:1;min-width:140px;">
                                <label style="font-size:10px;color:#64748B;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;">Le vendeur gagnera</label>
                                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#0F766E;font-variant-numeric:tabular-nums;line-height:1.3;">
                                    +<span x-text="fmt(simulatedGain)"></span> <span style="font-size:11px;font-weight:600;color:#94A3B8;">FCFA</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ÉTAPE 4 : Activation --}}
            <div style="background:white;border-radius:16px;border:1px solid #E2E8F0;padding:18px 22px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
                <label style="display:flex;align-items:center;gap:14px;cursor:pointer;">
                    {{-- Switch --}}
                    <div @click="form.is_active = !form.is_active"
                         :style="form.is_active ? 'background:linear-gradient(135deg,#44A08D,#4ECDC4);' : 'background:#CBD5E1;'"
                         style="position:relative;width:46px;height:26px;border-radius:9999px;transition:background .2s;flex-shrink:0;">
                        <div :style="form.is_active ? 'transform:translateX(20px);' : 'transform:translateX(0);'"
                             style="position:absolute;top:3px;left:3px;width:20px;height:20px;background:white;border-radius:50%;box-shadow:0 2px 6px rgba(0,0,0,0.15);transition:transform .2s;"></div>
                    </div>
                    <input type="checkbox" name="is_active" value="1" x-model="form.is_active" style="display:none;">
                    <div style="flex:1;">
                        <div style="font-size:14px;font-weight:700;color:#0F172A;">Compte actif dès la création</div>
                        <div style="font-size:12px;color:#64748B;margin-top:2px;" x-text="form.is_active ? 'Le vendeur peut se connecter immédiatement.' : 'Le vendeur sera bloqué (créé mais pas autorisé à se connecter).'"></div>
                    </div>
                </label>
            </div>

            {{-- Submit --}}
            <div class="form-actions">
                <a href="{{ route('admin.resellers.index') }}" class="btn-cancel">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    Annuler
                </a>
                <button type="submit" :disabled="!isValid"
                        :class="isValid ? 'btn-submit' : 'btn-submit btn-submit--disabled'">
                    <span>Créer le vendeur</span>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </button>
            </div>
        </div>

        {{-- =========================================================== --}}
        {{-- COLONNE PRÉVISUALISATION                                    --}}
        {{-- =========================================================== --}}
        <div style="position:sticky;top:90px;display:flex;flex-direction:column;gap:14px;">
            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.10em;color:#94A3B8;text-align:center;">Aperçu vendeur</div>

            {{-- Carte vendeur preview (mimics dashboard hero) --}}
            <div style="background:linear-gradient(135deg,#0F172A,#1E293B);border-radius:18px;padding:22px;color:white;position:relative;overflow:hidden;box-shadow:0 25px 50px -12px rgba(15,23,42,0.30);">
                <div style="position:absolute;top:-50px;right:-50px;width:200px;height:200px;border-radius:100px;background:radial-gradient(circle,rgba(78,205,196,0.20) 0%,transparent 70%);"></div>

                <div style="position:relative;display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                    <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#44A08D,#4ECDC4);display:flex;align-items:center;justify-content:center;font-family:'Space Grotesk','Inter',sans-serif;font-weight:800;font-size:20px;color:#0F172A;flex-shrink:0;"
                         x-text="form.name ? form.name.trim().charAt(0).toUpperCase() : '?'"></div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:15px;font-weight:700;line-height:1.2;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                             x-text="form.name || 'Nom du vendeur'"
                             :style="!form.name && 'color:#475569;font-style:italic;'"></div>
                        <div style="font-family:monospace;font-size:11px;color:#5EEAD4;font-weight:700;letter-spacing:0.05em;margin-top:2px;"
                             x-text="form.vendor_code || 'KA-V-XXXX'"></div>
                    </div>
                </div>

                <div style="position:relative;padding-top:14px;border-top:1px solid rgba(255,255,255,0.08);">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                        <div style="font-size:10px;color:#94A3B8;text-transform:uppercase;letter-spacing:0.06em;font-weight:700;">Solde portefeuille</div>
                        <div x-show="form.initial_credit > 0" style="display:inline-flex;align-items:center;gap:4px;font-size:9px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;background:rgba(78,205,196,0.15);color:#5EEAD4;padding:3px 7px;border-radius:9999px;line-height:1;">
                            <svg style="width:9px;height:9px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Pré-chargé
                        </div>
                    </div>
                    <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:24px;font-weight:800;font-variant-numeric:tabular-nums;margin-top:4px;">
                        <span x-text="fmt(form.initial_credit)"></span> <span style="font-size:13px;color:#94A3B8;font-weight:600;">/ <span x-text="fmt(form.max_wallet)"></span> FCFA</span>
                    </div>
                    <div style="position:relative;height:6px;background:rgba(255,255,255,0.08);border-radius:9999px;overflow:hidden;margin-top:10px;">
                        <div :style="`width:${form.max_wallet > 0 ? Math.min(100, (form.initial_credit / form.max_wallet) * 100) : 0}%;`"
                             style="height:100%;background:linear-gradient(90deg,#44A08D,#4ECDC4);transition:width .3s;"></div>
                    </div>
                    <div style="font-size:11px;color:#94A3B8;margin-top:8px;">Commission : <strong style="color:#5EEAD4;" x-text="form.commission_rate.toFixed(1) + '%'"></strong> sur chaque vente</div>
                </div>
            </div>

            {{-- Statut (pill compact, raccord visuel avec la hero card sombre du dessus) --}}
            <div style="background:linear-gradient(135deg,#0F172A,#1E293B);border-radius:18px;padding:14px 16px;display:flex;align-items:center;gap:12px;position:relative;overflow:hidden;box-shadow:0 12px 28px -14px rgba(15,23,42,0.4);">
                <div style="position:absolute;top:-30px;right:-30px;width:120px;height:120px;border-radius:50%;"
                     :style="form.is_active
                        ? 'background:radial-gradient(circle,rgba(78,205,196,0.22) 0%,transparent 70%);'
                        : 'background:radial-gradient(circle,rgba(244,63,94,0.18) 0%,transparent 70%);'"></div>

                <div style="position:relative;display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;flex-shrink:0;"
                     :style="form.is_active
                        ? 'background:rgba(78,205,196,0.15);border:1px solid rgba(78,205,196,0.35);'
                        : 'background:rgba(244,63,94,0.12);border:1px solid rgba(244,63,94,0.30);'">
                    <span style="position:relative;display:flex;width:8px;height:8px;">
                        <span x-show="form.is_active" style="position:absolute;inset:0;border-radius:50%;background:#5EEAD4;opacity:0.6;animation:vendor-ping 1.5s cubic-bezier(0,0,0.2,1) infinite;"></span>
                        <span style="position:relative;width:8px;height:8px;border-radius:50%;"
                              :style="form.is_active ? 'background:#5EEAD4;' : 'background:#F87171;'"></span>
                    </span>
                </div>

                <div style="position:relative;flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:13px;font-weight:700;color:white;letter-spacing:-0.01em;line-height:1.2;"
                             x-text="form.is_active ? 'Prêt à se connecter' : 'Compte bloqué'"></div>
                        <span style="font-size:8px;font-weight:800;letter-spacing:0.10em;text-transform:uppercase;padding:2px 7px;border-radius:9999px;line-height:1;"
                              :style="form.is_active
                                ? 'background:rgba(78,205,196,0.15);color:#5EEAD4;'
                                : 'background:rgba(244,63,94,0.15);color:#FCA5A5;'"
                              x-text="form.is_active ? 'Actif' : 'Bloqué'"></span>
                    </div>
                    <div style="font-size:11px;color:#94A3B8;margin-top:3px;line-height:1.4;"
                         x-text="form.is_active ? 'Le vendeur accède à son espace dès la création.' : 'Tu pourras l\'activer manuellement plus tard.'"></div>
                </div>
            </div>

            {{-- Récap identifiants --}}
            <div class="recap-card">
                <div class="recap-header">
                    <span class="recap-title">À transmettre au vendeur</span>
                    <button type="button" @click="copyAll()" class="recap-copy-all">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <span x-text="copyState.all ? 'Copié ✓' : 'Tout copier'"></span>
                    </button>
                </div>

                <div class="recap-rows">
                    {{-- URL --}}
                    <div class="recap-row">
                        <div class="recap-row-icon recap-row-icon--blue">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        </div>
                        <div class="recap-row-body">
                            <div class="recap-row-label">URL de connexion</div>
                            <div class="recap-row-value recap-row-value--small">{{ url('/vendor/login') }}</div>
                        </div>
                        <button type="button" @click="copy('{{ url('/vendor/login') }}', 'url')" class="recap-row-btn"
                                :class="copyState.url ? 'recap-row-btn--ok' : ''" title="Copier l'URL">
                            <svg x-show="!copyState.url" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <svg x-show="copyState.url" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </button>
                    </div>

                    {{-- Code --}}
                    <div class="recap-row">
                        <div class="recap-row-icon recap-row-icon--cyan">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="recap-row-body">
                            <div class="recap-row-label">Code vendeur</div>
                            <div class="recap-row-value" x-text="form.vendor_code || '—'"></div>
                        </div>
                        <button type="button" @click="copy(form.vendor_code, 'code2')" class="recap-row-btn"
                                :class="copyState.code2 ? 'recap-row-btn--ok' : ''" title="Copier le code">
                            <svg x-show="!copyState.code2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <svg x-show="copyState.code2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </button>
                    </div>

                    {{-- Mot de passe --}}
                    <div class="recap-row">
                        <div class="recap-row-icon recap-row-icon--amber">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <div class="recap-row-body">
                            <div class="recap-row-label">Mot de passe</div>
                            <div class="recap-row-value" x-text="showPass ? (form.password || '—') : '••••••••'"></div>
                        </div>
                        <button type="button" @click="showPass = !showPass" class="recap-row-btn" :title="showPass ? 'Masquer' : 'Afficher'">
                            <svg x-show="!showPass" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="showPass" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                        <button type="button" @click="copy(form.password, 'pass2')" class="recap-row-btn"
                                :class="copyState.pass2 ? 'recap-row-btn--ok' : ''" title="Copier le mot de passe">
                            <svg x-show="!copyState.pass2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <svg x-show="copyState.pass2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function resellerForm(init) {
    return {
        form: {
            name:            init.oldName,
            phone:           init.oldPhone,
            email:           init.oldEmail,
            vendor_code:     init.oldCode,
            password:        init.oldPass,
            max_wallet:      init.oldMaxWallet,
            initial_credit:  init.oldInitial,
            commission_rate: init.oldRate,
            is_active:       init.oldActive,
        },
        showPass: true,
        simulator: 10000,
        copyState: { code: false, pass: false, all: false, url: false, code2: false, pass2: false },

        get initialPresets() {
            const max = this.form.max_wallet;
            const candidates = [10000, 25000, 50000, 100000];
            return candidates.filter(v => v < max);
        },

        $watch_max() {
            // garde l'initial <= max
            if (this.form.initial_credit > this.form.max_wallet) {
                this.form.initial_credit = this.form.max_wallet;
            }
        },

        get isValid() {
            return this.form.name.trim().length > 0
                && this.form.vendor_code.trim().length >= 3
                && this.form.password.length >= 6;
        },

        get simulatedGain() {
            return Math.round(this.simulator * (this.form.commission_rate / 100));
        },

        get passStrength() {
            const p = this.form.password || '';
            let score = 0;
            if (p.length >= 6) score++;
            if (p.length >= 10) score++;
            if (/[A-Z]/.test(p) && /[a-z]/.test(p)) score++;
            if (/[0-9]/.test(p)) score++;
            if (/[^A-Za-z0-9]/.test(p)) score++;
            const map = [
                { pct: 10, color: '#F43F5E', label: 'Très faible' },
                { pct: 25, color: '#F43F5E', label: 'Faible' },
                { pct: 50, color: '#F59E0B', label: 'Moyen' },
                { pct: 75, color: '#10B981', label: 'Bon' },
                { pct: 90, color: '#10B981', label: 'Fort' },
                { pct: 100, color: '#0F766E', label: 'Excellent' },
            ];
            return map[Math.min(score, 5)];
        },

        fmt(n) {
            return new Intl.NumberFormat('fr-FR').format(Math.round(n || 0));
        },

        randStr(len, charset) {
            const set = charset || 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
            let s = '';
            for (let i = 0; i < len; i++) s += set.charAt(Math.floor(Math.random() * set.length));
            return s;
        },

        regenCode() {
            this.form.vendor_code = 'KA-V-' + this.randStr(4);
        },

        regenPass() {
            this.form.password = 'KA-' + this.randStr(6, 'ABCDEFGHJKMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz');
        },

        async copy(text, key) {
            try {
                await navigator.clipboard.writeText(text);
                this.copyState[key] = true;
                setTimeout(() => { this.copyState[key] = false; }, 1600);
            } catch (e) {}
        },

        async copyAll() {
            const txt = `KardAfrica — Espace vendeur\nURL : {{ url('/vendor/login') }}\nCode : ${this.form.vendor_code}\nMot de passe : ${this.form.password}`;
            await this.copy(txt, 'all');
        },

        init() {
            this.$watch('form.max_wallet', () => {
                if (this.form.initial_credit > this.form.max_wallet) {
                    this.form.initial_credit = this.form.max_wallet;
                }
            });
        },
    };
}
</script>

<style>
    @media (max-width: 980px) {
        form[data-no-loader] {
            grid-template-columns: 1fr !important;
        }
        form[data-no-loader] > div:last-child {
            position: static !important;
        }
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
    @keyframes vendor-ping {
        75%, 100% { transform: scale(2); opacity: 0; }
    }

    /* ===== Pills sélecteur (plafond + commission) ===== */
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
    .pill-base:focus { outline: 2px solid rgba(78,205,196,0.40); outline-offset: 2px; }

    .pill-inactive {
        background: #F1F5F9;
        color: #475569;
        border-color: #E2E8F0;
    }
    .pill-inactive:hover {
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

    .pill-value {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 13px;
        font-weight: 800;
        line-height: 1;
    }
    .pill-suffix {
        font-family: 'Inter', sans-serif;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: 0.10em;
        line-height: 1;
    }
    .pill-tag {
        font-family: 'Inter', sans-serif;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding: 4px 8px;
        border-radius: 9999px;
        line-height: 1;
    }
    .pill-flag {
        position: absolute;
        top: -9px;
        right: -8px;
        background: #0F172A;
        color: #5EEAD4;
        font-family: 'Inter', sans-serif;
        font-size: 8px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.10em;
        padding: 3px 8px;
        border-radius: 9999px;
        white-space: nowrap;
        line-height: 1;
        box-shadow: 0 6px 14px -3px rgba(15,23,42,0.40);
        border: 1.5px solid white;
    }

    /* ===== Boutons d'action du formulaire ===== */
    .form-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 4px;
    }

    .btn-cancel {
        display: inline-flex !important;
        align-items: center;
        gap: 8px;
        padding: 12px 18px;
        background: white;
        border: 1.5px solid #E2E8F0;
        border-radius: 12px;
        color: #64748B;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        line-height: 1;
        transition: all .18s ease;
        cursor: pointer;
    }
    .btn-cancel svg { width: 14px; height: 14px; flex-shrink: 0; }
    .btn-cancel:hover {
        background: #F8FAFC;
        border-color: #FCA5A5;
        color: #BE123C;
    }

    .btn-submit {
        display: inline-flex !important;
        align-items: center;
        gap: 10px;
        padding: 13px 26px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        border: 0;
        border-radius: 12px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 800;
        line-height: 1;
        cursor: pointer;
        white-space: nowrap;
        box-shadow: 0 14px 30px -10px rgba(68,160,141,0.55),
                    inset 0 1px 0 rgba(255,255,255,0.30);
        transition: all .18s ease;
        appearance: none;
        -webkit-appearance: none;
    }
    .btn-submit svg { width: 14px; height: 14px; flex-shrink: 0; transition: transform .2s ease; }
    .btn-submit:hover:not(.btn-submit--disabled) {
        transform: translateY(-2px);
        box-shadow: 0 18px 36px -10px rgba(68,160,141,0.65),
                    inset 0 1px 0 rgba(255,255,255,0.30);
    }
    .btn-submit:hover:not(.btn-submit--disabled) svg { transform: translateX(3px); }

    .btn-submit--disabled {
        background: #E2E8F0;
        color: #94A3B8;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }
    .btn-submit--disabled:hover { transform: none; box-shadow: none; }

    /* ===== Carte récap "À transmettre au vendeur" ===== */
    .recap-card {
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        padding: 16px;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }
    .recap-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
        gap: 8px;
    }
    .recap-title {
        font-family: 'Inter', sans-serif;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.10em;
        color: #44A08D;
    }
    .recap-copy-all {
        display: inline-flex !important;
        align-items: center;
        gap: 5px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        border: 0;
        padding: 5px 10px;
        border-radius: 9999px;
        font-family: 'Inter', sans-serif;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.04em;
        cursor: pointer;
        line-height: 1;
        box-shadow: 0 4px 10px -3px rgba(68,160,141,0.40);
        transition: all .15s ease;
        appearance: none;
        -webkit-appearance: none;
    }
    .recap-copy-all:hover { transform: translateY(-1px); box-shadow: 0 6px 14px -3px rgba(68,160,141,0.55); }
    .recap-copy-all svg { width: 11px; height: 11px; flex-shrink: 0; }

    .recap-rows {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .recap-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 10px;
        background: #F8FAFC;
        border: 1px solid transparent;
        border-radius: 10px;
        transition: all .15s ease;
    }
    .recap-row:hover {
        background: white;
        border-color: #E2E8F0;
        box-shadow: 0 2px 8px -3px rgba(15,23,42,0.08);
    }
    .recap-row-icon {
        flex-shrink: 0;
        width: 28px; height: 28px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
    }
    .recap-row-icon svg { width: 14px; height: 14px; }
    .recap-row-icon--blue  { background: #DBEAFE; color: #1D4ED8; }
    .recap-row-icon--cyan  { background: #CFFAFE; color: #0E7490; }
    .recap-row-icon--amber { background: #FEF3C7; color: #B45309; }

    .recap-row-body {
        flex: 1; min-width: 0;
    }
    .recap-row-label {
        font-family: 'Inter', sans-serif;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #94A3B8;
        line-height: 1.2;
    }
    .recap-row-value {
        font-family: 'JetBrains Mono', 'Fira Code', monospace;
        font-size: 13px;
        font-weight: 700;
        color: #0F172A;
        line-height: 1.4;
        margin-top: 3px;
        word-break: break-all;
    }
    .recap-row-value--small { font-size: 11px; line-height: 1.3; }

    .recap-row-btn {
        flex-shrink: 0;
        background: white;
        border: 1px solid #E2E8F0;
        color: #64748B;
        padding: 6px;
        border-radius: 8px;
        cursor: pointer;
        display: inline-flex !important;
        align-items: center; justify-content: center;
        transition: all .15s ease;
        appearance: none;
        -webkit-appearance: none;
    }
    .recap-row-btn svg { width: 13px; height: 13px; }
    .recap-row-btn:hover {
        background: #0F172A;
        color: #5EEAD4;
        border-color: #0F172A;
        transform: translateY(-1px);
    }
    .recap-row-btn--ok {
        background: #D1FAE5 !important;
        color: #047857 !important;
        border-color: #6EE7B7 !important;
        transform: none !important;
    }
</style>
@endsection
