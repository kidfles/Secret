<?php

// Output environment details
echo "Starting script...\n";

// Get database connection details from the .env file
$envFile = file_get_contents('.env');
echo "Reading .env file...\n";
preg_match('/DB_DATABASE=(.*)/', $envFile, $matches);

if (empty($matches)) {
    echo "Error: Could not find DB_DATABASE in .env file\n";
    echo "ENV file content (partial):\n";
    echo substr($envFile, 0, 500) . "...\n";
    exit(1);
}

$dbPath = trim($matches[1]);
echo "Database path found: $dbPath\n";

// Create connection to the database
try {
    echo "Connecting to database...\n";
    
    // Check if file exists
    if (!file_exists($dbPath)) {
        echo "Error: Database file does not exist at: $dbPath\n";
        exit(1);
    }
    
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to database successfully\n";
    
    // List all tables
    echo "Listing all tables:\n";
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo " - $table\n";
    }
    
    // Get schema info for locations table
    echo "\nGetting schema for locations table...\n";
    $stmt = $pdo->query("PRAGMA table_info(locations)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($columns)) {
        echo "No columns found in locations table\n";
        exit(1);
    }
    
    echo "Columns in the locations table:\n";
    echo str_repeat('-', 80) . "\n";
    echo sprintf("%-5s %-25s %-15s %-10s %-10s\n", "cid", "name", "type", "notnull", "dflt_value");
    echo str_repeat('-', 80) . "\n";
    
    foreach ($columns as $column) {
        echo sprintf("%-5s %-25s %-15s %-10s %-10s\n", 
            $column['cid'], 
            $column['name'], 
            $column['type'], 
            $column['notnull'] ? 'NOT NULL' : 'NULL', 
            $column['dflt_value'] ?? 'NULL'
        );
    }
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
} 