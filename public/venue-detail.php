<?php

declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/db.php';
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

$uploadDir = defined('UPLOAD_DIR') ? rtrim((string) UPLOAD_DIR, '/') : __DIR__ . '/uploads';
$siteName = SITE_NAME;
$currentUser = $_SESSION['current_user'] ?? 'Demo User';

try {
    $pdo = Database::connect();
} catch (Throwable $e) {
    $pdo = null;
}

$venueManager = $pdo ? new VenueManager($pdo, $uploadDir) : null;
$mediaManager = $pdo ? new MediaManager($pdo, $uploadDir) : null;

$venueId = (int)($_GET['id'] ?? 0);
$venue = null;

if ($venueManager && $venueId > 0) {
    $venue = $venueManager->findById($venueId);
}

if (!$venue) {
    set_flash('Venue not found.', 'error');
    redirect('venues-list.php');
    exit;
}

$isOwner = strtolower($currentUser) === strtolower($venue['owner'] ?? '');
$venueMedia = $mediaManager ? $mediaManager->getMediaForEntity('venue', $venueId) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $venueManager) {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'upload_venue_image' && $isOwner && $mediaManager) {
            if (!empty($_FILES['venue_image'])) {
                $userId = 1;
                $sortOrder = (int)($_POST['sort_order'] ?? 0);
                $mediaId = $mediaManager->addMediaForEntity($_FILES['venue_image'], 'venue', $venueId, $userId, $sortOrder);
                
                if ($mediaId) {
                    set_flash('Image uploaded successfully!');
                } else {
                    set_flash('Failed to upload image.', 'error');
                }
            }
            redirect('venue-detail.php?id=' . $venueId);
        } elseif ($_POST['action'] === 'toggle_public' && $isOwner) {
            $venue = $venueManager->update($venueId, [
                'name' => $venue['name'],
                'owner' => $venue['owner'],
                'is_public' => empty($venue['is_public']),
                'address' => $venue['address'],
                'city' => $venue['city'],
                'state' => $venue['state'],
                'zip_code' => $venue['zip_code'],
                'description' => $venue['description'],
                'deputies' => $venue['deputies'],
                'open_times' => $venue['open_times'],
                'tags' => $venue['tags'],
            ]);
            set_flash('Venue visibility updated successfully!');
        } elseif ($_POST['action'] === 'update_image') {
            $imageFile = null;
            if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $imageFile = $_FILES['image'];
            }

            if ($imageFile) {
                $venue = $venueManager->update($venueId, [
                    'name' => $venue['name'],
                    'owner' => $venue['owner'],
                    'address' => $venue['address'],
                    'city' => $venue['city'],
                    'state' => $venue['state'],
                    'zip_code' => $venue['zip_code'],
                    'description' => $venue['description'],
                    'deputies' => $venue['deputies'],
                    'open_times' => $venue['open_times'],
                    'tags' => $venue['tags'],
                    'is_private' => $venue['is_private'],
                    'is_public' => $venue['is_public'],
                ], $imageFile);
                set_flash('Venue image updated successfully!');
            }
        } elseif ($_POST['action'] === 'update_venue') {
            $venue = $venueManager->update($venueId, [
                'name' => trim($_POST['name'] ?? $venue['name']),
                'owner' => $venue['owner'],
                'address' => trim($_POST['address'] ?? ''),
                'city' => trim($_POST['city'] ?? ''),
                'state' => trim($_POST['state'] ?? ''),
                'zip_code' => trim($_POST['zip_code'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'deputies' => normalize_list_input($_POST['deputies'] ?? ''),
                'open_times' => trim($_POST['open_times'] ?? ''),
                'tags' => array_map('trim', explode(',', trim($_POST['tags'] ?? ''))),
                'is_private' => $venue['is_private'],
                'is_public' => $venue['is_public'],
            ]);
            set_flash('Venue updated successfully!');
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LENSsf::<?= e($venue['name']) ?></title>
    <link rel="stylesheet" href="css/calendar-7x5.css">
</head>
<body data-theme="dark">
<?php renderNavigation('venues', e($venue['name'])); ?>

<main class="main-content">
    <div class="container">
        <?php foreach (get_flashes() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endforeach; ?>

        <section>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2><?= e($venue['name']) ?></h2>
                <a href="venues-list.php" class="button-small">← Back to Venues</a>
            </div>

            <div class="card">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <?php if (!empty($venue['image'])): ?>
                            <div style="margin-bottom: 1rem;">
                                <img src="uploads/<?= e($venue['image']) ?>" alt="<?= e($venue['name']) ?>" style="width: 100%; border-radius: 8px;">
                            </div>
                        <?php endif; ?>

                        <?php if ($isOwner): ?>
                            <form method="post" enctype="multipart/form-data" style="margin-bottom: 1rem;">
                                <input type="hidden" name="action" value="update_image">
                                <label>
                                    Change Venue Image
                                    <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                                </label>
                                <button type="submit" class="button-small" style="margin-top: 0.5rem;">Upload Image</button>
                            </form>
                        <?php endif; ?>

                        <h3>Details</h3>
                        <?php if (!empty($venue['description'])): ?>
                            <p><?= e($venue['description']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($venue['address'])): ?>
                            <p><strong>Address:</strong> <?= e($venue['address']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($venue['city']) || !empty($venue['state'])): ?>
                            <p><strong>Location:</strong> <?= e(trim(($venue['city'] ?? '') . ', ' . ($venue['state'] ?? ''), ', ')) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($venue['zip_code'])): ?>
                            <p><strong>ZIP:</strong> <?= e($venue['zip_code']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($venue['open_times'])): ?>
                            <p><strong>Hours:</strong> <?= e($venue['open_times']) ?></p>
                        <?php endif; ?>

                        <p><strong>Owner:</strong> <?= e($venue['owner']) ?></p>

                        <?php if (!empty($venue['deputies'])): ?>
                            <p><strong>Deputies:</strong> <?= e(implode(', ', $venue['deputies'])) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($venue['tags'])): ?>
                            <div class="tag-list" style="margin-top: 1rem;">
                                <?php foreach ($venue['tags'] as $tag): ?>
                                    <span class="badge">#<?= e(strtolower((string)$tag)) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($venue['is_private']): ?>
                            <div style="margin-top: 1rem; padding: 1rem; background: var(--card-bg); border-radius: 8px;">
                                <p><strong>Privacy Status:</strong> 
                                    <?php if ($venue['is_public']): ?>
                                        <span class="badge" style="background: green;">Public (visible to all)</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: orange;">Private (only you can see)</span>
                                    <?php endif; ?>
                                </p>
                                <?php if ($isOwner): ?>
                                    <form method="post" style="margin-top: 0.5rem;">
                                        <input type="hidden" name="action" value="toggle_public">
                                        <button type="submit" class="button-small">
                                            Make <?= $venue['is_public'] ? 'Private' : 'Public' ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <?php if ($isOwner): ?>
                            <h3>Edit Venue</h3>
                            <form method="post" class="form">
                                <input type="hidden" name="action" value="update_venue">
                                
                                <div class="form-row">
                                    <label>
                                        Venue Name
                                        <input type="text" name="name" value="<?= e($venue['name']) ?>">
                                    </label>
                                </div>

                                <div class="form-row">
                                    <label>
                                        Description
                                        <textarea name="description" rows="3"><?= e($venue['description'] ?? '') ?></textarea>
                                    </label>
                                </div>

                                <div class="form-row">
                                    <label>
                                        Address
                                        <input type="text" name="address" value="<?= e($venue['address'] ?? '') ?>">
                                    </label>
                                </div>

                                <div class="form-row">
                                    <div class="form-split">
                                        <label>
                                            City
                                            <input type="text" name="city" value="<?= e($venue['city'] ?? '') ?>">
                                        </label>
                                        <label>
                                            State
                                            <input type="text" name="state" value="<?= e($venue['state'] ?? '') ?>">
                                        </label>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <label>
                                        ZIP Code
                                        <input type="text" name="zip_code" value="<?= e($venue['zip_code'] ?? '') ?>">
                                    </label>
                                </div>

                                <div class="form-row">
                                    <label>
                                        Hours Open
                                        <input type="text" name="open_times" value="<?= e($venue['open_times'] ?? '') ?>">
                                    </label>
                                </div>

                                <div class="form-row">
                                    <label>
                                        Deputies (comma-separated)
                                        <input type="text" name="deputies" value="<?= e(implode(', ', $venue['deputies'] ?? [])) ?>">
                                    </label>
                                </div>

                                <div class="form-row">
                                    <label>
                                        Tags (comma-separated)
                                        <input type="text" name="tags" value="<?= e(implode(', ', $venue['tags'] ?? [])) ?>">
                                    </label>
                                </div>

                                <div class="form-row">
                                    <button type="submit" class="button">Update Venue</button>
                                </div>
                            </form>

                            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border-color);">
                                <h3>Venue Images</h3>
                                
                                <?php if (!empty($venueMedia)): ?>
                                    <div class="photo-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                                        <?php foreach ($venueMedia as $media): ?>
                                            <div class="photo-card" style="border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden;">
                                                <img src="uploads/<?= e($media['file_path']) ?>" alt="Venue image" style="width: 100%; height: 150px; object-fit: cover;">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p style="color: var(--text-secondary); font-style: italic;">No additional images for this venue.</p>
                                <?php endif; ?>
                                
                                <h4>Upload New Image</h4>
                                <form method="post" enctype="multipart/form-data" style="margin-bottom: 2rem;">
                                    <input type="hidden" name="action" value="upload_venue_image">
                                    <label>
                                        Select Image
                                        <input type="file" name="venue_image" accept="image/jpeg,image/png,image/gif,image/webp" required>
                                        <small>Max size: 10MB</small>
                                    </label>
                                    <button type="submit" class="button-small" style="margin-top: 0.5rem;">Upload Image</button>
                                </form>
                            </div>

                            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border-color);">
                                <h3>Danger Zone</h3>
                                <button class="button event-action-btn delete" onclick="confirmDeleteVenue(<?= e($venue['id']) ?>, '<?= e($venue['name'], ENT_QUOTES) ?>')" style="background: linear-gradient(135deg, rgba(255, 61, 162, 0.32), rgba(157, 77, 255, 0.24)); border: 1px solid rgba(255, 61, 162, 0.35);">
                                    🗑️ Delete Venue
                                </button>
                            </div>
                        <?php else: ?>
                            <h3>Venue Images</h3>
                            <?php if (!empty($venueMedia)): ?>
                                <div class="photo-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                                    <?php foreach ($venueMedia as $media): ?>
                                        <div class="photo-card" style="border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden;">
                                            <img src="uploads/<?= e($media['file_path']) ?>" alt="Venue image" style="width: 100%; height: 150px; object-fit: cover;">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p style="color: var(--text-secondary); font-style: italic;">No images available for this venue.</p>
                            <?php endif; ?>
                            
                            <h3 style="margin-top: 2rem;">Events at this Venue</h3>
                            <p class="subtle">Event listing coming soon...</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<?php renderFooter(); ?>

<div class="modal" id="deleteVenueModal">
    <div class="modal-content">
        <h2 class="modal-title">⚠️ Confirm Delete Venue</h2>
        <p class="modal-text">Are you sure you want to delete "<span id="venueToDelete"></span>"? This action cannot be undone.</p>
        <div class="modal-actions">
            <button class="modal-btn cancel" onclick="closeDeleteVenueModal()">Cancel</button>
            <form id="deleteVenueForm" method="post" action="index.php" style="display: inline;">
                <input type="hidden" name="action" value="delete_venue">
                <input type="hidden" name="venue_id" id="deleteVenueId">
                <button type="submit" class="modal-btn confirm">Delete Venue</button>
            </form>
        </div>
    </div>
</div>

<script>
    function confirmDeleteVenue(venueId, venueName) {
        document.getElementById('venueToDelete').textContent = venueName;
        document.getElementById('deleteVenueId').value = venueId;
        document.getElementById('deleteVenueModal').classList.add('active');
    }

    function closeDeleteVenueModal() {
        document.getElementById('deleteVenueModal').classList.remove('active');
    }
</script>
<script src="js/calendar-7x5.js"></script>
</body>
</html>
