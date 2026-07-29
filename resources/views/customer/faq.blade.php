@extends('layouts.customer.master')

@section('title')FAQ @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Frequently Asked Questions</h3>
        @endslot
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">FAQ</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-body">
                        <div class="accordion" id="faqAccordion">
                            <!-- General Questions -->
                            <h5 class="mb-3 text-primary">General Questions</h5>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                        What is a rebuilt title?
                                    </button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        A rebuilt title is issued to a vehicle that was previously declared a total loss (salvage) but has been repaired to meet safety standards. In Florida, the vehicle must pass an inspection by an authorized inspector before a rebuilt title can be issued.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                        How long does the process take?
                                    </button>
                                </h2>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        The typical timeline is 2-4 weeks from inspection to receiving your rebuilt title. This includes: inspection scheduling (1-2 weeks), inspection day, document processing (2-3 days), and DMV processing (1-2 weeks).
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                        What documents do I need?
                                    </button>
                                </h2>
                                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <ul>
                                            <li>Current salvage/branded title</li>
                                            <li>Bill of sale</li>
                                            <li>Valid Florida driver's license</li>
                                            <li>Proof of Florida insurance</li>
                                            <li>Receipts for all replacement parts</li>
                                            <li>Photos of repairs (recommended)</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Inspection Questions -->
                            <h5 class="mb-3 mt-4 text-primary">Inspection Questions</h5>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                        What happens during the inspection?
                                    </button>
                                </h2>
                                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Our certified inspector will: verify the VIN matches your title, photograph the vehicle, inspect all replaced components, verify part sources using receipts, and complete the required HSMV forms.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                        What if my vehicle fails inspection?
                                    </button>
                                </h2>
                                <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        If your vehicle doesn't pass, the inspector will provide a detailed report of what needs to be corrected. Once you've made the necessary repairs, you can schedule a re-inspection at a reduced fee.
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Questions -->
                            <h5 class="mb-3 mt-4 text-primary">Payment & Pricing</h5>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                                        How much does it cost?
                                    </button>
                                </h2>
                                <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Our base inspection fee is $150. Additional costs may include: component verification fees ($10 per major component), mobile inspection fee ($75 for on-site inspections), and rush processing ($50 for expedited service).
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                                        What payment methods do you accept?
                                    </button>
                                </h2>
                                <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        We accept all major credit cards (Visa, MasterCard, American Express, Discover) and debit cards. Payments are processed securely through Stripe.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-body text-center">
                        <i data-feather="message-circle" style="width: 48px; height: 48px;" class="text-primary mb-3"></i>
                        <h5>Still Have Questions?</h5>
                        <p class="text-muted">Our support team is here to help</p>
                        <a href="{{ route('customer.support') }}" class="btn btn-primary">
                            Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
