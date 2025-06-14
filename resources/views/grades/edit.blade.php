@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4>Edit Grade - {{ $grade->letter_grade }}</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('grades.update', $grade) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="letter_grade" class="form-label">Letter Grade *</label>
                        <input type="text" class="form-control @error('letter_grade') is-invalid @enderror" 
                               id="letter_grade" name="letter_grade" value="{{ old('letter_grade', $grade->letter_grade) }}" 
                               placeholder="e.g., A+, B, C+" required>
                        @error('letter_grade')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="grade_point" class="form-label">Grade Point *</label>
                        <input type="number" class="form-control @error('grade_point') is-invalid @enderror" 
                               id="grade_point" name="grade_point" value="{{ old('grade_point', $grade->grade_point) }}" 
                               min="0" max="4" step="0.1" placeholder="e.g., 4.0, 3.6" required>
                        @error('grade_point')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="marks_from" class="form-label">Marks From *</label>
                            <input type="number" class="form-control @error('marks_from') is-invalid @enderror" 
                                   id="marks_from" name="marks_from" value="{{ old('marks_from', $grade->marks_from) }}" 
                                   min="0" max="100" required>
                            @error('marks_from')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="marks_to" class="form-label">Marks To *</label>
                            <input type="number" class="form-control @error('marks_to') is-invalid @enderror" 
                                   id="marks_to" name="marks_to" value="{{ old('marks_to', $grade->marks_to) }}" 
                                   min="0" max="100" required>
                            @error('marks_to')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="remarks" class="form-label">Remarks *</label>
                        <input type="text" class="form-control @error('remarks') is-invalid @enderror" 
                               id="remarks" name="remarks" value="{{ old('remarks', $grade->remarks) }}" 
                               placeholder="e.g., Outstanding, Excellent" required>
                        @error('remarks')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('grades.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Grade</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
