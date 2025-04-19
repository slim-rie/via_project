<?php
require '../../dbcon.php';
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== "admin") {
    header("Location: ../../login.php");
    exit();
}

if (isset($_GET['id'])) {
    $payroll_id = $_GET['id'];
    
    try {
        // Update the driver payroll record to mark as paid
        $stmt = $con->prepare("UPDATE payroll 
                              SET payment_status = 'Paid', payment_date = CURDATE() 
                              WHERE payroll_id = ?");
        $stmt->bind_param("i", $payroll_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $_SESSION['success'] = "Driver payroll marked as paid successfully";
        } else {
            $_SESSION['error'] = "No changes made or payroll not found";
        }
        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: ../payroll_view.php");
    exit();
} else {
    $_SESSION['error'] = "Invalid request";
    header("Location: ../payroll_view.php");
    exit();
}
?>