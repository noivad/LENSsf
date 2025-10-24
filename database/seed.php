<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo = Database::connect();
    
    echo "Seeding database with sample data...\n\n";
    
    // Create a venue for Death Guild
    $stmt = $pdo->prepare("
        INSERT INTO venues (name, description, address, city, state, zip_code, owner_name, deputies, tags)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        'DNA Lounge',
        'San Francisco\'s premier nightclub featuring live music and themed events. Home to Death Guild, the longest-running goth/industrial club in North America.',
        '375 Eleventh Street',
        'San Francisco',
        'CA',
        '94103',
        'Jamie Zawinski',
        'Alex Rosenberg, Barry Threw',
        'goth,industrial,nightclub,music,dance'
    ]);
    
    $venueId = (int) $pdo->lastInsertId();
    echo "✓ Created venue: DNA Lounge (ID: $venueId)\n";
    
    // Create Death Guild event - recurring Mondays
    // Let's create events for the next 4 Mondays
    $today = new DateTime();
    $daysUntilMonday = (8 - (int) $today->format('N')) % 7;
    $nextMonday = clone $today;
    if ($daysUntilMonday > 0) {
        $nextMonday->modify("+{$daysUntilMonday} days");
    } else {
        // If today is Monday, use today
        if ((int) $today->format('N') !== 1) {
            $nextMonday->modify('+7 days');
        }
    }
    
    echo "\nCreating Death Guild events for upcoming Mondays:\n";
    
    for ($i = 0; $i < 4; $i++) {
        $eventDate = clone $nextMonday;
        $eventDate->modify("+{$i} weeks");
        
        $stmt = $pdo->prepare("
            INSERT INTO events (title, description, event_date, event_time, venue_id, owner_name, deputies, tags)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            'Death Guild',
            'Death Guild is the longest-running goth/industrial club in North America. Every Monday night we bring you the best in dark alternative music - from classic goth rock and industrial to modern darkwave and EBM. Three rooms of music, full bar, and a dance floor that never stops.',
            $eventDate->format('Y-m-d'),
            '21:30:00', // 9:30 PM in 24-hour format
            $venueId,
            'Jamie Zawinski',
            'Alex Rosenberg, Barry Threw',
            '#goth #industrial #DeathGuild'
        ]);
        
        $eventId = (int) $pdo->lastInsertId();
        echo "  ✓ Created event for " . $eventDate->format('l, F j, Y') . " at 9:30 PM (ID: $eventId)\n";
    }
    
    // Add some additional sample venues
    echo "\nCreating additional sample venues:\n";
    
    $sampleVenues = [
        [
            'name' => 'The Fillmore',
            'description' => 'Historic music venue in San Francisco',
            'address' => '1805 Geary Boulevard',
            'city' => 'San Francisco',
            'state' => 'CA',
            'zip_code' => '94115',
            'owner_name' => 'Live Nation',
            'deputies' => 'Sarah Chen, Mike Rodriguez',
            'tags' => 'music,concert,live,historic'
        ],
        [
            'name' => 'The Chapel',
            'description' => 'Intimate venue for live music and events in the Mission District',
            'address' => '777 Valencia Street',
            'city' => 'San Francisco',
            'state' => 'CA',
            'zip_code' => '94110',
            'owner_name' => 'Jack Knowles',
            'deputies' => 'Emma Stone, David Lee',
            'tags' => 'music,indie,mission,bar'
        ]
    ];
    
    foreach ($sampleVenues as $venue) {
        $stmt = $pdo->prepare("
            INSERT INTO venues (name, description, address, city, state, zip_code, owner_name, deputies, tags)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $venue['name'],
            $venue['description'],
            $venue['address'],
            $venue['city'],
            $venue['state'],
            $venue['zip_code'],
            $venue['owner_name'],
            $venue['deputies'],
            $venue['tags']
        ]);
        
        echo "  ✓ Created venue: {$venue['name']}\n";
    }
    
    echo "\n✅ Database seeding completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error seeding database: " . $e->getMessage() . "\n";
    exit(1);
}
