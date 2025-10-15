<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/core/TagService.php';

class TagController {
    private TagService $tagService;

    public function __construct() {
        $this->tagService = new TagService();
    }

    public function handleRequest(): void {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'add_event_tag':
                if ($method !== 'POST') {
                    $this->methodNotAllowed();
                    return;
                }
                $this->addEventTag($userId);
                break;

            case 'add_venue_tag':
                if ($method !== 'POST') {
                    $this->methodNotAllowed();
                    return;
                }
                $this->addVenueTag($userId);
                break;

            case 'remove_event_tag':
                if ($method !== 'DELETE') {
                    $this->methodNotAllowed();
                    return;
                }
                $this->removeEventTag($userId);
                break;

            case 'remove_venue_tag':
                if ($method !== 'DELETE') {
                    $this->methodNotAllowed();
                    return;
                }
                $this->removeVenueTag($userId);
                break;

            case 'get_event_tags':
                if ($method !== 'GET') {
                    $this->methodNotAllowed();
                    return;
                }
                $this->getEventTags();
                break;

            case 'get_venue_tags':
                if ($method !== 'GET') {
                    $this->methodNotAllowed();
                    return;
                }
                $this->getVenueTags();
                break;

            case 'search_tags':
                if ($method !== 'GET') {
                    $this->methodNotAllowed();
                    return;
                }
                $this->searchTags();
                break;

            case 'get_popular_tags':
                if ($method !== 'GET') {
                    $this->methodNotAllowed();
                    return;
                }
                $this->getPopularTags();
                break;

            case 'get_events_by_tag':
                if ($method !== 'GET') {
                    $this->methodNotAllowed();
                    return;
                }
                $this->getEventsByTag();
                break;

            case 'get_venues_by_tag':
                if ($method !== 'GET') {
                    $this->methodNotAllowed();
                    return;
                }
                $this->getVenuesByTag();
                break;

            default:
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Action not found']);
        }
    }

    private function addEventTag(int $userId): void {
        $data = json_decode(file_get_contents('php://input'), true);
        $tagName = $data['tag_name'] ?? '';
        $eventId = (int) ($data['event_id'] ?? 0);
        $category = $data['category'] ?? null;

        if ($eventId === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Event ID is required']);
            return;
        }

        $result = $this->tagService->addTagToEvent($tagName, $eventId, $userId, $category);
        echo json_encode($result);
    }

    private function addVenueTag(int $userId): void {
        $data = json_decode(file_get_contents('php://input'), true);
        $tagName = $data['tag_name'] ?? '';
        $venueId = (int) ($data['venue_id'] ?? 0);
        $category = $data['category'] ?? null;

        if ($venueId === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Venue ID is required']);
            return;
        }

        $result = $this->tagService->addTagToVenue($tagName, $venueId, $userId, $category);
        echo json_encode($result);
    }

    private function removeEventTag(int $userId): void {
        $data = json_decode(file_get_contents('php://input'), true);
        $tagId = (int) ($data['tag_id'] ?? 0);
        $eventId = (int) ($data['event_id'] ?? 0);

        if ($eventId === 0 || $tagId === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Event ID and Tag ID are required']);
            return;
        }

        $result = $this->tagService->removeTagFromEvent($tagId, $eventId, $userId);
        echo json_encode($result);
    }

    private function removeVenueTag(int $userId): void {
        $data = json_decode(file_get_contents('php://input'), true);
        $tagId = (int) ($data['tag_id'] ?? 0);
        $venueId = (int) ($data['venue_id'] ?? 0);

        if ($venueId === 0 || $tagId === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Venue ID and Tag ID are required']);
            return;
        }

        $result = $this->tagService->removeTagFromVenue($tagId, $venueId, $userId);
        echo json_encode($result);
    }

    private function getEventTags(): void {
        $eventId = (int) ($_GET['event_id'] ?? 0);

        if ($eventId === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Event ID is required']);
            return;
        }

        $tags = $this->tagService->getEventTags($eventId);
        echo json_encode(['success' => true, 'tags' => $tags]);
    }

    private function getVenueTags(): void {
        $venueId = (int) ($_GET['venue_id'] ?? 0);

        if ($venueId === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Venue ID is required']);
            return;
        }

        $tags = $this->tagService->getVenueTags($venueId);
        echo json_encode(['success' => true, 'tags' => $tags]);
    }

    private function searchTags(): void {
        $query = $_GET['query'] ?? '';
        $limit = (int) ($_GET['limit'] ?? 10);

        $tags = $this->tagService->searchTags($query, $limit);
        echo json_encode(['success' => true, 'tags' => $tags]);
    }

    private function getPopularTags(): void {
        $limit = (int) ($_GET['limit'] ?? 20);

        $tags = $this->tagService->getPopularTags($limit);
        echo json_encode(['success' => true, 'tags' => $tags]);
    }

    private function getEventsByTag(): void {
        $tagId = (int) ($_GET['tag_id'] ?? 0);

        if ($tagId === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Tag ID is required']);
            return;
        }

        $events = $this->tagService->getEventsByTag($tagId);
        echo json_encode(['success' => true, 'events' => $events]);
    }

    private function getVenuesByTag(): void {
        $tagId = (int) ($_GET['tag_id'] ?? 0);

        if ($tagId === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Tag ID is required']);
            return;
        }

        $venues = $this->tagService->getVenuesByTag($tagId);
        echo json_encode(['success' => true, 'venues' => $venues]);
    }

    private function methodNotAllowed(): void {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    session_start();
    $controller = new TagController();
    $controller->handleRequest();
}
