#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../includes/db.php';

if (file_exists(__DIR__ . '/../config.php')) {
    require __DIR__ . '/../config.php';
}

echo "Running database migrations...\n\n";

try {
    $pdo = Database::connect();
    echo "✓ Database connected successfully\n\n";
    
    $migrationDir = __DIR__ . '/migrations';
    if (!is_dir($migrationDir)) {
        echo "No migrations directory found.\n";
        exit(0);
    }
    
    $files = glob($migrationDir . '/*.sql');
    sort($files);
    
    foreach ($files as $file) {
        $filename = basename($file);
        echo "Running migration: $filename\n";
        
        $sql = file_get_contents($file);
        if ($sql === false) {
            echo "  ✗ Failed to read file\n";
            continue;
        }
        
        try {
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
                if ($statement !== '') {
                    $pdo->exec($statement);
                }
            }
            echo "  ✓ Success\n";
        } catch (PDOException $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✓ All migrations completed\n";
} catch (Throwable $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
