<?php
$title = "Book";
$activePage = "book";
session_start();
echo "User ID from session: " . $_SESSION['user_id'];  // Debugging line

ob_start();
include "../dbcon.php"; // assumes you have this db connection file

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  echo "<script>alert('You must log in first.'); window.location.href = 'login.php';</script>";
  exit();
}

// Get the logged-in user's ID from session
$userId = $_SESSION['user_id'];

// Fetch customer profile
$profileQuery = $con->prepare("SELECT u.username, c.email, c.name, c.contact
                                FROM users u
                                JOIN customers c ON u.customer_id = c.customer_id
                                WHERE u.user_id = ?");
$profileQuery->bind_param("i", $userId);
$profileQuery->execute();
$profileResult = $profileQuery->get_result()->fetch_assoc();

if (!$profileResult) {
  // Handle the case where no profile data was returned
  echo "No profile found for this user.";
  exit();
}

// Fetch bookings
$bookingsQuery = $con->prepare("SELECT s.schedule_id, s.start_time, s.end_time, s.destination, s.pick_up,
       t.truck_no, d.delivery_status, p.total_amount, p.status AS payment_status
FROM schedules s
JOIN trucks t ON s.truck_id = t.truck_id
LEFT JOIN deliveries d ON s.schedule_id = d.schedule_id
LEFT JOIN payments p ON s.schedule_id = p.schedule_id
WHERE s.user_id = ?");
$bookingsQuery->bind_param("i", $userId);
$bookingsQuery->execute();
$bookingsResult = $bookingsQuery->get_result();

// Fetch available trucks
$trucks = $con->query("SELECT * FROM trucks WHERE status = 'available'");
?>

<h1>Welcome, <?= htmlspecialchars($profileResult['name']) ?>!</h1>

<h2>Your Profile</h2>
<ul>
  <li><strong>Username:</strong> <?= htmlspecialchars($profileResult['username']) ?></li>
  <li><strong>Email:</strong> <?= htmlspecialchars($profileResult['email']) ?></li>
</ul>

<hr>

<h2>Book a Truck Delivery</h2>
<form method="POST" action="booking/process_booking.php">
  <label>Pick-up Location: <input type="text" name="pick_up" required></label><br><br>
  <label>Destination: <input type="text" name="destination" required></label><br><br>
  <label>Start Time: <input type="datetime-local" name="start_time" required></label><br><br>
  <label>End Time: <input type="datetime-local" name="end_time" required></label><br><br>
  <label>Truck:
    <select name="truck_id" required>
      <?php while ($truck = $trucks->fetch_assoc()): ?>
        <option value="<?= $truck['truck_id'] ?>"><?= $truck['truck_no'] ?> (<?= $truck['capacity'] ?>)</option>
      <?php endwhile; ?>
    </select>
  </label><br><br>
  <button type="submit">Book Now</button>
</form>

<hr>

<h2>Your Bookings</h2>
<div class="fixed-header">
  <table border="1" cellpadding="5">
    <tr>
      <th>Schedule</th>
      <th>Pick-Up</th>
      <th>Destination</th>
      <th>Truck</th>
      <th>Delivery Status</th>
      <th>Payment Status</th>
      <th>Payment</th>
    </tr>
    <?php while ($row = $bookingsResult->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($row['start_time']) ?> to <?= htmlspecialchars($row['end_time']) ?></td>
        <td><?= htmlspecialchars($row['pick_up']) ?></td>
        <td><?= htmlspecialchars($row['destination']) ?></td>
        <td><?= htmlspecialchars($row['truck_no']) ?></td>
        <td><?= htmlspecialchars($row['delivery_status'] ?? 'Not yet started') ?></td>
        <td><?= htmlspecialchars($row['payment_status']) ?></td>
        <td>
          <?php if ($row['payment_status'] === 'Paid'): ?>
            Paid (₱<?= htmlspecialchars($row['total_amount']) ?>)
          <?php else: ?>
            <?php
            // Calculate the number of days for payment
            $start_time = new DateTime($row['start_time']);
            $end_time = new DateTime($row['end_time']);
            $interval = $start_time->diff($end_time);
            $number_of_days = $interval->days;

            // Calculate total amount
            $amount_per_day = 3000; // Amount charged per day
            $total_amount = $number_of_days * $amount_per_day;
            ?>
            <form method="POST" action="booking/pay_now.php">
              <input type="hidden" name="schedule_id" value="<?= htmlspecialchars($row['schedule_id']) ?>">
              <input type="hidden" name="amount" value="<?= $total_amount ?>"> <!-- Calculate amount based on days -->
              <button type="submit">Pay ₱<?= number_format($total_amount, 2) ?></button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>
</div>


<?php
$content = ob_get_clean();
include "../layout/client_layout.php";
?>