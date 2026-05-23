@extends('admin.layouts.admin')

@section('title', 'Daywatch')
@section('page-title', 'Abonnements Daywatch')

@php
    $activeFilters = (int)!empty(request('search')) + (int)!empty(request('status'));
@endphp

@section('content')
<div style="padding:24px;font-family:'Inter','Figtree',sans-serif;">

    {{-- Stats grid --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:18px;">
        {{-- Total --}}
        <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#F8FAFC,#F1F5F9);border:1px solid #E2E8F0;display:flex;align-items:center;justify-content:center;color:#475569;flex-shrink:0;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25"/></svg>
            </div>
            <div>
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Total abonnements</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#0F172A;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:2px;">
                    {{ number_format($stats['total'], 0, ',', ' ') }}
                </div>
            </div>
        </div>

        {{-- Active --}}
        <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#D1FAE5,#A7F3D0);display:flex;align-items:center;justify-content:center;color:#047857;flex-shrink:0;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">Actifs</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#047857;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:2px;">
                    {{ number_format($stats['active'], 0, ',', ' ') }}
                </div>
            </div>
        </div>

        {{-- Featured --}}
        <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#FEF3C7,#FDE68A);display:flex;align-items:center;justify-content:center;color:#B45309;flex-shrink:0;">
                <svg style="width:20px;height:20px;" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            </div>
            <div>
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:#94A3B8;">À la une</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:#B45309;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:2px;">
                    {{ number_format($stats['featured'], 0, ',', ' ') }}
                </div>
            </div>
        </div>

        {{-- Avg price --}}
        <div style="background:linear-gradient(135deg,#0F172A,#1E293B);border-radius:14px;padding:16px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 4px 12px rgba(15,23,42,0.15);position:relative;overflow:hidden;">
            <div style="position:absolute;top:-20px;right:-20px;width:90px;height:90px;border-radius:50%;background:radial-gradient(circle,rgba(78,205,196,0.18) 0%,transparent 70%);"></div>
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#44A08D,#4ECDC4);display:flex;align-items:center;justify-content:center;color:white;flex-shrink:0;position:relative;">
                <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div style="position:relative;">
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:rgba(255,255,255,0.55);">Prix moyen</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:20px;font-weight:800;color:white;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:2px;">
                    {{ number_format($stats['avg_price'], 0, ',', ' ') }}
                    <span style="font-size:11px;font-weight:600;color:rgba(255,255,255,0.55);">FCFA</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Toolbar : filtres + Nouveau --}}
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-bottom:18px;">
        <form method="GET" action="{{ route('admin.daywatch.index') }}"
              style="flex:1 1 auto;min-width:280px;background:white;border-radius:14px;padding:10px;border:1px solid #E2E8F0;box-shadow:0 1px 2px rgba(15,23,42,0.04);display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
            <div style="position:relative;flex:1 1 220px;min-width:200px;">
                <svg style="position:absolute;left:14px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#94A3B8;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, sous-titre, description…"
                       style="width:100%;padding:10px 14px 10px 40px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;outline:none;">
            </div>
            <select name="status" onchange="this.form.submit()"
                    style="padding:10px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;font-size:13px;font-weight:500;color:#334155;outline:none;cursor:pointer;">
                <option value="">Tous statuts</option>
                <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Actifs</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactifs</option>
            </select>
            <button type="submit" style="padding:10px 18px;background:#44A08D;color:white;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;">Filtrer</button>
            @if($activeFilters > 0)
                <a href="{{ route('admin.daywatch.index') }}" style="padding:10px 14px;color:#64748B;font-size:12px;font-weight:600;text-decoration:none;background:#F1F5F9;border-radius:10px;display:inline-flex;align-items:center;">✕</a>
            @endif
        </form>

        <a href="{{ route('admin.daywatch.create') }}"
           style="display:inline-flex;align-items:center;gap:8px;padding:12px 18px;background:linear-gradient(135deg,#44A08D,#4ECDC4);color:white;border-radius:12px;font-size:14px;font-weight:600;text-decoration:none;box-shadow:0 10px 24px -10px rgba(68,160,141,0.5);">
            <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Nouveau Daywatch
        </a>
    </div>

    {{-- Liste cards --}}
    @if($products->count() > 0)
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:14px;">
            @foreach($products as $product)
                <div style="background:white;border-radius:16px;border:1px solid #E2E8F0;overflow:hidden;box-shadow:0 1px 2px rgba(15,23,42,0.04);transition:transform .2s ease, box-shadow .2s ease;"
                     onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 10px 24px -10px rgba(15,23,42,0.10)';"
                     onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 1px 2px rgba(15,23,42,0.04)';">

                    {{-- Mini-carte --}}
                    <div style="position:relative;height:120px;padding:18px;color:#fff;background:linear-gradient(135deg,{{ $product->color }} 0%,{{ $product->color }}cc 100%);overflow:hidden;">
                        <div style="position:absolute;inset:0;background:radial-gradient(circle at 80% 0%,rgba(255,255,255,0.20) 0%,transparent 50%);pointer-events:none;"></div>
                        <div style="position:relative;display:flex;align-items:flex-start;justify-content:space-between;">
                            <div>
                                <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;opacity:0.8;">Daywatch</div>
                                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:700;margin-top:2px;line-height:1.1;">{{ $product->name }}</div>
                                @if($product->subtitle)
                                    <div style="font-size:11px;opacity:0.85;margin-top:4px;">{{ $product->subtitle }}</div>
                                @endif
                            </div>
                            @if($product->is_featured)
                                <span style="background:rgba(255,255,255,0.18);border:1px solid rgba(255,255,255,0.3);border-radius:9999px;padding:2px 8px;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;flex-shrink:0;">À la une</span>
                            @endif
                        </div>
                        <div style="position:absolute;bottom:14px;left:18px;right:18px;display:flex;align-items:end;justify-content:space-between;">
                            <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;font-variant-numeric:tabular-nums;line-height:1;">
                                {{ number_format($product->price_xaf, 0, ',', ' ') }}
                                <span style="font-size:11px;font-weight:500;opacity:0.8;">{{ $product->currency }}</span>
                            </div>
                            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;background:rgba(0,0,0,0.20);padding:3px 8px;border-radius:6px;">
                                {{ $product->duration_days }} j
                            </div>
                        </div>
                    </div>

                    {{-- Body --}}
                    <div style="padding:14px 16px;">
                        @if($product->description)
                            <p style="font-size:13px;color:#475569;line-height:1.55;margin:0 0 10px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ $product->description }}</p>
                        @endif

                        @if(!empty($product->features))
                            <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:12px;">
                                @foreach (array_slice($product->features, 0, 3) as $feat)
                                    <span style="background:#F1F5F9;color:#475569;font-size:10px;font-weight:600;padding:3px 8px;border-radius:9999px;">{{ $feat }}</span>
                                @endforeach
                                @if(count($product->features) > 3)
                                    <span style="background:#F1F5F9;color:#94A3B8;font-size:10px;font-weight:600;padding:3px 8px;border-radius:9999px;">+{{ count($product->features) - 3 }}</span>
                                @endif
                            </div>
                        @endif

                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding-top:10px;border-top:1px solid #F1F5F9;">
                            {{-- Statut --}}
                            <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;color:{{ $product->is_active ? '#047857' : '#94A3B8' }};">
                                <span style="width:6px;height:6px;border-radius:50%;background:{{ $product->is_active ? '#10B981' : '#CBD5E1' }};"></span>
                                {{ $product->is_active ? 'Actif' : 'Inactif' }}
                            </span>

                            {{-- Actions --}}
                            <div style="display:flex;align-items:center;gap:6px;">
                                <form action="{{ route('admin.daywatch.toggle', $product) }}" method="POST" style="display:inline;">
                                    @csrf @method('PATCH')
                                    <button type="submit" title="{{ $product->is_active ? 'Désactiver' : 'Activer' }}"
                                            style="width:30px;height:30px;border-radius:8px;background:#F8FAFC;border:1px solid #E2E8F0;color:#64748B;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;">
                                        @if($product->is_active)
                                            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                        @else
                                            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        @endif
                                    </button>
                                </form>
                                <a href="{{ route('admin.daywatch.edit', $product) }}" title="Modifier"
                                   style="width:30px;height:30px;border-radius:8px;background:#F8FAFC;border:1px solid #E2E8F0;color:#64748B;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;">
                                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form action="{{ route('admin.daywatch.destroy', $product) }}" method="POST" style="display:inline;"
                                      onsubmit="return confirm('Supprimer définitivement « {{ $product->name }} » ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Supprimer"
                                            style="width:30px;height:30px;border-radius:8px;background:#FEF2F2;border:1px solid #FECACA;color:#BE123C;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;">
                                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a2 2 0 012-2h2a2 2 0 012 2v3"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div style="margin-top:18px;">
            {{ $products->links() }}
        </div>
    @else
        <div style="background:white;border-radius:14px;border:1px solid #E2E8F0;padding:60px 40px;text-align:center;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="width:60px;height:60px;border-radius:14px;background:#F1F5F9;margin:0 auto 14px;display:flex;align-items:center;justify-content:center;">
                <svg style="width:26px;height:26px;color:#CBD5E1;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25"/></svg>
            </div>
            <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:16px;font-weight:700;color:#0F172A;margin-bottom:4px;">Aucun abonnement Daywatch</div>
            <div style="font-size:13px;color:#64748B;">{{ $activeFilters > 0 ? 'Aucun produit ne correspond à vos filtres.' : 'Cliquez sur « Nouveau Daywatch » pour créer votre premier abonnement.' }}</div>
        </div>
    @endif
</div>
@endsection
