@extends('layouts.app')

{{--  @section('title', 'Change Password') --}}
@push('styles')
<link rel="stylesheet" href="{{ asset('css/profile.css') }}">
@endpush

@section('content')
    <div class="container-shadow password-form">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="breadcrumbs">
                    <a href="{{ route('profile') }}">Profile</a> » <span>Change Password</span>
                </div>
                <div class="card">

                    <div class="card-body">
                        <p class="text-muted">Enter your new password below. We'll email you a quick confirmation link to verify the change. The change will not take effect until you confirm via email.</p>
                        <form method="POST" action="{{ route('profile.password.update') }}">
                            @csrf

                            <div class="form-group row mb-3">
                                <label for="password" class="col-md-4 col-form-label text-md-right">New Password</label>
                                <div class="col-md-6">
                                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                                    <small class="form-text text-muted">Minimum 8 characters.</small>
                                    @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row mb-4">
                                <label for="password_confirmation" class="col-md-4 col-form-label text-md-right">Confirm Password</label>
                                <div class="col-md-6">
                                    <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                                </div>
                            </div>

                            <div class="form-group row mb-0">
                                <div class="col-md-6 offset-md-4">
                                    <button type="submit" class="button button-confirmation">
                                        Request Password Change
                                    </button>
                                    <a href="{{ route('profile') }}" class="button button-warning ml-2">
                                        Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
