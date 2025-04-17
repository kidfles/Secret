@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
            <p class="text-sm text-blue-700">
                <strong>Note:</strong> All routes will start from Overrijssel, Netherlands. You don't need to add this location manually.
            </p>
        </div>
        
        <h2 class="text-lg font-semibold mb-4">Add Location</h2>
        <form action="{{ route('route-optimizer.store') }}" method="POST" id="locationForm">
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Location Name</label>
                    <input type="text" name="name" id="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                </div>

                <div>
                    <label for="full_address" class="block text-sm font-medium text-gray-700">Full Address</label>
                    <input type="text" id="full_address" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Enter full address (e.g., Hoofdstraat 123, Amsterdam)" required>
                    <p class="mt-1 text-sm text-gray-500">Enter the complete address and we'll parse it for you</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="street" class="block text-sm font-medium text-gray-700">Street Name</label>
                        <input type="text" name="street" id="street" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" readonly>
                    </div>

                    <div>
                        <label for="house_number" class="block text-sm font-medium text-gray-700">House Number</label>
                        <input type="text" name="house_number" id="house_number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" readonly>
                    </div>
                </div>

                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700">City</label>
                    <input type="text" name="city" id="city" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" readonly>
                </div>

                <div>
                    <label for="person_capacity" class="block text-sm font-medium text-gray-700">Number of Persons</label>
                    <input type="number" name="person_capacity" id="person_capacity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="2" min="1">
                    <p class="mt-1 text-sm text-gray-500">Default is 2 persons per location</p>
                </div>

                <!-- Hidden fields for coordinates -->
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                <input type="hidden" name="address" id="address">

                <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Add Location
                </button>
            </div>
        </form>

        <div class="mt-8">
            <h2 class="text-lg font-semibold mb-4">Your Locations</h2>
            <div class="space-y-4">
                @forelse($locations as $location)
                    <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors duration-200">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-medium text-lg">{{ $location->name }}</h3>
                                <p class="text-sm text-gray-600">{{ $location->address }}</p>
                                <p class="text-sm text-gray-500 mt-1">Capacity: {{ $location->person_capacity }} persons</p>
                            </div>
                            <form action="{{ route('route-optimizer.destroy', $location) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">No locations added yet.</p>
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
    document.addEventListener('DOMContentLoaded', function() {
        const map = L.map('map').setView([52.3676, 4.9041], 7); // Center on Netherlands
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        const locations = @json($locations);
        const markers = [];
        const polylines = [];

        locations.forEach(location => {
            const marker = L.marker([location.latitude, location.longitude])
                .bindPopup(`
                    <div class="p-2">
                        <h3 class="font-medium">${location.name}</h3>
                        <p class="text-sm">${location.address}</p>
                        <p class="text-sm text-gray-500 mt-1">Capacity: ${location.person_capacity} persons</p>
                    </div>
                `)
                .addTo(map);
            markers.push(marker);
        });

        if (markers.length > 0) {
            const bounds = L.latLngBounds(markers.map(marker => marker.getLatLng()));
            map.fitBounds(bounds);
        }
    });

    document.getElementById('full_address').addEventListener('blur', async function() {
        const fullAddress = this.value;
        if (!fullAddress) return;
        
        try {
            // Use Nominatim OpenStreetMap API for geocoding
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(fullAddress)}`);
            const data = await response.json();
            
            if (data && data.length > 0) {
                // Set the coordinates
                document.getElementById('latitude').value = data[0].lat;
                document.getElementById('longitude').value = data[0].lon;
                document.getElementById('address').value = fullAddress;
                
                // Parse the address components
                const addressParts = data[0].display_name.split(', ');
                
                // Extract city (usually the second-to-last part)
                const city = addressParts[addressParts.length - 2] || '';
                document.getElementById('city').value = city;
                
                // Extract street and house number from the first part
                const streetPart = addressParts[0] || '';
                const streetMatch = streetPart.match(/^(.+?)\s+(\d+.*)$/);
                
                if (streetMatch) {
                    document.getElementById('street').value = streetMatch[1].trim();
                    document.getElementById('house_number').value = streetMatch[2].trim();
                } else {
                    // If we can't parse it properly, just put the whole first part as street
                    document.getElementById('street').value = streetPart;
                    document.getElementById('house_number').value = '';
                }
            } else {
                alert('Address not found. Please check the address and try again.');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error looking up address. Please try again.');
        }
    });

    document.getElementById('locationForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // If the form is submitted without the address being looked up first,
        // we need to do the lookup now
        if (!document.getElementById('latitude').value) {
            const fullAddress = document.getElementById('full_address').value;
            if (!fullAddress) {
                alert('Please enter a valid address');
                return;
            }
            
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(fullAddress)}`);
                const data = await response.json();
                
                if (data && data.length > 0) {
                    document.getElementById('latitude').value = data[0].lat;
                    document.getElementById('longitude').value = data[0].lon;
                    document.getElementById('address').value = fullAddress;
                    
                    // Parse the address components
                    const addressParts = data[0].display_name.split(', ');
                    
                    // Extract city (usually the second-to-last part)
                    const city = addressParts[addressParts.length - 2] || '';
                    document.getElementById('city').value = city;
                    
                    // Extract street and house number from the first part
                    const streetPart = addressParts[0] || '';
                    const streetMatch = streetPart.match(/^(.+?)\s+(\d+.*)$/);
                    
                    if (streetMatch) {
                        document.getElementById('street').value = streetMatch[1].trim();
                        document.getElementById('house_number').value = streetMatch[2].trim();
                    } else {
                        // If we can't parse it properly, just put the whole first part as street
                        document.getElementById('street').value = streetPart;
                        document.getElementById('house_number').value = '';
                    }
                    
                    // Submit the form
                    this.submit();
                } else {
                    alert('Address not found. Please check the address and try again.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error looking up address. Please try again.');
            }
        } else {
            // If we already have the coordinates, just submit the form
            this.submit();
        }
    });
</script>
@endpush 