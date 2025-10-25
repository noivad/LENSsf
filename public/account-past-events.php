<?php

declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/managers/EventManager.php';

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

$allEvents = $eventManager->all();
$today = new DateTimeImmutable('today');

$pastEvents = array_filter($allEvents, function ($event) use ($currentUser, $today) {
    if (strcasecmp($event['owner'], $currentUser) !== 0) {
        return false;
    }
    if (empty($event['event_date'])) {
        return false;
    }
    $eventDate = DateTimeImmutable::createFromFormat('Y-m-d', $event['event_date']);
    return $eventDate && $eventDate < $today;
});

usort($pastEvents, function ($a, $b) {
    return strcmp($b['event_date'] ?? '', $a['event_date'] ?? '');
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LENSsf::My Past Events</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/account.css">
    <link rel="stylesheet" href="css/calendar-7x5.css">
</head>
<body data-theme="light">
    <div class="app-container">
        <nav class="sidebar-nav">
            <div class="nav-logo">LENS</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="calendar-7x5.php" class="nav-link">
                        <span class="nav-icon">🏠</span>
                        <span>Home</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="event-list.php" class="nav-link">
                        <span class="nav-icon">📋</span>
                        <span>Events</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="venue-info.php" class="nav-link">
                        <span class="nav-icon">📍</span>
                        <span>Venues</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="tags.php" class="nav-link">
                        <span class="nav-icon">🏷️</span>
                        <span>Tags</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="add-event.php" class="nav-link">
                        <span class="nav-icon">➕</span>
                        <span>Add Event</span>
                    </a>
                </li>
            </ul>
        </nav>

        <header class="top-header">
            <h1 class="header-title">LENSsf - My Past Events</h1>
            <div class="user-controls">
                <button class="theme-toggle" onclick="toggleTheme()">
                    <span id="theme-icon">☀️</span> Toggle Theme
                </button>
                <div class="user-profile">
                    <img src="https://i.pravatar.cc/150?img=33" alt="User Avatar" class="user-avatar" onclick="toggleUserDropdown()">
                    <div class="user-dropdown" id="userDropdown">
                        <a href="account-contact.php" class="dropdown-item" style="text-decoration: none; color: inherit; display: block;">
                            📧 Contact Info
                        </a>
                        <a href="account-notifications.php" class="dropdown-item" style="text-decoration: none; color: inherit; display: block;">
                            🔔 Notifications
                        </a>
                        <a href="account.php" class="dropdown-item" style="text-decoration: none; color: inherit; display: block;">
                            ⚙️ Account Info
                        </a>
                        <a href="account-past-events.php" class="dropdown-item" style="text-decoration: none; color: inherit; display: block;">
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
            <div class="container">
                <h2>My Past Events</h2>

                <section class="card">
                    <?php if (empty($pastEvents)): ?>
                        <p class="subtle">You have no past events.</p>
                    <?php else: ?>
                        <div class="event-grid">
                            <?php foreach ($pastEvents as $event): ?>
                                <div class="event-card">
                                    <h4><?= e($event['title']) ?></h4>
                                    
                                    <?php if (!empty($event['image'])): ?>
                                        <img src="uploads/<?= e($event['image']) ?>" alt="<?= e($event['title']) ?>">
                                    <?php endif; ?>

                                    <?php if (!empty($event['description'])): ?>
                                        <p><?= e(substr($event['description'], 0, 100)) ?><?= strlen($event['description']) > 100 ? '...' : '' ?></p>
                                    <?php endif; ?>

                                    <div class="event-meta">
                                        <div><strong>Date:</strong> <?= format_date($event['event_date']) ?></div>
                                        <?php if (!empty($event['start_time'])): ?>
                                            <div><strong>Time:</strong> <?= format_time($event['start_time']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($event['venue_name'])): ?>
                                            <div><strong>Venue:</strong> <?= e($event['venue_name']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </main>

        <footer class="footer">
            <p>&copy; <?= date('Y') ?> LENSsf - Local Event Network Service | Built with ❤️ for the community</p>
        </footer>
    </div>

    <script src="js/main.js"></script>
    <script src="js/calendar-7x5.js"></script>
</body>
</html>
