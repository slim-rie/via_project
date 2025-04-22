<?php
require '../dbcon.php';
$title = "Client Management";
$activePage = "clients";
ob_start();
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== "admin") {
    header("Location: ../login.php");
    exit();
}

?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Client Management</h1>
        <div>
            <a href="../register.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Client
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold">All Clients</h6>
            <div class="search-box">
                <input type="text" class="form-control form-control-sm" id="clientSearch" placeholder="Search clients...">
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="clientTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Client Name</th>
                            <th>Contact Info</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Total Bookings</th>
                            <th>Last Booking</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT c.*, 
                                (SELECT COUNT(*) FROM schedules WHERE customer_id = c.customer_id) AS total_bookings,
                                (SELECT MAX(start_time) FROM schedules WHERE customer_id = c.customer_id) AS last_booking
                                FROM customers c
                                ORDER BY c.full_name";
                        $result = mysqli_query($con, $sql);

                        while($row = mysqli_fetch_assoc($result)):
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['customer_id']) ?></td>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td>
                                <?= $row['contact_no'] ? htmlspecialchars($row['contact_no']) : '<span class="text-muted">Not provided</span>' ?>
                            </td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td>
                                <?= $row['address'] ? htmlspecialchars(substr($row['address'], 0, 30)) . (strlen($row['address']) > 30 ? '...' : '') : '<span class="text-muted">Not provided</span>' ?>
                            </td>
                            <td class="text-center"><?= $row['total_bookings'] ?></td>
                            <td>
                                <?= $row['last_booking'] ? date('M j, Y', strtotime($row['last_booking'])) : 'Never' ?>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="client_view.php?id=<?= $row['customer_id'] ?>" 
                                       class="btn btn-sm btn-info" 
                                       title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="clients/client_edit.php?id=<?= $row['customer_id'] ?>" 
                                       class="btn btn-sm btn-warning" 
                                       title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="clients/client_delete.php?id=<?= $row['customer_id'] ?>" 
                                       class="btn btn-sm btn-danger" 
                                       title="Delete"
                                       onclick="return confirm('Are you sure you want to delete this client?')">
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

<!-- Client Details Modal (for quick view) -->
<div class="modal fade" id="clientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Client Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="clientDetails">
                <!-- Content loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#clientTable').DataTable({
        "order": [[1, "asc"]],
        "responsive": true,
        "dom": '<"top"f>rt<"bottom"lip><"clear">',
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search clients...",
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ clients",
            "paginate": {
                "previous": "<i class='bi bi-chevron-left'></i>",
                "next": "<i class='bi bi-chevron-right'></i>"
            }
        }
    });

    // Quick view functionality
    $('.btn-info').on('click', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        
        $.get(url, function(data) {
            $('#clientDetails').html(data);
            $('#clientModal').modal('show');
        });
    });
});
</script>

<?php
$content = ob_get_clean();
include "../layout/admin_layout.php";
?>