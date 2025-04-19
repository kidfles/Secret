@extends('layouts.app')

@section('content')
<div class="max-w-[1920px] mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Nieuwe dagplanning maken</h1>
        <a href="{{ route('day-planner.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:bg-gray-200 active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            Terug naar dagplanningen
        </a>
    </div>

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

    <form action="{{ route('day-planner.store') }}" method="POST" id="day-planner-form">
        @csrf
        
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Algemene informatie</h2>
                
                <div class="mb-6">
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Datum</label>
                    <input type="date" name="date" id="date" value="{{ old('date', $today) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500" required>
                    @error('date')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Routes</h2>
                    <button type="button" id="add-route" class="inline-flex items-center px-3 py-1.5 bg-green-600 border border-transparent rounded-md font-medium text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Route toevoegen
                    </button>
                </div>
                
                <div id="routes-container" class="space-y-4">
                    <!-- Route template will be added here dynamically -->
                </div>
                
                @error('route_names')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                
                @error('route_names.*')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Locaties</h2>
                    <div class="flex space-x-2">
                        <button type="button" id="add-existing-location" class="inline-flex items-center px-3 py-1.5 bg-blue-600 border border-transparent rounded-md font-medium text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                            </svg>
                            Bestaande locatie
                        </button>
                        <button type="button" id="add-new-location" class="inline-flex items-center px-3 py-1.5 bg-green-600 border border-transparent rounded-md font-medium text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            Nieuwe locatie
                        </button>
                    </div>
                </div>
                
                <div id="existing-locations-container" class="mb-4">
                    <div class="bg-gray-50 p-4 mb-4 rounded-md">
                        <h3 class="text-md font-medium text-gray-700 mb-3">Bestaande locaties</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3" id="existing-locations-grid">
                            @foreach($existingLocations as $location)
                                <div class="bg-white border border-gray-200 rounded-md p-3 flex items-start">
                                    <input type="checkbox" name="location_ids[]" value="{{ $location->id }}" id="location-{{ $location->id }}" class="mt-1 h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                    <label for="location-{{ $location->id }}" class="ml-2 block">
                                        <span class="text-sm font-medium text-gray-900">{{ $location->name }}</span>
                                        <span class="block text-xs text-gray-500">{{ $location->address }}</span>
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            @if($location->tegels)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ $location->tegels }} tegels
                                                </span>
                                            @endif
                                            
                                            @if($location->begin_time && $location->end_time)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                    {{ \Carbon\Carbon::parse($location->begin_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($location->end_time)->format('H:i') }}
                                                </span>
                                            @endif
                                            
                                            <select name="route_assignments[{{ $location->id }}]" class="mt-1 block w-full py-1 px-2 text-xs rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 route-assignment-select" disabled>
                                                <option value="">-- Selecteer route --</option>
                                            </select>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                
                <div id="new-locations-container" class="space-y-4">
                    <!-- New location template will be added here dynamically -->
                </div>
            </div>
            
            <div class="p-6 bg-gray-50 flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Dagplanning opslaan
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Templates -->
<template id="route-template">
    <div class="route-item bg-gray-50 p-4 rounded-md relative">
        <button type="button" class="absolute top-3 right-3 text-gray-400 hover:text-red-500 remove-route">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Route naam</label>
                <input type="text" name="route_names[]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 route-name" placeholder="Route naam" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Start tijd (optioneel)</label>
                <input type="time" name="start_times[]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
            </div>
        </div>
    </div>
</template>

<template id="new-location-template">
    <div class="new-location-item bg-gray-50 p-4 rounded-md relative">
        <button type="button" class="absolute top-3 right-3 text-gray-400 hover:text-red-500 remove-new-location">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
        
        <h3 class="text-md font-medium text-gray-700 mb-3 flex items-center">
            <span class="h-5 w-5 bg-green-100 text-green-700 rounded-full inline-flex items-center justify-center mr-2 text-xs">+</span>
            Nieuwe locatie
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Naam</label>
                <input type="text" name="new_locations[__index__][name]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 location-name" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Adres</label>
                <input type="text" name="new_locations[__index__][address]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 location-address" required>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Breedtegraad</label>
                <input type="number" step="0.0000001" name="new_locations[__index__][latitude]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 location-latitude" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Lengtegraad</label>
                <input type="number" step="0.0000001" name="new_locations[__index__][longitude]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 location-longitude" required>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Aantal tegels</label>
                <input type="number" min="0" name="new_locations[__index__][tegels]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Begin tijd</label>
                <input type="time" name="new_locations[__index__][begin_time]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Eind tijd</label>
                <input type="time" name="new_locations[__index__][end_time]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tijd nodig (in minuten)</label>
                <input type="number" min="0" name="new_locations[__index__][completion_minutes]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Toewijzen aan route</label>
                <select name="route_assignments[new_location___index__]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 new-location-route-select">
                    <option value="">-- Selecteer route --</option>
                </select>
            </div>
        </div>
    </div>
</template>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let routeCounter = 0;
        let newLocationCounter = 0;
        
        // Add route button
        document.getElementById('add-route').addEventListener('click', function() {
            addRoute();
        });
        
        // Add existing location button
        document.getElementById('add-existing-location').addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('input[name="location_ids[]"]');
            checkboxes.forEach(function(checkbox) {
                const select = checkbox.closest('.bg-white').querySelector('select');
                if (checkbox.checked) {
                    select.disabled = false;
                    updateRouteSelects();
                } else {
                    select.disabled = true;
                }
            });
        });
        
        // Handle existing location checkboxes
        document.querySelectorAll('input[name="location_ids[]"]').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const select = this.closest('.bg-white').querySelector('select');
                if (this.checked) {
                    select.disabled = false;
                    updateRouteSelects();
                } else {
                    select.disabled = true;
                }
            });
        });
        
        // Add new location button
        document.getElementById('add-new-location').addEventListener('click', function() {
            addNewLocation();
        });
        
        // Form submission validation
        document.getElementById('day-planner-form').addEventListener('submit', function(e) {
            const routes = document.querySelectorAll('.route-item');
            
            if (routes.length === 0) {
                e.preventDefault();
                alert('Voeg minimaal één route toe.');
                return false;
            }
            
            // Check if at least one location is assigned
            const existingLocations = document.querySelectorAll('input[name="location_ids[]"]:checked');
            const newLocations = document.querySelectorAll('.new-location-item');
            
            if (existingLocations.length === 0 && newLocations.length === 0) {
                e.preventDefault();
                alert('Voeg minimaal één locatie toe.');
                return false;
            }
            
            // Check route assignments
            let hasUnassignedLocation = false;
            
            existingLocations.forEach(function(checkbox) {
                const select = checkbox.closest('.bg-white').querySelector('select');
                if (!select.value) {
                    hasUnassignedLocation = true;
                }
            });
            
            document.querySelectorAll('.new-location-route-select').forEach(function(select) {
                if (!select.value) {
                    hasUnassignedLocation = true;
                }
            });
            
            if (hasUnassignedLocation) {
                e.preventDefault();
                alert('Wijs elke locatie toe aan een route.');
                return false;
            }
        });
        
        // Function to add a new route
        function addRoute() {
            const routesContainer = document.getElementById('routes-container');
            const template = document.getElementById('route-template').content.cloneNode(true);
            
            // Set default route name
            template.querySelector('.route-name').value = 'Route ' + (routeCounter + 1);
            
            const routeElement = document.createElement('div');
            routeElement.appendChild(template);
            routeElement.classList.add('route-wrapper');
            routeElement.dataset.routeIndex = routeCounter;
            
            routesContainer.appendChild(routeElement);
            
            // Remove route button
            routeElement.querySelector('.remove-route').addEventListener('click', function() {
                routeElement.remove();
                updateRouteSelects();
            });
            
            routeCounter++;
            updateRouteSelects();
            
            return routeElement;
        }
        
        // Function to add a new location
        function addNewLocation() {
            const locationsContainer = document.getElementById('new-locations-container');
            const template = document.getElementById('new-location-template').content.cloneNode(true);
            
            // Replace template index with counter
            template.querySelectorAll('input, select').forEach(function(element) {
                if (element.name) {
                    element.name = element.name.replace('__index__', newLocationCounter);
                }
            });
            
            const locationElement = document.createElement('div');
            locationElement.appendChild(template);
            locationElement.classList.add('new-location-wrapper');
            
            // Set data-new-location-id attribute for route select
            locationElement.dataset.newLocationId = 'new_location_' + newLocationCounter;
            
            locationsContainer.appendChild(locationElement);
            
            // Remove new location button
            locationElement.querySelector('.remove-new-location').addEventListener('click', function() {
                locationElement.remove();
            });
            
            newLocationCounter++;
            updateRouteSelects();
            
            return locationElement;
        }
        
        // Function to update route selects
        function updateRouteSelects() {
            const routeOptions = [];
            const routeItems = document.querySelectorAll('.route-wrapper');
            
            routeItems.forEach(function(routeItem) {
                const routeIndex = routeItem.dataset.routeIndex;
                const routeName = routeItem.querySelector('.route-name').value;
                
                routeOptions.push({
                    value: routeIndex,
                    text: routeName
                });
            });
            
            // Update existing location selects
            document.querySelectorAll('.route-assignment-select').forEach(function(select) {
                const currentValue = select.value;
                select.innerHTML = '<option value="">-- Selecteer route --</option>';
                
                routeOptions.forEach(function(option) {
                    const optionElement = document.createElement('option');
                    optionElement.value = option.value;
                    optionElement.textContent = option.text;
                    
                    if (option.value === currentValue) {
                        optionElement.selected = true;
                    }
                    
                    select.appendChild(optionElement);
                });
            });
            
            // Update new location selects
            document.querySelectorAll('.new-location-route-select').forEach(function(select) {
                const currentValue = select.value;
                select.innerHTML = '<option value="">-- Selecteer route --</option>';
                
                routeOptions.forEach(function(option) {
                    const optionElement = document.createElement('option');
                    optionElement.value = option.value;
                    optionElement.textContent = option.text;
                    
                    if (option.value === currentValue) {
                        optionElement.selected = true;
                    }
                    
                    select.appendChild(optionElement);
                });
            });
        }
        
        // Add initial route
        addRoute();
    });
</script>
@endsection 