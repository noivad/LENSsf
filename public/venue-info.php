<?php

declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/db.php';
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

$uploadDir = defined('UPLOAD_DIR') ? rtrim((string) UPLOAD_DIR, '/') : __DIR__ . '/uploads';
$siteName = SITE_NAME;

try {
    $pdo = Database::connect();
} catch (Throwable $e) {
    $pdo = null;
}

$venueManager = $pdo ? new VenueManager($pdo, $uploadDir) : null;

$currentUser = $_SESSION['current_user'] ?? 'Demo User';

// Simple JSON API for adding a public tag
if (($venueManager && isset($_GET['action'])) && $_GET['action'] === 'add_public_tag') {
    header('Content-Type: application/json');
    $raw = file_get_contents('php://input');
    $data = $raw ? json_decode((string) $raw, true) : [];
    $venueId = (int) ($data['venue_id'] ?? 0);
    $tag = strtolower(trim((string) ($data['tag'] ?? '')));

    if ($venueId <= 0 || $tag === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }

    $venue = $venueManager->findById($venueId);
    if (!$venue) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Venue not found']);
        exit;
    }

    $isOwner = strtolower($currentUser) === strtolower($venue['owner'] ?? '');
    if (!$isOwner) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only venue owner can add public tags']);
        exit;
    }

    $updated = $venueManager->addTagPublic($venueId, $tag);
    echo json_encode(['success' => (bool) $updated, 'venue' => $updated]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_venue_adv' && $venueManager) {
    $name = trim($_POST['name'] ?? '');
    $owner = trim($_POST['owner'] ?? '');
    if ($name === '' || $owner === '') {
        set_flash('Please provide the venue name and owner.', 'error');
    } else {
        $imageFile = null;
        if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $imageFile = $_FILES['image'];
        }

        $tagsCsv = trim((string) ($_POST['tags_csv'] ?? ''));
        $tags = $tagsCsv !== '' ? array_values(array_filter(array_map('trim', explode(',', $tagsCsv)))) : [];

        $venue = $venueManager->create([
            'name' => $name,
            'address' => trim((string) ($_POST['address'] ?? '')),
            'city' => trim((string) ($_POST['city'] ?? '')),
            'state' => trim((string) ($_POST['state'] ?? '')),
            'zip_code' => trim((string) ($_POST['zip_code'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'owner' => $owner,
            'deputies' => normalize_list_input($_POST['deputies'] ?? ''),
            'open_times' => trim((string) ($_POST['open_times'] ?? '')),
            'tags' => $tags,
        ], $imageFile);

        if ($venue) {
            set_flash('Venue created successfully!');
        } else {
            set_flash('Failed to create venue. Please try again.', 'error');
        }
    }
}

$venues = $venueManager ? $venueManager->all() : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LENSsf::Venues</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/venue-info.css">
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
            <a href="venue-info.php" class="active">Venues</a>
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
    <?php foreach (get_flashes() as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>

    <section class="card">
        <div class="venue-header">
            <h2>Venues</h2>
            <button id="create-venue-btn" class="button">Create Venue</button>
        </div>

        <?php if ($venues): ?>
            <div class="venue-list" id="venue-list">
                <?php foreach ($venues as $v): ?>
                    <div class="venue-item" data-venue-id="<?= e((string)$v['id']) ?>" data-owner="<?= e((string)($v['owner'] ?? '')) ?>">
                        <h4><?= e($v['name']) ?></h4>
                        <?php if (!empty($v['description'])): ?><p><?= e($v['description']) ?></p><?php endif; ?>
                        <div class="venue-details">
                            <?php if (!empty($v['address'])): ?><span><?= e($v['address']) ?></span><?php endif; ?>
                            <?php if (!empty($v['city']) || !empty($v['state'])): ?><span><?= e(trim(($v['city'] ?? '') . ', ' . ($v['state'] ?? ''), ', ')) ?></span><?php endif; ?>
                            <?php if (!empty($v['zip_code'])): ?><span><?= e($v['zip_code']) ?></span><?php endif; ?>
                            <?php if (!empty($v['open_times'])): ?><span><strong>Hours:</strong> <?= e($v['open_times']) ?></span><?php endif; ?>
                        </div>
                        <?php if (!empty($v['tags'])): ?>
                            <div class="tag-list lines venue-tags">
                                <?php foreach ($v['tags'] as $tag): ?>
                                    <span class="badge">#<?= e(strtolower((string)$tag)) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="venue-meta venue-meta-actions">
                            <span><strong>Owner:</strong> <?= e((string)$v['owner']) ?></span>
                            <a href="venue-detail.php?venue=<?= urlencode($v['name']) ?>" class="button-small venue-detail-link">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="subtle">No venues yet. Click "Create Venue" to add one.</p>
        <?php endif; ?>
    </section>
</main>

<div class="overlay-mask" id="overlay-mask"></div>

<div class="popover" id="venue-popover" aria-hidden="true">
    <div class="popover-header">
        <strong id="venue-popover-title">Create Venue</strong>
        <button class="popover-close" type="button" aria-label="Close">×</button>
    </div>
    <div class="popover-body venue-popover-body">
        <div>
            <form id="venue-form" method="post" enctype="multipart/form-data" class="form" autocomplete="off">
                <input type="hidden" name="action" value="create_venue_adv">
                <input type="hidden" name="tags_csv" id="tags_csv" value="">

                <div class="form-row">
                    <label>Venue Name *
                        <input type="text" name="name" id="venue_name" required>
                    </label>
                </div>

                <div class="form-row">
                    <label>Description
                        <textarea name="description" id="venue_description" rows="3"></textarea>
                    </label>
                </div>

                <div class="form-row">
                    <label>Venue Image
                        <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                    </label>
                </div>

                <div class="form-row">
                    <label>Address
                        <input type="text" name="address" id="venue_address" placeholder="123 Main St">
                    </label>
                </div>

                <div class="form-row">
                    <div class="form-split">
                        <label>City
                            <input type="text" name="city" id="venue_city">
                        </label>
                        <label>State
                            <input type="text" name="state" id="venue_state">
                        </label>
                        <label>ZIP
                            <input type="text" name="zip_code" id="venue_zip">
                        </label>
                    </div>
                </div>

                <div class="form-row">
                    <label>Times Open
                        <input type="text" name="open_times" id="venue_open_times" placeholder="Mon-Fri 9am-5pm; Sat 10am-2pm">
                    </label>
                </div>

                <div class="form-row">
                    <label>Venue Owner Name *
                        <input type="text" name="owner" id="venue_owner" required value="<?= e($currentUser) ?>">
                    </label>
                </div>

                <div class="form-row">
                    <label>Deputies (comma-separated names)
                        <input type="text" name="deputies" id="venue_deputies" placeholder="Pat, Lee, Morgan">
                    </label>
                </div>

                <div class="form-row">
                    <label>Tags
                        <input type="text" id="tag_input" placeholder="Type a tag and press Enter or comma">
                        <small class="subtle">Tags are lowercase; press Enter or comma to add. Public tags are visible to everyone.</small>
                    </label>
                    <div id="tag_list" class="tag-list lines"></div>
                </div>

                <div class="form-row">
                    <button type="submit" class="button">Save Venue</button>
                </div>
            </form>

            <div id="venue-info" class="venue-info-hidden">
                <div id="venue-info-content"></div>
                <div class="form-row venue-tag-form">
                    <label>Add a tag
                        <input type="text" id="info_tag_input" placeholder="Type a tag and press Enter or comma">
                    </label>
                    <div class="tag-visibility-options">
                        <label><input type="radio" name="tag_visibility" value="private" checked> Private</label>
                        <label><input type="radio" name="tag_visibility" value="public" id="public_tag_radio"> Public (owner only)</label>
                    </div>
                    <div id="info_tag_list" class="tag-list lines venue-info-tags"></div>
                </div>
            </div>
        </div>
        <div>
            <div id="mini_map" class="mini-map"></div>
        </div>
    </div>
</div>

<footer>
    <div class="container">
        <p>&copy; <?= date('Y') ?> <?= e($siteName) ?></p>
    </div>
</footer>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    window.__VENUES__ = <?= json_encode($venues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    window.__CURRENT_USER__ = <?= json_encode($currentUser, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="js/venue-info.js"></script>
</body>
</html>
