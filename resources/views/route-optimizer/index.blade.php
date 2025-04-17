@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
            <p class="text-sm text-red-700">
                <strong>Opmerking:</strong> Alle routes beginnen in Overrijssel, Nederland. U hoeft deze locatie niet handmatig toe te voegen.
            </p>
        </div>
        
        <h2 class="text-lg font-semibold mb-4">Locatie Toevoegen</h2>
        <form action="{{ route('route-optimizer.store') }}" method="POST" id="locationForm">
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Locatienaam</label>
                    <input type="text" name="name" id="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500" required>
                </div>

                <div>
                    <label for="full_address" class="block text-sm font-medium text-gray-700">Volledig Adres</label>
                    <input type="text" id="full_address" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500" placeholder="Voer volledig adres in (bijv. Hoofdstraat 123, Amsterdam)" required>
                    <p class="mt-1 text-sm text-gray-500">Voer het volledige adres in en wij vullen het voor u in</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="street" class="block text-sm font-medium text-gray-700">Straatnaam</label>
                        <input type="text" name="street" id="street" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500" readonly>
                    </div>

                    <div>
                        <label for="house_number" class="block text-sm font-medium text-gray-700">Huisnummer</label>
                        <input type="text" name="house_number" id="house_number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500" readonly>
                    </div>
                </div>

                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700">Stad</label>
                    <input type="text" name="city" id="city" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500" readonly>
                </div>

                <div>
                    <label for="postal_code" class="block text-sm font-medium text-gray-700">Postcode</label>
                    <input type="text" name="postal_code" id="postal_code" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500" readonly>
                </div>

                <div>
                    <label for="person_capacity" class="block text-sm font-medium text-gray-700">Aantal Personen</label>
                    <input type="number" name="person_capacity" id="person_capacity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500" value="2" min="1">
                    <p class="mt-1 text-sm text-gray-500">Standaard is 2 personen per locatie</p>
                </div>

                <!-- Hidden fields for coordinates -->
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                <input type="hidden" name="address" id="address">

                <button type="submit" class="w-full bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    Locatie Toevoegen
                </button>
            </div>
        </form>

        <div class="mt-8">
            <h2 class="text-lg font-semibold mb-4">Uw Locaties</h2>
            <div class="space-y-4">
                @forelse($locations as $location)
                    <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors duration-200">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-medium text-lg">{{ $location->name }}</h3>
                                <p class="text-sm text-gray-600">{{ $location->street }} {{ $location->house_number }}</p>
                                <p class="text-sm text-gray-600">{{ $location->postal_code }} {{ $location->city }}</p>
                                <p class="text-sm text-gray-500 mt-1">Capaciteit: {{ $location->person_capacity }} personen</p>
                            </div>
                            <form action="{{ route('route-optimizer.destroy', $location) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">
                                    Verwijderen
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">Nog geen locaties toegevoegd.</p>
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
    });

    document.getElementById('full_address').addEventListener('blur', async function() {
        const fullAddress = this.value;
        if (!fullAddress) return;
        
        try {
            // Use Nominatim OpenStreetMap API for geocoding
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(fullAddress)}&countrycodes=nl`);
            const data = await response.json();
            
            if (data && data.length > 0) {
                // Set the coordinates
                document.getElementById('latitude').value = data[0].lat;
                document.getElementById('longitude').value = data[0].lon;
                document.getElementById('address').value = fullAddress;
                
                // Parse the address components
                const addressParts = data[0].display_name.split(', ');
                
                // Extract city (usually the second-to-last part before postal code)
                const cityIndex = addressParts.findIndex(part => /^\d{4}\s*[A-Z]{2}$/.test(part)) - 1;
                const city = cityIndex >= 0 ? addressParts[cityIndex] : '';
                document.getElementById('city').value = city;
                
                // Extract postal code
                const postalCodeMatch = addressParts.find(part => /^\d{4}\s*[A-Z]{2}$/.test(part));
                document.getElementById('postal_code').value = postalCodeMatch || '';
                
                // Extract street and house number from the first part
                const streetPart = addressParts[0] || '';
                
                // Dutch address format: "Streetname 123" or "Streetname 123A"
                const streetMatch = streetPart.match(/^(.+?)\s+(\d+\w*)$/);
                
                if (streetMatch) {
                    // Clean up the street name (remove any trailing spaces or special characters)
                    const streetName = streetMatch[1].trim().replace(/\s+/g, ' ');
                    const houseNumber = streetMatch[2].trim();
                    
                    document.getElementById('street').value = streetName;
                    document.getElementById('house_number').value = houseNumber;
                } else {
                    // If we can't parse it properly, try to extract just the number
                    const numberMatch = streetPart.match(/\d+\w*$/);
                    if (numberMatch) {
                        const streetName = streetPart.substring(0, numberMatch.index).trim();
                        const houseNumber = numberMatch[0];
                        document.getElementById('street').value = streetName;
                        document.getElementById('house_number').value = houseNumber;
                    } else {
                        // If no number found, use the whole string as street name
                        document.getElementById('street').value = streetPart;
                        document.getElementById('house_number').value = '';
                    }
                }
            } else {
                alert('Adres niet gevonden. Controleer het adres en probeer het opnieuw.');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Fout bij het opzoeken van het adres. Probeer het opnieuw.');
        }
    });

    document.getElementById('locationForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // If the form is submitted without the address being looked up first,
        // we need to do the lookup now
        if (!document.getElementById('latitude').value) {
            const fullAddress = document.getElementById('full_address').value;
            if (!fullAddress) {
                alert('Voer een geldig adres in');
                return;
            }
            
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(fullAddress)}&countrycodes=nl`);
                const data = await response.json();
                
                if (data && data.length > 0) {
                    document.getElementById('latitude').value = data[0].lat;
                    document.getElementById('longitude').value = data[0].lon;
                    document.getElementById('address').value = fullAddress;
                    
                    // Parse the address components
                    const addressParts = data[0].display_name.split(', ');
                    
                    // Extract city (usually the second-to-last part before postal code)
                    const cityIndex = addressParts.findIndex(part => /^\d{4}\s*[A-Z]{2}$/.test(part)) - 1;
                    const city = cityIndex >= 0 ? addressParts[cityIndex] : '';
                    document.getElementById('city').value = city;
                    
                    // Extract postal code
                    const postalCodeMatch = addressParts.find(part => /^\d{4}\s*[A-Z]{2}$/.test(part));
                    document.getElementById('postal_code').value = postalCodeMatch || '';
                    
                    // Extract street and house number from the first part
                    const streetPart = addressParts[0] || '';
                    
                    // Dutch address format: "Streetname 123" or "Streetname 123A"
                    const streetMatch = streetPart.match(/^(.+?)\s+(\d+\w*)$/);
                    
                    if (streetMatch) {
                        // Clean up the street name (remove any trailing spaces or special characters)
                        const streetName = streetMatch[1].trim().replace(/\s+/g, ' ');
                        const houseNumber = streetMatch[2].trim();
                        
                        document.getElementById('street').value = streetName;
                        document.getElementById('house_number').value = houseNumber;
                    } else {
                        // If we can't parse it properly, try to extract just the number
                        const numberMatch = streetPart.match(/\d+\w*$/);
                        if (numberMatch) {
                            const streetName = streetPart.substring(0, numberMatch.index).trim();
                            const houseNumber = numberMatch[0];
                            document.getElementById('street').value = streetName;
                            document.getElementById('house_number').value = houseNumber;
                        } else {
                            // If no number found, use the whole string as street name
                            document.getElementById('street').value = streetPart;
                            document.getElementById('house_number').value = '';
                        }
                    }
                    
                    // Submit the form
                    this.submit();
                } else {
                    alert('Adres niet gevonden. Controleer het adres en probeer het opnieuw.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Fout bij het opzoeken van het adres. Probeer het opnieuw.');
            }
        } else {
            // If we already have coordinates, submit the form
            this.submit();
        }
    });
</script>
@endpush 