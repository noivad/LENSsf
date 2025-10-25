<?php

declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/managers/VenueManager.php';
require __DIR__ . '/../includes/navigation.php';

if (file_exists(__DIR__ . '/../config.php')) {
    require __DIR__ . '/../config.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Local Event Network Service');
}

$uploadDir = defined('UPLOAD_DIR') ? rtrim((string) UPLOAD_DIR, '/') : __DIR__ . '/uploads';
$siteName = SITE_NAME;

try {
    $pdo = Database::connect();
} catch (Throwable $e) {
    $pdo = null;
}

$venueManager = $pdo ? new VenueManager($pdo, $uploadDir) : null;
$venues = $venueManager ? $venueManager->all() : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LENSsf::Venues</title>
    <link rel="stylesheet" href="css/calendar-7x5.css">
</head>
<body data-theme="dark">
<?php renderNavigation('venues', 'Venues'); ?>

<main class="main-content">
    <div class="container">
        <?php foreach (get_flashes() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endforeach; ?>

        <section>
            <div class="venue-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2>Venues</h2>
                <a href="create-venue.php" class="button">Create Venue</a>
            </div>

            <?php if ($venues): ?>
                <div class="calendar-grid venue-grid">
                    <?php foreach ($venues as $v): ?>
                        <div class="venue-card">
                            <a href="venue-detail.php?id=<?= e((string)$v['id']) ?>" class="venue-card-link">
                                <?php if (!empty($v['image'])): ?>
                                    <div class="venue-image">
                                        <img src="uploads/<?= e($v['image']) ?>" alt="<?= e($v['name']) ?>">
                                    </div>
                                <?php endif; ?>
                                <div class="venue-card-content">
                                    <h3><?= e($v['name']) ?></h3>
                                    <?php if (!empty($v['description'])): ?>
                                        <p class="venue-description"><?= e(mb_substr($v['description'], 0, 100)) ?><?= mb_strlen($v['description']) > 100 ? '...' : '' ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($v['city']) || !empty($v['state'])): ?>
                                        <p class="venue-location">📍 <?= e(trim(($v['city'] ?? '') . ', ' . ($v['state'] ?? ''), ', ')) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($v['tags'])): ?>
                                        <div class="tag-list">
                                            <?php foreach (array_slice($v['tags'], 0, 3) as $tag): ?>
                                                <span class="badge">#<?= e(strtolower((string)$tag)) ?></span>
                                            <?php endforeach; ?>
                                            <?php if (count($v['tags']) > 3): ?>
                                                <span class="badge">+<?= count($v['tags']) - 3 ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="subtle">No venues yet. <a href="create-venue.php">Create one now</a>.</p>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php renderFooter(); ?>

<script src="js/calendar-7x5.js"></script>
</body>
</html>
