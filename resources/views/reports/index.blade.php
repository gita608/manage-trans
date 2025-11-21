@extends('layouts.app')

@section('title', 'Reports | ' . config('app.name'))

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Reports</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Reports</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- end page title -->

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

    <!-- Daily/Weekly Report -->
    <div class="col-xl-4 col-md-6">
        <div class="card border shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start">
                    <div class="avatar-sm flex-shrink-0 me-3">
                        <span class="avatar-title bg-success-subtle text-success rounded fs-3">
                            <i class="ri-calendar-line"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-2">Daily/Weekly Report</h5>
                        <p class="text-muted mb-3 small">Daily and weekly trip analysis with peak hours and busiest days insights.</p>
                        <a href="{{ route('reports.daily-weekly') }}" class="btn btn-success btn-sm">
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
                            <li class="mb-2"><i class="ri-check-line text-success me-2"></i>Peak hours analysis</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

