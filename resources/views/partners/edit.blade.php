@extends('layouts.app')

@section('title', 'Edit Partner | ' . config('app.name'))

@push('styles')
<link href="{{ assetVersioned('assets/css/partner-review.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
<div class="partner-review-page">
@include('partials.page-header', [
    'title' => 'Edit Partner',
    'subtitle' => $partner->title,
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Partners', 'url' => route('partners.index')],
        ['label' => 'Edit'],
    ],
])

<div class="row justify-content-center">
    <div class="col-12 partner-review-form-shell">
        <div class="card partner-review-card">
            <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <div class="d-flex flex-wrap align-items-center gap-2 min-w-0">
                    <h5 class="card-title mb-0">
                        <i class="ri-building-line me-2"></i>Partner Information
                    </h5>
                    @if($partner->is_default)
                        <span class="badge bg-success-subtle text-success">
                            <i class="ri-star-fill me-1"></i>Default Partner
                        </span>
                    @endif
                </div>
                @if(auth()->user()->hasPermission('edit_partners'))
                    <a href="{{ route('partners.users.index', $partner) }}" class="btn btn-sm btn-soft-primary flex-shrink-0">
                        <i class="ri-user-line me-1"></i> Manage Portal Users
                    </a>
                @endif
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong><i class="ri-error-warning-line me-2"></i>Please fix the following errors:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('partners.update', $partner) }}" id="partnerEditForm">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $partner->title) }}" placeholder="Enter partner title" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="partner-settings-section">
                        <div class="section-title">
                            <i class="ri-settings-3-line me-1"></i> Partner Settings
                        </div>

                        <div class="partner-settings-option">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1" {{ old('is_default', $partner->is_default) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_default">Set as Default Partner</label>
                            </div>
                            <p class="partner-settings-help mb-0">If checked, this partner becomes the default and all others are unset.</p>
                        </div>
                    </div>

                    <div class="partner-settings-section">
                        <div class="section-title">
                            <i class="ri-global-line me-1"></i> Partner Portal Submission Methods
                        </div>

                        <div class="partner-settings-option">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="allow_manual_submission" name="allow_manual_submission" value="1" {{ old('allow_manual_submission', $partner->allow_manual_submission) ? 'checked' : '' }}>
                                <label class="form-check-label" for="allow_manual_submission">Allow Manual Submission</label>
                            </div>
                            <p class="partner-settings-help mb-0">Partner users can enter request details through web forms.</p>
                        </div>

                        <div class="partner-settings-option">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="allow_image_submission" name="allow_image_submission" value="1" {{ old('allow_image_submission', $partner->allow_image_submission) ? 'checked' : '' }}>
                                <label class="form-check-label" for="allow_image_submission">Allow Image Upload Submission</label>
                            </div>
                            <p class="partner-settings-help mb-0">Partner users can upload images for automatic data extraction.</p>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-footer partner-review-form-footer">
                <a href="{{ route('partners.index') }}" class="btn btn-light">
                    <i class="ri-close-line me-1"></i> Cancel
                </a>
                <button class="btn btn-success" type="submit" form="partnerEditForm">
                    <i class="ri-check-line me-1"></i> Update Partner
                </button>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
