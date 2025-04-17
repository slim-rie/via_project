<?php
require '../../dbcon.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = mysqli_real_escape_string($con, $_POST['full_name']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $contact_no = mysqli_real_escape_string($con, $_POST['contact_no'] ?? '');
    $address = mysqli_real_escape_string($con, $_POST['address'] ?? '');
    
    $sql = "INSERT INTO customers (full_name, email, contact_no, address) 
            VALUES ('$full_name', '$email', '$contact_no', '$address')";
    
    if(mysqli_query($con, $sql)) {
        $_SESSION['success'] = "Client added successfully";
    } else {
        $_SESSION['error'] = "Error adding client: " . mysqli_error($con);
    }
}

header("Location: ../clients.php");
exit();
?>