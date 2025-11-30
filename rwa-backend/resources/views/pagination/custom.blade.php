@if ($paginator->hasPages())
    <nav aria-label="Pagination Navigation">
        <ul class="pagination mb-0">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled">
                    <span class="page-link">‹ Previous</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="?{{ http_build_query(array_merge(request()->query(), ['page' => $paginator->currentPage() - 1])) }}">‹ Previous</a>
                </li>
            @endif

            {{-- Page Number Links --}}
            @php
                $start = max(1, $paginator->currentPage() - 2);
                $end = min($paginator->lastPage(), $paginator->currentPage() + 2);
            @endphp

            @if($start > 1)
                <li class="page-item">
                    <a class="page-link" href="?{{ http_build_query(array_merge(request()->query(), ['page' => 1])) }}">1</a>
                </li>
                @if($start > 2)
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                @endif
            @endif

            @for ($page = $start; $page <= $end; $page++)
                @if ($page == $paginator->currentPage())
                    <li class="page-item active">
                        <span class="page-link">{{ $page }}</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="?{{ http_build_query(array_merge(request()->query(), ['page' => $page])) }}">{{ $page }}</a>
                    </li>
                @endif
            @endfor

            @if($end < $paginator->lastPage())
                @if($end < $paginator->lastPage() - 1)
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                @endif
                <li class="page-item">
                    <a class="page-link" href="?{{ http_build_query(array_merge(request()->query(), ['page' => $paginator->lastPage()])) }}">{{ $paginator->lastPage() }}</a>
                </li>
            @endif

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="?{{ http_build_query(array_merge(request()->query(), ['page' => $paginator->currentPage() + 1])) }}">Next ›</a>
                </li>
            @else
                <li class="page-item disabled">
                    <span class="page-link">Next ›</span>
                </li>
            @endif
        </ul>
    </nav>
@endif