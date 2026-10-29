{{-- Paginación del panel: «Mostrando 1 a 15 de N resultados» + números de página --}}
@if ($paginator->hasPages() || $paginator->total() > 0)
<nav role="navigation" aria-label="Paginación"
     style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;font-size:.82rem;color:#64748b">
    <div>
        Mostrando <strong style="color:#0f172a">{{ $paginator->firstItem() ?? 0 }}</strong>
        a <strong style="color:#0f172a">{{ $paginator->lastItem() ?? 0 }}</strong>
        de <strong style="color:#0f172a">{{ $paginator->total() }}</strong> resultados
    </div>
    @if ($paginator->hasPages())
    @php
        $btn = 'display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 10px;border-radius:9px;border:1px solid #e2e8f0;background:#fff;color:#334155;text-decoration:none;font-weight:600';
        $sel = 'background:#1f6feb;border-color:#1f6feb;color:#fff';
        $off = 'opacity:.45;cursor:default';
    @endphp
    <div style="display:flex;gap:6px;flex-wrap:wrap">
        @if ($paginator->onFirstPage())
            <span style="{{ $btn }};{{ $off }}">‹ Anterior</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" style="{{ $btn }}">‹ Anterior</a>
        @endif
        @foreach ($elements as $element)
            @if (is_string($element))
                <span style="{{ $btn }};{{ $off }}">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" style="{{ $btn }};{{ $sel }}">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" style="{{ $btn }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" style="{{ $btn }}">Siguiente ›</a>
        @else
            <span style="{{ $btn }};{{ $off }}">Siguiente ›</span>
        @endif
    </div>
    @endif
</nav>
@endif
