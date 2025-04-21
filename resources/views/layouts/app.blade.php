<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Route Optimizer') }}</title>

    <!-- Fonts - Load only one font family to reduce overhead -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts and Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Load Leaflet asynchronously -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" media="print" onload="this.media='all'">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>
    
    <style>
        :root {
            --primary-color: #ef4444;
            --primary-hover: #dc2626;
            --primary-focus: #fecaca;
            --accent-color: #16a34a;
            --accent-hover: #15803d;
            --text-color: #1f2937;
            --text-muted: #6b7280;
            --bg-color: #f9fafb;
            --card-bg: #ffffff;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-md: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --radius: 0.375rem;
            --transition: 200ms cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.5;
        }
        
        /* Card styling */
        .card {
            background-color: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            transition: box-shadow var(--transition);
        }
        
        .card:hover {
            box-shadow: var(--shadow-md);
        }
        
        /* Button styling */
        .btn {
            transition: all var(--transition);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }
        
        .btn-primary:focus {
            box-shadow: 0 0 0 3px var(--primary-focus);
        }
        
        .btn-success {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .btn-success:hover {
            background-color: var(--accent-hover);
            border-color: var(--accent-hover);
        }
        
        /* Table styling */
        .table {
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table th {
            background-color: #f8fafc;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }
        
        .table th, .table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.01);
        }
        
        /* Loading animation */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Alert styling */
        .alert {
            border-radius: var(--radius);
            border-left-width: 4px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background-color: #ecfdf5;
            border-color: #10b981;
            color: #065f46;
        }
        
        .alert-danger {
            background-color: #fef2f2;
            border-color: #ef4444;
            color: #991b1b;
        }
        
        .alert-info {
            background-color: #eff6ff;
            border-color: #3b82f6;
            color: #1e40af;
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="alert alert-success" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="list-disc pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Loading indicator -->
    <div id="loading" class="loading">
        <div class="loading-spinner"></div>
    </div>

    <script>
        // Hide loading indicator when page is loaded
        window.addEventListener('load', function() {
            document.getElementById('loading').style.display = 'none';
        });
        
        // Function to set date in session and navigate to another URL
        window.setDateAndNavigate = function(date, url) {
            // Show loading indicator
            document.getElementById('loading').style.display = 'flex';
            
            // Ensure date is in YYYY-MM-DD format as required by the API
            // Log for debug purposes
            console.log('Setting date in session:', date);
            
            fetch('/api/set-selected-date', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ date: date })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Date set in session:', data);
                window.location.href = url;
            })
            .catch(error => {
                console.error('Error setting date in session:', error);
                // Continue anyway
                window.location.href = url;
            });
        }
    </script>

    @stack('scripts')
</body>
</html> 