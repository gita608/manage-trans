@extends('layouts.app')

@section('title', 'Add Partner User | ' . config('app.name'))

@push('styles')
<link href="{{ assetVersioned('assets/css/partner-review.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
<div class="partner-review-page">
@include('partials.page-header', [
    'title' => 'Add User — ' . $partner->title,
    'subtitle' => 'Create a new Partner Portal login account.',
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Partners', 'url' => route('partners.index')],
        ['label' => $partner->title, 'url' => route('partners.users.index', $partner)],
        ['label' => 'Add User'],
    ],
])

<div class="row">
    <div class="col-lg-8 col-xl-6 mx-auto">
        <div class="card partner-review-card">
            <div class="card-header">
                <h5 class="card-title mb-0">New User Information</h5>
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

                <form method="POST" action="{{ route('partners.users.store', $partner) }}">
                    @csrf

                    <div class="mb-4">
                        <label for="name" class="form-label">Full Name<span class="text-danger ms-1">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="Enter full name" required autofocus>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="email" class="form-label">Email Address<span class="text-danger ms-1">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="user@example.com" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">This will be used for Partner Portal login</small>
                    </div>

                    <div class="mb-4">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" placeholder="+1234567890">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr class="my-4">

                    <div class="mb-4">
                        <label for="password" class="form-label">Password<span class="text-danger ms-1">*</span></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Minimum 8 characters" required minlength="8">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Minimum 8 characters required</small>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Confirm Password<span class="text-danger ms-1">*</span></label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Re-enter password" required minlength="8">
                    </div>

                    <hr class="my-4">

                    <div class="mb-4">
                        <div class="form-check form-switch form-check-lg">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                <strong>Active Account</strong>
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1">When active, this user can log in to the Partner Portal. Inactive users cannot log in.</small>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="{{ route('partners.users.index', $partner) }}" class="btn btn-light">
                            <i class="ri-close-line me-1"></i>Cancel
                        </a>
                        <button class="btn btn-success" type="submit">
                            <i class="ri-check-line me-1"></i>Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
