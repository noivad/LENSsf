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

$filterTags = [];
if (isset($_GET['tags']) && !empty($_GET['tags'])) {
    $filterTags = array_map('trim', explode(',', (string) $_GET['tags']));
    $filterTags = array_map('strtolower', array_filter($filterTags));
}

$today = new DateTimeImmutable('today');
$oneMonthLater = $today->modify('+1 month');

$filteredEvents = array_filter($allEvents, function ($event) use ($today, $oneMonthLater, $filterTags) {
    if (empty($event['event_date'])) {
        return false;
    }

    $eventDate = DateTimeImmutable::createFromFormat('Y-m-d', $event['event_date']);
    if (!$eventDate || $eventDate < $today) {
        return false;
    }

    if (!empty($filterTags)) {
        $eventTags = array_map('strtolower', $event['tags'] ?? []);
        $hasMatchingTag = false;
        foreach ($filterTags as $filterTag) {
            if (in_array($filterTag, $eventTags, true)) {
                $hasMatchingTag = true;
                break;
            }
        }
        if (!$hasMatchingTag) {
            return false;
        }
    }

    return true;
});

usort($filteredEvents, function ($a, $b) {
    $dateA = $a['event_date'] ?? '';
    $dateB = $b['event_date'] ?? '';
    if ($dateA === $dateB) {
        return 0;
    }
    return $dateA < $dateB ? -1 : 1;
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - <?= e($siteName) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/event-list.css">
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="index.php"><?= e($siteName) ?></a></h1>
            <nav>
                <a href="index.php">Home</a>
                <a href="event-list.php" class="active">Events</a>
                <a href="calendar-7x5.php">Calendar</a>
                <a href="venue-info.php">Venues</a>
                <a href="tags.php">Tags</a>
                <a href="account.php">Account</a>
                <a href="add-event.php">Add Event</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php foreach (get_flashes() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endforeach; ?>

        <section class="card">
            <h2>Upcoming Events</h2>

            <?php if (!empty($filterTags)): ?>
                <div class="filter-bar">
                    <div class="active-filters">
                        <strong>Filtering by tags:</strong>
                        <?php foreach ($filterTags as $tag): ?>
                            <div class="filter-badge">
                                #<?= e($tag) ?>
                                <button onclick="removeTagFilter('<?= e($tag) ?>')" aria-label="Remove filter">&times;</button>
                            </div>
                        <?php endforeach; ?>
                        <a href="event-list.php" class="button-small">Clear All Filters</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($filteredEvents)): ?>
                <div class="no-events">
                    <p><strong>No upcoming events found.</strong></p>
                    <?php if (!empty($filterTags)): ?>
                        <p>Try <a href="event-list.php">clearing your filters</a> or <a href="tags.php">browse all tags</a>.</p>
                    <?php else: ?>
                        <p>Check back soon or <a href="add-event.php">create an event</a>!</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="events-list">
                    <?php foreach ($filteredEvents as $event): ?>
                        <div class="event-card">
                            <div class="event-header">
                                <div style="flex: 1;">
                                    <h3 class="event-title"><?= e($event['title']) ?></h3>
                                    
                                    <?php if (!empty($event['tags'])): ?>
                                        <div class="event-tags">
                                            <?php foreach ($event['tags'] as $tag): ?>
                                                <a href="event-list.php?tags=<?= urlencode(strtolower($tag)) ?>" class="tag-badge">
                                                    #<?= e(strtolower($tag)) ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($event['description'])): ?>
                                        <div class="event-description"><?= e($event['description']) ?></div>
                                    <?php endif; ?>

                                    <div class="event-meta">
                                        <span><strong>Date:</strong> <?= format_date($event['event_date']) ?></span>
                                        <?php if (!empty($event['start_time'])): ?>
                                            <span><strong>Time:</strong> <?= format_time($event['start_time']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($event['venue_name'])): ?>
                                            <span><strong>Venue:</strong> <?= e($event['venue_name']) ?></span>
                                        <?php endif; ?>
                                        <span><strong>By:</strong> <?= e($event['owner']) ?></span>
                                    </div>
                                </div>
                                <?php if (!empty($event['image'])): ?>
                                    <img src="uploads/<?= e($event['image']) ?>" alt="<?= e($event['title']) ?>" class="event-image">
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= e($siteName) ?></p>
        </div>
    </footer>

    <script>
        window.__CURRENT_FILTER_TAGS__ = <?= json_encode($filterTags) ?>;
    </script>
    <script src="js/event-list.js"></script>
</body>
</html>
