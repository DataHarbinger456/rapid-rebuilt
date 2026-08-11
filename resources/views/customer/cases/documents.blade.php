@extends('layouts.customer.master')

@section('title')Documents — Case #{{ $case->case_number }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Case Documents</h3>
        @endslot
        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customer.cases.index') }}">Cases</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customer.cases.show', $case) }}">{{ $case->case_number }}</a></li>
        <li class="breadcrumb-item active">Documents</li>
    @endcomponent

    <div class="container-fluid">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-7">
                @include('partials.document-readiness', ['readiness' => $readiness, 'variant' => 'customer'])
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h5>Upload Document</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('customer.documents.upload', $case) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Document Type</label>
                                <select name="document_type" class="form-select" required>
                                    @foreach ($requirements as $type => $requirement)
                                        <option value="{{ $type }}">{{ $requirement->label }}@if($requirement->required) *@endif</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">File (PDF, JPG, PNG — max 10 MB)</label>
                                <input type="file" name="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i data-feather="upload"></i> Upload
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
