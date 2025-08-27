@extends('layouts.app')

@section('title', 'Documentation')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/docs.css') }}">
@endsection

@section('scripts')
<script src="{{ asset('js/docs-sidebar.js') }}"></script>
@endsection

@section('content')
    <div class="docs-container">
        <div class="docs-layout">
            <div class="docs-sidebar">
                <h2>Index</h2>
                <ul class="docs-nav">
                    @foreach($documents as $doc)
                        @if($doc['type'] === 'directory')
                            <li class="docs-nav-item docs-nav-directory">
                                <span class="docs-nav-directory-title">{{ $doc['title'] }}</span>
                                <ul class="docs-nav-subdirectory">
                                    @foreach($doc['children'] as $child)
                                        <li class="docs-nav-item">
                                            <a href="{{ route('docs.show', ['filename' => $child['filename']]) }}">
                                                {{ $child['title'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @else
                            <li class="docs-nav-item">
                                <a href="{{ route('docs.show', ['filename' => $doc['filename']]) }}">
                                    {{ $doc['title'] }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>

            <div class="docs-main">
                <div class="docs-intro">
                    {!! $readme !!}
                </div>
            </div>
        </div>
    </div>
@endsection
