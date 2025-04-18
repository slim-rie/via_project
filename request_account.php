<?php 
include 'dbcon.php';
$success = false; // ✅ Initialize the variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact_number']);
    $username = trim($_POST['username']);

    // Optional: Check if username or email already exists in account_requests or users
    $check = $con->prepare("SELECT id FROM account_requests WHERE email = ? OR username = ?");
    $check->bind_param("ss", $email, $username);
    $check->execute();
    $result = $check->get_result();
    if ($result->num_rows > 0) {
        echo "Email or username already requested!";
    } else {
        // Insert new request
        $stmt = $con->prepare("INSERT INTO account_requests (full_name, email, contact_number, username) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $contact, $username);
        if ($stmt->execute()) {
            $success = true; // Set to true only if insert succeeds
            echo "Request sent successfully!";
        } else {
            echo "Error sending request.";
        }
        $stmt->close();
    }
    $check->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Account - Joredane Trucking Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Bruno Ace', sans-serif;
            background-color: #f0f2f5;
        }
        .form-container {
            max-width: 600px;
            margin: auto;
            margin-top: 5%;
            padding: 30px;
            background-color: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .form-label {
            font-size: 1rem;
            color: #364C84;
        }
        .form-control {
            border-radius: 50px;
        }
        .btn-primary, .btn-secondary {
            border-radius: 50px;
            padding-left: 30px;
            padding-right: 30px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="form-container">
        <h3 class="text-center mb-4 text-primary">Request an Account</h3>

        <?php if ($success): ?>
            <div class="alert alert-success text-center" role="alert">
                Your request has been submitted successfully!
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" name="full_name" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Contact Number</label>
                <input type="text" class="form-control" name="contact_number" pattern="[0-9]{10,15}" title="Enter a valid phone number" required>
            </div>

            <div class="mb-4">
                <label class="form-label">Preferred Username</label>
                <input type="text" class="form-control" name="username" required>
            </div>

            <div class="d-flex justify-content-between">
                <a href="login.php" class="btn btn-secondary">Back to Login</a>
                <button type="submit" class="btn btn-primary">Request Account</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
