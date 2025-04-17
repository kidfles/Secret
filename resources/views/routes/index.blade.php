@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Create New Route</h2>
        </div>
        <form action="{{ route('routes.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Route Name</label>
                    <input type="text" name="name" id="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>

                <div>
                    <label for="person_capacity" class="block text-sm font-medium text-gray-700">Persons per Route</label>
                    <input type="number" name="person_capacity" id="person_capacity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="2" min="1">
                    <p class="mt-1 text-sm text-gray-500">Default is 2 persons per route</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Locations</label>
                    <div class="space-y-2 max-h-60 overflow-y-auto">
                        @foreach($locations as $location)
                            <div class="flex items-center">
                                <input type="checkbox" name="locations[]" value="{{ $location->id }}" id="location-{{ $location->id }}" 
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="location-{{ $location->id }}" class="ml-2 block text-sm text-gray-900">
                                    {{ $location->name }} ({{ $location->address }}) - {{ $location->person_capacity }} persons
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Create Route
                </button>
            </div>
        </form>

        <div class="mt-8">
            <h2 class="text-lg font-semibold mb-4">Generate Routes</h2>
            <form action="{{ route('routes.generate') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="num_routes" class="block text-sm font-medium text-gray-700">Number of Routes</label>
                    <input type="number" name="num_routes" id="num_routes" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="3" min="1" required>
                    <p class="mt-1 text-sm text-gray-500">How many routes do you want to generate?</p>
                </div>

                <div>
                    <label for="route_capacity" class="block text-sm font-medium text-gray-700">Persons per Route</label>
                    <input type="number" name="route_capacity" id="route_capacity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="2" min="1" required>
                    <p class="mt-1 text-sm text-gray-500">Default is 2 persons per route</p>
                </div>

                <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    Generate Routes
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-semibold mb-4">Your Routes</h2>
        <div class="space-y-4">
            @forelse($routes as $route)
                <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors duration-200">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-medium text-lg">{{ $route->name }}</h3>
                            <p class="text-sm text-gray-600">{{ $route->description }}</p>
                            <div class="mt-2">
                                <span class="text-sm text-gray-500">Locations: {{ $route->locations->count() }}</span>
                                <span class="text-sm text-gray-500 ml-4">Capacity: {{ $route->person_capacity }} persons</span>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <a href="{{ route('routes.show', $route) }}" class="text-indigo-600 hover:text-indigo-800">
                                View
                            </a>
                            <form action="{{ route('routes.destroy', $route) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-center py-4">No routes created yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection 