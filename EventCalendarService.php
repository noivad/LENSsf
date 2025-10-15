<?php

declare(strict_types=1);

require_once __DIR__ . '/EventRepository.php';

class EventCalendarService {
    private EventRepository $eventRepository;

    public function __construct() {
        $this->eventRepository = new EventRepository();
    }

    public function generateCalendarGrid(int $startingDay, int $daysInMonth): string {
        $calendarGrid = '<div class="calendar-header">';
        $calendarGrid .= '<div class="calendar-day">Sun</div>';
        $calendarGrid .= '<div class="calendar-day">Mon</div>';
        $calendarGrid .= '<div class="calendar-day">Tue</div>';
        $calendarGrid .= '<div class="calendar-day">Wed</div>';
        $calendarGrid .= '<div class="calendar-day">Thu</div>';
        $calendarGrid .= '<div class="calendar-day">Fri</div>';
        $calendarGrid .= '<div class="calendar-day">Sat</div>';
        $calendarGrid .= '</div>';

        $calendarGrid .= '<div id="calendar-days" class="calendar-days">';

        for ($i = 0; $i < $startingDay; $i++) {
            $calendarGrid .= '<div class="calendar-date"></div>';
        }

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $calendarGrid .= '<div class="calendar-date">' . $day . '</div>';
        }

        $calendarGrid .= '</div>';

        return $calendarGrid;
    }

    public function fetchEvents(int $month, int $year): array {
        return $this->eventRepository->getEventsByMonthAndYear($month, $year);
    }

    public function generateEventList(array $events): string {
        $eventList = '<ul>';

        foreach ($events as $event) {
            $date = $event['date'] ?? $event['start_datetime'] ?? '';
            $title = $event['title'] ?? '';
            $eventList .= '<li>' . htmlspecialchars((string) $date) . ': ' . htmlspecialchars((string) $title) . '</li>';
        }

        $eventList .= '</ul>';

        return $eventList;
    }

    public function createEvent(array $eventData): int {
        return $this->eventRepository->createEvent($eventData);
    }

    public function getEventById(int $eventId): ?array {
        return $this->eventRepository->getEventById($eventId);
    }

    public function updateEvent(int $eventId, array $eventData): void {
        $this->eventRepository->updateEvent($eventId, $eventData);
    }

    public function deleteEvent(int $eventId): void {
        $this->eventRepository->deleteEvent($eventId);
    }

    public function getEventsByDate(string $date): array {
        return $this->eventRepository->getEventsByDate($date);
    }

    public function getEventsByDateRange(string $startDate, string $endDate): array {
        return $this->eventRepository->getEventsByDateRange($startDate, $endDate);
    }

    public function getEventsByUserId(int $userId): array {
        return $this->eventRepository->getEventsByUserId($userId);
    }

    public function searchEvents(string $searchTerm): array {
        return $this->eventRepository->searchEvents($searchTerm);
    }

    public function getUpcomingEvents(int $limit = 10): array {
        return $this->eventRepository->getUpcomingEvents($limit);
    }

    public function getEventCount(): int {
        return $this->eventRepository->getEventCount();
    }
}
