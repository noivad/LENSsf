<?php

declare(strict_types=1);

class TagManager
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function findOrCreateTag(string $tagName): ?int
    {
        $tagName = strtolower(trim($tagName));
        if ($tagName === '') {
            return null;
        }

        $stmt = $this->db->prepare('SELECT id FROM tags WHERE name = ?');
        $stmt->execute([$tagName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return (int) $result['id'];
        }

        $stmt = $this->db->prepare('INSERT INTO tags (name) VALUES (?)');
        $stmt->execute([$tagName]);
        return (int) $this->db->lastInsertId();
    }

    public function getTagsForVenue(int $venueId): array
    {
        $stmt = $this->db->prepare('
            SELECT t.id, t.name 
            FROM tags t 
            INNER JOIN venue_tags vt ON t.id = vt.tag_id 
            WHERE vt.venue_id = ?
            ORDER BY t.name
        ');
        $stmt->execute([$venueId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTagsForEvent(int $eventId): array
    {
        $stmt = $this->db->prepare('
            SELECT t.id, t.name 
            FROM tags t 
            INNER JOIN event_tags et ON t.id = et.tag_id 
            WHERE et.event_id = ?
            ORDER BY t.name
        ');
        $stmt->execute([$eventId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function setTagsForVenue(int $venueId, array $tagNames): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare('DELETE FROM venue_tags WHERE venue_id = ?')->execute([$venueId]);

            foreach ($tagNames as $tagName) {
                $tagId = $this->findOrCreateTag($tagName);
                if ($tagId) {
                    $stmt = $this->db->prepare('INSERT IGNORE INTO venue_tags (venue_id, tag_id) VALUES (?, ?)');
                    $stmt->execute([$venueId, $tagId]);
                }
            }

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function setTagsForEvent(int $eventId, array $tagNames): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare('DELETE FROM event_tags WHERE event_id = ?')->execute([$eventId]);

            foreach ($tagNames as $tagName) {
                $tagId = $this->findOrCreateTag($tagName);
                if ($tagId) {
                    $stmt = $this->db->prepare('INSERT IGNORE INTO event_tags (event_id, tag_id) VALUES (?, ?)');
                    $stmt->execute([$eventId, $tagId]);
                }
            }

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function addTagToVenue(int $venueId, string $tagName): bool
    {
        $tagId = $this->findOrCreateTag($tagName);
        if (!$tagId) {
            return false;
        }

        try {
            $stmt = $this->db->prepare('INSERT IGNORE INTO venue_tags (venue_id, tag_id) VALUES (?, ?)');
            $stmt->execute([$venueId, $tagId]);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function addTagToEvent(int $eventId, string $tagName): bool
    {
        $tagId = $this->findOrCreateTag($tagName);
        if (!$tagId) {
            return false;
        }

        try {
            $stmt = $this->db->prepare('INSERT IGNORE INTO event_tags (event_id, tag_id) VALUES (?, ?)');
            $stmt->execute([$eventId, $tagId]);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function getAllTags(): array
    {
        $stmt = $this->db->query('
            SELECT 
                t.id, 
                t.name,
                (SELECT COUNT(*) FROM venue_tags vt WHERE vt.tag_id = t.id) as venue_count,
                (SELECT COUNT(*) FROM event_tags et WHERE et.tag_id = t.id) as event_count
            FROM tags t
            ORDER BY t.name
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function hasUniversalTagsSupport(): bool
    {
        try {
            $stmt = $this->db->query("SHOW TABLES LIKE 'tags'");
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }
}
