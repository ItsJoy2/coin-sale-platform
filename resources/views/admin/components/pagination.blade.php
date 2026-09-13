@if ($paginator->hasPages())

<div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 mt-3">

    <div class="text-muted small">
        Showing
        <strong>{{ $paginator->firstItem() ?? 0 }}</strong>
        to
        <strong>{{ $paginator->lastItem() ?? 0 }}</strong>
        of
        <strong>{{ $paginator->total() }}</strong>
        results
    </div>

    <nav>
        <ul class="pagination mb-0 flex-wrap">

            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled">
                    <span class="page-link">&laquo;</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}">
                        &laquo;
                    </a>
                </li>
            @endif

            {{-- Page Numbers --}}
            @php
                $start = max($paginator->currentPage() - 2, 1);
                $end = min($start + 3, $paginator->lastPage());

                if ($end - $start < 3) {
                    $start = max($end - 3, 1);
                }
            @endphp

            @if($start > 1)
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->url(1) }}">
                        1
                    </a>
                </li>

                @if($start > 2)
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                @endif
            @endif

            @for($i = $start; $i <= $end; $i++)
                <li class="page-item {{ $i == $paginator->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="{{ $paginator->url($i) }}">
                        {{ $i }}
                    </a>
                </li>
            @endfor

            @if($end < $paginator->lastPage())

                @if($end < ($paginator->lastPage() - 1))
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                @endif

                <li class="page-item">
                    <a class="page-link"
                       href="{{ $paginator->url($paginator->lastPage()) }}">
                        {{ $paginator->lastPage() }}
                    </a>
                </li>

            @endif

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}">
                        &raquo;
                    </a>
                </li>
            @else
                <li class="page-item disabled">
                    <span class="page-link">&raquo;</span>
                </li>
            @endif

        </ul>
    </nav>

</div>

@endif
