<?php
include "../dbcon.php";
$title = "Truck Management";
$activePage = "trucks";
ob_start();
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== "admin") {
    header("Location: ../login.php");
    exit();
}

// Handle add/update truck
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_truck'])) {
        $truck_no = $_POST['truck_no'];
        $status = $_POST['status'];
        $driver_id = !empty($_POST['driver_id']) ? $_POST['driver_id'] : null;
        $helper_id = !empty($_POST['helper_id']) ? $_POST['helper_id'] : null;
        
        $stmt = $con->prepare("INSERT INTO trucks (truck_no, status, driver_id, helper_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $truck_no, $status, $driver_id, $helper_id);
        $stmt->execute();
        $_SESSION['message'] = "Truck added successfully!";
        $_SESSION['message_type'] = "success";
    } 
    elseif (isset($_POST['update_truck'])) {
        $truck_id = $_POST['truck_id'];
        $truck_no = $_POST['truck_no'];
        $status = $_POST['status'];
        $driver_id = !empty($_POST['driver_id']) ? $_POST['driver_id'] : null;
        $helper_id = !empty($_POST['helper_id']) ? $_POST['helper_id'] : null;
        
        $stmt = $con->prepare("UPDATE trucks SET truck_no = ?, status = ?, driver_id = ?, helper_id = ? WHERE truck_id = ?");
        $stmt->bind_param("ssiii", $truck_no, $status, $driver_id, $helper_id, $truck_id);
        $stmt->execute();
        $_SESSION['message'] = "Truck updated successfully!";
        $_SESSION['message_type'] = "success";
    }
}

// Handle delete truck
if (isset($_GET['delete'])) {
    $truck_id = $_GET['delete'];
    $stmt = $con->prepare("DELETE FROM trucks WHERE truck_id = ?");
    $stmt->bind_param("i", $truck_id);
    $stmt->execute();
    $_SESSION['message'] = "Truck deleted successfully!";
    $_SESSION['message_type'] = "danger";
    header("Location: trucks.php");
    exit();
}

// Fetch trucks with their assigned drivers and helpers
$trucks = $con->query("
    SELECT t.truck_id, t.truck_no, t.status, t.driver_id, t.helper_id, 
           d.full_name AS driver_name, h.full_name AS helper_name
    FROM trucks t
    LEFT JOIN drivers d ON t.driver_id = d.driver_id
    LEFT JOIN helpers h ON t.helper_id = h.helper_id
    ORDER BY t.truck_no
") or die($con->error);

// Fetch all drivers for dropdown
$drivers = $con->query("SELECT driver_id, full_name FROM drivers ORDER BY full_name") or die($con->error);

// Fetch all helpers for dropdown
$helpers = $con->query("SELECT helper_id, full_name FROM helpers ORDER BY full_name") or die($con->error);
?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Truck Management</h1>
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTruckModal">
                <i class="bi bi-plus-circle"></i> Add New Truck
            </button>
        </div>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <!-- Trucks Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold">All Trucks</h6>
            <div class="search-box">
                <input type="text" class="form-control form-control-sm" id="truckSearch" placeholder="Search trucks...">
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="truckTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th>Truck No</th>
                            <th>Status</th>
                            <th>Driver</th>
                            <th>Helper</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $trucks->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['truck_no']) ?></td>
                                <td>
                                    <span class="badge 
                                        <?= $row['status'] == 'Available' ? 'bg-success' : 
                                           ($row['status'] == 'Booked' ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                                <td><?= $row['driver_name'] ?? '<span class="text-muted">Not assigned</span>' ?></td>
                                <td><?= $row['helper_name'] ?? '<span class="text-muted">Not assigned</span>' ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-warning" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editTruckModal" 
                                                data-id="<?= $row['truck_id'] ?>"
                                                data-truck_no="<?= htmlspecialchars($row['truck_no']) ?>"
                                                data-status="<?= htmlspecialchars($row['status']) ?>"
                                                data-driver_id="<?= $row['driver_id'] ?>"
                                                data-helper_id="<?= $row['helper_id'] ?>">
                                            <i class="bi bi-pencil"></i> 
                                        </button>
                                        <a href="?delete=<?= $row['truck_id'] ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this truck?')">
                                            <i class="bi bi-trash"></i> 
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Truck Modal -->
<div class="modal fade" id="addTruckModal" tabindex="-1" aria-labelledby="addTruckModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTruckModalLabel">Add New Truck</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="truck_no" class="form-label">Truck Number</label>
                        <input type="text" class="form-control" id="truck_no" name="truck_no" required>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="Available">Available</option>
                            <option value="Booked">Booked</option>
                            <option value="Maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="driver_id" class="form-label">Driver</label>
                        <select class="form-select" id="driver_id" name="driver_id">
                            <option value="">Select Driver</option>
                            <?php while($driver = $drivers->fetch_assoc()): ?>
                                <option value="<?= $driver['driver_id'] ?>">
                                    <?= htmlspecialchars($driver['full_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="helper_id" class="form-label">Helper</label>
                        <select class="form-select" id="helper_id" name="helper_id">
                            <option value="">Select Helper</option>
                            <?php 
                            $helpers->data_seek(0); // Reset pointer
                            while($helper = $helpers->fetch_assoc()): ?>
                                <option value="<?= $helper['helper_id'] ?>">
                                    <?= htmlspecialchars($helper['full_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_truck" class="btn btn-primary">Add Truck</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Truck Modal -->
<div class="modal fade" id="editTruckModal" tabindex="-1" aria-labelledby="editTruckModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTruckModalLabel">Edit Truck</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="truck_id" id="edit_truck_id">
                    <div class="mb-3">
                        <label for="edit_truck_no" class="form-label">Truck Number</label>
                        <input type="text" class="form-control" id="edit_truck_no" name="truck_no" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="Available">Available</option>
                            <option value="Booked">Booked</option>
                            <option value="Maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_driver_id" class="form-label">Driver</label>
                        <select class="form-select" id="edit_driver_id" name="driver_id">
                            <option value="">Select Driver</option>
                            <?php 
                            $drivers->data_seek(0); // Reset pointer
                            while($driver = $drivers->fetch_assoc()): ?>
                                <option value="<?= $driver['driver_id'] ?>">
                                    <?= htmlspecialchars($driver['full_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_helper_id" class="form-label">Helper</label>
                        <select class="form-select" id="edit_helper_id" name="helper_id">
                            <option value="">Select Helper</option>
                            <?php 
                            $helpers->data_seek(0); // Reset pointer
                            while($helper = $helpers->fetch_assoc()): ?>
                                <option value="<?= $helper['helper_id'] ?>">
                                    <?= htmlspecialchars($helper['full_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_truck" class="btn btn-primary">Update Truck</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#truckTable').DataTable({
        "order": [[0, "asc"]],
        "responsive": true,
        "dom": '<"top"f>rt<"bottom"lip><"clear">',
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search trucks...",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ trucks",
            "paginate": {
                "previous": "<i class='bi bi-chevron-left'></i>",
                "next": "<i class='bi bi-chevron-right'></i>"
            }
        }
    });

    // Edit modal handler
    $('#editTruckModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var truckId = button.data('id');
        var truckNo = button.data('truck_no');
        var status = button.data('status');
        var driverId = button.data('driver_id');
        var helperId = button.data('helper_id');
        
        var modal = $(this);
        modal.find('#edit_truck_id').val(truckId);
        modal.find('#edit_truck_no').val(truckNo);
        modal.find('#edit_status').val(status);
        modal.find('#edit_driver_id').val(driverId);
        modal.find('#edit_helper_id').val(helperId);
    });
});
</script>

<?php
$content = ob_get_clean();
include "../layout/admin_layout.php";
?>