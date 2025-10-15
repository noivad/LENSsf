<?php
require_once '../repositories/EventRepository.php';

class EventCalendarService {
	private $eventRepository;

	public function __construct() {
		$this->eventRepository = new EventRepository();
	}

	public function generateCalendarGrid($startingDay, $daysInMonth) {
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

	public function fetchEvents($month, $year) {
		// Fetch events from the repository based on the given month and year
		$events = $this->eventRepository->getEventsByMonthAndYear($month, $year);

		return $events;
	}

	public function generateEventList($events) {
		$eventList = '<ul>';

		foreach ($events as $event) {
			$eventList .= '<li>' . $event['date'] . ': ' . $event['title'] . '</li>';
		}

		$eventList .= '</ul>';

		return $eventList;
	}
}
// complete EventCalendarService
<?php
require_once '../repositories/EventRepository.php';

class EventCalendarService {
	private $eventRepository;

	public function __construct() {
		$this->eventRepository = new EventRepository();
	}

	public function createEvent($eventData) {
		// Perform data validation and sanitization
		// ...

		// Create the event using the EventRepository
		$eventId = $this->eventRepository->createEvent($eventData);

		return $eventId;
	}

	public function getEventById($eventId) {
		// Retrieve the event by ID using the EventRepository
		$event = $this->eventRepository->getEventById($eventId);

		return $event;
	}

	public function updateEvent($eventId, $eventData) {
		// Perform data validation and sanitization
		// ...

		// Update the event using the EventRepository
		$this->eventRepository->updateEvent($eventId, $eventData);
	}

	public function deleteEvent($eventId) {
		// Delete the event using the EventRepository
		$this->eventRepository->deleteEvent($eventId);
	}

	public function getEventsByDate($date) {
		// Retrieve events by date using the EventRepository
		$events = $this->eventRepository->getEventsByDate($date);

		return $events;
	}

	public function getEventsByDateRange($startDate, $endDate) {
		// Retrieve events within a date range using the EventRepository
		$events = $this->eventRepository->getEventsByDateRange($startDate, $endDate);

		return $events;
	}

	public function getEventsByUserId($userId) {
		// Retrieve events created by a specific user using the EventRepository
		$events = $this->eventRepository->getEventsByUserId($userId);

		return $events;
	}

	public function searchEvents($searchTerm) {
		// Search for events based on a search term using the EventRepository
		$events = $this->eventRepository->searchEvents($searchTerm);

		return $events;
	}

	public function getUpcomingEvents($limit = 10) {
		// Retrieve upcoming events using the EventRepository
		$events = $this->eventRepository->getUpcomingEvents($limit);

		return $events;
	}

	public function getEventCount() {
		// Get the total count of events using the EventRepository
		$count = $this->eventRepository->getEventCount();

		return $count;
	}
}