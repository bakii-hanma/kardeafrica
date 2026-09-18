{{--
    Pagination « Layered » de la console admin — complète (numéros).
    Utilise exclusivement les tokens .adm (--navy, --teal, --border…).

    Variables Laravel classiques : $paginator, $elements.
    Le design suit les boutons lst-action : petites pills, fond surface,
    page courante en teal plein.
--}}
@if ($paginator->hasPages())
    <nav aria-label="Pagination" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <span style="font-size:11px;color:var(--text-faint);font-weight:600;">
            Page {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
        </span>
        <div style="display:inline-flex;align-items:center;gap:4px;flex-wrap:wrap;">
            {{-- Précédent --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true"
                      style="display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 9px;border-radius:var(--r-pill);background:var(--surface-inset);border:1px solid var(--border);color:var(--text-faint);font-size:11px;font-weight:700;pointer-events:none;">‹</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   style="display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 9px;border-radius:var(--r-pill);background:var(--surface);border:1px solid var(--border);color:var(--text);font-size:11px;font-weight:700;text-decoration:none;transition:background .12s ease,color .12s ease;"
                   onmouseover="this.style.background='var(--navy)';this.style.color='#FFFFFF';this.style.borderColor='var(--navy)';"
                   onmouseout="this.style.background='var(--surface)';this.style.color='var(--text)';this.style.borderColor='var(--border)';">‹</a>
            @endif

            {{-- Numéros --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span aria-disabled="true"
                          style="display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 6px;color:var(--text-faint);font-size:11px;font-weight:700;">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                  style="display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 9px;border-radius:var(--r-pill);background:var(--teal);border:1px solid var(--teal);color:#FFFFFF;font-size:11px;font-weight:800;box-shadow:0 2px 6px rgb(20 184 166 / .30);">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}"
                               style="display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 9px;border-radius:var(--r-pill);background:var(--surface);border:1px solid var(--border);color:var(--text);font-size:11px;font-weight:700;text-decoration:none;transition:background .12s ease,color .12s ease;"
                               onmouseover="this.style.background='var(--navy)';this.style.color='#FFFFFF';this.style.borderColor='var(--navy)';"
                               onmouseout="this.style.background='var(--surface)';this.style.color='var(--text)';this.style.borderColor='var(--border)';">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Suivant --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   style="display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 9px;border-radius:var(--r-pill);background:var(--surface);border:1px solid var(--border);color:var(--text);font-size:11px;font-weight:700;text-decoration:none;transition:background .12s ease,color .12s ease;"
                   onmouseover="this.style.background='var(--navy)';this.style.color='#FFFFFF';this.style.borderColor='var(--navy)';"
                   onmouseout="this.style.background='var(--surface)';this.style.color='var(--text)';this.style.borderColor='var(--border)';">›</a>
            @else
                <span aria-disabled="true"
                      style="display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 9px;border-radius:var(--r-pill);background:var(--surface-inset);border:1px solid var(--border);color:var(--text-faint);font-size:11px;font-weight:700;pointer-events:none;">›</span>
            @endif
        </div>
    </nav>
@endif