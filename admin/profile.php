<?php
$title = "Admin Profile";
$activePage = 'profile';
ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header  text-white">
                    <h5 class="mb-0">Profile Information</h5>
                </div>
                <div class="card-body text-center">
                    <img src="../assets/img/admin_avatar.png" class="rounded-circle mb-3" width="150" height="150" alt="Admin Avatar">
                    <h4 class="mb-1"><?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Admin User') ?></h4>
                    <p class="text-muted mb-3">Administrator</p>
                    
                    <div class="d-flex justify-content-center mb-3">
                        <button class="btn btn-outline-primary me-2">
                            <i class="bi bi-camera"></i> Change Photo
                        </button>
                        <button class="btn btn-outline-secondary">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header  text-white">
                    <h5 class="mb-0">Contact Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <p class="mb-1"><strong><i class="bi bi-envelope me-2"></i> Email</strong></p>
                        <p><?= htmlspecialchars($_SESSION['user']['email'] ?? 'admin@example.com') ?></p>
                    </div>
                    <div class="mb-3">
                        <p class="mb-1"><strong><i class="bi bi-telephone me-2"></i> Phone</strong></p>
                        <p><?= htmlspecialchars($_SESSION['user']['phone'] ?? '+1 234 567 890') ?></p>
                    </div>
                    <div class="mb-3">
                        <p class="mb-1"><strong><i class="bi bi-geo-alt me-2"></i> Address</strong></p>
                        <p><?= htmlspecialchars($_SESSION['user']['address'] ?? '123 Admin St, City, Country') ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header  text-white">
                    <h5 class="mb-0">Account Settings</h5>
                </div>
                <div class="card-body">
                    <form>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" value="<?= explode(' ', $_SESSION['user']['full_name'] ?? 'Admin')[0] ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" value="<?= explode(' ', $_SESSION['user']['full_name'] ?? 'User')[1] ?? '' ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($_SESSION['user']['email'] ?? 'admin@example.com') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" value="<?= htmlspecialchars($_SESSION['user']['phone'] ?? '+1 234 567 890') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" rows="3"><?= htmlspecialchars($_SESSION['user']['address'] ?? '123 Admin St, City, Country') ?></textarea>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header  text-white">
                    <h5 class="mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <form>
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control">
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include "../layout/admin_layout.php";
?>