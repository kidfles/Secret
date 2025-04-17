@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-semibold mb-4">Route Genereren</h2>
        <form action="{{ route('routes.generate') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="num_routes" class="block text-sm font-medium text-gray-700">Aantal Routes</label>
                    <input type="number" name="num_routes" id="num_routes" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500" value="1" min="1" required>
                    <p class="mt-1 text-sm text-gray-500">Aantal routes dat gegenereerd moet worden</p>
                </div>

                <div>
                    <label for="capacity" class="block text-sm font-medium text-gray-700">Capaciteit per Route</label>
                    <input type="number" name="capacity" id="capacity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500" value="2" min="1" required>
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
                                <p class="text-sm text-gray-600">Capaciteit: {{ $route->capacity }} personen</p>
                                <p class="text-sm text-gray-500 mt-1">
                                    Locaties: {{ $route->locations->pluck('name')->join(', ') }}
                                </p>
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
    let map = null;
    let markers = [];
    let polylines = [];
    const routes = @json($routes);

    function initializeMap() {
        if (!map) {
            map = L.map('map').setView([52.3676, 4.9041], 7);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Add markers and polylines for each route
            routes.forEach(route => {
                const routeLocations = route.locations;
                const routeMarkers = [];
                const routeCoordinates = [];

                routeLocations.forEach(location => {
                    const marker = L.marker([location.latitude, location.longitude])
                        .bindPopup(`
                            <div class="p-2">
                                <h3 class="font-medium">${location.name}</h3>
                                <p class="text-sm">${location.address}</p>
                                <p class="text-sm text-gray-500 mt-1">Capaciteit: ${location.person_capacity} personen</p>
                            </div>
                        `)
                        .addTo(map);
                    routeMarkers.push(marker);
                    routeCoordinates.push([location.latitude, location.longitude]);
                });

                if (routeCoordinates.length > 1) {
                    const polyline = L.polyline(routeCoordinates, {
                        color: 'red',
                        weight: 3,
                        opacity: 0.7
                    }).addTo(map);
                    polylines.push(polyline);
                }

                markers.push(...routeMarkers);
            });

            if (markers.length > 0) {
                const bounds = L.latLngBounds(markers.map(marker => marker.getLatLng()));
                map.fitBounds(bounds);
            }
        }
    }

    // Initialize map when it comes into view
    const mapObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                initializeMap();
                mapObserver.unobserve(entry.target);
            }
        });
    });

    document.querySelectorAll('#map').forEach(map => {
        mapObserver.observe(map);
    });
</script>
@endpush 