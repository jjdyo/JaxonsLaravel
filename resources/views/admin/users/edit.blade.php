@extends('layouts.app')

@section('title', 'Edit User')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/users/useradministration.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/users/api-keys.css') }}">
@endpush

@section('content')
<div class="user-management">
    <nav class="breadcrumb">
        <a href="{{ route('admin.dashboard') }}">Admin</a> »
        <a href="{{ route('admin.users.index') }}">User Management</a> »
        <a href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a> »
        <span>Edit</span>
    </nav>

    <h1>Edit User</h1>

    @include('partials.alerts')

    <div class="grid-2">
        <section class="card">
            <h2>Account Details</h2>
            <form action="{{ route('admin.users.update', $user) }}" method="POST" class="form">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="name">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" placeholder="Leave blank to keep current">
                    <small class="form-hint">Leave blank to keep the existing password.</small>
                </div>

                <div class="form-group form-check">
                    <input id="email_verified" name="email_verified" type="checkbox" value="1" {{ old('email_verified', $user->email_verified_at ? 1 : 0) ? 'checked' : '' }}>
                    <label for="email_verified">Email verified</label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </section>

        <section class="card">
            <h2>Roles</h2>
            <form action="{{ route('admin.users.roles.update', $user) }}" method="POST" class="form">
                @csrf
                <div class="form-group">
                    <label for="roles">Assigned Roles</label>
                    <select id="roles" name="roles[]" multiple size="6">
                        @php $assigned = $user->roles->pluck('id')->all(); @endphp
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ in_array($role->id, old('roles', $assigned)) ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="form-hint">Hold Ctrl (Windows) or Command (Mac) to select multiple roles.</small>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Roles</button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection
