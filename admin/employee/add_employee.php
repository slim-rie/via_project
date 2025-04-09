<?php
session_start();
include '../../dbcon.php'; // Ensure this file contains the database connection code

$error = $success = "";
$name = "";
$position = "";
$role = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim($_POST["name"]);
    $position = trim($_POST["position"]);
    $role = $_POST["role"];

    // Check if role is valid
    if ($role != "Driver" && $role != "Porter") {
        $error = "Invalid role selected.";
    } else {
        // Insert into employees table
        $insert_employee = $con->prepare("INSERT INTO employees (employee_id, name, position) VALUES (?, ?, ?)");
        $employee_id = null; // Let the database auto-increment this field

        // Attempt to execute the prepared statement
        if ($insert_employee->execute([$employee_id, $name, $position])) {
            // Get the last inserted employee ID
            $employee_id = $con->insert_id;

            // Insert into users table for the new employee
            $insert_user = $con->prepare("INSERT INTO users (username, password, role_id, employee_id) VALUES (?, ?, ?, ?)");
            $username = strtolower($name); // Use name as username
            $password = password_hash('defaultpassword', PASSWORD_DEFAULT); // Default password (change this logic as needed)
         
            
            // Determine role_id based on the selected role
            $role_id = ($role == "Driver") ? 2 : 2; // Assuming both Driver and Porter map to role_id 2 for employees

            if ($insert_user->execute([$username, $password, $role_id, $employee_id])) {
                $success = "Employee added successfully!";
            } else {
                $error = "Failed to add employee to users table.";
            }
        } else {
            $error = "Failed to add employee.";
        }

        // Close the prepared statements
        $insert_employee->close();
        $insert_user->close();
    }

    // Close the database connection
    $con->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Add Employee</title>
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
                <h2 class="text-center mb-4" style="color: #364C84;">Add New Employee</h2>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php elseif ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="mb-3">
                    <label for="name" class="form-label">Name:</label>
                    <input type="text" name="name" id="name" class="form-control rounded-pill" required value="<?php echo htmlspecialchars($name); ?>">
                </div>

                <div class="mb-3">
                    <label for="position" class="form-label">Position:</label>
                    <input type="text" name="position" id="position" class="form-control rounded-pill" required value="<?php echo htmlspecialchars($position); ?>">
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label">Select Role:</label>
                    <select name="role" id="role" class="form-select rounded-pill" required>
                        <option value="" disabled selected>Select a role</option>
                        <option value="Driver" <?= ($role == "Driver") ? 'selected' : '' ?>>Driver</option>
                        <option value="Porter" <?= ($role == "Porter") ? 'selected' : '' ?>>Porter</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end">
                    <a href="../profile.php" class="btn btn-outline-secondary rounded-pill mx-2">Back</a>
                    <button type="submit" class="btn btn-outline-primary rounded-pill mx-2">Register</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
