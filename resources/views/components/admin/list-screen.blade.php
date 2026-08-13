{{--
    Écran liste « layered » — enveloppe commune aux écrans transactionnels
    (Commandes, Livraisons, Paiements, Versements, Cartes).

    Ne porte AUCUNE logique métier : elle reçoit des onglets déjà calculés par
    la page et se contente de la mise en forme. Les filtres restent des query
    params, comme le sélecteur de période du P1 — recharger l'URL restaure
    l'onglet, la recherche et les dates.

    @props
      title       titre watermark
      total       nombre d'éléments filtrés (Pill count à côté du titre)
      tabs        [['key','label','count','url','active'], …] — count null = pas de badge
      searchName  nom du champ de recherche (défaut « search »)
      search      valeur courante
      placeholder texte du champ
      dates       afficher le filtre de plage de dates
      exportUrl   URL d'export si l'action EXISTE déjà, sinon null
      resetUrl    URL de réinitialisation des filtres
      filtered    au moins un filtre est actif (pilote l'état vide)
--}}
@props([
    'title',
    'total' => null,
    'tabs' => [],
    'searchName' => 'search',
    'search' => null,
    'placeholder' => 'Rechercher…',
    'dates' => true,
    'exportUrl' => null,
    'resetUrl' => null,
    'filtered' => false,
])

<x-ui.card class="lst">

    <div class="lst-head">
        <h1 class="lst-title">{{ $title }}</h1>
        @if ($total !== null)
            <x-ui.pill variant="count" class="lst-total">{{ number_format($total, 0, ',', ' ') }}</x-ui.pill>
        @endif
        @if ($filtered && $resetUrl)
            <a href="{{ $resetUrl }}" class="lst-reset">Réinitialiser les filtres</a>
        @endif
    </div>

    <div class="lst-toolbar">
        @if (! empty($tabs))
            <nav class="ui-tabs lst-tabs" aria-label="Filtrer par statut">
                @foreach ($tabs as $t)
                    <a href="{{ $t['url'] }}" class="ui-tab {{ ! empty($t['active']) ? 'is-active' : '' }}"
                       @if(! empty($t['active'])) aria-current="page" @endif>
                        {{ $t['label'] }}
                        {{-- Un compteur à zéro n'affiche aucun badge. --}}
                        @if (isset($t['count']) && $t['count'] > 0)
                            <span class="lst-tab-n">{{ $t['count'] }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
        @endif

        <form method="GET" class="lst-filters" data-no-loader>
            {{-- Les filtres non pilotés par ce formulaire survivent à la soumission. --}}
            @foreach (request()->except([$searchName, 'date_from', 'date_to', 'page']) as $k => $v)
                @if (is_scalar($v))
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endif
            @endforeach

            <label class="lst-search">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="search" name="{{ $searchName }}" value="{{ $search }}" placeholder="{{ $placeholder }}">
            </label>

            @if ($dates)
                <label class="lst-date">
                    <span>Du</span>
                    <input type="date" name="date_from" value="{{ request('date_from') }}">
                </label>
                <label class="lst-date">
                    <span>Au</span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}">
                </label>
            @endif

            <button type="submit" class="lst-apply">Filtrer</button>
        </form>

        @if ($exportUrl)
            <a href="{{ $exportUrl }}" class="lst-export">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Exporter
            </a>
        @endif

        {{ $actions ?? '' }}
    </div>

    {{ $slot }}
</x-ui.card>
