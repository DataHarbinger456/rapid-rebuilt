@extends('layouts.customer.master')

@section('title')Settings @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Settings</h3>
        @endslot
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Settings</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Change Password</h5>
                    </div>
                    <div class="card-body">
                        @if (session('password_success'))
                            <div class="alert alert-success" role="alert">{{ session('password_success') }}</div>
                        @endif

                        <form action="{{ route('customer.settings.password') }}" method="POST">
                            @csrf
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="current_password">Current Password</label>
                                    <input type="password" id="current_password" name="current_password"
                                           class="form-control @error('current_password') is-invalid @enderror"
                                           autocomplete="current-password" required>
                                    @error('current_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="password">New Password</label>
                                    <input type="password" id="password" name="password"
                                           class="form-control @error('password') is-invalid @enderror"
                                           autocomplete="new-password" required minlength="8">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="password_confirmation">Confirm Password</label>
                                    <input type="password" id="password_confirmation" name="password_confirmation"
                                           class="form-control" autocomplete="new-password" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Change Password</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5>Email Notifications</h5>
                    </div>
                    <div class="card-body">
                        @if (session('preferences_success'))
                            <div class="alert alert-success" role="alert">{{ session('preferences_success') }}</div>
                        @endif

                        <form action="{{ route('customer.settings.preferences') }}" method="POST">
                            @csrf
                            <div class="form-check mb-3">
                                {{-- Hidden fallback so an unticked box still posts "0". --}}
                                <input type="hidden" name="email_notifications" value="0">
                                <input type="checkbox" id="email_notifications" name="email_notifications" value="1"
                                       class="form-check-input @error('email_notifications') is-invalid @enderror"
                                       @checked(old('email_notifications', $user->getPreference('notifications.email', true)))>
                                <label class="form-check-label" for="email_notifications">
                                    Send me email updates about my cases
                                </label>
                                <div class="form-text">
                                    Covers case status updates, appointment confirmations and inspection results.
                                </div>
                                @error('email_notifications')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary">Save Preferences</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
