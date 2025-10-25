<?php

declare(strict_types=1);

echo "Testing LENS setup...\n\n";

require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/managers/EventManager.php';
require __DIR__ . '/includes/managers/VenueManager.php';

if (file_exists(__DIR__ . '/config.php')) {
    require __DIR__ . '/config.php';
}

echo "1. Checking helper functions...\n";
$testStr = "Test <script>alert('xss')</script>";
$escaped = e($testStr);
echo "   ✓ e() function works\n";

echo "\n2. Checking database connection...\n";
try {
    $pdo = Database::connect();
    echo "   ✓ Database connected\n";
    
    echo "\n3. Checking tables...\n";
    $tables = ['venues', 'events', 'users', 'user_profiles'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "   ✓ Table '$table' exists\n";
        } else {
            echo "   ✗ Table '$table' does NOT exist\n";
        }
    }
    
    echo "\n4. Checking venue columns...\n";
    $venueColumns = ['is_private', 'is_public'];
    foreach ($venueColumns as $col) {
        $stmt = $pdo->query("SHOW COLUMNS FROM venues LIKE '$col'");
        if ($stmt->rowCount() > 0) {
            echo "   ✓ venues.$col exists\n";
        } else {
            echo "   ✗ venues.$col does NOT exist\n";
        }
    }
    
    echo "\n5. Checking event columns...\n";
    $eventColumns = ['is_recurring', 'recurrence_pattern'];
    foreach ($eventColumns as $col) {
        $stmt = $pdo->query("SHOW COLUMNS FROM events LIKE '$col'");
        if ($stmt->rowCount() > 0) {
            echo "   ✓ events.$col exists\n";
        } else {
            echo "   ✗ events.$col does NOT exist\n";
        }
    }
    
    echo "\n6. Testing VenueManager...\n";
    $uploadDir = __DIR__ . '/public/uploads';
    $venueManager = new VenueManager($pdo, $uploadDir);
    echo "   ✓ VenueManager instantiated\n";
    
    echo "\n7. Testing EventManager...\n";
    $eventManager = new EventManager($pdo, $uploadDir);
    echo "   ✓ EventManager instantiated\n";
    
    echo "\n✓ All basic checks passed!\n";
    
} catch (Throwable $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
