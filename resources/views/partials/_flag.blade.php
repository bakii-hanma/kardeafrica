{{--
    Drapeaux / globes en SVG inline.
    Raison : les emojis-drapeaux (🇫🇷 …) ne sont PAS rendus par Windows
    (Chrome/Edge/Firefox), qui affiche à la place les 2 lettres « FR ».
    On rend donc de vrais SVG, identiques sur toutes les plateformes.

    Usage : @include('partials._flag', ['code' => 'fr'])
    Codes : fr, eu, us, africa, global  (fallback = globe neutre).
--}}
@php $code = strtolower($code ?? ''); @endphp
@switch($code)
    @case('fr')
        <svg viewBox="0 0 3 2" width="20" height="14" class="rounded-[3px] shrink-0 ring-1 ring-black/10" aria-hidden="true">
            <rect width="1" height="2" x="0" fill="#002654"/>
            <rect width="1" height="2" x="1" fill="#ffffff"/>
            <rect width="1" height="2" x="2" fill="#ED2939"/>
        </svg>
        @break

    @case('us')
        <svg viewBox="0 0 20 14" width="20" height="14" class="rounded-[3px] shrink-0 ring-1 ring-black/10" aria-hidden="true">
            <rect width="20" height="14" fill="#B22234"/>
            <g fill="#ffffff">
                <rect y="2"  width="20" height="1.55"/>
                <rect y="5"  width="20" height="1.55"/>
                <rect y="8"  width="20" height="1.55"/>
                <rect y="11" width="20" height="1.55"/>
            </g>
            <rect width="9" height="7.5" fill="#3C3B6E"/>
            <g fill="#ffffff">
                <circle cx="2" cy="1.8" r="0.5"/><circle cx="4.5" cy="1.8" r="0.5"/><circle cx="7" cy="1.8" r="0.5"/>
                <circle cx="3.25" cy="3.6" r="0.5"/><circle cx="5.75" cy="3.6" r="0.5"/>
                <circle cx="2" cy="5.4" r="0.5"/><circle cx="4.5" cy="5.4" r="0.5"/><circle cx="7" cy="5.4" r="0.5"/>
            </g>
        </svg>
        @break

    @case('eu')
        <svg viewBox="0 0 20 14" width="20" height="14" class="rounded-[3px] shrink-0 ring-1 ring-black/10" aria-hidden="true">
            <rect width="20" height="14" fill="#003399"/>
            <g fill="#FFCC00">
                <circle cx="10"    cy="2.8"  r="0.6"/>
                <circle cx="12.1"  cy="3.36" r="0.6"/>
                <circle cx="13.64" cy="4.9"  r="0.6"/>
                <circle cx="14.2"  cy="7"    r="0.6"/>
                <circle cx="13.64" cy="9.1"  r="0.6"/>
                <circle cx="12.1"  cy="10.64" r="0.6"/>
                <circle cx="10"    cy="11.2" r="0.6"/>
                <circle cx="7.9"   cy="10.64" r="0.6"/>
                <circle cx="6.36"  cy="9.1"  r="0.6"/>
                <circle cx="5.8"   cy="7"    r="0.6"/>
                <circle cx="6.36"  cy="4.9"  r="0.6"/>
                <circle cx="7.9"   cy="3.36" r="0.6"/>
            </g>
        </svg>
        @break

    @case('africa')
        {{-- 🌍 Globe Europe-Afrique --}}
        <svg viewBox="0 0 20 20" width="16" height="16" class="shrink-0" aria-hidden="true">
            <circle cx="10" cy="10" r="8" fill="none" stroke="#16a34a" stroke-width="1.5"/>
            <ellipse cx="10" cy="10" rx="3.3" ry="8" fill="none" stroke="#16a34a" stroke-width="1.2"/>
            <line x1="2" y1="10" x2="18" y2="10" stroke="#16a34a" stroke-width="1.2"/>
            <line x1="3.4" y1="5.5" x2="16.6" y2="5.5" stroke="#16a34a" stroke-width="1"/>
            <line x1="3.4" y1="14.5" x2="16.6" y2="14.5" stroke="#16a34a" stroke-width="1"/>
        </svg>
        @break

    @case('global')
        {{-- 🌐 Globe méridiens --}}
        <svg viewBox="0 0 20 20" width="16" height="16" class="shrink-0" aria-hidden="true">
            <circle cx="10" cy="10" r="8" fill="none" stroke="#2563eb" stroke-width="1.5"/>
            <ellipse cx="10" cy="10" rx="3.3" ry="8" fill="none" stroke="#2563eb" stroke-width="1.2"/>
            <line x1="2" y1="10" x2="18" y2="10" stroke="#2563eb" stroke-width="1.2"/>
            <line x1="3.4" y1="5.5" x2="16.6" y2="5.5" stroke="#2563eb" stroke-width="1"/>
            <line x1="3.4" y1="14.5" x2="16.6" y2="14.5" stroke="#2563eb" stroke-width="1"/>
        </svg>
        @break

    @default
        <svg viewBox="0 0 20 20" width="16" height="16" class="shrink-0" aria-hidden="true">
            <circle cx="10" cy="10" r="8" fill="none" stroke="#64748b" stroke-width="1.5"/>
            <ellipse cx="10" cy="10" rx="3.3" ry="8" fill="none" stroke="#64748b" stroke-width="1.2"/>
            <line x1="2" y1="10" x2="18" y2="10" stroke="#64748b" stroke-width="1.2"/>
        </svg>
@endswitch
