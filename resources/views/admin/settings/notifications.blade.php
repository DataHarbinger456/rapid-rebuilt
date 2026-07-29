@extends('layouts.admin.master')

@section('title')Notification Settings @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Notification Settings</h3>
        @endslot
        <li class="breadcrumb-item">Admin</li>
        <li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></li>
        <li class="breadcrumb-item active">Notifications</li>
    @endcomponent

    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Notification Channels</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.settings.notifications.update') }}" method="POST">
                            @csrf

                            <div class="mb-4">
                                <div class="form-check form-switch mb-3">
                                    <input type="checkbox" class="form-check-input" name="email_enabled" id="emailEnabled"
                                           {{ ($settings['email_enabled'] ?? $defaults['email_enabled']) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="emailEnabled">
                                        <strong>Email Notifications</strong>
                                    </label>
                                </div>
                                <p class="text-muted ms-4">Send email notifications for case updates, appointments, and inspection results.</p>
                            </div>

                            <div class="mb-4">
                                <div class="form-check form-switch mb-3">
                                    <input type="checkbox" class="form-check-input" name="sms_enabled" id="smsEnabled"
                                           {{ ($settings['sms_enabled'] ?? $defaults['sms_enabled']) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="smsEnabled">
                                        <strong>SMS Notifications</strong>
                                    </label>
                                </div>
                                <p class="text-muted ms-4">Send SMS notifications for appointment reminders and urgent updates. (Requires Twilio configuration)</p>
                            </div>

                            <hr>

                            <div class="mb-4">
                                <label class="form-label">Appointment Reminder (hours before)</label>
                                <select name="reminder_hours_before" class="form-select" style="width: auto;">
                                    @foreach([6, 12, 24, 48, 72] as $hours)
                                        <option value="{{ $hours }}" {{ ($settings['reminder_hours_before'] ?? $defaults['reminder_hours_before']) == $hours ? 'selected' : '' }}>
                                            {{ $hours }} hours
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Send appointment reminders this many hours before the scheduled time.</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Admin Notification Email</label>
                                <input type="email" name="admin_email" class="form-control"
                                       value="{{ $settings['admin_email'] ?? $defaults['admin_email'] }}" required>
                                <small class="text-muted">Email address to receive admin notifications (new cases, issues, etc.)</small>
                            </div>

                            <hr>

                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Notification Types</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Case Submitted
                                <span class="badge bg-primary">Email</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Appointment Confirmed
                                <span class="badge bg-primary">Email + SMS</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Appointment Reminder
                                <span class="badge bg-primary">Email + SMS</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Inspection Completed
                                <span class="badge bg-primary">Email</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Documents Ready
                                <span class="badge bg-primary">Email</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Status Changed
                                <span class="badge bg-secondary">In-App</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5>Test Notifications</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Send a test notification to verify your settings are working correctly.</p>
                        <button class="btn btn-outline-primary w-100 mb-2" onclick="sendTestEmail()">
                            <i data-feather="mail"></i> Send Test Email
                        </button>
                        <button class="btn btn-outline-secondary w-100" onclick="sendTestSMS()" id="testSmsBtn"
                            {{ !($settings['sms_enabled'] ?? false) ? 'disabled' : '' }}>
                            <i data-feather="smartphone"></i> Send Test SMS
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
function sendTestEmail() {
    alert('Test email would be sent to: ' + document.querySelector('[name="admin_email"]').value);
}

function sendTestSMS() {
    alert('Test SMS would be sent');
}

document.getElementById('smsEnabled').addEventListener('change', function() {
    document.getElementById('testSmsBtn').disabled = !this.checked;
});
</script>
@endpush
