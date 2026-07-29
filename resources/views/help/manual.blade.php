@php
    $userType = auth()->user()->user_type;
    $isStaff = in_array($userType, ['admin', 'staff']);
    $layoutPath = $isStaff ? 'layouts.admin.master' : 'layouts.customer.master';
@endphp

@extends($layoutPath)

@section('title')User Manual @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>User Manual</h3>
        @endslot
        <li class="breadcrumb-item"><a href="{{ $isStaff ? route('admin.dashboard') : route('customer.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">User Manual</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation -->
            <div class="col-xl-3 d-none d-xl-block">
                <div class="card">
                    <div class="card-header bg-primary">
                        <h5 class="text-white mb-0"><i data-feather="book-open" class="me-2"></i> Contents</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a class="list-group-item list-group-item-action active" href="#intro">Introduction</a>
                            <a class="list-group-item list-group-item-action" href="#getting-started">Getting Started</a>
                            @if(auth()->user()->isCustomer())
                            <a class="list-group-item list-group-item-action" href="#customer-cases">My Cases</a>
                            <a class="list-group-item list-group-item-action" href="#customer-documents">Documents</a>
                            <a class="list-group-item list-group-item-action" href="#customer-appointments">Appointments</a>
                            <a class="list-group-item list-group-item-action" href="#customer-payments">Payments</a>
                            @endif
                            @if(auth()->user()->isInspector())
                            <a class="list-group-item list-group-item-action" href="#inspector-schedule">My Schedule</a>
                            <a class="list-group-item list-group-item-action" href="#inspector-inspections">Inspections</a>
                            <a class="list-group-item list-group-item-action" href="#inspector-reports">Reports</a>
                            @endif
                            @if(auth()->user()->isStaff())
                            <a class="list-group-item list-group-item-action" href="#admin-dashboard">Dashboard</a>
                            <a class="list-group-item list-group-item-action" href="#admin-cases">Case Management</a>
                            <a class="list-group-item list-group-item-action" href="#admin-users">Users</a>
                            <a class="list-group-item list-group-item-action" href="#admin-reports">Reports</a>
                            @endif
                            <a class="list-group-item list-group-item-action" href="#faq">FAQ</a>
                            <a class="list-group-item list-group-item-action" href="#support">Support</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-xl-9">
                <!-- Hero Card -->
                <div class="card o-hidden border-0">
                    <div class="bg-primary b-r-4 card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="text-white mb-2">User Manual</h2>
                                <p class="text-white-50 mb-3">Complete guide to using RapidRebuilt - Florida's Rebuilt Title Management System</p>
                                <span class="badge badge-light px-3 py-2">
                                    {{ auth()->user()->user_type_label }} Account
                                </span>
                            </div>
                            <div class="col-md-4 text-end d-none d-md-block">
                                <i data-feather="book" style="width: 80px; height: 80px; opacity: 0.3; color: white;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Introduction -->
                <div class="card" id="intro">
                    <div class="card-header">
                        <h5><i data-feather="info" class="me-2"></i> Introduction</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">Welcome to RapidRebuilt, your trusted partner for rebuilt title processing in Florida.</p>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center p-3">
                                    <div class="bg-light-primary rounded-circle p-3 d-inline-block mb-3">
                                        <i data-feather="shield" class="text-primary" style="width: 32px; height: 32px;"></i>
                                    </div>
                                    <h6>Secure & Private</h6>
                                    <p class="text-muted small mb-0">Bank-level SSL encryption protects your data</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-3">
                                    <div class="bg-light-warning rounded-circle p-3 d-inline-block mb-3">
                                        <i data-feather="zap" class="text-warning" style="width: 32px; height: 32px;"></i>
                                    </div>
                                    <h6>Fast Processing</h6>
                                    <p class="text-muted small mb-0">Streamlined workflow for quick turnaround</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-3">
                                    <div class="bg-light-success rounded-circle p-3 d-inline-block mb-3">
                                        <i data-feather="check-circle" class="text-success" style="width: 32px; height: 32px;"></i>
                                    </div>
                                    <h6>DMV Compliant</h6>
                                    <p class="text-muted small mb-0">All forms meet Florida HSMV requirements</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Getting Started -->
                <div class="card" id="getting-started">
                    <div class="card-header">
                        <h5><i data-feather="play-circle" class="me-2"></i> Getting Started</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Follow these steps to get started with the system.</p>

                        <div class="d-flex align-items-start mb-4 p-3 bg-light rounded">
                            <div class="bg-primary rounded-circle p-2 me-3 text-white d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; min-width: 36px;">
                                <strong>1</strong>
                            </div>
                            <div>
                                <h6 class="mb-1">Access the System</h6>
                                <p class="text-muted mb-0">Log in with your registered email and password. Use the "Forgot Password" link if needed.</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start mb-4 p-3 bg-light rounded">
                            <div class="bg-primary rounded-circle p-2 me-3 text-white d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; min-width: 36px;">
                                <strong>2</strong>
                            </div>
                            <div>
                                <h6 class="mb-1">Update Your Profile</h6>
                                <p class="text-muted mb-0">Ensure your contact information, address, and preferences are current for accurate communications.</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start mb-4 p-3 bg-light rounded">
                            <div class="bg-primary rounded-circle p-2 me-3 text-white d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; min-width: 36px;">
                                <strong>3</strong>
                            </div>
                            <div>
                                <h6 class="mb-1">Explore the Dashboard</h6>
                                <p class="text-muted mb-0">Your dashboard provides an overview of active cases, upcoming appointments, and important notifications.</p>
                            </div>
                        </div>

                        <div class="alert alert-info d-flex align-items-center">
                            <i data-feather="info" class="me-2"></i>
                            <div><strong>Pro Tip:</strong> Keep your email address updated to receive instant notifications about your cases.</div>
                        </div>
                    </div>
                </div>

                @if(auth()->user()->isCustomer())
                <!-- Customer: Cases -->
                <div class="card" id="customer-cases">
                    <div class="card-header">
                        <h5><i data-feather="briefcase" class="me-2"></i> My Cases</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Learn how to create and manage your rebuilt title cases.</p>

                        <h6 class="mt-4 mb-3">Starting a New Case</h6>

                        <div class="d-flex align-items-start mb-3 p-3 bg-light rounded">
                            <div class="bg-primary rounded-circle p-2 me-3 text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; min-width: 32px;">
                                <small>1</small>
                            </div>
                            <div>
                                <h6 class="mb-1">Enter Vehicle Information</h6>
                                <p class="text-muted mb-0 small">Input your 17-character VIN. Our system will automatically decode the year, make, and model.</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start mb-3 p-3 bg-light rounded">
                            <div class="bg-primary rounded-circle p-2 me-3 text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; min-width: 32px;">
                                <small>2</small>
                            </div>
                            <div>
                                <h6 class="mb-1">Provide Title Details</h6>
                                <p class="text-muted mb-0 small">Enter current title number, issuing state, and brand type (salvage/rebuilt).</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start mb-3 p-3 bg-light rounded">
                            <div class="bg-primary rounded-circle p-2 me-3 text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; min-width: 32px;">
                                <small>3</small>
                            </div>
                            <div>
                                <h6 class="mb-1">List Replaced Components</h6>
                                <p class="text-muted mb-0 small">Document all major components replaced during reconstruction with sources and receipts.</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start mb-4 p-3 bg-light rounded">
                            <div class="bg-primary rounded-circle p-2 me-3 text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; min-width: 32px;">
                                <small>4</small>
                            </div>
                            <div>
                                <h6 class="mb-1">Review & Submit</h6>
                                <p class="text-muted mb-0 small">Verify all information is correct, accept terms of service, and submit for processing.</p>
                            </div>
                        </div>

                        <h6 class="mt-4 mb-3">Understanding Case Status</h6>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="bg-primary">
                                    <tr>
                                        <th class="text-white">Status</th>
                                        <th class="text-white">Description</th>
                                        <th class="text-white">Your Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="badge badge-secondary">Draft</span></td>
                                        <td>Case started but not yet submitted</td>
                                        <td>Complete and submit the case</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge badge-info">Submitted</span></td>
                                        <td>Under review by our team</td>
                                        <td>Wait for review completion</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge badge-warning">Pending Payment</span></td>
                                        <td>Payment required to proceed</td>
                                        <td>Complete payment</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge badge-warning">Pending Documents</span></td>
                                        <td>Additional documents needed</td>
                                        <td>Upload required documents</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge badge-primary">Scheduled</span></td>
                                        <td>Inspection appointment confirmed</td>
                                        <td>Attend scheduled appointment</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge badge-success">Completed</span></td>
                                        <td>Process successfully finished</td>
                                        <td>Download final documents</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Customer: Documents -->
                <div class="card" id="customer-documents">
                    <div class="card-header">
                        <h5><i data-feather="file-text" class="me-2"></i> Documents</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Manage required documentation for your case.</p>

                        <h6 class="mt-3 mb-3">Required Documents</h6>
                        <div class="list-group">
                            <div class="list-group-item d-flex align-items-center">
                                <div class="bg-light-primary rounded-circle p-2 me-3">
                                    <i data-feather="file" class="text-primary"></i>
                                </div>
                                <div>
                                    <strong>Vehicle Title</strong>
                                    <p class="mb-0 small text-muted">Original salvage title or certified copy</p>
                                </div>
                            </div>
                            <div class="list-group-item d-flex align-items-center">
                                <div class="bg-light-primary rounded-circle p-2 me-3">
                                    <i data-feather="user" class="text-primary"></i>
                                </div>
                                <div>
                                    <strong>Photo Identification</strong>
                                    <p class="mb-0 small text-muted">Valid driver's license or state ID</p>
                                </div>
                            </div>
                            <div class="list-group-item d-flex align-items-center">
                                <div class="bg-light-primary rounded-circle p-2 me-3">
                                    <i data-feather="shopping-bag" class="text-primary"></i>
                                </div>
                                <div>
                                    <strong>Parts Receipts</strong>
                                    <p class="mb-0 small text-muted">Invoices for all replaced components</p>
                                </div>
                            </div>
                            <div class="list-group-item d-flex align-items-center">
                                <div class="bg-light-primary rounded-circle p-2 me-3">
                                    <i data-feather="camera" class="text-primary"></i>
                                </div>
                                <div>
                                    <strong>Vehicle Photos</strong>
                                    <p class="mb-0 small text-muted">Before/after reconstruction photos (recommended)</p>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mt-4 d-flex align-items-center">
                            <i data-feather="info" class="me-2"></i>
                            <div><strong>File Requirements:</strong> Accepted formats: PDF, JPG, PNG. Maximum file size: 10MB per document.</div>
                        </div>
                    </div>
                </div>

                <!-- Customer: Appointments -->
                <div class="card" id="customer-appointments">
                    <div class="card-header">
                        <h5><i data-feather="calendar" class="me-2"></i> Appointments</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Schedule and manage your inspection appointments.</p>

                        <div class="d-flex align-items-start mb-3 p-3 bg-light rounded">
                            <div class="bg-primary rounded-circle p-2 me-3 text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; min-width: 32px;">
                                <small>1</small>
                            </div>
                            <div>
                                <h6 class="mb-1">Select Your Case</h6>
                                <p class="text-muted mb-0 small">Choose the approved case for which you need to schedule an inspection.</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start mb-3 p-3 bg-light rounded">
                            <div class="bg-primary rounded-circle p-2 me-3 text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; min-width: 32px;">
                                <small>2</small>
                            </div>
                            <div>
                                <h6 class="mb-1">Choose Inspection Type</h6>
                                <p class="text-muted mb-0 small"><strong>In-Office:</strong> Bring your vehicle to our inspection center<br>
                                <strong>Mobile:</strong> Inspector comes to your location (additional fee applies)</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start mb-4 p-3 bg-light rounded">
                            <div class="bg-primary rounded-circle p-2 me-3 text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; min-width: 32px;">
                                <small>3</small>
                            </div>
                            <div>
                                <h6 class="mb-1">Select Date & Time</h6>
                                <p class="text-muted mb-0 small">Pick an available slot from the calendar. You'll receive a confirmation email immediately.</p>
                            </div>
                        </div>

                        <div class="alert alert-warning d-flex align-items-center">
                            <i data-feather="alert-triangle" class="me-2"></i>
                            <div><strong>Important:</strong> Reschedule or cancel at least 24 hours in advance to avoid a cancellation fee.</div>
                        </div>
                    </div>
                </div>

                <!-- Customer: Payments -->
                <div class="card" id="customer-payments">
                    <div class="card-header">
                        <h5><i data-feather="credit-card" class="me-2"></i> Payments</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Secure payment options and pricing information.</p>

                        <h6 class="mt-3 mb-3">Accepted Payment Methods</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-6 col-md-3">
                                <div class="card text-center p-3">
                                    <i data-feather="credit-card" style="width: 32px; height: 32px;" class="text-primary mx-auto mb-2"></i>
                                    <p class="mb-0 fw-bold small">Visa</p>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card text-center p-3">
                                    <i data-feather="credit-card" style="width: 32px; height: 32px;" class="text-primary mx-auto mb-2"></i>
                                    <p class="mb-0 fw-bold small">Mastercard</p>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card text-center p-3">
                                    <i data-feather="credit-card" style="width: 32px; height: 32px;" class="text-primary mx-auto mb-2"></i>
                                    <p class="mb-0 fw-bold small">Amex</p>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card text-center p-3">
                                    <i data-feather="credit-card" style="width: 32px; height: 32px;" class="text-primary mx-auto mb-2"></i>
                                    <p class="mb-0 fw-bold small">Discover</p>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-success d-flex align-items-center">
                            <i data-feather="lock" class="me-2"></i>
                            <div><strong>100% Secure:</strong> All payments are processed through Stripe with bank-level encryption. We never store your card details.</div>
                        </div>
                    </div>
                </div>
                @endif

                @if(auth()->user()->isInspector())
                <!-- Inspector: Schedule -->
                <div class="card" id="inspector-schedule">
                    <div class="card-header">
                        <h5><i data-feather="calendar" class="me-2"></i> My Schedule</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Manage your inspection calendar and appointments.</p>

                        <h6 class="mt-3 mb-3">Calendar Overview</h6>
                        <p>Your schedule displays all assigned inspections. Switch between day, week, or month views.</p>

                        <div class="bg-light rounded p-3">
                            <h6>Appointment Color Codes</h6>
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <span class="badge badge-warning px-3 py-2">Pending</span>
                                <span class="badge badge-primary px-3 py-2">Confirmed</span>
                                <span class="badge badge-info px-3 py-2">In Progress</span>
                                <span class="badge badge-success px-3 py-2">Completed</span>
                                <span class="badge badge-danger px-3 py-2">Cancelled</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inspector: Inspections -->
                <div class="card" id="inspector-inspections">
                    <div class="card-header">
                        <h5><i data-feather="clipboard" class="me-2"></i> Performing Inspections</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Complete guide to conducting vehicle inspections.</p>

                        <div class="d-flex align-items-start mb-3 p-3 bg-light rounded">
                            <div class="bg-primary rounded-circle p-2 me-3 text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; min-width: 32px;">
                                <small>1</small>
                            </div>
                            <div>
                                <h6 class="mb-1">Start the Inspection</h6>
                                <p class="text-muted mb-0 small">Select the appointment and tap "Start Inspection". Verify owner identification.</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start mb-3 p-3 bg-light rounded">
                            <div class="bg-primary rounded-circle p-2 me-3 text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; min-width: 32px;">
                                <small>2</small>
                            </div>
                            <div>
                                <h6 class="mb-1">Verify VIN</h6>
                                <p class="text-muted mb-0 small">Scan or manually enter the VIN. Confirm it matches all documentation.</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start mb-3 p-3 bg-light rounded">
                            <div class="bg-primary rounded-circle p-2 me-3 text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; min-width: 32px;">
                                <small>3</small>
                            </div>
                            <div>
                                <h6 class="mb-1">Component Checklist</h6>
                                <p class="text-muted mb-0 small">Review each major component per HSMV 84490 requirements:</p>
                                <ul class="mt-2 mb-0 small text-muted">
                                    <li>Engine and transmission</li>
                                    <li>Body sections (front clip, quarter panels)</li>
                                    <li>Doors, hood, trunk</li>
                                    <li>Airbag systems</li>
                                </ul>
                            </div>
                        </div>

                        <div class="d-flex align-items-start mb-3 p-3 bg-light rounded">
                            <div class="bg-primary rounded-circle p-2 me-3 text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; min-width: 32px;">
                                <small>4</small>
                            </div>
                            <div>
                                <h6 class="mb-1">Capture Evidence</h6>
                                <p class="text-muted mb-0 small">Photograph: VIN locations, all inspected components, part labels, overall vehicle views.</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start mb-4 p-3 bg-light rounded">
                            <div class="bg-primary rounded-circle p-2 me-3 text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; min-width: 32px;">
                                <small>5</small>
                            </div>
                            <div>
                                <h6 class="mb-1">Complete & Sign</h6>
                                <p class="text-muted mb-0 small">Review all data, apply your digital signature, and submit the inspection report.</p>
                            </div>
                        </div>

                        <div class="alert alert-warning d-flex align-items-center">
                            <i data-feather="alert-triangle" class="me-2"></i>
                            <div><strong>Reminder:</strong> Ensure stable internet connection for photo uploads and data synchronization.</div>
                        </div>
                    </div>
                </div>

                <!-- Inspector: Reports -->
                <div class="card" id="inspector-reports">
                    <div class="card-header">
                        <h5><i data-feather="bar-chart-2" class="me-2"></i> Reports & Statistics</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Track your performance and inspection history.</p>

                        <p>Access detailed statistics including:</p>
                        <div class="list-group">
                            <div class="list-group-item d-flex align-items-center">
                                <i data-feather="check-circle" class="me-3 text-success"></i>
                                Monthly inspection count
                            </div>
                            <div class="list-group-item d-flex align-items-center">
                                <i data-feather="percent" class="me-3 text-primary"></i>
                                Pass/fail rates
                            </div>
                            <div class="list-group-item d-flex align-items-center">
                                <i data-feather="clock" class="me-3 text-info"></i>
                                Average inspection duration
                            </div>
                            <div class="list-group-item d-flex align-items-center">
                                <i data-feather="star" class="me-3 text-warning"></i>
                                Customer feedback scores
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @if(auth()->user()->isStaff())
                <!-- Admin: Dashboard -->
                <div class="card" id="admin-dashboard">
                    <div class="card-header">
                        <h5><i data-feather="home" class="me-2"></i> Admin Dashboard</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Real-time system overview and key metrics.</p>

                        <p>The dashboard provides instant visibility into:</p>
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light-primary rounded-circle p-2 me-3">
                                                <i data-feather="briefcase" class="text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">Active Cases</h6>
                                                <small class="text-muted">Cases currently in progress</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light-warning rounded-circle p-2 me-3">
                                                <i data-feather="clock" class="text-warning"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">Pending Review</h6>
                                                <small class="text-muted">Cases awaiting staff action</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light-info rounded-circle p-2 me-3">
                                                <i data-feather="calendar" class="text-info"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">Today's Appointments</h6>
                                                <small class="text-muted">Scheduled inspections for today</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light-success rounded-circle p-2 me-3">
                                                <i data-feather="dollar-sign" class="text-success"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">Monthly Revenue</h6>
                                                <small class="text-muted">Total payments received this month</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin: Cases Management -->
                <div class="card" id="admin-cases">
                    <div class="card-header">
                        <h5><i data-feather="briefcase" class="me-2"></i> Case Management</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Comprehensive case administration tools.</p>

                        <h6 class="mt-3 mb-3">Available Actions</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <i data-feather="eye" class="me-3 text-primary"></i>
                                            <div>
                                                <h6 class="mb-0">View Details</h6>
                                                <small class="text-muted">Access complete case information and history</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <i data-feather="user-check" class="me-3 text-primary"></i>
                                            <div>
                                                <h6 class="mb-0">Assign Inspector</h6>
                                                <small class="text-muted">Designate an inspector for the case</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <i data-feather="refresh-cw" class="me-3 text-primary"></i>
                                            <div>
                                                <h6 class="mb-0">Update Status</h6>
                                                <small class="text-muted">Change case workflow status</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <i data-feather="file-text" class="me-3 text-primary"></i>
                                            <div>
                                                <h6 class="mb-0">Generate Forms</h6>
                                                <small class="text-muted">Create official DMV documents</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin: Users -->
                <div class="card" id="admin-users">
                    <div class="card-header">
                        <h5><i data-feather="users" class="me-2"></i> User Management</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Administer customers, inspectors, and staff accounts.</p>

                        <h6 class="mt-3 mb-3">User Roles & Permissions</h6>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="bg-primary">
                                    <tr>
                                        <th class="text-white">Role</th>
                                        <th class="text-white">Capabilities</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="badge badge-success">Customer</span></td>
                                        <td>Create cases, upload documents, schedule appointments, process payments</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge badge-info">Inspector</span></td>
                                        <td>View assigned cases, conduct inspections, submit reports</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge badge-warning">Staff</span></td>
                                        <td>Manage cases, review documents, assign inspectors, view reports</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge badge-danger">Admin</span></td>
                                        <td>Full system access, user management, system settings</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Admin: Reports -->
                <div class="card" id="admin-reports">
                    <div class="card-header">
                        <h5><i data-feather="bar-chart-2" class="me-2"></i> Reports & Analytics</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Generate comprehensive business intelligence reports.</p>

                        <div class="list-group">
                            <div class="list-group-item d-flex align-items-center">
                                <i data-feather="file-text" class="me-3 text-primary"></i>
                                <div>
                                    <strong>Case Reports</strong>
                                    <small class="text-muted d-block">By status, date range, inspector</small>
                                </div>
                            </div>
                            <div class="list-group-item d-flex align-items-center">
                                <i data-feather="dollar-sign" class="me-3 text-success"></i>
                                <div>
                                    <strong>Financial Reports</strong>
                                    <small class="text-muted d-block">Revenue, payments, refunds</small>
                                </div>
                            </div>
                            <div class="list-group-item d-flex align-items-center">
                                <i data-feather="users" class="me-3 text-info"></i>
                                <div>
                                    <strong>Inspector Performance</strong>
                                    <small class="text-muted d-block">Productivity metrics</small>
                                </div>
                            </div>
                            <div class="list-group-item d-flex align-items-center">
                                <i data-feather="trending-up" class="me-3 text-warning"></i>
                                <div>
                                    <strong>Business Analytics</strong>
                                    <small class="text-muted d-block">Trends and forecasts</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- FAQ -->
                <div class="card" id="faq">
                    <div class="card-header">
                        <h5><i data-feather="help-circle" class="me-2"></i> Frequently Asked Questions</h5>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                        How long does the entire process take?
                                    </button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body text-muted">
                                        The typical timeline is 5-10 business days from document submission to inspection. After a successful inspection, final documents are ready within 1-2 business days.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                        What documents are required to start?
                                    </button>
                                </h2>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body text-muted">
                                        You'll need: Original salvage title, valid photo ID, receipts for all replacement parts, and photos of the reconstruction process (recommended but not required).
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                        Can I reschedule my inspection appointment?
                                    </button>
                                </h2>
                                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body text-muted">
                                        Yes, appointments can be rescheduled or cancelled at least 24 hours in advance at no charge. Late cancellations may incur a rescheduling fee.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                        What if my vehicle fails inspection?
                                    </button>
                                </h2>
                                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body text-muted">
                                        If deficiencies are found, the inspector will document them. You can make corrections and schedule a re-inspection. Re-inspection fees may apply depending on the issues identified.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Support -->
                <div class="card" id="support">
                    <div class="card-header">
                        <h5><i data-feather="headphones" class="me-2"></i> Contact Support</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">We're here to help with any questions or concerns.</p>

                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <div class="card border text-center p-4">
                                    <i data-feather="mail" style="width: 40px; height: 40px;" class="text-primary mx-auto mb-3"></i>
                                    <h6>Email Support</h6>
                                    <p class="small text-muted mb-0">support@rapidrebuilt.com</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border text-center p-4">
                                    <i data-feather="phone" style="width: 40px; height: 40px;" class="text-primary mx-auto mb-3"></i>
                                    <h6>Phone Support</h6>
                                    <p class="small text-muted mb-0">(305) 555-0100</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border text-center p-4">
                                    <i data-feather="message-circle" style="width: 40px; height: 40px;" class="text-primary mx-auto mb-3"></i>
                                    <h6>Live Chat</h6>
                                    <p class="small text-muted mb-0">Mon-Fri, 9AM-6PM EST</p>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4 pt-4 border-top">
                            <p class="text-muted small mb-0">
                                <strong>RapidRebuilt User Manual</strong> | Version 1.0 | Last Updated: {{ date('F Y') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    // Initialize feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Smooth scroll navigation
    document.querySelectorAll('.list-group a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });

                // Update active state
                document.querySelectorAll('.list-group .list-group-item').forEach(link => link.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });

    // Update active nav on scroll
    window.addEventListener('scroll', function() {
        let current = '';
        document.querySelectorAll('.card[id]').forEach(section => {
            const sectionTop = section.offsetTop;
            if (pageYOffset >= sectionTop - 150) {
                current = section.getAttribute('id');
            }
        });

        document.querySelectorAll('.list-group .list-group-item').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + current) {
                link.classList.add('active');
            }
        });
    });
</script>
@endsection
