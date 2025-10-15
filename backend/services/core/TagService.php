<?php

declare(strict_types=1);

require_once __DIR__ . '/../../repositories/TagRepository.php';

class TagService {
    private TagRepository $tagRepository;

    public function __construct() {
        $this->tagRepository = new TagRepository();
    }

    public function addTagToEvent(string $tagName, int $eventId, int $userId, ?string $category = null): array {
        if (empty($tagName)) {
            return ['success' => false, 'message' => 'Tag name cannot be empty'];
        }

        if (strlen($tagName) > 50) {
            return ['success' => false, 'message' => 'Tag name cannot exceed 50 characters'];
        }

        try {
            $tagId = $this->tagRepository->findOrCreateTag($tagName, $category);
            $success = $this->tagRepository->addEventTag($eventId, $tagId, $userId);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Tag added successfully',
                    'tag_id' => $tagId
                ];
            }

            return ['success' => false, 'message' => 'Failed to add tag'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function addTagToVenue(string $tagName, int $venueId, int $userId, ?string $category = null): array {
        if (empty($tagName)) {
            return ['success' => false, 'message' => 'Tag name cannot be empty'];
        }

        if (strlen($tagName) > 50) {
            return ['success' => false, 'message' => 'Tag name cannot exceed 50 characters'];
        }

        try {
            $tagId = $this->tagRepository->findOrCreateTag($tagName, $category);
            $success = $this->tagRepository->addVenueTag($venueId, $tagId, $userId);

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Tag added successfully',
                    'tag_id' => $tagId
                ];
            }

            return ['success' => false, 'message' => 'Failed to add tag'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function removeTagFromEvent(int $tagId, int $eventId, int $userId): array {
        try {
            $success = $this->tagRepository->removeEventTag($eventId, $tagId, $userId);

            if ($success) {
                return ['success' => true, 'message' => 'Tag removed successfully'];
            }

            return ['success' => false, 'message' => 'Tag not found or already removed'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function removeTagFromVenue(int $tagId, int $venueId, int $userId): array {
        try {
            $success = $this->tagRepository->removeVenueTag($venueId, $tagId, $userId);

            if ($success) {
                return ['success' => true, 'message' => 'Tag removed successfully'];
            }

            return ['success' => false, 'message' => 'Tag not found or already removed'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function getEventTags(int $eventId): array {
        try {
            return $this->tagRepository->getEventTags($eventId);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getVenueTags(int $venueId): array {
        try {
            return $this->tagRepository->getVenueTags($venueId);
        } catch (Exception $e) {
            return [];
        }
    }

    public function searchTags(string $query, int $limit = 10): array {
        try {
            return $this->tagRepository->searchTags($query, $limit);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getPopularTags(int $limit = 20): array {
        try {
            return $this->tagRepository->getPopularTags($limit);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getEventsByTag(int $tagId): array {
        try {
            return $this->tagRepository->getEventsByTag($tagId);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getVenuesByTag(int $tagId): array {
        try {
            return $this->tagRepository->getVenuesByTag($tagId);
        } catch (Exception $e) {
            return [];
        }
    }
}
