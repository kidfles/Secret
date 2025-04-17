@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-semibold mb-4">Add Location</h2>
        <form action="{{ route('route-optimizer.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Location Name</label>
                    <input type="text" name="name" id="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                </div>

                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                    <input type="text" name="address" id="address" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="latitude" class="block text-sm font-medium text-gray-700">Latitude</label>
                        <input type="number" step="any" name="latitude" id="latitude" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    </div>

                    <div>
                        <label for="longitude" class="block text-sm font-medium text-gray-700">Longitude</label>
                        <input type="number" step="any" name="longitude" id="longitude" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    </div>
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Add Location
                </button>
            </div>
        </form>

        <div class="mt-6">
            <h2 class="text-lg font-semibold mb-4">Locations</h2>
            <div class="space-y-4">
                @foreach($locations as $location)
                    <div class="flex justify-between items-center p-4 bg-gray-50 rounded-md">
                        <div>
                            <h3 class="font-medium">{{ $location->name }}</h3>
                            <p class="text-sm text-gray-600">{{ $location->address }}</p>
                            <p class="text-xs text-gray-500">{{ $location->latitude }}, {{ $location->longitude }}</p>
                        </div>
                        <form action="{{ route('route-optimizer.destroy', $location) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800">
                                Delete
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>

            @if($locations->count() >= 2)
                <button id="calculateRoute" class="mt-4 w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    Calculate Optimal Route
                </button>
            @endif
        </div>
    </div>

    <div class="bg-white shadow-sm rounded-lg p-6">
        <div id="map" class="h-[600px] rounded-lg"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const map = L.map('map').setView([52.3676, 4.9041], 7); // Center on Netherlands
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        const locations = @json($locations);
        const markers = [];
        const polyline = L.polyline([], { color: 'blue' }).addTo(map);

        locations.forEach(location => {
            const marker = L.marker([location.latitude, location.longitude])
                .bindPopup(location.name)
                .addTo(map);
            markers.push(marker);
        });

        if (markers.length > 0) {
            const bounds = L.latLngBounds(markers.map(marker => marker.getLatLng()));
            map.fitBounds(bounds);
        }

        const calculateRouteButton = document.getElementById('calculateRoute');
        if (calculateRouteButton) {
            calculateRouteButton.addEventListener('click', async () => {
                try {
                    const response = await fetch('{{ route("route-optimizer.calculate") }}');
                    const data = await response.json();

                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    // Clear existing polyline
                    polyline.setLatLngs([]);

                    // Create new route path
                    const routeCoordinates = data.route.map(location => [location.lat, location.lng]);
                    polyline.setLatLngs(routeCoordinates);

                    // Add route information
                    alert(`Total distance: ${data.total_distance} km`);
                } catch (error) {
                    console.error('Error calculating route:', error);
                    alert('Error calculating route. Please try again.');
                }
            });
        }
    });
</script>
@endpush 