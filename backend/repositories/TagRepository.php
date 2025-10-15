<?php

declare(strict_types=1);

require_once __DIR__ . '/../database/connection.php';

class TagRepository {
    private mysqli $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function findOrCreateTag(string $tagName, ?string $category = null): int {
        $tagName = strtolower(trim($tagName));

        $stmt = $this->db->prepare('SELECT id FROM tags WHERE name = ?');
        $stmt->bind_param('s', $tagName);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return (int) $row['id'];
        }

        $stmt = $this->db->prepare('INSERT INTO tags (name, category) VALUES (?, ?)');
        $stmt->bind_param('ss', $tagName, $category);
        $stmt->execute();

        return (int) $this->db->insert_id;
    }

    public function addEventTag(int $eventId, int $tagId, int $userId): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO event_tags (event_id, tag_id, user_id) VALUES (?, ?, ?) 
             ON DUPLICATE KEY UPDATE created_at = created_at'
        );
        $stmt->bind_param('iii', $eventId, $tagId, $userId);
        $success = $stmt->execute();

        if ($success && $stmt->affected_rows > 0) {
            $this->incrementTagUsageCount($tagId);
        }

        return $success;
    }

    public function addVenueTag(int $venueId, int $tagId, int $userId): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO venue_tags (venue_id, tag_id, user_id) VALUES (?, ?, ?) 
             ON DUPLICATE KEY UPDATE created_at = created_at'
        );
        $stmt->bind_param('iii', $venueId, $tagId, $userId);
        $success = $stmt->execute();

        if ($success && $stmt->affected_rows > 0) {
            $this->incrementTagUsageCount($tagId);
        }

        return $success;
    }

    public function removeEventTag(int $eventId, int $tagId, int $userId): bool {
        $stmt = $this->db->prepare(
            'DELETE FROM event_tags WHERE event_id = ? AND tag_id = ? AND user_id = ?'
        );
        $stmt->bind_param('iii', $eventId, $tagId, $userId);
        $success = $stmt->execute();

        if ($success && $stmt->affected_rows > 0) {
            $this->decrementTagUsageCount($tagId);
        }

        return $success;
    }

    public function removeVenueTag(int $venueId, int $tagId, int $userId): bool {
        $stmt = $this->db->prepare(
            'DELETE FROM venue_tags WHERE venue_id = ? AND tag_id = ? AND user_id = ?'
        );
        $stmt->bind_param('iii', $venueId, $tagId, $userId);
        $success = $stmt->execute();

        if ($success && $stmt->affected_rows > 0) {
            $this->decrementTagUsageCount($tagId);
        }

        return $success;
    }

    public function getEventTags(int $eventId): array {
        $stmt = $this->db->prepare(
            'SELECT t.id, t.name, t.category, t.usage_count, et.user_id, et.created_at
             FROM tags t
             INNER JOIN event_tags et ON t.id = et.tag_id
             WHERE et.event_id = ?
             ORDER BY t.name'
        );
        $stmt->bind_param('i', $eventId);
        $stmt->execute();
        $result = $stmt->get_result();

        $tags = [];
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }

        return $tags;
    }

    public function getVenueTags(int $venueId): array {
        $stmt = $this->db->prepare(
            'SELECT t.id, t.name, t.category, t.usage_count, vt.user_id, vt.created_at
             FROM tags t
             INNER JOIN venue_tags vt ON t.id = vt.tag_id
             WHERE vt.venue_id = ?
             ORDER BY t.name'
        );
        $stmt->bind_param('i', $venueId);
        $stmt->execute();
        $result = $stmt->get_result();

        $tags = [];
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }

        return $tags;
    }

    public function searchTags(string $query, int $limit = 10): array {
        $searchQuery = '%' . $query . '%';
        $stmt = $this->db->prepare(
            'SELECT id, name, category, usage_count FROM tags 
             WHERE name LIKE ? 
             ORDER BY usage_count DESC, name ASC 
             LIMIT ?'
        );
        $stmt->bind_param('si', $searchQuery, $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $tags = [];
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }

        return $tags;
    }

    public function getPopularTags(int $limit = 20): array {
        $stmt = $this->db->prepare(
            'SELECT id, name, category, usage_count FROM tags 
             WHERE usage_count > 0 
             ORDER BY usage_count DESC, name ASC 
             LIMIT ?'
        );
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $tags = [];
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row;
        }

        return $tags;
    }

    public function getEventsByTag(int $tagId): array {
        $stmt = $this->db->prepare(
            'SELECT DISTINCT e.* FROM events e
             INNER JOIN event_tags et ON e.id = et.event_id
             WHERE et.tag_id = ?
             ORDER BY e.start_datetime DESC'
        );
        $stmt->bind_param('i', $tagId);
        $stmt->execute();
        $result = $stmt->get_result();

        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }

        return $events;
    }

    public function getVenuesByTag(int $tagId): array {
        $stmt = $this->db->prepare(
            'SELECT DISTINCT v.* FROM venues v
             INNER JOIN venue_tags vt ON v.id = vt.venue_id
             WHERE vt.tag_id = ?
             ORDER BY v.name'
        );
        $stmt->bind_param('i', $tagId);
        $stmt->execute();
        $result = $stmt->get_result();

        $venues = [];
        while ($row = $result->fetch_assoc()) {
            $venues[] = $row;
        }

        return $venues;
    }

    private function incrementTagUsageCount(int $tagId): void {
        $stmt = $this->db->prepare('UPDATE tags SET usage_count = usage_count + 1 WHERE id = ?');
        $stmt->bind_param('i', $tagId);
        $stmt->execute();
    }

    private function decrementTagUsageCount(int $tagId): void {
        $stmt = $this->db->prepare('UPDATE tags SET usage_count = GREATEST(usage_count - 1, 0) WHERE id = ?');
        $stmt->bind_param('i', $tagId);
        $stmt->execute();
    }
}
