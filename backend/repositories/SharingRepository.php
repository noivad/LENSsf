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
            'SELECT e.*, es.shared_by, es.message, es.created_at
             FROM events e
             INNER JOIN event_shares es ON e.id = es.event_id
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
            'SELECT v.*, vs.shared_by, vs.message, vs.created_at
             FROM venues v
             INNER JOIN venue_shares vs ON v.id = vs.venue_id
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
}
