@extends('layouts.app')

@section('title', 'Trip Expenses Report | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Trip Expenses Report</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
                    <li class="breadcrumb-item active">Trip Expenses</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- end page title -->

<!-- Filters -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h6 class="card-title mb-0">Filters</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('reports.trip-expenses') }}" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Driver</label>
                            <select name="driver_id" class="form-select">
                                <option value="">All Drivers</option>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>
                                        {{ $driver->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Vessel</label>
                            <select name="vessel_id" class="form-select">
                                <option value="">All Vessels</option>
                                @foreach($vessels as $vessel)
                                    <option value="{{ $vessel->id }}" {{ request('vessel_id') == $vessel->id ? 'selected' : '' }}>
                                        {{ $vessel->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Expense Type</label>
                            <select name="expense_type_id" class="form-select">
                                <option value="">All Types</option>
                                @foreach($expenseTypes as $type)
                                    <option value="{{ $type->id }}" {{ request('expense_type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 d-flex justify-content-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-search-line me-1"></i> Apply Filters
                            </button>
                            <a href="{{ route('reports.trip-expenses') }}" class="btn btn-secondary">
                                <i class="ri-refresh-line me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-6 col-md-6">
        <div class="card border shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm flex-shrink-0 me-3">
                        <span class="avatar-title bg-primary-subtle text-primary rounded">
                            <i class="ri-money-dollar-circle-line fs-4"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 fs-12">Total Expenses</p>
                        <h3 class="mb-0 fw-bold">{{ number_format($totalExpenses, 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6 col-md-6">
        <div class="card border shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm flex-shrink-0 me-3">
                        <span class="avatar-title bg-info-subtle text-info rounded">
                            <i class="ri-file-list-3-line fs-4"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-uppercase fw-medium text-muted mb-0 fs-12">Total Transactions</p>
                        <h3 class="mb-0 fw-bold">{{ $expenses->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-xl-6">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h6 class="card-title mb-0">Expenses by Type</h6>
            </div>
            <div class="card-body">
                <canvas id="typeChart" height="250"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h6 class="card-title mb-0">Expenses by Date</h6>
            </div>
            <div class="card-body">
                <canvas id="dateChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Expense Details Table -->
<div class="row">
    <div class="col-12">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <div class="d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0">Expense Details</h6>
                    <span class="badge bg-primary">{{ $expenses->count() }} records</span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Trip Date</th>
                                <th>Expense Type</th>
                                <th>Amount</th>
                                <th>Submitted By</th>
                                <th>Vessel</th>
                                <th>Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expenses as $expense)
                            @php
                                $firstCrew = $expense->trip ? $expense->trip->crews->first() : null;
                            @endphp
                            <tr>
                                <td>{{ $expense->trip ? $expense->trip->trip_date->format('M d, Y') : '-' }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $expense->expenseType->title ?? 'Unknown' }}
                                    </span>
                                </td>
                                <td class="fw-bold">{{ number_format($expense->amount, 2) }}</td>
                                <td>{{ $expense->driver->name ?? 'Unknown' }}</td>
                                <td>{{ $firstCrew && $firstCrew->vessel ? $firstCrew->vessel->name : 'Unknown' }}</td>
                                <td>
                                    @if($expense->receipt)
                                        <a href="{{ Storage::url($expense->receipt) }}" target="_blank" class="btn btn-sm btn-soft-primary">
                                            <i class="ri-file-text-line me-1"></i> View
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="ri-money-dollar-circle-line fs-3 mb-2 d-block"></i>
                                    No expenses found for the selected filters
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="2" class="fw-bold text-end">Total</td>
                                <td class="fw-bold">{{ number_format($totalExpenses, 2) }}</td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Type Chart
    const typeCtx = document.getElementById('typeChart').getContext('2d');
    new Chart(typeCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode(array_keys($expensesByType->toArray())) !!},
            datasets: [{
                data: {!! json_encode(array_values($expensesByType->toArray())) !!},
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 159, 64, 0.8)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Date Chart
    const dateCtx = document.getElementById('dateChart').getContext('2d');
    const dateLabels = {!! json_encode(array_keys($expensesByDate->toArray())) !!};
    const dateData = {!! json_encode(array_values($expensesByDate->toArray())) !!};
    
    new Chart(dateCtx, {
        type: 'bar',
        data: {
            labels: dateLabels,
            datasets: [{
                label: 'Expenses',
                data: dateData,
                backgroundColor: 'rgba(13, 202, 240, 0.6)',
                borderColor: 'rgb(13, 202, 240)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
@endpush
@endsection
