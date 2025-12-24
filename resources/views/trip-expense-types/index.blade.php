@extends('layouts.app')

@section('title', 'Trip Expense Types | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Trip Expense Types Management</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Trip Expense Types</li>
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
                            <span class="avatar-title bg-success-subtle text-success rounded fs-3">
                                <i class="ri-money-dollar-circle-line"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-1">Add New Trip Expense Type</h5>
                            <p class="text-muted mb-0 small">Create a new expense type for trip reporting</p>
                        </div>
                    </div>
                    <a href="{{ route('trip-expense-types.create') }}" class="btn btn-primary">
                        <i class="ri-add-line me-1"></i> Add Expense Type
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Trip Expense Types List -->
<div class="row">
    <div class="col-lg-12">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">All Trip Expense Types</h5>
            </div>
            <div class="card-body">

                <div class="table-responsive">
                    <table id="trip-expense-types-table" class="table table-nowrap align-middle mb-0 datatable">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Title</th>
                                <th scope="col">Created At</th>
                                <th scope="col" class="no-export">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($expenseTypes as $expenseType)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $expenseType->title }}</td>
                                    <td>
                                        <div>{{ $expenseType->created_at->format('M d, Y') }}</div>
                                        <small class="text-muted">{{ $expenseType->created_at->format('h:i A') }}</small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('trip-expense-types.edit', $expenseType) }}" class="btn btn-sm btn-primary" title="Edit">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                            <form action="{{ route('trip-expense-types.destroy', $expenseType) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this trip expense type?');">
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
                                    <td colspan="4" class="text-center py-4">
                                        <p class="text-muted mb-0">No trip expense types found. <a href="{{ route('trip-expense-types.create') }}">Create your first expense type</a></p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @include('partials.datatable', ['selector' => '#trip-expense-types-table', 'order' => [[2, 'desc']]])
            </div>
        </div>
    </div>
</div>
@endsection





