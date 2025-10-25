<?php

declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/managers/EventManager.php';
require __DIR__ . '/../includes/managers/VenueManager.php';
require __DIR__ . '/../includes/managers/MediaManager.php';
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

$siteName = SITE_NAME;
$currentUser = $_SESSION['current_user'] ?? 'Demo User';
$uploadDir = defined('UPLOAD_DIR') ? rtrim((string) UPLOAD_DIR, '/') : __DIR__ . '/uploads';

$pdo = Database::connect();
$eventManager = new EventManager($pdo, $uploadDir);
$venueManager = new VenueManager($pdo, $uploadDir);
$mediaManager = new MediaManager($pdo, $uploadDir);

$eventTitle = $_GET['event'] ?? '';
$activeTab = $_GET['tab'] ?? 'details';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_event_image') {
    $eventId = (int)($_POST['event_id'] ?? 0);
    $event = $eventManager->findById($eventId);
    
    if ($event && !empty($_FILES['event_image'])) {
        $userId = 1;
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $mediaId = $mediaManager->addMediaForEntity($_FILES['event_image'], 'event', $eventId, $userId, $sortOrder);
        
        if ($mediaId) {
            set_flash('Image uploaded successfully!');
        } else {
            set_flash('Failed to upload image.', 'error');
        }
    }
    redirect('event-info.php?event=' . urlencode($eventTitle) . '&tab=' . $activeTab);
}

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

$eventMedia = $mediaManager->getMediaForEntity('event', (int)$event['id']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LENSsf::<?= e($event['title']) ?></title>
    <link rel="stylesheet" href="css/calendar-7x5.css">
    <link rel="stylesheet" href="css/event-info.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
</head>
<body data-theme="dark">
    <?php renderNavigation('events', e($event['title'])); ?>

    <main class="main-content">
        <div class="container">
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
                                    <a href="venue-detail.php?id=<?= e($venue['id']) ?>">
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
                <a href="?event=<?= urlencode($eventTitle) ?>&tab=images" class="submenu-item <?= $activeTab === 'images' ? 'active' : '' ?>">
                    Images (<?= count($eventMedia) ?>)
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

                    <?php if ($hasEditAccess || is_admin($currentUser)): ?>
                        <div class="event-actions">
                            <a href="add-event.php?edit=<?= urlencode($event['title']) ?>" class="button">
                                ✏️ Edit Event
                            </a>
                            <button class="button event-action-btn delete" onclick="confirmDeleteEvent(<?= e($event['id']) ?>, '<?= e($event['title'], ENT_QUOTES) ?>')" style="background: linear-gradient(135deg, rgba(255, 61, 162, 0.32), rgba(157, 77, 255, 0.24)); border: 1px solid rgba(255, 61, 162, 0.35);">
                                🗑️ Delete Event
                            </button>
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
            <?php elseif ($activeTab === 'images'): ?>
                <div class="event-content">
                    <h3>Event Images</h3>
                    
                    <?php if (!empty($eventMedia)): ?>
                        <div class="photo-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                            <?php foreach ($eventMedia as $media): ?>
                                <div class="photo-card" style="border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden;">
                                    <img src="uploads/<?= e($media['file_path']) ?>" alt="Event image" style="width: 100%; height: 200px; object-fit: cover;">
                                    <div style="padding: 0.5rem;">
                                        <small style="color: var(--text-secondary);">Uploaded: <?= e(date('M j, Y', strtotime($media['upload_date']))) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-deputies">No images uploaded for this event yet.</p>
                    <?php endif; ?>
                    
                    <?php if ($hasEditAccess || is_admin($currentUser)): ?>
                        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border-color);">
                            <h4>Upload New Image</h4>
                            <form method="post" enctype="multipart/form-data" class="form">
                                <input type="hidden" name="action" value="upload_event_image">
                                <input type="hidden" name="event_id" value="<?= e($event['id']) ?>">
                                <div class="form-row">
                                    <label>
                                        Select Image
                                        <input type="file" name="event_image" accept="image/jpeg,image/png,image/gif,image/webp" required>
                                        <small>Max size: 10MB. Accepted formats: JPEG, PNG, GIF, WebP</small>
                                    </label>
                                </div>
                                <div class="form-row">
                                    <button type="submit" class="button">Upload Image</button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
        </div>
    </main>

    <?php renderFooter(); ?>

    <div class="modal" id="deleteEventModal">
        <div class="modal-content">
            <h2 class="modal-title">⚠️ Confirm Delete Event</h2>
            <p class="modal-text">Are you sure you want to delete "<span id="eventToDelete"></span>"? This action cannot be undone.</p>
            <div class="modal-actions">
                <button class="modal-btn cancel" onclick="closeDeleteModal()">Cancel</button>
                <form id="deleteEventForm" method="post" action="index.php" style="display: inline;">
                    <input type="hidden" name="action" value="delete_event">
                    <input type="hidden" name="event_id" id="deleteEventId">
                    <button type="submit" class="modal-btn confirm">Delete Event</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        window.__EVENT_LOCATION__ = <?= json_encode([
            'name' => $venue['name'] ?? '',
            'address' => $venue['address'] ?? '',
            'city' => $venue['city'] ?? '',
            'state' => $venue['state'] ?? ''
        ], JSON_UNESCAPED_UNICODE) ?>;

        function confirmDeleteEvent(eventId, eventTitle) {
            document.getElementById('eventToDelete').textContent = eventTitle;
            document.getElementById('deleteEventId').value = eventId;
            document.getElementById('deleteEventModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteEventModal').classList.remove('active');
        }
    </script>
    <script src="js/event-info.js"></script>
</body>
</html>
