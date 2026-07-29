@extends('layouts.customer.master')

@section('title')Support @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Support</h3>
        @endslot
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Support</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Contact Us</h5>
                    </div>
                    <div class="card-body">
                        <form action="#" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <select name="subject" class="form-select" required>
                                    <option value="">Select a topic...</option>
                                    <option value="case_question">Question about my case</option>
                                    <option value="documents">Document issues</option>
                                    <option value="appointment">Appointment related</option>
                                    <option value="payment">Payment or billing</option>
                                    <option value="technical">Technical issue</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Related Case (Optional)</label>
                                <select name="case_id" class="form-select">
                                    <option value="">No specific case</option>
                                    @foreach(auth()->user()->cases ?? [] as $case)
                                        <option value="{{ $case->id }}">#{{ $case->case_number }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea name="message" class="form-control" rows="5" required
                                          placeholder="Describe your issue or question..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i data-feather="send"></i> Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Contact Information</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-3">
                                <i data-feather="phone" class="text-primary me-2"></i>
                                <strong>(305) 555-0100</strong>
                                <p class="text-muted ms-4 mb-0">Mon-Fri 9AM - 5PM EST</p>
                            </li>
                            <li class="mb-3">
                                <i data-feather="mail" class="text-primary me-2"></i>
                                <strong>support@rapidrebuilt.com</strong>
                            </li>
                            <li class="mb-3">
                                <i data-feather="map-pin" class="text-primary me-2"></i>
                                <strong>123 Main Street</strong>
                                <p class="text-muted ms-4 mb-0">Miami, FL 33101</p>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body text-center">
                        <i data-feather="help-circle" style="width: 48px; height: 48px;" class="text-info mb-3"></i>
                        <h5>Need Quick Answers?</h5>
                        <p class="text-muted">Check our FAQ for common questions</p>
                        <a href="{{ route('customer.faq') }}" class="btn btn-outline-info">
                            View FAQ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
