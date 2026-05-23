@extends('admin.layouts.admin')

@section('title', 'Paramètres')
@section('page-title', 'Paramètres système')

@section('content')
<style>
    @keyframes ka-spin { to { transform: rotate(360deg); } }
    .ka-spin { animation: ka-spin .8s linear infinite; transform-origin: center; }
</style>
<script>
    window.__settingsHealthRoute      = @json(route('admin.health.afrikard'));
    window.__settingsMaintenanceRoute = @json(route('admin.settings.maintenance'));
    window.__csrfToken                = @json(csrf_token());
</script>

<div style="padding:24px;font-family:'Inter','Figtree',sans-serif;max-width:1200px;margin:0 auto;"
     x-data="adminSettings()">

    {{-- ============================================================
         APP INFO
       ============================================================ --}}
    <section style="background:white;border-radius:16px;border:1px solid #E2E8F0;padding:22px;margin-bottom:18px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:18px;">
            <div>
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.10em;color:#44A08D;">Application</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:700;color:#0F172A;margin-top:4px;">{{ $info['app']['name'] }}</div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:9999px;font-size:11px;font-weight:700;
                             background:{{ $info['app']['env'] === 'production' ? '#D1FAE5' : '#FEF3C7' }};
                             color:{{ $info['app']['env'] === 'production' ? '#047857' : '#B45309' }};
                             border:1px solid {{ $info['app']['env'] === 'production' ? '#A7F3D0' : '#FDE68A' }};">
                    <span style="width:6px;height:6px;border-radius:50%;background:currentColor;"></span>
                    {{ ucfirst($info['app']['env']) }}
                </span>
                @if($info['app']['debug'])
                    <span style="display:inline-flex;align-items:center;padding:5px 12px;border-radius:9999px;font-size:11px;font-weight:700;background:#FFE4E6;color:#BE123C;border:1px solid #FECDD3;">DEBUG ON</span>
                @endif
                @if($info['maintenance']['down'])
                    <span style="display:inline-flex;align-items:center;padding:5px 12px;border-radius:9999px;font-size:11px;font-weight:700;background:#FEE2E2;color:#991B1B;border:1px solid #FECACA;">MAINTENANCE</span>
                @endif
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;">
            @foreach ([
                ['Laravel', $info['app']['laravel']],
                ['PHP',     $info['app']['php']],
                ['URL',     $info['app']['url']],
                ['Locale',  $info['app']['locale']],
                ['Timezone',$info['app']['timezone']],
                ['DB',      $info['database']['driver'] . ($info['database']['size'] ? ' · ' . $info['database']['size'] : '')],
                ['Queue',   $info['queue']['driver'] . ($info['queue']['sync'] ? ' (sync)' : '')],
                ['Cache',   $info['cache']['driver']],
            ] as [$label, $value])
                <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:12px;padding:12px 14px;">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.10em;color:#94A3B8;">{{ $label }}</div>
                    <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:14px;font-weight:700;color:#0F172A;margin-top:3px;word-break:break-word;">{{ $value }}</div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ============================================================
         MAINTENANCE MODE  (AJAX — pas de rechargement de page)
       ============================================================ --}}
    <section x-data="maintenanceToggle({{ $info['maintenance']['down'] ? 'true' : 'false' }})"
             :style="`background:${isDown ? 'linear-gradient(135deg,#FEF2F2,#FEE2E2)' : 'white'};border-radius:16px;border:1px solid ${isDown ? '#FECACA' : '#E2E8F0'};padding:22px;margin-bottom:18px;box-shadow:0 1px 2px rgba(15,23,42,0.04);transition:background .25s ease, border-color .25s ease;`">
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
            <div :style="`width:54px;height:54px;border-radius:14px;background:linear-gradient(135deg,${isDown ? '#F43F5E,#BE123C' : '#F59E0B,#EA580C'});display:flex;align-items:center;justify-content:center;color:white;flex-shrink:0;transition:background .25s ease;`">
                <svg style="width:26px;height:26px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div style="flex:1;min-width:240px;">
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:700;color:#0F172A;">Mode maintenance</div>
                <div style="font-size:13px;color:#475569;margin-top:4px;line-height:1.5;">
                    <template x-if="isDown">
                        <span>Le site est <strong style="color:#BE123C;">actuellement fermé</strong> aux visiteurs (réponse 503). L'accès admin reste actif.</span>
                    </template>
                    <template x-if="!isDown">
                        <span>Active ce mode pour bloquer l'accès au site pendant une opération critique (déploiement, MEP afrikard, etc.).</span>
                    </template>
                </div>
                {{-- Toast inline --}}
                <div x-show="toast.message" x-transition x-cloak
                     :style="`margin-top:10px;padding:8px 12px;border-radius:9px;font-size:12px;font-weight:600;
                              background:${toast.type === 'error' ? '#FEF2F2' : '#ECFDF5'};
                              color:${toast.type === 'error' ? '#991B1B' : '#047857'};
                              border:1px solid ${toast.type === 'error' ? '#FECACA' : '#A7F3D0'};
                              display:inline-flex;align-items:center;gap:8px;`">
                    <span x-text="toast.message"></span>
                </div>
            </div>

            <button type="button" @click="confirmOpen = true" :disabled="loading"
                    :style="`display:inline-flex;align-items:center;gap:8px;padding:11px 22px;border:0;border-radius:11px;font-size:13px;font-weight:700;color:white;
                             white-space:nowrap;
                             cursor:${loading ? 'not-allowed' : 'pointer'};
                             opacity:${loading ? 0.65 : 1};
                             background:${isDown ? 'linear-gradient(135deg,#10B981,#059669)' : 'linear-gradient(135deg,#F43F5E,#BE123C)'};
                             box-shadow:0 10px 24px -10px ${isDown ? 'rgba(16,185,129,0.5)' : 'rgba(244,63,94,0.5)'};
                             transition:background .25s ease, opacity .15s ease;`">
                <template x-if="loading">
                    <svg style="width:14px;height:14px;" class="ka-spin" fill="none" viewBox="0 0 24 24"><circle style="opacity:.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path style="opacity:.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                </template>
                <template x-if="!loading && isDown">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </template>
                <template x-if="!loading && !isDown">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </template>
                <span x-text="loading ? 'Patientez…' : (isDown ? 'Remettre en ligne' : 'Activer le mode maintenance')"></span>
            </button>
        </div>

        {{-- ============================================================
             Modal de confirmation — style carte cadeau
           ============================================================ --}}
        <div x-show="confirmOpen" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @keydown.escape.window="confirmOpen = false"
             style="position:fixed;inset:0;z-index:1000;display:flex;align-items:center;justify-content:center;padding:24px 16px;
                    background:rgba(2,6,23,0.65);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);"
             @click.self="confirmOpen = false">

            <div x-show="confirmOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-90"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 style="position:relative;width:100%;max-width:440px;background:white;border-radius:24px;overflow:hidden;
                        box-shadow:0 40px 80px -15px rgba(2,6,23,0.55), 0 0 0 1px rgba(15,23,42,0.06);
                        font-family:'Inter','Figtree',sans-serif;">

                {{-- Carte cadeau (header dégradé) --}}
                <div :style="`position:relative;padding:28px 24px 24px;color:white;overflow:hidden;
                              background:${isDown
                                  ? 'radial-gradient(circle at 80% 0%, rgba(255,255,255,0.18) 0%, transparent 45%), linear-gradient(135deg,#10B981 0%,#059669 100%)'
                                  : 'radial-gradient(circle at 80% 0%, rgba(255,255,255,0.18) 0%, transparent 45%), linear-gradient(135deg,#F43F5E 0%,#BE123C 100%)'};`">

                    {{-- Pattern points --}}
                    <div style="position:absolute;inset:0;background-image:radial-gradient(circle at 1px 1px, rgba(255,255,255,0.18) 1.2px, transparent 0);background-size:16px 16px;opacity:.55;pointer-events:none;"></div>

                    {{-- Halo --}}
                    <div style="position:absolute;top:-40px;right:-40px;width:140px;height:140px;border-radius:50%;background:radial-gradient(circle, rgba(255,255,255,0.30) 0%, transparent 70%);filter:blur(20px);"></div>

                    <div style="position:relative;display:flex;align-items:center;gap:14px;">
                        {{-- Icone dans une "puce" gold --}}
                        <div style="width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#FCD34D,#F59E0B);display:flex;align-items:center;justify-content:center;color:#78350F;flex-shrink:0;box-shadow:inset 0 1px 0 rgba(255,255,255,0.4),0 8px 16px -4px rgba(0,0,0,0.25);">
                            <template x-if="!isDown">
                                <svg style="width:24px;height:24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </template>
                            <template x-if="isDown">
                                <svg style="width:24px;height:24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.18em;opacity:0.85;">
                                <template x-if="!isDown">
                                    <span>Confirmation requise</span>
                                </template>
                                <template x-if="isDown">
                                    <span>Réactivation du site</span>
                                </template>
                            </div>
                            <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:700;line-height:1.15;letter-spacing:-0.01em;margin-top:2px;">
                                <template x-if="!isDown">
                                    <span>Couper l'accès public ?</span>
                                </template>
                                <template x-if="isDown">
                                    <span>Remettre en ligne ?</span>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Footer carte (façon numéro) --}}
                    <div style="position:relative;margin-top:22px;display:flex;align-items:center;justify-content:space-between;font-family:'Space Grotesk','Inter',sans-serif;font-weight:800;letter-spacing:0.10em;">
                        <span style="font-size:10px;opacity:0.7;text-transform:uppercase;">KardAfrica · Admin</span>
                        <span style="font-family:monospace;font-size:11px;opacity:0.6;letter-spacing:0.18em;">**** ****</span>
                    </div>
                </div>

                {{-- Body --}}
                <div style="padding:22px 24px 24px;">
                    <p style="font-size:14px;color:#475569;line-height:1.65;margin:0 0 6px;">
                        <template x-if="!isDown">
                            <span>Tous les visiteurs verront immédiatement la <strong style="color:#0F172A;">page de pause technique</strong> à la place du site. Tes commandes en cours et données restent intactes.</span>
                        </template>
                        <template x-if="isDown">
                            <span>Le site sera de nouveau accessible à tous les visiteurs <strong style="color:#0F172A;">dans la seconde</strong>. Toutes les fonctionnalités publiques redeviennent actives.</span>
                        </template>
                    </p>

                    <div style="margin-top:14px;padding:11px 14px;border-radius:11px;background:#F8FAFC;border:1px solid #E2E8F0;display:flex;align-items:flex-start;gap:10px;">
                        <svg style="width:16px;height:16px;color:#44A08D;flex-shrink:0;margin-top:1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <div style="font-size:12px;color:#475569;line-height:1.5;">
                            Tu gardes ton accès admin complet pendant la maintenance — tu peux désactiver à tout moment depuis cet écran.
                        </div>
                    </div>

                    {{-- Buttons --}}
                    <div style="margin-top:20px;display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;">
                        <button type="button" @click="confirmOpen = false"
                                style="padding:11px 18px;border:1px solid #E2E8F0;border-radius:11px;background:white;color:#475569;font-size:13px;font-weight:700;cursor:pointer;transition:all .15s;"
                                onmouseover="this.style.background='#F1F5F9';this.style.borderColor='#CBD5E1';"
                                onmouseout="this.style.background='white';this.style.borderColor='#E2E8F0';">
                            Annuler
                        </button>
                        <button type="button" @click="confirmOpen = false; toggle();" :disabled="loading"
                                :style="`display:inline-flex;align-items:center;gap:8px;padding:11px 20px;border:0;border-radius:11px;font-size:13px;font-weight:700;color:white;cursor:pointer;
                                         background:${isDown ? 'linear-gradient(135deg,#10B981,#059669)' : 'linear-gradient(135deg,#F43F5E,#BE123C)'};
                                         box-shadow:0 10px 24px -10px ${isDown ? 'rgba(16,185,129,0.5)' : 'rgba(244,63,94,0.5)'};
                                         transition:transform .15s ease;`"
                                onmouseover="this.style.transform='translateY(-1px)';"
                                onmouseout="this.style.transform='translateY(0)';">
                            <template x-if="!isDown">
                                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            </template>
                            <template x-if="isDown">
                                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
                            <span x-text="isDown ? 'Oui, remettre en ligne' : 'Oui, couper l\'accès'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================
         API HEALTH (afrikard + futursowax)
       ============================================================ --}}
    <section style="background:white;border-radius:16px;border:1px solid #E2E8F0;padding:22px;margin-bottom:18px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:18px;flex-wrap:wrap;">
            <div>
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.10em;color:#44A08D;">Services externes</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:700;color:#0F172A;margin-top:4px;">État des connexions</div>
            </div>
            <button type="button" @click="testApi()" :disabled="loading"
                    :style="{
                        display:        'inline-flex',
                        alignItems:     'center',
                        justifyContent: 'center',
                        gap:            '8px',
                        whiteSpace:     'nowrap',
                        flexShrink:     '0',
                        padding:        '10px 20px',
                        border:         '0',
                        borderRadius:   '11px',
                        fontSize:       '13px',
                        fontWeight:     '700',
                        color:          '#ffffff',
                        background:     'linear-gradient(135deg,#0F172A,#1E293B)',
                        boxShadow:      '0 8px 18px -8px rgba(15,23,42,0.4)',
                        opacity:        loading ? '0.65' : '1',
                        cursor:         loading ? 'not-allowed' : 'pointer',
                        transition:     'transform .15s ease'
                    }">
                <svg style="width:14px;height:14px;flex-shrink:0;" :class="loading ? 'ka-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span x-text="loading ? 'Test en cours…' : 'Tester afrikard'"></span>
            </button>
        </div>

        {{-- afrikard --}}
        <div :style="`background:${health.ok ? 'linear-gradient(135deg,#F0FDF4,#D1FAE5)' : (health === null ? '#F8FAFC' : 'linear-gradient(135deg,#FEF2F2,#FEE2E2)')};
                      border:1px solid ${health.ok ? '#A7F3D0' : (health === null ? '#E2E8F0' : '#FECACA')};
                      border-radius:14px;padding:16px;margin-bottom:10px;
                      display:flex;align-items:center;gap:14px;flex-wrap:wrap;`">
            <div :style="`width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:white;
                          background:${health.ok ? 'linear-gradient(135deg,#10B981,#059669)' : (health === null ? 'linear-gradient(135deg,#94A3B8,#64748B)' : 'linear-gradient(135deg,#F43F5E,#BE123C)')};`">
                <template x-if="loading">
                    <svg style="width:20px;height:20px;" class="ka-spin" fill="none" viewBox="0 0 24 24"><circle style="opacity:.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path style="opacity:.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                </template>
                <template x-if="!loading && health.ok">
                    <svg style="width:22px;height:22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </template>
                <template x-if="!loading && !health.ok">
                    <svg style="width:22px;height:22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </template>
            </div>
            <div style="flex:1;min-width:240px;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span style="font-family:'Space Grotesk','Inter',sans-serif;font-size:15px;font-weight:700;color:#0F172A;">afrikard API</span>
                    <span :style="`display:inline-flex;align-items:center;gap:5px;padding:2px 8px;border-radius:9999px;font-size:10px;font-weight:700;
                                   background:${health.ok ? '#FFFFFF' : '#FFFFFFAA'};
                                   color:${health.ok ? '#047857' : '#BE123C'};
                                   border:1px solid ${health.ok ? '#A7F3D0' : '#FECACA'};`">
                        <span :style="`width:5px;height:5px;border-radius:50%;background:${health.ok ? '#10B981' : '#F43F5E'};`"></span>
                        <span x-text="loading ? 'Vérification…' : (health.ok ? 'Disponible' : 'Indisponible')"></span>
                    </span>
                </div>
                <div style="font-size:12px;color:#475569;margin-top:4px;" x-text="health.message"></div>
                <div style="font-size:11px;color:#94A3B8;font-family:monospace;margin-top:4px;">{{ $info['afrikard']['base_url'] }}</div>
            </div>
            <div style="font-size:11px;color:#64748B;text-align:right;">
                <div>Latence : <span style="font-weight:700;color:#0F172A;font-variant-numeric:tabular-nums;" x-text="(health.latency_ms ?? 0) + ' ms'"></span></div>
                <div x-show="health.checked_at">à <span x-text="(health.checked_at || '').slice(11,19)"></span></div>
            </div>
        </div>

        {{-- futursowax --}}
        <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:14px;padding:16px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <div style="width:42px;height:42px;border-radius:11px;background:linear-gradient(135deg,{{ $info['futursowax']['configured'] ? '#10B981,#059669' : '#94A3B8,#64748B' }});display:flex;align-items:center;justify-content:center;color:white;flex-shrink:0;">
                <svg style="width:22px;height:22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <div style="flex:1;min-width:240px;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span style="font-family:'Space Grotesk','Inter',sans-serif;font-size:15px;font-weight:700;color:#0F172A;">Futursowax (paiement)</span>
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:2px 8px;border-radius:9999px;font-size:10px;font-weight:700;
                                 background:#FFFFFF;
                                 color:{{ $info['futursowax']['configured'] ? '#047857' : '#94A3B8' }};
                                 border:1px solid {{ $info['futursowax']['configured'] ? '#A7F3D0' : '#E2E8F0' }};">
                        <span style="width:5px;height:5px;border-radius:50%;background:{{ $info['futursowax']['configured'] ? '#10B981' : '#94A3B8' }};"></span>
                        {{ $info['futursowax']['configured'] ? 'Configuré' : 'Non configuré' }}
                    </span>
                </div>
                <div style="font-size:11px;color:#94A3B8;font-family:monospace;margin-top:4px;">{{ $info['futursowax']['base_url'] ?: '— aucune URL définie —' }}</div>
            </div>
        </div>
    </section>

    {{-- ============================================================
         ACTIVITY SNAPSHOT
       ============================================================ --}}
    <section style="background:white;border-radius:16px;border:1px solid #E2E8F0;padding:22px;margin-bottom:18px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.10em;color:#44A08D;">Activité</div>
        <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:700;color:#0F172A;margin-top:4px;margin-bottom:18px;">Données live</div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;">
            @foreach ([
                ['Utilisateurs',         $info['counts']['users'],          '#3B82F6'],
                ['Commandes',            $info['counts']['orders'],         '#0F172A'],
                ['Commandes payées',     $info['counts']['orders_paid'],    '#047857'],
                ['Livraisons en attente',$info['counts']['orders_pending'], '#B45309'],
                ['Produits Daywatch',    $info['counts']['daywatch'],       '#7C3AED'],
                ['Newsletter (actifs)',  $info['counts']['newsletter'],     '#EA580C'],
            ] as [$label, $value, $color])
                <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:12px;padding:14px;">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94A3B8;line-height:1.3;">{{ $label }}</div>
                    <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:24px;font-weight:800;color:{{ $color }};font-variant-numeric:tabular-nums;line-height:1.05;margin-top:6px;">
                        {{ number_format($value, 0, ',', ' ') }}
                    </div>
                </div>
            @endforeach
        </div>

        @if($info['counts']['orders_pending'] > 0)
            <div style="margin-top:14px;padding:12px 16px;background:#FFFBEB;border:1px solid #FDE68A;border-radius:12px;display:flex;align-items:center;gap:10px;">
                <svg style="width:18px;height:18px;color:#B45309;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div style="flex:1;font-size:13px;color:#78350F;">
                    <strong>{{ $info['counts']['orders_pending'] }}</strong> commande(s) payée(s) en attente de livraison.
                </div>
                <a href="{{ route('admin.orders.pending-delivery') }}"
                   style="font-size:12px;font-weight:700;color:#B45309;text-decoration:none;padding:6px 14px;background:white;border:1px solid #FDE68A;border-radius:9px;">
                    Gérer →
                </a>
            </div>
        @endif
    </section>

    {{-- ============================================================
         CACHE MANAGEMENT
       ============================================================ --}}
    <section style="background:white;border-radius:16px;border:1px solid #E2E8F0;padding:22px;margin-bottom:18px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:14px;margin-bottom:18px;">
            <div>
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.10em;color:#44A08D;">Maintenance</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:700;color:#0F172A;margin-top:4px;">Caches & stockage</div>
            </div>
        </div>

        {{-- Sizes --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;margin-bottom:16px;">
            @foreach ([
                ['Cache app',      $info['storage']['cache_dir_size'] ?? '—'],
                ['Cache vues',     $info['storage']['view_dir_size'] ?? '—'],
                ['Sessions',       $info['storage']['session_dir_size'] ?? '—'],
                ['Logs',           $info['storage']['logs_size'] ?? '—'],
            ] as [$label, $value])
                <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:11px;padding:12px;">
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94A3B8;">{{ $label }}</div>
                    <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:16px;font-weight:700;color:#0F172A;font-variant-numeric:tabular-nums;margin-top:4px;">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        {{-- Buttons --}}
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            @foreach ([
                ['app',    'Cache applicatif', '#44A08D'],
                ['config', 'Configuration',    '#3B82F6'],
                ['route',  'Routes',           '#7C3AED'],
                ['view',   'Vues compilées',   '#EA580C'],
                ['all',    'Tout vider',       '#0F172A'],
            ] as [$kind, $label, $color])
                <form method="POST" action="{{ route('admin.settings.cache') }}" data-no-loader>
                    @csrf
                    <input type="hidden" name="kind" value="{{ $kind }}">
                    <button type="submit"
                            style="display:inline-flex;align-items:center;gap:6px;padding:9px 14px;border:1px solid #E2E8F0;border-radius:10px;font-size:12px;font-weight:600;color:{{ $color }};background:white;cursor:pointer;transition:all .15s;"
                            onmouseover="this.style.background='{{ $color }}';this.style.color='white';this.style.borderColor='{{ $color }}';"
                            onmouseout="this.style.background='white';this.style.color='{{ $color }}';this.style.borderColor='#E2E8F0';">
                        <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22"/></svg>
                        {{ $label }}
                    </button>
                </form>
            @endforeach
        </div>
    </section>

    {{-- ============================================================
         TAUX DE CHANGE FCFA — éditables en BDD
       ============================================================ --}}
    <section style="background:white;border-radius:16px;border:1px solid #E2E8F0;padding:22px;margin-bottom:18px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:14px;margin-bottom:18px;">
            <div>
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.10em;color:#44A08D;">Pricing</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:700;color:#0F172A;margin-top:4px;">Taux de change FCFA</div>
                <div style="font-size:12px;color:#64748B;margin-top:4px;line-height:1.55;">
                    Marge appliquée sur les devises principales. Tout prix FCFA est arrondi au prochain palier (ex&nbsp;: 1&nbsp;016 → 1&nbsp;100 avec un palier de 100). Effet immédiat sur le catalogue et le panier après sauvegarde.
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.currency-rates') }}" data-no-loader>
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:14px;">

                {{-- EUR --}}
                <div>
                    <label style="display:block;font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px;">
                        🇪🇺 EUR → FCFA
                    </label>
                    <div style="position:relative;">
                        <input type="number" step="0.001" min="1" max="5000" name="rate_eur"
                               value="{{ old('rate_eur', $currencyRates['eur']) }}"
                               style="width:100%;padding:10px 60px 10px 12px;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;font-weight:600;color:#0F172A;font-variant-numeric:tabular-nums;"
                               required>
                        <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:11px;font-weight:700;color:#94A3B8;letter-spacing:0.05em;">FCFA</span>
                    </div>
                    <div style="font-size:10px;color:#94A3B8;margin-top:4px;font-variant-numeric:tabular-nums;">Peg officiel&nbsp;: 655.957 · Marge: <span style="color:#44A08D;font-weight:700;" id="margin-eur">—</span></div>
                </div>

                {{-- USD --}}
                <div>
                    <label style="display:block;font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px;">
                        🇺🇸 USD → FCFA
                    </label>
                    <div style="position:relative;">
                        <input type="number" step="0.001" min="1" max="5000" name="rate_usd"
                               value="{{ old('rate_usd', $currencyRates['usd']) }}"
                               style="width:100%;padding:10px 60px 10px 12px;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;font-weight:600;color:#0F172A;font-variant-numeric:tabular-nums;"
                               required>
                        <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:11px;font-weight:700;color:#94A3B8;letter-spacing:0.05em;">FCFA</span>
                    </div>
                    <div style="font-size:10px;color:#94A3B8;margin-top:4px;font-variant-numeric:tabular-nums;">Marché ≈ 620 · Marge: <span style="color:#44A08D;font-weight:700;" id="margin-usd">—</span></div>
                </div>

                {{-- AED --}}
                <div>
                    <label style="display:block;font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px;">
                        🇦🇪 AED → FCFA
                    </label>
                    <div style="position:relative;">
                        <input type="number" step="0.001" min="1" max="5000" name="rate_aed"
                               value="{{ old('rate_aed', $currencyRates['aed']) }}"
                               style="width:100%;padding:10px 60px 10px 12px;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;font-weight:600;color:#0F172A;font-variant-numeric:tabular-nums;"
                               required>
                        <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:11px;font-weight:700;color:#94A3B8;letter-spacing:0.05em;">FCFA</span>
                    </div>
                    <div style="font-size:10px;color:#94A3B8;margin-top:4px;font-variant-numeric:tabular-nums;">Marché ≈ 170 · Marge: <span style="color:#44A08D;font-weight:700;" id="margin-aed">—</span></div>
                </div>

                {{-- Round step --}}
                <div>
                    <label style="display:block;font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px;">
                        🎯 Palier d'arrondi
                    </label>
                    <div style="position:relative;">
                        <input type="number" step="1" min="1" max="10000" name="round_step"
                               value="{{ old('round_step', $currencyRates['round_step']) }}"
                               style="width:100%;padding:10px 60px 10px 12px;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;font-weight:600;color:#0F172A;font-variant-numeric:tabular-nums;"
                               required>
                        <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:11px;font-weight:700;color:#94A3B8;letter-spacing:0.05em;">FCFA</span>
                    </div>
                    <div style="font-size:10px;color:#94A3B8;margin-top:4px;">Ex&nbsp;: 100 → 1 016 devient 1 100</div>
                </div>
            </div>

            <button type="submit"
                    style="display:inline-flex;align-items:center;gap:8px;padding:11px 20px;border:none;border-radius:10px;font-size:13px;font-weight:700;color:white;background:#44A08D;cursor:pointer;box-shadow:0 4px 10px rgba(68,160,141,0.25);transition:all .15s;"
                    onmouseover="this.style.background='#3d9180';"
                    onmouseout="this.style.background='#44A08D';">
                <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Mettre à jour les taux
            </button>
        </form>

        <script>
            // Affiche dynamiquement la marge appliquée par rapport aux taux marché.
            (function() {
                const baseRates = { eur: 655.957, usd: 620, aed: 170 };
                function updateMargin(code) {
                    const input = document.querySelector(`input[name="rate_${code}"]`);
                    const target = document.getElementById(`margin-${code}`);
                    if (!input || !target) return;
                    const fn = () => {
                        const v = parseFloat(input.value);
                        if (!v || !baseRates[code]) { target.textContent = '—'; return; }
                        const pct = ((v / baseRates[code]) - 1) * 100;
                        const sign = pct >= 0 ? '+' : '';
                        target.textContent = `${sign}${pct.toFixed(1)}%`;
                        target.style.color = pct >= 0 ? '#44A08D' : '#DC2626';
                    };
                    input.addEventListener('input', fn);
                    fn();
                }
                ['eur', 'usd', 'aed'].forEach(updateMargin);
            })();
        </script>
    </section>

    {{-- ============================================================
         MAIL TEST
       ============================================================ --}}
    <section style="background:white;border-radius:16px;border:1px solid #E2E8F0;padding:22px;margin-bottom:18px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:14px;margin-bottom:14px;">
            <div>
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.10em;color:#44A08D;">Email</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:700;color:#0F172A;margin-top:4px;">Test SMTP</div>
                <div style="font-size:12px;color:#64748B;margin-top:4px;">Mailer : <span style="font-family:monospace;color:#0F172A;font-weight:600;">{{ $info['mail']['mailer'] }}</span> · Expéditeur : <span style="font-family:monospace;color:#0F172A;font-weight:600;">{{ $info['mail']['from'] }}</span></div>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.settings.test-mail') }}" data-no-loader
              style="display:flex;flex-wrap:wrap;gap:8px;">
            @csrf
            <input type="email" name="to" required placeholder="adresse@exemple.com"
                   value="{{ auth()->user()->email ?? '' }}"
                   style="flex:1;min-width:240px;padding:11px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:11px;font-size:14px;color:#0F172A;outline:none;">
            <button type="submit"
                    style="display:inline-flex;align-items:center;gap:8px;padding:11px 18px;border:0;border-radius:11px;font-size:13px;font-weight:700;color:white;cursor:pointer;background:linear-gradient(135deg,#44A08D,#4ECDC4);box-shadow:0 8px 18px -8px rgba(68,160,141,0.5);">
                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Envoyer un test
            </button>
        </form>
    </section>
</div>

<script>
    function adminSettings() {
        return {
            health:  { ok: null, message: 'Cliquez sur « Tester afrikard » pour vérifier.', latency_ms: 0, checked_at: null },
            loading: false,
            async testApi() {
                this.loading = true;
                try {
                    const res = await fetch(window.__settingsHealthRoute, { headers: { Accept: 'application/json' } });
                    this.health = await res.json();
                } catch (e) {
                    this.health = { ok: false, message: 'Erreur réseau côté navigateur', latency_ms: 0 };
                } finally {
                    this.loading = false;
                }
            }
        };
    }

    function maintenanceToggle(initialDown) {
        return {
            isDown:      !!initialDown,
            loading:     false,
            confirmOpen: false,
            toast:       { type: '', message: '' },
            async toggle() {
                if (this.loading) return;
                this.loading = true;
                this.toast   = { type: '', message: '' };
                try {
                    const res = await fetch(window.__settingsMaintenanceRoute, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': window.__csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({}),
                    });
                    // Si on reçoit du HTML (login redirect) on tombe ici proprement
                    const ct = res.headers.get('content-type') || '';
                    if (!ct.includes('application/json')) {
                        throw new Error('Session expirée. Reconnecte-toi.');
                    }
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        throw new Error(data.message || 'Erreur inconnue');
                    }
                    this.isDown = data.is_down;
                    this.toast  = { type: 'success', message: data.message };
                    setTimeout(() => { this.toast = { type: '', message: '' }; }, 5000);
                } catch (e) {
                    this.toast = { type: 'error', message: 'Échec : ' + e.message };
                } finally {
                    this.loading = false;
                }
            }
        };
    }
</script>
@endsection
