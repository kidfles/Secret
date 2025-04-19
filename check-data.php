<?php

// Get database connection details from the .env file
$envFile = file_get_contents('.env');
preg_match('/DB_DATABASE=(.*)/', $envFile, $matches);
$dbPath = trim($matches[1]);

// Create connection to the database
try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get location data
    echo "Locations with new fields:\n";
    echo str_repeat('-', 80) . "\n";
    
    $stmt = $pdo->query("SELECT id, name, tegels, begin_time, end_time, completion_minutes FROM locations LIMIT 5");
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($locations) === 0) {
        echo "No locations found\n";
    } else {
        // Print table header
        echo sprintf("%-3s %-20s %-7s %-12s %-12s %-12s\n", 
            "ID", "Name", "Tegels", "Begin Time", "End Time", "Completion Min"
        );
        echo str_repeat('-', 80) . "\n";
        
        foreach ($locations as $location) {
            echo sprintf("%-3s %-20s %-7s %-12s %-12s %-12s\n", 
                $location['id'], 
                substr($location['name'], 0, 20), 
                $location['tegels'], 
                $location['begin_time'], 
                $location['end_time'], 
                $location['completion_minutes']
            );
        }
    }
    
    // Get route data with time information
    echo "\n\nRoutes with start_time:\n";
    echo str_repeat('-', 80) . "\n";
    
    $stmt = $pdo->query("SELECT id, name, start_time FROM routes LIMIT 5");
    $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($routes) === 0) {
        echo "No routes found\n";
    } else {
        // Print table header
        echo sprintf("%-3s %-30s %-12s\n", 
            "ID", "Name", "Start Time"
        );
        echo str_repeat('-', 80) . "\n";
        
        foreach ($routes as $route) {
            echo sprintf("%-3s %-30s %-12s\n", 
                $route['id'], 
                substr($route['name'], 0, 30), 
                $route['start_time']
            );
        }
    }
    
    // Get route_location data with time information
    echo "\n\nRoute Location with timing info:\n";
    echo str_repeat('-', 80) . "\n";
    
    $stmt = $pdo->query("SELECT route_id, location_id, `order`, arrival_time, completion_time, travel_time 
                         FROM route_location ORDER BY route_id, `order` LIMIT 10");
    $routeLocations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($routeLocations) === 0) {
        echo "No route locations found\n";
    } else {
        // Print table header
        echo sprintf("%-3s %-3s %-3s %-12s %-12s %-12s\n", 
            "R#", "L#", "Ord", "Arrival", "Completion", "Travel Min"
        );
        echo str_repeat('-', 80) . "\n";
        
        foreach ($routeLocations as $rl) {
            echo sprintf("%-3s %-3s %-3s %-12s %-12s %-12s\n", 
                $rl['route_id'], 
                $rl['location_id'], 
                $rl['order'], 
                $rl['arrival_time'], 
                $rl['completion_time'], 
                $rl['travel_time']
            );
        }
    }
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
} 