<?php
$title = "Schedules Today";
$activePage = "schedules";
session_start();
ob_start();
include "../dbcon.php";

// Check if logged in and role is driver
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== "driver") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch driver_id from users table and store in session
if (!isset($_SESSION['driver_id'])) {
    $driverQuery = $con->prepare("SELECT driver_id FROM drivers WHERE user_id = ?");
    $driverQuery->bind_param("i", $user_id);
    $driverQuery->execute();
    $driverResult = $driverQuery->get_result();

    if ($row = $driverResult->fetch_assoc()) {
        $_SESSION['driver_id'] = $row['driver_id'];
    }
}

// Now you can use $_SESSION['driver_id'] anywhere in your script

$statusFilter = $_GET['filter'] ?? 'All';

$sql = "
   SELECT s.schedule_id, s.start_time, s.end_time, s.pick_up, s.destination, 
           u.username, 
           (SELECT delivery_status FROM deliveries 
            WHERE schedule_id = s.schedule_id 
            ORDER BY delivery_datetime DESC LIMIT 1) as delivery_status
    FROM schedules s 
    JOIN users u ON s.customer_id = u.user_id 
    WHERE s.driver_id = ?
";

if ($statusFilter !== "All") {
    $sql .= " AND d.delivery_status = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("is", $_SESSION['driver_id'], $statusFilter);
} else {
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $_SESSION['driver_id']);
}

$stmt->execute();
$schedulesResult = $stmt->get_result();
?>

<h1>All Schedules</h1>

<form method="GET" style="margin-bottom: 15px;">
    <label>Filter by Status:
        <select name="filter" onchange="this.form.submit()">
            <option value="All" <?= $statusFilter == 'All' ? 'selected' : '' ?>>All</option>
            <option value="Scheduled" <?= $statusFilter == 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
            <option value="In Transit" <?= $statusFilter == 'In Transit' ? 'selected' : '' ?>>In Transit</option>
            <option value="Delivered" <?= $statusFilter == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
            <option value="Returned" <?= $statusFilter == 'Returned' ? 'selected' : '' ?>>Returned</option>
        </select>
    </label>
</form>

<div class="fixed-header">
    <table border="1" cellpadding="5">
        <tr>
            <th>Schedule ID</th>
            <th>Customer Username</th>
            <th>Pick-Up</th>
            <th>Destination</th>
            <th>Start Time</th>
            <th>End Time</th>
            <th>Delivery Status</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $schedulesResult->fetch_assoc()): ?>
            <tr>
                <td><?= $row['schedule_id'] ?></td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?= htmlspecialchars($row['pick_up']) ?></td>
                <td><?= htmlspecialchars($row['destination']) ?></td>
                <td><?= htmlspecialchars($row['start_time']) ?></td>
                <td><?= htmlspecialchars($row['end_time']) ?></td>
                <td><?= htmlspecialchars($row['delivery_status'] ?? 'Scheduled') ?></td>
                <td>
                    <?php
                    $status = $row['delivery_status'] ?? 'Scheduled';
                    if (in_array($status, ['In Transit', 'Delivered'])):
                    ?>
                        <form method="POST" action="schedules/update_delivery_status.php" style="display:inline;">
                            <input type="hidden" name="schedule_id" value="<?= $row['schedule_id'] ?>">
                            <?php
                            if ($status === 'In Transit') {
                                echo '<input type="hidden" name="new_status" value="Delivered">';
                                echo '<button type="submit">Mark as Delivered</button>';
                            }
                            ?>
                        </form>
                    <?php else: ?>
                        No Action
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php
$content = ob_get_clean();
include "../layout/driver_layout.php";
?>
