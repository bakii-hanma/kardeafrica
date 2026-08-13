@extends('admin.layouts.admin')

@section('title', 'Versements commerçants')
@section('page-title', 'Versements')
@section('export-url', route('admin.versements.export', request()->only('onglet')))

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', ' ');

    $base = request()->except(['onglet', 'page']);

    $onglets = collect([
        ['key' => 'du',     'label' => 'À payer'],
        ['key' => 'soldes', 'label' => 'Soldés'],
        ['key' => 'tous',   'label' => 'Tous'],
    ])->map(fn ($t) => $t + [
        'count'  => $compteurs[$t['key']],
        'url'    => route('admin.versements.index', $base + ['onglet' => $t['key']]),
        'active' => $onglet === $t['key'],
    ])->all();

    $filtre = $recherche !== '' || $onglet !== 'du';
@endphp

<x-admin.list-screen
    title="Versements"
    :total="$lignes->count()"
    :tabs="$onglets"
    :search="$recherche"
    placeholder="Commerçant, contact, téléphone…"
    :dates="false"
    :filtered="$filtre"
    :reset-url="route('admin.versements.index')">

    {{-- Synthèse : calculée sur TOUS les commerçants, pas sur l'onglet — sinon
         le « total à payer » changerait en cliquant sur un filtre. --}}
    <div class="lst-summary">
        <x-ui.card variant="highlight" class="lst-sum-card">
            <x-ui.stat-number :value="$total" label="À payer maintenant" />
            <p class="lst-sum-meta">
                {{ $compteurs['du'] }} commerçant{{ $compteurs['du'] > 1 ? 's' : '' }} ·
                prochain lundi {{ $prochainLundi->format('d/m') }} : {{ $fmt($aVenir) }} FCFA
            </p>
        </x-ui.card>

        <x-ui.card variant="inset" class="lst-sum-card">
            <x-ui.stat-number :value="$totalVerse" label="Déjà versé" />
            <p class="lst-sum-meta">
                Cumul de tous les règlements enregistrés. Les virements Mobile Money
                se font hors de l'application.
            </p>
        </x-ui.card>
    </div>

    @if (session('success'))
        <div class="lst-flash">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="lst-flash lst-flash--err" role="alert">{{ $errors->first() }}</div>
    @endif

    @if ($lignes->isEmpty())
        <x-ui.empty-state :label="$recherche !== ''
            ? 'Aucun résultat pour « ' . $recherche . ' ».'
            : match ($onglet) {
                'soldes' => 'Aucun commerçant soldé pour l\'instant.',
                'tous'   => 'Aucun commerçant enregistré.',
                default  => 'Rien à verser aujourd\'hui. Les ventes de la semaine partiront le lundi ' . $prochainLundi->format('d/m') . '.',
            }" />
    @else
        <div class="lst-wrap">
            <table class="lst-table">
                <thead>
                    <tr>
                        <th>Commerçant</th>
                        <th class="r">Exigible</th>
                        <th class="r">Déjà versé</th>
                        <th class="r">À payer</th>
                        <th class="r">Semaine en cours</th>
                        <th>Enregistrer le versement</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lignes as $l)
                        <tr>
                            <td>
                                <a href="{{ route('admin.proprietaires.show', $l->id) }}" class="lst-cell-link">
                                    <x-admin.cell-user :name="$l->business_name"
                                        :sub="\App\Support\Phone::display($l->phone) . ($l->city ? ' · ' . $l->city : '')" />
                                </a>
                                @if ($l->dernier_versement)
                                    <span class="lst-ref-sub">dernier versement le {{ \Illuminate\Support\Carbon::parse($l->dernier_versement)->format('d/m/Y') }}</span>
                                @endif
                            </td>
                            <td class="r"><x-admin.cell-amount :value="$l->exigible" unit="" /></td>
                            <td class="r"><span class="lst-paid">{{ $fmt($l->verse) }}</span></td>
                            <td class="r"><x-admin.cell-amount :value="max(0, (float) $l->solde)" unit="" /></td>
                            <td class="r"><span class="lst-soon">{{ $fmt($l->a_venir) }}</span></td>
                            <td>
                                @if ((float) $l->solde > 0)
                                    {{-- Action existante, conservée à l'identique. --}}
                                    <form method="POST" action="{{ route('admin.versements.store') }}" class="lst-inline-form">
                                        @csrf
                                        <input type="hidden" name="card_owner_id" value="{{ $l->id }}">
                                        <input type="number" name="amount" step="1" min="1"
                                               value="{{ (int) $l->solde }}" class="lst-in lst-in--amount" aria-label="Montant">
                                        <select name="method" class="lst-in" aria-label="Moyen">
                                            @foreach (\App\Models\MerchantSettlement::METHODS as $k => $lib)
                                                <option value="{{ $k }}">{{ $lib }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="reference" placeholder="Réf." class="lst-in lst-in--ref" aria-label="Référence">
                                        <button type="submit" class="lst-apply">Versé</button>
                                    </form>
                                @else
                                    <x-ui.pill status="completed">Soldé</x-ui.pill>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-admin.list-screen>
@endsection
