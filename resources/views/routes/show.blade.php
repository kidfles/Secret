@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-2xl font-semibold">{{ $route->name }}</h2>
                @if($route->description)
                    <p class="text-gray-600 mt-2">{{ $route->description }}</p>
                @endif
            </div>
            <a href="{{ route('routes.index') }}" class="text-indigo-600 hover:text-indigo-800">
                Back to Routes
            </a>
        </div>

        <div class="space-y-4">
            <h3 class="text-lg font-medium">Locations in Route</h3>
            @foreach($route->locations as $location)
                <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors duration-200">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="font-medium">{{ $location->name }}</h4>
                            <p class="text-sm text-gray-600">{{ $location->address }}</p>
                            <p class="text-xs text-gray-500 mt-1">Coordinates: {{ $location->latitude }}, {{ $location->longitude }}</p>
                        </div>
                        <span class="text-sm text-gray-500">Stop {{ $loop->iteration }}</span>
                    </div>
                </div>
            @endforeach
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

        const locations = @json($route->locations);
        const markers = [];
        const coordinates = [];

        locations.forEach((location, index) => {
            const marker = L.marker([location.latitude, location.longitude])
                .bindPopup(`
                    <div class="p-2">
                        <h3 class="font-medium">Stop ${index + 1}: ${location.name}</h3>
                        <p class="text-sm">${location.address}</p>
                    </div>
                `)
                .addTo(map);
            markers.push(marker);
            coordinates.push([location.latitude, location.longitude]);
        });

        if (markers.length > 0) {
            const bounds = L.latLngBounds(markers.map(marker => marker.getLatLng()));
            map.fitBounds(bounds);

            // Draw route line
            const polyline = L.polyline(coordinates, {
                color: 'blue',
                weight: 3,
                opacity: 0.7
            }).addTo(map);
        }
    });
</script>
@endpush 