<?php
$title = "Book";
$activePage = "book";
session_start();
ob_start();
include "../dbcon.php";

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('You must log in first.'); window.location.href = 'login.php';</script>";
    exit();
}

$userId = $_SESSION['user_id'];

// Get profile details
$profileQuery = $con->prepare("SELECT u.username, c.full_name, c.contact_no FROM users u JOIN customers c ON u.user_id = c.user_id WHERE u.user_id = ?");
$profileQuery->bind_param("i", $userId);
$profileQuery->execute();
$profileResult = $profileQuery->get_result()->fetch_assoc();
if (!$profileResult) {
    echo "No profile found.";
    exit();
}

// Get customer ID
$getCustomerId = $con->prepare("SELECT customer_id FROM customers WHERE user_id = ?");
$getCustomerId->bind_param("i", $userId);
$getCustomerId->execute();
$customerId = $getCustomerId->get_result()->fetch_assoc()['customer_id'];

// Load bookings
$bookingsQuery = $con->prepare("
    SELECT s.schedule_id, s.start_time, s.end_time, s.destination, s.pick_up, s.distance_km, 
           t.truck_no,
           (SELECT delivery_status FROM deliveries WHERE schedule_id = s.schedule_id ORDER BY delivery_id DESC LIMIT 1) AS delivery_status,
           (SELECT total_amount FROM payments WHERE schedule_id = s.schedule_id ORDER BY payment_id DESC LIMIT 1) AS total_amount,
           (SELECT status FROM payments WHERE schedule_id = s.schedule_id ORDER BY payment_id DESC LIMIT 1) AS payment_status
    FROM schedules s
    JOIN trucks t ON s.truck_id = t.truck_id
    WHERE s.customer_id = ?
");
$bookingsQuery->bind_param("i", $customerId);
$bookingsQuery->execute();
$bookingsResult = $bookingsQuery->get_result();

$trucks = $con->query("SELECT * FROM trucks WHERE status = 'Available'");
$prefill_date = $_GET['date'] ?? '';
?>

<h1>Welcome, <?= htmlspecialchars($profileResult['full_name']) ?>!</h1>
<h2>Your Profile</h2>
<ul>
  <li><strong>Username:</strong> <?= htmlspecialchars($profileResult['username']) ?></li>
  <li><strong>Contact No:</strong> <?= htmlspecialchars($profileResult['contact_no']) ?></li>
</ul>
<hr>
<button id="openBookingModal">Book a Truck Delivery</button>

<!-- Booking Modal -->
<div id="bookingModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.6); z-index:999;">
  <div class="modal-content" style="background:#fff; margin:5% auto; padding:20px; border-radius:8px; width:90%; max-width:600px; box-shadow:0 0 10px rgba(0,0,0,0.3);">
    <h2>Book a Truck Delivery</h2>
    <form method="POST" action="booking/process_booking.php">
      <label>Pick-up Location:</label>
      <input type="text" name="pick_up" required>

      <label>Destination:</label>
      <input type="text" name="destination" required>

      <label>Distance (km):</label>
      <input type="number" name="distance_km" min="1" step="0.1" required>

      <label>Booking Date:</label>
      <input type="date" name="booking_date" value="<?= $prefill_date ?>" required>

      <label>Truck:</label>
      <select name="truck_id" required>
        <?php while ($truck = $trucks->fetch_assoc()): ?>
          <option value="<?= $truck['truck_id'] ?>"><?= $truck['truck_no'] ?> (<?= $truck['capacity'] ?>)</option>
        <?php endwhile; ?>
      </select>

      <input type="hidden" name="start_time" value="06:00">
      <input type="hidden" name="end_time" value="18:00">

      <div style="margin-top:15px;">
        <button type="submit">Book Now</button>
        <button type="button" id="closeBookingModal">Close</button>
      </div>
    </form>
  </div>
</div>

<hr>

<h2>Your Bookings</h2>
<table border="1">
  <thead>
    <tr>
      <th>Schedule</th>
      <th>Pick-Up</th>
      <th>Destination</th>
      <th>Distance</th>
      <th>Truck</th>
      <th>Delivery Status</th>
      <th>Payment Status</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($row = $bookingsResult->fetch_assoc()): ?>
      <tr>
        <td><?= date('M j, Y', strtotime($row['start_time'])) ?><br><?= date('h:i A', strtotime($row['start_time'])) ?>–<?= date('h:i A', strtotime($row['end_time'])) ?></td>
        <td><?= htmlspecialchars($row['pick_up']) ?></td>
        <td><?= htmlspecialchars($row['destination']) ?></td>
        <td><?= htmlspecialchars($row['distance_km']) ?> km</td>
        <td><?= htmlspecialchars($row['truck_no']) ?></td>
        <td><?= htmlspecialchars($row['delivery_status'] ?? 'Pending') ?></td>
        <td><?= htmlspecialchars($row['payment_status'] ?? 'Pending') ?></td>
        <td>
          <?php if ($row['payment_status'] === 'Paid'): ?>
            Paid (₱<?= number_format($row['total_amount'], 2) ?>)
          <?php else: ?>
            <form method="POST" action="booking/pay_now.php">
              <input type="hidden" name="schedule_id" value="<?= $row['schedule_id'] ?>">
              <input type="hidden" name="amount" value="<?= $row['total_amount'] ?>">
              <button type="submit">Pay ₱<?= number_format($row['total_amount'], 2) ?></button>
            </form>
          <?php endif; ?>

          <?php if (new DateTime($row['start_time']) > new DateTime()): ?>
            <form method="POST" action="booking/cancel_booking.php" onsubmit="return confirm('Cancel this booking?');">
              <input type="hidden" name="schedule_id" value="<?= $row['schedule_id'] ?>">
              <button type="submit">Cancel</button>
            </form>
          <?php else: ?>
            <button disabled>Cannot Cancel</button>
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const modal = document.getElementById('bookingModal');
  document.getElementById('openBookingModal').onclick = () => modal.style.display = 'block';
  document.getElementById('closeBookingModal').onclick = () => modal.style.display = 'none';
  window.onclick = e => { if (e.target === modal) modal.style.display = 'none'; };
  document.getElementById('booking_date').min = new Date().toISOString().split('T')[0];
});
</script>

<?php
$content = ob_get_clean();
include "../layout/client_layout.php";
?>
