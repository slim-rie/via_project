<?php
session_start();
include '../../dbcon.php'; // Ensure this file contains the database connection code

$error = $success = "";
$name = "";
$contact = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim($_POST["name"]);
    $contact = trim($_POST["contact"]);

    // Basic validation
    if (empty($name) || empty($contact)) {
        $error = "Please fill in all fields.";
    } else {
        // Insert into customers table
        $stmt = $con->prepare("INSERT INTO customers (name, contact) VALUES (?, ?)");

        if ($stmt) {
            $stmt->bind_param("ss", $name, $contact);
            if ($stmt->execute()) {
                $success = "Customer added successfully!";
                $name = $contact = ""; // Reset form values
            } else {
                $error = "Failed to add customer: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Failed to prepare statement: " . $con->error;
        }
    }

    $con->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Add Customer</title>
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
                <h2 class="text-center mb-4" style="color: #364C84;">Add New Customer</h2>

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
                    <label for="contact" class="form-label">Contact:</label>
                    <input type="text" name="contact" id="contact" class="form-control rounded-pill" required value="<?php echo htmlspecialchars($contact); ?>">
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
