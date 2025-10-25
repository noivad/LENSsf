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
    <link rel="stylesheet" href="css/calendar-7x5.css">
</head>
<body data-theme="light">
    <div class="app-container">
        <nav class="sidebar-nav">
            <div class="nav-logo">LENS</div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="calendar-7x5.php" class="nav-link">
                        <span class="nav-icon">🏠</span>
                        <span>Home</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="event-list.php" class="nav-link">
                        <span class="nav-icon">📋</span>
                        <span>Events</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="venue-info.php" class="nav-link">
                        <span class="nav-icon">📍</span>
                        <span>Venues</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="tags.php" class="nav-link">
                        <span class="nav-icon">🏷️</span>
                        <span>Tags</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="add-event.php" class="nav-link active">
                        <span class="nav-icon">➕</span>
                        <span>Add Event</span>
                    </a>
                </li>
            </ul>
        </nav>

        <header class="top-header">
            <h1 class="header-title">LENSsf - Add Event</h1>
            <div class="user-controls">
                <button class="theme-toggle" onclick="toggleTheme()">
                    <span id="theme-icon">☀️</span> Toggle Theme
                </button>
                <div class="user-profile">
                    <img src="https://i.pravatar.cc/150?img=33" alt="User Avatar" class="user-avatar" onclick="toggleUserDropdown()">
                    <div class="user-dropdown" id="userDropdown">
                        <a href="account-contact.php" class="dropdown-item" style="text-decoration: none; color: inherit; display: block;">
                            📧 Contact Info
                        </a>
                        <a href="account-notifications.php" class="dropdown-item" style="text-decoration: none; color: inherit; display: block;">
                            🔔 Notifications
                        </a>
                        <a href="account.php" class="dropdown-item" style="text-decoration: none; color: inherit; display: block;">
                            ⚙️ Account Info
                        </a>
                        <a href="account-past-events.php" class="dropdown-item" style="text-decoration: none; color: inherit; display: block;">
                            📜 My Past Events
                        </a>
                        <div class="dropdown-item" onclick="alert('Logging out...')">
                            🚪 Logout
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="container">
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
                            Recurrence Type *
                            <select name="recurrence_type" id="recurrence_type" onchange="updateRecurrenceOptions()">
                                <option value="">Select type</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly_day">Monthly (specific day of week)</option>
                                <option value="monthly_date">Monthly (specific date)</option>
                                <option value="custom">Custom interval</option>
                            </select>
                        </label>

                        <div id="weekly-options" style="display: none; margin-top: 1rem;">
                            <label>
                                Repeat every
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <input type="number" name="weekly_interval" min="1" max="52" value="1" style="width: 80px;">
                                    <span>week(s)</span>
                                </div>
                            </label>
                        </div>

                        <div id="monthly-day-options" style="display: none; margin-top: 1rem;">
                            <div class="form-split">
                                <label>
                                    Week of month
                                    <select name="month_week">
                                        <option value="first">First</option>
                                        <option value="second">Second</option>
                                        <option value="third">Third</option>
                                        <option value="fourth">Fourth</option>
                                        <option value="last">Last</option>
                                    </select>
                                </label>
                                <label>
                                    Day of week
                                    <select name="day_of_week">
                                        <option value="monday">Monday</option>
                                        <option value="tuesday">Tuesday</option>
                                        <option value="wednesday">Wednesday</option>
                                        <option value="thursday">Thursday</option>
                                        <option value="friday">Friday</option>
                                        <option value="saturday">Saturday</option>
                                        <option value="sunday">Sunday</option>
                                    </select>
                                </label>
                            </div>
                            <label style="margin-top: 1rem;">
                                Every
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <input type="number" name="monthly_day_interval" min="1" max="12" value="1" style="width: 80px;">
                                    <span>month(s)</span>
                                </div>
                                <small>e.g., "2" = every other month, "6" = twice a year</small>
                            </label>
                        </div>

                        <div id="monthly-date-options" style="display: none; margin-top: 1rem;">
                            <label>
                                Repeat every
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <input type="number" name="monthly_date_interval" min="1" max="12" value="1" style="width: 80px;">
                                    <span>month(s) on the same date</span>
                                </div>
                            </label>
                        </div>

                        <div id="custom-options" style="display: none; margin-top: 1rem;">
                            <label>
                                Repeat every
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <input type="number" name="custom_interval" min="1" value="1" style="width: 80px;">
                                    <select name="custom_unit" style="width: auto;">
                                        <option value="days">Day(s)</option>
                                        <option value="weeks">Week(s)</option>
                                        <option value="months">Month(s)</option>
                                    </select>
                                </div>
                            </label>
                        </div>

                        <label style="margin-top: 1rem;">
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
            </div>
        </main>

        <footer class="footer">
            <p>&copy; <?= date('Y') ?> LENSsf - Local Event Network Service | Built with ❤️ for the community</p>
        </footer>
    </div>

    <script src="js/main.js"></script>
    <script src="js/add-event.js"></script>
    <script src="js/calendar-7x5.js"></script>
</body>
</html>
