@extends('layouts.app')

@section('title', 'Add Partner | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Add New Partner</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('partners.index') }}">Partners</a></li>
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
                <h5 class="card-title mb-0">Partner Information</h5>
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

                <form method="POST" action="{{ route('partners.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" placeholder="Enter partner title" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_default">
                                Set as Default Partner
                            </label>
                        </div>
                        <small class="text-muted">If checked, this partner will be set as the default partner and all other partners will be unset as default.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Partner Portal Submission Methods</label>
                        
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="allow_manual_submission" name="allow_manual_submission" value="1" {{ old('allow_manual_submission') ? 'checked' : '' }}>
                            <label class="form-check-label" for="allow_manual_submission">
                                Allow Manual Submission
                            </label>
                        </div>
                        <small class="text-muted d-block mb-2">Partner users can manually enter request details through web forms.</small>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="allow_image_submission" name="allow_image_submission" value="1" {{ old('allow_image_submission') ? 'checked' : '' }}>
                            <label class="form-check-label" for="allow_image_submission">
                                Allow Image Upload Submission
                            </label>
                        </div>
                        <small class="text-muted d-block">Partner users can upload images for automatic data extraction.</small>
                    </div>

                    <div class="mt-4">
                        <button class="btn btn-success" type="submit">Create Partner</button>
                        <a href="{{ route('partners.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
