@extends('layouts.app')

@section('title', 'Vessels | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Vessels</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Vessels</li>
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
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">All Vessels</h5>
                    <a href="{{ route('vessels.create') }}" class="btn btn-success">
                        <i class="ri-add-line align-middle me-1"></i> Add New Vessel
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Name</th>
                                <th scope="col">Created At</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vessels as $vessel)
                                <tr>
                                    <td>{{ $vessel->id }}</td>
                                    <td>{{ $vessel->name }}</td>
                                    <td>{{ $vessel->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('vessels.show', $vessel) }}" class="btn btn-sm btn-info" title="View">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            <a href="{{ route('vessels.edit', $vessel) }}" class="btn btn-sm btn-primary" title="Edit">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                            <form action="{{ route('vessels.destroy', $vessel) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this vessel?');">
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
                                        <p class="text-muted mb-0">No vessels found. <a href="{{ route('vessels.create') }}">Create your first vessel</a></p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($vessels->hasPages())
                    <div class="d-flex justify-content-end mt-3">
                        {{ $vessels->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

