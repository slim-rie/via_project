<?php
require '../../dbcon.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = mysqli_real_escape_string($con, $_POST['customer_id']);
    $full_name = mysqli_real_escape_string($con, $_POST['full_name']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $contact_no = mysqli_real_escape_string($con, $_POST['contact_no'] ?? '');
    $address = mysqli_real_escape_string($con, $_POST['address'] ?? '');
    
    $sql = "UPDATE customers SET 
            full_name = '$full_name',
            email = '$email',
            contact_no = '$contact_no',
            address = '$address'
            WHERE customer_id = $customer_id";
    
    if(mysqli_query($con, $sql)) {
        $_SESSION['success'] = "Client updated successfully";
    } else {
        $_SESSION['error'] = "Error updating client: " . mysqli_error($con);
    }
}

header("Location: ../clients.php");
exit();
?>