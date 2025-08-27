@extends('layouts.app')

@section('title', 'Create API Token')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/users/api-keys.css') }}">
@endsection

@section('content')
<div class="api-tokens-container">
    <h1>Create API Token</h1>

    <p>API tokens allow third-party services to authenticate with our application on your behalf.</p>

    <form action="{{ route('api-tokens.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="name">Token Name</label>
            <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required>
            <small class="form-text">Give this token a name to help you identify what it's used for.</small>
            @error('name')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="expiration">Token Expiration</label>
            <select id="expiration" name="expiration" class="form-control" required>
                <option value="week" {{ old('expiration') == 'week' ? 'selected' : '' }}>1 Week</option>
                <option value="month" {{ old('expiration') == 'month' ? 'selected' : '' }}>1 Month</option>
                <option value="year" {{ old('expiration') == 'year' ? 'selected' : '' }}>1 Year</option>
            </select>
            <small class="form-text">Select when this token should expire.</small>
            @error('expiration')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label>Token Scopes</label>
            <div class="checkbox-group">
                @foreach(config('api-scopes.scopes') as $scope => $description)
                    <div class="checkbox-item">
                        <input type="checkbox" id="scope_{{ $scope }}" name="scopes[]" value="{{ $scope }}"
                            {{ in_array($scope, old('scopes', [])) ? 'checked' : '' }}>
                        <label for="scope_{{ $scope }}">
                            {{ $description }}
                        </label>
                    </div>
                @endforeach
            </div>
            <small class="form-text">Select the scopes this token should have access to. At least one scope is required.</small>
            @error('scopes')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary">Create Token</button>
            <a href="{{ route('api-tokens.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
