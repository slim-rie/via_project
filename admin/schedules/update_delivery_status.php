<?php
include "../../dbcon.php";
session_start();

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== "admin") {
    header("Location: ../../login.php");
    exit();
}

$schedule_id = $_POST['schedule_id'];
$new_status = $_POST['new_status'];

// Update delivery status
$update = $con->prepare("UPDATE deliveries SET delivery_status = ?, delivery_datetime = NOW() WHERE schedule_id = ?");
$update->bind_param("si", $new_status, $schedule_id);

if ($update->execute()) {
    // If status is completed, return truck to "available"
    if ($new_status === "Completed") {
        $truckUpdate = $con->prepare("
            UPDATE trucks 
            SET status = 'available' 
            WHERE truck_id = (
                SELECT truck_id FROM schedules WHERE schedule_id = ?
            )
        ");
        $truckUpdate->bind_param("i", $schedule_id);
        $truckUpdate->execute();
        $truckUpdate->close();
    }

    header("Location: ../schedules.php?status_updated=1");
    exit();
} else {
    echo "Error updating status: " . $update->error;
}

$update->close();
$con->close();
