<?php
include "../../dbcon.php";

// Get the schedule ID from the POST request
$schedule_id = $_POST['schedule_id'];

// Fetch the start_time and end_time for the selected schedule
$scheduleQuery = $con->prepare("SELECT start_time, end_time FROM schedules WHERE schedule_id = ?");
$scheduleQuery->bind_param("i", $schedule_id);
$scheduleQuery->execute();
$scheduleResult = $scheduleQuery->get_result()->fetch_assoc();

if (!$scheduleResult) {
    echo "Schedule not found.";
    exit();
}

// Calculate the number of days between start_time and end_time
$start_time = new DateTime($scheduleResult['start_time']);
$end_time = new DateTime($scheduleResult['end_time']);
$interval = $start_time->diff($end_time);
$number_of_days = $interval->days;

// Calculate total amount based on ₱3000 per day
$amount_per_day = 3000; // Amount charged per day
$total_amount = $number_of_days * $amount_per_day;

// Insert payment record
$stmt = $con->prepare("INSERT INTO payments (total_amount, status, date, schedule_id) VALUES (?, 'Paid', NOW(), ?)");
$stmt->bind_param("di", $total_amount, $schedule_id);

if ($stmt->execute()) {
    // Optional: Update delivery status if needed
    $updateDelivery = $con->prepare("UPDATE deliveries SET delivery_status = 'Pending' WHERE schedule_id = ?");
    $updateDelivery->bind_param("i", $schedule_id);
    $updateDelivery->execute();
    
    header("Location: ../booking.php?paid=1");
    exit();
} else {
    echo "Payment error: " . $stmt->error;
}

$stmt->close();
$scheduleQuery->close();
$con->close();
?>
