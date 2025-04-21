@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6 bg-white shadow-lg rounded-lg p-4 border-l-4 border-blue-500">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-edit mr-2 text-blue-500"></i>
                    Locatie Bewerken: {{ $location->name }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Werk de gegevens van deze locatie bij
                </p>
            </div>
            <a href="{{ route('route-optimizer.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-md flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Terug naar locaties
            </a>
        </div>
    </div>

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-md">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white shadow-lg rounded-lg p-6">
        <form action="{{ route('route-optimizer.update', $location->id) }}" method="POST" id="locationForm" class="space-y-6">
            @csrf
            @method('PUT')
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">Locatienaam</label>
                <input type="text" name="name" id="name" value="{{ $location->name }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 bg-gray-50 py-3" required>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="street" class="block text-sm font-semibold text-gray-700 mb-1">Straatnaam</label>
                    <input type="text" name="street" id="street" value="{{ $location->street }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 bg-gray-50 py-3" required>
                </div>

                <div>
                    <label for="house_number" class="block text-sm font-semibold text-gray-700 mb-1">Huisnummer</label>
                    <input type="text" name="house_number" id="house_number" value="{{ $location->house_number }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 bg-gray-50 py-3" required>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="city" class="block text-sm font-semibold text-gray-700 mb-1">Stad</label>
                    <input type="text" name="city" id="city" value="{{ $location->city }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 bg-gray-50 py-3" required>
                </div>

                <div>
                    <label for="postal_code" class="block text-sm font-semibold text-gray-700 mb-1">Postcode</label>
                    <input type="text" name="postal_code" id="postal_code" value="{{ $location->postal_code }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 bg-gray-50 py-3" required>
                </div>
            </div>

            <div>
                <label for="person_capacity" class="block text-sm font-semibold text-gray-700 mb-1">Aantal Personen</label>
                <input type="number" name="person_capacity" id="person_capacity" value="{{ $location->person_capacity }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 bg-gray-50 py-3" min="1" required>
            </div>

            <div class="mb-4">
                <label for="tegels" class="block text-sm font-medium text-gray-700">Aantal tegels</label>
                <input type="number" name="tegels" id="tegels" min="0" value="{{ $location->tegels }}"
                        class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>
            
            <div class="mb-4">
                <label for="tegels_type" class="block text-sm font-medium text-gray-700">Type tegels</label>
                <select name="tegels_type" id="tegels_type" 
                        class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    <option value="">Selecteer type</option>
                    <option value="pix25" {{ $location->tegels_type == 'pix25' ? 'selected' : '' }}>Pix 25</option>
                    <option value="pix100" {{ $location->tegels_type == 'pix100' ? 'selected' : '' }}>Pix 100</option>
                    <option value="vlakled" {{ $location->tegels_type == 'vlakled' ? 'selected' : '' }}>Vlakled</option>
                    <option value="patroon" {{ $location->tegels_type == 'patroon' ? 'selected' : '' }}>Patroon</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="completion_minutes" class="block text-sm font-medium text-gray-700">Benodige tijd (minuten)</label>
                <input type="number" name="completion_minutes" id="completion_minutes" min="0" value="{{ $location->completion_minutes }}"
                        class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>

            <div class="mb-4">
                <label for="date" class="block text-sm font-medium text-gray-700">Datum (optioneel)</label>
                <input type="date" name="date" id="date" value="{{ $location->date ? $location->date->format('Y-m-d') : '' }}"
                        class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                <p class="mt-1 text-sm text-gray-500">Indien ingevuld, wordt de locatie alleen voor deze datum getoond</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="begin_time" class="block text-sm font-semibold text-gray-700 mb-1">Vroegste Aankomsttijd</label>
                    <input type="time" name="begin_time" id="begin_time" value="{{ $location->begin_time ? $location->begin_time->format('H:i') : '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 bg-gray-50 py-3">
                    <p class="mt-2 text-sm text-gray-500 italic">Vroegste tijd dat deze locatie bezocht kan worden</p>
                </div>

                <div>
                    <label for="end_time" class="block text-sm font-semibold text-gray-700 mb-1">Uiterste Eindtijd</label>
                    <input type="time" name="end_time" id="end_time" value="{{ $location->end_time ? $location->end_time->format('H:i') : '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 bg-gray-50 py-3">
                    <p class="mt-2 text-sm text-gray-500 italic">Uiterste tijd dat deze locatie afgerond moet zijn</p>
                </div>
            </div>

            <!-- Hidden fields for coordinates and address -->
            <input type="hidden" name="latitude" id="latitude" value="{{ $location->latitude }}">
            <input type="hidden" name="longitude" id="longitude" value="{{ $location->longitude }}">
            <input type="hidden" name="address" id="address" value="{{ $location->address }}">

            <div class="flex space-x-4">
                <button type="submit" class="flex-1 bg-blue-600 text-white py-3 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-medium transition-colors duration-200">
                    <i class="fas fa-save mr-2"></i> Wijzigingen Opslaan
                </button>
                <a href="{{ route('route-optimizer.index') }}" class="flex-1 bg-gray-200 text-gray-800 py-3 px-4 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 font-medium transition-colors duration-200 text-center">
                    <i class="fas fa-times mr-2"></i> Annuleren
                </a>
            </div>
        </form>
    </div>

    <div class="mt-8">
        <div id="map" class="h-[400px] rounded-lg shadow-md"></div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize map
        var map = L.map('map').setView([{{ $location->latitude }}, {{ $location->longitude }}], 15);
        
        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // Add marker for the location
        var marker = L.marker([{{ $location->latitude }}, {{ $location->longitude }}])
            .addTo(map)
            .bindPopup('{{ $location->name }}<br>{{ $location->address }}')
            .openPopup();
        
        // Function to calculate completion time based on number of tegels
        function calculateCompletionTime() {
            const tegelsCount = parseInt(document.getElementById('tegels').value) || 0;
            
            // Only auto-calculate if no manual value has been set
            if (!document.getElementById('completion_minutes').dataset.manualValue) {
                const baseDuration = 40; // Base 40 minutes
                const additionalTime = Math.ceil(tegelsCount * 1.5); // 1.5 minutes per tegel, rounded up
                const completionTime = baseDuration + additionalTime;
                
                document.getElementById('completion_minutes').value = completionTime;
                document.getElementById('completion_minutes').placeholder = `${baseDuration} + (${tegelsCount} × 1.5) = ${completionTime}`;
            }
        }
        
        // Add event listener to recalculate when tegels input changes
        document.getElementById('tegels').addEventListener('input', calculateCompletionTime);
        
        // Flag completion_minutes as manually set when user changes it
        document.getElementById('completion_minutes').addEventListener('input', function() {
            this.dataset.manualValue = 'true';
        });
    });
</script>
@endsection 