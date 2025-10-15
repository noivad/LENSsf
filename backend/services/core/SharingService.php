<?php

declare(strict_types=1);

require_once __DIR__ . '/../../repositories/SharingRepository.php';

class SharingService {
    private SharingRepository $sharingRepository;

    public function __construct() {
        $this->sharingRepository = new SharingRepository();
    }

    public function shareEvent(int $eventId, int $sharedBy, int $sharedWith, ?string $message = null): array {
        if ($sharedBy === $sharedWith) {
            return ['success' => false, 'message' => 'You cannot share an event with yourself'];
        }

        try {
            $success = $this->sharingRepository->shareEvent($eventId, $sharedBy, $sharedWith, $message);

            if ($success) {
                return ['success' => true, 'message' => 'Event shared successfully'];
            }

            return ['success' => false, 'message' => 'Failed to share event'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function revokeEventShare(int $eventId, int $sharedBy, int $sharedWith): array {
        try {
            $success = $this->sharingRepository->revokeEventShare($eventId, $sharedBy, $sharedWith);

            if ($success) {
                return ['success' => true, 'message' => 'Event share revoked'];
            }

            return ['success' => false, 'message' => 'No share found to revoke'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function getEventShares(int $eventId): array {
        try {
            return $this->sharingRepository->getEventShares($eventId);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getEventsSharedWithUser(int $userId): array {
        try {
            return $this->sharingRepository->getEventsSharedWithUser($userId);
        } catch (Exception $e) {
            return [];
        }
    }

    public function shareVenue(int $venueId, int $sharedBy, int $sharedWith, ?string $message = null): array {
        if ($sharedBy === $sharedWith) {
            return ['success' => false, 'message' => 'You cannot share a venue with yourself'];
        }

        try {
            $success = $this->sharingRepository->shareVenue($venueId, $sharedBy, $sharedWith, $message);

            if ($success) {
                return ['success' => true, 'message' => 'Venue shared successfully'];
            }

            return ['success' => false, 'message' => 'Failed to share venue'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function revokeVenueShare(int $venueId, int $sharedBy, int $sharedWith): array {
        try {
            $success = $this->sharingRepository->revokeVenueShare($venueId, $sharedBy, $sharedWith);

            if ($success) {
                return ['success' => true, 'message' => 'Venue share revoked'];
            }

            return ['success' => false, 'message' => 'No share found to revoke'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function getVenueShares(int $venueId): array {
        try {
            return $this->sharingRepository->getVenueShares($venueId);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getVenuesSharedWithUser(int $userId): array {
        try {
            return $this->sharingRepository->getVenuesSharedWithUser($userId);
        } catch (Exception $e) {
            return [];
        }
    }
}
