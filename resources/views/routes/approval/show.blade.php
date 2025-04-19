@extends('layouts.app')

@section('content')
<div class="max-w-[1920px] mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Routeplanning {{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}</h1>
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

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Samenvatting</h2>
                <p class="text-gray-500 text-sm">{{ $routes->count() }} routes | {{ $totalLocations }} locaties | {{ $totalTiles }} tegels</p>
            </div>
            <div class="flex gap-3">
                @if(!$isApproved)
                    <form action="{{ route('routes.approval.approve', ['date' => $date]) }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Goedkeuren
                        </button>
                    </form>
                @else
                    <form action="{{ route('routes.approval.unapprove', ['date' => $date]) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:bg-yellow-700 active:bg-yellow-800 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                            Goedkeuring intrekken
                        </button>
                    </form>
                @endif
                
                <button type="button" onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                    </svg>
                    Afdrukken
                </button>
            </div>
        </div>
        
        @if($isApproved)
            <div class="bg-green-100 text-green-800 p-3 rounded-md mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Goedgekeurd op {{ \Carbon\Carbon::parse($approvedAt)->format('d-m-Y H:i') }}
                @if($approvedBy)
                    door {{ $approvedBy }}
                @endif
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        @foreach($routes as $route)
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">{{ $route->name }}</h3>
                    <div class="mt-2 grid grid-cols-3 gap-2 text-sm">
                        <div class="bg-gray-50 p-2 rounded">
                            <span class="text-gray-500">Afstand</span>
                            <p class="font-medium">{{ round($route->total_distance) }} km</p>
                        </div>
                        <div class="bg-gray-50 p-2 rounded">
                            <span class="text-gray-500">Locaties</span>
                            <p class="font-medium">{{ $route->locations->count() }}</p>
                        </div>
                        <div class="bg-gray-50 p-2 rounded">
                            <span class="text-gray-500">Tegels</span>
                            <p class="font-medium">{{ $route->locations->sum('tegels') }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="border-b">
                    <div class="p-3 bg-gray-50 border-b flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-600">Locaties</span>
                        <span class="text-xs text-gray-500">{{ $route->locations->count() }} totaal</span>
                    </div>
                    
                    <div class="divide-y max-h-80 overflow-y-auto">
                        @foreach($route->locations as $index => $location)
                            <div class="p-3 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <div class="h-6 w-6 bg-gray-200 rounded-full flex items-center justify-center text-xs font-medium text-gray-800 mr-3">
                                        {{ $index + 1 }}
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-800">{{ $location->name }}</h4>
                                        <p class="text-xs text-gray-500">{{ $location->address }}</p>
                                        <div class="flex gap-2 mt-1">
                                            @if($location->tegels > 0)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ $location->tegels }} tegels
                                                </span>
                                            @endif
                                            
                                            @if($location->begintime && $location->endtime)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                    {{ substr($location->begintime, 0, 5) }} - {{ substr($location->endtime, 0, 5) }}
                                                </span>
                                            @endif
                                            
                                            @if($location->completion_minutes)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                    {{ $location->completion_minutes }} min
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <div class="p-4 bg-gray-50">
                    <a href="{{ route('routes.show', $route->id) }}" class="text-sm text-red-600 hover:text-red-800 font-medium">
                        Route details bekijken →
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>

@endsection

@section('styles')
<style type="text/css" media="print">
    nav, header, footer, .no-print, a[href="{{ route('routes.approval.index') }}"], button[onclick="window.print()"], form {
        display: none !important;
    }
    
    .shadow {
        box-shadow: none !important;
    }
    
    body {
        background-color: white !important;
    }
</style>
@endsection 