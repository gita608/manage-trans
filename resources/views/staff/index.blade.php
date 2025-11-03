@extends('layouts.app')

@section('title', 'Staff | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Staff</h4>
            <div class="page-title-right">
                <a href="{{ route('staff.create') }}" class="btn btn-success"><i class="ri-add-line me-1"></i>Add Staff</a>
            </div>
        </div>
    </div>
</div>
<!-- end page title -->

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Photo</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staff as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone ?? '-' }}</td>
                            <td>
                                @if($user->photo)
                                    <img src="{{ asset('storage/' . $user->photo) }}" alt="photo" class="rounded" style="width:40px;height:40px;object-fit:cover;">
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('staff.edit', $user) }}" class="btn btn-sm btn-primary"><i class="ri-pencil-line"></i></a>
                                <form method="POST" action="{{ route('staff.destroy', $user) }}" class="d-inline" onsubmit="return confirm('Delete this staff?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="ri-delete-bin-line"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No staff found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $staff->links() }}
    </div>
</div>
@endsection


