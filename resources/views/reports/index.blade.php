@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Student Reports</h1>
            <p class="text-muted mb-0">Manage and view all student academic reports</p>
        </div>
        <a href="{{ route('reports.create') }}" class="btn btn-primary btn-lg shadow-sm">
            <i class="fas fa-plus me-2"></i>Create New Report
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Reports</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $reports->total() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Passed Students</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $reports->where('result_status', '!=', 'FAILED')->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Failed Students</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $reports->where('result_status', 'FAILED')->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Average GPA</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($reports->where('final_gpa', '>', 0)->avg('final_gpa'), 2) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reports Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white">
            <div class="row align-items-center">
                <div class="col">
                    <h6 class="m-0 font-weight-bold text-primary">Reports Overview</h6>
                </div>
                <div class="col-auto">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?filter=passed">Passed Only</a></li>
                            <li><a class="dropdown-item" href="?filter=failed">Failed Only</a></li>
                            <li><a class="dropdown-item" href="?filter=all">All Reports</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0 fw-bold text-uppercase text-xs text-muted">ID</th>
                            <th class="border-0 fw-bold text-uppercase text-xs text-muted">Student Name</th>
                            <th class="border-0 fw-bold text-uppercase text-xs text-muted">Class</th>
                            <th class="border-0 fw-bold text-uppercase text-xs text-muted">Academic Year</th>
                            <th class="border-0 fw-bold text-uppercase text-xs text-muted">Final GPA</th>
                            <th class="border-0 fw-bold text-uppercase text-xs text-muted">Final Grade</th>
                            <th class="border-0 fw-bold text-uppercase text-xs text-muted">Result Status</th>
                            <th class="border-0 fw-bold text-uppercase text-xs text-muted">Issue Date</th>
                            <th class="border-0 fw-bold text-uppercase text-xs text-muted">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $report)
                        <tr class="border-bottom">
                            <td class="align-middle">
                                <span class="badge bg-light text-dark border">{{ $report->id }}</span>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm rounded-circle bg-gradient-primary text-white d-flex align-items-center justify-content-center me-3">
                                        {{ strtoupper(substr($report->student->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <h6 class="mb-0 text-sm font-weight-bold">{{ $report->student->name }}</h6>
                                        <p class="text-xs text-muted mb-0">Roll: {{ $report->student->roll_number }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="align-middle">
                                <span class="badge bg-info text-white">
                                    {{ $report->student->class }} - {{ $report->student->section }}
                                </span>
                            </td>
                            <td class="align-middle">
                                <span class="text-sm font-weight-bold">{{ $report->academic_year }}</span>
                            </td>
                            <td class="align-middle">
                                @if($report->final_gpa > 0)
                                <span class="badge bg-success text-white px-3 py-2">
                                    {{ number_format($report->final_gpa, 2) }}
                                </span>
                                @else
                                <span class="badge bg-danger text-white px-3 py-2">0.00</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                @php
                                $gradeColor = match($report->final_grade) {
                                'A+', 'A' => 'success',
                                'B+', 'B' => 'primary',
                                'C+', 'C' => 'warning',
                                'D+', 'D' => 'info',
                                default => 'danger'
                                };
                                @endphp
                                <span class="badge bg-{{ $gradeColor }} text-white px-3 py-2">
                                    {{ $report->final_grade }}
                                </span>
                            </td>
                            <td class="align-middle">
                                @php
                                $statusColor = match(true) {
                                str_contains($report->result_status ?? '', 'DISTINCTION') => 'success',
                                str_contains($report->result_status ?? '', 'FIRST') => 'primary',
                                str_contains($report->result_status ?? '', 'SECOND') => 'info',
                                str_contains($report->result_status ?? '', 'THIRD') => 'warning',
                                str_contains($report->result_status ?? '', 'FAILED') => 'danger',
                                default => 'secondary'
                                };
                                @endphp
                                <span class="badge bg-{{ $statusColor }} text-white px-3 py-2">
                                    {{ $report->result_status ?? 'PASSED' }}
                                </span>
                            </td>
                            <td class="align-middle">
                                <span class="text-sm">{{ $report->issue_date->format('M d, Y') }}</span>
                            </td>
                            <td class="align-middle">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('reports.show', $report) }}"
                                        class="btn btn-info btn-sm text-white"
                                        title="View Report">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('reports.edit', $report) }}"
                                        class="btn btn-warning btn-sm text-white"
                                        title="Edit Report">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ route('reports.pdf', $report) }}"
                                        class="btn btn-danger btn-sm"
                                        title="Download PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                    @if(auth()->user()->isAdmin())
                                    <button type="button"
                                        class="btn btn-outline-danger btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteModal{{ $report->id }}"
                                        title="Delete Report">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-file-alt fa-3x mb-3"></i>
                                    <h5>No Reports Found</h5>
                                    <p>There are no student reports available yet.</p>
                                    <a href="{{ route('reports.create') }}" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Create First Report
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($reports->hasPages())
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Showing {{ $reports->firstItem() }} to {{ $reports->lastItem() }} of {{ $reports->total() }} results
                </div>
                {{ $reports->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

@if(auth()->user()->isAdmin())
@foreach($reports as $report)
<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal{{ $report->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <div class="mx-auto d-flex align-items-center justify-content-center h-12 w-12 rounded-circle bg-danger bg-opacity-10 mb-3">
                        <i class="fas fa-exclamation-triangle text-danger fa-2x"></i>
                    </div>
                    <h4 class="text-gray-900 mb-2">Are you absolutely sure?</h4>
                    <p class="text-muted">This action cannot be undone. This will permanently delete the report and all associated data.</p>
                </div>

                <div class="bg-light rounded p-3 mb-4">
                    <div class="row text-sm">
                        <div class="col-4 text-muted">Student:</div>
                        <div class="col-8 fw-bold">{{ $report->student->name }}</div>
                    </div>
                    <div class="row text-sm">
                        <div class="col-4 text-muted">Class:</div>
                        <div class="col-8">{{ $report->student->class }} - {{ $report->student->section }}</div>
                    </div>
                    <div class="row text-sm">
                        <div class="col-4 text-muted">Academic Year:</div>
                        <div class="col-8">{{ $report->academic_year }}</div>
                    </div>
                    <div class="row text-sm">
                        <div class="col-4 text-muted">Status:</div>
                        <div class="col-8">
                            <span class="badge bg-{{ $statusColor ?? 'secondary' }} text-white">
                                {{ $report->result_status ?? 'PASSED' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <form action="{{ route('reports.destroy', $report) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Delete Report
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach
@endif

<style>
    .border-left-primary {
        border-left: 0.25rem solid #4e73df !important;
    }

    .border-left-success {
        border-left: 0.25rem solid #1cc88a !important;
    }

    .border-left-warning {
        border-left: 0.25rem solid #f6c23e !important;
    }

    .border-left-info {
        border-left: 0.25rem solid #36b9cc !important;
    }

    .avatar {
        width: 2.5rem;
        height: 2.5rem;
    }

    .bg-gradient-primary {
        background: linear-gradient(87deg, #5e72e4 0, #825ee4 100%) !important;
    }

    .text-xs {
        font-size: 0.75rem;
    }

    .h-12 {
        height: 3rem;
    }

    .w-12 {
        width: 3rem;
    }
</style>
@endsection