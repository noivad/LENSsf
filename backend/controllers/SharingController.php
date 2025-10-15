<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/core/SharingService.php';

class SharingController {
    private SharingService $sharingService;

    public function __construct() {
        $this->sharingService = new SharingService();
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
            case 'share_event':
                if ($method !== 'POST') {
                    $this->methodNotAllowed();
                    return;
                }
                $this->shareEvent($userId);
                break;

            case 'revoke_event_share':
                if ($method !== 'DELETE') {
                    $this->methodNotAllowed();
                    return;
                }
                $this->revokeEventShare($userId);
                break;

            case 'get_event_shares':
                if ($method !== 'GET') {
                    $this->methodNotAllowed();
                    return;
                }
                $this->getEventShares($userId);
                break;

            case 'get_events_shared_with_me':
                if ($method !== 'GET') {
                    $this->methodNotAllowed();
                    return;
                }
                $this->getEventsSharedWithUser($userId);
                break;

            case 'share_venue':
                if ($method !== 'POST') {
                    $this->methodNotAllowed();
                    return;
                }
                $this->shareVenue($userId);
                break;

            case 'revoke_venue_share':
                if ($method !== 'DELETE') {
                    $this->methodNotAllowed();
                    return;
                }
                $this->revokeVenueShare($userId);
                break;

            case 'get_venue_shares':
                if ($method !== 'GET') {
                    $this->methodNotAllowed();
                    return;
                }
                $this->getVenueShares($userId);
                break;

            case 'get_venues_shared_with_me':
                if ($method !== 'GET') {
                    $this->methodNotAllowed();
                    return;
                }
                $this->getVenuesSharedWithUser($userId);
                break;

            default:
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Action not found']);
        }
    }

    private function shareEvent(int $userId): void {
        $data = json_decode(file_get_contents('php://input'), true);
        $eventId = (int) ($data['event_id'] ?? 0);
        $sharedWith = (int) ($data['shared_with'] ?? 0);
        $message = $data['message'] ?? null;

        if ($eventId === 0 || $sharedWith === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Event ID and recipient are required']);
            return;
        }

        $result = $this->sharingService->shareEvent($eventId, $userId, $sharedWith, $message);
        echo json_encode($result);
    }

    private function revokeEventShare(int $userId): void {
        $data = json_decode(file_get_contents('php://input'), true);
        $eventId = (int) ($data['event_id'] ?? 0);
        $sharedWith = (int) ($data['shared_with'] ?? 0);

        if ($eventId === 0 || $sharedWith === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Event ID and recipient are required']);
            return;
        }

        $result = $this->sharingService->revokeEventShare($eventId, $userId, $sharedWith);
        echo json_encode($result);
    }

    private function getEventShares(int $userId): void {
        $eventId = (int) ($_GET['event_id'] ?? 0);

        if ($eventId === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Event ID is required']);
            return;
        }

        $shares = $this->sharingService->getEventShares($eventId);
        echo json_encode(['success' => true, 'shares' => $shares]);
    }

    private function getEventsSharedWithUser(int $userId): void {
        $events = $this->sharingService->getEventsSharedWithUser($userId);
        echo json_encode(['success' => true, 'events' => $events]);
    }

    private function shareVenue(int $userId): void {
        $data = json_decode(file_get_contents('php://input'), true);
        $venueId = (int) ($data['venue_id'] ?? 0);
        $sharedWith = (int) ($data['shared_with'] ?? 0);
        $message = $data['message'] ?? null;

        if ($venueId === 0 || $sharedWith === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Venue ID and recipient are required']);
            return;
        }

        $result = $this->sharingService->shareVenue($venueId, $userId, $sharedWith, $message);
        echo json_encode($result);
    }

    private function revokeVenueShare(int $userId): void {
        $data = json_decode(file_get_contents('php://input'), true);
        $venueId = (int) ($data['venue_id'] ?? 0);
        $sharedWith = (int) ($data['shared_with'] ?? 0);

        if ($venueId === 0 || $sharedWith === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Venue ID and recipient are required']);
            return;
        }

        $result = $this->sharingService->revokeVenueShare($venueId, $userId, $sharedWith);
        echo json_encode($result);
    }

    private function getVenueShares(int $userId): void {
        $venueId = (int) ($_GET['venue_id'] ?? 0);

        if ($venueId === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Venue ID is required']);
            return;
        }

        $shares = $this->sharingService->getVenueShares($venueId);
        echo json_encode(['success' => true, 'shares' => $shares]);
    }

    private function getVenuesSharedWithUser(int $userId): void {
        $venues = $this->sharingService->getVenuesSharedWithUser($userId);
        echo json_encode(['success' => true, 'venues' => $venues]);
    }

    private function methodNotAllowed(): void {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    session_start();
    $controller = new SharingController();
    $controller->handleRequest();
}
