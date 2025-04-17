@extends('layouts.app')

@section('content')
<div class="bg-white shadow rounded-lg p-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Edit Location</h2>
        
        <!-- Search Form -->
        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Search Location</h3>
            <form id="searchForm" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="street" class="block text-sm font-medium text-gray-700">Street Name</label>
                        <input type="text" id="street" name="street" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="houseNumber" class="block text-sm font-medium text-gray-700">House Number</label>
                        <input type="text" id="houseNumber" name="houseNumber" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700">City</label>
                        <input type="text" id="city" name="city" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Search Location
                    </button>
                </div>
            </form>
        </div>

        <form action="{{ route('locations.update', $location) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Location Name</label>
                    <input type="text" name="name" id="name" value="{{ $location->name }}" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                    <input type="text" name="address" id="address" value="{{ $location->address }}" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            <!-- Hidden fields for coordinates -->
            <input type="hidden" name="latitude" id="latitude" value="{{ $location->latitude }}">
            <input type="hidden" name="longitude" id="longitude" value="{{ $location->longitude }}">

            <div class="h-96 mb-6">
                <div id="map" class="w-full h-full rounded-lg shadow-lg"></div>
            </div>

            <div class="flex justify-end space-x-4">
                <a href="{{ route('route-optimizer.index') }}" 
                    class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </a>
                <button type="submit" 
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Update Location
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let map;
    let marker;
    let searchTimeout;

    function initMap() {
        // Initialize map with current location or default to a central location
        map = L.map('map').setView([{{ $location->latitude }}, {{ $location->longitude }}], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Add marker for current location
        marker = L.marker([{{ $location->latitude }}, {{ $location->longitude }}], {
            draggable: true
        }).addTo(map);

        // Update coordinates when marker is dragged
        marker.on('dragend', function(e) {
            const position = e.target.getLatLng();
            document.getElementById('latitude').value = position.lat.toFixed(6);
            document.getElementById('longitude').value = position.lng.toFixed(6);
            
            // Reverse geocode to get address
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${position.lat}&lon=${position.lng}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('address').value = data.display_name;
                })
                .catch(error => console.error('Error:', error));
        });
    }

    // Initialize map when the page loads
    document.addEventListener('DOMContentLoaded', initMap);

    // Handle search form submission
    document.getElementById('searchForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const street = document.getElementById('street').value;
        const houseNumber = document.getElementById('houseNumber').value;
        const city = document.getElementById('city').value;
        
        // Construct search query
        const searchQuery = `${houseNumber} ${street}, ${city}`.trim();
        
        // Clear previous timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        // Add debounce to prevent too many requests
        searchTimeout = setTimeout(() => {
            // Search for the location using Nominatim
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery)}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        const location = data[0];
                        const lat = parseFloat(location.lat);
                        const lon = parseFloat(location.lon);
                        
                        // Update map view and marker
                        map.setView([lat, lon], 16);
                        marker.setLatLng([lat, lon]);
                        
                        // Update hidden form fields
                        document.getElementById('latitude').value = lat.toFixed(6);
                        document.getElementById('longitude').value = lon.toFixed(6);
                        document.getElementById('address').value = location.display_name;
                    } else {
                        alert('Location not found. Please try a different search.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error searching for location. Please try again.');
                });
        }, 500);
    });
</script>
@endpush 