@extends('layouts.app')

@section('title', 'Documentation')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/docs.css') }}">
@endsection

@section('content')
    <div class="docs-container">
        <h1>Project Documentation</h1>

        <div class="docs-intro">
            {!! $readme !!}
        </div>

        <div class="docs-index">
            <h2>Documentation Index</h2>

            <div class="docs-grid">
                @foreach($documents as $doc)
                    <div class="docs-item">
                        <a href="{{ route('docs.show', ['filename' => $doc['filename']]) }}">
                            <h3>{{ $doc['title'] }}</h3>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
