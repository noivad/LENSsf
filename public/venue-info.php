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
$currentUser = $_SESSION['current_user'] ?? 'Demo User';

$venueId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($venueId <= 0 || !$venueManager) {
    set_flash('Venue not found.', 'error');
    redirect('venues-list.php');
}

$venue = $venueManager->findById($venueId);
if (!$venue) {
    set_flash('Venue not found.', 'error');
    redirect('venues-list.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $venueManager) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_venue') {
        $imageFile = null;
        if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $imageFile = $_FILES['image'];
        }

        $tagsCsv = trim((string) ($_POST['tags_csv'] ?? ''));
        $tags = $tagsCsv !== '' ? array_values(array_filter(array_map('trim', explode(',', $tagsCsv)))) : [];

        $updated = $venueManager->update($venueId, [
            'name' => trim($_POST['name'] ?? ''),
            'address' => trim((string) ($_POST['address'] ?? '')),
            'city' => trim((string) ($_POST['city'] ?? '')),
            'state' => trim((string) ($_POST['state'] ?? '')),
            'zip_code' => trim((string) ($_POST['zip_code'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'owner' => trim($_POST['owner'] ?? ''),
            'deputies' => normalize_list_input($_POST['deputies'] ?? ''),
            'open_times' => trim((string) ($_POST['open_times'] ?? '')),
            'tags' => $tags,
        ], $imageFile);

        if ($updated) {
            $venue = $updated;
            set_flash('Venue updated successfully!');
        } else {
            set_flash('Failed to update venue.', 'error');
        }
    } elseif ($action === 'delete_venue') {
        if ($venueManager->delete($venueId)) {
            set_flash('Venue deleted successfully!');
            redirect('venues-list.php');
        } else {
            set_flash('Failed to delete venue.', 'error');
        }
    } elseif ($action === 'add_public_tag') {
        header('Content-Type: application/json');
        $raw = file_get_contents('php://input');
        $data = $raw ? json_decode((string) $raw, true) : [];
        $tag = strtolower(trim((string) ($data['tag'] ?? '')));

        if ($tag === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
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
}

$isOwner = strtolower($currentUser) === strtolower($venue['owner'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LENSsf::Venue - <?= e($venue['name']) ?></title>
    <link rel="stylesheet" href="css/calendar-7x5.css">
</head>
<body data-theme="dark">
<?php renderNavigation('venues', 'Venues'); ?>

<main class="main-content">
    <div class="container">
    <?php foreach (get_flashes() as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>

    <section class="card">
        <div class="venue-header">
            <h2><?= e($venue['name']) ?></h2>
            <div style="display: flex; gap: 1rem;">
                <a href="venues-list.php" class="button-small">← Back to List</a>
                <?php if ($isOwner): ?>
                    <button id="edit-toggle-btn" class="button">Edit Venue</button>
                    <button id="delete-venue-btn" class="button button-danger">Delete Venue</button>
                <?php endif; ?>
            </div>
        </div>

        <div id="view-mode">
            <?php if (!empty($venue['image'])): ?>
                <div class="venue-image-large" style="margin-bottom: 2rem;">
                    <img src="uploads/<?= e($venue['image']) ?>" alt="<?= e($venue['name']) ?>" style="max-width: 100%; max-height: 400px; border-radius: 12px; object-fit: cover;">
                </div>
            <?php endif; ?>

            <div class="venue-details-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <div>
                    <h3 style="margin-bottom: 1rem; color: var(--accent-blue);">Details</h3>
                    <?php if (!empty($venue['description'])): ?>
                        <p style="margin-bottom: 1rem;"><strong>Description:</strong><br><?= e($venue['description']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($venue['address'])): ?>
                        <p><strong>Address:</strong><br><?= e($venue['address']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($venue['city']) || !empty($venue['state'])): ?>
                        <p><strong>Location:</strong> <?= e(trim(($venue['city'] ?? '') . ', ' . ($venue['state'] ?? ''), ', ')) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($venue['zip_code'])): ?>
                        <p><strong>ZIP Code:</strong> <?= e($venue['zip_code']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($venue['open_times'])): ?>
                        <p><strong>Hours:</strong> <?= e($venue['open_times']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <h3 style="margin-bottom: 1rem; color: var(--accent-blue);">Management</h3>
                    <p><strong>Owner:</strong> <?= e((string)$venue['owner']) ?></p>
                    <?php if (!empty($venue['deputies'])): ?>
                        <p><strong>Deputies:</strong> <?= e(implode(', ', $venue['deputies'])) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($venue['tags'])): ?>
                        <div style="margin-top: 1rem;">
                            <strong>Tags:</strong>
                            <div class="tag-list lines venue-tags" style="margin-top: 0.5rem;">
                                <?php foreach ($venue['tags'] as $tag): ?>
                                    <span class="badge">#<?= e(strtolower((string)$tag)) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div id="edit-mode" style="display: none;">
            <form id="venue-form" method="post" enctype="multipart/form-data" class="form" autocomplete="off">
                <input type="hidden" name="action" value="update_venue">
                <input type="hidden" name="tags_csv" id="tags_csv" value="<?= e(implode(',', $venue['tags'])) ?>">

                <div class="form-row">
                    <label>Venue Name *
                        <input type="text" name="name" id="venue_name" required value="<?= e($venue['name']) ?>">
                    </label>
                </div>

                <div class="form-row">
                    <label>Description
                        <textarea name="description" id="venue_description" rows="3"><?= e($venue['description'] ?? '') ?></textarea>
                    </label>
                </div>

                <div class="form-row">
                    <label>Venue Image
                        <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                    </label>
                    <?php if (!empty($venue['image'])): ?>
                        <small class="subtle">Current image: <?= e($venue['image']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <label>Address
                        <input type="text" name="address" id="venue_address" placeholder="123 Main St" value="<?= e($venue['address'] ?? '') ?>">
                    </label>
                </div>

                <div class="form-row">
                    <div class="form-split">
                        <label>City
                            <input type="text" name="city" id="venue_city" value="<?= e($venue['city'] ?? '') ?>">
                        </label>
                        <label>State
                            <input type="text" name="state" id="venue_state" value="<?= e($venue['state'] ?? '') ?>">
                        </label>
                        <label>ZIP
                            <input type="text" name="zip_code" id="venue_zip" value="<?= e($venue['zip_code'] ?? '') ?>">
                        </label>
                    </div>
                </div>

                <div class="form-row">
                    <label>Times Open
                        <input type="text" name="open_times" id="venue_open_times" placeholder="Mon-Fri 9am-5pm; Sat 10am-2pm" value="<?= e($venue['open_times'] ?? '') ?>">
                    </label>
                </div>

                <div class="form-row">
                    <label>Venue Owner Name *
                        <input type="text" name="owner" id="venue_owner" required value="<?= e($venue['owner']) ?>">
                    </label>
                </div>

                <div class="form-row">
                    <label>Deputies (comma-separated names)
                        <input type="text" name="deputies" id="venue_deputies" placeholder="Pat, Lee, Morgan" value="<?= e(implode(', ', $venue['deputies'])) ?>">
                    </label>
                </div>

                <div class="form-row">
                    <label>Tags
                        <input type="text" id="tag_input" placeholder="Type a tag and press Enter or comma">
                        <small class="subtle">Tags are lowercase; press Enter or comma to add.</small>
                    </label>
                    <div id="tag_list" class="tag-list lines"></div>
                </div>

                <div class="form-row" style="display: flex; gap: 1rem;">
                    <button type="submit" class="button">Save Changes</button>
                    <button type="button" id="cancel-edit-btn" class="button-small">Cancel</button>
                </div>
            </form>
        </div>
    </section>
</main>

<?php renderFooter(); ?>

<script>
(function(){
    const editToggleBtn = document.getElementById('edit-toggle-btn');
    const deleteVenueBtn = document.getElementById('delete-venue-btn');
    const viewMode = document.getElementById('view-mode');
    const editMode = document.getElementById('edit-mode');
    const cancelEditBtn = document.getElementById('cancel-edit-btn');
    const tagInput = document.getElementById('tag_input');
    const tagList = document.getElementById('tag_list');
    const tagsCsv = document.getElementById('tags_csv');
    const form = document.getElementById('venue-form');

    let venueTags = <?= json_encode($venue['tags']) ?>;

    function renderTags() {
        tagList.innerHTML = '';
        tagsCsv.value = venueTags.join(',');
        venueTags.forEach((t) => {
            const span = document.createElement('span');
            span.className = 'badge';
            span.textContent = `#${t}`;
            
            const removeBtn = document.createElement('span');
            removeBtn.textContent = ' ×';
            removeBtn.style.cursor = 'pointer';
            removeBtn.style.marginLeft = '0.3rem';
            removeBtn.onclick = () => {
                venueTags = venueTags.filter(tag => tag !== t);
                renderTags();
            };
            
            span.appendChild(removeBtn);
            tagList.appendChild(span);
        });
    }

    function addTag(tag) {
        const t = (tag||'').toLowerCase().trim();
        if (!t) return;
        if (!venueTags.includes(t)) {
            venueTags.push(t);
            renderTags();
        }
    }

    editToggleBtn?.addEventListener('click', () => {
        viewMode.style.display = 'none';
        editMode.style.display = 'block';
        editToggleBtn.style.display = 'none';
        deleteVenueBtn.style.display = 'none';
        renderTags();
    });

    cancelEditBtn?.addEventListener('click', () => {
        viewMode.style.display = 'block';
        editMode.style.display = 'none';
        editToggleBtn.style.display = 'inline-block';
        deleteVenueBtn.style.display = 'inline-block';
        venueTags = <?= json_encode($venue['tags']) ?>;
    });

    deleteVenueBtn?.addEventListener('click', () => {
        if (confirm('Are you sure you want to delete this venue? This action cannot be undone.')) {
            const deleteForm = document.createElement('form');
            deleteForm.method = 'POST';
            deleteForm.style.display = 'none';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete_venue';
            
            deleteForm.appendChild(actionInput);
            document.body.appendChild(deleteForm);
            deleteForm.submit();
        }
    });

    tagInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            const raw = tagInput.value;
            tagInput.value = '';
            raw.split(',').forEach(addTag);
        }
    });
})();
</script>
</body>
</html>
