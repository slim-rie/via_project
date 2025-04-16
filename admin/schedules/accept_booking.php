<?php
include "../../dbcon.php";
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== "admin") {
    header("Location: ../../login.php");
    exit();
}

$schedule_id = $_POST['schedule_id'];

// Insert if not exists, otherwise update
$insertOrUpdate = $con->prepare("
    INSERT INTO deliveries (schedule_id, delivery_status)
    VALUES (?, 'Accepted')
    ON DUPLICATE KEY UPDATE delivery_status = 'Accepted'
");
$insertOrUpdate->bind_param("i", $schedule_id);

if ($insertOrUpdate->execute()) {
    header("Location: ../pending_bookings.php?success=1");
    exit();
} else {
    echo "Error accepting booking: " . $insertOrUpdate->error;
}

$insertOrUpdate->close();
$con->close();
?>
