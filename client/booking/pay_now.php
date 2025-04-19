<?php
session_start();
include '../../dbcon.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule_id = (int)$_POST['schedule_id'];
    $amount = (float)$_POST['amount'];

    $check = $con->prepare("SELECT payment_id, status FROM payments WHERE schedule_id = ? ORDER BY payment_id DESC LIMIT 1");
    $check->bind_param("i", $schedule_id);
    $check->execute();
    $payment = $check->get_result()->fetch_assoc();

    if (!$payment || $payment['status'] === 'Paid') {
        header("Location: ../booking.php?error=already_paid_or_missing");
        exit();
    }

    $payment_id = $payment['payment_id'];

    $update = $con->prepare("UPDATE payments SET status = 'Paid', date = NOW() WHERE payment_id = ?");
    $update->bind_param("i", $payment_id);

    if ($update->execute()) {
        header("Location: ../booking.php?success=payment_success");
    } else {
        header("Location: ../booking.php?error=payment_failed");
    }
}

$con->close();
?>
