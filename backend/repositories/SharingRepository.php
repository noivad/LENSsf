<?php

declare(strict_types=1);

require_once __DIR__ . '/../database/connection.php';

class SharingRepository {
    private mysqli $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function shareEvent(int $eventId, int $sharedBy, int $sharedWith, ?string $message = null): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO event_shares (event_id, shared_by, shared_with, message) VALUES (?, ?, ?, ?) 
             ON DUPLICATE KEY UPDATE message = VALUES(message), created_at = CURRENT_TIMESTAMP'
        );
        $stmt->bind_param('iiis', $eventId, $sharedBy, $sharedWith, $message);

        return $stmt->execute();
    }

    public function revokeEventShare(int $eventId, int $sharedBy, int $sharedWith): bool {
        $stmt = $this->db->prepare(
            'DELETE FROM event_shares WHERE event_id = ? AND shared_by = ? AND shared_with = ?'
        );
        $stmt->bind_param('iii', $eventId, $sharedBy, $sharedWith);

        return $stmt->execute();
    }

    public function getEventShares(int $eventId): array {
        $stmt = $this->db->prepare(
            'SELECT es.*, u.username AS recipient_username
             FROM event_shares es
             INNER JOIN users u ON es.shared_with = u.id
             WHERE es.event_id = ?
             ORDER BY es.created_at DESC'
        );
        $stmt->bind_param('i', $eventId);
        $stmt->execute();
        $result = $stmt->get_result();

        $shares = [];
        while ($row = $result->fetch_assoc()) {
            $shares[] = $row;
        }

        return $shares;
    }

    public function getEventsSharedWithUser(int $userId): array {
        $stmt = $this->db->prepare(
            'SELECT e.*, es.shared_by, es.message, es.created_at, sender.username AS shared_by_name
             FROM events e
             INNER JOIN event_shares es ON e.id = es.event_id
             LEFT JOIN users sender ON sender.id = es.shared_by
             WHERE es.shared_with = ?
             ORDER BY es.created_at DESC'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }

        return $events;
    }

    public function getMyEventShares(int $userId): array {
        $stmt = $this->db->prepare(
            'SELECT es.event_id, e.title AS event_title, e.event_date, es.shared_with, es.message, es.created_at, recipient.username AS shared_with_name
             FROM event_shares es
             INNER JOIN events e ON e.id = es.event_id
             LEFT JOIN users recipient ON recipient.id = es.shared_with
             WHERE es.shared_by = ?
             ORDER BY e.title ASC, es.created_at DESC'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $grouped = [];
        while ($row = $result->fetch_assoc()) {
            $eventId = (int) $row['event_id'];
            if (!isset($grouped[$eventId])) {
                $grouped[$eventId] = [
                    'event_id' => $eventId,
                    'event_title' => $row['event_title'] ?? null,
                    'event_date' => $row['event_date'] ?? null,
                    'shares' => []
                ];
            }
            $grouped[$eventId]['shares'][] = [
                'shared_with' => (int) $row['shared_with'],
                'shared_with_name' => $row['shared_with_name'] ?? null,
                'message' => $row['message'] ?? null,
                'shared_at' => $row['created_at'] ?? null,
            ];
        }

        return array_values($grouped);
    }

    public function shareVenue(int $venueId, int $sharedBy, int $sharedWith, ?string $message = null): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO venue_shares (venue_id, shared_by, shared_with, message) VALUES (?, ?, ?, ?) 
             ON DUPLICATE KEY UPDATE message = VALUES(message), created_at = CURRENT_TIMESTAMP'
        );
        $stmt->bind_param('iiis', $venueId, $sharedBy, $sharedWith, $message);

        return $stmt->execute();
    }

    public function revokeVenueShare(int $venueId, int $sharedBy, int $sharedWith): bool {
        $stmt = $this->db->prepare(
            'DELETE FROM venue_shares WHERE venue_id = ? AND shared_by = ? AND shared_with = ?'
        );
        $stmt->bind_param('iii', $venueId, $sharedBy, $sharedWith);

        return $stmt->execute();
    }

    public function getVenueShares(int $venueId): array {
        $stmt = $this->db->prepare(
            'SELECT vs.*, u.username AS recipient_username
             FROM venue_shares vs
             INNER JOIN users u ON vs.shared_with = u.id
             WHERE vs.venue_id = ?
             ORDER BY vs.created_at DESC'
        );
        $stmt->bind_param('i', $venueId);
        $stmt->execute();
        $result = $stmt->get_result();

        $shares = [];
        while ($row = $result->fetch_assoc()) {
            $shares[] = $row;
        }

        return $shares;
    }

    public function getVenuesSharedWithUser(int $userId): array {
        $stmt = $this->db->prepare(
            'SELECT v.*, vs.shared_by, vs.message, vs.created_at, sender.username AS shared_by_name
             FROM venues v
             INNER JOIN venue_shares vs ON v.id = vs.venue_id
             LEFT JOIN users sender ON sender.id = vs.shared_by
             WHERE vs.shared_with = ?
             ORDER BY vs.created_at DESC'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $venues = [];
        while ($row = $result->fetch_assoc()) {
            $venues[] = $row;
        }

        return $venues;
    }

    public function getMyVenueShares(int $userId): array {
        $stmt = $this->db->prepare(
            'SELECT vs.venue_id, v.name AS venue_name, vs.shared_with, vs.message, vs.created_at, recipient.username AS shared_with_name
             FROM venue_shares vs
             INNER JOIN venues v ON v.id = vs.venue_id
             LEFT JOIN users recipient ON recipient.id = vs.shared_with
             WHERE vs.shared_by = ?
             ORDER BY v.name ASC, vs.created_at DESC'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $grouped = [];
        while ($row = $result->fetch_assoc()) {
            $venueId = (int) $row['venue_id'];
            if (!isset($grouped[$venueId])) {
                $grouped[$venueId] = [
                    'venue_id' => $venueId,
                    'venue_name' => $row['venue_name'] ?? null,
                    'shares' => []
                ];
            }
            $grouped[$venueId]['shares'][] = [
                'shared_with' => (int) $row['shared_with'],
                'shared_with_name' => $row['shared_with_name'] ?? null,
                'message' => $row['message'] ?? null,
                'shared_at' => $row['created_at'] ?? null,
            ];
        }

        return array_values($grouped);
    }
}
