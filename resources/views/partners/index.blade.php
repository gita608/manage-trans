@extends('layouts.app')

@section('title', 'Partners | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Partners Management</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Partners</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- end page title -->

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="ri-check-double-line me-2 align-middle"></i><strong>Success!</strong> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Quick Action Card -->
<div class="row mb-4">
    <div class="col-lg-12">
        <div class="card border shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm flex-shrink-0 me-3">
                            <span class="avatar-title bg-primary-subtle text-primary rounded fs-3">
                                <i class="ri-group-line"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-1">Add New Partner</h5>
                            <p class="text-muted mb-0 small">Create a new partner</p>
                        </div>
                    </div>
                    <a href="{{ route('partners.create') }}" class="btn btn-primary">
                        <i class="ri-add-line me-1"></i> Add Partner
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Partners List -->
<div class="row">
    <div class="col-lg-12">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">All Partners</h5>
            </div>
            <div class="card-body">

                <div class="table-responsive">
                    <table id="partners-table" class="table table-nowrap align-middle mb-0 datatable">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Title</th>
                                <th scope="col">Default</th>
                                <th scope="col">Created At</th>
                                <th scope="col" class="no-export">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($partners as $partner)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $partner->title }}</td>
                                    <td>
                                        @if($partner->is_default)
                                            <span class="badge bg-success">Default</span>
                                        @else
                                            <span class="badge bg-secondary">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $partner->created_at->format('M d, Y') }}</div>
                                        <small class="text-muted">{{ $partner->created_at->format('h:i A') }}</small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('partners.edit', $partner) }}" class="btn btn-sm btn-primary" title="Edit">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                            <form action="{{ route('partners.destroy', $partner) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this partner?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <p class="text-muted mb-0">No partners found. <a href="{{ route('partners.create') }}">Create your first partner</a></p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @include('partials.datatable', ['selector' => '#partners-table', 'order' => [[3, 'desc']]])
            </div>
        </div>
    </div>
</div>
@endsection
