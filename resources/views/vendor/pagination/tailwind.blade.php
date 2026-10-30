@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex items-center gap-1">
        @if ($paginator->onFirstPage())
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-sm text-slate-300" aria-disabled="true">&lsaquo;</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
               class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-sm text-slate-600 hover:bg-slate-50">&lsaquo;</a>
        @endif

        @php
            $start = max(1, $paginator->currentPage() - 2);
            $end = min($paginator->lastPage(), $paginator->currentPage() + 2);
        @endphp

        @if ($start > 1)
            <a href="{{ $paginator->url(1) }}"
               class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-slate-200 bg-white px-2 text-sm text-slate-600 hover:bg-slate-50">1</a>
            @if ($start > 2)
                <span class="inline-flex h-8 w-8 items-center justify-center text-sm text-slate-400">&hellip;</span>
            @endif
        @endif

        @for ($page = $start; $page <= $end; $page++)
            @if ($page === $paginator->currentPage())
                <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-blue-600 bg-blue-600 px-2 text-sm font-semibold text-white" aria-current="page">{{ $page }}</span>
            @else
                <a href="{{ $paginator->url($page) }}"
                   class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-slate-200 bg-white px-2 text-sm text-slate-600 hover:bg-slate-50">{{ $page }}</a>
            @endif
        @endfor

        @if ($end < $paginator->lastPage())
            @if ($end < $paginator->lastPage() - 1)
                <span class="inline-flex h-8 w-8 items-center justify-center text-sm text-slate-400">&hellip;</span>
            @endif
            <a href="{{ $paginator->url($paginator->lastPage()) }}"
               class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-slate-200 bg-white px-2 text-sm text-slate-600 hover:bg-slate-50">{{ $paginator->lastPage() }}</a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next"
               class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-sm text-slate-600 hover:bg-slate-50">&rsaquo;</a>
        @else
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-sm text-slate-300" aria-disabled="true">&rsaquo;</span>
        @endif
    </nav>
@endif
