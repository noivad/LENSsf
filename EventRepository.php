<?php
require_once '../database/connection.php';

class EventRepository {
	private $db;

	public function __construct() {
		$this->db = getDbConnection();
	}

	public function getEventsByMonthAndYear($month, $year) {
		$startDate = $year . '-' . $month . '-01';
		$endDate = $year . '-' . $month . '-31';

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
}