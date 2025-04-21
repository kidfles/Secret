@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h1>Dagplanning - {{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}</h1>
                    <div>
                        @if($prevDate)
                            <a href="{{ route('day-planner.show', $prevDate) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Vorige dag
                            </a>
                        @endif
                        @if($nextDate)
                            <a href="{{ route('day-planner.show', $nextDate) }}" class="btn btn-outline-secondary">
                                Volgende dag <i class="fas fa-arrow-right"></i>
                            </a>
                        @endif
                        <a href="{{ route('day-planner.edit', $date) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Bewerken
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Quick Access Toolbar -->
                    <div class="quick-access-toolbar mb-4 p-3 bg-light rounded d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Snelle toegang</h5>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary" 
                                    onclick="setDateAndNavigate('{{ $date }}', '{{ route('routes.index') }}')">
                                <i class="fas fa-route"></i> Routes beheren
                            </button>
                            <button type="button" class="btn btn-info" 
                                    onclick="setDateAndNavigate('{{ $date }}', '{{ route('route-optimizer.index') }}')">
                                <i class="fas fa-map-marked-alt"></i> Locaties beheren
                            </button>
                            <button type="button" class="btn btn-success" 
                                    onclick="setDateAndNavigate('{{ $date }}', '{{ route('routes.approval.index') }}')">
                                <i class="fas fa-check-circle"></i> Routes goedkeuren
                            </button>
                        </div>
                    </div>

                    <!-- Day Planning Notes -->
                    <div class="day-planning-notes mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Notities</h5>
                            </div>
                            <div class="card-body">
                                @if($dayPlanning->notes)
                                    <div class="notes-content">
                                        {{ $dayPlanning->notes }}
                                    </div>
                                @else
                                    <p class="text-muted">Geen notities beschikbaar voor deze dag.</p>
                                @endif
                            </div>
                        </div>
                    </div>

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

                    <!-- Routes List -->
                    <h5>Routes voor deze dag</h5>
                    
                    @if($routes->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Route naam</th>
                                        <th>Locaties</th>
                                        <th>Acties</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($routes as $route)
                                        <tr>
                                            <td>{{ $route->name }}</td>
                                            <td>{{ $route->locations->count() }} locaties</td>
                                            <td>
                                                <a href="{{ route('routes.edit', $route->id) }}" class="btn btn-sm btn-info" 
                                                   onclick="event.preventDefault(); setDateAndNavigate('{{ $date }}', '{{ route('routes.edit', $route->id) }}')">
                                                    Bewerken
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            Er zijn nog geen routes gepland voor deze dag.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 