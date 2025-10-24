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

$allTags = [];
foreach ($allEvents as $event) {
    if (!empty($event['tags'])) {
        foreach ($event['tags'] as $tag) {
            $tag = strtolower(trim($tag));
            if ($tag !== '') {
                if (!isset($allTags[$tag])) {
                    $allTags[$tag] = 0;
                }
                $allTags[$tag]++;
            }
        }
    }
}

ksort($allTags);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tags - <?= e($siteName) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/tags.css">
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="index.php"><?= e($siteName) ?></a></h1>
            <nav>
                <a href="index.php">Home</a>
                <a href="event-list.php">Events</a>
                <a href="calendar-7x5.php">Calendar</a>
                <a href="venue-info.php">Venues</a>
                <a href="tags.php" class="active">Tags</a>
                <a href="account.php">Account</a>
                <a href="add-event.php">Add Event</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <section class="card">
            <h2>Event Tags</h2>
            
            <div class="info-box">
                <strong>Search tags:</strong> Type #tag1, #tag2 (comma-separated) to search for specific tags. 
                Click any tag below to filter events by that tag.
            </div>

            <div class="search-box">
                <input 
                    type="text" 
                    id="tag-search" 
                    placeholder="Search tags with #tag syntax (e.g., #music, #art)" 
                    aria-label="Search tags"
                >
            </div>

            <div id="selected-tags-container" class="selected-tags" style="display: none;">
                <strong>Selected tags:</strong>
                <div id="selected-tags"></div>
                <button class="button-small" id="clear-filters">Clear All Filters</button>
            </div>

            <?php if (empty($allTags)): ?>
                <p class="subtle">No tags found. Tags will appear here once events are created with tags.</p>
            <?php else: ?>
                <div class="tags-grid" id="tags-grid">
                    <?php foreach ($allTags as $tag => $count): ?>
                        <div class="tag-card" data-tag="<?= e($tag) ?>" onclick="selectTag('<?= e($tag) ?>')">
                            <div class="tag-name">#<?= e($tag) ?></div>
                            <div class="tag-count"><?= e((string) $count) ?> event<?= $count !== 1 ? 's' : '' ?></div>
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
        window.__ALL_TAGS__ = <?= json_encode($allTags, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="js/tags.js"></script>
</body>
</html>
