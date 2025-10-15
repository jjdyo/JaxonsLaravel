@extends('layouts.app')

@section('title', 'Admin • Systems')

@section('content')
<div class="admin-systems">
    <h1>Systems</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('admin.systems.update') }}" method="POST">
        @csrf
        <div class="settings-group">
            <details open>
                <summary><strong>System Configuration</strong></summary>
                <div class="settings-item">
                    <label for="timezone">System Timezone</label>
                    <select id="timezone" name="timezone">
                        @foreach($timezones as $tz)
                            <option value="{{ $tz }}" @selected($tz === $currentTimezone)>{{ $tz }}</option>
                        @endforeach
                    </select>
                    @error('timezone')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>
                <div class="settings-item" style="margin-top: .75rem;">
                    <label for="site_name">Site Name</label>
                    <input type="text" id="site_name" name="site_name" value="{{ old('site_name', $siteName ?? '') }}" maxlength="100" />
                    @error('site_name')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>
                <div class="settings-item" style="margin-top: .75rem;">
                    <label for="contact_email">Contact Email</label>
                    <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email', $contactEmail ?? '') }}" maxlength="255" />
                    @error('contact_email')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>
            </details>
        </div>

        <div class="settings-group" style="margin-top: 1rem;">
            <details>
                <summary><strong>User Management</strong></summary>
                <div class="settings-item">
                    <label for="users_per_page">Users per page</label>
                    <input type="number" id="users_per_page" name="users_per_page" value="{{ old('users_per_page', $usersPerPage ?? 15) }}" min="5" max="200" />
                    @error('users_per_page')
                        <div class="error">{{ $message }}</div>
                    @enderror
                    <p class="help">Controls pagination on the User Management page.</p>
                </div>
            </details>
        </div>

        <div class="settings-actions" style="margin-top: 1rem;">
            <button type="submit" class="button button-primary">Save settings</button>
        </div>
    </form>
</div>
@endsection
