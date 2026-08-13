{{-- ================================================================
     Bascule du mode de vente, en tête des deux écrans de vente.
     Remplace l'onglet « Cartes locales » de la barre de navigation : le
     revendeur choisit désormais ce qu'il vend au moment de vendre.

     Paramètre : $mode = 'digital' | 'local'
     ================================================================ --}}
<nav class="vmode" aria-label="Type de carte à vendre">
    <a href="{{ route('vendor.sell') }}"
       class="vmode-seg {{ $mode === 'digital' ? 'is-on' : '' }}"
       @if($mode === 'digital') aria-current="page" @endif>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
        </svg>
        <span>
            Cartes digitales
            <small>Netflix, Steam, Apple…</small>
        </span>
    </a>

    <a href="{{ route('vendor.local-cards.index') }}"
       class="vmode-seg {{ $mode === 'local' ? 'is-on' : '' }}"
       @if($mode === 'local') aria-current="page" @endif>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h8M12 8v8"/>
        </svg>
        <span>
            Carte Gabon
            <small>Commerçants de Libreville</small>
        </span>
    </a>
</nav>

@once
    @push('head')
    <style>
        .vmode {
            display: grid; grid-template-columns: 1fr 1fr; gap: 6px;
            background: #EEF2F6; border-radius: 14px; padding: 5px;
            margin-bottom: 16px;
        }
        .vmode-seg {
            display: flex; align-items: center; justify-content: center; gap: 9px;
            min-height: 56px; padding: 8px 10px;
            border-radius: 10px;
            color: #475569; text-decoration: none;
            font-size: 14px; font-weight: 700;
            text-align: left;
            transition: background .15s ease, color .15s ease, box-shadow .15s ease;
        }
        .vmode-seg svg { width: 19px; height: 19px; flex-shrink: 0; }
        .vmode-seg small {
            display: block; font-size: 11px; font-weight: 500;
            color: #64748B; margin-top: 1px;
        }
        .vmode-seg:hover { color: #0F172A; }
        .vmode-seg.is-on {
            background: #fff; color: #0F172A;
            box-shadow: 0 1px 3px rgba(15,23,42,.12), 0 0 0 1px rgba(15,23,42,.05);
        }
        .vmode-seg.is-on svg { color: #0F766E; }
        .vmode-seg:focus-visible { outline: 3px solid #4ECDC4; outline-offset: 2px; }
        /* Sous 420px, on garde le libellé principal et on masque le sous-titre */
        @media (max-width: 419px) {
            .vmode-seg { gap: 7px; font-size: 13px; }
            .vmode-seg small { display: none; }
        }
    </style>
    @endpush
@endonce
