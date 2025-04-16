<?php
$title = "Pending Bookings";
$activePage = "pending_bookings";
session_start();
ob_start();
include "../dbcon.php";

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== "admin") {
    header("Location: ../login.php");
    exit();
}

// Fetch pending bookings
$pendingBookingsQuery = $con->prepare("
    SELECT s.schedule_id, s.start_time, s.end_time, s.pick_up, s.destination, c.full_name AS customer_name
    FROM schedules s
    JOIN customers c ON s.customer_id = c.customer_id  -- Join with customers table
    LEFT JOIN deliveries d ON s.schedule_id = d.schedule_id
    WHERE d.delivery_status IS NULL OR d.delivery_status = 'Pending'
");
$pendingBookingsQuery->execute();
$pendingBookingsResult = $pendingBookingsQuery->get_result();
?>

<h1>Pending Bookings</h1>
<div class="fixed-header">
    <table border="1" cellpadding="5">
        <tr>
            <th>Booking ID</th>
            <th>Customer Name</th>  <!-- Changed 'Customer Username' to 'Customer Name' -->
            <th>Pick-Up</th>
            <th>Destination</th>
            <th>Start Time</th>
            <th>End Time</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $pendingBookingsResult->fetch_assoc()): ?>
            <tr>
                <td><?= $row['schedule_id'] ?></td>
                <td><?= htmlspecialchars($row['customer_name']) ?></td>  <!-- Changed to display customer name -->
                <td><?= htmlspecialchars($row['pick_up']) ?></td>
                <td><?= htmlspecialchars($row['destination']) ?></td>
                <td><?= htmlspecialchars($row['start_time']) ?></td>
                <td><?= htmlspecialchars($row['end_time']) ?></td>
                <td>
                    <form method="POST" action="schedules/accept_booking.php">
                        <input type="hidden" name="schedule_id" value="<?= $row['schedule_id'] ?>">
                        <button type="submit">Accept Booking</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php
$pendingBookingsQuery->close();
$con->close();
$content = ob_get_clean();
include "../layout/admin_layout.php";
?>
