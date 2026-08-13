@extends('admin.layouts.admin')

@section('title', 'Livraisons en attente')
@section('page-title', 'Commandes en attente de livraison')

@section('content')
<style>
    @keyframes ka-spin { to { transform: rotate(360deg); } }
    .ka-spin { animation: ka-spin .8s linear infinite; transform-origin: center; }
</style>
<script>
    // Expose les données serveur à Alpine sans @verbatim
    window.__deliveryHealth   = @json($apiStatus);
    window.__deliveryOrderIds = @json($orders->pluck('id')->all());
    window.__deliveryRouteHealth = @json(route('admin.health.afrikard'));
</script>

<div style="padding:24px;font-family:'Inter','Figtree',sans-serif;"
     x-data="pendingDelivery()">

    {{-- ============================================================
         API HEALTH BANNER
       ============================================================ --}}
    <div :style="`background:var(--surface);border-radius:16px;padding:18px 20px;margin-bottom:18px;
                  box-shadow:0 1px 2px rgba(15,23,42,0.04);
                  border:1px solid var(--border);
                  border-left:4px solid ${health.ok ? '#10B981' : '#F43F5E'};`">
        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <div :style="`width:46px;height:46px;border-radius:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;
                          background:${health.ok ? 'linear-gradient(135deg,var(--teal-soft),var(--teal-soft))' : 'linear-gradient(135deg,rgb(224 95 78 / .12),#FECACA)'};
                          color:${health.ok ? '#047857' : '#BE123C'};`">
                <template x-if="loading">
                    <svg style="width:22px;height:22px;" class="ka-spin" fill="none" viewBox="0 0 24 24"><circle style="opacity:.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path style="opacity:.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                </template>
                <template x-if="!loading && health.ok">
                    <svg style="width:24px;height:24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </template>
                <template x-if="!loading && !health.ok">
                    <svg style="width:24px;height:24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </template>
            </div>

            <div style="flex:1;min-width:240px;">
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.10em;color:var(--text-faint);">API afrikard</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:17px;font-weight:700;margin-top:2px;"
                     :style="`color:${health.ok ? '#047857' : '#BE123C'};`"
                     x-text="loading ? 'Vérification…' : (health.ok ? 'Disponible' : 'Indisponible')"></div>
                <div style="font-size:13px;color:var(--text-muted);margin-top:3px;" x-text="health.message"></div>
            </div>

            <div style="display:flex;flex-direction:column;align-items:flex-end;font-size:11px;color:var(--text-muted);line-height:1.5;">
                <div>
                    Latence : <span style="font-weight:700;color:var(--text);font-variant-numeric:tabular-nums;" x-text="(health.latency_ms ?? 0) + ' ms'"></span>
                </div>
                <div x-show="health.checked_at">
                    Dernier check : <span style="font-weight:700;color:var(--text);" x-text="(health.checked_at || '').slice(11,19)"></span>
                </div>
            </div>

            <button type="button" @click="testApi()" :disabled="loading"
                    style="display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border:0;border-radius:11px;font-size:13px;font-weight:700;background:linear-gradient(135deg,var(--text),var(--navy));color:var(--surface);box-shadow:0 8px 18px -8px rgba(15,23,42,0.4);"
                    :style="loading ? 'opacity:0.6;cursor:not-allowed;' : 'cursor:pointer;'">
                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Tester la connexion
            </button>
        </div>
    </div>

    {{-- ============================================================
         STATS GRID
       ============================================================ --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-bottom:18px;">
        {{-- En attente --}}
        <div style="background:var(--surface);border-radius:14px;border:1px solid var(--border);padding:18px 20px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,rgb(245 158 11 / .14),var(--chip-orange));display:flex;align-items:center;justify-content:center;color:var(--chip-orange);flex-shrink:0;">
                <svg style="width:22px;height:22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div style="min-width:0;">
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.10em;font-weight:700;color:var(--text-faint);">En attente de livraison</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:24px;font-weight:800;color:var(--chip-orange);font-variant-numeric:tabular-nums;line-height:1.05;margin-top:3px;">
                    {{ $orders->total() }}
                </div>
            </div>
        </div>

        {{-- Sélectionnées --}}
        <div style="background:var(--surface);border-radius:14px;border:1px solid var(--border);padding:18px 20px;display:flex;align-items:center;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,#E0F2FE,#BAE6FD);display:flex;align-items:center;justify-content:center;color:var(--chip-blue);flex-shrink:0;">
                <svg style="width:22px;height:22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div style="min-width:0;">
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.10em;font-weight:700;color:var(--text-faint);">Sélectionnées</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:24px;font-weight:800;color:var(--chip-blue);font-variant-numeric:tabular-nums;line-height:1.05;margin-top:3px;"
                     x-text="selected.length"></div>
            </div>
        </div>

        {{-- Total à livrer --}}
        <div style="background:linear-gradient(135deg,var(--text),var(--navy));border-radius:14px;padding:18px 20px;display:flex;align-items:center;gap:14px;box-shadow:0 4px 12px rgba(15,23,42,0.15);position:relative;overflow:hidden;">
            <div style="position:absolute;top:-25px;right:-25px;width:100px;height:100px;border-radius:50%;background:radial-gradient(circle,rgba(78,205,196,0.20) 0%,transparent 70%);"></div>
            <div style="width:46px;height:46px;border-radius:13px;background:var(--teal);display:flex;align-items:center;justify-content:center;color:var(--navy);flex-shrink:0;position:relative;">
                <svg style="width:22px;height:22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div style="min-width:0;position:relative;">
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.10em;font-weight:700;color:rgba(255,255,255,0.55);">Montant total à livrer</div>
                <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:22px;font-weight:800;color:var(--surface);font-variant-numeric:tabular-nums;line-height:1.05;margin-top:3px;">
                    {{ number_format($orders->sum('total_amount'), 0, ',', ' ') }}
                    <span style="font-size:11px;font-weight:600;color:rgba(255,255,255,0.55);">FCFA</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         BULK FORM + LIST
       ============================================================ --}}
    @if ($orders->count() > 0)
        <form method="POST" action="{{ route('admin.orders.retry-bulk') }}" data-no-loader
              @submit="if (selected.length === 0) { $event.preventDefault(); alert('Sélectionne au moins une commande.'); return; } if (!health.ok) { $event.preventDefault(); alert('API afrikard indisponible — teste la connexion d\'abord.'); return; } return confirm('Relancer ' + selected.length + ' commande(s) ?');">
            @csrf
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="order_ids[]" :value="id">
            </template>

            {{-- Toolbar --}}
            <div style="background:var(--surface);border-radius:var(--r-sub);padding:12px 16px;box-shadow:var(--shadow-card);margin-bottom:12px;display:flex;flex-wrap:wrap;align-items:center;gap:12px;">
                <label style="display:inline-flex;align-items:center;gap:10px;font-size:13px;font-weight:600;color:var(--text-muted);cursor:pointer;">
                    <input type="checkbox" @change="toggleAll($event.target.checked)" :checked="allSelected"
                           style="width:18px;height:18px;accent-color:var(--teal);">
                    Tout sélectionner sur cette page
                </label>

                <div style="margin-left:auto;display:flex;align-items:center;gap:14px;">
                    <span style="font-size:12px;color:var(--text-muted);" x-show="selected.length > 0">
                        <span style="font-weight:700;color:var(--text);" x-text="selected.length"></span> sélectionnée(s)
                    </span>
                    <button type="submit" :disabled="selected.length === 0 || !health.ok"
                            :style="`display:inline-flex;align-items:center;gap:8px;padding:11px 22px;border:0;border-radius:11px;font-size:13px;font-weight:700;color:var(--surface);
                                     background:var(--navy);
                                     box-shadow:var(--shadow-card);
                                     ${(selected.length === 0 || !health.ok) ? 'opacity:0.5;cursor:not-allowed;' : 'cursor:pointer;'}`">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Relancer la sélection
                    </button>
                </div>
            </div>

            {{-- List --}}
            <div style="display:flex;flex-direction:column;gap:8px;">
                @foreach ($orders as $order)
                    @php $itemsCount = $order->orderItems->sum('quantity'); @endphp
                    <label :style="`background:var(--surface);border-radius:var(--r-sub);padding:14px 16px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;
                                    box-shadow:var(--shadow-card);transition:all .15s ease;cursor:pointer;
                                    border:${selected.includes({{ $order->id }}) ? '1px solid var(--teal)' : '1px solid transparent'};
                                    ${selected.includes({{ $order->id }}) ? 'box-shadow:0 0 0 2px rgb(20 184 166 / .16);' : ''}`">

                        <input type="checkbox" :value="{{ $order->id }}" :checked="selected.includes({{ $order->id }})"
                               @change="toggleOne({{ $order->id }}, $event.target.checked)"
                               style="width:18px;height:18px;accent-color:var(--teal);flex-shrink:0;cursor:pointer;">

                        <div style="width:40px;height:40px;border-radius:11px;background:rgb(245 158 11 / .14);color:var(--chip-orange);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>

                        <div style="flex:1;min-width:200px;">
                            <div style="display:flex;align-items:center;gap:6px;margin-bottom:2px;flex-wrap:wrap;">
                                <span style="font-family:monospace;font-size:13px;font-weight:700;color:var(--text);">#{{ $order->order_number }}</span>
                                <span style="font-size:11px;color:var(--text-faint);">·</span>
                                <span style="font-size:11px;color:var(--text-muted);">{{ $order->created_at->diffForHumans() }}</span>
                            </div>
                            <div style="font-size:12px;color:var(--text-muted);display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                @if($order->user)
                                    <span style="font-weight:600;color:var(--text);">{{ $order->user->name }}</span>
                                    <span style="color:var(--text-faint);">·</span>
                                    <span style="color:var(--text-faint);font-family:monospace;font-size:11px;">{{ $order->user->email }}</span>
                                @else
                                    <span style="color:var(--text-faint);">Client supprimé</span>
                                @endif
                            </div>
                        </div>

                        <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;flex-wrap:wrap;">
                            <span style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:9999px;font-size:11px;font-weight:700;background:var(--teal-soft);color:var(--teal);border:1px solid var(--teal-soft);">
                                Payé
                            </span>
                            <span style="display:inline-flex;align-items:center;padding:3px 10px;border-radius:9999px;font-size:11px;font-weight:700;background:rgb(245 158 11 / .14);color:var(--chip-orange);border:1px solid var(--chip-orange);">
                                {{ $itemsCount }} {{ Str::plural('carte', $itemsCount) }}
                            </span>
                        </div>

                        <div style="text-align:right;flex-shrink:0;">
                            <div style="font-size:9px;text-transform:uppercase;letter-spacing:0.08em;font-weight:700;color:var(--text-faint);line-height:1;">Montant</div>
                            <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:16px;font-weight:800;color:var(--text);font-variant-numeric:tabular-nums;margin-top:2px;line-height:1;">
                                {{ number_format($order->total_amount, 0, ',', ' ') }}
                                <span style="font-size:11px;font-weight:500;color:var(--text-faint);">FCFA</span>
                            </div>
                        </div>

                        <a href="{{ route('admin.orders.show', $order) }}"
                           @click.stop
                           style="width:32px;height:32px;border-radius:8px;background:var(--surface-inset);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-muted);text-decoration:none;flex-shrink:0;"
                           title="Voir le détail">
                            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </label>
                @endforeach
            </div>
        </form>

        <div style="margin-top:18px;">
            {{ $orders->links() }}
        </div>
    @else
        <div style="background:var(--surface);border-radius:14px;border:1px solid var(--border);padding:60px 40px;text-align:center;box-shadow:0 1px 2px rgba(15,23,42,0.04);">
            <div style="width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,var(--teal-soft),var(--teal-soft));margin:0 auto 14px;display:flex;align-items:center;justify-content:center;color:var(--teal);">
                <svg style="width:30px;height:30px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div style="font-family:'Space Grotesk','Inter',sans-serif;font-size:18px;font-weight:700;color:var(--text);margin-bottom:4px;">Aucune livraison en attente</div>
            <div style="font-size:13px;color:var(--text-muted);">Toutes les commandes payées ont été livrées.</div>
        </div>
    @endif
</div>

<script>
    function pendingDelivery() {
        return {
            health:        window.__deliveryHealth,
            availableIds:  window.__deliveryOrderIds,
            healthRoute:   window.__deliveryRouteHealth,
            loading:       false,
            selected:      [],
            get allSelected() {
                return this.availableIds.length > 0 && this.selected.length === this.availableIds.length;
            },
            toggleAll(checked) {
                this.selected = checked ? [...this.availableIds] : [];
            },
            toggleOne(id, checked) {
                if (checked) {
                    if (!this.selected.includes(id)) this.selected.push(id);
                } else {
                    this.selected = this.selected.filter(x => x !== id);
                }
            },
            async testApi() {
                this.loading = true;
                try {
                    const res = await fetch(this.healthRoute, { headers: { Accept: 'application/json' } });
                    this.health = await res.json();
                } catch (e) {
                    this.health = { ok: false, message: 'Erreur réseau côté navigateur', latency_ms: 0 };
                } finally {
                    this.loading = false;
                }
            }
        };
    }
</script>
@endsection
