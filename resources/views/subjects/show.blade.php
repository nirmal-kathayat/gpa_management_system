@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>Subject Details - {{ $subject->name }}</h4>
                <div>
                    <a href="{{ route('subjects.edit', $subject) }}" class="btn btn-warning btn-sm">Edit</a>
                    <a href="{{ route('subjects.index') }}" class="btn btn-secondary btn-sm">Back</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">Subject Name:</th>
                                <td>{{ $subject->name }}</td>
                            </tr>
                            <tr>
                                <th>Subject Code:</th>
                                <td><code>{{ $subject->code }}</code></td>
                            </tr>
                            <tr>
                                <th>Full Marks:</th>
                                <td>{{ $subject->full_marks }}</td>
                            </tr>
                            <tr>
                                <th>Pass Marks:</th>
                                <td>{{ $subject->pass_marks }}</td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    @if($subject->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Created:</th>
                                <td>{{ $subject->created_at->format('M d, Y') }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6>Subject Statistics</h6>
                                <p class="mb-1"><strong>Pass Percentage:</strong> {{ round(($subject->pass_marks / $subject->full_marks) * 100, 1) }}%</p>
                                <p class="mb-0"><strong>Grade Threshold:</strong> {{ $subject->pass_marks }}/{{ $subject->full_marks }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
