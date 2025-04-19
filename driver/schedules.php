<?php
$title = "Today's Schedules";
$activePage = "schedules";
session_start();
ob_start();
include "../dbcon.php";

// Check if logged in and role is driver
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (strtolower($_SESSION['role']) !== "driver") {
    header("Location: ../unauthorized.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch driver_id if not already in session
if (!isset($_SESSION['driver_id'])) {
    $driverQuery = $con->prepare("SELECT driver_id FROM drivers WHERE user_id = ?");
    $driverQuery->bind_param("i", $user_id);
    $driverQuery->execute();
    $driverResult = $driverQuery->get_result();

    if ($row = $driverResult->fetch_assoc()) {
        $_SESSION['driver_id'] = $row['driver_id'];
    } else {
        // Handle case where driver record doesn't exist
        header("Location: ../unauthorized.php");
        exit();
    }
}

$driver_id = $_SESSION['driver_id'];
$today = date('Y-m-d');

// Get today's schedules for this driver
$sql = "
    SELECT 
        s.schedule_id, 
        s.start_time, 
        s.end_time, 
        s.pick_up, 
        s.destination,
        s.distance_km,
        u.username,
        t.truck_no,
        h.full_name AS helper_name,
        (SELECT delivery_status FROM deliveries 
         WHERE schedule_id = s.schedule_id 
         ORDER BY delivery_datetime DESC LIMIT 1) as delivery_status
    FROM schedules s 
    JOIN users u ON s.customer_id = u.user_id
    LEFT JOIN trucks t ON s.truck_id = t.truck_id
    LEFT JOIN helpers h ON s.helper_id = h.helper_id
    WHERE s.driver_id = ?
    AND DATE(s.start_time) = ?
    ORDER BY s.start_time ASC
";

$stmt = $con->prepare($sql);
$stmt->bind_param("is", $driver_id, $today);
$stmt->execute();
$schedulesResult = $stmt->get_result();
?>

<h1>Today's Schedules (<?= date('F j, Y') ?>)</h1>

<div class="fixed-header">
    <table border="1" cellpadding="5">
        <tr>
            <th>Schedule ID</th>
            <th>Customer</th>
            <th>Pick-Up</th>
            <th>Destination</th>
            <th>Distance (km)</th>
            <th>Truck No</th>
            <th>Helper</th>
            <th>Start Time</th>
            <th>End Time</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php if ($schedulesResult->num_rows > 0): ?>
            <?php while ($row = $schedulesResult->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['schedule_id'] ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['pick_up']) ?></td>
                    <td><?= htmlspecialchars($row['destination']) ?></td>
                    <td><?= $row['distance_km'] ?></td>
                    <td><?= htmlspecialchars($row['truck_no']) ?></td>
                    <td><?= htmlspecialchars($row['helper_name'] ?? 'N/A') ?></td>
                    <td><?= date('h:i A', strtotime($row['start_time'])) ?></td>
                    <td><?= date('h:i A', strtotime($row['end_time'])) ?></td>
                    <td><?= htmlspecialchars($row['delivery_status'] ?? 'Scheduled') ?></td>
                    <td>
                        <?php
                        $status = $row['delivery_status'] ?? 'Scheduled';
                        if ($status === 'Scheduled' || $status === 'Pending'): ?>
                            <form method="POST" action="schedules/update_delivery_status.php" style="display:inline;">
                                <input type="hidden" name="schedule_id" value="<?= $row['schedule_id'] ?>">
                                <input type="hidden" name="new_status" value="In Transit">
                                <button type="submit" class="btn-start">Start Delivery</button>
                            </form>
                        <?php elseif ($status === 'In Transit'): ?>
                            <form method="POST" action="schedules/update_delivery_status.php" style="display:inline;">
                                <input type="hidden" name="schedule_id" value="<?= $row['schedule_id'] ?>">
                                <input type="hidden" name="new_status" value="Delivered">
                                <button type="submit" class="btn-complete">Mark as Delivered</button>
                            </form>
                        <?php else: ?>
                            <span class="completed">Completed</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="11" style="text-align: center;">No schedules for today</td>
            </tr>
        <?php endif; ?>
    </table>
</div>



<?php
$content = ob_get_clean();
include "../layout/driver_layout.php";
?>