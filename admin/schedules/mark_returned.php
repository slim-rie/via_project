<?php
include "../../dbcon.php";
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== "admin") {
    header("Location: ../../login.php");
    exit();
}

$schedule_id = $_POST['schedule_id'];

// Update delivery status to 'Completed' in deliveries table
$updateDelivery = $con->prepare("UPDATE deliveries SET delivery_status = 'Completed' WHERE schedule_id = ?");
$updateDelivery->bind_param("i", $schedule_id);

if ($updateDelivery->execute()) {
    // Set the associated truck back to available in trucks table
    $truckQuery = $con->prepare("UPDATE trucks 
                                 SET status = 'available' 
                                 WHERE truck_id = (
                                     SELECT truck_id FROM schedules WHERE schedule_id = ?
                                 )");
    $truckQuery->bind_param("i", $schedule_id);
    $truckQuery->execute();
    $truckQuery->close();

    header("Location: ../admin/schedules.php?returned=1");
    exit();
} else {
    echo "Error: " . $updateDelivery->error;
}

$updateDelivery->close();
$con->close();
?>
