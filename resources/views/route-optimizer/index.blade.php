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
        <!-- Form Section -->
        <div class="bg-white shadow-lg rounded-lg p-6">
            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-md">
                <p class="text-sm text-red-700">
                    <strong class="font-semibold">Opmerking:</strong> Alle routes beginnen in Overrijssel, Nederland. U hoeft deze locatie niet handmatig toe te voegen.
                </p>
            </div>
            
            <h2 class="text-xl font-bold text-gray-800 mb-6">Locatie Toevoegen</h2>
            <form action="{{ route('route-optimizer.store') }}" method="POST" id="locationForm" class="space-y-6">
                @csrf
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">Locatienaam</label>
                    <input type="text" name="name" id="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 bg-gray-50 py-3" required>
                </div>

                <div>
                    <label for="full_address" class="block text-sm font-semibold text-gray-700 mb-1">Volledig Adres</label>
                    <input type="text" id="full_address" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 bg-gray-50 py-3" placeholder="Voer volledig adres in (bijv. Hoofdstraat 123, Amsterdam)" required>
                    <p class="mt-2 text-sm text-gray-500 italic">Voer het volledige adres in en wij vullen het voor u in</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="street" class="block text-sm font-semibold text-gray-700 mb-1">Straatnaam</label>
                        <input type="text" name="street" id="street" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm text-gray-600 py-3" readonly>
                    </div>

                    <div>
                        <label for="house_number" class="block text-sm font-semibold text-gray-700 mb-1">Huisnummer</label>
                        <input type="text" name="house_number" id="house_number" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm text-gray-600 py-3" readonly>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="city" class="block text-sm font-semibold text-gray-700 mb-1">Stad</label>
                        <input type="text" name="city" id="city" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm text-gray-600 py-3" readonly>
                    </div>

                    <div>
                        <label for="postal_code" class="block text-sm font-semibold text-gray-700 mb-1">Postcode</label>
                        <input type="text" name="postal_code" id="postal_code" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm text-gray-600 py-3" readonly>
                    </div>
                </div>

                <div>
                    <label for="person_capacity" class="block text-sm font-semibold text-gray-700 mb-1">Aantal Personen</label>
                    <input type="number" name="person_capacity" id="person_capacity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 bg-gray-50 py-3" value="2" min="1">
                    <p class="mt-2 text-sm text-gray-500 italic">Standaard is 2 personen per locatie</p>
                </div>

                <!-- Hidden fields for coordinates and address -->
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                <input type="hidden" name="address" id="address">

                <button type="submit" class="w-full bg-red-600 text-white py-3 px-4 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 font-medium transition-colors duration-200">
                    Locatie Toevoegen
                </button>
            </form>

            <div class="mt-8">
                <h2 class="text-xl font-bold text-gray-800 mb-6">Uw Locaties</h2>
                <div class="space-y-4">
                    @forelse($locations as $location)
                        <div class="bg-white border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors duration-200 shadow-sm">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-semibold text-lg text-gray-800">{{ $location->name }}</h3>
                                    <p class="text-sm text-gray-600">{{ $location->street }} {{ $location->house_number }}</p>
                                    <p class="text-sm text-gray-600">{{ $location->postal_code }} {{ $location->city }}</p>
                                    <p class="text-sm text-gray-500 mt-2">Capaciteit: {{ $location->person_capacity }} personen</p>
                                </div>
                                <form action="{{ route('route-optimizer.destroy', $location) }}" method="POST" onsubmit="return confirm('Weet u zeker dat u deze locatie wilt verwijderen?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium">
                                        Verwijderen
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 bg-white rounded-lg border-2 border-dashed border-gray-300">
                            <p class="text-gray-500">Nog geen locaties toegevoegd.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Map Section -->
        <div class="bg-white shadow-lg rounded-lg p-6">
            <div id="map" class="h-[600px] rounded-lg shadow-md"></div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize map
        const map = L.map('map').setView([52.3676, 4.9041], 7); // Center on Netherlands
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Add markers for existing locations
        const locations = @json($locations);
        const markers = [];

        locations.forEach(location => {
            const marker = L.marker([location.latitude, location.longitude])
                .bindPopup(`
                    <div class="p-2">
                        <h3 class="font-medium">${location.name}</h3>
                        <p class="text-sm">${location.street} ${location.house_number}</p>
                        <p class="text-sm">${location.postal_code} ${location.city}</p>
                        <p class="text-sm text-gray-500 mt-1">Capaciteit: ${location.person_capacity} personen</p>
                    </div>
                `)
                .addTo(map);
            markers.push(marker);
        });

        if (markers.length > 0) {
            const bounds = L.latLngBounds(markers.map(marker => marker.getLatLng()));
            map.fitBounds(bounds);
        }

        // Address parsing function
        function parseAddress(fullAddress) {
            if (!fullAddress) return;
            
            // Set the full address in the hidden field
            document.getElementById('address').value = fullAddress;
            
            // Split the address into parts
            const parts = fullAddress.split(' ');
            
            // The last part is usually the city
            const city = parts[parts.length - 1];
            document.getElementById('city').value = city;
            
            // The second-to-last part is usually the house number
            const houseNumber = parts[parts.length - 2];
            if (houseNumber && /^\d+\w*$/.test(houseNumber)) {
                document.getElementById('house_number').value = houseNumber;
                
                // Everything before the house number is the street name
                const streetName = parts.slice(0, parts.length - 2).join('');
                document.getElementById('street').value = streetName;
            } else {
                // If no house number found, use the second-to-last part as part of the street name
                const streetName = parts.slice(0, parts.length - 1).join(' ');
                document.getElementById('street').value = streetName;
                document.getElementById('house_number').value = '';
            }
            
            // Try to get coordinates and postal code for the full address
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(fullAddress)},Netherlands&countrycodes=nl`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        document.getElementById('latitude').value = data[0].lat;
                        document.getElementById('longitude').value = data[0].lon;
                        
                        // Try to extract postal code from the display_name
                        const displayName = data[0].display_name;
                        const postalCodeMatch = displayName.match(/\b\d{4}\s*[A-Z]{2}\b/);
                        if (postalCodeMatch) {
                            document.getElementById('postal_code').value = postalCodeMatch[0];
                        }
                        
                        // Update the map marker for the new location
                        updateMapMarker(data[0].lat, data[0].lon, fullAddress);
                    }
                })
                .catch(error => {
                    console.error('Error getting address coordinates:', error);
                });
        }
        
        // Function to update the map marker
        function updateMapMarker(lat, lon, address) {
            // Remove existing temporary marker if any
            if (window.tempMarker) {
                map.removeLayer(window.tempMarker);
            }
            
            // Add new marker
            window.tempMarker = L.marker([lat, lon])
                .bindPopup(`
                    <div class="p-2">
                        <h3 class="font-medium">Nieuwe Locatie</h3>
                        <p class="text-sm">${address}</p>
                    </div>
                `)
                .addTo(map);
            
            // Center map on new marker
            map.setView([lat, lon], 15);
        }

        // Add event listener for the full address input
        const fullAddressInput = document.getElementById('full_address');
        
        // Parse address on input change
        fullAddressInput.addEventListener('input', function() {
            parseAddress(this.value);
        });
        
        // Also parse on blur for good measure
        fullAddressInput.addEventListener('blur', function() {
            parseAddress(this.value);
        });
        
        // Handle form submission
        document.getElementById('locationForm').addEventListener('submit', function(e) {
            // Make sure we have coordinates before submitting
            if (!document.getElementById('latitude').value) {
                e.preventDefault();
                alert('Voer een geldig adres in');
                return;
            }
            
            // Make sure the address field is set
            if (!document.getElementById('address').value) {
                document.getElementById('address').value = fullAddressInput.value;
            }
        });
    });
</script>
@endsection 