{{--
    Saisie d'un numéro de téléphone avec indicatif explicite.

    Le sélecteur n'est pas un ornement : sans lui, « 0612345678 » est
    indistinguable d'une ligne française et d'une saisie locale mal formée, et
    aucune heuristique ne peut trancher après coup. Le pays est donc choisi à la
    source, et le numéro international est recomposé côté serveur — le
    formulaire reste juste même sans JavaScript.

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

    // Reprise après erreur de validation, puis valeur existante, puis défaut.
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
@endphp

<div class="ka-phone">
    <label class="ka-phone-label" for="{{ $champId }}">
        {{ $label }}
        @if (! $required)
            <span class="ka-phone-opt">(facultatif)</span>
        @endif
    </label>

    <div class="ka-phone-row">
        <select name="{{ $name }}_country" class="ka-phone-country"
                aria-label="Indicatif du pays">
            @foreach (DialCodes::COUNTRIES as $iso => $pays)
                <option value="{{ $iso }}" @selected($iso === $isoActuel)>
                    {{ $pays['flag'] }} +{{ $pays['code'] }} · {{ $pays['name'] }}
                </option>
            @endforeach
        </select>

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
@push('head')
<style>
    .ka-phone { display: block; }
    .ka-phone-label {
        display: block; font-size: 12px; font-weight: 800; color: #475569;
        text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px;
    }
    .ka-phone-opt { font-weight: 600; color: #94A3B8; text-transform: none; letter-spacing: 0; }

    /* Le pays au-dessus sur mobile : côte à côte, le sélecteur écrase le champ
       numéro sur les petits écrans, qui sont la cible principale. */
    .ka-phone-row { display: grid; grid-template-columns: 1fr; gap: 8px; }
    @media (min-width: 420px) { .ka-phone-row { grid-template-columns: minmax(0, 42%) 1fr; } }

    .ka-phone-country, .ka-phone-number {
        width: 100%; min-height: 46px; padding: 12px 14px;
        border: 1px solid #CBD5E1; border-radius: 12px;
        font-size: 15px; font-family: inherit; color: #0F172A; background: #fff;
    }
    .ka-phone-country { font-size: 14px; }
    .ka-phone-number { font-variant-numeric: tabular-nums; letter-spacing: .02em; }
    .ka-phone-country:focus, .ka-phone-number:focus {
        outline: none; border-color: #44A08D; box-shadow: 0 0 0 3px rgba(68,160,141,.14);
    }
    .ka-phone-hint  { font-size: 11.5px; color: #64748B; margin: 6px 0 0; line-height: 1.5; }
    .ka-phone-error { font-size: 12px; color: #B91C1C; font-weight: 700; margin: 6px 0 0; }
</style>
@endpush
@endonce
