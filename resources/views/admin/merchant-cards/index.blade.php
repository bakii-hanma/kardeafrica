@extends('admin.layouts.admin')

@section('title', 'Cartes marchand')
@section('page-title', 'Modération des cartes-cadeau marchand')

@section('content')
<div style="padding:24px;font-family:'Inter','Figtree',sans-serif;" x-data="merchantCardsModal()">

    {{-- ============ CUSTOM MODAL (teleported to body to escape parent stacking context) ============ --}}
    <template x-teleport="body">
        <div x-show="open" x-cloak class="mc-modal-root" @keydown.escape.window="close()">
            {{-- Backdrop --}}
            <div class="mc-modal-backdrop" @click="close()"></div>

            {{-- Dialog --}}
            <div class="mc-modal-dialog" role="dialog" aria-modal="true" @click.stop>
                <div class="mc-modal-head" :class="kind === 'approve' ? 'mc-modal-head--ok' : 'mc-modal-head--ko'">
                    <div class="mc-modal-ic" :class="kind === 'approve' ? 'mc-modal-ic--ok' : 'mc-modal-ic--ko'">
                        <template x-if="kind === 'approve'">
                            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </template>
                        <template x-if="kind !== 'approve'">
                            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </template>
                    </div>
                    <div class="mc-modal-text">
                        <h3 x-text="title"></h3>
                        <p x-text="message"></p>
                    </div>
                </div>

                <div class="mc-modal-card" x-show="cardName">
                    <div class="mc-modal-card-ic">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <div class="mc-modal-card-name" x-text="cardName"></div>
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

    {{-- ============ STATS ============ --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:18px;">
        {{-- Total --}}
        <a href="{{ route('admin.merchant-cards.index') }}" style="text-decoration:none;background:white;border-radius:14px;border:1px solid {{ $status === '' ? '#44A08D' : '#E2E8F0' }};padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,0.04);transition:transform .15s;">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#F8FAFC,#F1F5F9);border:1px solid #E2E8F0;display:flex;align-items:center;justify-content:center;color:#475569;flex-shrink:0;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <div>
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Total</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:2px;">{{ $stats['total'] }}</div>
            </div>
        </a>

        {{-- Pending (en attente) — highlighted --}}
        <a href="{{ route('admin.merchant-cards.index', ['status' => 'pending']) }}" style="text-decoration:none;background:{{ $status === 'pending' ? 'linear-gradient(135deg,#FEF3C7,#FDE68A)' : 'white' }};border-radius:14px;border:1px solid {{ $status === 'pending' ? '#F59E0B' : '#E2E8F0' }};padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,0.04);transition:transform .15s;">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#FEF3C7,#FDE68A);display:flex;align-items:center;justify-content:center;color:#B45309;flex-shrink:0;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#B45309;">À modérer</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#B45309;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:2px;">{{ $stats['pending'] }}</div>
            </div>
            @if($stats['pending'] > 0 && $status !== 'pending')
                <span style="margin-left:auto;background:#F59E0B;color:white;font-size:10px;font-weight:800;padding:3px 8px;border-radius:9999px;animation:pulse 2s ease-in-out infinite;">!</span>
            @endif
        </a>

        {{-- Active --}}
        <a href="{{ route('admin.merchant-cards.index', ['status' => 'active']) }}" style="text-decoration:none;background:white;border-radius:14px;border:1px solid {{ $status === 'active' ? '#10B981' : '#E2E8F0' }};padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,0.04);transition:transform .15s;">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#D1FAE5,#A7F3D0);display:flex;align-items:center;justify-content:center;color:#047857;flex-shrink:0;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Publiées</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#047857;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:2px;">{{ $stats['active'] }}</div>
            </div>
        </a>

        {{-- Rejected --}}
        <a href="{{ route('admin.merchant-cards.index', ['status' => 'rejected']) }}" style="text-decoration:none;background:white;border-radius:14px;border:1px solid {{ $status === 'rejected' ? '#DC2626' : '#E2E8F0' }};padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,0.04);transition:transform .15s;">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#FEE2E2,#FECACA);display:flex;align-items:center;justify-content:center;color:#B91C1C;flex-shrink:0;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <div>
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Refusées</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#B91C1C;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:2px;">{{ $stats['rejected'] }}</div>
            </div>
        </a>
    </div>

    {{-- ============ TOOLBAR ============ --}}
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-bottom:18px;">
        <a href="{{ route('admin.merchant-cards.create') }}"
           style="display:inline-flex;align-items:center;gap:8px;padding:12px 18px;background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;border-radius:12px;font-size:14px;font-weight:700;text-decoration:none;box-shadow:0 10px 24px -10px rgba(68,160,141,0.5);order:2;">
            <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Nouvelle carte
        </a>

        <form method="GET" action="{{ route('admin.merchant-cards.index') }}"
              style="flex:1 1 auto;min-width:280px;background:white;border-radius:14px;padding:10px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
            <div style="position:relative;flex:1 1 240px;min-width:200px;">
                <svg style="position:absolute;left:14px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#94A3B8;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ $search }}" placeholder="Nom de carte, marchand, code vendeur (KA-V-…)"
                       style="width:100%;padding:10px 14px 10px 40px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;outline:none;">
            </div>
            @if($status)
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <button type="submit" style="padding:10px 18px;background:#44A08D;color:white;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;">Filtrer</button>
            @if($search || $status)
                <a href="{{ route('admin.merchant-cards.index') }}" style="padding:10px 14px;color:#64748B;font-size:12px;font-weight:600;text-decoration:none;background:#F1F5F9;border-radius:10px;display:inline-flex;align-items:center;">✕ Reset</a>
            @endif
        </form>
    </div>

    {{-- ============ GRID ============ --}}
    @if($cards->count() > 0)
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">
            @foreach($cards as $card)
                @php
                    $isActive   = $card->is_active;
                    $isRejected = !$isActive && $card->rejection_reason;
                    $isPending  = !$isActive && !$card->rejection_reason;
                    if ($isActive)        { $badgeBg = '#10B981'; $badgeText = 'PUBLIÉE'; }
                    elseif ($isRejected)  { $badgeBg = '#DC2626'; $badgeText = 'REFUSÉE'; }
                    else                  { $badgeBg = '#F59E0B'; $badgeText = 'EN ATTENTE'; }
                @endphp
                <a href="{{ route('admin.merchant-cards.show', $card) }}"
                   style="background:white;border-radius:14px;border:1px solid #E2E8F0;overflow:hidden;text-decoration:none;color:inherit;box-shadow:0 2px 8px rgba(15,23,42,0.05);transition:transform .15s,box-shadow .15s;display:block;">

                    {{-- Visual preview --}}
                    <div style="position:relative;aspect-ratio:1.55;background:linear-gradient(135deg,#1E293B,#0F4F44);">
                        @if($card->visual_url)
                            <img src="{{ asset($card->visual_url) }}" alt="" style="width:100%;height:100%;object-fit:cover;">
                        @else
                            <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.5);font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;">Aucun visuel</div>
                        @endif
                        <div style="position:absolute;top:10px;left:10px;background:{{ $badgeBg }};color:white;font-size:10px;font-weight:800;padding:4px 10px;border-radius:9999px;letter-spacing:0.06em;backdrop-filter:blur(4px);">
                            {{ $badgeText }}
                        </div>
                    </div>

                    {{-- Body --}}
                    <div style="padding:14px 16px;">
                        @if(isset($categories[$card->category]))
                            <div style="font-size:10px;font-weight:800;color:#44A08D;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:3px;">{{ $categories[$card->category] }}</div>
                        @endif
                        <h3 style="font-family:'Space Grotesk','Inter',sans-serif;font-size:15px;font-weight:800;color:#0F172A;margin:0 0 6px;line-height:1.25;">{{ $card->name }}</h3>

                        <div style="font-size:12px;color:#64748B;margin-bottom:10px;">
                            <span style="display:inline-flex;align-items:center;gap:5px;">
                                <svg style="width:11px;height:11px;color:#94A3B8;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                {{ $card->reseller->business_name ?? $card->reseller->name }}
                            </span>
                            @if($card->reseller->vendor_code)
                                <span style="margin-left:6px;font-family:ui-monospace,monospace;font-size:10px;color:#94A3B8;">{{ $card->reseller->vendor_code }}</span>
                            @endif
                        </div>

                        <div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:10px;">
                            @foreach(array_slice($card->denominations ?? [], 0, 4) as $d)
                                <span style="background:#F1F5F9;color:#475569;font-size:10px;font-weight:700;padding:3px 7px;border-radius:5px;font-variant-numeric:tabular-nums;">{{ number_format($d, 0, ',', ' ') }}</span>
                            @endforeach
                            @if(count($card->denominations ?? []) > 4)
                                <span style="background:#F1F5F9;color:#94A3B8;font-size:10px;font-weight:700;padding:3px 7px;border-radius:5px;">+{{ count($card->denominations) - 4 }}</span>
                            @endif
                        </div>

                        @if($isRejected)
                            <div style="background:#FEE2E2;color:#991B1B;padding:8px 10px;border-radius:8px;font-size:11px;line-height:1.4;">
                                <strong style="display:block;font-weight:800;margin-bottom:2px;">Motif refus</strong>
                                {{ \Illuminate\Support\Str::limit($card->rejection_reason, 100) }}
                            </div>
                        @endif

                        @if($isPending)
                            <div style="display:flex;gap:6px;margin-top:4px;">
                                <form action="{{ route('admin.merchant-cards.approve', $card) }}" method="POST" id="approve-form-{{ $card->id }}" style="flex:1;" @click.stop>
                                    @csrf @method('PATCH')
                                    <button type="button"
                                            @click.prevent.stop="askApprove({{ $card->id }}, @js($card->name))"
                                            style="width:100%;display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:8px;background:#10B981;color:white;border:0;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">
                                        <svg style="width:13px;height:13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        Approuver
                                    </button>
                                </form>
                                <span style="display:inline-flex;align-items:center;justify-content:center;padding:8px 14px;background:#F1F5F9;color:#475569;font-size:12px;font-weight:700;border-radius:8px;">Détails →</span>
                            </div>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <div style="margin-top:20px;">{{ $cards->links() }}</div>
    @else
        <div style="background:white;border-radius:14px;padding:60px 24px;text-align:center;border:2px dashed #E2E8F0;">
            <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;border-radius:50%;background:#F1F5F9;color:#94A3B8;margin-bottom:14px;">
                <svg style="width:28px;height:28px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <h3 style="margin:0 0 6px;font-family:'Space Grotesk','Inter',sans-serif;font-weight:800;color:#0F172A;">Aucune carte</h3>
            <p style="margin:0;color:#64748B;font-size:14px;">
                @if($search || $status)
                    Aucun résultat pour ces filtres.
                @else
                    Quand un marchand créera une carte, elle apparaîtra ici pour modération.
                @endif
            </p>
        </div>
    @endif
</div>

<style>
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.5} }
    [x-cloak] { display: none !important; }

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
        max-width: 460px;
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

    .mc-modal-head {
        padding: 22px 24px 18px;
        display: flex; align-items: flex-start; gap: 14px;
    }
    .mc-modal-head--ok { background: linear-gradient(135deg,#ECFDF5,#D1FAE5); }
    .mc-modal-head--ko { background: linear-gradient(135deg,#FEE2E2,#FECACA); }

    .mc-modal-ic {
        width: 46px; height: 46px;
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        color: white;
        flex-shrink: 0;
        box-shadow: 0 6px 14px -4px rgba(0,0,0,0.20),
                    inset 0 1px 0 rgba(255,255,255,0.25);
    }
    .mc-modal-ic--ok { background: linear-gradient(135deg,#10B981,#059669); }
    .mc-modal-ic--ko { background: linear-gradient(135deg,#DC2626,#B91C1C); }

    .mc-modal-text { flex: 1; min-width: 0; }
    .mc-modal-text h3 {
        margin: 0 0 4px;
        font-family: 'Space Grotesk','Inter',sans-serif;
        font-size: 17px; font-weight: 800;
        color: #0F172A;
        line-height: 1.25;
    }
    .mc-modal-text p {
        margin: 0;
        font-size: 13px; line-height: 1.5;
        color: #475569;
    }

    .mc-modal-card {
        padding: 14px 24px;
        background: #F8FAFC;
        border-top: 1px solid #E2E8F0;
        border-bottom: 1px solid #E2E8F0;
        display: flex; align-items: center; gap: 12px;
    }
    .mc-modal-card-ic {
        width: 36px; height: 36px;
        border-radius: 10px;
        background: linear-gradient(135deg,#1E293B,#0F4F44);
        display: flex; align-items: center; justify-content: center;
        color: white;
        flex-shrink: 0;
    }
    .mc-modal-card-name {
        font-size: 13px; font-weight: 700;
        color: #0F172A;
    }

    .mc-modal-actions {
        padding: 16px 24px 20px;
        display: flex; gap: 10px;
        justify-content: flex-end;
    }
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
    .mc-modal-btn--cancel {
        background: #F1F5F9;
        color: #334155;
    }
    .mc-modal-btn--cancel:hover { background: #E2E8F0; }
    .mc-modal-btn--ok {
        background: linear-gradient(135deg,#10B981,#059669);
        color: white;
        font-weight: 800;
        box-shadow: 0 8px 18px -6px rgba(16,185,129,0.55),
                    inset 0 1px 0 rgba(255,255,255,0.25);
    }
    .mc-modal-btn--ko {
        background: linear-gradient(135deg,#DC2626,#B91C1C);
        color: white;
        font-weight: 800;
        box-shadow: 0 8px 18px -6px rgba(220,38,38,0.55),
                    inset 0 1px 0 rgba(255,255,255,0.25);
    }
</style>

<script>
function merchantCardsModal() {
    return {
        open: false,
        kind: 'approve',
        title: '',
        message: '',
        confirmLabel: '',
        cardName: '',
        targetFormId: null,

        askApprove(cardId, cardName) {
            this.kind = 'approve';
            this.title = 'Publier cette carte ?';
            this.message = 'La carte deviendra immédiatement visible publiquement sur Kardafrica.';
            this.confirmLabel = 'Publier';
            this.cardName = cardName;
            this.targetFormId = 'approve-form-' + cardId;
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
