@extends('layouts.app')

@section('title', 'Vessel Details | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Vessel Details</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('vessels.index') }}">Vessels</a></li>
                    <li class="breadcrumb-item active">Details</li>
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
                    <h5 class="card-title mb-0">Vessel Information</h5>
                    <div>
                        <a href="{{ route('vessels.edit', $vessel) }}" class="btn btn-primary">
                            <i class="ri-pencil-line align-middle me-1"></i> Edit
                        </a>
                        <form action="{{ route('vessels.destroy', $vessel) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this vessel?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="ri-delete-bin-line align-middle me-1"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tbody>
                        <tr>
                            <th width="200">ID:</th>
                            <td>{{ $vessel->id }}</td>
                        </tr>
                        <tr>
                            <th>Name:</th>
                            <td>{{ $vessel->name }}</td>
                        </tr>
                        <tr>
                            <th>Created At:</th>
                            <td>{{ $vessel->created_at->format('M d, Y h:i A') }}</td>
                        </tr>
                        <tr>
                            <th>Last Updated:</th>
                            <td>{{ $vessel->updated_at->format('M d, Y h:i A') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <a href="{{ route('vessels.index') }}" class="btn btn-secondary">
                    <i class="ri-arrow-left-line align-middle me-1"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

