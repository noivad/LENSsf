<?php

declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/managers/VenueManager.php';
require __DIR__ . '/../includes/managers/EventManager.php';
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
$eventManager = $pdo ? new EventManager($pdo, $uploadDir) : null;
$fromEvent = $_GET['from'] === 'event';

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
            
            if (isset($_SESSION['pending_event_data']) && $eventManager) {
                $eventData = $_SESSION['pending_event_data'];
                $eventData['venue_id'] = $venue['id'];
                
                $imageFile = null;
                if (isset($_SESSION['pending_event_files']['image']) && $_SESSION['pending_event_files']['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $imageFile = $_SESSION['pending_event_files']['image'];
                }
                
                $event = $eventManager->create([
                    'title' => trim($eventData['title'] ?? ''),
                    'description' => trim($eventData['description'] ?? ''),
                    'event_date' => trim($eventData['event_date'] ?? ''),
                    'start_time' => trim($eventData['start_time'] ?? ''),
                    'end_time' => trim($eventData['end_time'] ?? ''),
                    'venue_id' => $venue['id'],
                    'owner' => trim($eventData['owner'] ?? ''),
                    'deputies' => normalize_list_input($eventData['deputies'] ?? ''),
                    'tags' => normalize_list_input($eventData['tags'] ?? ''),
                    'is_recurring' => !empty($eventData['is_recurring']),
                    'recurrence_type' => trim($eventData['recurrence_type'] ?? ''),
                    'recurrence_end_date' => trim($eventData['recurrence_end_date'] ?? ''),
                    'weekly_interval' => $eventData['weekly_interval'] ?? 1,
                    'month_week' => trim($eventData['month_week'] ?? ''),
                    'day_of_week' => trim($eventData['day_of_week'] ?? ''),
                    'monthly_day_interval' => $eventData['monthly_day_interval'] ?? 1,
                    'monthly_date_interval' => $eventData['monthly_date_interval'] ?? 1,
                    'custom_interval' => $eventData['custom_interval'] ?? 1,
                    'custom_unit' => trim($eventData['custom_unit'] ?? 'days'),
                ], $imageFile);
                
                unset($_SESSION['pending_event_data']);
                unset($_SESSION['pending_event_files']);
                
                if ($event) {
                    set_flash('Event created successfully with new venue!');
                    redirect('event-list.php');
                } else {
                    set_flash('Venue created but failed to create event. Please try again.', 'error');
                    redirect('add-event.php');
                }
            } else {
                redirect('venues-list.php');
            }
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
            
            <?php if ($fromEvent): ?>
                <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                    <strong>📌 Creating venue for your event</strong><br>
                    After you create this venue, your event will be automatically created and linked to it.
                </div>
            <?php endif; ?>

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
