<?php
include "../../dbcon.php";
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== "admin") {
    header("Location: ../login.php");
    exit();
}

$schedule_id = $_POST['schedule_id'];

// Update delivery status to 'Accepted'
$updateDelivery = $con->prepare("UPDATE deliveries SET delivery_status = 'Accepted' WHERE schedule_id = ?");
$updateDelivery->bind_param("i", $schedule_id);

if ($updateDelivery->execute()) {
    // Redirect to the admin pending bookings page
    header("Location: ../pending_bookings.php?success=1");
    exit();
} else {
    echo "Error accepting booking: " . $updateDelivery->error;
}

$updateDelivery->close();
$con->close();
?>
