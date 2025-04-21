<nav class="bg-white shadow-md border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Logo and brand -->
            <div class="flex items-center">
                <div class="flex-shrink-0 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                    <h1 class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-red-600 to-red-800">Route Optimalisatie</h1>
                </div>
                
                <!-- Primary Navigation -->
                <div class="hidden sm:ml-10 sm:flex sm:space-x-2">
                    <!-- Workflow oriented navigation -->
                    <a href="{{ route('day-planner.index') }}" 
                       class="flex items-center px-4 py-2 rounded-md text-sm font-medium transition-all duration-200 
                       {{ request()->routeIs('day-planner.*') ? 'bg-red-600 text-white shadow-md' : 'text-gray-700 hover:bg-red-50 hover:text-red-700' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Dagplanning</span>
                    </a>

                    <div class="flex items-center text-gray-400 mx-0.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                
                    <a href="{{ route('route-optimizer.index') }}" 
                       class="flex items-center px-4 py-2 rounded-md text-sm font-medium transition-all duration-200
                       {{ request()->routeIs('route-optimizer.*') ? 'bg-red-600 text-white shadow-md' : 'text-gray-700 hover:bg-red-50 hover:text-red-700' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Locaties</span>
                    </a>

                    <div class="flex items-center text-gray-400 mx-0.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                
                    <a href="{{ route('routes.index') }}" 
                       class="flex items-center px-4 py-2 rounded-md text-sm font-medium transition-all duration-200
                       {{ request()->routeIs('routes.*') ? 'bg-red-600 text-white shadow-md' : 'text-gray-700 hover:bg-red-50 hover:text-red-700' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                        <span>Routes</span>
                    </a>
                </div>
            </div>
            
            <!-- Secondary Navigation -->
            <div class="flex items-center">
                <a href="{{ route('routes.approval.index') }}" 
                   class="flex items-center px-4 py-2 rounded-md text-sm font-medium transition-all duration-200
                   {{ request()->routeIs('routes.approval.*') ? 'bg-green-600 text-white shadow-md' : 'text-gray-700 border border-green-200 hover:bg-green-50 hover:text-green-700' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Routes goedkeuren</span>
                </a>
            </div>
        </div>
    </div>
</nav> 