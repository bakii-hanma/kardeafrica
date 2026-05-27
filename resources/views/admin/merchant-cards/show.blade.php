@extends('admin.layouts.admin')

@section('title', 'Carte : ' . $card->name)
@section('page-title', 'Modération · ' . $card->name)

@php
    $isActive = $card->is_active;
@endphp

@section('content')
<div class="mcs-wrap" x-data="merchantCardActionModal()">

    {{-- ============ CUSTOM MODAL (teleported to body to escape parent stacking context) ============ --}}
    <template x-teleport="body">
        <div x-show="open" x-cloak class="mc-modal-root" @keydown.escape.window="close()">
            <div class="mc-modal-backdrop" @click="close()"></div>
            <div class="mc-modal-dialog" role="dialog" aria-modal="true" @click.stop>
                <div class="mc-modal-head" :class="kind === 'approve' ? 'mc-modal-head--ok' : 'mc-modal-head--ko'">
                    <div class="mc-modal-ic" :class="kind === 'approve' ? 'mc-modal-ic--ok' : 'mc-modal-ic--ko'">
                        <template x-if="kind === 'approve'">
                            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </template>
                        <template x-if="kind === 'reject'">
                            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </template>
                        <template x-if="kind === 'unpublish'">
                            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </template>
                    </div>
                    <div class="mc-modal-text">
                        <h3 x-text="title"></h3>
                        <p x-text="message"></p>
                    </div>
                </div>

                <div class="mc-modal-reason" x-show="kind === 'reject' && reasonPreview">
                    <div class="mc-modal-reason-label">Motif qui sera envoyé au marchand</div>
                    <div class="mc-modal-reason-body" x-text="reasonPreview"></div>
                </div>

                <div class="mc-modal-actions">
                    <button type="button" @click="close()" class="mc-modal-btn mc-modal-btn--cancel">Annuler</button>
                    <button type="button" @click="confirmAction()" class="mc-modal-btn" :class="kind === 'approve' ? 'mc-modal-btn--ok' : 'mc-modal-btn--ko'">
                        <template x-if="kind === 'approve'">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </template>
                        <span x-text="confirmLabel"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- ============ HERO STATUS BANNER ============ --}}
    <div class="mcs-hero {{ $isActive ? 'mcs-hero--ok' : 'mcs-hero--pending' }}">
        <div class="mcs-hero-glow"></div>
        <a href="{{ route('admin.merchant-cards.index') }}" class="mcs-back">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Retour aux cartes
        </a>

        <div class="mcs-hero-body">
            <div class="mcs-hero-ic">
                @if($isActive)
                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                @else
                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                @endif
            </div>
            <div class="mcs-hero-text">
                <span class="mcs-hero-pill">
                    {{ $isActive ? 'Publiée sur Kardafrica' : 'Brouillon' }}
                </span>
                <h1>{{ $card->name }}</h1>
                <p>
                    @if($isActive)
                        Carte visible publiquement{{ $card->activated_at ? ' depuis '.$card->activated_at->translatedFormat('d M Y') : '' }}. Tu peux la dépublier si nécessaire.
                    @else
                        Carte en brouillon. Active-la pour la rendre visible sur le marketplace.
                    @endif
                </p>
            </div>
            <div style="position:relative;display:flex;gap:8px;flex-wrap:wrap;align-self:flex-start;">
                <a href="{{ route('admin.merchant-cards.edit', $card) }}"
                   style="display:inline-flex;align-items:center;gap:6px;padding:9px 14px;background:rgba(255,255,255,0.15);color:white;border-radius:10px;font-size:12px;font-weight:700;text-decoration:none;backdrop-filter:blur(8px);">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Éditer
                </a>
                <form action="{{ route('admin.merchant-cards.destroy', $card) }}" method="POST"
                      onsubmit="return confirm('Supprimer définitivement cette carte ? Si des achats existent, elle sera juste désactivée.');">
                    @csrf @method('DELETE')
                    <button type="submit"
                            style="display:inline-flex;align-items:center;gap:6px;padding:9px 14px;background:rgba(220,38,38,0.30);color:white;border:0;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;backdrop-filter:blur(8px);">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                        Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ============ STATS STRIP ============ --}}
    <div class="mcs-stats">
        <div class="mcs-stat">
            <div class="mcs-stat-label">Achats</div>
            <div class="mcs-stat-value">{{ $card->purchases->count() }}</div>
        </div>
        <div class="mcs-stat">
            <div class="mcs-stat-label">Revenus</div>
            <div class="mcs-stat-value">{{ number_format($card->total_revenue ?? 0, 0, ',', ' ') }}<span class="mcs-stat-unit">F</span></div>
        </div>
        <div class="mcs-stat">
            <div class="mcs-stat-label">Validité</div>
            <div class="mcs-stat-value">{{ $card->validity_months }}<span class="mcs-stat-unit">mois</span></div>
        </div>
        <div class="mcs-stat">
            <div class="mcs-stat-label">Créée le</div>
            <div class="mcs-stat-value mcs-stat-value--small">{{ $card->created_at->translatedFormat('d M Y') }}</div>
        </div>
    </div>

    {{-- ============ MAIN 2-COL LAYOUT ============ --}}
    <div class="mcs-grid">
        {{-- ============ LEFT : VISUAL + DETAILS ============ --}}
        <div class="mcs-left">

            {{-- Visual --}}
            <div class="mcs-card mcs-card--p0">
                @if($card->visual_url)
                    <div class="mcs-visual">
                        <img src="{{ asset($card->visual_url) }}" alt="{{ $card->name }}">
                        <div class="mcs-visual-overlay">
                            <span class="mcs-visual-bizname">{{ \Illuminate\Support\Str::upper($card->reseller->business_name ?? $card->reseller->name) }}</span>
                            <h2>{{ $card->name }}</h2>
                            <div class="mcs-visual-denoms">
                                @foreach(array_slice($card->denominations ?? [], 0, 5) as $d)
                                    <span>{{ number_format($d, 0, ',', ' ') }} F</span>
                                @endforeach
                                @if(count($card->denominations ?? []) > 5)
                                    <span class="mcs-visual-denoms-more">+{{ count($card->denominations) - 5 }}</span>
                                @endif
                                @if($card->allow_custom_amount)
                                    <span class="mcs-visual-denoms-custom">+ libre</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mcs-visual mcs-visual--empty">
                        <div>
                            <div class="mcs-visual-empty-ic">
                                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                            <strong>Aucun visuel uploadé</strong>
                            <p>Le marchand doit en fournir un avant que tu puisses approuver cette carte.</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Denominations --}}
            <div class="mcs-card">
                <div class="mcs-card-head">
                    <svg class="mcs-card-head-ic" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <h3>Dénominations</h3>
                </div>
                <div class="mcs-denoms">
                    @foreach($card->denominations ?? [] as $d)
                        <span class="mcs-denom">{{ number_format($d, 0, ',', ' ') }} <small>F</small></span>
                    @endforeach
                    @if($card->allow_custom_amount)
                        <span class="mcs-denom mcs-denom--custom">+ Libre <small>{{ number_format($card->min_amount ?? 0, 0, ',', ' ') }}–{{ number_format($card->max_amount ?? 0, 0, ',', ' ') }}</small></span>
                    @endif
                </div>
            </div>

            {{-- Description --}}
            @if($card->description)
                <div class="mcs-card">
                    <div class="mcs-card-head">
                        <svg class="mcs-card-head-ic" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <h3>Description</h3>
                    </div>
                    <p class="mcs-prose">{{ $card->description }}</p>
                </div>
            @endif

            {{-- Terms --}}
            @if($card->terms_conditions)
                <div class="mcs-card">
                    <div class="mcs-card-head">
                        <svg class="mcs-card-head-ic" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <h3>Conditions d'utilisation</h3>
                    </div>
                    <p class="mcs-prose">{{ $card->terms_conditions }}</p>
                </div>
            @endif

        </div>

        {{-- ============ RIGHT : STATUT + MARCHAND ============ --}}
        <div class="mcs-right">

            {{-- ============ STATUT BLOCK (toggle simple, plus de modération) ============ --}}
            @if($isActive)
                <div class="mcs-active">
                    <div class="mcs-active-head">
                        <div class="mcs-active-ic">
                            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <h3>Carte publiée</h3>
                            <p>Visible publiquement sur Kardafrica.</p>
                        </div>
                    </div>
                    <a href="{{ route('gabon.card', $card) }}" target="_blank" class="mcs-active-link">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        Voir sur /gabon
                    </a>
                    <a href="{{ route('admin.merchant-cards.edit', $card) }}" class="mcs-active-link" style="margin-left:8px;">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Modifier
                    </a>
                </div>
            @else
                <div class="mcs-decision">
                    <div class="mcs-decision-glow"></div>
                    <div class="mcs-decision-step">Brouillon</div>
                    <h3 class="mcs-decision-title">Carte non publiée</h3>
                    <p class="mcs-decision-hint">Active la carte pour la rendre visible sur /gabon.</p>

                    <form action="{{ route('admin.merchant-cards.approve', $card) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" class="mcs-btn-approve" onclick="return confirm('Publier cette carte sur Kardafrica ?');">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Publier maintenant
                        </button>
                    </form>

                    <a href="{{ route('admin.merchant-cards.edit', $card) }}" style="display:inline-flex;align-items:center;justify-content:center;gap:6px;width:100%;margin-top:10px;padding:11px;background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.8);border:1px solid rgba(255,255,255,0.15);border-radius:11px;font-size:13px;font-weight:700;text-decoration:none;">
                        Modifier les détails
                    </a>
                </div>
            @endif

            {{-- ============ MARCHAND CARD ============ --}}
            <div class="mcs-merchant">
                <div class="mcs-merchant-banner"></div>
                <div class="mcs-merchant-body">
                    <div class="mcs-merchant-av">
                        @if($card->reseller->logo_url)
                            <img src="{{ asset($card->reseller->logo_url) }}" alt="">
                        @else
                            {{ strtoupper(substr($card->reseller->business_name ?? $card->reseller->name, 0, 1)) }}
                        @endif
                    </div>
                    <div class="mcs-merchant-name">{{ $card->reseller->business_name ?? $card->reseller->name }}</div>
                    <div class="mcs-merchant-code">{{ $card->reseller->vendor_code }}</div>

                    <div class="mcs-merchant-kyc">
                        @if($card->reseller->kyc_status === 'approved')
                            <span class="mcs-kyc mcs-kyc--ok">
                                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                KYC approuvé
                            </span>
                        @elseif($card->reseller->kyc_status === 'rejected')
                            <span class="mcs-kyc mcs-kyc--ko">KYC refusé</span>
                        @else
                            <span class="mcs-kyc mcs-kyc--pending">KYC en attente</span>
                        @endif
                        @if(isset($categories[$card->reseller->business_type ?? '']))
                            <span class="mcs-kyc mcs-kyc--cat">{{ $categories[$card->reseller->business_type] }}</span>
                        @endif
                    </div>

                    <div class="mcs-merchant-rows">
                        @if($card->reseller->city)
                            <div class="mcs-merchant-row">
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span>{{ $card->reseller->city }}@if($card->reseller->province), {{ $card->reseller->province }}@endif</span>
                            </div>
                        @endif
                        @if($card->reseller->phone)
                            <a class="mcs-merchant-row mcs-merchant-row--link" href="tel:{{ $card->reseller->phone }}">
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <span>{{ $card->reseller->phone }}</span>
                            </a>
                        @endif
                        @if($card->reseller->whatsapp_number)
                            <a class="mcs-merchant-row mcs-merchant-row--link" href="https://wa.me/{{ preg_replace('/\D/', '', $card->reseller->whatsapp_number) }}" target="_blank">
                                <svg width="13" height="13" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 018.413 3.488 11.824 11.824 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24z"/></svg>
                                <span>{{ $card->reseller->whatsapp_number }}</span>
                            </a>
                        @endif
                    </div>

                    <div class="mcs-merchant-actions">
                        <a href="{{ route('admin.resellers.show', $card->reseller) }}" class="mcs-merchant-btn mcs-merchant-btn--neutral">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                            Fiche vendeur
                        </a>
                        @if($card->reseller->slug && $card->reseller->kyc_status === 'approved')
                            <a href="{{ route('gabon.merchant', $card->reseller->slug) }}" target="_blank" class="mcs-merchant-btn mcs-merchant-btn--public">
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                Profil public
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ============ Timeline (sticky note bottom) ============ --}}
            <div class="mcs-timeline">
                <div class="mcs-timeline-row">
                    <div class="mcs-timeline-dot mcs-timeline-dot--blue"></div>
                    <div>
                        <div class="mcs-timeline-label">Créée</div>
                        <div class="mcs-timeline-when">{{ $card->created_at->translatedFormat('d M Y · H:i') }}</div>
                    </div>
                </div>
                @if($card->updated_at && $card->updated_at->ne($card->created_at))
                    <div class="mcs-timeline-row">
                        <div class="mcs-timeline-dot mcs-timeline-dot--orange"></div>
                        <div>
                            <div class="mcs-timeline-label">Dernière modification</div>
                            <div class="mcs-timeline-when">{{ $card->updated_at->translatedFormat('d M Y · H:i') }}</div>
                        </div>
                    </div>
                @endif
                @if($card->activated_at)
                    <div class="mcs-timeline-row">
                        <div class="mcs-timeline-dot mcs-timeline-dot--green"></div>
                        <div>
                            <div class="mcs-timeline-label">Publiée</div>
                            <div class="mcs-timeline-when">{{ $card->activated_at->translatedFormat('d M Y · H:i') }}</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }

    /* ============ Show page layout ============ */
    .mcs-wrap {
        padding: 24px;
        font-family: 'Inter','Figtree',sans-serif;
        max-width: 1240px;
        margin: 0 auto;
    }

    /* ----- Hero status banner ----- */
    .mcs-hero {
        position: relative;
        border-radius: 20px;
        padding: 22px 26px 24px;
        margin-bottom: 18px;
        overflow: hidden;
        color: white;
        box-shadow: 0 14px 30px -12px rgba(15,23,42,0.30);
    }
    .mcs-hero--pending {
        background: linear-gradient(135deg, #B45309 0%, #F59E0B 60%, #FBBF24 100%);
    }
    .mcs-hero--ok {
        background: linear-gradient(135deg, #047857 0%, #10B981 60%, #34D399 100%);
    }
    .mcs-hero--ko {
        background: linear-gradient(135deg, #991B1B 0%, #DC2626 60%, #EF4444 100%);
    }
    .mcs-hero-glow {
        position: absolute; top: -40%; right: -10%;
        width: 320px; height: 320px;
        background: radial-gradient(circle, rgba(255,255,255,0.25), transparent 60%);
        pointer-events: none;
    }
    .mcs-back {
        position: relative;
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 11px; font-weight: 700;
        color: rgba(255,255,255,0.75);
        text-decoration: none;
        letter-spacing: .06em;
        text-transform: uppercase;
    }
    .mcs-back:hover { color: white; }
    .mcs-hero-body {
        position: relative;
        display: flex; align-items: flex-start; gap: 16px;
        margin-top: 12px;
    }
    .mcs-hero-ic {
        width: 52px; height: 52px;
        border-radius: 16px;
        background: rgba(255,255,255,0.18);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,0.20);
    }
    .mcs-hero-text { flex: 1; min-width: 0; }
    .mcs-hero-pill {
        display: inline-block;
        font-size: 10px; font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
        padding: 4px 10px;
        background: rgba(255,255,255,0.20);
        border-radius: 9999px;
        margin-bottom: 6px;
        backdrop-filter: blur(8px);
    }
    .mcs-hero-text h1 {
        margin: 0 0 4px;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 24px; font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.15;
    }
    .mcs-hero-text p {
        margin: 0;
        font-size: 13px; line-height: 1.55;
        color: rgba(255,255,255,0.85);
        max-width: 640px;
    }

    /* ----- Stats strip ----- */
    .mcs-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 10px;
        margin-bottom: 18px;
    }
    .mcs-stat {
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        padding: 14px 16px;
    }
    .mcs-stat-label {
        font-size: 10px; font-weight: 800;
        color: #94A3B8;
        text-transform: uppercase; letter-spacing: .08em;
        margin-bottom: 4px;
    }
    .mcs-stat-value {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 22px; font-weight: 800;
        color: #0F172A;
        font-variant-numeric: tabular-nums;
        line-height: 1;
    }
    .mcs-stat-value--small { font-size: 14px; line-height: 1.1; }
    .mcs-stat-unit {
        font-size: 11px; font-weight: 700;
        color: #94A3B8;
        margin-left: 4px;
    }

    /* ----- Main grid ----- */
    .mcs-grid {
        display: grid;
        gap: 18px;
        grid-template-columns: 1fr;
    }
    @media (min-width: 1024px) {
        .mcs-grid { grid-template-columns: minmax(0, 1fr) 360px; }
    }
    .mcs-left, .mcs-right {
        display: flex; flex-direction: column; gap: 14px;
        min-width: 0;
    }

    /* ----- Generic card ----- */
    .mcs-card {
        background: white;
        border-radius: 16px;
        border: 1px solid #E2E8F0;
        padding: 18px 20px;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }
    .mcs-card--p0 { padding: 0; overflow: hidden; }
    .mcs-card--danger { border-color: #FECACA; background: #FEF2F2; }
    .mcs-card-head {
        display: flex; align-items: center; gap: 8px;
        margin-bottom: 12px;
    }
    .mcs-card-head h3 {
        margin: 0;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 13px; font-weight: 800;
        color: #0F172A;
        text-transform: uppercase;
        letter-spacing: .06em;
    }
    .mcs-card-head-ic { color: #44A08D; flex-shrink: 0; }
    .mcs-card-head-ic--danger { color: #DC2626; }
    .mcs-card-danger .mcs-card-head h3 { color: #991B1B; }
    .mcs-prose {
        margin: 0;
        font-size: 13px; line-height: 1.65;
        color: #334155;
        white-space: pre-line;
    }
    .mcs-prose--danger { color: #7F1D1D; }

    /* ----- Visual card with overlay ----- */
    .mcs-visual {
        position: relative;
        aspect-ratio: 1.55;
        background: linear-gradient(135deg,#1E293B,#0F4F44);
    }
    .mcs-visual img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .mcs-visual-overlay {
        position: absolute;
        bottom: 0; left: 0; right: 0;
        padding: 22px 22px 18px;
        background: linear-gradient(0deg, rgba(15,23,42,0.92) 0%, rgba(15,23,42,0.55) 60%, transparent 100%);
        color: white;
    }
    .mcs-visual-bizname {
        display: block;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 10px; font-weight: 800;
        letter-spacing: .14em;
        opacity: .8;
        margin-bottom: 6px;
    }
    .mcs-visual-overlay h2 {
        margin: 0 0 8px;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 20px; font-weight: 800;
        letter-spacing: -0.01em;
        text-shadow: 0 2px 6px rgba(0,0,0,0.5);
        line-height: 1.2;
    }
    .mcs-visual-denoms { display: flex; flex-wrap: wrap; gap: 4px; }
    .mcs-visual-denoms span {
        padding: 3px 9px;
        background: rgba(255,255,255,0.20);
        border-radius: 6px;
        font-size: 11px; font-weight: 800;
        font-variant-numeric: tabular-nums;
        backdrop-filter: blur(6px);
        border: 1px solid rgba(255,255,255,0.10);
    }
    .mcs-visual-denoms-more { background: rgba(255,255,255,0.32) !important; }
    .mcs-visual-denoms-custom { background: rgba(94,234,212,0.30) !important; color: #ECFDF5; }
    .mcs-visual--empty {
        background: linear-gradient(135deg,#FEF3C7,#FDE68A);
        display: flex; align-items: center; justify-content: center;
        color: #B45309;
        text-align: center;
        border: 2px dashed #F59E0B;
        border-radius: inherit;
    }
    .mcs-visual-empty-ic {
        display: inline-flex; align-items: center; justify-content: center;
        width: 56px; height: 56px;
        background: rgba(180,83,9,0.15);
        border-radius: 16px;
        margin-bottom: 10px;
    }
    .mcs-visual--empty strong {
        display: block;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 14px; font-weight: 800;
    }
    .mcs-visual--empty p { margin: 4px 24px 0; font-size: 12px; max-width: 320px; }

    /* ----- Denominations chips ----- */
    .mcs-denoms { display: flex; flex-wrap: wrap; gap: 8px; }
    .mcs-denom {
        display: inline-flex; align-items: baseline; gap: 4px;
        padding: 8px 14px;
        background: linear-gradient(135deg, #F8FAFC, #F1F5F9);
        border: 1px solid #E2E8F0;
        border-radius: 10px;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 15px; font-weight: 800;
        color: #0F172A;
        font-variant-numeric: tabular-nums;
    }
    .mcs-denom small {
        font-size: 10px; font-weight: 700;
        color: #94A3B8;
    }
    .mcs-denom--custom {
        background: linear-gradient(135deg, #DBEAFE, #BFDBFE);
        border-color: #93C5FD;
        color: #1E40AF;
    }
    .mcs-denom--custom small { color: #1E40AF; opacity: .7; }

    /* ----- Decision block (sombre) ----- */
    .mcs-decision {
        position: relative;
        background: linear-gradient(135deg, #0F172A 0%, #1E293B 60%, #0F4F44 100%);
        color: white;
        border-radius: 18px;
        padding: 22px 22px 24px;
        overflow: hidden;
        box-shadow: 0 14px 30px -10px rgba(15,23,42,0.40);
    }
    .mcs-decision-glow {
        position: absolute; top: -40%; right: -10%;
        width: 280px; height: 280px;
        background: radial-gradient(circle, rgba(94,234,212,0.25), transparent 60%);
        pointer-events: none;
    }
    .mcs-decision-step {
        position: relative;
        display: inline-block;
        font-size: 10px; font-weight: 800;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: #5EEAD4;
        margin-bottom: 6px;
    }
    .mcs-decision-title {
        margin: 0 0 4px;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 18px; font-weight: 800;
        position: relative;
    }
    .mcs-decision-hint {
        margin: 0 0 18px;
        font-size: 12px;
        color: rgba(255,255,255,0.65);
        position: relative;
    }
    .mcs-btn-approve {
        position: relative;
        width: 100%;
        display: inline-flex; align-items: center; justify-content: center;
        gap: 8px;
        padding: 14px;
        background: linear-gradient(135deg, #10B981, #059669);
        color: white;
        border: 0;
        border-radius: 12px;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 14px; font-weight: 800;
        cursor: pointer;
        box-shadow: 0 10px 24px -8px rgba(16,185,129,0.65),
                    inset 0 1px 0 rgba(255,255,255,0.25);
        transition: transform .15s, box-shadow .15s;
    }
    .mcs-btn-approve:hover { transform: translateY(-1px); box-shadow: 0 14px 28px -8px rgba(16,185,129,0.75), inset 0 1px 0 rgba(255,255,255,0.30); }

    .mcs-decision-divider {
        position: relative;
        display: flex; align-items: center;
        margin: 14px 0 12px;
    }
    .mcs-decision-divider::before, .mcs-decision-divider::after {
        content: ''; flex: 1; height: 1px;
        background: rgba(255,255,255,0.10);
    }
    .mcs-decision-divider span {
        padding: 0 12px;
        font-size: 10px; font-weight: 800;
        letter-spacing: .14em;
        color: rgba(255,255,255,0.50);
    }
    .mcs-decision-label {
        position: relative;
        display: block;
        font-size: 11px; font-weight: 700;
        color: rgba(255,255,255,0.60);
        text-transform: uppercase; letter-spacing: .06em;
        margin-bottom: 5px;
    }
    .mcs-decision-textarea {
        position: relative;
        width: 100%;
        padding: 10px 12px;
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.15);
        border-radius: 10px;
        color: white;
        font-size: 13px;
        font-family: inherit;
        outline: none;
        resize: vertical;
        margin-bottom: 10px;
        transition: border-color .15s, background .15s;
    }
    .mcs-decision-textarea:focus {
        border-color: rgba(94,234,212,0.50);
        background: rgba(255,255,255,0.12);
    }
    .mcs-decision-textarea::placeholder { color: rgba(255,255,255,0.40); }
    .mcs-btn-reject {
        width: 100%;
        display: inline-flex; align-items: center; justify-content: center;
        gap: 8px;
        padding: 12px;
        background: rgba(220,38,38,0.15);
        color: #FCA5A5;
        border: 1px solid rgba(220,38,38,0.30);
        border-radius: 12px;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 13px; font-weight: 800;
        cursor: pointer;
        transition: background .15s, color .15s;
    }
    .mcs-btn-reject:hover {
        background: rgba(220,38,38,0.25);
        color: #FECACA;
    }

    /* ----- Active card block ----- */
    .mcs-active {
        background: linear-gradient(135deg,#ECFDF5,#D1FAE5);
        border: 1px solid #10B981;
        border-radius: 18px;
        padding: 22px;
    }
    .mcs-active-head { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
    .mcs-active-ic {
        width: 44px; height: 44px;
        border-radius: 14px;
        background: linear-gradient(135deg,#10B981,#059669);
        color: white;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 6px 14px -4px rgba(16,185,129,0.45),
                    inset 0 1px 0 rgba(255,255,255,0.25);
    }
    .mcs-active h3 {
        margin: 0;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 15px; font-weight: 800;
        color: #065F46;
    }
    .mcs-active p { margin: 2px 0 0; font-size: 12px; color: #047857; }
    .mcs-active-link {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 14px;
        background: white;
        color: #0F4F44;
        border: 1px solid #BBF7D0;
        border-radius: 10px;
        font-size: 12px; font-weight: 700;
        text-decoration: none;
        transition: transform .15s;
    }
    .mcs-active-link:hover { transform: translateY(-1px); }
    .mcs-active-unpublish { margin-top: 12px; }
    .mcs-active-unpublish button {
        font-size: 11px; font-weight: 700;
        color: #B91C1C;
        background: none; border: 0;
        cursor: pointer;
        text-decoration: underline;
        padding: 0;
    }
    .mcs-active-unpublish button:hover { color: #7F1D1D; }

    /* ----- Marchand card ----- */
    .mcs-merchant {
        background: white;
        border-radius: 16px;
        border: 1px solid #E2E8F0;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
    }
    .mcs-merchant-banner {
        height: 56px;
        background: linear-gradient(135deg, #0F172A 0%, #1E293B 60%, #0F4F44 100%);
        position: relative;
    }
    .mcs-merchant-banner::after {
        content: '';
        position: absolute; inset: 0;
        background: radial-gradient(circle at 80% 50%, rgba(94,234,212,0.25), transparent 70%);
    }
    .mcs-merchant-body {
        position: relative;
        padding: 0 20px 18px;
        text-align: center;
    }
    .mcs-merchant-av {
        position: relative;
        width: 64px; height: 64px;
        border-radius: 18px;
        background: linear-gradient(135deg, #44A08D, #4ECDC4);
        color: white;
        display: flex; align-items: center; justify-content: center;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-weight: 800; font-size: 24px;
        margin: -32px auto 10px;
        border: 4px solid white;
        box-shadow: 0 6px 14px -3px rgba(15,23,42,0.20);
        overflow: hidden;
    }
    .mcs-merchant-av img { width: 100%; height: 100%; object-fit: cover; }
    .mcs-merchant-name {
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 16px; font-weight: 800; color: #0F172A;
        margin-bottom: 2px;
        line-height: 1.25;
    }
    .mcs-merchant-code {
        font-size: 10px;
        color: #94A3B8;
        font-family: ui-monospace, monospace;
        letter-spacing: .04em;
    }
    .mcs-merchant-kyc {
        display: flex; gap: 5px; flex-wrap: wrap; justify-content: center;
        margin: 10px 0 14px;
    }
    .mcs-kyc {
        display: inline-flex; align-items: center; gap: 3px;
        padding: 3px 9px;
        border-radius: 9999px;
        font-size: 10px; font-weight: 800;
        letter-spacing: .04em;
    }
    .mcs-kyc--ok      { background: #D1FAE5; color: #047857; }
    .mcs-kyc--ko      { background: #FEE2E2; color: #B91C1C; }
    .mcs-kyc--pending { background: #FEF3C7; color: #B45309; }
    .mcs-kyc--cat     { background: #F1F5F9; color: #475569; }

    .mcs-merchant-rows {
        display: flex; flex-direction: column; gap: 4px;
        text-align: left;
        margin-bottom: 14px;
        border-top: 1px solid #F1F5F9;
        padding-top: 12px;
    }
    .mcs-merchant-row {
        display: flex; align-items: center; gap: 8px;
        font-size: 12px;
        color: #334155;
        padding: 6px 0;
    }
    .mcs-merchant-row svg { color: #94A3B8; flex-shrink: 0; }
    .mcs-merchant-row--link {
        text-decoration: none;
        color: #334155;
        transition: color .15s;
        font-variant-numeric: tabular-nums;
    }
    .mcs-merchant-row--link:hover { color: #44A08D; }
    .mcs-merchant-row--link:hover svg { color: #44A08D; }

    .mcs-merchant-actions {
        display: flex; gap: 6px; flex-wrap: wrap; justify-content: center;
    }
    .mcs-merchant-btn {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 8px 13px;
        border-radius: 9px;
        font-size: 11px; font-weight: 800;
        text-decoration: none;
        transition: transform .15s;
    }
    .mcs-merchant-btn:hover { transform: translateY(-1px); }
    .mcs-merchant-btn--neutral { background: #F1F5F9; color: #0F172A; }
    .mcs-merchant-btn--public  { background: #ECFDF5; color: #0F4F44; border: 1px solid #BBF7D0; }

    /* ----- Timeline ----- */
    .mcs-timeline {
        background: white;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        padding: 14px 16px;
    }
    .mcs-timeline-row {
        display: flex; gap: 12px;
        padding: 8px 0;
        border-bottom: 1px solid #F1F5F9;
    }
    .mcs-timeline-row:last-child { border-bottom: 0; }
    .mcs-timeline-dot {
        width: 9px; height: 9px;
        border-radius: 50%;
        margin-top: 5px;
        flex-shrink: 0;
        box-shadow: 0 0 0 3px rgba(0,0,0,0.04);
    }
    .mcs-timeline-dot--blue   { background: #3B82F6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
    .mcs-timeline-dot--orange { background: #F59E0B; box-shadow: 0 0 0 3px rgba(245,158,11,0.18); }
    .mcs-timeline-dot--green  { background: #10B981; box-shadow: 0 0 0 3px rgba(16,185,129,0.18); }
    .mcs-timeline-label {
        font-size: 11px; font-weight: 800;
        color: #0F172A;
        text-transform: uppercase; letter-spacing: .04em;
    }
    .mcs-timeline-when {
        font-size: 11px;
        color: #64748B;
        font-variant-numeric: tabular-nums;
        margin-top: 1px;
    }

    /* ============ Custom modal (teleported to <body>) ============ */
    .mc-modal-root {
        position: fixed; inset: 0;
        z-index: 9999;
        display: flex; align-items: center; justify-content: center;
        padding: 20px;
        animation: mcFadeIn .15s ease-out;
    }
    @keyframes mcFadeIn { from { opacity: 0 } to { opacity: 1 } }
    .mc-modal-backdrop {
        position: absolute; inset: 0;
        background: rgba(15, 23, 42, 0.55);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
    }
    .mc-modal-dialog {
        position: relative;
        background: white;
        border-radius: 18px;
        max-width: 480px;
        width: 100%;
        box-shadow: 0 25px 50px -12px rgba(15,23,42,0.45);
        overflow: hidden;
        font-family: 'Inter','Figtree',sans-serif;
        animation: mcSlideIn .22s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes mcSlideIn {
        from { transform: translateY(8px) scale(.96); opacity: 0; }
        to   { transform: translateY(0)    scale(1);   opacity: 1; }
    }
    .mc-modal-head { padding: 22px 24px 18px; display: flex; align-items: flex-start; gap: 14px; }
    .mc-modal-head--ok { background: linear-gradient(135deg,#ECFDF5,#D1FAE5); }
    .mc-modal-head--ko { background: linear-gradient(135deg,#FEE2E2,#FECACA); }
    .mc-modal-ic {
        width: 46px; height: 46px;
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        color: white;
        flex-shrink: 0;
        box-shadow: 0 6px 14px -4px rgba(0,0,0,0.20), inset 0 1px 0 rgba(255,255,255,0.25);
    }
    .mc-modal-ic--ok { background: linear-gradient(135deg,#10B981,#059669); }
    .mc-modal-ic--ko { background: linear-gradient(135deg,#DC2626,#B91C1C); }
    .mc-modal-text { flex: 1; min-width: 0; }
    .mc-modal-text h3 {
        margin: 0 0 4px;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 17px; font-weight: 800; color: #0F172A; line-height: 1.25;
    }
    .mc-modal-text p { margin: 0; font-size: 13px; line-height: 1.5; color: #475569; }
    .mc-modal-reason {
        padding: 14px 24px;
        background: #FEF2F2;
        border-top: 1px solid #FECACA;
        border-bottom: 1px solid #FECACA;
    }
    .mc-modal-reason-label {
        font-size: 10px; font-weight: 800; color: #B91C1C;
        text-transform: uppercase; letter-spacing: 0.06em;
        margin-bottom: 5px;
    }
    .mc-modal-reason-body {
        font-size: 13px; color: #7F1D1D;
        line-height: 1.5;
        white-space: pre-line;
    }
    .mc-modal-actions { padding: 16px 24px 20px; display: flex; gap: 10px; justify-content: flex-end; }
    .mc-modal-btn {
        padding: 11px 20px;
        border: 0; border-radius: 11px;
        font-size: 13px; font-weight: 700;
        cursor: pointer;
        font-family: inherit;
        display: inline-flex; align-items: center; gap: 6px;
        transition: transform .15s, box-shadow .15s;
    }
    .mc-modal-btn:hover { transform: translateY(-1px); }
    .mc-modal-btn--cancel { background: #F1F5F9; color: #334155; }
    .mc-modal-btn--cancel:hover { background: #E2E8F0; }
    .mc-modal-btn--ok {
        background: linear-gradient(135deg,#10B981,#059669);
        color: white; font-weight: 800;
        box-shadow: 0 8px 18px -6px rgba(16,185,129,0.55), inset 0 1px 0 rgba(255,255,255,0.25);
    }
    .mc-modal-btn--ko {
        background: linear-gradient(135deg,#DC2626,#B91C1C);
        color: white; font-weight: 800;
        box-shadow: 0 8px 18px -6px rgba(220,38,38,0.55), inset 0 1px 0 rgba(255,255,255,0.25);
    }
</style>

<script>
function merchantCardActionModal() {
    return {
        open: false,
        kind: 'approve', // 'approve' | 'reject' | 'unpublish'
        title: '',
        message: '',
        confirmLabel: '',
        reasonPreview: '',
        targetFormId: null,

        askApprove(cardName) {
            this.kind = 'approve';
            this.title = 'Publier « ' + cardName + ' » ?';
            this.message = "La carte deviendra immédiatement visible publiquement sur Kardafrica. Le marchand pourra commencer à vendre.";
            this.confirmLabel = 'Approuver et publier';
            this.reasonPreview = '';
            this.targetFormId = 'form-approve';
            this._show();
        },
        askReject(cardName) {
            const reasonEl = document.getElementById('reject-reason');
            const reason = reasonEl ? reasonEl.value.trim() : '';
            if (!reason || reason.length < 5) {
                if (reasonEl) {
                    reasonEl.focus();
                    reasonEl.style.borderColor = '#FCA5A5';
                    reasonEl.style.background = 'rgba(220,38,38,0.20)';
                }
                return;
            }
            this.kind = 'reject';
            this.title = 'Refuser « ' + cardName + ' » ?';
            this.message = "Le marchand verra ton motif dans son espace vendeur. Il pourra corriger et soumettre à nouveau.";
            this.confirmLabel = 'Confirmer le refus';
            this.reasonPreview = reason;
            this.targetFormId = 'form-reject';
            this._show();
        },
        askUnpublish(cardName) {
            this.kind = 'unpublish';
            this.title = 'Dépublier « ' + cardName + ' » ?';
            this.message = "La carte sera retirée de /gabon et repassera en attente de modération. À utiliser si tu repères un problème après publication.";
            this.confirmLabel = 'Dépublier';
            this.reasonPreview = '';
            this.targetFormId = 'form-unpublish';
            this._show();
        },
        _show() {
            this.open = true;
            document.body.style.overflow = 'hidden';
        },
        close() {
            this.open = false;
            document.body.style.overflow = '';
        },
        confirmAction() {
            const form = document.getElementById(this.targetFormId);
            if (form) form.submit();
            this.close();
        },
    };
}
</script>
@endsection
