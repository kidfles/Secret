@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
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
                    <strong class="font-semibold">Opmerking:</strong> Alle routes beginnen in Nederasselt, Nederland. U hoeft deze locatie niet handmatig toe te voegen.
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
                    <div class="relative">
                        <input type="text" id="full_address" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 bg-gray-50 py-3" placeholder="Voer volledig adres in (bijv. Hoofdstraat 123, Amsterdam)" required>
                        <div id="address-suggestions" class="absolute z-10 w-full mt-1 bg-white rounded-md shadow-lg hidden max-h-60 overflow-y-auto">
                            <!-- Suggestions will be populated here -->
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-500 italic">Voer het volledige adres in en wij vullen het voor u in</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="street" class="block text-sm font-semibold text-gray-700 mb-1">Straatnaam (optioneel)</label>
                        <input type="text" name="street" id="street" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm text-gray-600 py-3">
                    </div>

                    <div>
                        <label for="house_number" class="block text-sm font-semibold text-gray-700 mb-1">Huisnummer (optioneel)</label>
                        <input type="text" name="house_number" id="house_number" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm text-gray-600 py-3">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="city" class="block text-sm font-semibold text-gray-700 mb-1">Stad</label>
                        <input type="text" name="city" id="city" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm text-gray-600 py-3" required>
                    </div>

                    <div>
                        <label for="postal_code" class="block text-sm font-semibold text-gray-700 mb-1">Postcode</label>
                        <input type="text" name="postal_code" id="postal_code" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm text-gray-600 py-3" required>
                    </div>
                </div>

                <div>
                    <label for="person_capacity" class="block text-sm font-semibold text-gray-700 mb-1">Aantal Personen</label>
                    <input type="number" name="person_capacity" id="person_capacity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 bg-gray-50 py-3" value="2" min="1">
                    <p class="mt-2 text-sm text-gray-500 italic">Standaard is 2 personen per locatie</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="tegels" class="block text-sm font-semibold text-gray-700 mb-1">Aantal Tegels</label>
                        <input type="number" name="tegels" id="tegels" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 bg-gray-50 py-3" value="0" min="0" onchange="calculateCompletionTime()">
                        <p class="mt-2 text-sm text-gray-500 italic">Vul het aantal tegels in (0-100)</p>
                    </div>

                    <div>
                        <label for="tegels_type" class="block text-sm font-semibold text-gray-700 mb-1">Type Tegels</label>
                        <select name="tegels_type" id="tegels_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 bg-gray-50 py-3">
                            <option value="">Selecteer type</option>
                            <option value="pix100">PIX 100</option>
                            <option value="pix25">PIX 25</option>
                            <option value="vlakled">Vlak LED</option>
                            <option value="patroon">Patroon</option>
                        </select>
                        <p class="mt-2 text-sm text-gray-500 italic">Selecteer het type tegels</p>
                    </div>
                </div>

                <div>
                    <label for="completion_minutes" class="block text-sm font-semibold text-gray-700 mb-1">Benodigde Tijd (min)</label>
                    <input type="number" name="completion_minutes" id="completion_minutes" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 bg-gray-50 py-3" placeholder="Automatisch berekend" readonly>
                    <p class="mt-2 text-sm text-gray-500 italic">Wordt automatisch berekend</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="begin_time" class="block text-sm font-semibold text-gray-700 mb-1">Vroegste Aankomsttijd</label>
                        <input type="time" name="begin_time" id="begin_time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 bg-gray-50 py-3">
                        <p class="mt-2 text-sm text-gray-500 italic">Vroegste tijd dat deze locatie bezocht kan worden</p>
                    </div>

                    <div>
                        <label for="end_time" class="block text-sm font-semibold text-gray-700 mb-1">Uiterste Eindtijd</label>
                        <input type="time" name="end_time" id="end_time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 bg-gray-50 py-3">
                        <p class="mt-2 text-sm text-gray-500 italic">Uiterste tijd dat deze locatie afgerond moet zijn</p>
                    </div>
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
                                    @if($location->person_capacity)
                                        <p class="text-sm text-gray-500">
                                            <span class="bg-red-100 text-red-800 px-2 py-0.5 rounded text-xs">
                                                {{ $location->person_capacity }} personen
                                            </span>
                                        </p>
                                    @endif
                                    
                                    @if($location->tegels > 0)
                                        <p class="text-sm text-gray-500">
                                            <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs">
                                                {{ $location->tegels }} tegels 
                                                @if($location->tegels_type)
                                                    ({{ ucfirst($location->tegels_type) }})
                                                @endif
                                                - {{ $location->completion_time }} min
                                            </span>
                                        </p>
                                    @endif
                                    
                                    @if($location->begin_time || $location->end_time)
                                        <div class="mt-1 flex flex-wrap gap-2">
                                            @if($location->begin_time)
                                                <span class="bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded text-xs">
                                                    Vanaf: {{ \Carbon\Carbon::parse($location->begin_time)->format('H:i') }}
                                                </span>
                                            @endif
                                            @if($location->end_time)
                                                <span class="bg-red-100 text-red-800 px-2 py-0.5 rounded text-xs">
                                                    Tot: {{ \Carbon\Carbon::parse($location->end_time)->format('H:i') }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                    
                                    @if($location->completion_minutes)
                                        <p class="text-sm text-gray-500 mt-1">
                                            <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs">
                                                Benodigd: {{ $location->completion_minutes }} min
                                            </span>
                                        </p>
                                    @endif
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
        // Initialize the map
        var map = L.map('map').setView([51.9225, 5.5808], 10);
        window.map = map;
        
        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // Function to calculate completion time based on number of tegels
        window.calculateCompletionTime = function() {
            const tegelsCount = parseInt(document.getElementById('tegels').value) || 0;
            const baseDuration = 40; // Base 40 minutes
            const additionalTime = Math.ceil(tegelsCount * 1.5); // 1.5 minutes per tegel, rounded up
            const completionTime = baseDuration + additionalTime;
            
            document.getElementById('completion_minutes').value = completionTime;
            
            // Also update the label to show the calculation
            const completionMinutesField = document.getElementById('completion_minutes');
            completionMinutesField.setAttribute('placeholder', `${baseDuration} + (${tegelsCount} × 1.5) = ${completionTime}`);
            
            // Enable manual override if needed
            completionMinutesField.readOnly = tegelsCount > 0 ? false : true;
            
            return completionTime;
        };
        
        // Calculate initial completion time
        calculateCompletionTime();
        
        // Add event listener to recalculate when tegels input changes
        document.getElementById('tegels').addEventListener('input', calculateCompletionTime);
        
        // Create a marker cluster group
        var markerCluster = L.markerClusterGroup();

        // Add markers for existing locations
        const locations = @json($locations);
        const markersList = [];

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
            markersList.push(marker);
        });

        if (markersList.length > 0) {
            const bounds = L.latLngBounds(markersList.map(marker => marker.getLatLng()));
            map.fitBounds(bounds);
        }

        // Address suggestions functionality
        const fullAddressInput = document.getElementById('full_address');
        const suggestionsContainer = document.getElementById('address-suggestions');

        // Function to fetch and display address suggestions
        function fetchAddressSuggestions(query) {
            if (!query || query.length < 3) {
                suggestionsContainer.classList.add('hidden');
                return;
            }

            // Show loading state
            suggestionsContainer.innerHTML = '<div class="p-2 text-gray-500">Zoeken...</div>';
            suggestionsContainer.classList.remove('hidden');

            // Add User-Agent header to comply with Nominatim usage policy
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)},Netherlands&countrycodes=nl&limit=5`, {
                headers: {
                    'User-Agent': 'RouteOptimizer/1.0'
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    suggestionsContainer.innerHTML = '';
                    
                    if (data && data.length > 0) {
                        data.forEach(result => {
                            const div = document.createElement('div');
                            div.className = 'p-2 hover:bg-gray-100 cursor-pointer border-b border-gray-200';
                            div.textContent = result.display_name;
                            div.addEventListener('click', () => {
                                fullAddressInput.value = result.display_name;
                                suggestionsContainer.classList.add('hidden');
                                parseAddress(result);
                            });
                            suggestionsContainer.appendChild(div);
                        });
                    } else {
                        const div = document.createElement('div');
                        div.className = 'p-2 text-gray-500';
                        div.textContent = 'Geen adressen gevonden';
                        suggestionsContainer.appendChild(div);
                    }
                })
                .catch(error => {
                    console.error('Error fetching address suggestions:', error);
                    suggestionsContainer.innerHTML = '<div class="p-2 text-gray-500">Probeer het opnieuw</div>';
                    // Hide suggestions after 3 seconds
                    setTimeout(() => {
                        suggestionsContainer.classList.add('hidden');
                    }, 3000);
                });
        }

        // Add event listeners for address input with debounce
        let debounceTimer;
        fullAddressInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchAddressSuggestions(this.value);
            }, 500); // Increased debounce time to 500ms
        });

        // Show suggestions on focus if there's text
        fullAddressInput.addEventListener('focus', function() {
            if (this.value.length >= 3) {
                fetchAddressSuggestions(this.value);
            }
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!fullAddressInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                suggestionsContainer.classList.add('hidden');
            }
        });

        // Prevent form submission when selecting an address
        suggestionsContainer.addEventListener('click', function(e) {
            e.preventDefault();
        });

        // Function to parse address from Nominatim result
        function parseAddress(result) {
            const address = result.display_name;
            document.getElementById('address').value = address;
            
            // Split the address into parts
            const parts = address.split(',').map(part => part.trim());
            
            // Extract postal code (usually in format "1234 AB" or "AB 1234")
            const postalCodeMatch = address.match(/\b\d{4}\s*[A-Z]{2}\b|\b[A-Z]{2}\s*\d{4}\b/);
            if (postalCodeMatch) {
                document.getElementById('postal_code').value = postalCodeMatch[0].replace(/\s+/g, '');
            }
            
            // Extract city (usually the second-to-last part before the postal code)
            let city = '';
            for (let i = parts.length - 1; i >= 0; i--) {
                if (!/^\d+$/.test(parts[i]) && !/^\d{4}\s*[A-Z]{2}$/.test(parts[i])) {
                    city = parts[i];
                    break;
                }
            }
            document.getElementById('city').value = city;
            
            // Extract street and house number
            // For addresses like "1102, Aalsburg, Wijchen", the first part is the house number
            const firstPart = parts[0];
            if (/^\d+$/.test(firstPart)) {
                // If first part is just a number, it's the house number
                document.getElementById('house_number').value = firstPart;
                // The street is the second part
                document.getElementById('street').value = parts[1] || '';
            } else {
                // Try to extract house number from the first part
                const streetMatch = firstPart.match(/^(.*?)\s*(\d+\w*)$/);
                if (streetMatch) {
                    document.getElementById('street').value = streetMatch[1].trim();
                    document.getElementById('house_number').value = streetMatch[2];
                } else {
                    document.getElementById('street').value = firstPart;
                    document.getElementById('house_number').value = '';
                }
            }
            
            // Set coordinates
            document.getElementById('latitude').value = result.lat;
            document.getElementById('longitude').value = result.lon;
            
            // Update map marker
            updateMapMarker(result.lat, result.lon, address);
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

            // Make sure required fields are filled
            const requiredFields = ['name', 'city', 'postal_code'];
            for (const field of requiredFields) {
                const input = document.getElementById(field);
                if (!input.value.trim()) {
                    e.preventDefault();
                    alert(`Vul het veld "${input.previousElementSibling.textContent.replace(' (optioneel)', '')}" in`);
                    input.focus();
                    return;
                }
            }
        });
    });
</script>
@endsection 