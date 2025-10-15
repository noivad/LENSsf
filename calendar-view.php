<?php
// Include the necessary files and initialize the required services
require_once '../backend/services/core/EventCalendarService.php';
require_once '../backend/services/core/CalendarSubscriptionService.php';

$eventCalendarService = new EventCalendarService();
$calendarSubscriptionService = new CalendarSubscriptionService();

// Get the current date
$currentDate = new DateTime();

// Get the month and year from the query parameters (default to current month and year)
$month = isset($_GET['month']) ? intval($_GET['month']) : $currentDate->format('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : $currentDate->format('Y');

// Create a DateTime object for the first day of the month
$firstDay = new DateTime("$year-$month-01");

// Get the number of days in the month
$daysInMonth = $firstDay->format('t');

// Get the name of the current month
$monthName = $firstDay->format('F');

// Get the index of the starting day (0 = Sunday, 1 = Monday, etc.)
$startingDay = $firstDay->format('w');

// Generate the calendar grid
$calendarGrid = $eventCalendarService->generateCalendarGrid($startingDay, $daysInMonth);

// Fetch events for the current month and year
$events = $eventCalendarService->fetchEvents($month, $year);

// Generate the event list
$eventList = $eventCalendarService->generateEventList($events);
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>User Calendar</title>
	<link rel="stylesheet" href="calendar.css">
</head>
<body>
	<div class="container">
		<h1>User Calendar</h1>
		<div class="calendar-controls">
			<a href="?month=<?php echo $month - 1; ?>&year=<?php echo $month == 1 ? $year - 1 : $year; ?>">Previous Month</a>
			<span id="current-month"><?php echo $monthName . ' ' . $year; ?></span>
			<a href="?month=<?php echo $month + 1; ?>&year=<?php echo $month == 12 ? $year + 1 : $year; ?>">Next Month</a>
		</div>
		<div class="calendar-grid">
			<?php echo $calendarGrid; ?>
		</div>
		<div class="event-list">
			<h2>Events</h2>
			<?php echo $eventList; ?>
		</div>
	</div>
</body>
</html><?php
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