<?php
require '../../dbcon.php';

if(isset($_GET['id'])) {
    $client_id = mysqli_real_escape_string($con, $_GET['id']);
    $sql = "SELECT * FROM customers WHERE customer_id = $client_id";
    $result = mysqli_query($con, $sql);
    $client = mysqli_fetch_assoc($result);
}

$title = "Edit Client";
$activePage = "clients";
ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Client</h1>
        <a href="../clients.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Clients
        </a>
    </div>

    <?php if(isset($client)): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold">Edit Client Information</h6>
        </div>
        <div class="card-body">
            <form action="client_update.php" method="POST">
                <input type="hidden" name="customer_id" value="<?= $client['customer_id'] ?>">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="full_name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?= htmlspecialchars($client['full_name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= htmlspecialchars($client['email']) ?>" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="contact_no" class="form-label">Contact Number</label>
                        <input type="tel" class="form-control" id="contact_no" name="contact_no" 
                               value="<?= htmlspecialchars($client['contact_no']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2"><?= htmlspecialchars($client['address']) ?></textarea>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Client
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-danger">Client not found</div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include "../../layout/admin_layout.php";
?>