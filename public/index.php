<?php

declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/managers/EventManager.php';
require __DIR__ . '/../includes/managers/VenueManager.php';
require __DIR__ . '/../includes/managers/PhotoManager.php';

if (file_exists(__DIR__ . '/../config.php')) {
    require __DIR__ . '/../config.php';
}

autoloadSession();
ensureSiteName();

// Simple demo current user support
if (isset($_GET['as'])) {
    $_SESSION['current_user'] = trim((string) $_GET['as']);
} elseif (!isset($_SESSION['current_user'])) {
    $_SESSION['current_user'] = 'Alex Johnson';
}

$uploadDir = defined('UPLOAD_DIR') ? rtrim((string) UPLOAD_DIR, '/') : __DIR__ . '/uploads';
$maxUploadSize = defined('MAX_UPLOAD_SIZE') ? (int) MAX_UPLOAD_SIZE : 5_242_880;

$pdo = Database::connect();
$eventManager = new EventManager($pdo, $uploadDir);
$venueManager = new VenueManager($pdo, $uploadDir);
$photoManager = new PhotoManager($pdo, $uploadDir, $maxUploadSize);

$page = $_GET['page'] ?? 'home';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;

    switch ($action) {
        case 'create_event':
            handleCreateEvent($eventManager);
            break;
        case 'create_venue':
            handleCreateVenue($venueManager);
            break;
        case 'upload_photo':
            handleUploadPhoto($photoManager);
            break;
        case 'add_comment':
            handleAddComment($photoManager);
            break;
        case 'add_to_calendar':
            handleAddToCalendar($eventManager);
            break;
        case 'share_event':
            handleShareEvent($eventManager);
            break;
        case 'add_tag':
            handleAddTag($eventManager);
            break;
    }
}

$venues = $venueManager->all();
$events = $eventManager->all();
$photos = $photoManager->all();
$upcomingEvents = $eventManager->upcoming(5);
$siteName = SITE_NAME;

function autoloadSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function ensureSiteName(): void
{
    if (!defined('SITE_NAME')) {
        define('SITE_NAME', 'Local Event Network Service');
    }
}

function handleCreateEvent(EventManager $eventManager): void
{
    $title = trim($_POST['title'] ?? '');
    $eventDate = trim($_POST['event_date'] ?? '');
    $owner = trim($_POST['owner'] ?? '');

    if ($title === '' || $eventDate === '' || $owner === '') {
        set_flash('Please provide the event title, date, and owner name.', 'error');
        redirect('?page=events#create');
    }

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

    if ($imageFile && (int) ($imageFile['error'] ?? UPLOAD_ERR_OK) === UPLOAD_ERR_OK && empty($event['image'])) {
        set_flash('Event created, but the image could not be uploaded.', 'error');
    } else {
        set_flash('Event created successfully!');
    }

    redirect('?page=events');
}

function handleCreateVenue(VenueManager $venueManager): void
{
    $name = trim($_POST['name'] ?? '');
    $owner = trim($_POST['owner'] ?? '');

    if ($name === '' || $owner === '') {
        set_flash('Please provide the venue name and owner.', 'error');
        redirect('?page=venues#create');
    }

    $imageFile = null;
    if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $imageFile = $_FILES['image'];
    }

    $venue = $venueManager->create([
        'name' => $name,
        'address' => trim($_POST['address'] ?? ''),
        'city' => trim($_POST['city'] ?? ''),
        'state' => trim($_POST['state'] ?? ''),
        'zip_code' => trim($_POST['zip_code'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'owner' => $owner,
        'deputies' => normalize_list_input($_POST['deputies'] ?? ''),
    ], $imageFile);

    if ($imageFile && (int) ($imageFile['error'] ?? UPLOAD_ERR_OK) === UPLOAD_ERR_OK && empty($venue['image'])) {
        set_flash('Venue created, but the image could not be uploaded.', 'error');
    } else {
        set_flash('Venue created successfully!');
    }

    redirect('?page=venues');
}

function handleUploadPhoto(PhotoManager $photoManager): void
{
    $uploadedBy = trim($_POST['uploaded_by'] ?? '');

    if ($uploadedBy === '') {
        set_flash('Please provide your name when uploading a photo.', 'error');
        redirect('?page=photos#upload');
    }

    $photo = $photoManager->add($_FILES['photo'] ?? [], [
        'caption' => trim($_POST['caption'] ?? ''),
        'uploaded_by' => $uploadedBy,
        'event_id' => $_POST['event_id'] ?: null,
    ]);

    if ($photo) {
        set_flash('Photo uploaded successfully!');
    } else {
        set_flash('Failed to upload photo. Please check the file type (JPEG, PNG, GIF) and size (max 5 MB).', 'error');
    }

    redirect('?page=photos');
}

function handleAddComment(PhotoManager $photoManager): void
{
    $photoId = $_POST['photo_id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $comment = trim($_POST['comment'] ?? '');

    if ($photoId === '' || $name === '' || $comment === '') {
        set_flash('Please provide your name and comment.', 'error');
        redirect('?page=photos');
    }

    $photoManager->addComment($photoId, $name, $comment);
    set_flash('Comment added successfully!');
    redirect('?page=photos');
}

function handleAddToCalendar(EventManager $eventManager): void
{
    $eventId = $_POST['event_id'] ?? '';
    $name = trim($_POST['name'] ?? '');

    if ($eventId === '' || $name === '') {
        set_flash('Please provide your name to add the event to a calendar.', 'error');
        redirect('?page=events');
    }

    $eventManager->addCalendarEntry($eventId, $name);
    set_flash('Event added to calendar!');
    redirect('?page=events');
}

function handleShareEvent(EventManager $eventManager): void
{
    $eventId = $_POST['event_id'] ?? '';
    $people = normalize_list_input($_POST['people'] ?? '');

    if ($eventId === '' || $people === []) {
        set_flash('Please provide at least one name to share the event.', 'error');
        redirect('?page=events');
    }

    $eventManager->share($eventId, $people);
    set_flash('Event shared successfully!');
    redirect('?page=events');
}

function handleAddTag(EventManager $eventManager): void
{
    $eventId = (int) ($_POST['event_id'] ?? 0);
    $tag = trim($_POST['tag'] ?? '');

    if ($eventId <= 0 || $tag === '') {
        set_flash('Please provide a tag to add.', 'error');
        redirect('?page=calendar');
    }

    $updated = $eventManager->addTag($eventId, $tag);
    if ($updated === null) {
        set_flash('Could not add tag. Please try again.', 'error');
    } else {
        set_flash('Tag added successfully!');
    }

    redirect('?page=calendar');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($siteName) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="?"> <?= e($siteName) ?> </a></h1>
            <nav>
                <a href="?" class="<?= $page === 'home' ? 'active' : '' ?>">Home</a>
                <a href="?page=events" class="<?= $page === 'events' ? 'active' : '' ?>">Events</a>
                <a href="?page=calendar" class="<?= $page === 'calendar' ? 'active' : '' ?>">Calendar</a>
                <a href="?page=venues" class="<?= $page === 'venues' ? 'active' : '' ?>">Venues</a>
                <a href="?page=photos" class="<?= $page === 'photos' ? 'active' : '' ?>">Photos</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php foreach (get_flashes() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endforeach; ?>

        <?php
        switch ($page) {
            case 'events':
                include __DIR__ . '/../includes/pages/events.php';
                break;
            case 'event':
                include __DIR__ . '/../includes/pages/event.php';
                break;
            case 'venues':
                include __DIR__ . '/../includes/pages/venues.php';
                break;
            case 'photos':
                include __DIR__ . '/../includes/pages/photos.php';
                break;
            case 'calendar':
                include __DIR__ . '/../includes/pages/calendar.php';
                break;
            default:
                include __DIR__ . '/../includes/pages/home.php';
                break;
        }
        ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= e($siteName) ?></p>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>
