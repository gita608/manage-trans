@extends('layouts.app')

@section('title', 'Edit Partner User | ' . config('app.name'))

@push('styles')
<link href="{{ assetVersioned('assets/css/partner-review.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
<div class="partner-review-page">
@include('partials.page-header', [
    'title' => 'Edit User — ' . $partner->title,
    'subtitle' => 'Update account details or reset portal access.',
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Partners', 'url' => route('partners.index')],
        ['label' => $partner->title, 'url' => route('partners.users.index', $partner)],
        ['label' => 'Edit User'],
    ],
])

<div class="row">
    <div class="col-lg-8 col-xl-6 mx-auto">
        <div class="card partner-review-card">
            <div class="card-header">
                <h5 class="card-title mb-0">Edit {{ $partnerUser->name }}</h5>
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

                <form method="POST" action="{{ route('partners.users.update', [$partner, $partnerUser]) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label for="name" class="form-label">Full Name<span class="text-danger ms-1">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $partnerUser->name) }}" placeholder="Enter full name" required autofocus>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="email" class="form-label">Email Address<span class="text-danger ms-1">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $partnerUser->email) }}" placeholder="user@example.com" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">This is used for Partner Portal login</small>
                    </div>

                    <div class="mb-4">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $partnerUser->phone) }}" placeholder="+1234567890">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr class="my-4">

                    <div class="mb-4">
                        <div class="form-check form-switch form-check-lg">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', $partnerUser->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                <strong>Active Account</strong>
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1">When active, this user can log in to the Partner Portal. Inactive users cannot log in.</small>
                    </div>

                    <div class="alert alert-info d-flex align-items-start">
                        <i class="ri-information-line me-2 mt-1"></i>
                        <div>
                            <strong>Password Change:</strong> Use the "Reset Password" action from the users list to change this user's password.
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="{{ route('partners.users.index', $partner) }}" class="btn btn-light">
                            <i class="ri-close-line me-1"></i>Cancel
                        </a>
                        <button class="btn btn-success" type="submit">
                            <i class="ri-check-line me-1"></i>Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
