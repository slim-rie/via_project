<?php
session_start();
include 'dbcon.php';

// Initialize variables
$username = $password = $email = $role = $full_name = $contact_no = $address = "";
$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $email = trim($_POST["email"]);
    $role = strtolower($_POST["role"]);
    $full_name = trim($_POST["full_name"]);
    $contact_no = trim($_POST["contact_no"]);
    $address = trim($_POST["address"]);

    // Check for strong password (minimum 8 characters, at least 1 letter, 1 number)
    if (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/", $password)) {
        $error = "Password must be at least 8 characters long, containing at least one letter and one number.";
    } else {
        // Check for duplicate username
        $stmt_check = $con->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $error = "Username already taken.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert into users table
            $insert_user = $con->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $insert_user->bind_param("sss", $username, $hashed_password, $role);

            if ($insert_user->execute()) {
                // Retrieve the new user_id
                $user_id = $insert_user->insert_id;

                // Insert into the respective table based on role
                if ($role === 'admin') {
                    $insert_customer = $con->prepare("INSERT INTO admins (user_id, full_name, email, contact_no, address) VALUES (?, ?, ?, ?, ?)");
                    $insert_customer->bind_param("issss", $user_id, $full_name, $email, $contact_no, $address);
                    $insert_customer->execute();
                    $insert_customer->close();
                } elseif ($role === 'customer') {
                    $insert_customer = $con->prepare("INSERT INTO customers (user_id, full_name, email, contact_no, address) VALUES (?, ?, ?, ?, ?)");
                    $insert_customer->bind_param("issss", $user_id, $full_name, $email, $contact_no, $address);
                    $insert_customer->execute();
                    $insert_customer->close();
                } elseif ($role === 'driver') {
                    $insert_employee = $con->prepare("INSERT INTO drivers (user_id, full_name, email, contact_no, address) VALUES (?, ?, ?, ?, ?)");
                    $insert_employee->bind_param("issss", $user_id, $full_name, $email, $contact_no, $address);
                    $insert_employee->execute();
                    $insert_employee->close();
                } elseif ($role === 'helper') {
                    $insert_employee = $con->prepare("INSERT INTO helpers (user_id, full_name, email, contact_no, address) VALUES (?, ?, ?, ?, ?)");
                    $insert_employee->bind_param("issss", $user_id, $full_name, $email, $contact_no, $address);
                    $insert_employee->execute();
                    $insert_employee->close();
                }

                $success = "User registered successfully!";
                $username = $password = $email = $role = $full_name = $contact_no = $address = ""; // Clear fields
            } else {
                $error = "Something went wrong. Please try again.";
            }

            $insert_user->close();
        }

        $stmt_check->close();
    }

    $con->close();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin - Register User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Bruno Ace', Arial, sans-serif !important;
            background-color: #f8f9fa;
            color: #364C84;
        }
    </style>
</head>

<body>
    <div class="container vh-100 d-flex justify-content-center align-items-center">
        <div class="col-md-6">
            <form method="POST" class="p-4 shadow rounded-4 bg-light">
                <h2 class="text-center mb-4" style="color: #364C84;">Register New User</h2>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php elseif ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="mb-3">
                    <label for="username" class="form-label">Username:</label>
                    <input type="text" name="username" id="username" class="form-control rounded-pill" required value="<?php echo htmlspecialchars($username); ?>">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password:</label>
                    <input type="password" name="password" id="password" class="form-control rounded-pill" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" name="email" id="email" class="form-control rounded-pill" required value="<?php echo htmlspecialchars($email); ?>">
                </div>

                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name:</label>
                    <input type="full_name" name="full_name" id="full_name" class="form-control rounded-pill" required value="<?php echo htmlspecialchars($full_name); ?>">
                </div>

                <div class="mb-3">
                    <label for="contact_no" class="form-label">Contact Number:</label>
                    <input type="tel" name="contact_no" id="contact_no" class="form-control rounded-pill" required
                        pattern="[0-9]{11}" title="Please enter a valid 11-digit Philippine mobile number"
                        value="<?php echo htmlspecialchars($contact_no); ?>">
                    <small class="text-muted">Format: 09123456789 (11 digits)</small>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Address:</label>
                    <textarea name="address" id="address" class="form-control" rows="3" required><?php echo htmlspecialchars($address); ?></textarea>
                    <small class="text-muted">Please include city/municipality and province</small>
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Select Role:</label>
                    <select name="role" id="role" class="form-select rounded-pill" required>
                        <option value="" disabled selected>Select a role</option>
                        <option value="Admin" <?= ($role == "Admin") ? 'selected' : '' ?>>Admin</option>
                        <option value="Driver" <?= ($role == "Driver") ? 'selected' : '' ?>>Driver</option>
                        <option value="Helper" <?= ($role == "Helper") ? 'selected' : '' ?>>Helper</option>
                        <option value="Customer" <?= ($role == "Customer") ? 'selected' : '' ?>>Customer</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end">
                    <a href="admin/dashboard.php" class="btn btn-outline-secondary rounded-pill mx-2">Back</a>
                    <button type="submit" class="btn btn-outline-primary rounded-pill mx-2">Register</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>