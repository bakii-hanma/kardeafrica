@extends('admin.layouts.admin')

@section('title', 'Créer un utilisateur')
@section('page-title', 'Nouvel utilisateur')

@section('content')
<div style="padding:24px;font-family:'Inter','Figtree',sans-serif;">

    <a href="{{ route('admin.users.index') }}"
       style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:white;border:1px solid #E2E8F0;border-radius:9px;color:#475569;text-decoration:none;font-size:13px;font-weight:600;margin-bottom:14px;transition:all 0.15s;"
       onmouseover="this.style.borderColor='#CBD5E1';" onmouseout="this.style.borderColor='#E2E8F0';">
        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Tous les utilisateurs
    </a>

    <div style="display:grid;grid-template-columns:1fr 320px;gap:18px;align-items:flex-start;max-width:1100px;">

        {{-- ===== FORMULAIRE PRINCIPAL ===== --}}
        <div style="background:white;border-radius:16px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);overflow:hidden;"
             x-data="userCreateForm()">

            {{-- Header --}}
            <div style="background:linear-gradient(135deg,#1F2937 0%,#0F172A 100%);padding:22px 26px;color:white;position:relative;overflow:hidden;">
                <div style="position:absolute;top:-30px;right:-30px;width:180px;height:180px;border-radius:50%;background:rgba(78,205,196,0.18);filter:blur(40px);"></div>
                <div style="position:relative;display:flex;align-items:center;gap:14px;">
                    <div style="width:48px;height:48px;border-radius:13px;background:linear-gradient(135deg,#4ECDC4,#44A08D);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 8px 16px rgba(78,205,196,0.3);">
                        <svg style="width:22px;height:22px;color:white;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    </div>
                    <div>
                        <h2 style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:20px;font-weight:700;margin:0;letter-spacing:-0.01em;">Nouveau compte utilisateur</h2>
                        <p style="font-size:12px;color:#94A3B8;margin:3px 0 0;">Remplissez les informations ci-dessous pour créer un nouveau compte.</p>
                    </div>
                </div>
            </div>

            {{-- Erreurs --}}
            @if($errors->any())
                <div style="margin:18px 26px 0;padding:12px 14px;background:#FEF2F2;border:1px solid #FECACA;border-radius:11px;">
                    <div style="display:flex;align-items:flex-start;gap:8px;">
                        <svg style="width:16px;height:16px;color:#DC2626;flex-shrink:0;margin-top:2px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <div style="flex:1;font-size:12px;color:#991B1B;">
                            <div style="font-weight:700;margin-bottom:2px;">Veuillez corriger les erreurs suivantes :</div>
                            <ul style="margin:0;padding-left:16px;list-style:disc;line-height:1.6;">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.users.store') }}" style="padding:24px 26px;display:flex;flex-direction:column;gap:18px;">
                @csrf

                {{-- Section : Identité --}}
                <div>
                    <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#64748B;margin-bottom:10px;display:flex;align-items:center;gap:6px;">
                        <span style="width:18px;height:18px;border-radius:5px;background:#ECFDF5;color:#059669;display:inline-flex;align-items:center;justify-content:center;">
                            <svg style="width:10px;height:10px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </span>
                        Identité
                    </div>

                    <div style="display:grid;grid-template-columns:1fr;gap:12px;">
                        <div>
                            <label for="name" style="display:block;font-size:12px;font-weight:600;color:#334155;margin-bottom:4px;">
                                Nom complet <span style="color:#DC2626;">*</span>
                            </label>
                            <div style="position:relative;">
                                <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:#94A3B8;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                       placeholder="Ex: Jean Dupont"
                                       x-model="form.name"
                                       style="width:100%;padding:10px 14px 10px 36px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;outline:none;transition:all 0.15s;"
                                       onfocus="this.style.background='white';this.style.borderColor='#44A08D';this.style.boxShadow='0 0 0 3px rgba(68,160,141,0.1)';"
                                       onblur="this.style.background='#F8FAFC';this.style.borderColor='#E2E8F0';this.style.boxShadow='none';">
                            </div>
                        </div>

                        <div>
                            <label for="email" style="display:block;font-size:12px;font-weight:600;color:#334155;margin-bottom:4px;">
                                Adresse email <span style="color:#DC2626;">*</span>
                            </label>
                            <div style="position:relative;">
                                <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:#94A3B8;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                       placeholder="email@exemple.com"
                                       x-model="form.email"
                                       style="width:100%;padding:10px 14px 10px 36px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;outline:none;transition:all 0.15s;"
                                       onfocus="this.style.background='white';this.style.borderColor='#44A08D';this.style.boxShadow='0 0 0 3px rgba(68,160,141,0.1)';"
                                       onblur="this.style.background='#F8FAFC';this.style.borderColor='#E2E8F0';this.style.boxShadow='none';">
                            </div>
                        </div>

                        <div>
                            <label for="phone" style="display:block;font-size:12px;font-weight:600;color:#334155;margin-bottom:4px;">
                                Téléphone <span style="color:#94A3B8;font-weight:400;">(optionnel)</span>
                            </label>
                            <div style="position:relative;">
                                <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:#94A3B8;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                                       placeholder="Ex: 074000000"
                                       style="width:100%;padding:10px 14px 10px 36px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;outline:none;transition:all 0.15s;"
                                       onfocus="this.style.background='white';this.style.borderColor='#44A08D';this.style.boxShadow='0 0 0 3px rgba(68,160,141,0.1)';"
                                       onblur="this.style.background='#F8FAFC';this.style.borderColor='#E2E8F0';this.style.boxShadow='none';">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section : Sécurité --}}
                <div style="padding-top:18px;border-top:1px solid #F1F5F9;">
                    <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#64748B;margin-bottom:10px;display:flex;align-items:center;gap:6px;">
                        <span style="width:18px;height:18px;border-radius:5px;background:#FAF5FF;color:#7C3AED;display:inline-flex;align-items:center;justify-content:center;">
                            <svg style="width:10px;height:10px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </span>
                        Sécurité
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div>
                            <label for="password" style="display:block;font-size:12px;font-weight:600;color:#334155;margin-bottom:4px;">
                                Mot de passe <span style="color:#DC2626;">*</span>
                            </label>
                            <div style="position:relative;">
                                <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:#94A3B8;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                <input :type="showPassword ? 'text' : 'password'" name="password" id="password" required minlength="6"
                                       placeholder="Min. 6 caractères"
                                       x-model="form.password"
                                       @input="evaluateStrength()"
                                       style="width:100%;padding:10px 36px 10px 36px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;outline:none;transition:all 0.15s;"
                                       onfocus="this.style.background='white';this.style.borderColor='#44A08D';this.style.boxShadow='0 0 0 3px rgba(68,160,141,0.1)';"
                                       onblur="this.style.background='#F8FAFC';this.style.borderColor='#E2E8F0';this.style.boxShadow='none';">
                                <button type="button" @click="showPassword = !showPassword"
                                        style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:6px;color:#94A3B8;border-radius:5px;">
                                    <svg x-show="!showPassword" style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="showPassword" x-cloak style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>

                            {{-- Strength meter --}}
                            <div x-show="form.password.length > 0" x-cloak style="margin-top:6px;">
                                <div style="display:flex;gap:3px;margin-bottom:4px;">
                                    <div :style="`flex:1;height:3px;border-radius:9999px;background:${strengthScore >= 1 ? strengthColor : '#E2E8F0'};transition:all 0.2s;`"></div>
                                    <div :style="`flex:1;height:3px;border-radius:9999px;background:${strengthScore >= 2 ? strengthColor : '#E2E8F0'};transition:all 0.2s;`"></div>
                                    <div :style="`flex:1;height:3px;border-radius:9999px;background:${strengthScore >= 3 ? strengthColor : '#E2E8F0'};transition:all 0.2s;`"></div>
                                    <div :style="`flex:1;height:3px;border-radius:9999px;background:${strengthScore >= 4 ? strengthColor : '#E2E8F0'};transition:all 0.2s;`"></div>
                                </div>
                                <div style="font-size:10px;font-weight:600;" :style="`color:${strengthColor};`" x-text="strengthLabel"></div>
                            </div>
                        </div>

                        <div>
                            <label for="password_confirmation" style="display:block;font-size:12px;font-weight:600;color:#334155;margin-bottom:4px;">
                                Confirmer <span style="color:#DC2626;">*</span>
                            </label>
                            <div style="position:relative;">
                                <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:#94A3B8;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                <input :type="showPassword ? 'text' : 'password'" name="password_confirmation" id="password_confirmation" required
                                       placeholder="Retaper le mot de passe"
                                       x-model="form.password_confirmation"
                                       style="width:100%;padding:10px 14px 10px 36px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;outline:none;transition:all 0.15s;"
                                       :style="form.password_confirmation && form.password !== form.password_confirmation ? 'border-color:#FCA5A5;background:#FEF2F2;' : ''"
                                       onfocus="!this.value || this.value === document.getElementById('password').value ? (this.style.background='white', this.style.borderColor='#44A08D', this.style.boxShadow='0 0 0 3px rgba(68,160,141,0.1)') : null;"
                                       onblur="this.style.boxShadow='none';">
                            </div>
                            <div x-show="form.password_confirmation && form.password !== form.password_confirmation" x-cloak
                                 style="font-size:10px;color:#DC2626;margin-top:4px;font-weight:600;">
                                Les mots de passe ne correspondent pas
                            </div>
                            <div x-show="form.password_confirmation && form.password === form.password_confirmation && form.password.length > 0" x-cloak
                                 style="font-size:10px;color:#059669;margin-top:4px;font-weight:600;display:flex;align-items:center;gap:3px;">
                                <svg style="width:10px;height:10px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Les mots de passe correspondent
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section : Rôle --}}
                <div style="padding-top:18px;border-top:1px solid #F1F5F9;">
                    <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#64748B;margin-bottom:10px;display:flex;align-items:center;gap:6px;">
                        <span style="width:18px;height:18px;border-radius:5px;background:#EFF6FF;color:#2563EB;display:inline-flex;align-items:center;justify-content:center;">
                            <svg style="width:10px;height:10px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </span>
                        Rôle et permissions
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:8px;">
                        @foreach ([
                            ['value' => 'user',      'label' => 'Client',        'desc' => 'Achat, suivi commandes', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'bg' => '#DBEAFE', 'color' => '#1E40AF'],
                            ['value' => 'moderator', 'label' => 'Modérateur',    'desc' => 'Support, gestion clients', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'bg' => '#FEF3C7', 'color' => '#92400E'],
                            ['value' => 'admin',     'label' => 'Administrateur','desc' => 'Accès complet à la console', 'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', 'bg' => '#EDE9FE', 'color' => '#5B21B6'],
                        ] as $opt)
                            @php $isSelected = (old('role', 'user') === $opt['value']); @endphp
                            <label style="cursor:pointer;display:block;">
                                <input type="radio" name="role" value="{{ $opt['value'] }}" {{ $isSelected ? 'checked' : '' }}
                                       x-model="form.role"
                                       style="position:absolute;opacity:0;pointer-events:none;">
                                <div :class="form.role === '{{ $opt['value'] }}' ? 'role-selected' : ''"
                                     style="padding:12px;border-radius:11px;border:2px solid #E2E8F0;background:white;transition:all 0.15s;display:flex;align-items:flex-start;gap:10px;"
                                     :style="form.role === '{{ $opt['value'] }}' ? 'border-color:#44A08D;background:#ECFDF5;box-shadow:0 0 0 4px rgba(68,160,141,0.08);' : ''">
                                    <div style="width:32px;height:32px;border-radius:9px;background:{{ $opt['bg'] }};color:{{ $opt['color'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $opt['icon'] }}"/></svg>
                                    </div>
                                    <div style="flex:1;min-width:0;">
                                        <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                                            <span style="font-size:13px;font-weight:700;color:#0F172A;">{{ $opt['label'] }}</span>
                                            <span x-show="form.role === '{{ $opt['value'] }}'" x-cloak
                                                  style="width:18px;height:18px;border-radius:50%;background:#44A08D;color:white;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                <svg style="width:10px;height:10px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            </span>
                                        </div>
                                        <div style="font-size:11px;color:#64748B;margin-top:2px;line-height:1.3;">{{ $opt['desc'] }}</div>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Submit --}}
                <div style="display:flex;align-items:center;gap:8px;padding-top:18px;border-top:1px solid #F1F5F9;">
                    <button type="submit"
                            :disabled="!isValid"
                            style="display:inline-flex;align-items:center;gap:8px;padding:12px 22px;background:linear-gradient(135deg,#4ECDC4,#44A08D);color:white;border:none;border-radius:11px;font-size:14px;font-weight:700;cursor:pointer;box-shadow:0 6px 16px rgba(68,160,141,0.25);transition:all 0.15s;"
                            :style="!isValid ? 'opacity:0.5;cursor:not-allowed;' : ''"
                            onmouseover="if(!this.disabled){this.style.transform='translateY(-1px)';this.style.boxShadow='0 8px 20px rgba(68,160,141,0.35)';}"
                            onmouseout="this.style.transform='none';this.style.boxShadow='0 6px 16px rgba(68,160,141,0.25)';">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        Créer le compte
                    </button>
                    <a href="{{ route('admin.users.index') }}"
                       style="padding:12px 22px;background:#F1F5F9;color:#475569;border:none;border-radius:11px;font-size:14px;font-weight:600;text-decoration:none;transition:all 0.15s;"
                       onmouseover="this.style.background='#E2E8F0';" onmouseout="this.style.background='#F1F5F9';">
                        Annuler
                    </a>
                </div>
            </form>
        </div>

        {{-- ===== SIDEBAR PREVIEW ===== --}}
        <aside style="display:flex;flex-direction:column;gap:14px;position:sticky;top:80px;" x-data x-show="true">
            {{-- Preview card --}}
            <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);overflow:hidden;">
                <div style="padding:12px 16px;border-bottom:1px solid #F1F5F9;background:linear-gradient(180deg,#F8FAFC,white);">
                    <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#64748B;">Aperçu en direct</div>
                </div>
                <div style="padding:16px;" x-data="{ get formData() { return Alpine.$data(document.querySelector('[x-data=\'userCreateForm()\']')) ?? {}; } }">
                    {{-- Mock card style admin/users/show header --}}
                    <div style="background:linear-gradient(135deg,#1F2937 0%,#0F172A 100%);border-radius:11px;padding:14px;color:white;position:relative;overflow:hidden;">
                        <div style="position:absolute;top:-20px;right:-20px;width:100px;height:100px;border-radius:50%;background:rgba(78,205,196,0.2);filter:blur(20px);"></div>
                        <div style="position:relative;display:flex;align-items:center;gap:10px;">
                            <div style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#4ECDC4,#44A08D);display:flex;align-items:center;justify-content:center;font-family:'Space Grotesk', 'Inter', sans-serif;font-weight:700;font-size:16px;color:white;flex-shrink:0;"
                                 x-text="formData.form?.name?.charAt(0)?.toUpperCase() || '?'"></div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:14px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                     x-text="formData.form?.name || 'Nom complet'"
                                     :style="formData.form?.name ? '' : 'color:rgba(255,255,255,0.4);'"></div>
                                <div style="font-size:10px;color:#CBD5E1;font-family:monospace;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                     x-text="formData.form?.email || 'email@exemple.com'"
                                     :style="formData.form?.email ? '' : 'color:rgba(203,213,225,0.4);'"></div>
                            </div>
                        </div>
                        <div style="margin-top:10px;display:flex;align-items:center;gap:6px;">
                            <span x-show="formData.form?.role === 'admin'" x-cloak style="padding:2px 7px;border-radius:9999px;font-size:9px;font-weight:700;background:rgba(167,139,250,0.25);color:#DDD6FE;">Admin</span>
                            <span x-show="formData.form?.role === 'moderator'" x-cloak style="padding:2px 7px;border-radius:9999px;font-size:9px;font-weight:700;background:rgba(251,191,36,0.25);color:#FDE68A;">Modérateur</span>
                            <span x-show="!formData.form?.role || formData.form?.role === 'user'" style="padding:2px 7px;border-radius:9999px;font-size:9px;font-weight:700;background:rgba(96,165,250,0.25);color:#BFDBFE;">Client</span>
                            <span style="display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:9999px;font-size:9px;font-weight:700;background:rgba(16,185,129,0.20);color:#86EFAC;">
                                <span style="width:4px;height:4px;border-radius:50%;background:#86EFAC;"></span>
                                Actif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tips --}}
            <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:12px;padding:14px;">
                <div style="display:flex;align-items:flex-start;gap:8px;">
                    <svg style="width:16px;height:16px;color:#2563EB;flex-shrink:0;margin-top:1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div style="flex:1;">
                        <div style="font-family:'Space Grotesk', 'Inter', sans-serif;font-size:12px;font-weight:700;color:#1E3A8A;margin-bottom:4px;">À savoir</div>
                        <ul style="margin:0;padding:0;list-style:none;font-size:11px;color:#1E40AF;line-height:1.6;">
                            <li>· Le compte est <strong>immédiatement actif</strong>.</li>
                            <li>· L'utilisateur peut se connecter avec son email.</li>
                            <li>· Le rôle peut être modifié à tout moment.</li>
                            <li>· Aucun email de bienvenue n'est envoyé.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<script>
function userCreateForm() {
    return {
        form: {
            name: '{{ old('name') }}',
            email: '{{ old('email') }}',
            password: '',
            password_confirmation: '',
            role: '{{ old('role', 'user') }}',
        },
        showPassword: false,
        strengthScore: 0,

        get strengthLabel() {
            return ['Trop faible', 'Faible', 'Correct', 'Bon', 'Excellent'][this.strengthScore] || '';
        },
        get strengthColor() {
            return ['#94A3B8', '#DC2626', '#F59E0B', '#3B82F6', '#059669'][this.strengthScore] || '#94A3B8';
        },
        get isValid() {
            return this.form.name.length > 1
                && this.form.email.includes('@')
                && this.form.password.length >= 6
                && this.form.password === this.form.password_confirmation;
        },

        evaluateStrength() {
            const p = this.form.password;
            let score = 0;
            if (p.length >= 6)  score++;
            if (p.length >= 10) score++;
            if (/[A-Z]/.test(p) && /[a-z]/.test(p)) score++;
            if (/\d/.test(p) && /[^A-Za-z0-9]/.test(p)) score++;
            this.strengthScore = score;
        },
    };
}
</script>
@endsection
