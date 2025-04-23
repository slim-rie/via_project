<?php
include "../../dbcon.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "User not logged in.";
    header("Location: ../../login.php");
    exit();
}

if (!isset($_POST['schedule_id'])) {
    $_SESSION['error'] = "No schedule ID provided.";
    header("Location: ../booking.php");
    exit();
}

$schedule_id = $_POST['schedule_id'];
$customer_id = $_SESSION['user_id'];

// Verify latest delivery is Delivered
$checkDelivery = $con->prepare("SELECT delivery_id, delivery_status 
                                FROM deliveries d
                                JOIN schedules s ON d.schedule_id = s.schedule_id
                                WHERE d.schedule_id = ? AND s.customer_id = ? 
                                ORDER BY d.delivery_id DESC LIMIT 1");
if ($checkDelivery === false) {
    error_log("Error preparing statement for checking delivery: " . $con->error);
    $_SESSION['error'] = "Error preparing delivery check.";
    header("Location: ../booking.php");
    exit();
}

$checkDelivery->bind_param("ii", $schedule_id, $customer_id);
if (!$checkDelivery->execute()) {
    error_log("Error executing delivery check query: " . $checkDelivery->error);
    $_SESSION['error'] = "Error executing delivery check.";
    header("Location: ../booking.php");
    exit();
}

$latestDelivery = $checkDelivery->get_result()->fetch_assoc();
if (!$latestDelivery) {
    $_SESSION['error'] = "No delivery found for this schedule.";
    header("Location: ../booking.php");
    exit();
}

if ($latestDelivery['delivery_status'] !== 'Delivered') {
    $_SESSION['error'] = "Cannot mark as received - delivery not yet completed.";
    header("Location: ../booking.php");
    exit();
}

$delivery_id = $latestDelivery['delivery_id'];

// Update that delivery row to mark as Received
$update = $con->prepare("UPDATE deliveries 
                         SET delivery_status = 'Received', received_date = NOW() 
                         WHERE delivery_id = ?");
if ($update === false) {
    error_log("Error preparing statement for updating delivery status: " . $con->error);
    $_SESSION['error'] = "Error preparing delivery status update.";
    header("Location: ../booking.php");
    exit();
}

$update->bind_param("i", $delivery_id);
if (!$update->execute()) {
    error_log("Error executing delivery status update: " . $update->error);
    $_SESSION['error'] = "Error updating delivery status.";
    header("Location: ../booking.php");
    exit();
}

// Optional: Update schedule status to "Received" as well
$updateSchedule = $con->prepare("UPDATE schedules 
                                 SET schedule_status = 'Received' 
                                 WHERE schedule_id = ?");
if ($updateSchedule === false) {
    error_log("Error preparing statement for updating schedule status: " . $con->error);
    $_SESSION['error'] = "Error preparing schedule status update.";
    header("Location: ../booking.php");
    exit();
}

$updateSchedule->bind_param("i", $schedule_id);
if (!$updateSchedule->execute()) {
    error_log("Error executing schedule status update: " . $updateSchedule->error);
    $_SESSION['error'] = "Error updating schedule status.";
    header("Location: ../booking.php");
    exit();
}

$_SESSION['success'] = "Delivery marked as received successfully";
header("Location: ../booking.php");
exit();
?>
