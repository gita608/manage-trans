@extends('layouts.app')

@section('title', 'Reports | ' . config('app.name'))

@section('content')
@include('partials.page-header', [
    'title' => 'Reports',
    'breadcrumbs' => [
        ['label' => 'Dashboard', 'url' => route('dashboard')],
        ['label' => 'Reports'],
    ],
])

<div class="row g-3">
    <!-- Trip Summary Report -->
    <div class="col-xl-4 col-md-6">
        <div class="card border shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start">
                    <div class="avatar-sm flex-shrink-0 me-3">
                        <span class="avatar-title bg-primary-subtle text-primary rounded fs-3">
                            <i class="ri-file-list-3-line"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-2">Trip Summary Report</h5>
                        <p class="text-muted mb-3 small">Comprehensive trip statistics with filters by date, driver, vessel, and status.</p>
                        <a href="{{ route('reports.trip-summary') }}" class="btn btn-primary btn-sm">
                            <i class="ri-arrow-right-line me-1"></i> View Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Trip Expenses Report -->
    <div class="col-xl-4 col-md-6">
        <div class="card border shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start">
                    <div class="avatar-sm flex-shrink-0 me-3">
                        <span class="avatar-title bg-warning-subtle text-warning rounded fs-3">
                            <i class="ri-money-dollar-circle-line"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-2">Trip Expenses Report</h5>
                        <p class="text-muted mb-3 small">Detailed breakdown of trip expenses with filters by type, driver, and vessel.</p>
                        <a href="{{ route('reports.trip-expenses') }}" class="btn btn-warning btn-sm">
                            <i class="ri-arrow-right-line me-1"></i> View Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Driver Performance Report -->
    <div class="col-xl-4 col-md-6">
        <div class="card border shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start">
                    <div class="avatar-sm flex-shrink-0 me-3">
                        <span class="avatar-title bg-info-subtle text-info rounded fs-3">
                            <i class="ri-user-star-line"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-2">Driver Performance</h5>
                        <p class="text-muted mb-3 small">Analyze driver performance, utilization, and compare Internal vs Outsourcing drivers.</p>
                        <a href="{{ route('reports.driver-performance') }}" class="btn btn-info btn-sm">
                            <i class="ri-arrow-right-line me-1"></i> View Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card border shadow-sm">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Report Features</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="mb-3"><i class="ri-filter-line me-2 text-primary"></i>Advanced Filtering</h6>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><i class="ri-check-line text-success me-2"></i>Date range selection</li>
                            <li class="mb-2"><i class="ri-check-line text-success me-2"></i>Filter by driver</li>
                            <li class="mb-2"><i class="ri-check-line text-success me-2"></i>Filter by vessel</li>
                            <li class="mb-2"><i class="ri-check-line text-success me-2"></i>Filter by status</li>
                            <li class="mb-2"><i class="ri-check-line text-success me-2"></i>Filter by driver type</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="mb-3"><i class="ri-bar-chart-line me-2 text-primary"></i>Visual Analytics</h6>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><i class="ri-check-line text-success me-2"></i>Status distribution charts</li>
                            <li class="mb-2"><i class="ri-check-line text-success me-2"></i>Trend analysis graphs</li>
                            <li class="mb-2"><i class="ri-check-line text-success me-2"></i>Performance metrics</li>
                            <li class="mb-2"><i class="ri-check-line text-success me-2"></i>Comparative analysis</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

