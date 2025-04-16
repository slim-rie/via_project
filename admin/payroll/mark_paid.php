<?php
require '../../dbcon.php';

if(isset($_GET['id'])) {
    $payroll_id = (int)$_GET['id'];
    
    // Begin transaction
    mysqli_begin_transaction($con);
    
    try {
        // First verify the payroll exists and is pending
        $check_sql = "SELECT payroll_id FROM payroll 
                     WHERE payroll_id = ? AND payment_status = 'Pending'";
        $check_stmt = mysqli_prepare($con, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "i", $payroll_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if(mysqli_stmt_num_rows($check_stmt) == 0) {
            throw new Exception("Payroll not found or already paid");
        }
        
        // Update to paid status
        $update_sql = "UPDATE payroll 
                      SET payment_status = 'Paid', 
                          payment_date = NOW() 
                      WHERE payroll_id = ?";
        $update_stmt = mysqli_prepare($con, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "i", $payroll_id);
        mysqli_stmt_execute($update_stmt);
        
        mysqli_commit($con);
        $_SESSION['message'] = "Payroll #$payroll_id marked as paid successfully";
    } catch (Exception $e) {
        mysqli_rollback($con);
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: ../payroll_view.php");
    exit();
}