@extends('layouts.app')

@section('content')
<div class="max-w-[1920px] mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Nieuwe routeplanning maken</h1>
        <a href="{{ route('routes.approval.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:bg-gray-200 active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            Terug naar planningen
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

    @if($unscheduledRoutes->isEmpty())
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <p class="text-gray-500 mb-4">Er zijn geen beschikbare routes om in te plannen.</p>
            <a href="{{ route('routes.index') }}" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Maak nieuwe routes aan
            </a>
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-6">
            <form action="{{ route('routes.approval.schedule') }}" method="POST">
                @csrf
                
                <div class="mb-6">
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Plannningsdatum</label>
                    <input type="date" name="date" id="date" value="{{ $date ?? \Carbon\Carbon::today()->format('Y-m-d') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500" required>
                    @error('date')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Selecteer routes om in te plannen</h2>
                    
                    <div class="mb-4 flex justify-between items-center">
                        <div class="flex items-center">
                            <input type="checkbox" id="select-all-routes" class="rounded border-gray-300 text-red-600 shadow-sm focus:border-red-500 focus:ring-red-500">
                            <label for="select-all-routes" class="ml-2 text-sm font-medium text-gray-700">Selecteer alle routes</label>
                        </div>
                        <div class="text-sm text-gray-500">
                            <span id="selected-count">0</span> van {{ $unscheduledRoutes->count() }} routes geselecteerd
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($unscheduledRoutes as $route)
                            <div class="relative flex items-start">
                                <div class="flex h-5 items-center">
                                    <input type="checkbox" name="route_ids[]" value="{{ $route->id }}" id="route-{{ $route->id }}" class="route-checkbox h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                </div>
                                <div class="ml-3 text-sm leading-6">
                                    <label for="route-{{ $route->id }}" class="font-medium text-gray-900">{{ $route->name }}</label>
                                    <p class="text-gray-500">
                                        {{ $route->locations->count() }} locaties
                                        <br>
                                        {{ round($route->total_distance) }} km
                                    </p>
                                    
                                    @if($route->scheduled_date)
                                        <span class="bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded text-xs">
                                            Gepland op {{ \Carbon\Carbon::parse($route->scheduled_date)->format('d-m-Y') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    @error('route_ids')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" id="submit-button" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        Plan geselecteerde routes
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllCheckbox = document.getElementById('select-all-routes');
        const routeCheckboxes = document.querySelectorAll('.route-checkbox');
        const selectedCountElement = document.getElementById('selected-count');
        const submitButton = document.getElementById('submit-button');
        
        function updateSelectedCount() {
            const selectedCount = document.querySelectorAll('.route-checkbox:checked').length;
            selectedCountElement.textContent = selectedCount;
            
            // Enable/disable submit button
            submitButton.disabled = selectedCount === 0;
        }
        
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                routeCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateSelectedCount();
            });
        }
        
        routeCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateSelectedCount();
                
                // Update "select all" checkbox
                if (selectAllCheckbox) {
                    const allChecked = document.querySelectorAll('.route-checkbox:checked').length === routeCheckboxes.length;
                    selectAllCheckbox.checked = allChecked;
                }
            });
        });
        
        // Initial count update
        updateSelectedCount();
    });
</script>
@endsection 