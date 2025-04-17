<nav class="bg-gradient-to-r from-red-900 to-red-700 shadow-lg">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <div class="flex-shrink-0 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                    <h1 class="text-xl font-bold text-white">Route Optimalisatie</h1>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:space-x-4">
                    <a href="{{ route('route-optimizer.index') }}" 
                       class="px-3 py-2 rounded-md text-sm font-medium text-white hover:bg-red-800 transition-colors {{ request()->routeIs('route-optimizer.index') ? 'bg-red-800' : '' }}">
                        Locaties
                    </a>
                    <a href="{{ route('routes.index') }}" 
                       class="px-3 py-2 rounded-md text-sm font-medium text-white hover:bg-red-800 transition-colors {{ request()->routeIs('routes.*') ? 'bg-red-800' : '' }}">
                        Routes
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav> 