<?php
declare(strict_types=1);

$venueName = $_GET['venue'] ?? '';

// Mock data for venues and their events
$venues = [
    'Blue Note Club' => [
        'name' => 'Blue Note Club',
        'address' => '123 Jazz Street, San Francisco, CA 94102',
        'description' => 'A legendary jazz club featuring the finest local and international talent. Intimate setting with excellent acoustics and a full bar.',
        'image' => 'https://picsum.photos/seed/bluenote/600/400',
        'tags' => ['jazz', 'music', 'live', 'nightlife', 'bar'],
        'lat' => 37.7749,
        'lng' => -122.4194,
    ],
    'Modern Art Gallery' => [
        'name' => 'Modern Art Gallery',
        'address' => '456 Art Avenue, San Francisco, CA 94103',
        'description' => 'Contemporary art space showcasing cutting-edge exhibitions from emerging and established artists. Features rotating collections and special events.',
        'image' => 'https://picsum.photos/seed/modernart/600/400',
        'tags' => ['art', 'gallery', 'contemporary', 'culture', 'exhibitions'],
        'lat' => 37.7739,
        'lng' => -122.4212,
    ],
    "Tony's Pizzeria" => [
        'name' => "Tony's Pizzeria",
        'address' => '789 Food Street, San Francisco, CA 94104',
        'description' => 'Family-owned pizzeria serving authentic Neapolitan pizza with locally sourced ingredients. Community gathering spot with a warm atmosphere.',
        'image' => 'https://picsum.photos/seed/tonys/600/400',
        'tags' => ['food', 'pizza', 'italian', 'restaurant', 'community'],
        'lat' => 37.7729,
        'lng' => -122.4230,
    ],
    'Central Convention Center' => [
        'name' => 'Central Convention Center',
        'address' => '321 Convention Drive, San Francisco, CA 94105',
        'description' => 'State-of-the-art convention facility hosting conferences, trade shows, and major events. Multiple halls with flexible configurations.',
        'image' => 'https://picsum.photos/seed/convention/600/400',
        'tags' => ['convention', 'conference', 'business', 'events', 'tech'],
        'lat' => 37.7719,
        'lng' => -122.4248,
    ],
    'City Theater' => [
        'name' => 'City Theater',
        'address' => '555 Theater Row, San Francisco, CA 94106',
        'description' => 'Historic theater presenting plays, musicals, and performing arts. Beautiful restored venue with excellent sightlines and acoustics.',
        'image' => 'https://picsum.photos/seed/theater/600/400',
        'tags' => ['theater', 'performance', 'arts', 'culture', 'entertainment'],
        'lat' => 37.7709,
        'lng' => -122.4266,
    ],
    'Eon City Park' => [
        'name' => 'Eon City Park',
        'address' => '1000 Park Boulevard, San Francisco, CA 94107',
        'description' => 'Expansive urban park perfect for outdoor events, sports, and community gatherings. Features scenic trails and open spaces.',
        'image' => 'https://picsum.photos/seed/park/600/400',
        'tags' => ['outdoor', 'park', 'sports', 'nature', 'recreation'],
        'lat' => 37.7699,
        'lng' => -122.4284,
    ],
    'Community Center' => [
        'name' => 'Community Center',
        'address' => '888 Community Lane, San Francisco, CA 94108',
        'description' => 'Multi-purpose community facility hosting events, classes, and social gatherings. Welcoming space for all ages.',
        'image' => 'https://picsum.photos/seed/community/600/400',
        'tags' => ['community', 'events', 'social', 'education', 'family'],
        'lat' => 37.7689,
        'lng' => -122.4302,
    ],
];

$venue = $venues[$venueName] ?? null;

if (!$venue) {
    $venue = [
        'name' => htmlspecialchars($venueName, ENT_QUOTES),
        'address' => 'San Francisco, CA',
        'description' => 'A great venue in San Francisco.',
        'image' => 'https://picsum.photos/seed/' . rawurlencode($venueName) . '/600/400',
        'tags' => ['venue', 'events'],
        'lat' => 37.7749,
        'lng' => -122.4194,
    ];
}

// All events from calendar
$allEvents = [
    [
        'title' => 'Jazz Night',
        'venue' => 'Blue Note Club',
        'date' => '2025-10-05',
        'start_time' => '19:00',
        'end_time' => '23:00',
        'status' => 'past',
        'description' => 'An evening of improvisation featuring local legends.',
        'tags' => ['jazz','music','live']
    ],
    [
        'title' => 'Art Exhibition Opening',
        'venue' => 'Modern Art Gallery',
        'date' => '2025-10-15',
        'start_time' => '18:00',
        'end_time' => '21:00',
        'status' => 'upcoming',
        'description' => 'Celebrate the launch of the "Lightscapes" collection with the artists.',
        'tags' => ['art','gallery','opening']
    ],
    [
        'title' => 'Pizza Party',
        'venue' => "Tony's Pizzeria",
        'date' => '2025-10-15',
        'start_time' => '19:30',
        'end_time' => '22:00',
        'status' => 'upcoming',
        'description' => 'Community-organized meetup to celebrate the fall menu launch.',
        'tags' => ['food','pizza','community']
    ],
    [
        'title' => 'Tech Conference',
        'venue' => 'Central Convention Center',
        'date' => '2025-10-17',
        'start_time' => '09:00',
        'end_time' => '18:00',
        'status' => 'happening',
        'description' => 'Keynotes on emerging AI systems plus hands-on futuristic demos.',
        'tags' => ['tech','conference','ai']
    ],
    [
        'title' => 'Theater Performance',
        'venue' => 'City Theater',
        'date' => '2025-10-20',
        'start_time' => '20:00',
        'end_time' => '22:30',
        'status' => 'upcoming',
        'description' => 'Premiere of the sci-fi stage play "Echoes of Tomorrow".',
        'tags' => ['theater','performance','premiere']
    ],
    [
        'title' => 'Marathon Event',
        'venue' => 'Eon City Park',
        'date' => '2025-10-25',
        'start_time' => '06:00',
        'end_time' => '12:00',
        'status' => 'upcoming',
        'description' => 'City-wide marathon following the Skyline Nebula route.',
        'tags' => ['sports','marathon','outdoor']
    ],
    [
        'title' => 'Halloween Party',
        'venue' => 'Community Center',
        'date' => '2025-10-28',
        'start_time' => '19:00',
        'end_time' => '00:00',
        'status' => 'upcoming',
        'description' => 'Costumes, synthwave DJs, and an augmented reality haunted maze.',
        'tags' => ['halloween','party','costumes']
    ],
    [
        'title' => 'Summer Jazz Festival',
        'venue' => 'Blue Note Club',
        'date' => '2025-08-15',
        'start_time' => '18:00',
        'end_time' => '23:00',
        'status' => 'past',
        'description' => 'Three-day jazz extravaganza with performers from around the world.',
        'tags' => ['jazz','music','festival']
    ],
    [
        'title' => 'Contemporary Sculpture Exhibit',
        'venue' => 'Modern Art Gallery',
        'date' => '2025-09-01',
        'start_time' => '10:00',
        'end_time' => '18:00',
        'status' => 'past',
        'description' => 'Exploring modern forms and materials in sculpture.',
        'tags' => ['art','sculpture','contemporary']
    ],
];

// Filter events for this venue
$venueEvents = array_filter($allEvents, function($e) use ($venueName) {
    return $e['venue'] === $venueName;
});

// Separate upcoming and past events
$upcomingEvents = array_filter($venueEvents, function($e) {
    return in_array($e['status'], ['upcoming', 'happening'], true);
});

$pastEvents = array_filter($venueEvents, function($e) {
    return $e['status'] === 'past';
});

// Sort by date
usort($upcomingEvents, function($a, $b) {
    return strcmp($a['date'], $b['date']);
});

usort($pastEvents, function($a, $b) {
    return strcmp($b['date'], $a['date']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($venue['name'], ENT_QUOTES); ?> - LENS</title>
    <link rel="stylesheet" href="css/calendar-7x5.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <style>
        .venue-hero{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem}
        .venue-image{width:100%;aspect-ratio:3/2;object-fit:cover;border-radius:18px;border:2px solid var(--border-color)}
        .venue-map{width:100%;aspect-ratio:3/2;border-radius:18px;border:2px solid var(--border-color)}
        .venue-name{font-size:2.5rem;font-weight:700;margin:1rem 0;color:var(--accent-cyan)}
        .venue-info-section{background:var(--bg-secondary);border-radius:18px;border:1px solid var(--border-color);padding:1.5rem;margin-bottom:1.5rem}
        .venue-description{font-size:1.1rem;line-height:1.7;color:var(--text-secondary);margin-bottom:1rem}
        .venue-address{font-size:1rem;color:var(--text-secondary);margin-bottom:1rem}
        .tag-chips{display:flex;flex-wrap:wrap;gap:0.35rem;margin-top:0.4rem}
        .tag-chip{background:var(--bg-tertiary);border:1px solid var(--border-color);color:var(--text-primary);padding:0.15rem 0.5rem;border-radius:8px;font-size:0.78rem}
        .section-title{font-size:1.8rem;font-weight:600;margin:2rem 0 1rem;color:var(--text-primary)}
        .events-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.5rem}
        .event-card{background:var(--bg-secondary);border-radius:18px;border:1px solid var(--border-color);overflow:hidden;transition:all 0.25s ease}
        .event-card:hover{transform:translateY(-4px);box-shadow:0 0 24px var(--hover-glow);border-color:var(--accent-cyan)}
        .event-card-image{width:100%;height:180px;object-fit:cover}
        .event-card-body{padding:1rem}
        .event-card-title{font-size:1.2rem;font-weight:600;color:var(--accent-cyan);margin-bottom:0.5rem}
        .event-card-detail{font-size:0.9rem;color:var(--text-secondary);margin-bottom:0.3rem}
        .past-events-photos{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem}
        .photo-card{position:relative;aspect-ratio:1;overflow:hidden;border-radius:12px;border:1px solid var(--border-color);cursor:pointer;transition:all 0.25s ease}
        .photo-card:hover{transform:scale(1.05);box-shadow:0 0 18px var(--hover-glow)}
        .photo-card img{width:100%;height:100%;object-fit:cover}
        .photo-overlay{position:absolute;bottom:0;left:0;right:0;background:linear-gradient(to top,rgba(0,0,0,0.8),transparent);padding:0.8rem;color:white;font-size:0.85rem}
        @media(max-width:768px){.venue-hero{grid-template-columns:1fr}}
    </style>
</head>
<body data-theme="dark">
    <div class="app-container">
        <nav class="sidebar-nav">
            <div class="nav-logo">LENS</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link">
                        <span class="nav-icon">🏠</span>
                        <span>Home</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="calendar-7x5.php" class="nav-link">
                        <span class="nav-icon">📅</span>
                        <span>Calendar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="events-list-add-info.php" class="nav-link">
                        <span class="nav-icon">🎉</span>
                        <span>Events</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="venue-info.php" class="nav-link active">
                        <span class="nav-icon">📍</span>
                        <span>Venues</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?page=photos" class="nav-link">
                        <span class="nav-icon">📸</span>
                        <span>Photos</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="shared.html" class="nav-link">
                        <span class="nav-icon">🏷️</span>
                        <span>Tags</span>
                    </a>
                </li>
            </ul>
        </nav>

        <header class="top-header">
            <h1 class="header-title">Venue Info</h1>
            <div class="user-controls">
                <button class="theme-toggle" onclick="toggleTheme()">
                    <span id="theme-icon">☀️</span> Toggle Theme
                </button>
                <div class="user-profile">
                    <img src="https://i.pravatar.cc/150?img=33" alt="User Avatar" class="user-avatar" onclick="toggleUserDropdown()">
                    <div class="user-dropdown" id="userDropdown">
                        <a href="account.html" class="dropdown-item" style="text-decoration: none; color: inherit; display: block;">
                            📧 Contact Info
                        </a>
                        <a href="account.html?tab=notifications" class="dropdown-item" style="text-decoration: none; color: inherit; display: block;">
                            🔔 Notifications
                        </a>
                        <a href="account-settings.html" class="dropdown-item" style="text-decoration: none; color: inherit; display: block;">
                            ⚙️ Account Info
                        </a>
                        <a href="account-events.html" class="dropdown-item" style="text-decoration: none; color: inherit; display: block;">
                            📜 My Past Events
                        </a>
                        <div class="dropdown-item" onclick="alert('Logging out...')">
                            🚪 Logout
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="calendar-wrapper">
                <div class="venue-hero">
                    <img src="<?php echo htmlspecialchars($venue['image'], ENT_QUOTES); ?>" alt="<?php echo htmlspecialchars($venue['name'], ENT_QUOTES); ?>" class="venue-image">
                    <div id="venueMap" class="venue-map"></div>
                </div>

                <h2 class="venue-name"><?php echo htmlspecialchars($venue['name'], ENT_QUOTES); ?></h2>

                <div class="venue-info-section">
                    <div class="venue-address">📍 <?php echo htmlspecialchars($venue['address'], ENT_QUOTES); ?></div>
                    <div class="venue-description"><?php echo htmlspecialchars($venue['description'], ENT_QUOTES); ?></div>
                    <div class="tag-chips">
                        <?php foreach ($venue['tags'] as $tag): ?>
                            <span class="tag-chip">#<?php echo htmlspecialchars($tag, ENT_QUOTES); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if (!empty($upcomingEvents)): ?>
                    <h3 class="section-title">🚀 Upcoming Events at <?php echo htmlspecialchars($venue['name'], ENT_QUOTES); ?></h3>
                    <div class="events-grid">
                        <?php foreach ($upcomingEvents as $event): ?>
                            <div class="event-card">
                                <img src="https://picsum.photos/seed/<?php echo rawurlencode($event['title']); ?>/400/300" alt="<?php echo htmlspecialchars($event['title'], ENT_QUOTES); ?>" class="event-card-image">
                                <div class="event-card-body">
                                    <h4 class="event-card-title">
                                        <a href="events-list-add-info.php?event=<?php echo urlencode($event['title']); ?>" style="color: inherit; text-decoration: none;">
                                            <?php echo htmlspecialchars($event['title'], ENT_QUOTES); ?>
                                        </a>
                                    </h4>
                                    <div class="event-card-detail">📅 <?php echo htmlspecialchars(date('F j, Y', strtotime($event['date'])), ENT_QUOTES); ?></div>
                                    <div class="event-card-detail">🕐 <?php echo htmlspecialchars($event['start_time'] . ' - ' . $event['end_time'], ENT_QUOTES); ?></div>
                                    <?php if (!empty($event['description'])): ?>
                                        <div class="event-card-detail" style="margin-top: 0.5rem;"><?php echo htmlspecialchars($event['description'], ENT_QUOTES); ?></div>
                                    <?php endif; ?>
                                    <div class="tag-chips" style="margin-top: 0.5rem;">
                                        <?php foreach ($event['tags'] as $tag): ?>
                                            <span class="tag-chip">#<?php echo htmlspecialchars($tag, ENT_QUOTES); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($pastEvents)): ?>
                    <h3 class="section-title">📸 Past Events at <?php echo htmlspecialchars($venue['name'], ENT_QUOTES); ?></h3>
                    <div class="past-events-photos">
                        <?php foreach ($pastEvents as $event): ?>
                            <div class="photo-card">
                                <img src="https://picsum.photos/seed/<?php echo rawurlencode($event['title']); ?>/400/400" alt="<?php echo htmlspecialchars($event['title'], ENT_QUOTES); ?>">
                                <div class="photo-overlay">
                                    <strong><?php echo htmlspecialchars($event['title'], ENT_QUOTES); ?></strong><br>
                                    <?php echo htmlspecialchars(date('M j, Y', strtotime($event['date'])), ENT_QUOTES); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($upcomingEvents) && empty($pastEvents)): ?>
                    <div class="venue-info-section">
                        <p style="text-align: center; color: var(--text-secondary);">No events scheduled at this venue yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <footer class="footer">
            <p>© <?php echo date('Y'); ?> LENS - Local Event Network Service | Built with ❤️ for the community</p>
        </footer>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        const venueData = <?php echo json_encode($venue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        
        // Initialize map
        const map = L.map('venueMap').setView([venueData.lat, venueData.lng], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
        L.marker([venueData.lat, venueData.lng]).addTo(map)
            .bindPopup('<strong>' + venueData.name + '</strong><br>' + venueData.address)
            .openPopup();

        // Theme toggle
        function toggleTheme() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            body.setAttribute('data-theme', newTheme);
            document.getElementById('theme-icon').textContent = newTheme === 'dark' ? '☀️' : '🌙';
        }

        // User dropdown
        function toggleUserDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('active');
        }

        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('userDropdown');
            const avatar = document.querySelector('.user-avatar');
            if (!dropdown.contains(e.target) && e.target !== avatar) {
                dropdown.classList.remove('active');
            }
        });
    </script>
</body>
</html>
