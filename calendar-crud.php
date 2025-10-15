<?php
// Include the necessary files and initialize the required services
require_once '../backend/services/core/EventCalendarService.php';

$eventCalendarService = new EventCalendarService();

// Handle form submission for creating/updating events
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$eventData = [
		'title' => $_POST['title'],
		'description' => $_POST['description'],
		'start_datetime' => $_POST['start_datetime'],
		'end_datetime' => $_POST['end_datetime']
	];
	if (isset($_POST['event_id'])) {
		// Update existing event
		$eventId = $_POST['event_id'];
		$eventCalendarService->updateEvent($eventId, $eventData);
	} else {
		// Create new event
		$eventCalendarService->createEvent($eventData);
	}
	// Redirect back to the calendar view after creating/updating
	header('Location: calendar-view.php');
	exit();
}

// Handle event deletion
if (isset($_GET['delete_event_id'])) {
	$eventId = $_GET['delete_event_id'];
	$eventCalendarService->deleteEvent($eventId);
	// Redirect back to the calendar view after deleting
	header('Location: calendar-view.php');
	exit();
}

// Fetch event data for editing (if event_id is provided)
$event = null;
if (isset($_GET['event_id'])) {
	$eventId = $_GET['event_id'];
	$event = $eventCalendarService->getEventById($eventId);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Calendar Event Management</title>
	<link rel="stylesheet" href="calendar.css">
</head>
<body>
	<div class="container">
		<h1><?php echo $event ? 'Edit Event' : 'Create Event'; ?></h1>
		<form method="POST" action="calendar-crud.php">
			<?php if ($event) : ?>
				<input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
			<?php endif; ?>
			<div class="form-group">
				<label for="title">Title</label>
				<input type="text" name="title" id="title" value="<?php echo $event ? $event['title'] : ''; ?>" required>
			</div>
			<div class="form-group">
				<label for="description">Description</label>
				<textarea name="description" id="description"><?php echo $event ? $event['description'] : ''; ?></textarea>
			</div>
			<div class="form-group">
				<label for="start_datetime">Start Date/Time</label>
				<input type="datetime-local" name="start_datetime" id="start_datetime" value="<?php echo $event ? $event['start_datetime'] : ''; ?>" required>
			</div>
			<div class="form-group">
				<label for="end_datetime">End Date/Time</label>
				<input type="datetime-local" name="end_datetime" id="end_datetime" value="<?php echo $event ? $event['end_datetime'] : ''; ?>" required>
			</div>
			<button type="submit"><?php echo $event ? 'Update Event' : 'Create Event'; ?></button>
		</form>
	</div>
</body>
</html>