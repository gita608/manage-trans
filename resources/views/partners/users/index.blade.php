@extends('layouts.app')

@section('title', 'Partner Users | ' . config('app.name'))

@push('styles')
<link href="{{ assetVersioned('assets/css/partner-review.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
<div class="partner-review-page">
@include('partials.page-header', [
    'title' => $partner->title . ' — Users',
    'subtitle' => 'Manage partner portal login accounts.',
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Partners', 'url' => route('partners.index')],
        ['label' => 'Users'],
    ],
])

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-checkbox-circle-line me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <div class="col-lg-12">
        <div class="card partner-review-card">
            <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="ri-user-line me-2"></i>Partner Login Users
                </h5>
                @if(auth()->user()->hasPermission('edit_partners'))
                    <a href="{{ route('partners.users.create', $partner) }}" class="btn btn-success btn-sm">
                        <i class="ri-add-line align-middle me-1"></i> Add User
                    </a>
                @endif
            </div>
            <div class="card-body">
                @if($partnerUsers->count() > 0)
                    {{-- Desktop Table --}}
                    <div class="table-responsive partner-review-table-wrap d-none d-md-block">
                        <table class="table table-hover table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    @if(auth()->user()->hasPermission('edit_partners'))
                                        <th class="text-end partner-review-actions-col">Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($partnerUsers as $user)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $user->name }}</div>
                                        </td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->phone ?? '—' }}</td>
                                        <td>
                                            @if($user->is_active)
                                                <span class="badge bg-success-subtle text-success">
                                                    <i class="ri-checkbox-circle-line me-1"></i>Active
                                                </span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">
                                                    <i class="ri-close-circle-line me-1"></i>Inactive
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($user->last_login_at)
                                                {{ $user->last_login_at->format('M d, Y g:i A') }}
                                            @else
                                                <span class="text-muted">Never</span>
                                            @endif
                                        </td>
                                        @if(auth()->user()->hasPermission('edit_partners'))
                                            <td class="text-end partner-review-actions-col">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-soft-secondary partner-review-actions-toggle"
                                                            type="button"
                                                            data-bs-toggle="dropdown"
                                                            data-bs-popper-config='{"strategy":"fixed","placement":"bottom-end"}'
                                                            aria-expanded="false"
                                                            aria-label="Actions for {{ $user->name }}">
                                                        <i class="ri-more-2-fill"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('partners.users.edit', [$partner, $user]) }}">
                                                                <i class="ri-pencil-line align-middle me-2"></i>Edit
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#passwordModal{{ $user->id }}">
                                                                <i class="ri-lock-password-line align-middle me-2"></i>Reset Password
                                                            </button>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <form method="POST" action="{{ route('partners.users.toggleStatus', [$partner, $user]) }}" onsubmit="return confirm('{{ $user->is_active ? 'Deactivate this user? They will not be able to log in to the Partner Portal.' : 'Reactivate this user? They will be able to log in to the Partner Portal.' }}')">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit" class="dropdown-item {{ $user->is_active ? 'text-danger' : 'text-success' }}">
                                                                    @if($user->is_active)
                                                                        <i class="ri-close-circle-line align-middle me-2"></i>Deactivate
                                                                    @else
                                                                        <i class="ri-checkbox-circle-line align-middle me-2"></i>Reactivate
                                                                    @endif
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if(auth()->user()->hasPermission('edit_partners'))
                        @foreach($partnerUsers as $user)
                            <div class="modal fade" id="passwordModal{{ $user->id }}" tabindex="-1" aria-labelledby="passwordModalLabel{{ $user->id }}" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('partners.users.updatePassword', [$partner, $user]) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="passwordModalLabel{{ $user->id }}">Reset Password</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="mb-3">Resetting password for <strong>{{ $user->name }}</strong></p>
                                                <div class="mb-3">
                                                    <label for="password{{ $user->id }}" class="form-label">New Password<span class="text-danger ms-1">*</span></label>
                                                    <input type="password" class="form-control" id="password{{ $user->id }}" name="password" required minlength="8" placeholder="Minimum 8 characters">
                                                    <small class="text-muted">Minimum 8 characters</small>
                                                </div>
                                                <div class="mb-0">
                                                    <label for="password_confirmation{{ $user->id }}" class="form-label">Confirm Password<span class="text-danger ms-1">*</span></label>
                                                    <input type="password" class="form-control" id="password_confirmation{{ $user->id }}" name="password_confirmation" required minlength="8" placeholder="Re-enter password">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ri-lock-password-line me-1"></i>Reset Password
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif

                    {{-- Mobile Cards --}}
                    <div class="d-md-none">
                        @foreach($partnerUsers as $user)
                            <div class="card mb-3 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1">{{ $user->name }}</h6>
                                            <small class="text-muted">{{ $user->email }}</small>
                                        </div>
                                        @if($user->is_active)
                                            <span class="badge bg-success-subtle text-success">
                                                <i class="ri-checkbox-circle-line me-1"></i>Active
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">
                                                <i class="ri-close-circle-line me-1"></i>Inactive
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-muted small mb-2">
                                        <div><i class="ri-phone-line me-1"></i>{{ $user->phone ?? '—' }}</div>
                                        <div>
                                            <i class="ri-time-line me-1"></i>Last login:
                                            @if($user->last_login_at)
                                                {{ $user->last_login_at->format('M d, Y') }}
                                            @else
                                                Never
                                            @endif
                                        </div>
                                    </div>
                                    @if(auth()->user()->hasPermission('edit_partners'))
                                        <div class="d-flex flex-wrap gap-2 mt-3">
                                            <a href="{{ route('partners.users.edit', [$partner, $user]) }}" class="btn btn-sm btn-soft-primary flex-fill">
                                                <i class="ri-pencil-line me-1"></i>Edit
                                            </a>
                                            <button type="button" class="btn btn-sm btn-soft-secondary flex-fill" data-bs-toggle="modal" data-bs-target="#passwordModal{{ $user->id }}">
                                                <i class="ri-lock-password-line me-1"></i>Password
                                            </button>
                                            <form method="POST" action="{{ route('partners.users.toggleStatus', [$partner, $user]) }}" class="flex-fill" onsubmit="return confirm('{{ $user->is_active ? 'Deactivate this user?' : 'Reactivate this user?' }}')">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-soft-danger' : 'btn-soft-success' }} w-100">
                                                    @if($user->is_active)
                                                        <i class="ri-close-circle-line me-1"></i>Deactivate
                                                    @else
                                                        <i class="ri-checkbox-circle-line me-1"></i>Reactivate
                                                    @endif
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="partner-review-empty">
                        <div class="partner-review-empty-icon">
                            <i class="ri-user-line" aria-hidden="true"></i>
                        </div>
                        <h6>No Partner Users Yet</h6>
                        <p class="text-muted mb-0 small">Partner users can log in to the Partner Portal to submit transportation requests.</p>
                        @if(auth()->user()->hasPermission('edit_partners'))
                            <a href="{{ route('partners.users.create', $partner) }}" class="btn btn-success mt-3">
                                <i class="ri-add-line align-middle me-1"></i> Add First User
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
</div>
@endsection