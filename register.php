<?php
include 'dbcon.php';

$username = $password = "";
$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    // Check if user already exists
    $stmt = $con->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $error = "Username already taken.";
    } else {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $insert = $con->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $insert->bind_param("ss", $username, $hashed_password);

        if ($insert->execute()) {
            $success = "Registration successful! You can now <a href='login.php'>log in</a>.";
            $username = $password = "";
        } else {
            $error = "Something went wrong. Please try again.";
        }

        $insert->close();
    }

    $stmt->close();
    $con->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register</title>
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
                <h2 class="text-center mb-4" style="color: #364C84;">Register Account</h2>

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

                <div class="d-flex justify-content-end">
                    <a href="login.php" class="btn btn-outline-secondary rounded-pill mx-2">Back to Login</a>
                    <button type="submit" class="btn btn-outline-primary rounded-pill mx-2">Register</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>