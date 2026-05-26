@extends('admin.layouts.admin')

@section('title', 'Carte : ' . $card->name)
@section('page-title', 'Modération · ' . $card->name)

@php
    $isActive   = $card->is_active;
    $isRejected = !$isActive && $card->rejection_reason;
    $isPending  = !$isActive && !$card->rejection_reason;
@endphp

@section('content')
<div style="padding:24px;font-family:'Inter','Figtree',sans-serif;max-width:1180px;margin:0 auto;" x-data="merchantCardActionModal()">

    {{-- ============ CUSTOM MODAL ============ --}}
    <div x-show="open" x-cloak @keydown.escape.window="close()"
         style="position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;padding:20px;">
        <div x-show="open" x-transition.opacity @click="close()"
             style="position:absolute;inset:0;background:rgba(15,23,42,0.55);backdrop-filter:blur(4px);"></div>

        <div x-show="open"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             role="dialog" aria-modal="true"
             style="position:relative;background:white;border-radius:18px;max-width:460px;width:100%;box-shadow:0 25px 50px -12px rgba(15,23,42,0.45);overflow:hidden;">

            <div :style="kind === 'approve' ? 'background:linear-gradient(135deg,#ECFDF5,#D1FAE5);' : 'background:linear-gradient(135deg,#FEE2E2,#FECACA);'"
                 style="padding:22px 24px 18px;display:flex;align-items:flex-start;gap:14px;">
                <div :style="kind === 'approve' ? 'background:linear-gradient(135deg,#10B981,#059669);' : 'background:linear-gradient(135deg,#DC2626,#B91C1C);'"
                     style="width:46px;height:46px;border-radius:14px;display:flex;align-items:center;justify-content:center;color:white;flex-shrink:0;box-shadow:0 6px 14px -4px rgba(0,0,0,0.20),inset 0 1px 0 rgba(255,255,255,0.25);">
                    <template x-if="kind === 'approve'">
                        <svg style="width:22px;height:22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </template>
                    <template x-if="kind === 'reject'">
                        <svg style="width:22px;height:22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </template>
                    <template x-if="kind === 'unpublish'">
                        <svg style="width:22px;height:22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </template>
                </div>
                <div style="flex:1;min-width:0;">
                    <h3 style="margin:0 0 4px;font-family:'Space Grotesk','Inter',sans-serif;font-size:17px;font-weight:800;color:#0F172A;line-height:1.25;" x-text="title"></h3>
                    <p style="margin:0;font-size:13px;color:#475569;line-height:1.5;" x-text="message"></p>
                </div>
            </div>

            {{-- Reject reason preview --}}
            <div x-show="kind === 'reject' && reasonPreview" style="padding:14px 24px;background:#FEF2F2;border-top:1px solid #FECACA;border-bottom:1px solid #FECACA;">
                <div style="font-size:10px;font-weight:800;color:#B91C1C;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:5px;">Motif qui sera envoyé au marchand</div>
                <div style="font-size:13px;color:#7F1D1D;line-height:1.5;white-space:pre-line;" x-text="reasonPreview"></div>
            </div>

            <div style="padding:16px 24px 20px;display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" @click="close()"
                        style="padding:11px 20px;background:#F1F5F9;color:#334155;border:0;border-radius:11px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;">
                    Annuler
                </button>
                <button type="button" @click="confirmAction()"
                        :style="kind === 'approve' ? 'background:linear-gradient(135deg,#10B981,#059669);box-shadow:0 8px 18px -6px rgba(16,185,129,0.55),inset 0 1px 0 rgba(255,255,255,0.25);' : 'background:linear-gradient(135deg,#DC2626,#B91C1C);box-shadow:0 8px 18px -6px rgba(220,38,38,0.55),inset 0 1px 0 rgba(255,255,255,0.25);'"
                        style="padding:11px 20px;color:white;border:0;border-radius:11px;font-size:13px;font-weight:800;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:6px;">
                    <span x-text="confirmLabel"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ============ Crumb + status ============ --}}
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px;">
        <a href="{{ route('admin.merchant-cards.index') }}" style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:700;color:#64748B;text-decoration:none;">
            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Retour aux cartes
        </a>
        @if($isActive)
            <span style="background:#10B981;color:white;font-size:11px;font-weight:800;padding:5px 12px;border-radius:9999px;letter-spacing:.06em;text-transform:uppercase;">✓ Publiée — visible sur /gabon</span>
            @if($card->activated_at)
                <span style="font-size:11px;color:#64748B;">Depuis {{ $card->activated_at->translatedFormat('d M Y \à H:i') }}</span>
            @endif
        @elseif($isRejected)
            <span style="background:#DC2626;color:white;font-size:11px;font-weight:800;padding:5px 12px;border-radius:9999px;letter-spacing:.06em;text-transform:uppercase;">✗ Refusée</span>
        @else
            <span style="background:#F59E0B;color:white;font-size:11px;font-weight:800;padding:5px 12px;border-radius:9999px;letter-spacing:.06em;text-transform:uppercase;">⏳ En attente de modération</span>
        @endif
    </div>

    <div style="display:grid;grid-template-columns:1fr;gap:20px;@media (min-width:960px){grid-template-columns:480px 1fr;}">
        <div style="display:grid;grid-template-columns:1fr;gap:20px;">

        {{-- ============ COL GAUCHE : VISUAL ============ --}}
        <div>
            @if($card->visual_url)
                <div style="position:relative;aspect-ratio:1.55;border-radius:16px;overflow:hidden;background:#0F172A;box-shadow:0 14px 30px -10px rgba(15,23,42,0.30);">
                    <img src="{{ asset($card->visual_url) }}" alt="{{ $card->name }}" style="width:100%;height:100%;object-fit:cover;">
                </div>
                <p style="margin:8px 4px 0;font-size:11px;color:#94A3B8;">Visuel uploadé par le marchand.</p>
            @else
                <div style="aspect-ratio:1.55;border-radius:16px;background:linear-gradient(135deg,#FEF3C7,#FDE68A);display:flex;align-items:center;justify-content:center;color:#B45309;border:2px dashed #F59E0B;">
                    <div style="text-align:center;">
                        <svg style="width:32px;height:32px;margin:0 auto 6px;display:block;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <strong style="font-size:13px;font-weight:800;">Aucun visuel</strong>
                        <p style="margin:2px 0 0;font-size:11px;">Demande au marchand d'en uploader un avant approbation.</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- ============ Stats ============ --}}
        <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:18px;">
            <h3 style="margin:0 0 12px;font-family:'Space Grotesk','Inter',sans-serif;font-size:12px;font-weight:800;color:#64748B;text-transform:uppercase;letter-spacing:.06em;">Ventes</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div>
                    <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:24px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;">{{ $card->purchases->count() }}</div>
                    <div style="font-size:11px;color:#64748B;">Achats</div>
                </div>
                <div>
                    <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:24px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;">{{ number_format($card->total_revenue ?? 0, 0, ',', ' ') }}</div>
                    <div style="font-size:11px;color:#64748B;">Revenus FCFA</div>
                </div>
            </div>
        </div>
        </div>

        {{-- ============ COL DROITE : ACTIONS + INFO ============ --}}
        <div>
            {{-- Approve/Reject actions --}}
            @if($isPending || $isRejected)
                <div style="background:linear-gradient(135deg,#0F172A,#0F4F44);color:white;border-radius:16px;padding:24px;margin-bottom:16px;position:relative;overflow:hidden;">
                    <div style="position:absolute;top:-40%;right:-10%;width:280px;height:280px;background:radial-gradient(circle,rgba(94,234,212,0.20),transparent 60%);pointer-events:none;"></div>
                    <h3 style="margin:0 0 4px;font-family:'Space Grotesk','Inter',sans-serif;font-size:16px;font-weight:800;position:relative;">Décision de modération</h3>
                    <p style="margin:0 0 18px;font-size:13px;color:rgba(255,255,255,0.7);position:relative;">Approuver = carte publiée sur /gabon. Refuser = le marchand voit ton motif.</p>

                    {{-- Approve --}}
                    <form action="{{ route('admin.merchant-cards.approve', $card) }}" method="POST" id="form-approve" style="position:relative;margin-bottom:10px;">
                        @csrf @method('PATCH')
                        <button type="button" @click="askApprove(@js($card->name))"
                                style="width:100%;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:13px;background:linear-gradient(135deg,#10B981,#059669);color:white;border:0;border-radius:12px;font-family:'Space Grotesk','Inter',sans-serif;font-size:14px;font-weight:800;cursor:pointer;box-shadow:0 10px 22px -8px rgba(16,185,129,0.6),inset 0 1px 0 rgba(255,255,255,0.25);">
                            <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Approuver et publier
                        </button>
                    </form>

                    {{-- Reject --}}
                    <form action="{{ route('admin.merchant-cards.reject', $card) }}" method="POST" id="form-reject" style="position:relative;">
                        @csrf @method('PATCH')
                        <label style="display:block;font-size:11px;font-weight:700;color:rgba(255,255,255,0.6);margin-bottom:4px;text-transform:uppercase;letter-spacing:.06em;">Motif du refus</label>
                        <textarea name="rejection_reason" id="reject-reason" rows="3" required minlength="5" maxlength="500"
                                  placeholder="Ex : Visuel de mauvaise qualité, conditions trop restrictives, dénominations trop élevées…"
                                  style="width:100%;padding:10px 12px;background:rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.18);border-radius:10px;color:white;font-size:13px;font-family:inherit;outline:none;resize:vertical;margin-bottom:10px;">{{ $card->rejection_reason }}</textarea>
                        <button type="button" @click="askReject(@js($card->name))"
                                style="width:100%;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:13px;background:rgba(220,38,38,0.15);color:#FCA5A5;border:1px solid rgba(220,38,38,0.30);border-radius:12px;font-family:'Space Grotesk','Inter',sans-serif;font-size:14px;font-weight:800;cursor:pointer;">
                            <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            Refuser avec ce motif
                        </button>
                    </form>
                </div>
            @else
                {{-- Active card : show "Unpublish" + public URL --}}
                <div style="background:linear-gradient(135deg,#ECFDF5,#D1FAE5);border:1px solid #10B981;border-radius:16px;padding:22px;margin-bottom:16px;">
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                        <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#10B981,#059669);display:flex;align-items:center;justify-content:center;color:white;">
                            <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <h3 style="margin:0;font-family:'Space Grotesk','Inter',sans-serif;font-size:16px;font-weight:800;color:#065F46;">Carte publiée</h3>
                            <p style="margin:2px 0 0;font-size:12px;color:#047857;">Visible publiquement sur Kardafrica.</p>
                        </div>
                    </div>
                    <a href="{{ route('gabon.card', $card) }}" target="_blank" style="display:inline-flex;align-items:center;gap:6px;padding:9px 14px;background:white;color:#0F4F44;border:1px solid #BBF7D0;border-radius:10px;font-size:12px;font-weight:700;text-decoration:none;">
                        <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        Voir sur /gabon
                    </a>
                    <form action="{{ route('admin.merchant-cards.reject', $card) }}" method="POST" id="form-unpublish" style="margin-top:10px;">
                        @csrf @method('PATCH')
                        <input type="hidden" name="rejection_reason" value="Dépubliée par l'admin (raison à compléter)">
                        <button type="button" @click="askUnpublish(@js($card->name))"
                                style="font-size:11px;color:#B91C1C;background:none;border:0;cursor:pointer;text-decoration:underline;">
                            Dépublier (re-modération)
                        </button>
                    </form>
                </div>
            @endif

            {{-- ============ Carte info ============ --}}
            <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:20px;margin-bottom:14px;">
                <h3 style="margin:0 0 14px;font-family:'Space Grotesk','Inter',sans-serif;font-size:12px;font-weight:800;color:#64748B;text-transform:uppercase;letter-spacing:.06em;">Détails de la carte</h3>

                <table style="width:100%;font-size:13px;">
                    <tr><td style="padding:6px 0;color:#64748B;width:130px;">Nom</td><td style="padding:6px 0;color:#0F172A;font-weight:600;">{{ $card->name }}</td></tr>
                    @if(isset($categories[$card->category]))
                        <tr><td style="padding:6px 0;color:#64748B;">Catégorie</td><td style="padding:6px 0;color:#0F172A;">{{ $categories[$card->category] }}</td></tr>
                    @endif
                    <tr><td style="padding:6px 0;color:#64748B;">Dénominations</td><td style="padding:6px 0;color:#0F172A;font-variant-numeric:tabular-nums;">
                        @foreach($card->denominations ?? [] as $d)
                            <span style="display:inline-block;background:#F1F5F9;padding:2px 7px;border-radius:5px;font-size:11px;font-weight:700;margin-right:3px;margin-bottom:2px;">{{ number_format($d, 0, ',', ' ') }}</span>
                        @endforeach
                    </td></tr>
                    @if($card->allow_custom_amount)
                        <tr><td style="padding:6px 0;color:#64748B;">Montant libre</td><td style="padding:6px 0;color:#0F172A;">
                            <span style="background:#DBEAFE;color:#1E40AF;padding:2px 8px;border-radius:5px;font-size:11px;font-weight:700;">Autorisé</span>
                            <span style="margin-left:6px;font-size:12px;font-variant-numeric:tabular-nums;color:#64748B;">{{ number_format($card->min_amount ?? 0, 0, ',', ' ') }} – {{ number_format($card->max_amount ?? 0, 0, ',', ' ') }} FCFA</span>
                        </td></tr>
                    @endif
                    <tr><td style="padding:6px 0;color:#64748B;">Validité</td><td style="padding:6px 0;color:#0F172A;">{{ $card->validity_months }} mois</td></tr>
                    <tr><td style="padding:6px 0;color:#64748B;">Créée</td><td style="padding:6px 0;color:#0F172A;">{{ $card->created_at->translatedFormat('d M Y à H:i') }}</td></tr>
                    @if($card->updated_at && $card->updated_at->ne($card->created_at))
                        <tr><td style="padding:6px 0;color:#64748B;">Modifiée</td><td style="padding:6px 0;color:#0F172A;">{{ $card->updated_at->translatedFormat('d M Y à H:i') }}</td></tr>
                    @endif
                </table>

                @if($card->description)
                    <div style="margin-top:14px;padding-top:14px;border-top:1px solid #F1F5F9;">
                        <div style="font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;">Description</div>
                        <p style="margin:0;font-size:13px;line-height:1.6;color:#334155;white-space:pre-line;">{{ $card->description }}</p>
                    </div>
                @endif

                @if($card->terms_conditions)
                    <div style="margin-top:14px;padding-top:14px;border-top:1px solid #F1F5F9;">
                        <div style="font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;">Conditions d'utilisation</div>
                        <p style="margin:0;font-size:13px;line-height:1.6;color:#334155;white-space:pre-line;">{{ $card->terms_conditions }}</p>
                    </div>
                @endif

                @if($card->rejection_reason)
                    <div style="margin-top:14px;padding:12px 14px;background:#FEE2E2;color:#991B1B;border-radius:10px;font-size:12px;line-height:1.5;">
                        <strong style="display:block;margin-bottom:3px;font-weight:800;">Motif du refus en cours</strong>
                        {{ $card->rejection_reason }}
                    </div>
                @endif
            </div>

            {{-- ============ Marchand info ============ --}}
            <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:20px;">
                <h3 style="margin:0 0 14px;font-family:'Space Grotesk','Inter',sans-serif;font-size:12px;font-weight:800;color:#64748B;text-transform:uppercase;letter-spacing:.06em;">Marchand</h3>

                <div style="display:flex;gap:12px;align-items:center;margin-bottom:14px;">
                    <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;display:flex;align-items:center;justify-content:center;font-family:'Space Grotesk','Inter',sans-serif;font-weight:800;font-size:18px;flex-shrink:0;">
                        {{ strtoupper(substr($card->reseller->business_name ?? $card->reseller->name, 0, 1)) }}
                    </div>
                    <div>
                        <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:14px;font-weight:800;color:#0F172A;">{{ $card->reseller->business_name ?? $card->reseller->name }}</div>
                        <div style="font-size:11px;color:#64748B;font-family:ui-monospace,monospace;">{{ $card->reseller->vendor_code }}</div>
                    </div>
                </div>

                <table style="width:100%;font-size:13px;">
                    @if($card->reseller->city)
                        <tr><td style="padding:5px 0;color:#64748B;width:120px;">Ville</td><td style="padding:5px 0;color:#0F172A;">{{ $card->reseller->city }}@if($card->reseller->province), {{ $card->reseller->province }}@endif</td></tr>
                    @endif
                    @if($card->reseller->phone)
                        <tr><td style="padding:5px 0;color:#64748B;">Téléphone</td><td style="padding:5px 0;color:#0F172A;font-variant-numeric:tabular-nums;">{{ $card->reseller->phone }}</td></tr>
                    @endif
                    @if($card->reseller->whatsapp_number)
                        <tr><td style="padding:5px 0;color:#64748B;">WhatsApp</td><td style="padding:5px 0;color:#0F172A;font-variant-numeric:tabular-nums;">{{ $card->reseller->whatsapp_number }}</td></tr>
                    @endif
                    <tr><td style="padding:5px 0;color:#64748B;">KYC</td><td style="padding:5px 0;">
                        @if($card->reseller->kyc_status === 'approved')
                            <span style="background:#D1FAE5;color:#047857;padding:2px 8px;border-radius:5px;font-size:11px;font-weight:700;">✓ Approuvé</span>
                        @elseif($card->reseller->kyc_status === 'rejected')
                            <span style="background:#FEE2E2;color:#B91C1C;padding:2px 8px;border-radius:5px;font-size:11px;font-weight:700;">✗ Refusé</span>
                        @else
                            <span style="background:#FEF3C7;color:#B45309;padding:2px 8px;border-radius:5px;font-size:11px;font-weight:700;">⏳ En attente</span>
                        @endif
                    </td></tr>
                </table>

                <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="{{ route('admin.resellers.show', $card->reseller) }}" style="display:inline-flex;align-items:center;gap:5px;padding:7px 12px;background:#F1F5F9;color:#0F172A;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;">
                        Fiche vendeur
                    </a>
                    @if($card->reseller->slug && $card->reseller->kyc_status === 'approved')
                        <a href="{{ route('gabon.merchant', $card->reseller->slug) }}" target="_blank" style="display:inline-flex;align-items:center;gap:5px;padding:7px 12px;background:#ECFDF5;color:#0F4F44;border:1px solid #BBF7D0;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;">
                            Profil public
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>[x-cloak] { display: none !important; }</style>

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
