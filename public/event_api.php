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
            ];
        }
        echo json_encode(['success' => true, 'shares' => $list]);
        break;
    }

    case 'share': {
        $data = read_json_body();
        $eventId = (int) ($data['event_id'] ?? 0);
        $person = trim((string) ($data['person'] ?? ''));
        if ($eventId <= 0 || $person === '') { http_response_code(400); echo json_encode(['success'=>false,'message'=>'event_id and person required']); break; }
        $eventManager->share((string)$eventId, [$person]);
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
        if ($eventId <= 0 || $person === '') { http_response_code(400); echo json_encode(['success'=>false,'message'=>'event_id and person required']); break; }
        $event = $eventManager->addDeputy($eventId, $person);
        echo json_encode(['success' => (bool) $event, 'event' => $event]);
        break;
    }

    case 'upload_photo': {
        $eventId = (int) ($_POST['event_id'] ?? 0);
        if ($eventId <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'event_id required']); break; }
        $uploadedBy = trim((string) ($_POST['uploaded_by'] ?? 'Anonymous'));
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

    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
