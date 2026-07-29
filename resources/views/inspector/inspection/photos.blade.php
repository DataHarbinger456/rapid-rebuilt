@extends('layouts.admin.master')

@section('title')Inspection Photos @endsection

@push('css')
<link rel="stylesheet" type="text/css" href="{{asset('assets/css/dropzone.css')}}">
<style>
    .photo-card {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
    }
    .photo-card img {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }
    .photo-card .overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(transparent, rgba(0,0,0,0.8));
        padding: 1rem;
        color: white;
    }
    .photo-required {
        border: 2px dashed #dc3545;
    }
    .photo-uploaded {
        border: 2px solid #28a745;
    }
</style>
@endpush

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Inspection Photos</h3>
        @endslot
        <li class="breadcrumb-item">Inspector</li>
        <li class="breadcrumb-item active">Photos</li>
    @endcomponent

    <div class="container-fluid">
        <!-- Progress -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Photo Progress</span>
                            <span>{{ $photos->count() }} / {{ count($required_photos) }} required photos</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            @php
                                $percent = count($required_photos) > 0
                                    ? round(($photos->count() / count($required_photos)) * 100)
                                    : 0;
                            @endphp
                            <div class="progress-bar {{ $has_all_required ? 'bg-success' : 'bg-warning' }}"
                                 role="progressbar"
                                 style="width: {{ min($percent, 100) }}%">
                            </div>
                        </div>
                        @if(!$has_all_required)
                            <small class="text-danger mt-2 d-block">
                                Missing: {{ implode(', ', array_map(fn($p) => ucfirst(str_replace('_', ' ', $p)), $missing_photos)) }}
                            </small>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Upload Photos</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('api.inspections.photos', $inspection) }}" method="POST" enctype="multipart/form-data" id="upload-form">
                            @csrf
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Photo Type</label>
                                        <select name="photo_type" class="form-select" required>
                                            <option value="">Select type...</option>
                                            @foreach($required_photos as $type)
                                                <option value="{{ $type }}"
                                                    {{ in_array($type, $missing_photos) ? '' : 'disabled' }}>
                                                    {{ ucfirst(str_replace('_', ' ', $type)) }}
                                                    {{ in_array($type, $missing_photos) ? '(Required)' : '(Done)' }}
                                                </option>
                                            @endforeach
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="mb-3">
                                        <label class="form-label">Photo</label>
                                        <input type="file" name="photo" class="form-control" accept="image/*" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i data-feather="upload"></i> Upload
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <input type="text" name="description" class="form-control" placeholder="Optional description...">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Required Photos Grid -->
        <div class="row">
            <div class="col-12">
                <h5 class="mb-3">Required Photos</h5>
            </div>
            @foreach($required_photos as $type)
                @php
                    $photo = $photos->firstWhere('photo_type', $type);
                @endphp
                <div class="col-md-3 mb-4">
                    <div class="photo-card {{ $photo ? 'photo-uploaded' : 'photo-required' }}">
                        @if($photo)
                            <img src="{{ asset('storage/' . $photo->file_path) }}" alt="{{ $type }}">
                            <div class="overlay">
                                <small>{{ ucfirst(str_replace('_', ' ', $type)) }}</small>
                                <br>
                                <small class="opacity-75">{{ $photo->created_at->format('M j, g:i A') }}</small>
                            </div>
                        @else
                            <div class="d-flex align-items-center justify-content-center bg-light" style="height: 200px;">
                                <div class="text-center text-muted">
                                    <i data-feather="camera" style="width: 48px; height: 48px;"></i>
                                    <p class="mt-2 mb-0">{{ ucfirst(str_replace('_', ' ', $type)) }}</p>
                                    <small class="text-danger">Required</small>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Additional Photos -->
        @php $additionalPhotos = $photos->filter(fn($p) => !in_array($p->photo_type, $required_photos)); @endphp
        @if($additionalPhotos->count() > 0)
            <div class="row mt-4">
                <div class="col-12">
                    <h5 class="mb-3">Additional Photos</h5>
                </div>
                @foreach($additionalPhotos as $photo)
                    <div class="col-md-3 mb-4">
                        <div class="photo-card">
                            <img src="{{ asset('storage/' . $photo->file_path) }}" alt="{{ $photo->photo_type }}">
                            <div class="overlay">
                                <small>{{ ucfirst(str_replace('_', ' ', $photo->photo_type)) }}</small>
                                @if($photo->description)
                                    <br><small class="opacity-75">{{ $photo->description }}</small>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Navigation -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body d-flex justify-content-between">
                        <a href="{{ route('inspector.checklist', $inspection->scheduling) }}" class="btn btn-outline-secondary">
                            <i data-feather="arrow-left"></i> Back to Checklist
                        </a>
                        <a href="{{ route('inspector.components', $inspection) }}" class="btn btn-primary">
                            Next: Verify Components <i data-feather="arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
