@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h1>Nieuwe route aanmaken</h1>
            <div>
                <a href="{{ route('routes.index') }}" class="btn btn-outline-secondary">Terug</a>
            </div>
        </div>
        
        <div class="card-body">
            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif
            
            <form action="{{ route('routes.store') }}" method="POST">
                @csrf
                
                <div class="mb-3">
                    <label for="name" class="form-label">Route naam</label>
                    <input type="text" class="form-control" id="name" name="name" 
                           value="{{ old('name') }}" required>
                    @error('name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="date" class="form-label">Datum</label>
                    <input type="date" class="form-control" id="date" name="date" 
                           value="{{ old('date', now()->format('Y-m-d')) }}" required>
                    @error('date')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                
                <button type="submit" class="btn btn-primary">Opslaan</button>
            </form>
        </div>
    </div>
</div>
@endsection 