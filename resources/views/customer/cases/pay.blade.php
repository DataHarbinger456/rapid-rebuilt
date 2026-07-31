@extends('layouts.customer.master')

@section('title')Payment - Case #{{ $case->case_number }} @endsection

@push('css')
<style>
.payment-card {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    transition: all 0.3s;
}
.payment-card:hover {
    border-color: #7366ff;
}
.payment-card.selected {
    border-color: #7366ff;
    background: #f8f9ff;
}
.price-breakdown {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
}
.price-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e9ecef;
}
.price-row:last-child {
    border-bottom: none;
}
.price-row.total {
    font-size: 1.25rem;
    font-weight: bold;
    border-top: 2px solid #dee2e6;
    margin-top: 0.5rem;
    padding-top: 1rem;
}
.secure-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #28a745;
    font-size: 0.875rem;
}
</style>
@endpush

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Payment</h3>
        @endslot
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customer.cases.index') }}">Cases</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customer.cases.show', $case) }}">{{ $case->case_number }}</a></li>
        <li class="breadcrumb-item active">Payment</li>
    @endcomponent

    <div class="container-fluid">
        @if (session('error'))
            <div class="alert alert-danger" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <div class="row">
            <!-- Order Summary -->
            <div class="col-lg-5 order-lg-2 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i data-feather="file-text"></i> Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <!-- Vehicle Info -->
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Vehicle</h6>
                            @if($case->vehicle)
                                <p class="mb-1"><strong>{{ $case->vehicle->year }} {{ $case->vehicle->make }} {{ $case->vehicle->model }}</strong></p>
                                <p class="text-muted small mb-0">VIN: {{ $case->vehicle->vin }}</p>
                            @else
                                <p class="text-muted">No vehicle information</p>
                            @endif
                        </div>

                        <!-- Price Breakdown -->
                        <div class="price-breakdown">
                            <div class="price-row">
                                <span>Rebuilt Title Inspection Service</span>
                                <span>${{ number_format($case->base_price ?? 150, 2) }}</span>
                            </div>

                            @if($case->is_rush)
                                <div class="price-row">
                                    <span>Rush Processing</span>
                                    <span>${{ number_format($case->rush_fee ?? 50, 2) }}</span>
                                </div>
                            @endif

                            @if($case->components && $case->components->count() > 0)
                                <div class="price-row">
                                    <span>Component Verification ({{ $case->components->count() }})</span>
                                    <span>${{ number_format(($case->component_price ?? 10) * $case->components->count(), 2) }}</span>
                                </div>
                            @endif

                            @if($case->inspection_type === 'mobile')
                                <div class="price-row">
                                    <span>Mobile Inspection Fee</span>
                                    <span>${{ number_format($case->mobile_fee ?? 75, 2) }}</span>
                                </div>
                            @endif

                            <div class="price-row total">
                                <span>Total</span>
                                <span>${{ number_format($case->total_amount ?? 0, 2) }}</span>
                            </div>
                        </div>

                        <div class="secure-badge mt-3">
                            <i data-feather="lock"></i>
                            <span>Secure payment powered by Stripe</span>
                        </div>
                    </div>
                </div>

                <!-- What's Included -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i data-feather="check-circle"></i> What's Included</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><i data-feather="check" class="text-success me-2"></i> Complete vehicle inspection</li>
                            <li class="mb-2"><i data-feather="check" class="text-success me-2"></i> HSMV 84490 form preparation</li>
                            <li class="mb-2"><i data-feather="check" class="text-success me-2"></i> Component verification</li>
                            <li class="mb-2"><i data-feather="check" class="text-success me-2"></i> Photo documentation</li>
                            <li class="mb-2"><i data-feather="check" class="text-success me-2"></i> DMV-ready document package</li>
                            <li><i data-feather="check" class="text-success me-2"></i> Customer support</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <div class="col-lg-7 order-lg-1">
                <div class="card">
                    <div class="card-header">
                        <h5><i data-feather="credit-card"></i> Payment Details</h5>
                    </div>
                    <div class="card-body">
                        <form id="payment-form" action="{{ route('customer.cases.process-payment', $case) }}" method="POST">
                            @csrf

                            <!-- Payment Method Selection -->
                            <div class="mb-4">
                                <label class="form-label">Select Payment Method</label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="payment-card p-3 selected" data-method="card">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="payment_method" id="methodCard" value="card" checked>
                                                <label class="form-check-label" for="methodCard">
                                                    <strong>Credit/Debit Card</strong>
                                                    <div class="text-muted small">Visa, Mastercard, Amex</div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="payment-card p-3" data-method="ach">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="payment_method" id="methodACH" value="ach">
                                                <label class="form-check-label" for="methodACH">
                                                    <strong>Bank Transfer (ACH)</strong>
                                                    <div class="text-muted small">Direct from bank account</div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Payment Section -->
                            <div id="card-payment-section">
                                <div class="mb-3">
                                    <label for="card-element" class="form-label">Card Information</label>
                                    <div id="card-element" class="form-control" style="padding: 12px; height: auto;">
                                        <!-- Stripe Elements will be inserted here -->
                                    </div>
                                    <div id="card-errors" class="text-danger mt-2" role="alert"></div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="billing_name" class="form-label">Cardholder Name</label>
                                        <input type="text" class="form-control" id="billing_name" name="billing_name"
                                               value="{{ auth()->user()->name }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="billing_zip" class="form-label">Billing ZIP Code</label>
                                        <input type="text" class="form-control" id="billing_zip" name="billing_zip"
                                               value="{{ auth()->user()->zip }}" required>
                                    </div>
                                </div>
                            </div>

                            <!-- ACH Payment Section (hidden by default) -->
                            <div id="ach-payment-section" style="display: none;">
                                <div class="alert alert-info">
                                    <i data-feather="info"></i>
                                    Bank transfers typically take 3-5 business days to process. Your case will proceed once payment is confirmed.
                                </div>
                                <div class="mb-3">
                                    <label for="account_holder_name" class="form-label">Account Holder Name</label>
                                    <input type="text" class="form-control" id="account_holder_name" name="account_holder_name">
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="routing_number" class="form-label">Routing Number</label>
                                        <input type="text" class="form-control" id="routing_number" name="routing_number" maxlength="9">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="account_number" class="form-label">Account Number</label>
                                        <input type="text" class="form-control" id="account_number" name="account_number">
                                    </div>
                                </div>
                            </div>

                            <!-- Terms -->
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="agreeTerms" name="agree_terms" required>
                                    <label class="form-check-label" for="agreeTerms">
                                        I agree to the <a href="#" target="_blank">Terms of Service</a> and <a href="#" target="_blank">Refund Policy</a>
                                    </label>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary btn-lg w-100" id="submit-payment">
                                <i data-feather="lock" class="me-2"></i>
                                Pay ${{ number_format($case->total_amount ?? 0, 2) }}
                            </button>

                            <p class="text-center text-muted mt-3 small">
                                Your payment information is encrypted and secure.
                            </p>
                        </form>
                    </div>
                </div>

                <!-- Questions -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h6><i data-feather="help-circle"></i> Have Questions?</h6>
                        <p class="mb-0 text-muted">
                            Contact us at <a href="mailto:support@rapidrebuilt.com">support@rapidrebuilt.com</a>
                            or call <a href="tel:+18001234567">(800) 123-4567</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
$(document).ready(function() {
    // Initialize Stripe
    const stripe = Stripe('{{ config("services.stripe.key") }}');
    const elements = stripe.elements();

    // Create card element
    const cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#495057',
                '::placeholder': {
                    color: '#6c757d'
                }
            },
            invalid: {
                color: '#dc3545'
            }
        }
    });
    cardElement.mount('#card-element');

    // Handle card errors
    cardElement.on('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });

    // Payment method selection
    $('.payment-card').click(function() {
        $('.payment-card').removeClass('selected');
        $(this).addClass('selected');
        $(this).find('input[type="radio"]').prop('checked', true);

        const method = $(this).data('method');
        if (method === 'card') {
            $('#card-payment-section').show();
            $('#ach-payment-section').hide();
        } else {
            $('#card-payment-section').hide();
            $('#ach-payment-section').show();
        }
    });

    // Handle form submission
    const form = document.getElementById('payment-form');
    form.addEventListener('submit', async function(event) {
        event.preventDefault();

        const submitButton = document.getElementById('submit-payment');
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing...';

        const paymentMethod = $('input[name="payment_method"]:checked').val();

        if (paymentMethod === 'card') {
            // Create payment method with Stripe
            const { paymentMethod: pm, error } = await stripe.createPaymentMethod({
                type: 'card',
                card: cardElement,
                billing_details: {
                    name: document.getElementById('billing_name').value,
                    address: {
                        postal_code: document.getElementById('billing_zip').value
                    }
                }
            });

            if (error) {
                document.getElementById('card-errors').textContent = error.message;
                submitButton.disabled = false;
                submitButton.innerHTML = '<i data-feather="lock" class="me-2"></i> Pay ${{ number_format($case->total_amount ?? 0, 2) }}';
                feather.replace();
            } else {
                // Add payment method to form and submit
                const hiddenInput = document.createElement('input');
                hiddenInput.setAttribute('type', 'hidden');
                hiddenInput.setAttribute('name', 'stripe_payment_method');
                hiddenInput.setAttribute('value', pm.id);
                form.appendChild(hiddenInput);
                form.submit();
            }
        } else {
            // ACH payment - submit form directly
            form.submit();
        }
    });
});
</script>
@endpush
