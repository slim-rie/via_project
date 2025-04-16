<?php
include "../../dbcon.php";

$schedule_id = $_POST['schedule_id'];

// Get schedule dates
$scheduleQuery = $con->prepare("SELECT start_time, end_time FROM schedules WHERE schedule_id = ?");
$scheduleQuery->bind_param("i", $schedule_id);
$scheduleQuery->execute();
$scheduleResult = $scheduleQuery->get_result()->fetch_assoc();

if (!$scheduleResult) {
    echo "Schedule not found.";
    exit();
}

// Calculate days between start and end
$start_time = new DateTime($scheduleResult['start_time']);
$end_time = new DateTime($scheduleResult['end_time']);
$interval = $start_time->diff($end_time);
$number_of_days = $interval->days;

// Avoid 0-day charges
if ($number_of_days == 0) $number_of_days = 1;

$amount_per_day = 5000;
$total_amount = $number_of_days * $amount_per_day;

// Insert into payments
$stmt = $con->prepare("INSERT INTO payments (total_amount, status, date, schedule_id) VALUES (?, 'Paid', NOW(), ?)");
$stmt->bind_param("di", $total_amount, $schedule_id);

if ($stmt->execute()) {
    // No need to update delivery status again as it's already 'Pending'
    header("Location: ../booking.php?paid=1");
    exit();
} else {
    echo "Payment error: " . $stmt->error;
}

$stmt->close();
$scheduleQuery->close();
$con->close();
?>
