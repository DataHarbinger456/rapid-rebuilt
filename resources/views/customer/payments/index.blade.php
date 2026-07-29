@extends('layouts.customer.master')

@section('title')Payment History @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Payment History</h3>
        @endslot
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Payments</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>All Payments</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Case</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Receipt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($payments ?? [] as $payment)
                                        <tr>
                                            <td>{{ $payment->created_at->format('M j, Y') }}</td>
                                            <td>
                                                <a href="{{ route('customer.cases.show', $payment->case) }}">
                                                    #{{ $payment->case->case_number }}
                                                </a>
                                            </td>
                                            <td>{{ $payment->description }}</td>
                                            <td><strong>US${{ number_format($payment->amount, 2, '.', ',') }}</strong></td>
                                            <td>
                                                <span class="badge badge-{{ $payment->status === 'completed' ? 'success' : 'warning' }}">
                                                    {{ ucfirst($payment->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($payment->receipt_url)
                                                    <a href="{{ $payment->receipt_url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i data-feather="download"></i>
                                                    </a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <i data-feather="credit-card" style="width: 48px; height: 48px;" class="text-muted mb-3"></i>
                                                <p class="text-muted mb-0">No payment history yet</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
