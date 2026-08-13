{{-- ================================================================
     Logos des moyens de paiement — couleurs de marque d'origine (SVG inline).
     Params : $h (classe hauteur, def h-6 md:h-7) · $chip (bool : pastille blanche,
     utile sur fond sombre pour garder la lisibilité des logos colorés).
     Moov Money = fallback texte (aucun SVG officiel libre).
     ================================================================ --}}
@php
    $h    = $h ?? 'h-6 md:h-7';
    $chip = $chip ?? false;
    $wrap = $chip
        ? 'inline-flex items-center justify-center bg-white rounded-md px-2 py-1'
        : 'inline-flex items-center';
@endphp
<div class="flex flex-wrap items-center gap-x-6 gap-y-3">
    {{-- Airtel Money (rouge Airtel) --}}
    <span class="{{ $wrap }}">
        <svg class="{{ $h }} w-auto" viewBox="0 0 24 24" role="img" aria-label="Airtel Money" fill="#E40000" xmlns="http://www.w3.org/2000/svg">
            <path d="M7.137 23.862c.79 0 1.708-.19 2.751-.554 1.55-.538 2.784-1.281 3.986-2.009l.316-.205a29.733 29.733 0 0 0 3.764-2.72 16.574 16.574 0 0 0 5.457-7.529c.395-1.138.949-3.384.268-5.487a7.117 7.117 0 0 0-2.862-3.749c-.158-.126-1.898-1.47-5.203-1.47-3.005 0-6.31 1.107-9.806 3.32l-.11.08-.317.205a20.133 20.133 0 0 0-2.309 1.693C1.585 6.813-.091 9.106.004 11.067c.031.79.427 1.534 1.075 2.008a3.472 3.472 0 0 0 2.214.68c1.803 0 3.765-.948 5.109-1.74l.253-.157.696-.443.237-.158c1.898-1.234 3.875-2.515 6.105-3.258a5.255 5.255 0 0 1 1.55-.285 3.163 3.163 0 0 1 .664.08 2.112 2.112 0 0 1 1.47 1.106c.523 1.012.396 2.61-.316 4.08a17.871 17.871 0 0 1-4.887 5.836 19.488 19.488 0 0 1-3.194 2.215l-.095.031a9.634 9.634 0 0 1-1.471.696l-.08.032-.41.158c-2.23.57-.87-1.329-.87-1.329.474-.537.98-1.028 1.518-1.502.316-.269.633-.554.933-.854l.064-.063c.395-.38.933-.902.901-1.645-.047-.98-1.075-1.582-2.056-1.613h-.063c-.95 0-1.819.522-2.404.98a7.27 7.27 0 0 0-1.598 1.74c-.6.901-1.85 3.226-.632 5.077.49.743 1.313 1.123 2.42 1.123z"/>
        </svg>
    </span>

    {{-- Moov Money : fallback texte (bleu + orange Moov), même hauteur --}}
    <span class="{{ $wrap }}">
        <span class="font-display font-extrabold leading-none {{ str_contains($h, 'h-5') ? 'text-[13px]' : 'text-[15px] md:text-[17px]' }}" aria-label="Moov Money">
            <span style="color:#0033A0;">Moov</span><span style="color:#F58220;">Money</span>
        </span>
    </span>

    {{-- Visa (bleu Visa) --}}
    <span class="{{ $wrap }}">
        <svg class="{{ $h }} w-auto" viewBox="0 0 24 24" role="img" aria-label="Visa" fill="#1434CB" xmlns="http://www.w3.org/2000/svg">
            <path d="M9.112 8.262L5.97 15.758H3.92L2.374 9.775c-.094-.368-.175-.503-.461-.658C1.447 8.864.677 8.627 0 8.479l.046-.217h3.3a.904.904 0 01.894.764l.817 4.338 2.018-5.102zm8.033 5.049c.008-1.979-2.736-2.088-2.717-2.972.006-.269.262-.555.822-.628a3.66 3.66 0 011.913.336l.34-1.59a5.207 5.207 0 00-1.814-.333c-1.917 0-3.266 1.02-3.278 2.479-.012 1.079.963 1.68 1.698 2.04.756.367 1.01.603 1.006.931-.005.504-.602.725-1.16.734-.975.015-1.54-.263-1.992-.473l-.351 1.642c.453.208 1.289.39 2.156.398 2.037 0 3.37-1.006 3.377-2.564m5.061 2.447H24l-1.565-7.496h-1.656a.883.883 0 00-.826.55l-2.909 6.946h2.036l.405-1.12h2.488zm-2.163-2.656l1.02-2.815.588 2.815zm-8.16-4.84l-1.603 7.496H8.34l1.605-7.496z"/>
        </svg>
    </span>

    {{-- Mastercard (rouge + orange, cercles entrelacés) --}}
    <span class="{{ $wrap }}">
        <svg class="{{ $h }} w-auto" viewBox="0 0 48 30" role="img" aria-label="Mastercard" xmlns="http://www.w3.org/2000/svg">
            <circle cx="19" cy="15" r="14" fill="#EB001B"/>
            <circle cx="29" cy="15" r="14" fill="#F79E1B" style="mix-blend-mode:multiply"/>
        </svg>
    </span>
</div>
