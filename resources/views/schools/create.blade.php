@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4>Add New School</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('schools.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">School Name *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                            id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address *</label>
                        <textarea class="form-control @error('address') is-invalid @enderror"
                            id="address" name="address" rows="3" required>{{ old('address') }}</textarea>
                        @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                id="phone" name="phone" value="{{ old('phone') }}">
                            @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                id="email" name="email" value="{{ old('email') }}">
                            @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="logo" class="form-label">School Logo</label>
                        <input type="file" class="form-control @error('logo') is-invalid @enderror"
                            id="logo" name="logo" accept="image/*">
                        @error('logo')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Upload school logo (JPEG, PNG, JPG, GIF - Max: 2MB)</div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('schools.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create School</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection