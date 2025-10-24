<?php

declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/managers/EventManager.php';
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

$pdo = Database::connect();
$eventManager = new EventManager($pdo, $uploadDir);
$venueManager = new VenueManager($pdo, $uploadDir);

$venues = $venueManager->all();
$siteName = SITE_NAME;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $eventDate = trim($_POST['event_date'] ?? '');
    $owner = trim($_POST['owner'] ?? '');

    if ($title === '' || $eventDate === '' || $owner === '') {
        set_flash('Please provide the event title, date, and owner name.', 'error');
    } else {
        $imageFile = null;
        if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $imageFile = $_FILES['image'];
        }

        $event = $eventManager->create([
            'title' => $title,
            'description' => trim($_POST['description'] ?? ''),
            'event_date' => $eventDate,
            'start_time' => trim($_POST['start_time'] ?? ''),
            'venue_id' => $_POST['venue_id'] ?: null,
            'owner' => $owner,
            'deputies' => normalize_list_input($_POST['deputies'] ?? ''),
            'tags' => normalize_list_input($_POST['tags'] ?? ''),
        ], $imageFile);

        if ($event) {
            if ($imageFile && (int) ($imageFile['error'] ?? UPLOAD_ERR_OK) === UPLOAD_ERR_OK && empty($event['image'])) {
                set_flash('Event created, but the image could not be uploaded.', 'error');
            } else {
                set_flash('Event created successfully!');
            }
            redirect('index.php?page=events');
        } else {
            set_flash('Failed to create event. Please try again.', 'error');
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event - <?= e($siteName) ?></title>
    <link rel="stylesheet" href="css/style.css">
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
                <a href="tags.php">Tags</a>
                <a href="account.php">Account</a>
                <a href="add-event.php" class="active">Add Event</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php foreach (get_flashes() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endforeach; ?>

        <section>
            <h2>Add New Event</h2>

            <div class="card">
                <form method="post" enctype="multipart/form-data" class="form">
                    <div class="form-row">
                        <label>
                            Event Title *
                            <input type="text" name="title" required value="<?= e($_POST['title'] ?? '') ?>">
                        </label>
                    </div>

                    <div class="form-row">
                        <label>
                            Description
                            <textarea name="description" rows="5"><?= e($_POST['description'] ?? '') ?></textarea>
                        </label>
                    </div>

                    <div class="form-row">
                        <label>
                            Event Image
                            <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                            <small>Max size: 10MB. Accepted formats: JPEG, PNG, GIF, WebP</small>
                        </label>
                    </div>

                    <div class="form-row">
                        <div class="form-split">
                            <label>
                                Event Date *
                                <input type="date" name="event_date" required value="<?= e($_POST['event_date'] ?? '') ?>">
                            </label>
                            <label>
                                Start Time
                                <input type="time" name="start_time" value="<?= e($_POST['start_time'] ?? '') ?>">
                            </label>
                        </div>
                    </div>

                    <div class="form-row">
                        <label>
                            Venue
                            <select name="venue_id">
                                <option value="">— No venue —</option>
                                <?php foreach ($venues as $venue): ?>
                                    <option value="<?= e($venue['id']) ?>" <?= ($_POST['venue_id'] ?? '') == $venue['id'] ? 'selected' : '' ?>>
                                        <?= e($venue['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>

                    <div class="form-row">
                        <label>
                            Event Owner Name *
                            <input type="text" name="owner" required value="<?= e($_POST['owner'] ?? '') ?>">
                        </label>
                    </div>

                    <div class="form-row">
                        <label>
                            Deputies (comma-separated names)
                            <input type="text" name="deputies" placeholder="Alice, Bob, Charlie" value="<?= e($_POST['deputies'] ?? '') ?>">
                        </label>
                    </div>

                    <div class="form-row">
                        <label>
                            Tags (comma-separated)
                            <input type="text" name="tags" placeholder="music, festival, family" value="<?= e($_POST['tags'] ?? '') ?>">
                        </label>
                    </div>

                    <div class="form-row">
                        <button type="submit" class="button">Create Event</button>
                        <a href="index.php?page=events" class="button-small" style="margin-left: 1rem; background: var(--secondary-color);">Cancel</a>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= e($siteName) ?></p>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>
