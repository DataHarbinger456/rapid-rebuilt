@extends('layouts.customer.master')

@section('title')Edit Case @endsection

@push('css')
<style>
.wizard-steps {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2rem;
    position: relative;
}
.wizard-steps::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 10%;
    right: 10%;
    height: 2px;
    background: #e9ecef;
    z-index: 0;
}
.wizard-step {
    text-align: center;
    position: relative;
    z-index: 1;
    flex: 1;
}
.wizard-step .step-number {
    width: 40px;
    height: 40px;
    line-height: 40px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    font-weight: bold;
    margin: 0 auto 0.5rem;
    transition: all 0.3s;
}
.wizard-step.active .step-number,
.wizard-step.completed .step-number {
    background: #7366ff;
    color: #fff;
}
.wizard-step.completed .step-number {
    background: #28a745;
}
.wizard-step .step-title {
    font-size: 0.875rem;
    color: #6c757d;
}
.wizard-step.active .step-title {
    color: #7366ff;
    font-weight: 600;
}
.step-content {
    display: none;
}
.step-content.active {
    display: block;
}
</style>
@endpush

@php
    // Old input wins after a validation bounce; otherwise map the saved
    // components back onto the form's field names. Always at least one row.
    $componentRows = old('components') ?: $case->components->map(fn ($component) => [
        'type' => $component->part_type,
        'source' => $component->condition,
        'source_vin' => $component->serial_number ?? $component->receipt_number,
        'description' => $component->other_description,
    ])->all();

    if (empty($componentRows)) {
        $componentRows = [['type' => '', 'source' => '', 'source_vin' => '', 'description' => '']];
    }
@endphp

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Edit Case {{ $case->case_number }}</h3>
        @endslot
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customer.cases.index') }}">Cases</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customer.cases.show', $case) }}">{{ $case->case_number }}</a></li>
        <li class="breadcrumb-item active">Edit</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <div class="card">
                    <div class="card-body">
                        <!-- Wizard Steps -->
                        <div class="wizard-steps">
                            <div class="wizard-step active" data-step="1">
                                <div class="step-number">1</div>
                                <div class="step-title">Vehicle Info</div>
                            </div>
                            <div class="wizard-step" data-step="2">
                                <div class="step-number">2</div>
                                <div class="step-title">Components</div>
                            </div>
                            <div class="wizard-step" data-step="3">
                                <div class="step-number">3</div>
                                <div class="step-title">Review</div>
                            </div>
                        </div>

                        <form id="caseWizardForm" action="{{ route('customer.cases.update', $case) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="current_step" id="currentStep" value="1">

                            <!-- Step 1: Vehicle Information -->
                            <div class="step-content active" data-step="1">
                                <h4 class="mb-4">Vehicle Information</h4>

                                <div class="row mb-4">
                                    <div class="col-md-8">
                                        <label class="form-label">Vehicle Identification Number (VIN) *</label>
                                        <div class="input-group">
                                            <input type="text" name="vin" id="vinInput" class="form-control form-control-lg"
                                                   maxlength="17" placeholder="Enter 17-character VIN" required
                                                   value="{{ old('vin', $case->vehicle?->vin) }}">
                                            <button type="button" class="btn btn-primary" id="decodeVinBtn">
                                                <i data-feather="search"></i> Decode VIN
                                            </button>
                                        </div>
                                        <small class="text-muted">Re-decode to refresh the details below, or edit them directly.</small>
                                    </div>
                                </div>

                                {{-- Unlike the create wizard these stay visible and editable:
                                     the vehicle already exists, so there is nothing to reveal. --}}
                                <div id="vehicleDetails">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Year *</label>
                                            <input type="number" name="year" id="vehicleYear" class="form-control" required
                                                   value="{{ old('year', $case->vehicle?->year) }}">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Make *</label>
                                            <input type="text" name="make" id="vehicleMake" class="form-control" required
                                                   value="{{ old('make', $case->vehicle?->make) }}">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Model *</label>
                                            <input type="text" name="model" id="vehicleModel" class="form-control" required
                                                   value="{{ old('model', $case->vehicle?->model) }}">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Body Style</label>
                                            <input type="text" name="body_style" id="vehicleBody" class="form-control"
                                                   value="{{ old('body_style', $case->vehicle?->body_type) }}">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Engine</label>
                                            <input type="text" name="engine" id="vehicleEngine" class="form-control"
                                                   value="{{ old('engine', $case->vehicle?->engine_type) }}">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Color *</label>
                                            @php($selectedColor = strtolower((string) old('color', $case->vehicle?->color)))
                                            <select name="color" class="form-select" required>
                                                <option value="">Select Color</option>
                                                @foreach (['white', 'black', 'silver', 'gray', 'red', 'blue', 'green', 'other'] as $colorOption)
                                                    <option value="{{ $colorOption }}" @selected($selectedColor === $colorOption)>
                                                        {{ ucfirst($colorOption) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Odometer Reading (Miles) *</label>
                                            <input type="number" name="odometer" class="form-control" required min="0"
                                                   placeholder="Current mileage"
                                                   value="{{ old('odometer', $case->vehicle?->odometer_reading) }}">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Title State *</label>
                                            @php($selectedState = old('title_state', $case->vehicle?->title_state))
                                            <select name="title_state" class="form-select" required>
                                                <option value="">Select State</option>
                                                @foreach (\App\Models\Vehicle::US_STATES as $code => $name)
                                                    <option value="{{ $code }}" @selected($selectedState === $code)>{{ $name }}</option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted">The state that issued the current title.</small>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Vehicle Type *</label>
                                            @php($selectedType = old('vehicle_type', $case->vehicle?->vehicle_type ?? 'rebuilt'))
                                            <select name="vehicle_type" class="form-select" required>
                                                @foreach (\App\Models\Vehicle::VEHICLE_TYPES as $value => $label)
                                                    <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted">How the vehicle was built. This sets the type checked in Section 3 of the HSMV 82040.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2: Components -->
                            <div class="step-content" data-step="2">
                                <h4 class="mb-4">Major Component Parts</h4>
                                <p class="text-muted mb-4">List all major parts that have been replaced during the rebuild. This information is required for the HSMV 84491 form.</p>

                                <div id="componentsList">
                                    @foreach ($componentRows as $rowIndex => $row)
                                        <div class="component-item card mb-3">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-3 mb-2">
                                                        <label class="form-label">Component Type *</label>
                                                        <select name="components[{{ $rowIndex }}][type]" class="form-select" required>
                                                            <option value="">Select Type</option>
                                                            @foreach (\App\Models\Component::PART_TYPES as $partValue => $partLabel)
                                                                <option value="{{ $partValue }}"
                                                                    @selected(($row['type'] ?? null) === $partValue)>{{ $partLabel }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3 mb-2">
                                                        <label class="form-label">Source *</label>
                                                        <select name="components[{{ $rowIndex }}][source]" class="form-select" required>
                                                            <option value="">Select Source</option>
                                                            @foreach (\App\Models\Component::CONDITIONS as $conditionValue => $conditionLabel)
                                                                <option value="{{ $conditionValue }}"
                                                                    @selected(($row['source'] ?? null) === $conditionValue)>{{ $conditionLabel }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4 mb-2">
                                                        <label class="form-label">Source VIN/Invoice #</label>
                                                        <input type="text" name="components[{{ $rowIndex }}][source_vin]" class="form-control"
                                                               placeholder="VIN or Invoice Number"
                                                               value="{{ $row['source_vin'] ?? '' }}">
                                                    </div>
                                                    <div class="col-md-2 mb-2 d-flex align-items-end">
                                                        <button type="button" class="btn btn-outline-danger w-100 remove-component"
                                                            @disabled(count($componentRows) <= 1)>
                                                            <i data-feather="trash-2"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="row mt-2">
                                                    <div class="col-md-12">
                                                        <label class="form-label">Description/Notes</label>
                                                        <input type="text" name="components[{{ $rowIndex }}][description]" class="form-control"
                                                               placeholder="Additional details about this component"
                                                               value="{{ $row['description'] ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <button type="button" class="btn btn-outline-primary" id="addComponentBtn">
                                    <i data-feather="plus-circle"></i> Add Another Component
                                </button>
                            </div>

                            <!-- Step 3: Review -->
                            <div class="step-content" data-step="3">
                                <h4 class="mb-4">Review & Submit</h4>

                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6><i data-feather="truck"></i> Vehicle Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="reviewVehicle">
                                            <!-- Populated by JavaScript -->
                                        </div>
                                    </div>
                                </div>

                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6><i data-feather="settings"></i> Components</h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="reviewComponents">
                                            <!-- Populated by JavaScript -->
                                        </div>
                                    </div>
                                </div>

                                {{-- Documents are not edited here: this wizard posts no files, so
                                     uploads stay on the documents screen and keep their review state. --}}
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i data-feather="file"></i> Documents</h6>
                                        <a href="{{ route('customer.cases.documents', $case) }}" class="btn btn-sm btn-outline-primary">
                                            Manage Documents
                                        </a>
                                    </div>
                                    <div class="card-body">
                                        @forelse($case->documents as $document)
                                            <div class="d-flex justify-content-between align-items-center {{ !$loop->last ? 'mb-2' : '' }}">
                                                <span>
                                                    <i data-feather="file-text"></i>
                                                    {{ $document->original_name }}
                                                </span>
                                                <span class="badge badge-light text-dark">{{ ucfirst($document->status) }}</span>
                                            </div>
                                        @empty
                                            <p class="text-muted mb-0">No documents uploaded yet. Use “Manage Documents” to add them.</p>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="card mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0"><i data-feather="dollar-sign"></i> Estimated Cost</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <table class="table table-sm">
                                                    <tr>
                                                        <td>Base Inspection Fee:</td>
                                                        <td class="text-end">US${{ number_format($pricing['base_price'] ?? 150, 2, '.', ',') }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Component Verification (<span id="componentCount">{{ count($componentRows) }}</span>):</td>
                                                        <td class="text-end" id="componentCost">US$0.00</td>
                                                    </tr>
                                                    <tr class="table-primary">
                                                        <td><strong>Estimated Total:</strong></td>
                                                        <td class="text-end"><strong id="totalCost">US${{ number_format($pricing['base_price'] ?? 150, 2, '.', ',') }}</strong></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                        <small class="text-muted">* Final price may vary based on inspection requirements</small>
                                    </div>
                                </div>

                                <div class="form-check mb-4">
                                    <input type="checkbox" class="form-check-input" id="termsAgreement" required>
                                    <label class="form-check-label" for="termsAgreement">
                                        I certify that the information provided is accurate and I agree to the
                                        <a href="#" target="_blank">Terms of Service</a> and <a href="#" target="_blank">Privacy Policy</a>. *
                                    </label>
                                </div>
                            </div>

                            <!-- Navigation Buttons -->
                            <div class="d-flex justify-content-between mt-4 pt-4 border-top">
                                <button type="button" class="btn btn-outline-secondary" id="prevBtn" style="display: none;">
                                    <i data-feather="arrow-left"></i> Previous
                                </button>
                                <div class="ms-auto">
                                    <a href="{{ route('customer.cases.show', $case) }}" class="btn btn-light me-2">Cancel</a>
                                    <button type="button" class="btn btn-secondary me-2" id="saveDraftBtn">
                                        <i data-feather="save"></i> Save Draft
                                    </button>
                                    <button type="button" class="btn btn-primary" id="nextBtn">
                                        Next <i data-feather="arrow-right"></i>
                                    </button>
                                    <button type="submit" class="btn btn-success" id="submitBtn" style="display: none;">
                                        <i data-feather="check-circle"></i> Submit Case
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
let currentStep = 1;
const totalSteps = 3;
const basePrice = {{ $pricing['base_price'] ?? 150 }};
const componentPrice = {{ $pricing['component_price'] ?? 10 }};
let componentIndex = {{ count($componentRows) }};

$(document).ready(function() {
    updateNavigation();

    // VIN Decode — refreshes the already-visible fields.
    $('#decodeVinBtn').click(function() {
        const vin = $('#vinInput').val().trim().toUpperCase();
        if (vin.length !== 17) {
            swal('Error', 'Please enter a valid 17-character VIN', 'error');
            return;
        }

        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Decoding...');

        $.ajax({
            url: '{{ route("api.vehicles.decode-vin") }}',
            method: 'POST',
            data: { vin: vin },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    $('#vehicleYear').val(data.year);
                    $('#vehicleMake').val(data.make);
                    $('#vehicleModel').val(data.model);
                    $('#vehicleBody').val(data.body_type || '');
                    $('#vehicleEngine').val(data.engine_type || '');
                } else {
                    swal('Error', response.message || 'Failed to decode VIN', 'error');
                }
            },
            error: function() {
                swal('Error', 'Failed to decode VIN. Please check and try again.', 'error');
            },
            complete: function() {
                $('#decodeVinBtn').prop('disabled', false).html('<i data-feather="search"></i> Decode VIN');
                feather.replace();
            }
        });
    });

    // Add Component
    $('#addComponentBtn').click(function() {
        const template = `
            <div class="component-item card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Component Type *</label>
                            <select name="components[${componentIndex}][type]" class="form-select" required>
                                <option value="">Select Type</option>
                                @foreach (\App\Models\Component::PART_TYPES as $partValue => $partLabel)
                                    <option value="{{ $partValue }}">{{ $partLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Source *</label>
                            <select name="components[${componentIndex}][source]" class="form-select" required>
                                <option value="">Select Source</option>
                                @foreach (\App\Models\Component::CONDITIONS as $conditionValue => $conditionLabel)
                                    <option value="{{ $conditionValue }}">{{ $conditionLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Source VIN/Invoice #</label>
                            <input type="text" name="components[${componentIndex}][source_vin]" class="form-control"
                                   placeholder="VIN or Invoice Number">
                        </div>
                        <div class="col-md-2 mb-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger w-100 remove-component">
                                <i data-feather="trash-2"></i>
                            </button>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <label class="form-label">Description/Notes</label>
                            <input type="text" name="components[${componentIndex}][description]" class="form-control"
                                   placeholder="Additional details about this component">
                        </div>
                    </div>
                </div>
            </div>
        `;
        $('#componentsList').append(template);
        componentIndex++;
        feather.replace();
        updateRemoveButtons();
    });

    // Remove Component
    $(document).on('click', '.remove-component', function() {
        $(this).closest('.component-item').remove();
        updateRemoveButtons();
    });

    // Navigation
    $('#nextBtn').click(function() {
        if (validateStep(currentStep)) {
            currentStep++;
            showStep(currentStep);
            updateNavigation();
            if (currentStep === totalSteps) {
                populateReview();
            }
        }
    });

    $('#prevBtn').click(function() {
        currentStep--;
        showStep(currentStep);
        updateNavigation();
    });

    // Save Draft
    $('#saveDraftBtn').click(function() {
        $('#currentStep').val(currentStep);
        const form = $('#caseWizardForm');
        form.append('<input type="hidden" name="save_draft" value="1">');
        form.submit();
    });
});

function updateRemoveButtons() {
    const items = $('.component-item');
    items.find('.remove-component').prop('disabled', items.length <= 1);
}

function showStep(step) {
    $('.step-content').removeClass('active');
    $(`.step-content[data-step="${step}"]`).addClass('active');

    $('.wizard-step').removeClass('active completed');
    $(`.wizard-step[data-step="${step}"]`).addClass('active');
    for (let i = 1; i < step; i++) {
        $(`.wizard-step[data-step="${i}"]`).addClass('completed');
    }
}

function updateNavigation() {
    $('#prevBtn').toggle(currentStep > 1);
    $('#nextBtn').toggle(currentStep < totalSteps);
    $('#submitBtn').toggle(currentStep === totalSteps);
}

function validateStep(step) {
    const stepContent = $(`.step-content[data-step="${step}"]`);
    const inputs = stepContent.find('input[required], select[required]');
    let valid = true;

    inputs.each(function() {
        if (!this.checkValidity()) {
            $(this).addClass('is-invalid');
            valid = false;
        } else {
            $(this).removeClass('is-invalid');
        }
    });

    if (!valid) {
        swal('Please fill in all required fields', '', 'warning');
    }

    return valid;
}

function populateReview() {
    // Vehicle Review — reads the live inputs, same as the create wizard.
    const vehicleHtml = `
        <div class="row">
            <div class="col-md-4"><strong>VIN:</strong> ${$('#vinInput').val()}</div>
            <div class="col-md-4"><strong>Year:</strong> ${$('#vehicleYear').val()}</div>
            <div class="col-md-4"><strong>Make:</strong> ${$('#vehicleMake').val()}</div>
            <div class="col-md-4"><strong>Model:</strong> ${$('#vehicleModel').val()}</div>
            <div class="col-md-4"><strong>Color:</strong> ${$('[name="color"]').val()}</div>
            <div class="col-md-4"><strong>Odometer:</strong> ${$('[name="odometer"]').val()} mi</div>
        </div>
    `;
    $('#reviewVehicle').html(vehicleHtml);

    // Components Review
    const componentCount = $('.component-item').length;
    let componentsHtml = `<p>${componentCount} component(s) listed</p><ul>`;
    $('.component-item').each(function() {
        const type = $(this).find('[name*="[type]"]').val();
        const source = $(this).find('[name*="[source]"]').val();
        componentsHtml += `<li>${type} (${source})</li>`;
    });
    componentsHtml += '</ul>';
    $('#reviewComponents').html(componentsHtml);

    // Update pricing
    $('#componentCount').text(componentCount);
    const compCost = componentCount * componentPrice;
    $('#componentCost').text('US$' + compCost.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#totalCost').text('US$' + (basePrice + compCost).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
}
</script>
@endpush
