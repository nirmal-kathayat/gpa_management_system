@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Dashboard</h2>
    <div class="text-muted">
        {{ now()->format('l, F j, Y') }}
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4>{{ $totalStudents }}</h4>
                        <p class="mb-0">Total Students</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4>{{ $totalSchools }}</h4>
                        <p class="mb-0">Total Schools</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-school fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4>{{ $totalSubjects }}</h4>
                        <p class="mb-0">Active Subjects</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-book fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4>{{ $totalReports }}</h4>
                        <p class="mb-0">Total Reports</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-file-alt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Reports -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Recent Reports</h5>
            </div>
            <div class="card-body">
                @if($recentReports->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Year</th>
                                    <th>GPA</th>
                                    <th>Grade</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentReports as $report)
                                <tr>
                                    <td>{{ $report->student->name }}</td>
                                    <td>{{ $report->academic_year }}</td>
                                    <td>{{ $report->final_gpa }}</td>
                                    <td><span class="badge bg-primary">{{ $report->final_grade }}</span></td>
                                    <td>{{ $report->created_at->format('M d') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted">No reports created yet.</p>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Grade Distribution -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Grade Distribution</h5>
            </div>
            <div class="card-body">
                @if($gradeDistribution->count() > 0)
                    <canvas id="gradeChart" width="400" height="200"></canvas>
                @else
                    <p class="text-muted">No grade data available.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Top Performing Students -->
<div class="row mt-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Top Performing Students</h5>
            </div>
            <div class="card-body">
                @if($topStudents->count() > 0)
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Student Name</th>
                                    <th>Class</th>
                                    <th>Academic Year</th>
                                    <th>GPA</th>
                                    <th>Grade</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topStudents as $index => $report)
                                <tr>
                                    <td>
                                        @if($index == 0)
                                            <span class="badge bg-warning">🥇</span>
                                        @elseif($index == 1)
                                            <span class="badge bg-secondary">🥈</span>
                                        @elseif($index == 2)
                                            <span class="badge bg-dark">🥉</span>
                                        @else
                                            {{ $index + 1 }}
                                        @endif
                                    </td>
                                    <td>{{ $report->student->name }}</td>
                                    <td>{{ $report->student->class }} - {{ $report->student->section }}</td>
                                    <td>{{ $report->academic_year }}</td>
                                    <td><strong>{{ $report->final_gpa }}</strong></td>
                                    <td><span class="badge bg-success">{{ $report->final_grade }}</span></td>
                                    <td>
                                        <a href="{{ route('reports.show', $report) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted">No student reports available.</p>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Monthly Statistics -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Monthly Report Creation</h5>
            </div>
            <div class="card-body">
                @if($monthlyStats->count() > 0)
                    @foreach($monthlyStats->take(6) as $stat)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>{{ DateTime::createFromFormat('!m', $stat->month)->format('M') }} {{ $stat->year }}</span>
                        <span class="badge bg-primary">{{ $stat->count }}</span>
                    </div>
                    @endforeach
                @else
                    <p class="text-muted">No monthly data available.</p>
                @endif
            </div>
        </div>
    </div>
</div>

@if($gradeDistribution->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('gradeChart').getContext('2d');
const gradeChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($gradeDistribution->pluck('final_grade')) !!},
        datasets: [{
            data: {!! json_encode($gradeDistribution->pluck('count')) !!},
            backgroundColor: [
                '#28a745', '#17a2b8', '#ffc107', '#fd7e14', 
                '#6f42c1', '#e83e8c', '#20c997', '#6c757d'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>
@endif
@endsection
