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
                <button type="button" class="btn btn-primary rounded-pill" id="new-planning-btn">
                    <i class="fas fa-plus me-1"></i> Nieuwe Planning
                </button>
            </div>
        </div>
        
        <!-- New Planning Date Selector (hidden by default) -->
        <div id="new-planning-panel" class="new-planning-panel shadow-sm mb-4" style="display: none;">
            <form id="new-planning-form" action="{{ route('day-planner.store') }}" method="POST">
                @csrf
                <div class="row align-items-center g-3">
                    <div class="col-md-5">
                        <label for="planning-date" class="form-label mb-2 fw-medium">Voor welke datum wil je een planning maken?</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-calendar-alt text-primary"></i>
                            </span>
                            <input type="date" class="form-control border-start-0" 
                                   id="planning-date" name="date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div id="date-validation-message" class="text-danger mt-1" style="display: none;"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-2 fw-medium">Snelkeuze</label>
                        <div class="quick-date-buttons">
                            <button type="button" class="btn-quick-date" data-offset="0">
                                <i class="fas fa-calendar-day"></i>
                                <span>Vandaag</span>
                            </button>
                            <button type="button" class="btn-quick-date" data-offset="1">
                                <i class="fas fa-sun"></i>
                                <span>Morgen</span>
                            </button>
                            <button type="button" class="btn-quick-date" data-offset="7">
                                <i class="fas fa-calendar-week"></i>
                                <span>Volgende week</span>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex justify-content-end align-items-end mt-4">
                        <button type="button" class="btn btn-light rounded-pill px-4 me-2 shadow-sm" id="cancel-new-planning">
                            <i class="fas fa-times me-1"></i> Annuleren
                        </button>
                        <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm" id="submit-new-planning">
                            <i class="fas fa-check me-1"></i> Aanmaken
                        </button>
                    </div>
                </div>
            </form>
            <div class="spinner-container text-center py-3" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Laden...</span>
                </div>
                <p class="mt-2 text-muted">Planning aanmaken...</p>
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
    <div id="alert-container">
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
    </div>

    <!-- Days Section - Redesigned Modern UI -->
    <div class="days-section">
        <h2 class="section-title mb-4">Geplande dagen</h2>
        
        <div id="day-cards-container">
            @if(count($plannedDays) > 0)
                <div class="day-cards">
                    @foreach($plannedDays as $day)
                        <div class="day-card">
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
                                    <a href="{{ route('route-optimizer.index') }}" 
                                       class="edit-button" aria-label="Bewerken" data-date="{{ $day['date'] }}">
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
                    <p>Creëer een nieuwe planning om te beginnen. Daarna kun je locaties toevoegen in de locatiebeheerder.</p>
                </div>
            @endif
        </div>
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
    
    /* New planning panel styles */
    .new-planning-panel {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-top: 1rem;
        position: relative;
        overflow: hidden;
        animation: slideDown 0.3s ease;
        box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        border: 1px solid rgba(0,0,0,0.03);
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-15px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .quick-date-buttons {
        display: flex;
        gap: 10px;
    }
    
    .btn-quick-date {
        display: flex;
        flex-direction: column;
        align-items: center;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 10px 16px;
        cursor: pointer;
        transition: all 0.2s ease;
        flex: 1;
        min-width: 90px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.03);
    }
    
    .btn-quick-date i {
        font-size: 1.25rem;
        color: #0d6efd;
        margin-bottom: 5px;
    }
    
    .btn-quick-date span {
        font-weight: 500;
        font-size: 0.9rem;
        color: #495057;
    }
    
    .btn-quick-date:hover {
        background: #e9f2ff;
        border-color: #0d6efd;
        transform: translateY(-2px);
        box-shadow: 0 5px 10px rgba(13, 110, 253, 0.1);
    }
    
    .btn-quick-date:active {
        transform: translateY(0);
    }
    
    .spinner-container {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 10;
        border-radius: 12px;
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
    
    /* Custom alert styles */
    .custom-alert {
        border-left: 4px solid;
        border-radius: 8px;
        animation: slideIn 0.3s ease;
    }
    
    .custom-alert.alert-success {
        border-left-color: #28a745;
    }
    
    .custom-alert.alert-danger {
        border-left-color: #dc3545;
    }
    
    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
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
        
        .quick-date-buttons {
            flex-wrap: wrap;
        }
        
        .btn-quick-date {
            flex: 1 0 auto;
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
        
        // New Planning Panel Toggle
        const newPlanningBtn = document.getElementById('new-planning-btn');
        const newPlanningPanel = document.getElementById('new-planning-panel');
        const cancelNewPlanning = document.getElementById('cancel-new-planning');
        const newPlanningForm = document.getElementById('new-planning-form');
        const spinnerContainer = document.querySelector('.spinner-container');
        const dateValidationMessage = document.getElementById('date-validation-message');
        const plannedDates = {!! json_encode($plannedDays->pluck('date')) !!};
        
        newPlanningBtn.addEventListener('click', function() {
            newPlanningPanel.style.display = 'block';
            // Focus the date input
            setTimeout(() => {
                document.getElementById('planning-date').focus();
            }, 300);
        });
        
        cancelNewPlanning.addEventListener('click', function() {
            newPlanningPanel.style.display = 'none';
            dateValidationMessage.style.display = 'none';
            newPlanningForm.reset();
        });
        
        // Check if date already has a planning
        function isDatePlanned(date) {
            return plannedDates.includes(date);
        }
        
        // Handle form submission - now using regular form submission, not AJAX
        newPlanningForm.addEventListener('submit', function(e) {
            const date = document.getElementById('planning-date').value;
            
            // Check if the date already has a planning
            if (isDatePlanned(date)) {
                e.preventDefault();
                dateValidationMessage.textContent = 'Voor deze datum bestaat al een planning.';
                dateValidationMessage.style.display = 'block';
                return false;
            }
            
            // Show loading spinner
            spinnerContainer.style.display = 'flex';
            
            // Continue with form submission - it will follow the redirect from the controller
        });
        
        // Helper function to show an alert
        function showAlert(type, message) {
            const alertContainer = document.getElementById('alert-container');
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show shadow-sm custom-alert">
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    ${message}
                </div>
            `;
            
            alertContainer.innerHTML = alertHtml;
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const alert = document.querySelector('.alert');
                if (alert) {
                    alert.classList.remove('show');
                    setTimeout(() => {
                        alertContainer.innerHTML = '';
                    }, 300);
                }
            }, 5000);
        }
        
        // Helper function to refresh the day cards
        function refreshDayCards() {
            fetch('{{ route('day-planner.index') }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                // We need to extract the day cards section from the HTML
                const tempElement = document.createElement('div');
                tempElement.innerHTML = html;
                
                const newDayCards = tempElement.querySelector('#day-cards-container');
                if (newDayCards) {
                    document.getElementById('day-cards-container').innerHTML = newDayCards.innerHTML;
                }
            })
            .catch(error => {
                console.error('Error refreshing day cards:', error);
            });
        }
        
        // Quick date selection in new planning panel
        document.querySelectorAll('.btn-quick-date').forEach(button => {
            button.addEventListener('click', function() {
                const offset = parseInt(this.getAttribute('data-offset'));
                const date = new Date();
                date.setDate(date.getDate() + offset);
                const formattedDate = date.toISOString().slice(0, 10);
                document.getElementById('planning-date').value = formattedDate;
                
                // Check date validation right away
                if (isDatePlanned(formattedDate)) {
                    dateValidationMessage.textContent = 'Voor deze datum bestaat al een planning.';
                    dateValidationMessage.style.display = 'block';
                } else {
                    dateValidationMessage.style.display = 'none';
                }
                
                // Visual feedback - highlight the selected button
                document.querySelectorAll('.btn-quick-date').forEach(btn => {
                    btn.classList.remove('active');
                    btn.style.background = '';
                    btn.style.borderColor = '';
                });
                
                this.classList.add('active');
                this.style.background = '#e9f2ff';
                this.style.borderColor = '#0d6efd';
            });
        });
        
        // Check for duplicate on date input change
        document.getElementById('planning-date').addEventListener('change', function() {
            const selectedDate = this.value;
            
            if (isDatePlanned(selectedDate)) {
                dateValidationMessage.textContent = 'Voor deze datum bestaat al een planning.';
                dateValidationMessage.style.display = 'block';
            } else {
                dateValidationMessage.style.display = 'none';
            }
        });
        
        // Date navigation shortcuts
        document.getElementById('today-btn').addEventListener('click', function() {
            const today = new Date().toISOString().slice(0, 10);
            window.location.href = '{{ url("/day-planner") }}/' + today;
        });
        
        document.getElementById('yesterday-btn').addEventListener('click', function() {
            const yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            const formattedDate = yesterday.toISOString().slice(0, 10);
            window.location.href = '{{ url("/day-planner") }}/' + formattedDate;
        });
        
        document.getElementById('tomorrow-btn').addEventListener('click', function() {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const formattedDate = tomorrow.toISOString().slice(0, 10);
            window.location.href = '{{ url("/day-planner") }}/' + formattedDate;
        });
        
        // Calendar picker trigger
        document.querySelector('.date-picker-trigger').addEventListener('click', function() {
            document.getElementById('hidden-date-picker').click();
        });
        
        // Hidden date picker
        document.getElementById('hidden-date-picker').addEventListener('change', function() {
            if (this.value) {
                window.location.href = '{{ url("/day-planner") }}/' + this.value;
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
                            window.location.href = '{{ url("/day-planner") }}/' + selectedDate;
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
        
        // Close new planning panel when clicking outside (but not inside the panel or button)
        document.addEventListener('click', function(event) {
            if (newPlanningPanel.style.display === 'block' && 
                !newPlanningPanel.contains(event.target) && 
                !newPlanningBtn.contains(event.target)) {
                newPlanningPanel.style.display = 'none';
                dateValidationMessage.style.display = 'none';
                newPlanningForm.reset();
            }
        });
        
        // AJAX helper to set date in session
        function setDateInSession(date, callback) {
            const csrfToken = document.querySelector('input[name="_token"]').value;
            fetch('/api/set-selected-date', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ date: date })
            })
            .then(response => response.json())
            .then(data => {
                if (callback) callback(data);
            })
            .catch(error => {
                console.error('Error setting date:', error);
                // Continue anyway
                if (callback) callback({});
            });
        }
        
        // Update click handler for day cards
        document.querySelectorAll('.day-card').forEach(card => {
            card.addEventListener('click', function(e) {
                // Don't process if edit button was clicked
                if (e.target.closest('.edit-button')) {
                    return;
                }
                
                e.preventDefault();
                // Find the date from either the card or its edit button
                const editButton = this.querySelector('.edit-button');
                const date = editButton ? editButton.getAttribute('data-date') : null;
                
                if (!date) {
                    console.error("Could not find date for this card");
                    return;
                }
                
                setDateInSession(date, function() {
                    window.location.href = '{{ route('route-optimizer.index') }}';
                });
            });
        });
        
        // Update click handler for edit buttons
        document.querySelectorAll('.day-card .edit-button').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const date = this.getAttribute('data-date');
                
                if (!date || date === "undefined" || date === "") {
                    console.error("Missing date attribute:", this);
                    return;
                }
                
                // Set date in session and navigate to routes page
                setDateInSession(date, function() {
                    window.location.href = '{{ route('route-optimizer.index') }}';
                });
            });
        });
    });
</script>
@endsection 