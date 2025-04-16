<?php
require '../dbcon.php';

$events = [];

$sql = "SELECT s.schedule_id, s.start_time, s.end_time, t.truck_no, c.full_name
        FROM schedules s
        JOIN trucks t ON s.truck_id = t.truck_id
        JOIN customers c ON s.customer_id = c.customer_id
        JOIN users u ON c.user_id = u.user_id";

$result = mysqli_query($con, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    $events[] = [
        'title' => "{$row['truck_no']} - {$row['full_name']}",
        'start' => $row['start_time'],
        'end' => $row['end_time'],
        'color' => '#007bff' // optional: make all events blue
    ];
}

header('Content-Type: application/json');
echo json_encode($events);
