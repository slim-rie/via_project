<?php
session_start();
include '../../dbcon.php'; // Fix path from '../../dbcon.php'

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get customer_id
$customerQuery = $con->prepare("SELECT customer_id FROM customers WHERE user_id = ?");
$customerQuery->bind_param("i", $_SESSION['user_id']);
$customerQuery->execute();
$customerResult = $customerQuery->get_result()->fetch_assoc();
$customer_id = $customerResult['customer_id'] ?? null;

if (!$customer_id) {
    header("Location: ../booking.php?error=no_customer");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $truck_id = $_POST['truck_id'];
    $booking_date = $_POST['booking_date'];
    
    // Get truck's driver_id
    $truckQuery = $con->prepare("SELECT driver_id FROM trucks WHERE truck_id = ?");
    $truckQuery->bind_param("i", $truck_id);
    $truckQuery->execute();
    $truckResult = $truckQuery->get_result()->fetch_assoc();
    $driver_id = $truckResult['driver_id'] ?? null;

    if (!$driver_id) {
        header("Location: ../booking.php?error=no_driver");
        exit();
    }

    // Fixed times (06:00-18:00)
    $start_time = $booking_date . ' 06:00:00';
    $end_time = $booking_date . ' 18:00:00';

    // Insert into schedules
    $insert = $con->prepare("
        INSERT INTO schedules 
        (customer_id, truck_id, driver_id, start_time, end_time, destination, pick_up)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $insert->bind_param(
        "iiissss", 
        $customer_id, 
        $truck_id, 
        $driver_id,
        $start_time,
        $end_time,
        $_POST['destination'],
        $_POST['pick_up']
    );

    if ($insert->execute()) {
        header("Location: ../booking.php?success=1");
    } else {
        header("Location: ../booking.php?error=db_error");
    }
    
    $insert->close();
}
$con->close();
?>