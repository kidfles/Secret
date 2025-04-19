<?php

// Simple test script to compare the old and new calculation formulas

// Old formula: 30 + (2 × tegels)
function oldCalc($tegels) {
    return 30 + ($tegels * 2);
}

// New formula: 40 + (1.5 × tegels)
function newCalc($tegels) {
    return 40 + ceil($tegels * 1.5);
}

// Test with various tegels counts
$tegelCounts = [0, 10, 20, 30, 40, 50, 60];

echo "Comparison of Old vs New Completion Time Calculation:\n";
echo str_repeat('-', 60) . "\n";
echo sprintf("%-10s %-20s %-20s %-10s\n", 
    "Tegels", "Old (30 + 2×tegels)", "New (40 + 1.5×tegels)", "Difference"
);
echo str_repeat('-', 60) . "\n";

foreach ($tegelCounts as $count) {
    $old = oldCalc($count);
    $new = newCalc($count);
    $diff = $new - $old;
    
    echo sprintf("%-10s %-20s %-20s %-10s\n", 
        $count, 
        "$old minutes", 
        "$new minutes", 
        "$diff minutes"
    );
} 