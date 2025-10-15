<?php
// Include the necessary files and initialize the required services
require_once '../backend/services/core/CalendarSubscriptionService.php';
require_once '../backend/services/advanced/CalendarCustomizationService.php';

$calendarSubscriptionService = new CalendarSubscriptionService();
$calendarCustomizationService = new CalendarCustomizationService();

// Fetch the user's calendar settings
$userId = $_SESSION['user_id']; // Assuming you have user authentication in place
$calendarSettings = $calendarCustomizationService->getCalendarSettings($userId);

// Handle form submission for updating calendar settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$updatedSettings = [
		'layout' => $_POST['layout'],
		'colors' => $_POST['colors'],
		'event_display' => $_POST['event_display']
	];
	$calendarCustomizationService->updateCalendarSettings($userId, $updatedSettings);
	// Redirect back to the calendar settings page after updating
	header('Location: calendar-settings.php');
	exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Calendar Settings</title>
	<link rel="stylesheet" href="calendar.css">
</head>
<body>
	<div class="container">
		<h1>Calendar Settings</h1>
		<form method="POST" action="calendar-settings.php">
			<div class="form-group">
				<label for="layout">Calendar Layout</label>
				<select name="layout" id="layout">
					<option value="month" <?php echo $calendarSettings['layout'] === 'month' ? 'selected' : ''; ?>>Month</option>
					<option value="week" <?php echo $calendarSettings['layout'] === 'week' ? 'selected' : ''; ?>>Week</option>
					<option value="day" <?php echo $calendarSettings['layout'] === 'day' ? 'selected' : ''; ?>>Day</option>
				</select>
			</div>
			<div class="form-group">
				<label for="colors">Calendar Colors</label>
				<input type="color" name="colors" id="colors" value="<?php echo $calendarSettings['colors']; ?>">
			</div>
			<div class="form-group">
				<label for="event_display">Event Display</label>
				<select name="event_display" id="event_display">
					<option value="list" <?php echo $calendarSettings['event_display'] === 'list' ? 'selected' : ''; ?>>List</option>
					<option value="grid" <?php echo $calendarSettings['event_display'] === 'grid' ? 'selected' : ''; ?>>Grid</option>
				</select>
			</div>
			<button type="submit">Save Settings</button>
		</form>
	</div>
</body>
</html>