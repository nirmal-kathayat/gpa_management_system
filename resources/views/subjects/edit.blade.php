@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4>Edit Subject - {{ $subject->name }}</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('subjects.update', $subject) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Subject Name *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name', $subject->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="code" class="form-label">Subject Code *</label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror" 
                               id="code" name="code" value="{{ old('code', $subject->code) }}" required>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Unique identifier for the subject (e.g., ENG1, MATH)</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_marks" class="form-label">Full Marks *</label>
                            <input type="number" class="form-control @error('full_marks') is-invalid @enderror" 
                                   id="full_marks" name="full_marks" value="{{ old('full_marks', $subject->full_marks) }}" 
                                   min="1" max="200" required>
                            @error('full_marks')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="pass_marks" class="form-label">Pass Marks *</label>
                            <input type="number" class="form-control @error('pass_marks') is-invalid @enderror" 
                                   id="pass_marks" name="pass_marks" value="{{ old('pass_marks', $subject->pass_marks) }}" 
                                   min="1" max="100" required>
                            @error('pass_marks')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                   {{ old('is_active', $subject->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active Subject
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('subjects.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
