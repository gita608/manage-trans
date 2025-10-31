@extends('layouts.app')

@section('title', 'Profile | ' . config('app.name', 'Laravel'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Profile</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Profile</li>
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
                <h5 class="card-title mb-0">Update Profile</h5>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

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

                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', auth()->user()->name) }}" placeholder="Enter your name" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', auth()->user()->email) }}" placeholder="Enter email address" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password-input">New Password</label>
                        <p class="text-muted fs-12 mb-2">Leave blank if you don't want to change the password</p>
                        <div class="position-relative auth-pass-inputgroup">
                            <input type="password" class="form-control pe-5 password-input @error('password') is-invalid @enderror" name="password" placeholder="Enter new password" id="password-input">
                            <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" id="password-addon"><i class="ri-eye-fill align-middle"></i></button>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password-confirm">Confirm New Password</label>
                        <div class="position-relative auth-pass-inputgroup">
                            <input type="password" class="form-control pe-5 password-input" name="password_confirmation" placeholder="Confirm new password" id="password-confirm">
                            <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" id="password-confirm-addon"><i class="ri-eye-fill align-middle"></i></button>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button class="btn btn-success" type="submit">Update Profile</button>
                        <a href="{{ route('dashboard') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/pages/password-addon.init.js') }}"></script>
<script>
    // Add toggle functionality for password confirmation field
    document.addEventListener('DOMContentLoaded', function() {
        const passwordConfirmAddon = document.getElementById('password-confirm-addon');
        const passwordConfirmInput = document.getElementById('password-confirm');
        
        if (passwordConfirmAddon && passwordConfirmInput) {
            passwordConfirmAddon.addEventListener('click', function() {
                const type = passwordConfirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordConfirmInput.setAttribute('type', type);
                const icon = this.querySelector('i');
                icon.classList.toggle('ri-eye-fill');
                icon.classList.toggle('ri-eye-off-fill');
            });
        }
    });
</script>
@endpush
