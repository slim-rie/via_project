<?php
require '../dbcon.php';
$title = "Client Details";
$activePage = "clients";
ob_start();
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== "admin") {
    header("Location: ../login.php");
    exit();
}

if(isset($_GET['id'])) {
    $client_id = mysqli_real_escape_string($con, $_GET['id']);
    
    $sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM schedules WHERE customer_id = c.customer_id) AS total_bookings,
            (SELECT MAX(start_time) FROM schedules WHERE customer_id = c.customer_id) AS last_booking
            FROM customers c
            WHERE c.customer_id = $client_id";
    $result = mysqli_query($con, $sql);
    $client = mysqli_fetch_assoc($result);
    
    if($client) {
        ?>
        <div class="container-fluid px-0">
            <!-- Added px-0 to remove default padding that might conflict with sidebar -->
            <div class="card shadow mb-4 ml-3">
                <!-- Added ml-3 to create space from sidebar -->
                <div class="card-header py-3" style="background-color: #364C84; color: white;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="m-0 font-weight-bold">Client Information</h5>
                        <a href="clients.php" class="btn btn-sm btn-light">
                            <i class="bi bi-arrow-left"></i> Back to Clients
                        </a>
                    </div>
                </div>
                <div class="card-body pl-4">
                    <!-- Added pl-4 for inner padding -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center mb-4">
                                <div class="bg-gradient-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" 
                                     style="width: 100px; height: 100px; font-size: 2.5rem; background: linear-gradient(180deg, #364C84, #4A5C9B);">
                                    <?= strtoupper(substr($client['full_name'], 0, 1)) ?>
                                </div>
                                <h4 class="mt-3 text-gray-900"><?= htmlspecialchars($client['full_name']) ?></h4>
                                <small class="text-muted">Client ID: <?= $client['customer_id'] ?></small>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="mb-4">
                                <h5 class="text-primary"><i class="bi bi-person-lines-fill"></i> Contact Information</h5>
                                <hr class="mt-2 mb-3">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <p class="mb-1"><strong class="text-gray-700">Email:</strong></p>
                                        <p class="<?= $client['email'] ? '' : 'text-muted' ?>">
                                            <?= $client['email'] ? htmlspecialchars($client['email']) : 'Not provided' ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <p class="mb-1"><strong class="text-gray-700">Phone:</strong></p>
                                        <p class="<?= $client['contact_no'] ? '' : 'text-muted' ?>">
                                            <?= $client['contact_no'] ? htmlspecialchars($client['contact_no']) : 'Not provided' ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <p class="mb-1"><strong class="text-gray-700">Address:</strong></p>
                                    <p class="<?= $client['address'] ? '' : 'text-muted' ?>">
                                        <?= $client['address'] ? nl2br(htmlspecialchars($client['address'])) : 'Not provided' ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <h5 class="text-primary"><i class="bi bi-graph-up"></i> Booking Statistics</h5>
                                <hr class="mt-2 mb-3">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <p class="mb-1"><strong class="text-gray-700">Total Bookings:</strong></p>
                                        <p class="h5 text-gray-800"><?= $client['total_bookings'] ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <p class="mb-1"><strong class="text-gray-700">Last Booking:</strong></p>
                                        <p class="h5 text-gray-800">
                                            <?= $client['last_booking'] ? date('F j, Y', strtotime($client['last_booking'])) : 'Never' ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow mb-4 ml-3">
                <!-- Added ml-3 to match first card -->
                <div class="card-header py-3" style="background-color: #364C84; color: white;">
                    <h5 class="m-0 font-weight-bold">Recent Bookings</h5>
                </div>
                <div class="card-body pl-4">
                    <!-- Added pl-4 for inner padding -->
                    <?php
                    $bookings_sql = "SELECT s.schedule_id, s.start_time, s.destination, t.truck_no 
                                   FROM schedules s
                                   JOIN trucks t ON s.truck_id = t.truck_id
                                   WHERE s.customer_id = {$client['customer_id']}
                                   ORDER BY s.start_time DESC
                                   LIMIT 5";
                    $bookings_result = mysqli_query($con, $bookings_sql);
                    
                    if(mysqli_num_rows($bookings_result) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Destination</th>
                                        <th>Truck</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($booking = mysqli_fetch_assoc($bookings_result)): ?>
                                    <tr>
                                        <td><?= date('M j, Y', strtotime($booking['start_time'])) ?></td>
                                        <td><?= htmlspecialchars($booking['destination']) ?></td>
                                        <td><?= htmlspecialchars($booking['truck_no']) ?></td>
                                        <td>
                                            <a href="schedule_view.php?id=<?= $booking['schedule_id'] ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-calendar-x text-gray-400" style="font-size: 3rem;"></i>
                            <h5 class="text-gray-500 mt-3">No bookings found</h5>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    } else {
        echo '<div class="alert alert-danger ml-3">Client not found</div>';
    }
} else {
    echo '<div class="alert alert-danger ml-3">Invalid request</div>';
}

$content = ob_get_clean();
include "../layout/admin_layout.php";
?>