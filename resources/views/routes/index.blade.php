@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
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

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold">Routes genereren</h2>
                    <form action="{{ route('routes.deleteAll') }}" method="POST" class="inline" onsubmit="return confirm('Weet je zeker dat je alle routes wilt verwijderen?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                            Alle routes verwijderen
                        </button>
                    </form>
                </div>
                <form action="{{ route('routes.generate') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="num_routes" class="block text-sm font-medium text-gray-700">Aantal routes</label>
                        <input type="number" name="num_routes" id="num_routes" min="1" value="1" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Routes genereren
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Startlocatie</h2>
                <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
                    <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div>
                        <h3 class="font-medium">Broekstraat 68</h3>
                        <p class="text-sm text-gray-600">Nederasselt</p>
                    </div>
                </div>
            </div>

            @foreach($routes as $route)
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold">Route {{ $loop->iteration }}</h2>
                    <div class="flex space-x-2">
                        <button onclick="recalculateRoute({{ $route->id }})" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <form action="{{ route('routes.destroy', $route) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('Weet je zeker dat je deze route wilt verwijderen?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="space-y-2" id="route-{{ $route->id }}">
                    @foreach($route->locations as $location)
                    <div class="location-item flex items-center space-x-4 p-4 bg-gray-50 rounded-lg cursor-move" draggable="true" data-location-id="{{ $location->id }}">
                        <div class="w-8 h-8 rounded-full bg-gray-500 flex items-center justify-center text-white">
                            {{ $loop->iteration }}
                        </div>
                        <div>
                            <h3 class="font-medium">{{ $location->address }}</h3>
                            <p class="text-sm text-gray-600">{{ $location->city }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div id="map" class="h-[600px] rounded-lg"></div>
        </div>
    </div>
</div>

<style>
.route-locations {
    min-height: 50px;
}

.location-item {
    cursor: move;
}

.location-item.dragging {
    opacity: 0.5;
    background: #f8f9fa;
}

.route-locations.drag-over {
    background: #f3f4f6;
}

.location-content {
    pointer-events: none;
}
</style>

@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<link href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" rel="stylesheet">
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize map
    const map = L.map('map').setView([51.8372, 5.6697], 10);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Add starting location marker
    const startLocation = {
        lat: 51.8372,
        lng: 5.6697,
        name: 'Broekstraat 68',
        address: 'Nederasselt'
    };
    const startMarker = L.marker([startLocation.lat, startLocation.lng]).addTo(map);
    startMarker.bindPopup(`
        <div class="p-2">
            <h3 class="font-medium">${startLocation.name}</h3>
            <p class="text-sm text-gray-600">${startLocation.address}</p>
        </div>
    `);

    // Store route polylines
    const routePolylines = new Map();
    const routeMarkers = new Map();

    // Update map with route visualization
    function updateMap(routes) {
        // Clear existing polylines and markers
        routePolylines.forEach(polyline => polyline.remove());
        routePolylines.clear();
        routeMarkers.forEach(markers => markers.forEach(marker => marker.remove()));
        routeMarkers.clear();

        // Add starting location to bounds
        const bounds = L.latLngBounds([startLocation.lat, startLocation.lng]);

        // Colors for different routes
        const colors = ['#FF0000', '#00FF00', '#0000FF', '#FFA500', '#800080'];

        routes.forEach((route, index) => {
            const color = colors[index % colors.length];
            const markers = [];
            const coordinates = [[startLocation.lat, startLocation.lng]];

            // Add route locations
            route.locations.forEach(location => {
                const marker = L.marker([location.latitude, location.longitude]).addTo(map);
                marker.bindPopup(`
                    <div class="p-2">
                        <h3 class="font-medium">${location.address}</h3>
                        <p class="text-sm text-gray-600">${location.city}</p>
                    </div>
                `);
                markers.push(marker);
                coordinates.push([location.latitude, location.longitude]);
                bounds.extend([location.latitude, location.longitude]);
            });

            // Add polyline for the route
            const polyline = L.polyline(coordinates, { color: color, weight: 3, opacity: 0.7 }).addTo(map);
            routePolylines.set(route.id, polyline);
            routeMarkers.set(route.id, markers);

            // Add back to start
            polyline.addLatLng([startLocation.lat, startLocation.lng]);
        });

        // Fit map to bounds
        map.fitBounds(bounds, { padding: [50, 50] });
    }

    // Initialize routes
    const routes = @json($routes);
    updateMap(routes);

    // Drag and drop functionality
    document.querySelectorAll('.location-item').forEach(item => {
        item.addEventListener('dragstart', handleDragStart);
        item.addEventListener('dragend', handleDragEnd);
    });

    document.querySelectorAll('[id^="route-"]').forEach(container => {
        container.addEventListener('dragover', handleDragOver);
        container.addEventListener('dragenter', handleDragEnter);
        container.addEventListener('dragleave', handleDragLeave);
        container.addEventListener('drop', handleDrop);
    });

    function handleDragStart(e) {
        e.target.classList.add('opacity-50');
        e.dataTransfer.setData('text/plain', e.target.dataset.locationId);
    }

    function handleDragEnd(e) {
        e.target.classList.remove('opacity-50');
    }

    function handleDragOver(e) {
        e.preventDefault();
    }

    function handleDragEnter(e) {
        e.preventDefault();
        e.target.closest('[id^="route-"]').classList.add('bg-blue-50');
    }

    function handleDragLeave(e) {
        e.target.closest('[id^="route-"]').classList.remove('bg-blue-50');
    }

    function handleDrop(e) {
        e.preventDefault();
        const container = e.target.closest('[id^="route-"]');
        container.classList.remove('bg-blue-50');

        const locationId = e.dataTransfer.getData('text/plain');
        const sourceRoute = document.querySelector(`[data-location-id="${locationId}"]`).closest('[id^="route-"]');
        const targetRoute = container;

        if (sourceRoute !== targetRoute) {
            moveLocation(locationId, sourceRoute.id.split('-')[1], targetRoute.id.split('-')[1]);
        }
    }

    function moveLocation(locationId, sourceRouteId, targetRouteId) {
        fetch(`/routes/${sourceRouteId}/move-location`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                location_id: locationId,
                target_route_id: targetRouteId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error moving location: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error moving location');
        });
    }

    function recalculateRoute(routeId) {
        fetch(`/routes/${routeId}/recalculate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error recalculating route: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error recalculating route');
        });
    }
});
</script>
@endpush