@extends('layouts.customer.master')

@section('title')Schedule Inspection @endsection

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
.time-slot {
    cursor: pointer;
    transition: all 0.2s;
}
.time-slot:hover {
    background-color: #f8f9fa;
    border-color: #7366ff !important;
}
.time-slot.selected {
    background-color: #7366ff;
    color: white;
    border-color: #7366ff !important;
}
.time-slot.unavailable {
    opacity: 0.5;
    cursor: not-allowed;
    text-decoration: line-through;
}
.calendar-day.available {
    background-color: #d4edda;
}
.calendar-day.selected {
    background-color: #7366ff;
    color: white;
}
</style>
@endpush

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Schedule Inspection</h3>
        @endslot
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customer.cases.show', $case) }}">Case #{{ $case->case_number }}</a></li>
        <li class="breadcrumb-item active">Schedule</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Select Date & Time</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('customer.appointments.store') }}" method="POST" id="scheduleForm">
                            @csrf
                            <input type="hidden" name="case_id" value="{{ $case->id }}">
                            <input type="hidden" name="selected_date" id="selectedDate">
                            <input type="hidden" name="selected_time" id="selectedTime">

                            <!-- Calendar -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Select Date</label>
                                    <div id="datePicker"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Available Time Slots</label>
                                    <div id="timeSlots">
                                        <p class="text-muted">Please select a date first</p>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <!-- Inspection Type -->
                            <div class="mb-4">
                                <label class="form-label">Inspection Type</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card border h-100" onclick="selectInspectionType('office')" id="typeOffice">
                                            <div class="card-body text-center">
                                                <i data-feather="home" style="width: 48px; height: 48px;" class="text-primary mb-3"></i>
                                                <h5>Office Inspection</h5>
                                                <p class="text-muted mb-0">Bring your vehicle to our inspection center</p>
                                                <p class="text-success mt-2 mb-0"><strong>No additional fee</strong></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border h-100" onclick="selectInspectionType('mobile')" id="typeMobile">
                                            <div class="card-body text-center">
                                                <i data-feather="truck" style="width: 48px; height: 48px;" class="text-info mb-3"></i>
                                                <h5>Mobile Inspection</h5>
                                                <p class="text-muted mb-0">We come to your location</p>
                                                <p class="text-primary mt-2 mb-0"><strong>+US${{ number_format($pricing['mobile_inspection_fee'] ?? 75, 2, '.', ',') }}</strong></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="inspection_type" id="inspectionType" value="office">
                            </div>

                            <!-- Mobile Location -->
                            <div id="mobileLocationSection" style="display: none;">
                                <div class="mb-4">
                                    <label class="form-label">Inspection Address</label>
                                    <input type="text" name="address" class="form-control" placeholder="Street Address">
                                </div>
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">City</label>
                                        <input type="text" name="city" class="form-control" placeholder="City">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">State</label>
                                        <input type="text" name="state" class="form-control" value="FL" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">ZIP</label>
                                        <input type="text" name="zip" class="form-control" placeholder="ZIP Code">
                                    </div>
                                </div>
                                <div class="alert alert-info">
                                    <i data-feather="info"></i>
                                    Mobile inspections are available within 50 miles of our office. Additional travel fees may apply for distances beyond this.
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="mb-4">
                                <label class="form-label">Additional Notes (Optional)</label>
                                <textarea name="notes" class="form-control" rows="3"
                                          placeholder="Any special instructions or gate codes..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" disabled>
                                <i data-feather="calendar"></i> Confirm Appointment
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-xl-4">
                <!-- Case Summary -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Case Summary</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td class="text-muted">Case #:</td>
                                <td><strong>{{ $case->case_number }}</strong></td>
                            </tr>
                            @if($case->vehicle)
                                <tr>
                                    <td class="text-muted">Vehicle:</td>
                                    <td>{{ $case->vehicle->year }} {{ $case->vehicle->make }} {{ $case->vehicle->model }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">VIN:</td>
                                    <td><small>{{ $case->vehicle->vin }}</small></td>
                                </tr>
                            @endif
                            <tr>
                                <td class="text-muted">Components:</td>
                                <td>{{ $case->components->count() }} parts</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Selected Appointment -->
                <div class="card mb-4" id="appointmentSummary" style="display: none;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Selected Appointment</h5>
                    </div>
                    <div class="card-body text-center">
                        <h4 id="summaryDate">-</h4>
                        <h3 class="text-primary" id="summaryTime">-</h3>
                        <p class="text-muted" id="summaryType">Office Inspection</p>
                    </div>
                </div>

                <!-- What to Expect -->
                <div class="card">
                    <div class="card-header">
                        <h5><i data-feather="info"></i> What to Expect</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i data-feather="clock" class="text-primary me-2"></i>
                                Inspection takes 30-60 minutes
                            </li>
                            <li class="mb-2">
                                <i data-feather="camera" class="text-primary me-2"></i>
                                Photos of vehicle and components
                            </li>
                            <li class="mb-2">
                                <i data-feather="clipboard" class="text-primary me-2"></i>
                                VIN verification
                            </li>
                            <li class="mb-2">
                                <i data-feather="check-circle" class="text-primary me-2"></i>
                                Component verification
                            </li>
                            <li>
                                <i data-feather="file-text" class="text-primary me-2"></i>
                                Documentation completion
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
let selectedDate = null;
let selectedTime = null;
let inspectionType = 'office';

// Initialize date picker
const datePicker = flatpickr("#datePicker", {
    inline: true,
    minDate: "today",
    maxDate: new Date().fp_incr(60), // 60 days ahead
    disable: [
        function(date) {
            // Disable weekends
            return (date.getDay() === 0 || date.getDay() === 6);
        }
    ],
    onChange: function(selectedDates, dateStr) {
        selectedDate = dateStr;
        document.getElementById('selectedDate').value = dateStr;
        loadTimeSlots(dateStr);
    }
});

function loadTimeSlots(date) {
    document.getElementById('timeSlots').innerHTML = `
        <div class="text-center py-3">
            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
            <span class="ms-2">Loading available times...</span>
        </div>
    `;

    $.ajax({
        url: '{{ route("api.appointments.slots") }}',
        method: 'GET',
        data: { date: date },
        success: function(response) {
            if (response.success && response.data.length > 0) {
                let html = '<div class="row g-2">';
                response.data.forEach(function(slot) {
                    // The API omits `available` for open slots and exposes the
                    // human-readable label as `formatted`.
                    const isAvailable = slot.available ?? true;
                    const unavailableClass = isAvailable ? '' : 'unavailable';
                    const display = slot.display ?? slot.formatted;
                    html += `
                        <div class="col-6">
                            <div class="card time-slot ${unavailableClass}" data-time="${slot.time}" onclick="selectTimeSlot(this, '${slot.time}', ${isAvailable})">
                                <div class="card-body text-center py-2">
                                    <strong>${display}</strong>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                document.getElementById('timeSlots').innerHTML = html;
            } else {
                document.getElementById('timeSlots').innerHTML = `
                    <div class="alert alert-warning">
                        No available time slots for this date. Please select another date.
                    </div>
                `;
            }
        },
        error: function() {
            document.getElementById('timeSlots').innerHTML = `
                <div class="alert alert-danger">
                    Error loading time slots. Please try again.
                </div>
            `;
        }
    });
}

function selectTimeSlot(element, time, available) {
    if (!available) return;

    document.querySelectorAll('.time-slot').forEach(el => el.classList.remove('selected'));
    element.classList.add('selected');

    selectedTime = time;
    document.getElementById('selectedTime').value = time;

    updateSummary();
    checkFormValidity();
}

function selectInspectionType(type) {
    inspectionType = type;
    document.getElementById('inspectionType').value = type;

    document.getElementById('typeOffice').classList.toggle('border-primary', type === 'office');
    document.getElementById('typeMobile').classList.toggle('border-primary', type === 'mobile');
    document.getElementById('typeOffice').querySelector('.card-body').classList.toggle('bg-light', type === 'office');
    document.getElementById('typeMobile').querySelector('.card-body').classList.toggle('bg-light', type === 'mobile');

    document.getElementById('mobileLocationSection').style.display = type === 'mobile' ? 'block' : 'none';

    updateSummary();
}

function updateSummary() {
    if (selectedDate && selectedTime) {
        document.getElementById('appointmentSummary').style.display = 'block';

        const date = new Date(selectedDate);
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('summaryDate').textContent = date.toLocaleDateString('en-US', options);
        document.getElementById('summaryTime').textContent = selectedTime;
        document.getElementById('summaryType').textContent = inspectionType === 'mobile' ? 'Mobile Inspection' : 'Office Inspection';
    }
}

function checkFormValidity() {
    const valid = selectedDate && selectedTime;
    document.getElementById('submitBtn').disabled = !valid;
}

// Initialize
selectInspectionType('office');
</script>
@endpush
