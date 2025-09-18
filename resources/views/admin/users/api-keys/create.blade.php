@extends('layouts.app')

@section('title', 'Create API Key for ' . $user->name)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/users/useradministration.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/users/api-keys.css') }}">
@endpush

@section('content')
<div class="user-management">
    <h1>Create API Key for {{ $user->name }}</h1>

    <div class="breadcrumbs">
        <a href="{{ route('admin.users.index') }}">User Management</a> &raquo;
        <a href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a> &raquo;
        <a href="{{ route('admin.users.api-keys.index', $user) }}">API Keys</a> &raquo;
        <span>Create</span>
    </div>

    <form action="{{ route('admin.users.api-keys.store', $user) }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required>
            <small class="form-text">Give this API key a name to help you identify it later.</small>
            @error('name')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="expires_at">Expiration Date (Optional)</label>
            <input type="date" id="expires_at" name="expires_at" class="form-control" value="{{ old('expires_at') }}">
            <small class="form-text">If set, the API key will expire on this date. Leave blank for no expiration.</small>
            @error('expires_at')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label>Abilities (Optional)</label>
            <div class="checkbox-group">
                <div>
                    <input type="checkbox" id="ability_read" name="abilities[]" value="read" {{ in_array('read', old('abilities', [])) ? 'checked' : '' }}>
                    <label for="ability_read">Read</label>
                </div>
                <div>
                    <input type="checkbox" id="ability_write" name="abilities[]" value="write" {{ in_array('write', old('abilities', [])) ? 'checked' : '' }}>
                    <label for="ability_write">Write</label>
                </div>
                <div>
                    <input type="checkbox" id="ability_delete" name="abilities[]" value="delete" {{ in_array('delete', old('abilities', [])) ? 'checked' : '' }}>
                    <label for="ability_delete">Delete</label>
                </div>
            </div>
            <small class="form-text">Select the abilities this API key should have. If none are selected, the key will have no abilities.</small>
            @error('abilities')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary">Create API Key</button>
            <a href="{{ route('admin.users.api-keys.index', $user) }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
