@extends('layouts.app')

@section('title', 'Add Trip Expense Type | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Add New Trip Expense Type</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trip-expense-types.index') }}">Trip Expense Types</a></li>
                    <li class="breadcrumb-item active">Add New</li>
                </ol>
            </div>

        </div>
    </div>
</div>
<!-- end page title -->

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Trip Expense Type Information</h5>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('trip-expense-types.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" placeholder="Enter expense type title" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">Allowed / Required Input Types <span class="text-danger">*</span></label>
                        <small class="text-muted d-block mb-2">Select the input fields that should be shown and required when submitting this expense type:</small>

                        <div class="d-flex gap-4 flex-wrap">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="input_types[]" value="amount" id="input_type_amount" {{ is_array(old('input_types', ['amount', 'image'])) && (in_array('amount', old('input_types', ['amount', 'image'])) || in_array('number', old('input_types', ['amount', 'image']))) ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="input_type_amount">
                                    <i class="ri-money-dollar-circle-line text-success me-1"></i> Number (Amount)
                                </label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="input_types[]" value="hours" id="input_type_hours" {{ is_array(old('input_types')) && in_array('hours', old('input_types')) ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="input_type_hours">
                                    <i class="ri-time-line text-primary me-1"></i> Number (Hours)
                                </label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="input_types[]" value="text" id="input_type_text" {{ is_array(old('input_types')) && in_array('text', old('input_types')) ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="input_type_text">
                                    <i class="ri-text text-info me-1"></i> Text (Description / Note)
                                </label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="input_types[]" value="image" id="input_type_image" {{ is_array(old('input_types', ['amount', 'image'])) && in_array('image', old('input_types', ['amount', 'image'])) ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="input_type_image">
                                    <i class="ri-image-line text-warning me-1"></i> Image (Receipt Upload)
                                </label>
                            </div>
                        </div>

                        @error('input_types')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-4">
                        <button class="btn btn-success" type="submit">Create Expense Type</button>
                        <a href="{{ route('trip-expense-types.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection




