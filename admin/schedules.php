<?php
$title = "All Schedules";
$activePage = "schedules";
session_start();
ob_start();
include "../dbcon.php";

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== "admin") {
    header("Location: ../login.php");
    exit();
}

$statusFilter = $_GET['filter'] ?? 'All';

$sql = "
    SELECT s.schedule_id, s.start_time, s.end_time, s.pick_up, s.destination, 
           u.username, d.delivery_status 
    FROM schedules s 
    JOIN users u ON s.user_id = u.user_id 
    LEFT JOIN deliveries d ON s.schedule_id = d.schedule_id
";

if ($statusFilter !== "All") {
    $sql .= " WHERE d.delivery_status = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $statusFilter);
} else {
    $stmt = $con->prepare($sql);
}

$stmt->execute();
$schedulesResult = $stmt->get_result();
?>

<h1>All Schedules</h1>

<form method="GET" style="margin-bottom: 15px;">
    <label>Filter by Status:
        <select name="filter" onchange="this.form.submit()">
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
                <td><?= htmlspecialchars($row['delivery_status'] ?? 'Pending') ?></td>
                <td>
                    <?php
                    $status = $row['delivery_status'] ?? 'Pending';
                    if (in_array($status, ['Pending', 'Accepted', 'In Transit', 'Delivered'])):
                    ?>
                        <form method="POST" action="schedules/update_delivery_status.php" style="display:inline;">
                            <input type="hidden" name="schedule_id" value="<?= $row['schedule_id'] ?>">
                            <?php
                            if ($status === 'Pending') {
                                echo '<input type="hidden" name="new_status" value="Accepted">';
                                echo '<button type="submit">Accept</button>';
                            } elseif ($status === 'Accepted') {
                                echo '<input type="hidden" name="new_status" value="In Transit">';
                                echo '<button type="submit">Start Delivery</button>';
                            } elseif ($status === 'In Transit') {
                                echo '<input type="hidden" name="new_status" value="Delivered">';
                                echo '<button type="submit">Mark as Delivered</button>';
                            } elseif ($status === 'Delivered') {
                                echo '<input type="hidden" name="new_status" value="Completed">';
                                echo '<button type="submit" onclick="return confirm(\'Confirm truck is back to company?\')">Mark as Completed</button>';
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
$stmt->close();
$con->close();
$content = ob_get_clean();
include "../layout/admin_layout.php";
?>
