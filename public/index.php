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

// Basic role handling for guest accounts
if (isset($_GET['role'])) {
    $role = strtolower(trim((string) $_GET['role']));
    $_SESSION['role'] = in_array($role, ['guest','member','admin'], true) ? $role : 'guest';
} elseif (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'guest';
}

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

if ($page === 'home' && !isset($_GET['action']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: calendar-7x5.php');
    exit;
}

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
        case 'set_theme':
            handleSetTheme();
            break;
        case 'delete_photo':
            handleDeletePhoto($pdo, $uploadDir);
            break;
        case 'delete_venue_image':
            handleDeleteVenueImage($pdo, $uploadDir);
            break;
        case 'update_photo_comment':
            handleUpdatePhotoComment($pdo);
            break;
        case 'delete_photo_comment':
            handleDeletePhotoComment($pdo);
            break;
        case 'update_event_comment':
            handleUpdateEventComment($pdo);
            break;
        case 'delete_event_comment':
            handleDeleteEventComment($pdo);
            break;
        case 'kick_user':
            handleKickUser($pdo);
            break;
        case 'update_event':
            handleUpdateEvent($eventManager);
            break;
        case 'delete_event':
            handleDeleteEvent($eventManager);
            break;
        case 'update_venue':
            handleUpdateVenue($venueManager);
            break;
        case 'delete_venue':
            handleDeleteVenue($venueManager);
            break;
    }
}

$venues = $venueManager->all();
$events = $eventManager->all();
$photos = $photoManager->all();
$upcomingEvents = $eventManager->upcoming(5);
$siteName = SITE_NAME;

// Theme handling (default to dark)
$theme = (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'light') ? 'light' : 'dark';
$bodyClass = $theme === 'light' ? 'theme-light' : 'theme-dark';

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

function handleSetTheme(): void
{
    $theme = $_POST['theme'] ?? 'dark';
    $redirect = $_POST['redirect'] ?? '?page=account_settings';
    $theme = $theme === 'light' ? 'light' : 'dark';
    setcookie('theme', $theme, [
        'expires' => time() + 60 * 60 * 24 * 365,
        'path' => '/',
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
    set_flash('Theme updated to ' . ucfirst($theme) . '.');
    redirect($redirect);
}

function handleDeletePhoto(PDO $pdo, string $uploadDir): void
{
    $photoId = (int) ($_POST['photo_id'] ?? 0);
    $redirect = $_POST['redirect'] ?? '?page=account&tab=photos';
    $currentUser = $_SESSION['current_user'] ?? '';
    if ($photoId <= 0) {
        set_flash('Invalid photo.', 'error');
        redirect($redirect);
    }
    $stmt = $pdo->prepare('SELECT p.filename, p.uploaded_by, e.owner_name, e.deputies FROM photos p LEFT JOIN events e ON e.id = p.event_id WHERE p.id = :id');
    $stmt->execute([':id' => $photoId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        set_flash('Photo not found.', 'error');
        redirect($redirect);
    }
    $isOwner = strcasecmp((string)($row['owner_name'] ?? ''), $currentUser) === 0;
    $isDeputy = false;
    if (!empty($row['deputies'])) {
        $deps = json_decode((string)$row['deputies'], true);
        $isDeputy = is_array($deps) && in_array($currentUser, $deps, true);
    }
    if (($row['uploaded_by'] ?? '') !== $currentUser && !$isOwner && !$isDeputy && !is_admin($currentUser)) {
        set_flash('You can only delete your own photos or photos for events you manage.', 'error');
        redirect($redirect);
    }
    $filepath = rtrim($uploadDir, '/') . '/' . $row['filename'];
    if (is_file($filepath)) {
        @unlink($filepath);
    }
    $del = $pdo->prepare('DELETE FROM photos WHERE id = :id');
    $del->execute([':id' => $photoId]);
    set_flash('Photo deleted.');
    redirect($redirect);
}

function handleDeleteVenueImage(PDO $pdo, string $uploadDir): void
{
    $venueId = (int) ($_POST['venue_id'] ?? 0);
    $redirect = '?page=venue&id=' . $venueId;
    $currentUser = $_SESSION['current_user'] ?? '';
    if ($venueId <= 0) {
        set_flash('Invalid venue.', 'error');
        redirect('?page=venues');
    }
    $stmt = $pdo->prepare('SELECT image, owner_name, deputies FROM venues WHERE id = :id');
    $stmt->execute([':id' => $venueId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        set_flash('Venue not found.', 'error');
        redirect('?page=venues');
    }
    $isOwner = strcasecmp((string)($row['owner_name'] ?? ''), $currentUser) === 0;
    $isDeputy = false;
    if (!empty($row['deputies'])) {
        $deps = json_decode((string)$row['deputies'], true);
        $isDeputy = is_array($deps) && in_array($currentUser, $deps, true);
    }
    if (!$isOwner && !$isDeputy && !is_admin($currentUser)) {
        set_flash('You do not have permission to modify this venue.', 'error');
        redirect($redirect);
    }
    if (!empty($row['image'])) {
        $filepath = rtrim($uploadDir, '/') . '/' . $row['image'];
        if (is_file($filepath)) {
            @unlink($filepath);
        }
    }
    $upd = $pdo->prepare('UPDATE venues SET image = NULL WHERE id = :id');
    $upd->execute([':id' => $venueId]);
    set_flash('Venue image removed.');
    redirect($redirect);
}

function canModifyForEventDate(?string $date): bool
{
    if (!$date) {
        return true;
    }
    $today = new DateTimeImmutable('today');
    $eventDate = DateTimeImmutable::createFromFormat('Y-m-d', $date);
    return $eventDate && $eventDate >= $today;
}

function handleKickUser(PDO $pdo): void
{
    $identifier = trim((string) ($_POST['user_identifier'] ?? ''));
    $duration = (int) ($_POST['duration'] ?? 0);
    $unit = strtolower(trim((string) ($_POST['unit'] ?? 'hours')));
    $redirect = '?page=admin';
    $currentUser = $_SESSION['current_user'] ?? '';
    if (!is_admin($currentUser)) {
        set_flash('Only admins can perform this action.', 'error');
        redirect($redirect);
    }
    if ($identifier === '' || $duration <= 0) {
        set_flash('Please provide a user identifier and a valid duration.', 'error');
        redirect($redirect);
    }
    $dt = new DateTimeImmutable('now');
    switch ($unit) {
        case 'months':
        case 'month':
            $ends = $dt->modify('+' . $duration . ' months');
            break;
        case 'days':
        case 'day':
            $ends = $dt->modify('+' . $duration . ' days');
            break;
        default:
            $ends = $dt->modify('+' . $duration . ' hours');
            break;
    }
    $stmt = $pdo->prepare('INSERT INTO banned_users (user_identifier, ends_at) VALUES (:u, :ends)');
    $stmt->execute([':u' => $identifier, ':ends' => $ends->format('Y-m-d H:i:s')]);
    set_flash('User kicked until ' . $ends->format('Y-m-d H:i'), 'success');
    redirect($redirect);
}

function handleUpdatePhotoComment(PDO $pdo): void
{
    $commentId = (int) ($_POST['comment_id'] ?? 0);
    $text = trim($_POST['comment_text'] ?? '');
    $redirect = $_POST['redirect'] ?? '?page=account&tab=comments';
    $currentUser = $_SESSION['current_user'] ?? '';
    if ($commentId <= 0 || $text === '') {
        set_flash('Invalid comment data.', 'error');
        redirect($redirect);
    }
    $stmt = $pdo->prepare('SELECT pc.name, e.event_date, e.owner_name, e.deputies
        FROM photo_comments pc
        JOIN photos p ON p.id = pc.photo_id
        LEFT JOIN events e ON e.id = p.event_id
        WHERE pc.id = :id');
    $stmt->execute([':id' => $commentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        set_flash('Comment not found.', 'error');
        redirect($redirect);
    }
    $isOwner = strcasecmp((string)($row['owner_name'] ?? ''), $currentUser) === 0;
    $isDeputy = false;
    if (!empty($row['deputies'])) {
        $deps = json_decode((string)$row['deputies'], true);
        $isDeputy = is_array($deps) && in_array($currentUser, $deps, true);
    }
    if (($row['name'] ?? '') !== $currentUser && !$isOwner && !$isDeputy && !is_admin($currentUser)) {
        set_flash('You can only edit your own comments or comments on events you manage.', 'error');
        redirect($redirect);
    }
    if (!is_admin($currentUser) && !canModifyForEventDate($row['event_date'] ?? null)) {
        set_flash('This event has passed. You cannot edit this comment.', 'error');
        redirect($redirect);
    }
    $upd = $pdo->prepare('UPDATE photo_comments SET comment = :comment WHERE id = :id');
    $upd->execute([':comment' => $text, ':id' => $commentId]);
    set_flash('Comment updated.');
    redirect($redirect);
}

function handleDeletePhotoComment(PDO $pdo): void
{
    $commentId = (int) ($_POST['comment_id'] ?? 0);
    $redirect = $_POST['redirect'] ?? '?page=account&tab=comments';
    $currentUser = $_SESSION['current_user'] ?? '';
    if ($commentId <= 0) {
        set_flash('Invalid comment.', 'error');
        redirect($redirect);
    }
    $stmt = $pdo->prepare('SELECT pc.name, e.event_date, e.owner_name, e.deputies
        FROM photo_comments pc
        JOIN photos p ON p.id = pc.photo_id
        LEFT JOIN events e ON e.id = p.event_id
        WHERE pc.id = :id');
    $stmt->execute([':id' => $commentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        set_flash('Comment not found.', 'error');
        redirect($redirect);
    }
    $isOwner = strcasecmp((string)($row['owner_name'] ?? ''), $currentUser) === 0;
    $isDeputy = false;
    if (!empty($row['deputies'])) {
        $deps = json_decode((string)$row['deputies'], true);
        $isDeputy = is_array($deps) && in_array($currentUser, $deps, true);
    }
    if (($row['name'] ?? '') !== $currentUser && !$isOwner && !$isDeputy && !is_admin($currentUser)) {
        set_flash('You can only delete your own comments or comments on events you manage.', 'error');
        redirect($redirect);
    }
    if (!is_admin($currentUser) && !canModifyForEventDate($row['event_date'] ?? null)) {
        set_flash('This event has passed. You cannot delete this comment.', 'error');
        redirect($redirect);
    }
    $del = $pdo->prepare('DELETE FROM photo_comments WHERE id = :id');
    $del->execute([':id' => $commentId]);
    set_flash('Comment deleted.');
    redirect($redirect);
}

function handleUpdateEventComment(PDO $pdo): void
{
    $commentId = (int) ($_POST['comment_id'] ?? 0);
    $text = trim($_POST['comment_text'] ?? '');
    $redirect = $_POST['redirect'] ?? '?page=account&tab=comments';
    $currentUser = $_SESSION['current_user'] ?? '';
    if ($commentId <= 0 || $text === '') {
        set_flash('Invalid comment data.', 'error');
        redirect($redirect);
    }
    $stmt = $pdo->prepare('SELECT ec.name, e.event_date, e.owner_name, e.deputies
        FROM event_comments ec
        JOIN events e ON e.id = ec.event_id
        WHERE ec.id = :id');
    $stmt->execute([':id' => $commentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        set_flash('Comment not found.', 'error');
        redirect($redirect);
    }
    $isOwner = strcasecmp((string)($row['owner_name'] ?? ''), $currentUser) === 0;
    $isDeputy = false;
    if (!empty($row['deputies'])) {
        $deps = json_decode((string)$row['deputies'], true);
        $isDeputy = is_array($deps) && in_array($currentUser, $deps, true);
    }
    if (($row['name'] ?? '') !== $currentUser && !$isOwner && !$isDeputy && !is_admin($currentUser)) {
        set_flash('You can only edit your own comments or comments on events you manage.', 'error');
        redirect($redirect);
    }
    if (!is_admin($currentUser) && !canModifyForEventDate($row['event_date'] ?? null)) {
        set_flash('This event has passed. You cannot edit this comment.', 'error');
        redirect($redirect);
    }
    $upd = $pdo->prepare('UPDATE event_comments SET comment = :comment WHERE id = :id');
    $upd->execute([':comment' => $text, ':id' => $commentId]);
    set_flash('Comment updated.');
    redirect($redirect);
}

function handleDeleteEventComment(PDO $pdo): void
{
    $commentId = (int) ($_POST['comment_id'] ?? 0);
    $redirect = $_POST['redirect'] ?? '?page=account&tab=comments';
    $currentUser = $_SESSION['current_user'] ?? '';
    if ($commentId <= 0) {
        set_flash('Invalid comment.', 'error');
        redirect($redirect);
    }
    $stmt = $pdo->prepare('SELECT ec.name, e.event_date, e.owner_name, e.deputies
        FROM event_comments ec
        JOIN events e ON e.id = ec.event_id
        WHERE ec.id = :id');
    $stmt->execute([':id' => $commentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        set_flash('Comment not found.', 'error');
        redirect($redirect);
    }
    $isOwner = strcasecmp((string)($row['owner_name'] ?? ''), $currentUser) === 0;
    $isDeputy = false;
    if (!empty($row['deputies'])) {
        $deps = json_decode((string)$row['deputies'], true);
        $isDeputy = is_array($deps) && in_array($currentUser, $deps, true);
    }
    if (($row['name'] ?? '') !== $currentUser && !$isOwner && !$isDeputy && !is_admin($currentUser)) {
        set_flash('You can only delete your own comments or comments on events you manage.', 'error');
        redirect($redirect);
    }
    if (!is_admin($currentUser) && !canModifyForEventDate($row['event_date'] ?? null)) {
        set_flash('This event has passed. You cannot delete this comment.', 'error');
        redirect($redirect);
    }
    $del = $pdo->prepare('DELETE FROM event_comments WHERE id = :id');
    $del->execute([':id' => $commentId]);
    set_flash('Comment deleted.');
    redirect($redirect);
}

function handleUpdateEvent(EventManager $eventManager): void
{
    $eventId = (int) ($_POST['event_id'] ?? 0);
    $currentUser = $_SESSION['current_user'] ?? '';

    if ($eventId <= 0) {
        set_flash('Invalid event.', 'error');
        redirect('?page=events');
    }

    $event = $eventManager->findById($eventId);
    if (!$event) {
        set_flash('Event not found.', 'error');
        redirect('?page=events');
    }

    $isEditor = $currentUser === ($event['owner'] ?? '') || in_array($currentUser, $event['deputies'] ?? [], true);
    if (!$isEditor && !is_admin($currentUser)) {
        set_flash('You do not have permission to edit this event.', 'error');
        redirect('?page=event&id=' . $eventId);
    }

    $imageFile = null;
    if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $imageFile = $_FILES['image'];
    }

    $result = $eventManager->update($eventId, [
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'event_date' => trim($_POST['event_date'] ?? ''),
        'start_time' => trim($_POST['start_time'] ?? ''),
        'venue_id' => $_POST['venue_id'] ?: null,
        'owner' => trim($_POST['owner'] ?? ''),
        'deputies' => normalize_list_input($_POST['deputies'] ?? ''),
        'tags' => normalize_list_input($_POST['tags'] ?? ''),
    ], $imageFile);

    if ($result) {
        set_flash('Event updated successfully!');
    } else {
        set_flash('Failed to update event. Please check all required fields.', 'error');
    }

    redirect('?page=event&id=' . $eventId);
}

function handleDeleteEvent(EventManager $eventManager): void
{
    $eventId = (int) ($_POST['event_id'] ?? 0);
    $currentUser = $_SESSION['current_user'] ?? '';

    if ($eventId <= 0) {
        set_flash('Invalid event.', 'error');
        redirect('?page=events');
    }

    $event = $eventManager->findById($eventId);
    if (!$event) {
        set_flash('Event not found.', 'error');
        redirect('?page=events');
    }

    $isOwner = strcasecmp((string)($event['owner'] ?? ''), $currentUser) === 0;
    $isDeputy = in_array($currentUser, $event['deputies'] ?? [], true);

    if (!$isOwner && !$isDeputy && !is_admin($currentUser)) {
        set_flash('You do not have permission to delete this event.', 'error');
        redirect('?page=event&id=' . $eventId);
    }

    if ($eventManager->delete($eventId)) {
        set_flash('Event deleted successfully.');
    } else {
        set_flash('Failed to delete event.', 'error');
    }

    redirect('?page=events');
}

function handleUpdateVenue(VenueManager $venueManager): void
{
    $venueId = (int) ($_POST['venue_id'] ?? 0);
    $currentUser = $_SESSION['current_user'] ?? '';

    if ($venueId <= 0) {
        set_flash('Invalid venue.', 'error');
        redirect('?page=venues');
    }

    $venue = $venueManager->findById($venueId);
    if (!$venue) {
        set_flash('Venue not found.', 'error');
        redirect('?page=venues');
    }

    $isEditor = strcasecmp($currentUser, (string) ($venue['owner'] ?? '')) === 0 || in_array($currentUser, $venue['deputies'] ?? [], true);
    if (!$isEditor && !is_admin($currentUser)) {
        set_flash('You do not have permission to edit this venue.', 'error');
        redirect('?page=venue&id=' . $venueId);
    }

    $imageFile = null;
    if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $imageFile = $_FILES['image'];
    }

    $result = $venueManager->update($venueId, [
        'name' => trim($_POST['name'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'city' => trim($_POST['city'] ?? ''),
        'state' => trim($_POST['state'] ?? ''),
        'zip_code' => trim($_POST['zip_code'] ?? ''),
        'owner' => trim($_POST['owner'] ?? ''),
        'deputies' => normalize_list_input($_POST['deputies'] ?? ''),
        'tags' => normalize_list_input($_POST['tags'] ?? ''),
    ], $imageFile);

    if ($result) {
        set_flash('Venue updated successfully!');
    } else {
        set_flash('Failed to update venue. Please check all required fields.', 'error');
    }

    redirect('?page=venue&id=' . $venueId);
}

function handleDeleteVenue(VenueManager $venueManager): void
{
    $venueId = (int) ($_POST['venue_id'] ?? 0);
    $currentUser = $_SESSION['current_user'] ?? '';

    if ($venueId <= 0) {
        set_flash('Invalid venue.', 'error');
        redirect('?page=venues');
    }

    $venue = $venueManager->findById($venueId);
    if (!$venue) {
        set_flash('Venue not found.', 'error');
        redirect('?page=venues');
    }

    $isOwner = strcasecmp((string)($venue['owner'] ?? ''), $currentUser) === 0;
    $isDeputy = in_array($currentUser, $venue['deputies'] ?? [], true);

    if (!$isOwner && !$isDeputy && !is_admin($currentUser)) {
        set_flash('You do not have permission to delete this venue.', 'error');
        redirect('?page=venue&id=' . $venueId);
    }

    if ($venueManager->delete($venueId)) {
        set_flash('Venue deleted successfully.');
    } else {
        set_flash('Failed to delete venue.', 'error');
    }

    redirect('?page=venues');
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
<body class="<?= e($bodyClass) ?>">
    <header>
        <div class="container">
            <h1><a href="?"> <?= e($siteName) ?> </a></h1>
            <nav>
                <a href="?" class="<?= $page === 'home' ? 'active' : '' ?>">Home</a>
                <a href="event-list.php" class="<?= $page === 'events' ? 'active' : '' ?>">Events</a>
                <a href="calendar-7x5.php" class="<?= $page === 'calendar' ? 'active' : '' ?>">Calendar</a>
                <a href="venue-info.php" class="<?= $page === 'venues' ? 'active' : '' ?>">Venues</a>
                <a href="tags.php" class="<?= $page === 'tags' ? 'active' : '' ?>">Tags</a>
                <?php if (!is_guest()): ?>
                    <a href="account.php" class="<?= $page === 'account' ? 'active' : '' ?>">Account</a>
                    <a href="add-event.php">Add Event</a>
                <?php endif; ?>
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
            case 'venue':
                include __DIR__ . '/../includes/pages/venue.php';
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
            case 'tags':
                include __DIR__ . '/../includes/pages/tags.php';
                break;
            case 'shared':
                include __DIR__ . '/../includes/pages/shared.php';
                break;
            case 'account':
                include __DIR__ . '/../includes/pages/account.php';
                break;
            case 'account_info':
                include __DIR__ . '/../includes/pages/account_info.php';
                break;
            case 'account_settings':
                include __DIR__ . '/../includes/pages/account_settings.php';
                break;
            case 'account_events':
                include __DIR__ . '/../includes/pages/account_events.php';
                break;
            case 'event_edit':
                include __DIR__ . '/../includes/pages/event_edit.php';
                break;
            case 'venue_edit':
                include __DIR__ . '/../includes/pages/venue_edit.php';
                break;
            case 'venue_info':
                include __DIR__ . '/../includes/pages/venue_info.php';
                break;
            case 'admin':
                include __DIR__ . '/../includes/pages/admin.php';
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
