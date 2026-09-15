{{--
    Choix du numéro de destination d'un remboursement (client ou admin).

    Deux options :
      - « Numéro du compte » : numéro enregistré à la création du compte.
      - « Autre numéro »     : saisie libre (indicatif + national).

    Détection automatique de l'opérateur Mobile Money gabonais à la frappe :
      - Airtel Money : 077, 074, 076
      - Moov Money   : 066, 062, 0065

    Champs soumis :
      {name}_mode     → 'account' | 'other'
      {name}_country  → iso (ex. GA)
      {name}_national → numéro national (ex. 077 12 34 56)

    Styles inline : fonctionne dans les vues client (Tailwind) comme admin (inline).

    @props :
      name           préfixe des champs (défaut 'refund_phone')
      accountDisplay affichage du numéro du compte (ex. +241 06 87 65 43)
      accountOperator opérateur du numéro de compte (ex. Airtel Money) ou null
--}}
@props([
    'name'            => 'refund_phone',
    'accountDisplay'  => null,
    'accountOperator' => null,
])

@php
    use App\Support\DialCodes;
    $countriesJs = collect(DialCodes::COUNTRIES)
        ->map(fn ($p, $iso) => ['iso' => $iso, 'code' => (string) $p['code'], 'name' => $p['name']])
        ->values();
@endphp

<div class="ka-rfo" style="margin-top:14px;padding-top:14px;border-top:1px solid #E2E8F0;"
     x-data="kaRefundPhone(@js($countriesJs), {{ json_encode(DialCodes::DEFAULT) }})">

    <input type="hidden" name="{{ $name }}_mode" :value="mode">

    <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#64748B;margin-bottom:8px;">
        Où renvoyer l'argent&nbsp;?
    </div>

    {{-- Option 1 : numéro du compte --}}
    <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border:1.5px solid #CBD5E1;border-radius:12px;cursor:pointer;background:#fff;margin-bottom:6px;"
           :style="mode === 'account' && {'border-color':'#44A08D','box-shadow':'0 0 0 3px rgba(68,160,141,.14)'}">
        <input type="radio" name="{{ $name }}_choice" value="account" x-model="mode"
               style="margin-top:3px;accent-color:#44A08D;width:16px;height:16px;">
        <span style="min-width:0;">
            <span style="font-size:13px;font-weight:700;color:#0F172A;display:block;">Numéro du compte</span>
            <span style="font-size:12px;color:#475569;display:block;margin-top:2px;">
                {{ $accountDisplay ?: '—' }}
                @if($accountOperator)
                    <span style="display:inline-block;margin-left:6px;padding:1px 7px;border-radius:9999px;font-size:10px;font-weight:700;
                        {{ $accountOperator === 'Airtel Money' ? 'background:#F0FDF4;color:#15803D;' : 'background:#FFF7ED;color:#C2410C;' }}">
                        {{ $accountOperator }}
                    </span>
                @endif
            </span>
        </span>
    </label>

    {{-- Option 2 : autre numéro --}}
    <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border:1.5px solid #CBD5E1;border-radius:12px;cursor:pointer;background:#fff;"
           :style="mode === 'other' && {'border-color':'#44A08D','box-shadow':'0 0 0 3px rgba(68,160,141,.14)'}">
        <input type="radio" name="{{ $name }}_choice" value="other" x-model="mode"
               style="margin-top:3px;accent-color:#44A08D;width:16px;height:16px;">
        <span style="min-width:0;">
            <span style="font-size:13px;font-weight:700;color:#0F172A;display:block;">Autre numéro</span>
            <span style="font-size:12px;color:#475569;display:block;margin-top:2px;">Renseigner un autre numéro Mobile Money</span>
        </span>
    </label>

    {{-- Champs « autre numéro » --}}
    <div x-show="mode === 'other'" x-cloak style="margin-top:10px;">
        <div style="display:grid;grid-template-columns:minmax(0,44%) 1fr;gap:8px;">
            <select name="{{ $name }}_country" x-model="iso"
                    aria-label="Indicatif du pays"
                    style="min-height:44px;padding:10px 12px;border:1px solid #CBD5E1;border-radius:11px;font-size:14px;font-family:inherit;color:#0F172A;background:#fff;">
                @foreach (DialCodes::COUNTRIES as $iso => $pays)
                    <option value="{{ $iso }}">+{{ $pays['code'] }} · {{ $pays['name'] }}</option>
                @endforeach
            </select>
            <input type="tel" name="{{ $name }}_national" x-model="national" @input="detect(national)"
                   inputmode="numeric" autocomplete="tel-national" placeholder="066 00 00 00"
                   style="min-height:44px;padding:10px 12px;border:1px solid #CBD5E1;border-radius:11px;font-size:14px;font-family:inherit;color:#0F172A;background:#fff;font-variant-numeric:tabular-nums;">
        </div>

        {{-- Opérateur détecté --}}
        <div x-show="operator" x-cloak style="margin-top:8px;display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:9999px;font-size:11px;font-weight:700;"
             :style="operator === 'Airtel Money' ? 'background:#F0FDF4;color:#15803D;' : 'background:#FFF7ED;color:#C2410C;'">
            <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            <span x-text="operator"></span>
        </div>

        {{-- Numéro incomplet / non reconnu --}}
        <div x-show="!operator && digitsLength >= 3" x-cloak
             style="margin-top:8px;font-size:11.5px;color:#B45309;font-weight:600;">
            Numéro non reconnu — vérifie les préfixes (Airtel&nbsp;: 077/074/076 · Moov&nbsp;: 066/062/0065).
        </div>
    </div>
</div>

@once
<style>
    .ka-rfo [x-cloak] { display: none !important; }
</style>
<script>
    function kaRefundPhone(countries, initialIso) {
        const AIRTEL = ['077', '074', '076'];
        const MOOV   = ['066', '062', '065', '0065'];
        return {
            mode: 'account',
            iso: initialIso,
            national: '',
            operator: null,
            digitsLength: 0,
            detect(raw) {
                const digits = (raw || '').replace(/\D/g, '');
                this.digitsLength = digits.length;
                const candidates = digits.startsWith('0') ? [digits] : ['0' + digits, digits];
                let found = null;
                for (const c of candidates) {
                    if (AIRTEL.some(p => c.startsWith(p))) { found = 'Airtel Money'; break; }
                    if (MOOV.some(p => c.startsWith(p)))   { found = 'Moov Money';   break; }
                }
                this.operator = found;
            },
        };
    }
</script>
@endonce