@extends('layouts.admin.master')

@section('title')Settings @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Settings</h3>
        @endslot
        <li class="breadcrumb-item">Admin</li>
        <li class="breadcrumb-item active">Settings</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                @foreach($setting_groups as $groupKey => $group)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>{{ $group['label'] }}</h5>
                        </div>
                        <div class="card-body">
                            <form action="#" method="POST">
                                @csrf
                                @foreach($group['settings'] as $settingKey)
                                    <div class="mb-3 row">
                                        <label class="col-sm-4 col-form-label">{{ ucfirst(str_replace('_', ' ', $settingKey)) }}</label>
                                        <div class="col-sm-8">
                                            @if(str_contains($settingKey, 'enabled'))
                                                <div class="form-check form-switch">
                                                    <input type="checkbox" class="form-check-input" name="settings[{{ $settingKey }}]"
                                                           {{ ($settings[$settingKey] ?? false) ? 'checked' : '' }}>
                                                </div>
                                            @elseif(str_contains($settingKey, 'price') || str_contains($settingKey, 'fee'))
                                                <div class="input-group">
                                                    <span class="input-group-text">$</span>
                                                    <input type="number" step="0.01" class="form-control" name="settings[{{ $settingKey }}]"
                                                           value="{{ $settings[$settingKey] ?? '' }}">
                                                </div>
                                            @else
                                                <input type="text" class="form-control" name="settings[{{ $settingKey }}]"
                                                       value="{{ $settings[$settingKey] ?? '' }}">
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">Save {{ $group['label'] }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

@endsection
