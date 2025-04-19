@extends('layouts.app')

@section('content')
<div class="container-fluid py-5 px-4">
    <!-- Redesigned Header Section -->
    <div class="header-section mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="display-5 fw-bold text-dark m-0">Dagplanner</h1>
            <div class="quick-actions">
                <button type="button" class="btn btn-outline-secondary rounded-pill me-2" id="today-btn">
                    <i class="fas fa-calendar-day me-1"></i> Vandaag
                </button>
                <a href="{{ route('day-planner.create') }}" class="btn btn-primary rounded-pill">
                    <i class="fas fa-plus me-1"></i> Nieuwe Planning
                </a>
            </div>
        </div>
        
        <!-- Unified Modern Search Bar -->
        <div class="search-container mb-4">
            <div class="unified-search shadow-sm">
                <div class="search-input-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="date-search" class="unified-search-input" 
                           placeholder="Zoek op datum of spring naar een specifieke dag...">
                </div>
                <div class="search-shortcuts">
                    <button type="button" class="search-shortcut" id="yesterday-btn">
                        <i class="fas fa-chevron-left"></i>
                        <span>Gisteren</span>
                    </button>
                    <div class="shortcut-divider"></div>
                    <button type="button" class="search-shortcut" id="tomorrow-btn">
                        <span>Morgen</span>
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    <div class="shortcut-divider"></div>
                    <button type="button" class="search-shortcut date-picker-trigger">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Kalender</span>
                    </button>
                    <input type="date" id="hidden-date-picker" class="visually-hidden">
                </div>
            </div>
            <!-- Search results will be appended here by JavaScript -->
        </div>
    </div>

    <!-- Alerts -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            {{ session('success') }}
        </div>
    @endif
    
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            {{ session('error') }}
        </div>
    @endif

    <!-- Days Section - Redesigned Modern UI -->
    <div class="days-section">
        <h2 class="section-title mb-4">Geplande dagen</h2>
        
        @if(count($plannedDays) > 0)
            <div class="day-cards">
                @foreach($plannedDays as $day)
                    <div class="day-card" onclick="window.location.href='/day-planner/{{ $day['date'] }}'">
                        <div class="day-card-content">
                            <div class="day-info">
                                <div class="date-badge">
                                    <span class="day-num">{{ \Carbon\Carbon::parse($day['date'])->format('d') }}</span>
                                    <span class="month">{{ \Carbon\Carbon::parse($day['date'])->format('M') }}</span>
                                </div>
                                <div class="day-details">
                                    <h3 class="day-title">{{ \Carbon\Carbon::parse($day['date'])->format('l') }}</h3>
                                    <div class="route-count">
                                        <i class="fas fa-route me-2"></i>
                                        <span>{{ $day['routes_count'] }} {{ $day['routes_count'] == 1 ? 'route' : 'routes' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="day-actions">
                                <a href="{{ url('/day-planner/' . $day['date'] . '/edit') }}" 
                                   class="edit-button" aria-label="Bewerken">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <img src="https://cdn-icons-png.flaticon.com/512/6133/6133991.png" alt="Empty calendar" class="empty-icon">
                <h3>Nog geen dagen gepland</h3>
                <p>Creëer een nieuwe planning of zoek naar een datum hierboven om te beginnen</p>
            </div>
        @endif
    </div>
</div>

<!-- Add Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* Global styles */
    body {
        background-color: #f8f9fa;
    }
    
    /* Header and search styles - Modern Redesign */
    .header-section {
        position: relative;
    }
    
    .search-container {
        position: relative;
        margin-top: 1.5rem;
    }
    
    .unified-search {
        display: flex;
        background: white;
        border-radius: 12px;
        overflow: hidden;
        padding: 0.5rem 1rem;
        align-items: center;
        transition: all 0.3s ease;
    }
    
    .unified-search:focus-within {
        box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
    }
    
    .search-input-wrapper {
        display: flex;
        align-items: center;
        flex: 1;
        margin-right: 1rem;
        position: relative;
    }
    
    .search-icon {
        color: #6c757d;
        margin-right: 0.75rem;
    }
    
    .unified-search-input {
        border: none;
        width: 100%;
        font-size: 1rem;
        padding: 0.75rem 0;
        outline: none;
    }
    
    .search-shortcuts {
        display: flex;
        align-items: center;
        border-left: 1px solid #f0f0f0;
        padding-left: 1rem;
    }
    
    .search-shortcut {
        display: flex;
        align-items: center;
        background: none;
        border: none;
        padding: 0.35rem 0.75rem;
        color: #495057;
        cursor: pointer;
        border-radius: 6px;
        transition: all 0.2s ease;
        font-size: 0.9rem;
    }
    
    .search-shortcut:hover {
        background-color: #f8f9fa;
        color: #0d6efd;
    }
    
    .search-shortcut i {
        margin: 0 0.4rem;
    }
    
    .shortcut-divider {
        width: 1px;
        height: 20px;
        background-color: #e9ecef;
        margin: 0 0.5rem;
    }
    
    .search-results {
        position: absolute;
        top: calc(100% + 0.5rem);
        left: 0;
        right: 0;
        background: white;
        border-radius: 12px;
        z-index: 100;
        max-height: 350px;
        overflow-y: auto;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    
    .search-result-item {
        padding: 1rem;
        cursor: pointer;
        border-bottom: 1px solid #f1f1f1;
        transition: all 0.2s ease;
    }
    
    .search-result-item:hover {
        background-color: #f8f9fa;
    }
    
    .search-result-item:last-child {
        border-bottom: none;
    }
    
    /* Quick actions */
    .quick-actions {
        display: flex;
        gap: 0.5rem;
    }
    
    /* Section title */
    .section-title {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        color: #212529;
        position: relative;
        display: inline-block;
    }
    
    .section-title:after {
        content: '';
        position: absolute;
        left: 0;
        bottom: -8px;
        width: 40px;
        height: 4px;
        background: linear-gradient(to right, #dc3545, #f8bbc1);
        border-radius: 2px;
    }
    
    /* Day cards grid */
    .day-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
    }
    
    /* Day card */
    .day-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 6px 15px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
    }
    
    .day-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 20px rgba(0,0,0,0.1);
    }
    
    .day-card-content {
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    /* Day info */
    .day-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .date-badge {
        background: linear-gradient(45deg, #f55b53, #eb3349);
        color: white;
        min-width: 60px;
        min-height: 60px;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 0.5rem;
        box-shadow: 0 4px 10px rgba(235, 51, 73, 0.3);
    }
    
    .day-num {
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1;
    }
    
    .month {
        font-size: 0.8rem;
        font-weight: 500;
        text-transform: uppercase;
        opacity: 0.9;
    }
    
    .day-details {
        display: flex;
        flex-direction: column;
    }
    
    .day-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
        color: #333;
    }
    
    .route-count {
        display: flex;
        align-items: center;
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    /* Actions */
    .day-actions {
        margin-left: auto;
    }
    
    .edit-button {
        background-color: #dc3545;
        color: white;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 3px 10px rgba(220, 53, 69, 0.3);
        transition: all 0.2s ease;
    }
    
    .edit-button:hover {
        background-color: #c82333;
        transform: scale(1.1);
        color: white;
    }
    
    /* Empty state */
    .empty-state {
        background-color: white;
        border-radius: 16px;
        padding: 4rem 2rem;
        text-align: center;
        box-shadow: 0 6px 15px rgba(0,0,0,0.05);
    }
    
    .empty-icon {
        width: 120px;
        height: 120px;
        margin-bottom: 1.5rem;
        opacity: 0.7;
    }
    
    .empty-state h3 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #343a40;
        margin-bottom: 0.5rem;
    }
    
    .empty-state p {
        color: #6c757d;
        max-width: 400px;
        margin: 0 auto;
    }
    
    /* Media queries */
    @media (max-width: 768px) {
        .unified-search {
            flex-direction: column;
            align-items: stretch;
            padding: 0.75rem;
        }
        
        .search-input-wrapper {
            margin-right: 0;
            margin-bottom: 0.75rem;
        }
        
        .search-shortcuts {
            border-left: none;
            padding-left: 0;
            border-top: 1px solid #f0f0f0;
            padding-top: 0.75rem;
            justify-content: space-between;
        }
        
        .day-cards {
            grid-template-columns: 1fr;
        }
        
        .day-card-content {
            padding: 1.25rem;
        }
        
        .quick-actions {
            flex-direction: column;
            align-items: flex-end;
            gap: 0.5rem;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Bootstrap components
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        
        // Date navigation shortcuts
        document.getElementById('today-btn').addEventListener('click', function() {
            const today = new Date().toISOString().slice(0, 10);
            window.location.href = '/day-planner/' + today;
        });
        
        document.getElementById('yesterday-btn').addEventListener('click', function() {
            const yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            const formattedDate = yesterday.toISOString().slice(0, 10);
            window.location.href = '/day-planner/' + formattedDate;
        });
        
        document.getElementById('tomorrow-btn').addEventListener('click', function() {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const formattedDate = tomorrow.toISOString().slice(0, 10);
            window.location.href = '/day-planner/' + formattedDate;
        });
        
        // Calendar picker trigger
        document.querySelector('.date-picker-trigger').addEventListener('click', function() {
            document.getElementById('hidden-date-picker').click();
        });
        
        // Hidden date picker
        document.getElementById('hidden-date-picker').addEventListener('change', function() {
            if (this.value) {
                window.location.href = '/day-planner/' + this.value;
            }
        });
        
        // Date search functionality
        const searchInput = document.getElementById('date-search');
        const searchContainer = document.querySelector('.search-container');
        
        // Create search results element dynamically only when needed
        let searchResults = null;
        
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            // Remove existing search results if present
            if (searchResults) {
                searchResults.remove();
                searchResults = null;
            }
            
            if (query.length < 2) {
                return;
            }
            
            // Create search results container
            searchResults = document.createElement('div');
            searchResults.className = 'search-results';
            searchResults.innerHTML = '<div class="search-result-item">Zoeken...</div>';
            searchContainer.appendChild(searchResults);
            
            // In a real app, this would be an AJAX call to the backend
            // For now, we'll simulate with some example dates
            setTimeout(() => {
                const today = new Date();
                const results = [];
                
                // Add some example results
                for (let i = -5; i < 15; i++) {
                    const date = new Date();
                    date.setDate(today.getDate() + i);
                    
                    const dateStr = date.toISOString().slice(0, 10);
                    const formattedDate = date.toLocaleDateString('nl-NL', {
                        weekday: 'long', 
                        day: 'numeric', 
                        month: 'long',
                        year: 'numeric'
                    });
                    
                    // Only add if matches the search
                    if (formattedDate.toLowerCase().includes(query.toLowerCase())) {
                        results.push({ dateStr, formattedDate });
                    }
                }
                
                // If search results were removed during the timeout, stop here
                if (!searchResults) return;
                
                // Display results or no results message
                if (results.length > 0) {
                    searchResults.innerHTML = results.map(result => 
                        `<div class="search-result-item" data-date="${result.dateStr}">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-calendar-day text-primary"></i>
                                </div>
                                <div>
                                    <div class="fw-medium">${result.formattedDate}</div>
                                    <div class="text-muted small">Klik om naar deze dag te gaan</div>
                                </div>
                            </div>
                        </div>`
                    ).join('');
                    
                    // Add click event to results
                    document.querySelectorAll('.search-result-item').forEach(item => {
                        item.addEventListener('click', function() {
                            const selectedDate = this.getAttribute('data-date');
                            window.location.href = '/day-planner/' + selectedDate;
                        });
                    });
                } else {
                    searchResults.innerHTML = `
                        <div class="search-result-item">
                            <div class="text-center py-2">
                                <div class="mb-2"><i class="fas fa-search text-muted"></i></div>
                                <div>Geen resultaten gevonden</div>
                                <div class="small text-muted">Probeer een andere zoekterm</div>
                            </div>
                        </div>`;
                }
            }, 300);
        });
        
        // Close search results when clicking outside
        document.addEventListener('click', function(event) {
            if (searchResults && !searchInput.contains(event.target) && !searchResults.contains(event.target)) {
                searchResults.remove();
                searchResults = null;
            }
        });
    });
</script>
@endsection 