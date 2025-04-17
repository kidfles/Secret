<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Redis;

try {
    Redis::set('test_key', 'Hello Redis!');
    $value = Redis::get('test_key');
    echo "Redis connection successful! Value: " . $value . "\n";
} catch (\Exception $e) {
    echo "Redis connection failed: " . $e->getMessage() . "\n";
} 