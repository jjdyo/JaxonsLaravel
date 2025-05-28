@extends('layouts.app')

@section('title', 'Documentation')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/docs.css') }}">
@endsection

@section('content')
    <div class="docs-container">
        <div class="docs-layout">
            <div class="docs-sidebar">
                <h2>Index</h2>
                <ul class="docs-nav">
                    @foreach($documents as $doc)
                        <li class="docs-nav-item">
                            <a href="{{ route('docs.show', ['filename' => $doc['filename']]) }}">
                                {{ $doc['title'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="docs-main">
                <h1>Project Documentation</h1>

                <div class="docs-intro">
                    {!! $readme !!}
                </div>
            </div>
        </div>
    </div>
@endsection
