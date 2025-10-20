<?php

declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/managers/EventManager.php';
require __DIR__ . '/../includes/managers/PhotoManager.php';

if (file_exists(__DIR__ . '/../config.php')) {
    require __DIR__ . '/../config.php';
}

header('Content-Type: application/json');

try {
    $pdo = Database::connect();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error']);
    exit;
}

$uploadDir = defined('UPLOAD_DIR') ? rtrim((string) UPLOAD_DIR, '/') : __DIR__ . '/uploads';
$maxUploadSize = defined('MAX_UPLOAD_SIZE') ? (int) MAX_UPLOAD_SIZE : 5_242_880;

$eventManager = new EventManager($pdo, $uploadDir);
$photoManager = new PhotoManager($pdo, $uploadDir, $maxUploadSize);

$action = $_GET['action'] ?? '';

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function load_people(): array {
    $file = __DIR__ . '/../data/people.json';
    if (!file_exists($file)) {
        return [];
    }
    $json = file_get_contents($file);
    $arr = json_decode((string) $json, true);
    return is_array($arr) ? $arr : [];
}

function is_event_editor(PDO $pdo, int $eventId, string $user): bool {
    $stmt = $pdo->prepare('SELECT owner_name, deputies FROM events WHERE id = :id');
    $stmt->execute([':id' => $eventId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return false;
    $owner = (string) ($row['owner_name'] ?? '');
    if (strcasecmp($owner, $user) === 0) return true;
    $deps = [];
    if (!empty($row['deputies'])) {
        $tmp = json_decode((string)$row['deputies'], true);
        if (is_array($tmp)) $deps = $tmp;
    }
    return in_array($user, $deps, true) || is_admin($user);
}

function latest_share_message(PDO $pdo, int $eventId, string $person): ?string {
    try {
        $stmt = $pdo->prepare('SELECT message FROM event_share_messages WHERE event_id = :event_id AND shared_with = :person ORDER BY id DESC LIMIT 1');
        $stmt->execute([':event_id' => $eventId, ':person' => $person]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (string) ($row['message'] ?? '') : null;
    } catch (Throwable $e) {
        return null;
    }
}

function is_banned(PDO $pdo, string $identifier): bool {
    $identifier = trim($identifier);
    if ($identifier === '') return false;
    try {
        $stmt = $pdo->prepare('SELECT 1 FROM banned_users WHERE user_identifier = :u AND ends_at > NOW() LIMIT 1');
        $stmt->execute([':u' => $identifier]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

switch ($action) {
    case 'search_people': {
        $q = trim((string) ($_GET['q'] ?? ''));
        $eventId = (int) ($_GET['event_id'] ?? 0);
        $event = $eventId > 0 ? $eventManager->findById($eventId) : null;

        $people = load_people();
        $qLower = strtolower($q);
        $eventText = '';
        if ($event) {
            $eventText = strtolower(trim(($event['title'] ?? '') . ' ' . ($event['description'] ?? '')));
        }

        $matches = array_values(array_filter($people, static function(array $p) use ($qLower, $eventText): bool {
            $name = strtolower($p['display_name'] ?? '');
            $handle = strtolower($p['handle'] ?? '');
            $byPrefix = $qLower === '' || substr($name, 0, strlen($qLower)) === $qLower || substr($handle, 0, strlen($qLower)) === $qLower;
            $interests = array_map('strtolower', $p['interests'] ?? []);
            $byInterests = $eventText !== '' && array_reduce($interests, function($carry, $tag) use ($eventText) {
                return $carry || (strpos($eventText, $tag) !== false);
            }, false);
            return $byPrefix || $byInterests;
        }));

        echo json_encode(['success' => true, 'people' => array_slice($matches, 0, 10)]);
        break;
    }

    case 'list_shares': {
        $eventId = (int) ($_GET['event_id'] ?? 0);
        if ($eventId <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'event_id required']); break; }
        $event = $eventManager->findById($eventId);
        $names = $event ? ($event['shared_with'] ?? []) : [];
        $people = load_people();
        $byName = [];
        foreach ($people as $p) { $byName[strtolower($p['display_name'])] = $p; }
        $list = [];
        foreach ($names as $n) {
            $p = $byName[strtolower($n)] ?? null;
            $list[] = [
                'person' => $n,
                'display_name' => $p['display_name'] ?? $n,
                'handle' => $p['handle'] ?? null,
                'avatar' => $p['avatar'] ?? null,
                'message' => latest_share_message($pdo, $eventId, $n),
            ];
        }
        echo json_encode(['success' => true, 'shares' => $list]);
        break;
    }

    case 'share': {
        $data = read_json_body();
        $eventId = (int) ($data['event_id'] ?? 0);
        $person = trim((string) ($data['person'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));
        $actor = $_SESSION['current_user'] ?? '';
        if ($eventId <= 0 || $person === '') { http_response_code(400); echo json_encode(['success'=>false,'message'=>'event_id and person required']); break; }
        if (is_banned($pdo, $actor)) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'banned']); break; }
        $eventManager->share((string)$eventId, [$person]);
        if ($message !== '') {
            try {
                $stmt = $pdo->prepare('INSERT INTO event_share_messages (event_id, shared_with, message) VALUES (:event_id, :shared_with, :message)');
                $stmt->execute([':event_id' => $eventId, ':shared_with' => $person, ':message' => $message]);
            } catch (Throwable $e) {
                // ignore
            }
        }
        echo json_encode(['success' => true]);
        break;
    }

    case 'unshare': {
        $data = read_json_body();
        $eventId = (int) ($data['event_id'] ?? 0);
        $person = trim((string) ($data['person'] ?? ''));
        if ($eventId <= 0 || $person === '') { http_response_code(400); echo json_encode(['success'=>false,'message'=>'event_id and person required']); break; }
        $eventManager->unshare((string)$eventId, $person);
        echo json_encode(['success' => true]);
        break;
    }

    case 'add_deputy': {
        $data = read_json_body();
        $eventId = (int) ($data['event_id'] ?? 0);
        $person = trim((string) ($data['person'] ?? ''));
        $actor = $_SESSION['current_user'] ?? '';
        if ($eventId <= 0 || $person === '') { http_response_code(400); echo json_encode(['success'=>false,'message'=>'event_id and person required']); break; }
        if (is_banned($pdo, $actor)) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'banned']); break; }
        $event = $eventManager->addDeputy($eventId, $person);
        echo json_encode(['success' => (bool) $event, 'event' => $event]);
        break;
    }

    case 'upload_photo': {
        $eventId = (int) ($_POST['event_id'] ?? 0);
        if ($eventId <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'event_id required']); break; }
        $uploadedBy = trim((string) ($_POST['uploaded_by'] ?? 'Anonymous'));
        if (is_banned($pdo, $uploadedBy)) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'banned']); break; }
        $photo = $photoManager->add($_FILES['photo'] ?? [], [ 'caption' => null, 'uploaded_by' => $uploadedBy, 'event_id' => $eventId ]);
        if (!$photo) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'upload failed']); break; }
        echo json_encode(['success' => true, 'photo' => $photo]);
        break;
    }

    case 'add_photo_comment': {
        $data = read_json_body();
        $photoId = (int) ($data['photo_id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));
        $comment = trim((string) ($data['comment'] ?? ''));
        if ($photoId <= 0 || $name === '' || $comment === '') { http_response_code(400); echo json_encode(['success'=>false]); break; }
        if (is_banned($pdo, $name)) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'banned']); break; }
        $photoManager->addComment((string)$photoId, $name, $comment);
        echo json_encode(['success' => true]);
        break;
    }

    case 'list_event_comments': {
        $eventId = (int) ($_GET['event_id'] ?? 0);
        if ($eventId <= 0) { http_response_code(400); echo json_encode(['success'=>false]); break; }
        $comments = $eventManager->getEventComments($eventId);
        echo json_encode(['success' => true, 'comments' => $comments]);
        break;
    }

    case 'add_event_comment': {
        $data = read_json_body();
        $eventId = (int) ($data['event_id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));
        $comment = trim((string) ($data['comment'] ?? ''));
        if ($eventId <= 0 || $name === '' || $comment === '') { http_response_code(400); echo json_encode(['success'=>false]); break; }
        if (is_banned($pdo, $name)) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'banned']); break; }
        $row = $eventManager->addEventComment($eventId, $name, $comment);
        echo json_encode(['success' => (bool) $row, 'comment' => $row]);
        break;
    }

    case 'upload_event_image': {
        $eventId = (int) ($_POST['event_id'] ?? 0);
        if ($eventId <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'event_id required']); break; }
        $event = $eventManager->updateImage($eventId, $_FILES['image'] ?? []);
        if (!$event) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'upload failed']); break; }
        echo json_encode(['success' => true, 'event' => $event]);
        break;
    }

    case 'delete_event_image': {
        $data = read_json_body();
        $eventId = (int) ($data['event_id'] ?? 0);
        $currentUser = $_SESSION['current_user'] ?? '';
        if ($eventId <= 0) { http_response_code(400); echo json_encode(['success'=>false]); break; }
        if (!is_event_editor($pdo, $eventId, $currentUser)) { http_response_code(403); echo json_encode(['success'=>false]); break; }
        $stmt = $pdo->prepare('SELECT image FROM events WHERE id = :id');
        $stmt->execute([':id' => $eventId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['image'])) {
            $path = rtrim($uploadDir, '/') . '/' . $row['image'];
            if (is_file($path)) { @unlink($path); }
        }
        $upd = $pdo->prepare('UPDATE events SET image = NULL WHERE id = :id');
        $upd->execute([':id' => $eventId]);
        echo json_encode(['success' => true]);
        break;
    }

    case 'delete_photo': {
        $data = read_json_body();
        $photoId = (int) ($data['photo_id'] ?? 0);
        if ($photoId <= 0) { http_response_code(400); echo json_encode(['success'=>false]); break; }
        $stmt = $pdo->prepare('SELECT p.filename, p.event_id FROM photos p WHERE p.id = :id');
        $stmt->execute([':id' => $photoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) { http_response_code(404); echo json_encode(['success'=>false]); break; }
        $currentUser = $_SESSION['current_user'] ?? '';
        $eventId = (int) ($row['event_id'] ?? 0);
        if ($eventId && !is_event_editor($pdo, $eventId, $currentUser)) { http_response_code(403); echo json_encode(['success'=>false]); break; }
        $filepath = rtrim($uploadDir, '/') . '/' . $row['filename'];
        if (is_file($filepath)) { @unlink($filepath); }
        $del = $pdo->prepare('DELETE FROM photos WHERE id = :id');
        $del->execute([':id' => $photoId]);
        echo json_encode(['success' => true]);
        break;
    }

    case 'delete_photo_comment': {
        $data = read_json_body();
        $commentId = (int) ($data['comment_id'] ?? 0);
        if ($commentId <= 0) { http_response_code(400); echo json_encode(['success'=>false]); break; }
        $stmt = $pdo->prepare('SELECT p.event_id FROM photo_comments pc JOIN photos p ON p.id = pc.photo_id WHERE pc.id = :id');
        $stmt->execute([':id' => $commentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) { http_response_code(404); echo json_encode(['success'=>false]); break; }
        $currentUser = $_SESSION['current_user'] ?? '';
        $eventId = (int) ($row['event_id'] ?? 0);
        if (!is_event_editor($pdo, $eventId, $currentUser)) { http_response_code(403); echo json_encode(['success'=>false]); break; }
        $del = $pdo->prepare('DELETE FROM photo_comments WHERE id = :id');
        $del->execute([':id' => $commentId]);
        echo json_encode(['success' => true]);
        break;
    }

    case 'delete_event_comment': {
        $data = read_json_body();
        $commentId = (int) ($data['comment_id'] ?? 0);
        if ($commentId <= 0) { http_response_code(400); echo json_encode(['success'=>false]); break; }
        $stmt = $pdo->prepare('SELECT ec.event_id FROM event_comments ec WHERE ec.id = :id');
        $stmt->execute([':id' => $commentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) { http_response_code(404); echo json_encode(['success'=>false]); break; }
        $currentUser = $_SESSION['current_user'] ?? '';
        $eventId = (int) ($row['event_id'] ?? 0);
        if (!is_event_editor($pdo, $eventId, $currentUser)) { http_response_code(403); echo json_encode(['success'=>false]); break; }
        $del = $pdo->prepare('DELETE FROM event_comments WHERE id = :id');
        $del->execute([':id' => $commentId]);
        echo json_encode(['success' => true]);
        break;
    }

    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
