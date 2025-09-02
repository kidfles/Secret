@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Date Selection Header --}}
    @if(isset($selectedDate))
    <div class="mb-6 bg-white shadow-lg rounded-lg p-4 border-l-4 border-blue-500">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-calendar-day mr-2 text-blue-500"></i>
                    {{-- Use the dynamically calculated date from controller --}}
                    Locaties voor {{ $formattedDate }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    U ziet locaties die voor deze datum zijn ingepland en ongebruikte locaties
                </p>
                
                {{-- Hidden developer debug info (only visible in source) --}}
                <!-- Debug: selectedDate = {{ $selectedDate }}, formattedDate = {{ $formattedDate }} -->
                @if(isset($rawSelectedDate))
                <!-- Debug: rawSelectedDate = {{ $rawSelectedDate }} -->
                @endif
            </div>
            <a href="{{ route('day-planner.index') }}" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-4 py-2 rounded-md flex items-center">
                <i class="fas fa-calendar-alt mr-2"></i>
                Andere datum kiezen
            </a>
        </div>
    </div>
    @else
    <div class="mb-6 bg-white shadow-lg rounded-lg p-4 border-l-4 border-yellow-500">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-exclamation-triangle mr-2 text-yellow-500"></i>
                    Geen datum geselecteerd
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Alle locaties worden getoond. Kies een datum om gefilterde locaties te zien.
                </p>
            </div>
            <a href="{{ route('day-planner.index') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md flex items-center">
                <i class="fas fa-calendar-alt mr-2"></i>
                Selecteer een datum
            </a>
        </div>
    </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-md">
            {{ session('error') }}
        </div>
    @endif
    
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded-md">
            {{ session('success') }}
        </div>
    @endif
    
    @if(session('info'))
        <div class="mb-4 p-4 bg-blue-100 border-l-4 border-blue-500 text-blue-700 rounded-md">
            {{ session('info') }}
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
                        <input type="text" id="full_address" name="full_address" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 bg-gray-50 py-3" placeholder="Voer volledig adres in (bijv. Hoofdstraat 123, Amsterdam)" required>
                        <div id="address-suggestions" class="absolute z-50 w-full mt-1 bg-white rounded-md shadow-lg hidden max-h-60 overflow-y-auto border border-gray-200">
                            <!-- Suggestions will be populated here -->
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-500 italic">Voer het volledige adres in en wij vullen het voor u in</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="street" class="block text-sm font-semibold text-gray-700 mb-1">Straatnaam</label>
                        <input type="text" name="street" id="street" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm text-gray-600 py-3" required>
                    </div>

                    <div>
                        <label for="house_number" class="block text-sm font-semibold text-gray-700 mb-1">Huisnummer</label>
                        <input type="text" name="house_number" id="house_number" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm text-gray-600 py-3" required>
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

                <div class="mb-4">
                    <label for="tegels" class="block text-sm font-medium text-gray-700">Aantal tegels</label>
                    <input type="number" name="tegels" id="tegels" min="0" 
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                
                <div class="mb-4">
                    <label for="tegels_type" class="block text-sm font-medium text-gray-700">Type tegels</label>
                    <select name="tegels_type" id="tegels_type" 
                            class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        <option value="">Selecteer type</option>
                        <option value="pix25">Pix 25</option>
                        <option value="pix100">Pix 100</option>
                        <option value="vlakled">Vlakled</option>
                        <option value="patroon">Patroon</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="completion_minutes" class="block text-sm font-medium text-gray-700">Benodige tijd (minuten)</label>
                    <input type="number" name="completion_minutes" id="completion_minutes" min="0" 
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>

                <div class="mb-4">
                    <label for="date" class="block text-sm font-medium text-gray-700">Datum (optioneel)</label>
                    <input type="date" name="date" id="date" value="{{ session('selected_date') }}"
                           class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    <p class="mt-1 text-sm text-gray-500">Indien ingevuld, wordt de locatie alleen voor deze datum getoond</p>
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
                                    
                                    @if($location->date)
                                        <p class="text-sm text-gray-500 mt-1">
                                            <span class="bg-purple-100 text-purple-800 px-2 py-0.5 rounded text-xs">
                                                Datum: {{ \Carbon\Carbon::parse($location->date)->format('d-m-Y') }}
                                            </span>
                                        </p>
                                    @endif
                                </div>
                                <div class="flex space-x-3">
                                    <a href="{{ route('route-optimizer.edit', $location->id) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                        <i class="fas fa-edit mr-1"></i> Bewerken
                                    </a>
                                    <form action="{{ route('route-optimizer.destroy', $location) }}" method="POST" onsubmit="return confirm('Weet u zeker dat u deze locatie wilt verwijderen?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 font-medium">
                                            <i class="fas fa-trash mr-1"></i> Verwijderen
                                        </button>
                                    </form>
                                </div>
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

 
@endsection

@push('scripts')
<script>
    window.__LOCATIONS__ = @json($locations);
</script>
<script src="{{ asset('js/route-optimizer.js') }}" defer></script>
@endpush