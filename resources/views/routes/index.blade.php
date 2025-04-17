@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-semibold mb-4">Route Genereren</h2>
        
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded-md">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-md">
                {{ session('error') }}
            </div>
        @endif
        
        <form action="{{ route('routes.generate') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="num_routes" class="block text-sm font-medium text-gray-700">Aantal Routes</label>
                    <input type="number" name="num_routes" id="num_routes" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500" value="1" min="1" required>
                    <p class="mt-1 text-sm text-gray-500">Aantal routes dat gegenereerd moet worden</p>
                </div>

                <div>
                    <label for="route_capacity" class="block text-sm font-medium text-gray-700">Capaciteit per Route</label>
                    <input type="number" name="route_capacity" id="route_capacity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500" value="2" min="1" required>
                    <p class="mt-1 text-sm text-gray-500">Maximaal aantal personen per route</p>
                </div>

                <button type="submit" class="w-full bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    Routes Genereren
                </button>
            </div>
        </form>

        <div class="mt-8">
            <h2 class="text-lg font-semibold mb-4">Uw Routes</h2>
            <div class="space-y-4">
                @forelse($routes as $route)
                    <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors duration-200">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-medium text-lg">{{ $route->name }}</h3>
                                <p class="text-sm text-gray-600">Capaciteit: {{ $route->person_capacity }} personen</p>
                                <div class="mt-2">
                                    <h4 class="text-sm font-medium text-gray-700">Locaties:</h4>
                                    <ol class="list-decimal list-inside text-sm text-gray-600">
                                        @foreach($route->locations as $location)
                                            <li>{{ $location->name }} ({{ $location->person_capacity }} personen)</li>
                                        @endforeach
                                    </ol>
                                </div>
                            </div>
                            <form action="{{ route('routes.destroy', $route) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">
                                    Verwijderen
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">Nog geen routes gegenereerd.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="bg-white shadow-sm rounded-lg p-6">
        <div id="map" class="h-[600px] rounded-lg"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Defer map initialization until after page load
    window.addEventListener('load', function() {
        // Check if Leaflet is loaded
        if (typeof L === 'undefined') {
            console.error('Leaflet not loaded');
            return;
        }
        
        // Initialize map with a slight delay to prioritize other page elements
        setTimeout(function() {
            const map = L.map('map').setView([52.3676, 4.9041], 7); // Center on Netherlands
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            const routes = @json($routes);
            const markers = [];
            const polylines = [];
            
            // Only process routes if there are any
            if (routes.length === 0) {
                return;
            }
            
            // Process routes in batches to avoid blocking the UI
            const processRoutes = function(startIndex) {
                const batchSize = 2; // Process 2 routes at a time
                const endIndex = Math.min(startIndex + batchSize, routes.length);
                
                for (let i = startIndex; i < endIndex; i++) {
                    const route = routes[i];
                    const routeCoordinates = [];
                    const routeColor = getRouteColor(i);
                    
                    // Process locations for this route
                    route.locations.forEach((location, locationIndex) => {
                        const marker = L.marker([location.latitude, location.longitude])
                            .bindPopup(`
                                <div class="p-2">
                                    <h3 class="font-medium">${route.name} - Stop ${locationIndex + 1}</h3>
                                    <p class="text-sm">${location.name}</p>
                                    <p class="text-sm text-gray-500">${location.person_capacity} personen</p>
                                </div>
                            `)
                            .addTo(map);
                        markers.push(marker);
                        routeCoordinates.push([location.latitude, location.longitude]);
                    });

                    if (routeCoordinates.length > 1) {
                        const polyline = L.polyline(routeCoordinates, {
                            color: routeColor,
                            weight: 3,
                            opacity: 0.7
                        }).addTo(map);
                        polylines.push(polyline);
                    }
                }
                
                // If there are more routes to process, schedule the next batch
                if (endIndex < routes.length) {
                    setTimeout(function() {
                        processRoutes(endIndex);
                    }, 100);
                } else {
                    // All routes processed, fit bounds to markers
                    if (markers.length > 0) {
                        const bounds = L.latLngBounds(markers.map(marker => marker.getLatLng()));
                        map.fitBounds(bounds);
                    }
                }
            };
            
            // Start processing routes
            processRoutes(0);
        }, 500); // 500ms delay
    });

    function getRouteColor(index) {
        const colors = [
            '#FF0000', // Red
            '#0000FF', // Blue
            '#00FF00', // Green
            '#FFA500', // Orange
            '#800080', // Purple
            '#008080', // Teal
            '#FFD700', // Gold
            '#FF69B4', // Pink
            '#4B0082', // Indigo
            '#006400'  // Dark Green
        ];
        return colors[index % colors.length];
    }
</script>
@endpush 