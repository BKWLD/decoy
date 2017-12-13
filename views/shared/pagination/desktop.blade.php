<?php
use Bkwld\Decoy\Markup\UrlWindow;

// Make a smaller window
$window = (new UrlWindow($paginator))->get(3);

// Add dots to the window
$elements = [
    $window['first'],
    is_array($window['slider']) ? '...' : null,
    $window['slider'],
    is_array($window['slider']) ? '...' : null,
    $window['last'],
];

?>
@if ($paginator->hasPages())
    <ul class="pagination">

        {{-- First page --}}
        @if ($paginator->onFirstPage())
            <li class="disabled"><span>&laquo;</span></li>
        @else
            <li><a href="{{ $paginator->url(1) }}" rel="prev">&laquo;</a></li>
        @endif

        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <li class="disabled"><span>&lsaquo;</span></li>
        @else
            <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev">&lsaquo;</a></li>
        @endif


        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <li class="disabled"><span>{{ $element }}</span></li>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="active"><span>{{ $page }}</span></li>
                    @else
                        <li><a href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <li><a href="{{ $paginator->nextPageUrl() }}" rel="next">&rsaquo;</a></li>
        @else
            <li class="disabled"><span>&rsaquo;</span></li>
        @endif

        {{-- Last page --}}
        @if ($paginator->currentPage() == $paginator->lastPage())
            <li class="disabled"><span>&raquo;</span></li>
        @else
            <li><a href="{{ $paginator->url($paginator->lastPage()) }}" rel="prev">&raquo;</a></li>
        @endif

    </ul>
@endif
