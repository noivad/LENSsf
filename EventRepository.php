<?php

declare(strict_types=1);

require_once __DIR__ . '/backend/database/connection.php';

class EventRepository {
    private mysqli $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    public function getEventsByMonthAndYear(int $month, int $year): array {
        $startDate = sprintf('%d-%02d-01', $year, $month);
        $endDate = sprintf('%d-%02d-31', $year, $month);

        $query = "SELECT * FROM events WHERE start_datetime BETWEEN ? AND ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();

        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }

        return $events;
    }

    public function createEvent(array $eventData): int {
        return 0;
    }

    public function getEventById(int $eventId): ?array {
        return null;
    }

    public function updateEvent(int $eventId, array $eventData): void {
    }

    public function deleteEvent(int $eventId): void {
    }

    public function getEventsByDate(string $date): array {
        return [];
    }

    public function getEventsByDateRange(string $startDate, string $endDate): array {
        return [];
    }

    public function getEventsByUserId(int $userId): array {
        return [];
    }

    public function searchEvents(string $searchTerm): array {
        return [];
    }

    public function getUpcomingEvents(int $limit): array {
        return [];
    }

    public function getEventCount(): int {
        return 0;
    }
}
