<?php
include "../../dbcon.php";
session_start();

$userId = $_SESSION['user_id'] ?? 4; // fallback

$pick_up = $_POST['pick_up'];
$destination = $_POST['destination'];
$start_time = $_POST['start_time'];
$end_time = $_POST['end_time'];
$truck_id = $_POST['truck_id'];

// Insert schedule
$stmt = $con->prepare("INSERT INTO schedules (start_time, end_time, destination, pick_up, truck_id, user_id)
VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssii", $start_time, $end_time, $destination, $pick_up, $truck_id, $userId);

if ($stmt->execute()) {
    $schedule_id = $stmt->insert_id;

    // Set truck to unavailable
    $con->query("UPDATE trucks SET status='unavailable' WHERE truck_id=$truck_id");

    // Insert delivery row with Pending status
    $delivery = $con->prepare("INSERT INTO deliveries (delivery_status, delivery_datetime, schedule_id)
                               VALUES ('Pending', NOW(), ?)");
    $delivery->bind_param("i", $schedule_id);
    $delivery->execute();
    $delivery->close();

    header("Location: ../booking.php?success=1");
} else {
    echo "Error: " . $stmt->error;
}


$stmt->close();
$con->close();
?>
