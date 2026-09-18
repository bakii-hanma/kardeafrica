{{--
    Pagination « Layered » de la console admin — simple (‹ Précédent / Suivant ›).
    Pour les pages qui paginent en `simplePaginate` (pas de numéros).
--}}
@if ($paginator->hasPages())
    <nav aria-label="Pagination" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true"
                  style="display:inline-flex;align-items:center;gap:5px;padding:7px 13px;border-radius:var(--r-pill);background:var(--surface-inset);border:1px solid var(--border);color:var(--text-faint);font-size:11px;font-weight:700;pointer-events:none;">‹&nbsp;Précédent</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
               style="display:inline-flex;align-items:center;gap:5px;padding:7px 13px;border-radius:var(--r-pill);background:var(--surface);border:1px solid var(--border);color:var(--text);font-size:11px;font-weight:700;text-decoration:none;transition:background .12s ease,color .12s ease;"
               onmouseover="this.style.background='var(--navy)';this.style.color='#FFFFFF';this.style.borderColor='var(--navy)';"
               onmouseout="this.style.background='var(--surface)';this.style.color='var(--text)';this.style.borderColor='var(--border)';">‹&nbsp;Précédent</a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next"
               style="display:inline-flex;align-items:center;gap:5px;padding:7px 13px;border-radius:var(--r-pill);background:var(--surface);border:1px solid var(--border);color:var(--text);font-size:11px;font-weight:700;text-decoration:none;transition:background .12s ease,color .12s ease;"
               onmouseover="this.style.background='var(--navy)';this.style.color='#FFFFFF';this.style.borderColor='var(--navy)';"
               onmouseout="this.style.background='var(--surface)';this.style.color='var(--text)';this.style.borderColor='var(--border)';">Suivant&nbsp;›</a>
        @else
            <span aria-disabled="true"
                  style="display:inline-flex;align-items:center;gap:5px;padding:7px 13px;border-radius:var(--r-pill);background:var(--surface-inset);border:1px solid var(--border);color:var(--text-faint);font-size:11px;font-weight:700;pointer-events:none;">Suivant&nbsp;›</span>
        @endif
    </nav>
@endif