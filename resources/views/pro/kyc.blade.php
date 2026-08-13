@extends('layouts.app')

@section('title', 'Votre dossier — KardAfrica')

@section('content')
<div class="bg-[#FAFAF7] min-h-screen pb-20">
    <div class="max-w-xl mx-auto px-4 sm:px-6 pt-10 md:pt-14">

        @include('pro._stepper', ['current' => 3])

        <div class="bg-white rounded-2xl p-6 md:p-8 border border-slate-100 shadow-card mt-6">
            <h1 class="font-display text-2xl font-bold text-slate-900">Complétez votre dossier</h1>
            <p class="text-sm text-slate-500 mt-1.5">Étape 3 sur 3 — après validation, votre accès provisoire est activé immédiatement.</p>

            @if($owner->status === \App\Models\CardOwner::STATUS_DOCS_REQUESTED && $owner->docs_requested_note)
                <div class="mt-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm px-4 py-3">
                    <span class="font-semibold">Pièces demandées par l'équipe :</span> {{ $owner->docs_requested_note }}
                </div>
            @endif

            <form method="POST" action="{{ route('pro.kyc.submit') }}" enctype="multipart/form-data" class="mt-6 space-y-4" x-data="{}">
                @csrf

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Ville</label>
                        <input type="text" name="city" value="{{ old('city', $owner->city) }}" required
                               class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#44A08D]/40 focus:border-[#44A08D]">
                        @error('city')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Quartier</label>
                        <input type="text" name="quartier" value="{{ old('quartier', $owner->quartier) }}" required
                               class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#44A08D]/40 focus:border-[#44A08D]">
                        @error('quartier')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Adresse (optionnel)</label>
                    <input type="text" name="address" value="{{ old('address', $owner->address) }}"
                           class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#44A08D]/40 focus:border-[#44A08D]">
                </div>

                {{-- Géolocalisation optionnelle --}}
                <div class="rounded-xl border border-slate-200 p-4 bg-slate-50/60">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-slate-700">Position de l'établissement (optionnel)</div>
                            <div class="text-xs text-slate-500 mt-0.5" id="geo-status">Aide-nous à localiser votre boutique.</div>
                        </div>
                        <button type="button" id="geo-btn"
                                class="shrink-0 inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-white border border-slate-200 text-sm font-semibold text-[#44A08D] hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Me localiser
                        </button>
                    </div>
                    <input type="hidden" name="geo_lat" id="geo_lat" value="{{ old('geo_lat', $owner->geo_lat) }}">
                    <input type="hidden" name="geo_lng" id="geo_lng" value="{{ old('geo_lng', $owner->geo_lng) }}">
                </div>

                {{-- Uploads --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Photo de la pièce d'identité du gérant</label>
                    <input type="file" name="id_document" accept="image/*" required
                           class="w-full text-sm text-slate-600 file:mr-3 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:bg-[#44A08D]/10 file:text-[#44A08D] file:font-semibold file:cursor-pointer">
                    <p class="text-xs text-slate-400 mt-1">JPG, PNG ou WEBP — 4 Mo max. Document confidentiel, stocké de façon sécurisée.</p>
                    @error('id_document')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Fiche circuit / justificatif d'entreprise (RCCM, patente…)</label>
                    <input type="file" name="business_document" accept="image/*" required
                           class="w-full text-sm text-slate-600 file:mr-3 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:bg-[#44A08D]/10 file:text-[#44A08D] file:font-semibold file:cursor-pointer">
                    <p class="text-xs text-slate-400 mt-1">JPG, PNG ou WEBP — 4 Mo max.</p>
                    @error('business_document')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="w-full mt-2 inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl bg-[#44A08D] hover:bg-[#3d9180] text-white font-semibold shadow-lg shadow-[#44A08D]/40 transition-all active:scale-[0.99]">
                    Envoyer mon dossier &amp; activer mon accès
                </button>
                <p class="text-xs text-slate-400 text-center">Vos pièces ne sont visibles que par notre équipe de validation.</p>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const btn = document.getElementById('geo-btn');
        const status = document.getElementById('geo-status');
        if (!btn) return;
        btn.addEventListener('click', function () {
            if (!navigator.geolocation) { status.textContent = 'Géolocalisation non supportée par ce navigateur.'; return; }
            status.textContent = 'Localisation en cours…';
            navigator.geolocation.getCurrentPosition(function (pos) {
                document.getElementById('geo_lat').value = pos.coords.latitude.toFixed(7);
                document.getElementById('geo_lng').value = pos.coords.longitude.toFixed(7);
                status.textContent = '✓ Position enregistrée.';
            }, function () {
                status.textContent = 'Impossible de récupérer votre position. Vous pouvez continuer sans.';
            }, { enableHighAccuracy: true, timeout: 8000 });
        });
    })();
</script>
@endpush
