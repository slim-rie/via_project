<?php
session_start();
include '../../dbcon.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get customer_id
$customerQuery = $con->prepare("SELECT customer_id FROM customers WHERE user_id = ?");
$customerQuery->bind_param("i", $_SESSION['user_id']);
$customerQuery->execute();
$customer_id = $customerQuery->get_result()->fetch_assoc()['customer_id'] ?? null;

if (!$customer_id) {
    header("Location: ../booking.php?error=no_customer");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required = ['truck_id', 'booking_date', 'pick_up', 'destination', 'distance_km'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            header("Location: ../booking.php?error=missing_field");
            exit();
        }
    }

    $truck_id = (int)$_POST['truck_id'];
    $booking_date = $_POST['booking_date'];
    $pick_up = $con->real_escape_string($_POST['pick_up']);
    $destination = $con->real_escape_string($_POST['destination']);
    $distance_km = (float)$_POST['distance_km'];

    // Cost calculation
    $base_rate = 10000;
    $extra_charge = ($distance_km > 30) ? ceil(($distance_km - 30) / 10) * 5000 : 0;
    $total_cost = $base_rate + $extra_charge;

    $truckQuery = $con->prepare("SELECT driver_id, helper_id FROM trucks WHERE truck_id = ? AND status = 'Available'");
    $truckQuery->bind_param("i", $truck_id);
    $truckQuery->execute();
    $truckResult = $truckQuery->get_result()->fetch_assoc();
    $driver_id = $truckResult['driver_id'] ?? null;
    $helper_id = $truckResult['helper_id'] ?? null;

    if (!$driver_id) {
        header("Location: ../booking.php?error=no_driver");
        exit();
    }

    $start_time = $booking_date . " 06:00:00";
    $end_time = $booking_date . " 18:00:00";

    $con->begin_transaction();

    try {
        $insertSchedule = $con->prepare("INSERT INTO schedules (customer_id, truck_id, driver_id, helper_id, start_time, end_time, destination, pick_up, distance_km) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insertSchedule->bind_param("iiiissssd", $customer_id, $truck_id, $driver_id, $helper_id, $start_time, $end_time, $destination, $pick_up, $distance_km);
        $insertSchedule->execute();
        $schedule_id = $con->insert_id;

        $insertPayment = $con->prepare("INSERT INTO payments (schedule_id, total_amount, status) VALUES (?, ?, 'Pending')");
        $insertPayment->bind_param("id", $schedule_id, $total_cost);
        $insertPayment->execute();

        $updateTruck = $con->prepare("UPDATE trucks SET status = 'Booked' WHERE truck_id = ?");
        $updateTruck->bind_param("i", $truck_id);
        $updateTruck->execute();

        $insertDelivery = $con->prepare("INSERT INTO deliveries (schedule_id, delivery_status) VALUES (?, 'Pending')");
        $insertDelivery->bind_param("i", $schedule_id);
        $insertDelivery->execute();

        $con->commit();
        header("Location: ../booking.php?success=1");
    } catch (Exception $e) {
        $con->rollback();
        error_log("Booking error: " . $e->getMessage());
        header("Location: ../booking.php?error=booking_failed&message=" . urlencode($e->getMessage()));
    }
}
$con->close();
