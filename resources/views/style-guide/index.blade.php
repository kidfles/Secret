@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Style Guide</h1>

    <!-- Colors Section -->
    <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-4">Colors</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="p-4 bg-red-500 text-white rounded-lg">
                <p class="font-semibold">Primary Red</p>
                <p class="text-sm opacity-75">bg-red-500</p>
            </div>
            <div class="p-4 bg-blue-500 text-white rounded-lg">
                <p class="font-semibold">Primary Blue</p>
                <p class="text-sm opacity-75">bg-blue-500</p>
            </div>
            <div class="p-4 bg-gray-100 text-gray-800 rounded-lg">
                <p class="font-semibold">Light Gray</p>
                <p class="text-sm opacity-75">bg-gray-100</p>
            </div>
            <div class="p-4 bg-white border border-gray-200 rounded-lg">
                <p class="font-semibold">White</p>
                <p class="text-sm opacity-75">bg-white</p>
            </div>
        </div>
    </section>

    <!-- Typography Section -->
    <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-4">Typography</h2>
        <div class="space-y-4">
            <div>
                <h1 class="text-4xl font-bold">Heading 1</h1>
                <p class="text-sm text-gray-600">text-4xl font-bold</p>
            </div>
            <div>
                <h2 class="text-3xl font-semibold">Heading 2</h2>
                <p class="text-sm text-gray-600">text-3xl font-semibold</p>
            </div>
            <div>
                <h3 class="text-2xl font-medium">Heading 3</h3>
                <p class="text-sm text-gray-600">text-2xl font-medium</p>
            </div>
            <div>
                <p class="text-base">Body text - The quick brown fox jumps over the lazy dog.</p>
                <p class="text-sm text-gray-600">text-base</p>
            </div>
            <div>
                <p class="text-sm">Small text - The quick brown fox jumps over the lazy dog.</p>
                <p class="text-sm text-gray-600">text-sm</p>
            </div>
        </div>
    </section>

    <!-- Buttons Section -->
    <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-4">Buttons</h2>
        <div class="space-y-4">
            <div class="flex flex-wrap gap-4">
                <button class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
                    Primary Button
                </button>
                <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                    Secondary Button
                </button>
                <button class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded">
                    Tertiary Button
                </button>
            </div>
            <div class="flex flex-wrap gap-4">
                <button class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-full">
                    Rounded Button
                </button>
                <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-full">
                    Rounded Button
                </button>
            </div>
        </div>
    </section>

    <!-- Cards Section -->
    <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-4">Cards</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-xl font-semibold mb-2">Basic Card</h3>
                <p class="text-gray-600">This is a basic card with shadow and padding.</p>
            </div>
            <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-red-500">
                <h3 class="text-xl font-semibold mb-2">Card with Border</h3>
                <p class="text-gray-600">This card has a colored left border for emphasis.</p>
            </div>
        </div>
    </section>

    <!-- Alerts Section -->
    <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-4">Alerts</h2>
        <div class="space-y-4">
            <div class="p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded">
                <p class="font-medium">Success Alert</p>
                <p class="text-sm">This is a success message.</p>
            </div>
            <div class="p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded">
                <p class="font-medium">Error Alert</p>
                <p class="text-sm">This is an error message.</p>
            </div>
            <div class="p-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 rounded">
                <p class="font-medium">Warning Alert</p>
                <p class="text-sm">This is a warning message.</p>
            </div>
            <div class="p-4 bg-blue-100 border-l-4 border-blue-500 text-blue-700 rounded">
                <p class="font-medium">Info Alert</p>
                <p class="text-sm">This is an information message.</p>
            </div>
        </div>
    </section>

    <!-- Form Elements Section -->
    <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-4">Form Elements</h2>
        <div class="max-w-md space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Text Input</label>
                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent" placeholder="Enter text...">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Input</label>
                <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <option>Option 1</option>
                    <option>Option 2</option>
                    <option>Option 3</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Textarea</label>
                <textarea class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent" rows="3" placeholder="Enter text..."></textarea>
            </div>
        </div>
    </section>

    <!-- Badges Section -->
    <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-4">Badges</h2>
        <div class="flex flex-wrap gap-4">
            <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-sm">Red Badge</span>
            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">Blue Badge</span>
            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-sm">Green Badge</span>
            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm">Yellow Badge</span>
        </div>
    </section>

    <!-- Icons Section -->
    <section class="mb-12">
        <h2 class="text-2xl font-semibold mb-4">Icons</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="p-4 bg-white rounded-lg shadow text-center">
                <i class="fas fa-calendar text-2xl text-red-500 mb-2"></i>
                <p class="text-sm text-gray-600">Calendar</p>
            </div>
            <div class="p-4 bg-white rounded-lg shadow text-center">
                <i class="fas fa-map-marker-alt text-2xl text-blue-500 mb-2"></i>
                <p class="text-sm text-gray-600">Location</p>
            </div>
            <div class="p-4 bg-white rounded-lg shadow text-center">
                <i class="fas fa-route text-2xl text-green-500 mb-2"></i>
                <p class="text-sm text-gray-600">Route</p>
            </div>
            <div class="p-4 bg-white rounded-lg shadow text-center">
                <i class="fas fa-clock text-2xl text-yellow-500 mb-2"></i>
                <p class="text-sm text-gray-600">Time</p>
            </div>
        </div>
    </section>
</div>
@endsection 