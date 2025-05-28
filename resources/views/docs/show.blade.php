@extends('layouts.app')

@section('title', $title . ' - Documentation')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/docs.css') }}">
@endsection

@section('content')
    <div class="docs-container">
        <div class="docs-layout">
            <div class="docs-sidebar">
                <ul class="docs-nav">
                    @foreach($documents as $doc)
                        <li class="docs-nav-item {{ $doc['filename'] == request()->route('filename') ? 'active' : '' }}">
                            <a href="{{ route('docs.show', ['filename' => $doc['filename']]) }}">
                                {{ $doc['title'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="back-to-index">
                    <a href="{{ route('docs.index') }}" class="back-link">&larr; Back to Main Page</a>
                </div>
            </div>

            <div class="docs-main">
                <div class="docs-content">
                    {!! $content !!}
                </div>
            </div>
        </div>
    </div>
@endsection
