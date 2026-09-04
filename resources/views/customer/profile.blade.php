@extends('layouts.customer.master')

@section('title')My Profile @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>My Profile</h3>
        @endslot
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Profile</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="mb-4">
                            <img src="{{ auth()->user()->avatar ?? asset('assets/images/dashboard/profile.jpg') }}"
                                 class="rounded-circle img-100" alt="Profile">
                        </div>
                        <h4>{{ auth()->user()->name }}</h4>
                        <p class="text-muted">{{ auth()->user()->email }}</p>
                        <span class="badge badge-primary">Customer</span>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <form action="#" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="name" class="form-control"
                                           value="{{ auth()->user()->name }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control"
                                           value="{{ auth()->user()->email }}" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control"
                                           value="{{ auth()->user()->phone ?? '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Address</label>
                                    <input type="text" name="address" class="form-control"
                                           value="{{ auth()->user()->address ?? '' }}">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5>Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('customer.settings.password') }}" method="POST">
                            @csrf
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="password" class="form-control" required minlength="8">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" name="password_confirmation" class="form-control" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-outline-primary">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
