document.addEventListener('DOMContentLoaded', function() {
	// Add any necessary JavaScript functionality here
	// For example, you can handle event clicks, display event details, etc.

	// Example: Add click event listener to calendar dates
	const calendarDates = document.querySelectorAll('.calendar-date');
	calendarDates.forEach(function(date) {
		date.addEventListener('click', function() {
			// Handle date click event
			console.log('Clicked date:', this.textContent);
			// You can show event details, open a modal, etc.
		});
	});
});