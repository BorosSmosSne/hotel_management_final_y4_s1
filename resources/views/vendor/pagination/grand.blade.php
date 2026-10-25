{{-- NEW: replaces Bootstrap pagination. [data-pagination] is what the AJAX table scripts hook into. --}}
@if ($paginator->hasPages())
    <nav data-pagination role="navigation" aria-label="Pagination" class="flex items-center gap-1.5">
        @if ($paginator->onFirstPage())
            <span class="pg pg-off" aria-disabled="true" aria-label="Previous page"><x-icon name="chevron-left" class="size-4" /></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pg bg-tint" aria-label="Previous page"><x-icon name="chevron-left" class="size-4" /></a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pg pg-off">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pg pg-on" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="pg" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pg bg-tint" aria-label="Next page"><x-icon name="chevron-right" class="size-4" /></a>
        @else
            <span class="pg pg-off" aria-disabled="true" aria-label="Next page"><x-icon name="chevron-right" class="size-4" /></span>
        @endif
    </nav>
@endif
