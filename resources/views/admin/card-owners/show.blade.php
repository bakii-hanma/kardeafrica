@extends('admin.layouts.admin')

@section('title', $owner->business_name)
@section('page-title', $owner->business_name)

@section('content')
<div style="padding:24px;font-family:'Inter','Figtree',sans-serif;max-width:1180px;margin:0 auto;">

    @if(session('success'))
        <div style="background:#D1FAE5;color:#065F46;border:1px solid #6EE7B7;padding:12px 14px;border-radius:12px;margin-bottom:14px;font-size:13px;font-weight:600;">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if(session('temp_password'))
        <div x-data="{ copied: false }"
             style="background:linear-gradient(135deg,#FEF3C7,#FDE68A);border:1px solid #F59E0B;border-radius:14px;padding:16px 18px;margin-bottom:14px;">
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <div style="flex:1;min-width:240px;">
                    <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:13px;font-weight:800;color:#92400E;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">🔑 Identifiants à transmettre au commerçant</div>
                    <div style="font-size:12px;color:#78350F;">Ces infos ne seront plus affichées après cette page. <strong>Note-les ou envoie-les maintenant.</strong></div>
                </div>
            </div>
            <div style="margin-top:12px;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;">
                <div style="background:white;border:1px solid #FCD34D;border-radius:10px;padding:10px 12px;">
                    <div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:700;color:#92400E;">Email</div>
                    <div style="font-family:ui-monospace,monospace;font-size:14px;font-weight:700;color:#78350F;margin-top:2px;word-break:break-all;">{{ $owner->email }}</div>
                </div>
                <div style="background:white;border:1px solid #FCD34D;border-radius:10px;padding:10px 12px;">
                    <div style="font-size:10px;text-transform:uppercase;letter-spacing:.08em;font-weight:700;color:#92400E;">Mot de passe</div>
                    <div style="display:flex;align-items:center;gap:8px;margin-top:2px;">
                        <code style="font-family:ui-monospace,monospace;font-size:16px;font-weight:800;color:#78350F;letter-spacing:.10em;flex:1;">{{ session('temp_password') }}</code>
                        <button type="button"
                                @click="navigator.clipboard.writeText('{{ session('temp_password') }}').then(()=>{ copied=true; setTimeout(()=>copied=false,1800); })"
                                style="padding:6px 12px;background:#92400E;color:white;border:0;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;">
                            <span x-show="!copied">Copier</span>
                            <span x-show="copied" x-cloak>✓ Copié</span>
                        </button>
                    </div>
                </div>
            </div>
            <div style="margin-top:10px;font-size:11px;color:#78350F;">
                Connexion : <a href="{{ route('owner.login') }}" target="_blank" style="color:#78350F;font-weight:800;text-decoration:underline;">{{ url('/proprietaire/login') }}</a>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div style="background:linear-gradient(135deg,#0F172A 0%,#1E293B 60%,#0F4F44 100%);color:white;border-radius:18px;padding:24px 28px;margin-bottom:18px;display:flex;align-items:center;gap:18px;flex-wrap:wrap;">
        @if($owner->logo_url)
            <img src="{{ asset($owner->logo_url) }}" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,0.2);" alt="">
        @else
            <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#44A08D,#4ECDC4);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:28px;border:3px solid rgba(255,255,255,0.2);">
                {{ strtoupper(substr($owner->business_name, 0, 1)) }}
            </div>
        @endif
        <div style="flex:1;min-width:240px;">
            <a href="{{ route('admin.card-owners.index') }}" style="display:inline-flex;align-items:center;gap:5px;color:rgba(255,255,255,0.6);font-size:11px;font-weight:700;text-decoration:none;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">
                ← Propriétaires
            </a>
            <h1 style="margin:0;font-family:'Space Grotesk','Inter',sans-serif;font-size:26px;font-weight:800;letter-spacing:-0.02em;">{{ $owner->business_name }}</h1>
            <p style="margin:4px 0 0;color:rgba(255,255,255,0.7);font-size:13px;">
                {{ $owner->contact_name }}
                @if($owner->city) · {{ $owner->city }}@endif
                @if($owner->business_type && isset($categories[$owner->business_type])) · {{ $categories[$owner->business_type] }}@endif
            </p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="{{ route('admin.card-owners.edit', $owner) }}" style="display:inline-flex;align-items:center;gap:6px;padding:10px 16px;background:rgba(255,255,255,0.12);color:white;border:1px solid rgba(255,255,255,0.18);border-radius:11px;font-size:12px;font-weight:700;text-decoration:none;backdrop-filter:blur(8px);">
                Modifier
            </a>
            @if($owner->cards_count === 0)
                <form method="POST" action="{{ route('admin.card-owners.destroy', $owner) }}"
                      onsubmit="return confirm('Supprimer définitivement « {{ $owner->business_name }} » ?')"
                      style="display:inline;">
                    @csrf @method('DELETE')
                    <button type="submit" style="padding:10px 16px;background:rgba(220,38,38,0.18);color:#FCA5A5;border:1px solid rgba(220,38,38,0.3);border-radius:11px;font-size:12px;font-weight:700;cursor:pointer;">
                        Supprimer
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr;gap:14px;@media (min-width: 900px) {grid-template-columns:340px 1fr;}">
        {{-- Coordonnées --}}
        <div style="background:white;border:1px solid #E2E8F0;border-radius:14px;padding:18px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <h3 style="margin:0 0 12px;font-family:'Space Grotesk','Inter',sans-serif;font-size:14px;font-weight:800;color:#0F172A;">Coordonnées</h3>
            <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;">
                <div>
                    <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Email</div>
                    <div style="color:#0F172A;font-weight:600;">{{ $owner->email }}</div>
                </div>
                <div>
                    <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Téléphone</div>
                    <div style="color:#0F172A;font-weight:600;">{{ $owner->phone }}</div>
                </div>
                @if($owner->whatsapp_number)
                    <div>
                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">WhatsApp</div>
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $owner->whatsapp_number) }}" target="_blank" style="color:#10B981;font-weight:600;text-decoration:none;">{{ $owner->whatsapp_number }}</a>
                    </div>
                @endif
                @if($owner->address)
                    <div>
                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Adresse</div>
                        <div style="color:#475569;">{{ $owner->address }}</div>
                    </div>
                @endif
                <div>
                    <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Statut</div>
                    @if($owner->is_active)
                        <span style="display:inline-block;padding:3px 10px;border-radius:9999px;background:#D1FAE5;color:#065F46;font-weight:700;font-size:11px;">Actif</span>
                    @else
                        <span style="display:inline-block;padding:3px 10px;border-radius:9999px;background:#F1F5F9;color:#475569;font-weight:700;font-size:11px;">Inactif</span>
                    @endif
                </div>
                <div>
                    <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Dernière connexion</div>
                    <div style="color:#475569;">{{ $owner->last_login_at?->translatedFormat('d M Y H:i') ?? 'Jamais' }}</div>
                </div>
            </div>
        </div>

        {{-- Cartes attachées --}}
        <div style="background:white;border:1px solid #E2E8F0;border-radius:14px;padding:18px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                <h3 style="margin:0;font-family:'Space Grotesk','Inter',sans-serif;font-size:14px;font-weight:800;color:#0F172A;">
                    Cartes attachées
                    <span style="margin-left:6px;color:#94A3B8;font-weight:600;">({{ $owner->cards_count }})</span>
                </h3>
                <a href="{{ route('admin.merchant-cards.create', ['owner' => $owner->id]) }}"
                   style="display:inline-flex;align-items:center;gap:5px;padding:7px 12px;background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;border-radius:9px;font-size:12px;font-weight:700;text-decoration:none;">
                    + Nouvelle carte
                </a>
            </div>

            @if($owner->cards->count() > 0)
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px;">
                    @foreach($owner->cards as $card)
                        <a href="{{ route('admin.merchant-cards.show', $card) }}" style="display:block;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:12px;overflow:hidden;text-decoration:none;color:inherit;">
                            <div style="aspect-ratio:1.55;background:linear-gradient(135deg,#1E293B,#0F4F44);position:relative;">
                                @if($card->visual_url)
                                    <img src="{{ asset($card->visual_url) }}" style="width:100%;height:100%;object-fit:cover;" alt="">
                                @endif
                                <span style="position:absolute;top:8px;right:8px;padding:3px 8px;border-radius:9999px;background:{{ $card->is_active ? '#10B981' : '#64748B' }};color:white;font-size:9px;font-weight:800;letter-spacing:0.06em;">
                                    {{ $card->is_active ? 'ACTIVE' : 'BROUILLON' }}
                                </span>
                            </div>
                            <div style="padding:10px 12px;">
                                <div style="font-weight:700;color:#0F172A;font-size:13px;line-height:1.3;">{{ $card->name }}</div>
                                <div style="font-size:11px;color:#64748B;margin-top:2px;">{{ $card->total_sold }} vendues · {{ number_format($card->total_revenue ?? 0, 0, ',', ' ') }} FCFA</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div style="text-align:center;padding:28px 18px;border:2px dashed #E2E8F0;border-radius:12px;color:#64748B;">
                    <p style="margin:0 0 10px;font-size:13px;">Aucune carte rattachée à ce propriétaire pour le moment.</p>
                    <a href="{{ route('admin.merchant-cards.create', ['owner' => $owner->id]) }}"
                       style="display:inline-flex;align-items:center;gap:5px;padding:8px 14px;background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;border-radius:9px;font-size:12px;font-weight:700;text-decoration:none;">
                        + Créer une première carte
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
