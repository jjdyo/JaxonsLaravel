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
            <form id="main-user-update-form" action="{{ route('admin.users.update', $user) }}" method="POST" class="form">
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

                <div class="form-group">
                    <label>Email verified</label>
                    <input type="hidden" id="email_verified" name="email_verified" value="{{ old('email_verified', $user->email_verified_at ? 1 : 0) }}">
                    <button type="button" id="btn-verified-toggle" class="btn">...</button>
                    <small class="form-hint">Click to toggle verification status. Green = Yes, Red = No.</small>
                </div>
            </form>
        </section>

        <section class="card">
            <h2>Roles</h2>
            <div class="form-group">
                <label for="roles">Assigned Roles</label>
                <select id="roles" name="roles[]" multiple size="6" form="main-user-update-form">
                    @php $assignedRoles = $user->roles->pluck('id')->all(); @endphp
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ in_array($role->id, old('roles', $assignedRoles)) ? 'selected' : '' }}>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
                <div class="form-actions-inline">
                    <button type="button" class="button button-warning" id="clear-roles-btn">Clear all roles</button>
                </div>
                <small class="form-hint">Hold Ctrl (Windows) or Command (Mac) to select multiple roles.</small>
            </div>
        </section>

        <section class="card">
            <h2>Permissions</h2>
            <div class="form-group">
                <label for="permissions">Direct Permissions</label>
                <select id="permissions" name="permissions[]" multiple size="8" form="main-user-update-form">
                    @php $assignedPerms = $user->permissions->pluck('id')->all(); @endphp
                    @foreach($permissions as $permission)
                        <option value="{{ $permission->id }}" {{ in_array($permission->id, old('permissions', $assignedPerms)) ? 'selected' : '' }}>
                            {{ $permission->name }}
                        </option>
                    @endforeach
                </select>
                <div class="form-actions-inline">
                    <button type="button" class="button button-warning" id="clear-permissions-btn">Clear all permissions</button>
                    <input type="hidden" name="permissions_force_sync" id="permissions_force_sync" value="0" form="main-user-update-form">
                </div>
                <small class="form-hint">Hold Ctrl (Windows) or Command (Mac) to select multiple permissions. These are direct permissions in addition to those granted by roles.</small>
            </div>
        </section>

        <section class="card">
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" form="main-user-update-form">Save Changes</button>
                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </section>
    </div>
</div>

@push('scripts')
<script>
    (function() {
        // Email verified single-button toggle
        const hidden = document.getElementById('email_verified');
        const toggleBtn = document.getElementById('btn-verified-toggle');
        function refreshToggle() {
            if (!hidden || !toggleBtn) return;
            const isYes = String(hidden.value) === '1';
            toggleBtn.textContent = isYes ? 'YES' : 'NO';
            toggleBtn.classList.remove('button-success', 'button-danger');
            toggleBtn.classList.add(isYes ? 'button-success' : 'button-danger');
        }
        if (toggleBtn && hidden) {
            toggleBtn.addEventListener('click', function() {
                hidden.value = String(hidden.value) === '1' ? '0' : '1';
                refreshToggle();
            });
            refreshToggle();
        }

        // Clear roles
        const rolesSelect = document.getElementById('roles');
        const clearRolesBtn = document.getElementById('clear-roles-btn');
        if (rolesSelect && clearRolesBtn) {
            clearRolesBtn.addEventListener('click', function() {
                for (let i = 0; i < rolesSelect.options.length; i++) {
                    rolesSelect.options[i].selected = false;
                }
            });
        }

        // Clear permissions (and force sync)
        const permsSelect = document.getElementById('permissions');
        const clearPermsBtn = document.getElementById('clear-permissions-btn');
        const forcePerms = document.getElementById('permissions_force_sync');
        if (permsSelect && clearPermsBtn && forcePerms) {
            clearPermsBtn.addEventListener('click', function() {
                for (let i = 0; i < permsSelect.options.length; i++) {
                    permsSelect.options[i].selected = false;
                }
                forcePerms.value = '1';
            });
        }
    })();
</script>
@endpush
@endsection
