@if($paginator->hasPages())
    <div class="mo-pager">
        @if($paginator->onFirstPage())
            <span class="disabled"><i class="fas fa-chevron-left"></i></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}"><i class="fas fa-chevron-left"></i></a>
        @endif

        <span class="info">Hal {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}"><i class="fas fa-chevron-right"></i></a>
        @else
            <span class="disabled"><i class="fas fa-chevron-right"></i></span>
        @endif
    </div>
@endif
