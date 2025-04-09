<?php
session_start();
include 'dbcon.php';

// Redirect if not logged in or not an admin
if (!isset($_SESSION["user_id"]) || strtolower($_SESSION["role"]) !== "admin") {
    echo "<script>
            alert('Access denied. Only admins can register users.');
            window.location.href = 'login.php';
          </script>";
    exit();
}

$username = $password = $email = $role = $name = "";
$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $email = trim($_POST["email"]);
    $role = $_POST["role"];
    $name = trim($_POST["name"]); // Get the name from the form

    // Map role names to their IDs
    $role_map = [
        'Admin' => 1,
        'Driver' => 2,
        'Porter' => 2,
        'Client' => 3,
    ];

    if (!array_key_exists($role, $role_map)) {
        $error = "Invalid role selected.";
    } else {
        // Check for duplicate username
        $stmt = $con->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username already taken.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert into users table (without email)
            $insert_user = $con->prepare("INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)");
            $insert_user->bind_param("ssi", $username, $hashed_password, $role_map[$role]);

            if ($insert_user->execute()) {
                // Retrieve the new user_id
                $user_id = $insert_user->insert_id;

                // Insert into related table based on role
                if ($role === 'Client') {
                    $insert_customer = $con->prepare("INSERT INTO customers (name, email) VALUES (?, ?)");
                    $insert_customer->bind_param("ss", $name, $email);
                    $insert_customer->execute();
                    $insert_customer->close();

                    // Update the users table with customer_id
                    $update_user = $con->prepare("UPDATE users SET customer_id = ? WHERE user_id = ?");
                    $customer_id = $con->insert_id; // Retrieve customer_id from last insert
                    $update_user->bind_param("ii", $customer_id, $user_id);
                    $update_user->execute();
                    $update_user->close();
                } elseif (in_array($role, ['Driver', 'Porter'])) {
                    $insert_employee = $con->prepare("INSERT INTO employees (name, email) VALUES (?, ?)");
                    $insert_employee->bind_param("ss", $name, $email);
                    $insert_employee->execute();
                    $insert_employee->close();

                    // Update the users table with employee_id
                    $update_user = $con->prepare("UPDATE users SET employee_id = ? WHERE user_id = ?");
                    $employee_id = $con->insert_id; // Retrieve employee_id from last insert
                    $update_user->bind_param("ii", $employee_id, $user_id);
                    $update_user->execute();
                    $update_user->close();
                }

                $success = "User registered successfully!";
                $username = $password = $email = $role = $name = ""; // Clear fields
            } else {
                $error = "Something went wrong. Please try again.";
            }

            $insert_user->close();
        }

        $stmt->close();
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
                    <label for="name" class="form-label">Name:</label>
                    <input type="text" name="name" id="name" class="form-control rounded-pill" required value="<?php echo htmlspecialchars($name); ?>">
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label">Select Role:</label>
                    <select name="role" id="role" class="form-select rounded-pill" required>
                        <option value="" disabled selected>Select a role</option>
                        <option value="Admin" <?= ($role == "Admin") ? 'selected' : '' ?>>Admin</option>
                        <option value="Driver" <?= ($role == "Driver") ? 'selected' : '' ?>>Driver</option>
                        <option value="Porter" <?= ($role == "Porter") ? 'selected' : '' ?>>Porter</option>
                        <option value="Client" <?= ($role == "Client") ? 'selected' : '' ?>>Customer</option>
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
