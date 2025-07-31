@if ($paginator->hasPages())
    <nav class="user-pagination">
        <div class="pagination-info">
            <p class="pagination-count">
                Showing {{ $paginator->firstItem() ?? 0 }} to {{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }}
            </p>
        </div>

        <div class="pagination-links">
            <ul class="pagination-list">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <li class="pagination-item disabled">
                        <span class="pagination-link pagination-previous">&lsaquo;</span>
                    </li>
                @else
                    <li class="pagination-item">
                        <a class="pagination-link pagination-previous" href="{{ $paginator->previousPageUrl() }}" rel="prev">&lsaquo;</a>
                    </li>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <li class="pagination-item disabled">
                            <span class="pagination-link">{{ $element }}</span>
                        </li>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="pagination-item active">
                                    <span class="pagination-link">{{ $page }}</span>
                                </li>
                            @else
                                <li class="pagination-item">
                                    <a class="pagination-link" href="{{ $url }}">{{ $page }}</a>
                                </li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <li class="pagination-item">
                        <a class="pagination-link pagination-next" href="{{ $paginator->nextPageUrl() }}" rel="next">&rsaquo;</a>
                    </li>
                @else
                    <li class="pagination-item disabled">
                        <span class="pagination-link pagination-next">&rsaquo;</span>
                    </li>
                @endif
            </ul>
        </div>
    </nav>
@endif
