@extends('layouts.app')

@section('content')
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<link rel="stylesheet"
      href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />

<div class="max-w-[1920px] mx-auto px-4 py-8">
  {{-- success / error alerts --}}
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

  {{-- Tile Distribution Summary --}}
  @if($routes->isNotEmpty())
    @php
      $routeTotals = $routes->map(function($route) {
        return [
          'name' => $route->name,
          'total_tiles' => $route->locations->sum('tegels_count'),
          'location_count' => $route->locations->count()
        ];
      });
      $avgTiles = $routeTotals->avg('total_tiles');
      $maxDiff = $routeTotals->max('total_tiles') - $routeTotals->min('total_tiles');
      $totalTiles = $routeTotals->sum('total_tiles');
    @endphp
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
          <span class="block font-medium">{{ $totalTiles }}</span>
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

  <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
    <div class="lg:col-span-3 space-y-6">

      {{-- Generate & Delete All --}}
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-semibold">Routes genereren</h2>
          <form action="{{ route('routes.deleteAll') }}" method="POST"
                onsubmit="return confirm('Weet je zeker dat je alle routes wilt verwijderen?');">
            @csrf @method('DELETE')
            <button class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
              Alle routes verwijderen
            </button>
          </form>
        </div>
        <form action="{{ route('routes.generate') }}" method="POST" class="space-y-4">
          @csrf
          <label for="num_routes" class="block text-sm font-medium text-gray-700">Aantal routes</label>
          <input type="number" name="num_routes" id="num_routes" min="1" value="1" required
                 class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500">
          <div class="flex justify-end">
            <button type="submit"
                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
              Routes genereren
            </button>
          </div>
        </form>
      </div>

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

          {{-- Draggable list --}}
          <div class="route-locations space-y-2" id="route-{{ $route->id }}">
            @foreach($route->locations as $location)
            <div class="location-item flex items-center space-x-4 p-4 bg-gray-50 rounded-lg"
                 data-location-id="{{ $location->id }}">
              <div class="w-8 h-8 rounded-full bg-gray-500 flex items-center justify-center text-white">
                {{ $loop->iteration }}
              </div>
              <div class="flex-1">
                <h3 class="font-medium">{{ $location->address }}</h3>
                <p class="text-sm text-gray-600">{{ $location->city }}</p>
                @if($location->tegels_count > 0)
                <div class="mt-1 text-xs flex items-center">
                  <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded">
                    {{ $location->tegels_count }} {{ $location->tegels_type ?? 'tegels' }}
                  </span>
                </div>
                @endif
              </div>
            </div>
            @endforeach
          </div>
          
          {{-- Total tiles count --}}
          @php
            $totalTiles = $route->locations->sum('tegels_count');
            $tileTypes = $route->locations->where('tegels_count', '>', 0)->groupBy('tegels_type');
          @endphp
          @if($totalTiles > 0)
          <div class="mt-4 pt-3 border-t border-gray-200">
            <p class="text-sm font-medium">
              Totaal aantal tegels: <span class="text-blue-600">{{ $totalTiles }}</span>
            </p>
            <div class="mt-1 flex flex-wrap gap-2">
              @foreach($tileTypes as $type => $locations)
              <span class="text-xs bg-gray-100 px-2 py-1 rounded">
                {{ $type ?? 'onbekend' }}: {{ $locations->sum('tegels_count') }}
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

<style>
  .route-locations { min-height:30px; }
  .location-item { cursor:grab; user-select:none; }
</style>
@endsection

@push('scripts')
  {{-- SortableJS for drag'n'drop --}}
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
  {{-- Leaflet for map --}}
  <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

  <script>
  document.addEventListener('DOMContentLoaded', () => {
    // Mutable copy of routes for map redraws
    let routesData = @json($routes);

    // Initialize Leaflet
    const map = L.map('map').setView([51.8372,5.6697],10);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution:'© OpenStreetMap contributors'
    }).addTo(map);
    const start = { lat:51.8372, lng:5.6697 };
    L.marker([start.lat, start.lng]).addTo(map)
     .bindPopup('<strong>Broekstraat 68</strong><br>Nederasselt>');
    const polylines = new Map(), markers = new Map();

    function updateMap(routes) {
      // clear old
      polylines.forEach(pl=>pl.remove()); polylines.clear();
      markers.forEach(arr=>arr.forEach(m=>m.remove())); markers.clear();
      const bounds = L.latLngBounds([start.lat,start.lng]);
      const colors = @json($routeColors);
      routes.forEach((rt, idx) => {
        const coords = [[start.lat, start.lng]], mlist = [];
        rt.locations.forEach(loc => {
          const m = L.marker([loc.latitude, loc.longitude]).addTo(map)
                     .bindPopup(`<strong>${loc.address}</strong><br>${loc.city}${loc.tegels_count ? '<br>' + loc.tegels_count + ' ' + (loc.tegels_type || 'tegels') : ''}`);
          mlist.push(m);
          coords.push([loc.latitude, loc.longitude]);
          bounds.extend([loc.latitude, loc.longitude]);
        });
        coords.push([start.lat, start.lng]);
        const pl = L.polyline(coords, {
          color: colors[idx % colors.length],
          weight: 3, opacity: 0.7
        }).addTo(map);
        markers.set(rt.id, mlist);
        polylines.set(rt.id, pl);
      });
      map.fitBounds(bounds, { padding:[50,50] });
    }
    updateMap(routesData);

    // Helpers
    function showWarning(routeId) {
      document.querySelector(`[data-route-id="${routeId}"] .route-warning`)
              .classList.remove('hidden');
    }
    function reorderRoute(routeId, newOrder) {
      return fetch(`/routes/${routeId}`, {
        method:'PUT',
        headers:{
          'Content-Type':'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ locations:newOrder })
      })
      .then(res => {
        if (!res.ok) throw new Error('Order save failed');
        showWarning(routeId);
      });
    }
    function moveLocation(locId, srcId, tgtId) {
      return fetch(`/routes/move-location`, {
        method:'POST',
        headers:{
          'Content-Type':'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ location_id:locId, target_route_id:tgtId })
      })
      .then(r => r.json())
      .then(d => { 
        if (!d.success) throw new Error(d.message);
        if (d.warning) {
          alert(d.warning);
        }
        return d;
      });
    }
    function recalc(routeId, reload=true) {
      console.log('Recalculating route', routeId);
      return fetch(`/routes/recalculate`, {
        method:'POST',
        headers:{
          'Content-Type':'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ route_id:routeId })
      })
      .then(r => r.json())
      .then(d => {
        if (!d.success) throw new Error(d.message);
        if (reload) location.reload();
      });
    }

    // Wire up "Herbereken" buttons
    document.querySelectorAll('.js-recalc').forEach(btn => {
      btn.addEventListener('click', e => {
        const id = btn.dataset.routeId;
        recalc(id);
      });
    });

    // SortableJS for drag'n'drop
    document.querySelectorAll('.route-locations').forEach(listEl => {
      const routeId = listEl.id.replace('route-','');
      Sortable.create(listEl, {
        group: 'routes',
        animation: 150,
        onEnd: evt => {
          const srcId = evt.from.id.replace('route-','');
          const tgtId = evt.to.id.replace('route-','');
          const locId = evt.item.dataset.locationId;
          const newOrder = Array.from(evt.to.children)
                                .map(ch => ch.dataset.locationId);

          if (srcId === tgtId) {
            // same-route: reorder + warning + map redraw
            reorderRoute(srcId, newOrder)
              .then(() => {
                // update client copy & redraw
                const rt = routesData.find(r=>r.id==srcId);
                rt.locations = newOrder.map(id =>
                  rt.locations.find(l=>l.id==id)
                );
                updateMap(routesData);
              })
              .catch(console.error);

          } else {
            // cross-route: move, auto-recalc both, then reload
            moveLocation(locId, srcId, tgtId)
              .then(()=>Promise.all([
                recalc(srcId,false),
                recalc(tgtId,false)
              ]))
              .then(()=>location.reload())
              .catch(console.error);
          }
        }
      });
    });
  });
  </script>
@endpush
