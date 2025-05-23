@extends('layouts.app')

@section('title', $title . ' - Documentation')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/docs.css') }}">
@endsection

@section('content')
    <div class="docs-container">
        <div class="docs-header">
            <a href="{{ route('docs.index') }}" class="back-link">&larr; Back to Documentation Index</a>
            <h1>{{ $title }}</h1>
        </div>

        <div class="docs-content">
            {!! $content !!}
        </div>
    </div>
@endsection
