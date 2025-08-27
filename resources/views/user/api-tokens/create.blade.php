@extends('layouts.app')

@section('title', 'Create API Token')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/users/api-keys.css') }}">
    <style>
        .api-tokens-container {
            max-width: 700px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-text {
            font-size: 0.85em;
            color: var(--color-text);
            opacity: 0.8;
            margin-top: 5px;
        }
        .error {
            color: #ff6b6b;
            margin-top: 5px;
        }
        .btn-group {
            margin-top: 30px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
        .btn-primary {
            background-color: var(--color-link);
            color: var(--color-primary);
        }
        .btn-primary:hover {
            background-color: var(--color-link-hover);
        }
        .btn-secondary {
            background-color: var(--color-button);
            color: var(--color-text);
        }
        .btn-secondary:hover {
            background-color: var(--color-button-hover);
        }
    </style>
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
            <div class="checkbox-group" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 10px;">
                @foreach(config('api-scopes.scopes') as $scope => $description)
                    <div style="display: flex; align-items: center;">
                        <input type="checkbox" id="scope_{{ $scope }}" name="scopes[]" value="{{ $scope }}"
                            {{ in_array($scope, old('scopes', [])) ? 'checked' : '' }}>
                        <label for="scope_{{ $scope }}" style="margin-left: 8px; font-weight: normal;">
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
