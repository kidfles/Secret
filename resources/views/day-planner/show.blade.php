@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Dagplanning - {{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}</h5>
                    <div>
                        <a href="{{ route('day-planner.index') }}" class="btn btn-sm btn-secondary">Terug</a>
                        <a href="{{ route('day-planner.edit', $date) }}" class="btn btn-sm btn-primary">Bewerken</a>
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
                                                <a href="{{ route('routes.show', $route->id) }}" class="btn btn-sm btn-info">
                                                    Details bekijken
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