<?php
$title = "All Schedules";
$activePage = "schedules";
session_start();
ob_start();
include "../dbcon.php";

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== "admin") {
    header("Location: ../login.php");
    exit();
}

$statusFilter = $_GET['filter'] ?? 'All';

// Corrected SQL query with proper joins
$sql = "
    SELECT 
        s.schedule_id,
        s.start_time,
        s.end_time,
        s.pick_up,
        s.destination,
        c.full_name AS customer_name,
        t.truck_no,
        dr.driver_id,
        dr.full_name AS driver_name,
        (SELECT delivery_status FROM deliveries 
         WHERE schedule_id = s.schedule_id 
         ORDER BY delivery_datetime DESC LIMIT 1) AS delivery_status
    FROM schedules s
    JOIN customers c ON s.customer_id = c.customer_id
    LEFT JOIN trucks t ON s.truck_id = t.truck_id
    LEFT JOIN drivers dr ON s.driver_id = dr.driver_id
";

if ($statusFilter !== "All") {
    $sql .= " WHERE EXISTS (
        SELECT 1 FROM deliveries d 
        WHERE d.schedule_id = s.schedule_id 
        AND d.delivery_status = ?
    )";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $statusFilter);
} else {
    $stmt = $con->prepare($sql);
}

$stmt->execute();
$schedulesResult = $stmt->get_result();
?>

<h1>All Schedules</h1>

<form method="GET" class="filter-form">
    <label>Filter by Status:
        <select name="filter" class="form-select" onchange="this.form.submit()">
            <option value="All" <?= $statusFilter == 'All' ? 'selected' : '' ?>>All</option>
            <option value="Pending" <?= $statusFilter == 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Accepted" <?= $statusFilter == 'Accepted' ? 'selected' : '' ?>>Accepted</option>
            <option value="In Transit" <?= $statusFilter == 'In Transit' ? 'selected' : '' ?>>In Transit</option>
            <option value="Delivered" <?= $statusFilter == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
            <option value="Completed" <?= $statusFilter == 'Completed' ? 'selected' : '' ?>>Completed</option>
        </select>
    </label>
</form>

<div class="fixed-header">
    <table>
        <thead>
            <tr>
                <th>Schedule ID</th>
                <th>Customer Name</th>
                <th>Pick-Up</th>
                <th>Destination</th>
                <th>Truck No</th>
                <th>Driver</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Status</th> 
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($schedulesResult->num_rows > 0): ?>
                <?php while ($row = $schedulesResult->fetch_assoc()): 
                    $status = $row['delivery_status'] ?? 'Pending';
                    $statusClass = strtolower(str_replace(' ', '-', $status));
                ?>
                    <tr>
                        <td><?= $row['schedule_id'] ?></td>
                        <td><?= htmlspecialchars($row['customer_name']) ?></td>
                        <td><?= htmlspecialchars($row['pick_up']) ?></td>
                        <td><?= htmlspecialchars($row['destination']) ?></td>
                        <td><?= htmlspecialchars($row['truck_no'] ?? 'Not assigned') ?></td>
                        <td><?= htmlspecialchars($row['driver_name'] ?? 'Not assigned') ?></td>
                        <td><?= date('M j, Y h:i A', strtotime($row['start_time'])) ?></td>
                        <td><?= date('M j, Y h:i A', strtotime($row['end_time'])) ?></td>
                        <td>
                            <span class="status-badge status-<?= $statusClass ?>">
                                <?= htmlspecialchars($status) ?>
                            </span>
                        </td>
                        <td>
                            <?php if (in_array($status, ['Pending', 'Accepted', 'In Transit', 'Delivered'])): ?>
                                <form method="POST" action="schedules/update_delivery_status.php" class="action-form">
                                    <input type="hidden" name="schedule_id" value="<?= $row['schedule_id'] ?>">
                                    <?php if ($status === 'Pending'): ?>
                                        <input type="hidden" name="new_status" value="Accepted">
                                        <button type="submit" class="btn-action btn-accept">
                                            <i class="bi bi-check-circle"></i> Accept
                                        </button>
                                    <?php elseif ($status === 'Accepted'): ?>
                                        <input type="hidden" name="new_status" value="In Transit">
                                        <button type="submit" class="btn-action btn-start">
                                            <i class="bi bi-play-fill"></i> Start
                                        </button>
                                    <?php elseif ($status === 'In Transit'): ?>
                                        <input type="hidden" name="new_status" value="Delivered">
                                        <button type="submit" class="btn-action btn-deliver">
                                            <i class="bi bi-truck"></i> Deliver
                                        </button>
                                    <?php elseif ($status === 'Delivered'): ?>
                                        <input type="hidden" name="new_status" value="Completed">
                                        <button type="submit" class="btn-action btn-complete" onclick="return confirm('Confirm truck is back to company?')">
                                            <i class="bi bi-check-all"></i> Complete
                                        </button>
                                    <?php endif; ?>
                                </form>
                            <?php else: ?>
                                <span class="no-action">Completed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" class="no-schedules">No schedules found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- <style>
    .filter-form {
        margin-bottom: 20px;
        padding: 15px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .form-select {
        padding: 8px 12px;
        border-radius: 4px;
        border: 1px solid #ddd;
        background-color: white;
    }
    
    .fixed-header table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .fixed-header th {
        background-color: #364C84;
        color: white;
        padding: 12px;
        text-align: left;
        position: sticky;
        top: 0;
    }
    
    .fixed-header td {
        padding: 12px;
        border-bottom: 1px solid #eee;
    }
    
    .fixed-header tr:hover {
        background-color: #f5f7ff;
    }
    
    .status-badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    .status-pending {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .status-accepted {
        background-color: #cce5ff;
        color: #004085;
    }
    
    .status-in-transit {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-delivered {
        background-color: #d1ecf1;
        color: #0c5460;
    }
    
    .status-completed {
        background-color: #e2e3e5;
        color: #383d41;
    }
    
    .btn-action {
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        color: white;
        cursor: pointer;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .btn-accept {
        background-color: #17a2b8;
    }
    
    .btn-start {
        background-color: #28a745;
    }
    
    .btn-deliver {
        background-color: #ffc107;
        color: #212529;
    }
    
    .btn-complete {
        background-color: #6c757d;
    }
    
    .no-action {
        color: #6c757d;
        font-style: italic;
    }
    
    .no-schedules {
        text-align: center;
        padding: 20px;
        color: #6c757d;
    }
    
    .action-form {
        margin: 0;
    }
</style> -->

<?php
$stmt->close();
$con->close();
$content = ob_get_clean();
include "../layout/admin_layout.php";
?>