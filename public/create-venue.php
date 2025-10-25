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
$currentUser = $_SESSION['current_user'] ?? 'Demo User';

try {
    $pdo = Database::connect();
} catch (Throwable $e) {
    $pdo = null;
}

$venueManager = $pdo ? new VenueManager($pdo, $uploadDir) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $venueManager) {
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
            'is_private' => !empty($_POST['is_private']),
            'is_public' => empty($_POST['is_private']) || !empty($_POST['is_public']),
        ], $imageFile);

        if ($venue) {
            set_flash('Venue created successfully!');
            redirect('venues-list.php');
        } else {
            set_flash('Failed to create venue. Please try again.', 'error');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LENSsf::Create Venue</title>
    <link rel="stylesheet" href="css/calendar-7x5.css">
</head>
<body data-theme="dark">
<?php renderNavigation('venues', 'Create Venue'); ?>

<main class="main-content">
    <div class="container">
        <?php foreach (get_flashes() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endforeach; ?>

        <section>
            <h2>Create Venue</h2>

            <div class="card">
                <form method="post" enctype="multipart/form-data" class="form">
                    <div class="form-row">
                        <label>
                            Venue Name *
                            <input type="text" name="name" required value="<?= e($_POST['name'] ?? '') ?>">
                        </label>
                    </div>

                    <div class="form-row">
                        <label>
                            Description
                            <textarea name="description" rows="4"><?= e($_POST['description'] ?? '') ?></textarea>
                        </label>
                    </div>

                    <div class="form-row">
                        <label>
                            Venue Image
                            <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                            <small>Max size: 10MB. Accepted formats: JPEG, PNG, GIF, WebP</small>
                        </label>
                    </div>

                    <div class="form-row">
                        <label>
                            Address
                            <input type="text" name="address" placeholder="123 Main St" value="<?= e($_POST['address'] ?? '') ?>">
                        </label>
                    </div>

                    <div class="form-row">
                        <div class="form-split">
                            <label>
                                City
                                <input type="text" name="city" value="<?= e($_POST['city'] ?? '') ?>">
                            </label>
                            <label>
                                State
                                <input type="text" name="state" value="<?= e($_POST['state'] ?? '') ?>">
                            </label>
                            <label>
                                ZIP
                                <input type="text" name="zip_code" value="<?= e($_POST['zip_code'] ?? '') ?>">
                            </label>
                        </div>
                    </div>

                    <div class="form-row">
                        <label>
                            Times Open
                            <input type="text" name="open_times" placeholder="Mon-Fri 9am-5pm; Sat 10am-2pm" value="<?= e($_POST['open_times'] ?? '') ?>">
                        </label>
                    </div>

                    <div class="form-row">
                        <label>
                            Venue Owner Name *
                            <input type="text" name="owner" required value="<?= e($_POST['owner'] ?? $currentUser) ?>">
                        </label>
                    </div>

                    <div class="form-row">
                        <label>
                            Deputies (comma-separated names)
                            <input type="text" name="deputies" placeholder="Pat, Lee, Morgan" value="<?= e($_POST['deputies'] ?? '') ?>">
                        </label>
                    </div>

                    <div class="form-row">
                        <label>
                            Tags (comma-separated)
                            <input type="text" name="tags_csv" placeholder="music, outdoor, downtown" value="<?= e($_POST['tags_csv'] ?? '') ?>">
                            <small>Tags are lowercase and help users find your venue</small>
                        </label>
                    </div>

                    <div class="form-row">
                        <label>
                            <input type="checkbox" name="is_private" value="1" <?= !empty($_POST['is_private']) ? 'checked' : '' ?>>
                            This is a private/custom venue (e.g., home address)
                        </label>
                        <small>Private venues can be made public later from the venue page</small>
                    </div>

                    <div class="form-row">
                        <button type="submit" class="button">Create Venue</button>
                        <a href="venues-list.php" class="button-small" style="margin-left: 1rem; background: var(--secondary-color);">Cancel</a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</main>

<?php renderFooter(); ?>

<script src="js/calendar-7x5.js"></script>
</body>
</html>
