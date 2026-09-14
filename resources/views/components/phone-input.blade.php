{{--
    Saisie d'un numéro de téléphone avec indicatif explicite.

    Le pays est choisi à la source (le numéro international est recomposé côté
    serveur à partir de {name}_country + {name}_national). Amélioration
    progressive :
      - SANS JavaScript : un <select> natif complet reste utilisable.
      - AVEC JavaScript : un sélecteur cherchable avec vrais drapeaux (flagcdn),
        qui pilote ce même <select> — donc l'envoi serveur est identique.

    @props :
      name      nom du champ (le contrôleur lit {name}_country et {name}_national)
      value     numéro international existant, pour pré-remplir
      label     libellé affiché
      required  champ obligatoire
      hint      texte d'aide sous le champ
--}}
@props([
    'name'     => 'phone',
    'value'    => null,
    'label'    => 'Numéro WhatsApp',
    'required' => false,
    'hint'     => null,
])

@php
    use App\Support\DialCodes;
    use App\Support\Phone;

    $isoActuel = old($name . '_country') ?: ($value ? DialCodes::guessIso($value) : DialCodes::DEFAULT);

    $nationalActuel = old($name . '_national');
    if ($nationalActuel === null && $value) {
        $normalise = Phone::normalize($value);
        $indicatif = DialCodes::code($isoActuel);
        $nationalActuel = $normalise && str_starts_with($normalise, $indicatif)
            ? substr($normalise, strlen($indicatif))
            : $normalise;
    }

    $champId = $name . '-' . uniqid();

    // Liste passée à Alpine : iso, indicatif, nom.
    $countriesJs = collect(DialCodes::COUNTRIES)
        ->map(fn ($p, $iso) => ['iso' => $iso, 'code' => (string) $p['code'], 'name' => $p['name']])
        ->values();
@endphp

<div class="ka-phone" x-data="kaPhoneInput(@js($countriesJs), '{{ $isoActuel }}')">
    <label class="ka-phone-label" for="{{ $champId }}">
        {{ $label }}
        @if (! $required)
            <span class="ka-phone-opt">(facultatif)</span>
        @endif
    </label>

    <div class="ka-phone-row">
        <div class="ka-phone-country-wrap">
            {{-- Champ réel soumis au serveur. Piloté par Alpine (x-model) quand
                 JS est là ; utilisable seul comme <select> natif sinon. --}}
            <select name="{{ $name }}_country" x-ref="native" x-model="iso"
                    aria-label="Indicatif du pays"
                    class="ka-phone-country ka-phone-native" :class="ready && 'ka-hidden'">
                @foreach (DialCodes::COUNTRIES as $iso => $pays)
                    <option value="{{ $iso }}" @selected($iso === $isoActuel)>
                        +{{ $pays['code'] }} · {{ $pays['name'] }}
                    </option>
                @endforeach
            </select>

            {{-- Sélecteur enrichi (drapeaux + recherche), affiché seulement si JS. --}}
            <div x-show="ready" x-cloak class="ka-phone-picker">
                <button type="button" class="ka-phone-trigger" @click="toggle()"
                        :aria-expanded="open" aria-haspopup="listbox">
                    <img class="ka-flag" :src="flag(iso)" :alt="current.name" width="22" height="16" loading="lazy">
                    <span class="ka-phone-dial" x-text="'+' + current.code"></span>
                    <svg class="ka-phone-caret" :class="open && 'rotate-180'" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <div x-show="open" x-cloak @click.outside="open=false" @keydown.escape="open=false"
                     class="ka-phone-dropdown">
                    <input type="text" x-model="search" x-ref="search"
                           placeholder="Rechercher un pays ou un indicatif…"
                           class="ka-phone-search" @keydown.enter.prevent="selectFirst()">
                    <ul class="ka-phone-list" role="listbox">
                        <template x-for="c in filtered" :key="c.iso">
                            <li role="option" :aria-selected="c.iso === iso"
                                @click="choose(c)"
                                class="ka-phone-optrow" :class="c.iso === iso && 'is-active'">
                                <img class="ka-flag" :src="flag(c.iso)" :alt="c.name" width="22" height="16" loading="lazy">
                                <span class="ka-phone-optname" x-text="c.name"></span>
                                <span class="ka-phone-optdial" x-text="'+' + c.code"></span>
                            </li>
                        </template>
                        <li x-show="filtered.length === 0" class="ka-phone-empty">Aucun pays trouvé</li>
                    </ul>
                </div>
            </div>
        </div>

        <input type="tel" id="{{ $champId }}" name="{{ $name }}_national"
               value="{{ $nationalActuel }}"
               inputmode="numeric" autocomplete="tel-national"
               placeholder="066 87 65 43"
               @required($required)
               class="ka-phone-number">
    </div>

    @if ($hint)
        <p class="ka-phone-hint">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="ka-phone-error">{{ $message }}</p>
    @enderror
</div>

@once
<style>
    .ka-phone { display: block; }
    [x-cloak] { display: none !important; }
    .ka-phone-label {
        display: block; font-size: 12px; font-weight: 800; color: #475569;
        text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px;
    }
    .ka-phone-opt { font-weight: 600; color: #94A3B8; text-transform: none; letter-spacing: 0; }

    .ka-phone-row { display: grid; grid-template-columns: 1fr; gap: 8px; }
    @media (min-width: 420px) { .ka-phone-row { grid-template-columns: minmax(0, 44%) 1fr; } }

    .ka-phone-country-wrap { position: relative; }
    .ka-phone-country, .ka-phone-number, .ka-phone-trigger {
        width: 100%; min-height: 46px; padding: 12px 14px;
        border: 1px solid #CBD5E1; border-radius: 12px;
        font-size: 15px; font-family: inherit; color: #0F172A; background: #fff;
    }
    .ka-phone-number { font-variant-numeric: tabular-nums; letter-spacing: .02em; }
    .ka-hidden { display: none !important; }

    /* Déclencheur enrichi */
    .ka-phone-trigger {
        display: flex; align-items: center; gap: 8px; cursor: pointer; text-align: left;
    }
    .ka-phone-dial { font-weight: 700; }
    .ka-phone-caret { margin-left: auto; color: #94A3B8; transition: transform .15s; flex-shrink: 0; }
    .ka-flag { border-radius: 3px; box-shadow: 0 0 0 1px rgba(0,0,0,.08); object-fit: cover; flex-shrink: 0; }

    .ka-phone-country:focus, .ka-phone-number:focus, .ka-phone-trigger:focus {
        outline: none; border-color: #44A08D; box-shadow: 0 0 0 3px rgba(68,160,141,.14);
    }

    /* Dropdown */
    .ka-phone-dropdown {
        position: absolute; z-index: 60; top: calc(100% + 6px); left: 0; right: 0;
        background: #fff; border: 1px solid #E2E8F0; border-radius: 14px;
        box-shadow: 0 18px 40px -12px rgba(15,23,42,.25); overflow: hidden;
        min-width: 260px;
    }
    .ka-phone-search {
        width: 100%; border: none; border-bottom: 1px solid #EEF2F6;
        padding: 12px 14px; font-size: 14px; font-family: inherit; color: #0F172A;
    }
    .ka-phone-search:focus { outline: none; background: #F8FAFC; }
    .ka-phone-list { list-style: none; margin: 0; padding: 6px; max-height: 260px; overflow-y: auto; }
    .ka-phone-optrow {
        display: flex; align-items: center; gap: 10px; padding: 9px 10px;
        border-radius: 9px; cursor: pointer;
    }
    .ka-phone-optrow:hover { background: #F1F5F9; }
    .ka-phone-optrow.is-active { background: #ECFDF5; }
    .ka-phone-optname { flex: 1; min-width: 0; font-size: 14px; color: #0F172A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ka-phone-optdial { font-size: 13px; font-weight: 700; color: #64748B; font-variant-numeric: tabular-nums; }
    .ka-phone-empty { padding: 14px; text-align: center; color: #94A3B8; font-size: 13px; }

    .ka-phone-hint  { font-size: 11.5px; color: #64748B; margin: 6px 0 0; line-height: 1.5; }
    .ka-phone-error { font-size: 12px; color: #B91C1C; font-weight: 700; margin: 6px 0 0; }
</style>

<script>
    function kaPhoneInput(countries, initialIso) {
        return {
            countries: countries,
            iso: initialIso,
            open: false,
            search: '',
            ready: false,
            init() { this.ready = true; },
            get current() {
                return this.countries.find(c => c.iso === this.iso) || this.countries[0] || { iso: '', code: '', name: '' };
            },
            get filtered() {
                const q = this.search.trim().toLowerCase().replace(/^\+/, '');
                if (!q) return this.countries;
                return this.countries.filter(c =>
                    c.name.toLowerCase().includes(q) ||
                    c.code.includes(q) ||
                    c.iso.toLowerCase().includes(q)
                );
            },
            // Drapeau SVG depuis flagcdn (fiable sur toutes les plateformes,
            // contrairement aux emojis-drapeaux non rendus sous Windows).
            flag(iso) { return 'https://flagcdn.com/' + String(iso).toLowerCase() + '.svg'; },
            toggle() {
                this.open = !this.open;
                if (this.open) this.$nextTick(() => this.$refs.search && this.$refs.search.focus());
            },
            choose(c) { this.iso = c.iso; this.open = false; this.search = ''; },
            selectFirst() { const f = this.filtered; if (f.length) this.choose(f[0]); },
        };
    }
</script>
@endonce
