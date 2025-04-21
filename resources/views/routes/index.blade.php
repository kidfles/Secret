@extends('layouts.app')

@section('content')
{{-- External CSS files with improved loading --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<link rel="preload" as="style" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" onload="this.onload=null;this.rel='stylesheet'"/>
<noscript><link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css"></noscript>
<link rel="stylesheet" href="{{ asset('css/routes.css') }}">

<div class="max-w-[1920px] mx-auto px-4 py-8">
  {{-- Date Selection Header --}}
  @if(isset($selectedDate))
  <div class="mb-6 bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">
          <i class="fas fa-calendar-day mr-2 text-blue-500"></i>
          Routes voor {{ $formattedDate }}
        </h2>
        <p class="text-sm text-gray-600 mt-1">
          Alleen routes gepland voor deze datum worden getoond
        </p>
      </div>
      <a href="{{ route('day-planner.index') }}" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-4 py-2 rounded-md flex items-center">
        <i class="fas fa-calendar-alt mr-2"></i>
        Andere datum kiezen
      </a>
    </div>
  </div>
  @else
  <div class="mb-6 bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">
          <i class="fas fa-exclamation-triangle mr-2 text-yellow-500"></i>
          Geen datum geselecteerd
        </h2>
        <p class="text-sm text-gray-600 mt-1">
          Alle routes worden getoond. Kies een datum om gefilterde routes te zien.
        </p>
      </div>
      <a href="{{ route('day-planner.index') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md flex items-center">
        <i class="fas fa-calendar-alt mr-2"></i>
        Selecteer een datum
      </a>
    </div>
  </div>
  @endif

  {{-- success / error alerts --}}
  @if(session('error'))
    <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-md">
      {{ session('error') }}
    </div>
  @endif

  {{-- Tile Distribution Summary --}}
  @if($routes->isNotEmpty())
    <div class="mb-4 p-4 bg-blue-50 border-l-4 border-blue-300 text-blue-800 rounded-md">
      <div class="flex justify-between items-start">
        <h3 class="font-medium">Verdeling tegels</h3>
        <div class="flex items-center space-x-2">
          <div class="w-6 h-6 rounded-full bg-blue-500 flex items-center justify-center text-white">
            <i class="fas fa-map-marker-alt text-xs"></i>
          </div>
          <div class="text-sm">
            <span class="font-medium">Broekstraat 68, Nederasselt</span>
          </div>
        </div>
      </div>
      <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-2">
        <div>
          <span class="text-sm text-gray-600">Totaal tegels:</span>
          <span class="block font-medium">{{ $totalTilesAll }}</span>
        </div>
        <div>
          <span class="text-sm text-gray-600">Gemiddeld per route:</span>
          <span class="block font-medium">{{ round($avgTiles, 1) }}</span>
        </div>
        <div>
          <span class="text-sm text-gray-600">Max verschil:</span>
          <span class="block font-medium">{{ $maxDiff }}</span>
        </div>
      </div>
    </div>
  @else
    <div class="mb-4 p-4 bg-blue-50 border-l-4 border-blue-300 text-blue-800 rounded-md">
      <div class="flex justify-between items-start">
        <h3 class="font-medium">Startlocatie</h3>
        <div class="flex items-center space-x-2">
          <div class="w-6 h-6 rounded-full bg-blue-500 flex items-center justify-center text-white">
            <i class="fas fa-map-marker-alt text-xs"></i>
          </div>
          <div class="text-sm">
            <span class="font-medium">Broekstraat 68, Nederasselt</span>
          </div>
        </div>
      </div>
    </div>
  @endif

  <div class="bg-white p-4 rounded shadow-md mb-6">
    <div class="flex flex-col md:flex-row md:justify-between">
      <h1 class="text-2xl font-bold mb-4">Routes</h1>
      <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
        <form action="{{ route('routes.generate') }}" method="POST" class="inline flex space-x-2">
          @csrf
          <div class="flex items-center">
            <label for="num_routes" class="mr-2 text-sm">Aantal routes:</label>
            <input type="number" name="num_routes" id="num_routes" min="1" value="3" class="w-16 h-full px-2 py-1 border border-gray-300 rounded">
          </div>
          <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
            Generate Route
          </button>
        </form>
        <button id="optimize-all-routes" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded">
          Optimize All Routes
        </button>
        <form action="{{ route('routes.deleteAll') }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete all routes?');">
          @csrf
          @method('DELETE')
          <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
            Delete All Routes
          </button>
        </form>
      </div>
    </div>
  </div>

  @if(session('success'))
    <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded-md">
      {{ session('success') }}
    </div>
  @endif

  <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
    <div class="lg:col-span-3 space-y-6">

      {{-- Routes Grid --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        @foreach($routes as $route)
        <div class="bg-white rounded-lg shadow p-6 md:p-7" data-route-id="{{ $route->id }}">
          {{-- Header --}}
          <div class="flex justify-between items-center mb-2">
            <div class="flex items-center gap-2 flex-1 min-w-0">
              <div class="w-3 h-3 rounded-full flex-shrink-0"
                   style="background-color: {{ $routeColors[$loop->index % count($routeColors)] }}"></div>
              <form action="{{ route('routes.update', $route) }}" method="POST" class="flex-1 min-w-0">
                @csrf @method('PUT')
                <input type="text" name="name" value="{{ $route->name }}" required
                       class="w-full px-2 py-1 text-lg font-semibold bg-transparent border-b border-gray-300 focus:border-blue-500 focus:outline-none"
                       onblur="if(this.value.trim()) this.form.submit()">
                @error('name')
                <span class="text-xs text-red-600">{{ $message }}</span>
                @enderror
              </form>
            </div>
            <div class="flex space-x-2 flex-shrink-0 ml-2">
              {{-- Recalculate button now with class and data-attribute --}}
              <button type="button"
                      class="js-recalc flex items-center space-x-1 text-blue-600 hover:text-blue-800 focus:outline-none"
                      data-route-id="{{ $route->id }}"
                      title="Herbereken route">
                <i class="fa-solid fa-sync"></i>
                <span class="hidden md:inline">Herbereken</span>
              </button>

              {{-- Delete --}}
              <form action="{{ route('routes.destroy', $route) }}" method="POST">
                @csrf @method('DELETE')
                <button onclick="return confirm('Weet je zeker?')"
                        class="text-red-600 hover:text-red-800 focus:outline-none"
                        title="Verwijder route">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </form>
            </div>
          </div>

          {{-- Warning banner --}}
          <div class="route-warning hidden mb-4 p-3 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 rounded">
            Let op: deze volgorde is niet de snelste route. Klik op "Herbereken" om opnieuw te optimaliseren.
          </div>

          {{-- Draggable list - lazy load for routes with many locations --}}
          <div class="route-locations space-y-2" id="route-{{ $route->id }}">
            @php $locationCount = count($route->locations); @endphp
            
            @if($locationCount > 20)
              {{-- Show only first 10 locations initially for large routes --}}
              @foreach($route->locations->take(10) as $location)
              <div class="location-item flex items-center space-x-4 p-4 bg-gray-50 rounded-lg"
                   data-location-id="{{ $location->id }}">
                <div class="w-8 h-8 rounded-full bg-gray-500 flex items-center justify-center text-white">
                  {{ $loop->iteration }}
                </div>
                <div class="flex-1">
                  <h3 class="font-medium">{{ $location->address }}</h3>
                  <p class="text-sm text-gray-600">{{ $location->city }}</p>
                  <div class="mt-1 flex flex-wrap gap-1">
                    @if($location->tegels > 0)
                      <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs">
                        {{ $location->tegels }} tegels
                      </span>
                    @elseif($location->tegels_count > 0)
                      <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs">
                        {{ $location->tegels_count }} {{ $location->tegels_type ?? 'tegels' }}
                      </span>
                    @endif

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

                    @if($location->completion_minutes)
                      <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs">
                        {{ $location->completion_minutes }} min
                      </span>
                    @endif
                  </div>
                </div>
              </div>
              @endforeach
              
              {{-- Show button to load remaining locations --}}
              <div class="load-more-container py-2 text-center">
                <button type="button" class="load-more-btn bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm"
                        data-route-id="{{ $route->id }}">
                  Laad overige {{ $locationCount - 10 }} locaties
                </button>
              </div>
              
              {{-- Hidden container for remaining locations --}}
              <div class="remaining-locations hidden" id="remaining-{{ $route->id }}">
                @foreach($route->locations->slice(10) as $location)
                <div class="location-item flex items-center space-x-4 p-4 bg-gray-50 rounded-lg"
                     data-location-id="{{ $location->id }}">
                  <div class="w-8 h-8 rounded-full bg-gray-500 flex items-center justify-center text-white">
                    {{ $loop->iteration + 10 }}
                  </div>
                  <div class="flex-1">
                    <h3 class="font-medium">{{ $location->address }}</h3>
                    <p class="text-sm text-gray-600">{{ $location->city }}</p>
                    <div class="mt-1 flex flex-wrap gap-1">
                      @if($location->tegels > 0)
                        <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs">
                          {{ $location->tegels }} tegels
                        </span>
                      @elseif($location->tegels_count > 0)
                        <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs">
                          {{ $location->tegels_count }} {{ $location->tegels_type ?? 'tegels' }}
                        </span>
                      @endif

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

                      @if($location->completion_minutes)
                        <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs">
                          {{ $location->completion_minutes }} min
                        </span>
                      @endif
                    </div>
                  </div>
                </div>
                @endforeach
              </div>
            @else
              {{-- Regular display for routes with fewer locations --}}
              @foreach($route->locations as $location)
              <div class="location-item flex items-center space-x-4 p-4 bg-gray-50 rounded-lg"
                   data-location-id="{{ $location->id }}">
                <div class="w-8 h-8 rounded-full bg-gray-500 flex items-center justify-center text-white">
                  {{ $loop->iteration }}
                </div>
                <div class="flex-1">
                  <h3 class="font-medium">{{ $location->address }}</h3>
                  <p class="text-sm text-gray-600">{{ $location->city }}</p>
                  <div class="mt-1 flex flex-wrap gap-1">
                    @if($location->tegels > 0)
                      <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs">
                        {{ $location->tegels }} tegels
                      </span>
                    @elseif($location->tegels_count > 0)
                      <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs">
                        {{ $location->tegels_count }} {{ $location->tegels_type ?? 'tegels' }}
                      </span>
                    @endif

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

                    @if($location->completion_minutes)
                      <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs">
                        {{ $location->completion_minutes }} min
                      </span>
                    @endif
                  </div>
                </div>
              </div>
              @endforeach
            @endif
          </div>
          
          {{-- Total tiles count --}}
          @if(isset($routeStats[$route->id]) && $routeStats[$route->id]['total_tiles'] > 0)
          <div class="mt-4 pt-3 border-t border-gray-200">
            <p class="text-sm font-medium">
              Totaal aantal tegels: <span class="text-blue-600">{{ $routeStats[$route->id]['total_tiles'] }}</span>
            </p>
            <div class="mt-1 flex flex-wrap gap-2">
              @foreach($routeStats[$route->id]['tiles_by_type'] as $type => $count)
              <span class="text-xs bg-gray-100 px-2 py-1 rounded">
                {{ $type }}: {{ $count }}
              </span>
              @endforeach
            </div>
          </div>
          @endif
        </div>
        @endforeach
      </div>

    </div>

    {{-- Map Panel --}}
    <div class="lg:col-span-2 bg-white rounded-lg shadow p-6 lg:sticky lg:top-6 lg:self-start">
      <div id="map" class="h-[500px] lg:h-[calc(100vh-120px)] rounded-lg"></div>
    </div>
  </div>
</div>

{{-- Pass data to JavaScript --}}
<script>
  window.routesData = @json($routes);
  window.routeColors = @json($routeColors);

  document.addEventListener('DOMContentLoaded', function() {
    // Handle optimize all routes button
    document.getElementById('optimize-all-routes').addEventListener('click', function() {
      optimizeAllRoutes();
    });

    // Function to optimize all routes (cross-route optimization)
    function optimizeAllRoutes() {
      // Show loading message
      const loadingOverlay = document.createElement('div');
      loadingOverlay.classList.add('fixed', 'inset-0', 'bg-black', 'bg-opacity-50', 'flex', 'items-center', 'justify-center', 'z-50');
      loadingOverlay.id = 'loading-overlay';
      
      const loadingMsg = document.createElement('div');
      loadingMsg.classList.add('bg-white', 'p-6', 'rounded-lg', 'shadow-lg', 'text-center');
      loadingMsg.innerHTML = `
        <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-purple-500 mx-auto mb-4"></div>
        <p class="text-lg">Optimizing all routes...</p>
        <p class="text-sm text-gray-600 mt-2">This may take a few minutes for complex routes</p>
      `;
      
      loadingOverlay.appendChild(loadingMsg);
      document.body.appendChild(loadingOverlay);
      
      // Send AJAX request
      fetch('{{ route('routes.optimize-all') }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
      })
      .then(response => response.json())
      .then(data => {
        // Remove loading overlay
        document.getElementById('loading-overlay').remove();
        
        // Check for success or error
        if (data.success) {
          // Directly reload the page without showing alert
          window.location.reload();
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(error => {
        // Remove loading overlay
        if (document.getElementById('loading-overlay')) {
          document.getElementById('loading-overlay').remove();
        }
        
        console.error('Error:', error);
        alert('An error occurred during optimization. Please try again later.');
      });
    }
  });
</script>
@endsection

@push('scripts')
  {{-- External libraries with defer for better performance --}}
  <script defer src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
  <script defer src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
  
  {{-- Our application JS --}}
  <script defer src="{{ asset('js/routes.js') }}"></script>
@endpush

