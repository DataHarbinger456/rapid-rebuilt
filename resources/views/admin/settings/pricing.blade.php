@extends('layouts.admin.master')

@section('title')Pricing Settings @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Pricing Settings</h3>
        @endslot
        <li class="breadcrumb-item">Admin</li>
        <li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></li>
        <li class="breadcrumb-item active">Pricing</li>
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
                        <h5>Service Pricing</h5>
                        <span class="text-muted">Configure pricing for all services</span>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.settings.pricing.update') }}" method="POST" id="pricingForm">
                            @csrf

                            <div class="mb-4">
                                <label class="form-label">Base Inspection Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control form-control-lg" name="base_price"
                                           value="{{ $pricing['base_price'] ?? $defaults['base_price'] }}" required>
                                </div>
                                <small class="text-muted">Standard price for a rebuilt title inspection</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Rush Processing Fee</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" name="rush_fee"
                                           value="{{ $pricing['rush_fee'] ?? $defaults['rush_fee'] }}" required>
                                </div>
                                <small class="text-muted">Additional fee for expedited processing</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Component Verification Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" name="component_price"
                                           value="{{ $pricing['component_price'] ?? $defaults['component_price'] }}" required>
                                </div>
                                <small class="text-muted">Price per major component to verify</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Mobile Inspection Fee</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" name="mobile_inspection_fee"
                                           value="{{ $pricing['mobile_inspection_fee'] ?? $defaults['mobile_inspection_fee'] }}" required>
                                </div>
                                <small class="text-muted">Additional fee for on-site inspections</small>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary" onclick="resetDefaults()">Reset to Defaults</button>
                                <button type="submit" class="btn btn-primary btn-lg">Save Pricing</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Price Calculator</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Number of Components</label>
                            <input type="number" class="form-control" id="calcComponents" value="5" min="0">
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="calcRush">
                            <label class="form-check-label" for="calcRush">Rush Processing</label>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="calcMobile">
                            <label class="form-check-label" for="calcMobile">Mobile Inspection</label>
                        </div>
                        <hr>
                        <div class="text-center">
                            <small class="text-muted">Estimated Total</small>
                            <h2 class="text-primary" id="calcTotal">US$0.00</h2>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5>Default Values</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex justify-content-between mb-2">
                                <span>Base Price:</span>
                                <strong>US${{ number_format($defaults['base_price'], 2, '.', ',') }}</strong>
                            </li>
                            <li class="d-flex justify-content-between mb-2">
                                <span>Rush Fee:</span>
                                <strong>US${{ number_format($defaults['rush_fee'], 2, '.', ',') }}</strong>
                            </li>
                            <li class="d-flex justify-content-between mb-2">
                                <span>Per Component:</span>
                                <strong>US${{ number_format($defaults['component_price'], 2, '.', ',') }}</strong>
                            </li>
                            <li class="d-flex justify-content-between">
                                <span>Mobile Fee:</span>
                                <strong>US${{ number_format($defaults['mobile_inspection_fee'], 2, '.', ',') }}</strong>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
function calculateTotal() {
    const basePrice = parseFloat(document.querySelector('[name="base_price"]').value) || 0;
    const componentPrice = parseFloat(document.querySelector('[name="component_price"]').value) || 0;
    const rushFee = parseFloat(document.querySelector('[name="rush_fee"]').value) || 0;
    const mobileFee = parseFloat(document.querySelector('[name="mobile_inspection_fee"]').value) || 0;

    const components = parseInt(document.getElementById('calcComponents').value) || 0;
    const isRush = document.getElementById('calcRush').checked;
    const isMobile = document.getElementById('calcMobile').checked;

    let total = basePrice + (components * componentPrice);
    if (isRush) total += rushFee;
    if (isMobile) total += mobileFee;

    document.getElementById('calcTotal').textContent = 'US$' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function resetDefaults() {
    document.querySelector('[name="base_price"]').value = {{ $defaults['base_price'] }};
    document.querySelector('[name="rush_fee"]').value = {{ $defaults['rush_fee'] }};
    document.querySelector('[name="component_price"]').value = {{ $defaults['component_price'] }};
    document.querySelector('[name="mobile_inspection_fee"]').value = {{ $defaults['mobile_inspection_fee'] }};
    calculateTotal();
}

document.querySelectorAll('#pricingForm input').forEach(input => {
    input.addEventListener('input', calculateTotal);
});
document.getElementById('calcComponents').addEventListener('input', calculateTotal);
document.getElementById('calcRush').addEventListener('change', calculateTotal);
document.getElementById('calcMobile').addEventListener('change', calculateTotal);

calculateTotal();
</script>
@endpush
