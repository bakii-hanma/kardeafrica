@extends('admin.layouts.admin')

@section('title', 'Compte pro — ' . $owner->business_name)
@section('page-title', 'Dossier pro')

@section('content')
<div style="padding:24px;font-family:'Inter','Figtree',sans-serif;max-width:900px;">

    @if(session('success'))
        <div style="margin-bottom:16px;padding:12px 16px;background:#D1FAE5;border:1px solid #A7F3D0;border-radius:12px;color:#047857;font-size:14px;font-weight:600;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="margin-bottom:16px;padding:12px 16px;background:#FEE2E2;border:1px solid #FECACA;border-radius:12px;color:#B91C1C;font-size:14px;">{{ $errors->first() }}</div>
    @endif

    <a href="{{ route('admin.proprietaires.index', ['status' => $owner->status]) }}" style="font-size:13px;color:#64748B;text-decoration:none;">← Retour à la liste</a>

    {{-- En-tête --}}
    <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:22px;margin-top:12px;display:flex;gap:18px;align-items:center;">
        <div style="width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,#4ECDC4,#44A08D);display:flex;align-items:center;justify-content:center;color:white;font-weight:800;font-size:22px;font-family:'Space Grotesk',sans-serif;">
            {{ strtoupper(substr($owner->business_name, 0, 1)) }}
        </div>
        <div style="flex:1;">
            <div style="font-size:20px;font-weight:800;color:#0F172A;font-family:'Space Grotesk',sans-serif;">{{ $owner->business_name }}</div>
            <div style="font-size:13px;color:#64748B;margin-top:2px;">Gérant : {{ $owner->contact_name }}</div>
        </div>
        @php
            $badge = [
                'provisional'    => ['À valider', '#44A08D', '#E6F4F1'],
                'docs_requested' => ['Pièces demandées', '#D97706', '#FEF3C7'],
                'active'         => ['Validé', '#047857', '#D1FAE5'],
                'rejected'       => ['Refusé', '#DC2626', '#FEE2E2'],
                'suspended'      => ['Suspendu', '#64748B', '#F1F5F9'],
                'pending_otp'    => ['Numéro non vérifié', '#64748B', '#F1F5F9'],
                'otp_verified'   => ['Dossier incomplet', '#64748B', '#F1F5F9'],
            ][$owner->status] ?? ['Inconnu', '#64748B', '#F1F5F9'];
        @endphp
        <span style="padding:6px 14px;border-radius:9999px;font-size:12px;font-weight:700;color:{{ $badge[1] }};background:{{ $badge[2] }};">{{ $badge[0] }}</span>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;">
        {{-- Coordonnées --}}
        <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:20px;">
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;margin-bottom:12px;">Coordonnées</div>
            @foreach([
                'Email' => $owner->email,
                'Téléphone' => $owner->phone,
                'WhatsApp' => $owner->whatsapp_number,
                'Ville' => $owner->city ?: '—',
                'Quartier' => $owner->quartier ?: '—',
                'Adresse' => $owner->address ?: '—',
                'Position' => ($owner->geo_lat && $owner->geo_lng) ? $owner->geo_lat.', '.$owner->geo_lng : '—',
                'Cartes créées' => $owner->cards_count ?? 0,
            ] as $k => $v)
                <div style="display:flex;justify-content:space-between;gap:12px;padding:6px 0;font-size:13px;border-bottom:1px solid #F8FAFC;">
                    <span style="color:#64748B;">{{ $k }}</span>
                    <span style="color:#0F172A;font-weight:600;text-align:right;">
                        @if($k === 'Position' && $owner->geo_lat && $owner->geo_lng)
                            <a href="https://maps.google.com/?q={{ $owner->geo_lat }},{{ $owner->geo_lng }}" target="_blank" style="color:#44A08D;">{{ $v }}</a>
                        @else
                            {{ $v }}
                        @endif
                    </span>
                </div>
            @endforeach
        </div>

        {{-- Pièces KYC --}}
        <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:20px;">
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;margin-bottom:12px;">Pièces justificatives</div>
            @if($owner->kyc_submitted_at)
                <p style="font-size:12px;color:#64748B;margin-bottom:12px;">Déposées {{ $owner->kyc_submitted_at->diffForHumans() }}</p>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    @if($owner->id_document_path)
                        <a href="{{ route('admin.proprietaires.document', [$owner, 'id']) }}" target="_blank"
                           style="display:flex;align-items:center;gap:10px;padding:12px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;text-decoration:none;color:#0F172A;font-size:13px;font-weight:600;">
                            <svg style="width:18px;height:18px;color:#44A08D;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            Pièce d'identité du gérant
                        </a>
                    @endif
                    @if($owner->business_document_path)
                        <a href="{{ route('admin.proprietaires.document', [$owner, 'business']) }}" target="_blank"
                           style="display:flex;align-items:center;gap:10px;padding:12px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;text-decoration:none;color:#0F172A;font-size:13px;font-weight:600;">
                            <svg style="width:18px;height:18px;color:#44A08D;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            Fiche circuit / justificatif d'entreprise
                        </a>
                    @endif
                </div>
            @else
                <p style="font-size:13px;color:#94A3B8;">Aucune pièce déposée pour l'instant.</p>
            @endif

            @if($owner->docs_requested_note)
                <div style="margin-top:12px;padding:10px 12px;background:#FEF3C7;border:1px solid #FDE68A;border-radius:10px;font-size:12px;color:#92400E;">
                    <strong>Pièces demandées :</strong> {{ $owner->docs_requested_note }}
                </div>
            @endif
        </div>
    </div>

    {{-- Actions admin --}}
    @if(!in_array($owner->status, ['pending_otp','otp_verified'], true))
    <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:20px;margin-top:16px;">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;margin-bottom:14px;">Décision</div>
        <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-start;">
            {{-- Valider --}}
            @if($owner->status !== 'active')
            <form method="POST" action="{{ route('admin.proprietaires.approve', $owner) }}">
                @csrf @method('PATCH')
                <button type="submit" style="padding:11px 18px;background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">✓ Valider le compte</button>
            </form>
            @endif

            {{-- Suspendre --}}
            @if($owner->status === 'active')
            <form method="POST" action="{{ route('admin.proprietaires.suspend', $owner) }}">
                @csrf @method('PATCH')
                <button type="submit" style="padding:11px 18px;background:#F1F5F9;color:#475569;border:1px solid #E2E8F0;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">Suspendre</button>
            </form>
            @endif
        </div>

        {{-- Demander des pièces (WhatsApp) --}}
        <form method="POST" action="{{ route('admin.proprietaires.request-docs', $owner) }}" style="margin-top:16px;">
            @csrf @method('PATCH')
            <label style="display:block;font-size:13px;font-weight:600;color:#475569;margin-bottom:6px;">Demander des pièces complémentaires (envoyé par WhatsApp)</label>
            <textarea name="note" rows="2" placeholder="Ex. Merci de renvoyer une photo plus nette de la pièce d'identité, recto-verso."
                      style="width:100%;padding:10px 12px;border:1px solid #E2E8F0;border-radius:10px;font-size:13px;font-family:inherit;resize:vertical;">{{ old('note') }}</textarea>
            <button type="submit" style="margin-top:8px;padding:10px 16px;background:#D97706;color:white;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">Demander via WhatsApp</button>
        </form>

        {{-- Refuser --}}
        @if($owner->status !== 'rejected')
        <form method="POST" action="{{ route('admin.proprietaires.reject', $owner) }}" style="margin-top:16px;border-top:1px solid #F1F5F9;padding-top:16px;">
            @csrf @method('PATCH')
            <label style="display:block;font-size:13px;font-weight:600;color:#475569;margin-bottom:6px;">Refuser le dossier (motif envoyé par WhatsApp)</label>
            <textarea name="note" rows="2" placeholder="Motif du refus…"
                      style="width:100%;padding:10px 12px;border:1px solid #E2E8F0;border-radius:10px;font-size:13px;font-family:inherit;resize:vertical;">{{ old('note') }}</textarea>
            <button type="submit" style="margin-top:8px;padding:10px 16px;background:#FEE2E2;color:#B91C1C;border:1px solid #FECACA;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">Refuser</button>
        </form>
        @endif
    </div>
    @endif
</div>

{{-- ============ REVERSEMENTS ============
     `owner_net_amount` s'accumulait sans qu'aucune trace ne dise ce qui avait
     été réellement versé. Ce bloc rend le solde vérifiable des deux côtés. --}}
<div style="background:#fff;border:1px solid #E2E8F0;border-radius:16px;padding:18px;margin-top:20px;">
    <h2 style="font-size:15px;font-weight:800;color:#0F172A;margin:0 0 14px;">Reversements</h2>

    @if (session('success'))
        <div style="background:#D1FAE5;border:1px solid #6EE7B7;color:#065F46;border-radius:10px;padding:10px 12px;margin-bottom:14px;font-size:13px;font-weight:600;">
            {{ session('success') }}
        </div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-bottom:16px;">
        <div style="background:#F8FAFC;border-radius:10px;padding:12px;">
            <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748B;">Servi au comptoir</div>
            <div style="font-size:18px;font-weight:800;color:#0F172A;">{{ number_format($earnings->redeemedGross(), 0, ',', ' ') }} <span style="font-size:11px;color:#94A3B8;">FCFA</span></div>
        </div>
        <div style="background:#F8FAFC;border-radius:10px;padding:12px;">
            <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748B;">Acquis (net)</div>
            <div style="font-size:18px;font-weight:800;color:#0F172A;">{{ number_format($earnings->dueNet(), 0, ',', ' ') }} <span style="font-size:11px;color:#94A3B8;">FCFA</span></div>
        </div>
        <div style="background:#F8FAFC;border-radius:10px;padding:12px;">
            <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748B;">Déjà versé</div>
            <div style="font-size:18px;font-weight:800;color:#047857;">{{ number_format($earnings->settled(), 0, ',', ' ') }} <span style="font-size:11px;color:#94A3B8;">FCFA</span></div>
        </div>
        <div style="background:#0F172A;border-radius:10px;padding:12px;">
            <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:rgba(255,255,255,.7);">Reste à verser</div>
            <div style="font-size:18px;font-weight:800;color:#fff;">{{ number_format($earnings->outstanding(), 0, ',', ' ') }} <span style="font-size:11px;color:rgba(255,255,255,.6);">FCFA</span></div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.proprietaires.settlement', $owner) }}"
          style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;align-items:end;">
        @csrf
        <label style="font-size:12px;font-weight:700;color:#475569;">
            Montant (FCFA)
            <input type="number" name="amount" step="1" min="1" required
                   value="{{ max(0, (int) $earnings->outstanding()) }}"
                   style="width:100%;margin-top:4px;padding:10px;border:1px solid #CBD5E1;border-radius:9px;font-size:14px;">
        </label>
        <label style="font-size:12px;font-weight:700;color:#475569;">
            Moyen
            <select name="method" style="width:100%;margin-top:4px;padding:10px;border:1px solid #CBD5E1;border-radius:9px;font-size:14px;">
                @foreach (\App\Models\MerchantSettlement::METHODS as $k => $lib)
                    <option value="{{ $k }}">{{ $lib }}</option>
                @endforeach
            </select>
        </label>
        <label style="font-size:12px;font-weight:700;color:#475569;">
            Référence
            <input type="text" name="reference" placeholder="N° transaction"
                   style="width:100%;margin-top:4px;padding:10px;border:1px solid #CBD5E1;border-radius:9px;font-size:14px;">
        </label>
        <button type="submit" style="padding:11px 16px;background:#0F766E;color:#fff;border:0;border-radius:9px;font-size:13px;font-weight:800;cursor:pointer;">
            Enregistrer le versement
        </button>
    </form>

    @if ($settlements->count() > 0)
        <table style="width:100%;margin-top:16px;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="text-align:left;color:#64748B;font-size:10px;text-transform:uppercase;letter-spacing:.07em;">
                    <th style="padding:8px 6px;">Date</th>
                    <th style="padding:8px 6px;">Moyen</th>
                    <th style="padding:8px 6px;">Référence</th>
                    <th style="padding:8px 6px;">Par</th>
                    <th style="padding:8px 6px;text-align:right;">Montant</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($settlements as $s)
                    <tr style="border-top:1px solid #F1F5F9;">
                        <td style="padding:9px 6px;">{{ $s->settled_at?->format('d/m/Y') }}</td>
                        <td style="padding:9px 6px;">{{ $s->methodLabel() }}</td>
                        <td style="padding:9px 6px;color:#64748B;">{{ $s->reference ?: '—' }}</td>
                        <td style="padding:9px 6px;color:#64748B;">{{ $s->recordedBy?->name ?? '—' }}</td>
                        <td style="padding:9px 6px;text-align:right;font-weight:800;">{{ number_format((float) $s->amount, 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="margin-top:12px;">{{ $settlements->links() }}</div>
    @endif
</div>

@endsection
