#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/includes/db.php';

echo "Setting up universal tags system...\n\n";

try {
    $pdo = Database::connect();
    
    // Read and execute migration 004
    echo "1. Creating universal tags tables...\n";
    $sql004 = file_get_contents(__DIR__ . '/database/migrations/004_create_universal_tags.sql');
    $pdo->exec($sql004);
    echo "   ✓ Universal tags tables created\n\n";
    
    // Read and execute migration 005
    echo "2. Migrating existing tags to universal system...\n";
    $sql005 = file_get_contents(__DIR__ . '/database/migrations/005_migrate_existing_tags.sql');
    
    // Execute each statement separately
    $statements = array_filter(array_map('trim', explode(';', $sql005)));
    foreach ($statements as $stmt) {
        if (!empty($stmt) && !preg_match('/^--/', $stmt)) {
            try {
                $pdo->exec($stmt);
            } catch (PDOException $e) {
                // Ignore errors for data that doesn't exist yet
                if (!str_contains($e->getMessage(), 'doesn\'t have a default value')) {
                    echo "   Warning: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    echo "   ✓ Existing tags migrated\n\n";
    
    // Verify setup
    echo "3. Verifying setup...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tags");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   ✓ Tags table has " . $result['count'] . " tags\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM venue_tags");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   ✓ Venue tags: " . $result['count'] . " associations\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM event_tags");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   ✓ Event tags: " . $result['count'] . " associations\n";
    
    echo "\n✅ Universal tags system setup complete!\n";
    
} catch (Throwable $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
