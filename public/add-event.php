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
            'end_time' => trim($_POST['end_time'] ?? ''),
            'venue_id' => $_POST['venue_id'] ?: null,
            'owner' => $owner,
            'deputies' => normalize_list_input($_POST['deputies'] ?? ''),
            'tags' => normalize_list_input($_POST['tags'] ?? ''),
            'is_recurring' => !empty($_POST['is_recurring']),
            'recurrence_pattern' => trim($_POST['recurrence_pattern'] ?? ''),
            'recurrence_end_date' => trim($_POST['recurrence_end_date'] ?? ''),
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
    <title>LENSsf::Add Event</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/add-event.css">
</head>
<body data-theme="light">
    <header>
        <div class="container">
            <h1><a href="index.php"><?= e($siteName) ?></a></h1>
            <nav>
                <a href="calendar-home.php">Home</a>
                <a href="event-list.php">Events</a>
                <a href="calendar-7x5.php">Calendar</a>
                <a href="venue-info.php">Venues</a>
                <a href="tags.php">Tags</a>
                <a href="account.php">Account</a>
                <a href="add-event.php" class="active">Add Event</a>
                <button class="theme-toggle" onclick="toggleTheme()" style="background: var(--primary-color); color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.5rem; cursor: pointer;">
                    <span id="theme-icon">🌙</span>
                </button>
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
                            <label>
                                End Time
                                <input type="time" name="end_time" value="<?= e($_POST['end_time'] ?? '') ?>">
                            </label>
                        </div>
                    </div>

                    <div class="form-row">
                        <label>
                            <input type="checkbox" name="is_recurring" value="1" <?= ($_POST['is_recurring'] ?? '') ? 'checked' : '' ?>> Recurring Event
                        </label>
                    </div>

                    <div class="form-row" id="recurring-options" style="display: none;">
                        <label>
                            Recurrence Pattern *
                            <select name="recurrence_pattern">
                                <option value="">Select pattern</option>
                                <option value="every_week">Every week</option>
                                <option value="every_two_weeks">Every 2 weeks</option>
                                <option value="every_month">Every month</option>
                                <option value="every_two_months">Every 2 months</option>
                                <option value="first_monday">First Monday of the month</option>
                                <option value="first_tuesday">First Tuesday of the month</option>
                                <option value="first_wednesday">First Wednesday of the month</option>
                                <option value="first_thursday">First Thursday of the month</option>
                                <option value="first_friday">First Friday of the month</option>
                                <option value="second_monday">Second Monday of the month</option>
                                <option value="third_thursday">Third Thursday of the month</option>
                                <option value="last_friday">Last Friday of the month</option>
                            </select>
                        </label>
                        <label>
                            Until Date
                            <input type="date" name="recurrence_end_date" value="<?= e($_POST['recurrence_end_date'] ?? '') ?>">
                            <small>Leave blank for indefinite recurrence</small>
                        </label>
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
    <script src="js/add-event.js"></script>
</body>
</html>
