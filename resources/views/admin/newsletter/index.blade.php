@extends('admin.layouts.admin')

@section('title', 'Newsletter')
@section('export-url', route('admin.newsletter.export'))
@section('page-title', 'Newsletter')

@section('content')
<div style="padding: 24px;">

    {{-- Stats --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:24px;">
        @foreach ([
            ['label' => 'Total inscrits',     'value' => $stats['total'],    'color' => '#1F2937', 'icon' => 'M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1a3 3 0 006 0v-1a9 9 0 10-9 9'],
            ['label' => 'Actifs',             'value' => $stats['active'],   'color' => '#059669', 'icon' => 'M5 13l4 4L19 7'],
            ['label' => 'Désinscrits',        'value' => $stats['inactive'], 'color' => '#6B7280', 'icon' => 'M6 18L18 6M6 6l12 12'],
            ['label' => 'Nouveaux (7 jours)', 'value' => $stats['last7'],    'color' => '#44A08D', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
        ] as $card)
            <div class="stat-card" style="background:white; border-radius:12px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:8px;">
                    <span style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:#6B7280;">{{ $card['label'] }}</span>
                    <div style="width:36px; height:36px; border-radius:10px; background:{{ $card['color'] }}15; display:flex; align-items:center; justify-content:center;">
                        <svg style="width:18px; height:18px; color:{{ $card['color'] }};" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}"/></svg>
                    </div>
                </div>
                <div style="font-size:28px; font-weight:800; color:#111827;">{{ number_format($card['value'], 0, ',', ' ') }}</div>
            </div>
        @endforeach
    </div>

    {{-- Toolbar : filtres + export --}}
    <form method="GET" action="{{ route('admin.newsletter.index') }}"
          style="background:white; border-radius:12px; padding:12px; box-shadow:0 1px 3px rgba(0,0,0,0.05); margin-bottom:16px; display:flex; flex-wrap:wrap; gap:8px; align-items:center;">

        <div style="position:relative; flex:1; min-width:200px;">
            <svg style="position:absolute; left:12px; top:50%; transform:translateY(-50%); width:16px; height:16px; color:#9CA3AF;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Rechercher par email…"
                   style="width:100%; padding:10px 12px 10px 36px; border:1px solid #E5E7EB; border-radius:8px; font-size:14px;">
        </div>

        <select name="status" onchange="this.form.submit()"
                style="padding:10px 12px; border:1px solid #E5E7EB; border-radius:8px; font-size:14px; min-width:140px;">
            <option value="">Tous statuts</option>
            <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Actifs</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Désinscrits</option>
        </select>

        <select name="source" onchange="this.form.submit()"
                style="padding:10px 12px; border:1px solid #E5E7EB; border-radius:8px; font-size:14px; min-width:140px;">
            <option value="">Toutes sources</option>
            @foreach($sources as $src)
                <option value="{{ $src }}" {{ request('source') === $src ? 'selected' : '' }}>{{ ucfirst($src) }}</option>
            @endforeach
        </select>

        <select name="sort" onchange="this.form.submit()"
                style="padding:10px 12px; border:1px solid #E5E7EB; border-radius:8px; font-size:14px; min-width:140px;">
            <option value="latest" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>Plus récents</option>
            <option value="oldest" {{ request('sort') === 'oldest'           ? 'selected' : '' }}>Plus anciens</option>
            <option value="email"  {{ request('sort') === 'email'            ? 'selected' : '' }}>Email A→Z</option>
        </select>

        <button type="submit"
                style="padding:10px 16px; background:#1F2937; color:white; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer;">
            Filtrer
        </button>

        @if(request()->hasAny(['search', 'status', 'source']))
            <a href="{{ route('admin.newsletter.index') }}" style="padding:10px 12px; color:#6B7280; font-size:14px; text-decoration:none;">
                Effacer
            </a>
        @endif

        <a href="{{ route('admin.newsletter.export', request()->query()) }}"
           style="margin-left:auto; padding:10px 16px; background:#44A08D; color:white; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
            <svg style="width:14px; height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Exporter Excel
        </a>
    </form>

    {{-- Flash messages : gérés en modal global via le layout --}}

    {{-- Table --}}
    <div style="background:white; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.05); overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <thead style="background:#F9FAFB; border-bottom:1px solid #E5E7EB;">
                    <tr>
                        <th style="text-align:left; padding:12px 16px; font-weight:600; color:#6B7280; text-transform:uppercase; font-size:11px; letter-spacing:0.05em;">Email</th>
                        <th style="text-align:left; padding:12px 16px; font-weight:600; color:#6B7280; text-transform:uppercase; font-size:11px; letter-spacing:0.05em;">Statut</th>
                        <th style="text-align:left; padding:12px 16px; font-weight:600; color:#6B7280; text-transform:uppercase; font-size:11px; letter-spacing:0.05em;">Source</th>
                        <th style="text-align:left; padding:12px 16px; font-weight:600; color:#6B7280; text-transform:uppercase; font-size:11px; letter-spacing:0.05em;">Date</th>
                        <th style="text-align:right; padding:12px 16px; font-weight:600; color:#6B7280; text-transform:uppercase; font-size:11px; letter-spacing:0.05em;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscribers as $sub)
                        <tr style="border-bottom:1px solid #F3F4F6;">
                            <td style="padding:14px 16px; color:#111827; font-weight:500;">
                                <a href="mailto:{{ $sub->email }}" style="color:#111827; text-decoration:none;">{{ $sub->email }}</a>
                            </td>
                            <td style="padding:14px 16px;">
                                @if($sub->is_active)
                                    <span class="badge-active" style="display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:9999px; font-size:11px; font-weight:700;">
                                        <span style="width:6px; height:6px; border-radius:9999px; background:#059669;"></span>
                                        Actif
                                    </span>
                                @else
                                    <span class="badge-cancelled" style="padding:3px 8px; border-radius:9999px; font-size:11px; font-weight:700;">
                                        Désinscrit
                                    </span>
                                @endif
                            </td>
                            <td style="padding:14px 16px; color:#6B7280; font-size:13px;">
                                <span style="font-family:monospace; padding:2px 6px; background:#F3F4F6; border-radius:4px; font-size:11px;">
                                    {{ $sub->source }}
                                </span>
                            </td>
                            <td style="padding:14px 16px; color:#6B7280; font-size:13px;">
                                {{ $sub->subscribed_at?->format('d/m/Y H:i') }}
                                @if($sub->unsubscribed_at)
                                    <div style="font-size:11px; color:#9CA3AF;">Désinscrit le {{ $sub->unsubscribed_at->format('d/m/Y') }}</div>
                                @endif
                            </td>
                            <td style="padding:14px 16px; text-align:right;">
                                <form method="POST" action="{{ route('admin.newsletter.toggle', $sub) }}" style="display:inline;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            style="padding:6px 10px; border:1px solid #E5E7EB; background:white; border-radius:6px; cursor:pointer; font-size:12px; color:#374151; margin-right:4px;"
                                            title="{{ $sub->is_active ? 'Désinscrire' : 'Réactiver' }}">
                                        {{ $sub->is_active ? 'Désinscrire' : 'Réactiver' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.newsletter.destroy', $sub) }}" style="display:inline;"
                                      onsubmit="return confirm('Supprimer définitivement {{ $sub->email }} ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            style="padding:6px 10px; border:1px solid #FECACA; background:white; color:#DC2626; border-radius:6px; cursor:pointer; font-size:12px;">
                                        Supprimer
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding:60px 16px; text-align:center; color:#6B7280;">
                                <svg style="width:48px; height:48px; margin:0 auto 12px; color:#D1D5DB;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <div style="font-weight:600; color:#374151; margin-bottom:4px;">Aucun abonné</div>
                                <div style="font-size:13px;">Les inscriptions à la newsletter apparaîtront ici.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($subscribers->hasPages())
            <div style="padding:12px 16px; border-top:1px solid #F3F4F6; background:#FAFBFC;">
                {{ $subscribers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
