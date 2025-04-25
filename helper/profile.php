<?php
// profile.php for helper
require '../dbcon.php';

ob_start();
session_start();

// Check if logged in and is a helper
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== "helper") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All password fields are required!";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match!";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long!";
    } else {
        // Verify current password
        $sql = "SELECT password FROM users WHERE user_id = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($current_password, $user['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE users SET password = ? WHERE user_id = ?";
                $update_stmt = $con->prepare($update_sql);
                $update_stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($update_stmt->execute()) {
                    $success = "Password changed successfully!";
                } else {
                    $error = "Failed to update password. Please try again.";
                }
            } else {
                $error = "Current password is incorrect!";
            }
        } else {
            $error = "User not found!";
        }
    }
}

// Get helper data with assigned truck and driver information
$sql = "SELECT h.*, u.username, t.truck_no, t.truck_type, d.full_name as driver_name
        FROM helpers h
        JOIN users u ON h.user_id = u.user_id
        LEFT JOIN trucks t ON h.helper_id = t.helper_id
        LEFT JOIN drivers d ON t.driver_id = d.driver_id
        WHERE h.user_id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$helper = $result->fetch_assoc();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Helper Profile</h1>
        <button class="btn btn-primary" id="changePasswordBtn" style="background-color: #364C84; border-color: #364C84;">
            <i class="bi bi-key"></i> Change Password
        </button>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4 profile-card">
                <div class="card-header py-3" style="background: linear-gradient(135deg, #364C84, #4A5C9B);">
                    <h6 class="m-0 font-weight-bold text-white">Helper Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="profile-info-item">
                                <div class="info-label">Username</div>
                                <div class="info-value"><?= htmlspecialchars($helper['username']) ?></div>
                            </div>
                            <div class="profile-info-item">
                                <div class="info-label">Full Name</div>
                                <div class="info-value"><?= htmlspecialchars($helper['full_name']) ?></div>
                            </div>
                            <div class="profile-info-item">
                                <div class="info-label">Email</div>
                                <div class="info-value"><?= htmlspecialchars($helper['email']) ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="profile-info-item">
                                <div class="info-label">Contact Number</div>
                                <div class="info-value"><?= htmlspecialchars($helper['contact_no']) ?></div>
                            </div>
                            <div class="profile-info-item">
                                <div class="info-label">Assigned Truck</div>
                                <div class="info-value">
                                    <?php if ($helper['truck_no']): ?>
                                        <?= htmlspecialchars($helper['truck_no']) ?> (<?= htmlspecialchars($helper['truck_type']) ?>)
                                    <?php else: ?>
                                        Not assigned
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="profile-info-item">
                                <div class="info-label">Assigned Driver</div>
                                <div class="info-value"><?= htmlspecialchars($helper['driver_name'] ?? 'Not assigned') ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <p class="text-muted">Contact admin if you need to update your profile information.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #364C84, #4A5C9B);">
                <h5 class="modal-title text-white" id="changePasswordModalLabel">Change Password</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="passwordForm">
                    <div class="form-group mb-4">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group mb-4">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <small class="form-text text-muted">Minimum 8 characters</small>
                    </div>
                    <div class="form-group mb-4">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="passwordForm" name="change_password" class="btn btn-primary" style="background-color: #364C84; border-color: #364C84;">Change Password</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize modal
        var changePasswordModal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
        
        // Show modal when button is clicked
        document.getElementById('changePasswordBtn').addEventListener('click', function() {
            changePasswordModal.show();
        });
        
        // Clear form when modal is hidden
        document.getElementById('changePasswordModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('passwordForm').reset();
        });
    });
</script>

<?php
$content = ob_get_clean();
include "../layout/helper_layout.php";
?>