@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h1>Dagplanning bewerken - {{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}</h1>
            <div>
                <a href="{{ route('day-planner.show', $date) }}" class="btn btn-outline-secondary">Terug</a>
            </div>
        </div>
        
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            
            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif
            
            <form action="{{ route('day-planner.update', $date) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="mb-3">
                    <label for="new_date" class="form-label">Datum wijzigen</label>
                    <input type="date" class="form-control" id="new_date" name="new_date" 
                           value="{{ $date }}" required>
                    @error('new_date')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
                
                <button type="submit" class="btn btn-primary">Opslaan</button>
            </form>
            
            <hr>
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Routes op deze dag ({{ $routes->count() }})</h2>
            </div>
            
            @if($routes->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Naam</th>
                                <th>Aantal locaties</th>
                                <th>Acties</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($routes as $route)
                                <tr>
                                    <td>{{ $route->name }}</td>
                                    <td>{{ $route->locations_count ?? $route->locations->count() }}</td>
                                    <td>
                                        <a href="{{ route('routes.show', $route->id) }}" class="btn btn-sm btn-info">Details</a>
                                        <a href="{{ route('routes.edit', $route->id) }}" class="btn btn-sm btn-warning">Bewerken</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info">
                    Er zijn nog geen routes voor deze dag.
                </div>
            @endif
            
            <form action="{{ route('day-planner.destroy', $date) }}" method="POST" class="mt-4" 
                  onsubmit="return confirm('Weet je zeker dat je alle routes voor deze dag wilt verwijderen?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Verwijder alle routes voor deze dag</button>
            </form>
        </div>
    </div>
</div>
@endsection 