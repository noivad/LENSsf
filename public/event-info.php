<?php

declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/managers/EventManager.php';
require __DIR__ . '/../includes/managers/VenueManager.php';

if (file_exists(__DIR__ . '/../config.php')) {
    require __DIR__ . '/../config.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Local Event Network Service');
}

$siteName = SITE_NAME;
$currentUser = $_SESSION['current_user'] ?? 'Demo User';
$uploadDir = defined('UPLOAD_DIR') ? rtrim((string) UPLOAD_DIR, '/') : __DIR__ . '/uploads';

$pdo = Database::connect();
$eventManager = new EventManager($pdo, $uploadDir);
$venueManager = new VenueManager($pdo, $uploadDir);

$eventTitle = $_GET['event'] ?? '';
$activeTab = $_GET['tab'] ?? 'details';

$event = null;
$venue = null;

if ($eventTitle) {
    $allEvents = $eventManager->all();
    foreach ($allEvents as $e) {
        if ($e['title'] === $eventTitle) {
            $event = $e;
            break;
        }
    }
}

if ($event && !empty($event['venue_id'])) {
    $venue = $venueManager->findById((int) $event['venue_id']);
}

if (!$event) {
    header('Location: event-list.php');
    exit;
}

$isCreator = strtolower($currentUser) === strtolower($event['owner'] ?? '');
$isDeputy = false;
if (!empty($event['deputies'])) {
    foreach ($event['deputies'] as $deputy) {
        if (strtolower($currentUser) === strtolower($deputy)) {
            $isDeputy = true;
            break;
        }
    }
}
$hasEditAccess = $isCreator || $isDeputy;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LENSsf::<?= e($event['title']) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/event-info.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
</head>
<body data-theme="light">
    <header>
        <div class="container">
            <h1><a href="index.php"><?= e($siteName) ?></a></h1>
            <nav>
                <a href="calendar-home.php">Home</a>
                <a href="event-list.php">Events</a>
                <a href="calendar-7x5.php">Calendar</a>
                <a href="venue-info.php">Venues</a>
                <a href="tags.php">Tags</a>
                <a href="account.php">Account</a>
                <a href="add-event.php">Add Event</a>
                <button class="theme-toggle" onclick="toggleTheme()" style="background: var(--primary-color); color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.5rem; cursor: pointer;">
                    <span id="theme-icon">🌙</span>
                </button>
            </nav>
        </div>
    </header>

    <main class="container">
        <section class="card">
            <div class="event-info-header">
                <div class="event-info-main">
                    <h1 class="event-name"><?= e($event['title']) ?></h1>
                    
                    <div class="event-details-grid">
                        <div class="event-meta-info">
                            <div class="event-info-item">
                                <strong>📅 Date:</strong> <?= format_date($event['event_date']) ?>
                            </div>
                            <?php if (!empty($event['start_time'])): ?>
                                <div class="event-info-item">
                                    <strong>🕐 Time:</strong> <?= format_time($event['start_time']) ?>
                                    <?php if (!empty($event['end_time'])): ?>
                                        - <?= format_time($event['end_time']) ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($venue): ?>
                                <div class="event-info-item">
                                    <strong>📍 Location:</strong> 
                                    <a href="venue-detail.php?venue=<?= urlencode($venue['name']) ?>">
                                        <?= e($venue['name']) ?>
                                    </a>
                                    <?php if (!empty($venue['address'])): ?>
                                        <br><span class="venue-address"><?= e($venue['address']) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="event-info-item">
                                <strong>👤 Organizer:</strong> <?= e($event['owner']) ?>
                            </div>
                        </div>
                        
                        <?php if ($venue && !empty($venue['address'])): ?>
                            <div class="event-map-container">
                                <div id="event-map" class="event-map"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="event-submenu">
                <a href="?event=<?= urlencode($eventTitle) ?>&tab=details" class="submenu-item <?= $activeTab === 'details' ? 'active' : '' ?>">
                    Details
                </a>
                <a href="?event=<?= urlencode($eventTitle) ?>&tab=deputies" class="submenu-item <?= $activeTab === 'deputies' ? 'active' : '' ?>">
                    Event Deputies
                </a>
            </div>

            <?php if ($activeTab === 'details'): ?>
                <div class="event-content">
                    <?php if (!empty($event['description'])): ?>
                        <div class="event-description">
                            <h3>About this Event</h3>
                            <p><?= nl2br(e($event['description'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($event['tags'])): ?>
                        <div class="event-tags-section">
                            <h3>Tags</h3>
                            <div class="event-tags">
                                <?php foreach ($event['tags'] as $tag): ?>
                                    <a href="event-list.php?tags=<?= urlencode(strtolower($tag)) ?>" class="event-tag">
                                        #<?= e(strtolower($tag)) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($hasEditAccess): ?>
                        <div class="event-actions">
                            <a href="add-event.php?edit=<?= urlencode($event['title']) ?>" class="button">
                                ✏️ Edit Event
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($activeTab === 'deputies'): ?>
                <div class="event-content">
                    <h3>Event Deputies</h3>
                    <p class="info-text">
                        Deputies can help manage this event, including editing event details and managing attendees.
                    </p>
                    
                    <?php if (!empty($event['deputies'])): ?>
                        <ul class="deputies-list">
                            <?php foreach ($event['deputies'] as $deputy): ?>
                                <li class="deputy-item">
                                    <span class="deputy-icon">👤</span>
                                    <span class="deputy-name"><?= e($deputy) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="no-deputies">No deputies assigned to this event.</p>
                    <?php endif; ?>
                    
                    <?php if ($isCreator): ?>
                        <div class="event-actions">
                            <a href="add-event.php?edit=<?= urlencode($event['title']) ?>" class="button">
                                Manage Deputies
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= e($siteName) ?></p>
        </div>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        window.__EVENT_LOCATION__ = <?= json_encode([
            'name' => $venue['name'] ?? '',
            'address' => $venue['address'] ?? '',
            'city' => $venue['city'] ?? '',
            'state' => $venue['state'] ?? ''
        ], JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="js/event-info.js"></script>
</body>
</html>
