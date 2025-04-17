<?php
require '../../dbcon.php';

if(isset($_GET['id'])) {
    $client_id = mysqli_real_escape_string($con, $_GET['id']);
    
    // Check if client has any bookings first
    $check_sql = "SELECT COUNT(*) AS booking_count FROM schedules WHERE customer_id = $client_id";
    $check_result = mysqli_query($con, $check_sql);
    $check_data = mysqli_fetch_assoc($check_result);
    
    if($check_data['booking_count'] > 0) {
        $_SESSION['error'] = "Cannot delete client with existing bookings";
        header("Location: ../clients.php");
        exit();
    }
    
    // Proceed with deletion
    $delete_sql = "DELETE FROM customers WHERE customer_id = $client_id";
    if(mysqli_query($con, $delete_sql)) {
        $_SESSION['success'] = "Client deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting client: " . mysqli_error($con);
    }
}

header("Location: ../clients.php");
exit();
?>