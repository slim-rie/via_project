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

$profileQuery = $con->prepare("SELECT u.username, c.full_name, c.contact_no 
                               FROM users u
                               JOIN customers c ON u.user_id = c.user_id
                               WHERE u.user_id = ?");
$profileQuery->bind_param("i", $userId);
$profileQuery->execute();
$profileResult = $profileQuery->get_result()->fetch_assoc();
if (!$profileResult) {
  echo "No profile found for this user.";
  exit();
}

$getCustomerId = $con->prepare("SELECT customer_id FROM customers WHERE user_id = ?");
$getCustomerId->bind_param("i", $userId);
$getCustomerId->execute();
$customerId = $getCustomerId->get_result()->fetch_assoc()['customer_id'];

$bookingsQuery = $con->prepare("
    SELECT 
    s.schedule_id, 
    s.start_time, 
    s.end_time, 
    s.destination, 
    s.pick_up,
    t.truck_no,
    (
      SELECT d.delivery_status
      FROM deliveries d
      WHERE d.schedule_id = s.schedule_id
      ORDER BY d.delivery_id DESC
      LIMIT 1
    ) AS delivery_status,
    (
      SELECT p.total_amount
      FROM payments p
      WHERE p.schedule_id = s.schedule_id
      ORDER BY p.payment_id DESC
      LIMIT 1
    ) AS total_amount,
    (
      SELECT p.status
      FROM payments p
      WHERE p.schedule_id = s.schedule_id
      ORDER BY p.payment_id DESC
      LIMIT 1
    ) AS payment_status
FROM schedules s
JOIN trucks t ON s.truck_id = t.truck_id
WHERE s.customer_id = ?;

");
$bookingsQuery->bind_param("i", $customerId);
$bookingsQuery->execute();
$bookingsResult = $bookingsQuery->get_result();

$trucks = $con->query("SELECT * FROM trucks WHERE status = 'Available'");
$prefill_date = isset($_GET['date']) ? $_GET['date'] : '';
?>

<?php if (isset($_GET['success'])): ?>
  <div style="background-color: #d4edda; padding: 10px; color: #155724;">Booking successful!</div>
<?php elseif (isset($_GET['error'])): ?>
  <div style="background-color: #f8d7da; padding: 10px; color: #721c24;">This truck is already booked for your selected time.</div>
<?php endif; ?>

<h1>Welcome, <?= htmlspecialchars($profileResult['full_name']) ?>!</h1>

<h2>Your Profile</h2>
<ul>
  <li><strong>Username:</strong> <?= htmlspecialchars($profileResult['username']) ?></li>
  <li><strong>Contact No:</strong> <?= htmlspecialchars($profileResult['contact_no']) ?></li>
</ul>

<hr>

<!-- Button to open the booking modal -->
<button id="openBookingModal" style="padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
  Book a Truck Delivery
</button>

<!-- Booking Modal -->
<div id="bookingModal">
  <div class="modal-content">
    <h2>Book a Truck Delivery</h2>
    <form method="POST" action="booking/process_booking.php">
      <div class="form-group">
        <label>Pick-up Location:</label>
        <input type="text" name="pick_up" required>
      </div>

      <div class="form-group">
        <label>Destination:</label>
        <input type="text" name="destination" required>
      </div>

      <div class="form-group">
        <label>Booking Date:</label>
        <input type="date" id="booking_date" name="booking_date" value="<?= $prefill_date ?>" required>
      </div>

      <div class="form-group time-display">
        <div>
          <label>Start Time:</label>
          <div class="time-value">6:00 AM</div>
          <input type="hidden" name="start_time" value="06:00">
        </div>
        <div>
          <label>End Time:</label>
          <div class="time-value">6:00 PM</div>
          <input type="hidden" name="end_time" value="18:00">
        </div>
      </div>

      <div class="form-group">
        <label>Truck:</label>
        <select name="truck_id" required>
          <?php $trucks->data_seek(0);
          while ($truck = $trucks->fetch_assoc()): ?>
            <option value="<?= $truck['truck_id'] ?>"><?= $truck['truck_no'] ?> (<?= $truck['capacity'] ?>)</option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn-primary">Book Now</button>
        <button type="button" id="closeBookingModal" class="btn-secondary">Close</button>
      </div>
    </form>
  </div>
</div>

<hr>

<h2>Your Bookings</h2>
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
      <td><?= htmlspecialchars($row['payment_status'] ?? 'Pending') ?></td>
      <td>
        <?php if ($row['payment_status'] === 'Paid'): ?>
          Paid (₱<?= htmlspecialchars($row['total_amount']) ?>)
        <?php else: ?>
          <?php
          $start_time = new DateTime($row['start_time']);
          $end_time = new DateTime($row['end_time']);
          $days = $start_time->diff($end_time)->days ?: 1;
          $rate = 3000;
          $total = $rate * $days;
          ?>
          <form method="POST" action="booking/pay_now.php">
            <input type="hidden" name="schedule_id" value="<?= $row['schedule_id'] ?>">
            <input type="hidden" name="amount" value="<?= $total ?>">
            <button type="submit">Pay ₱<?= number_format($total, 2) ?></button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endwhile; ?>
</table>

<!-- Auto-set End Time -->
<script>
  window.addEventListener("DOMContentLoaded", function() {
    // Date input validation
    const bookingDate = document.getElementById('booking_date');

    // Set minimum date to today
    bookingDate.min = new Date().toISOString().split('T')[0];

    // Validate existing date
    if (bookingDate.value) {
      const selectedDate = new Date(bookingDate.value);
      const today = new Date();
      today.setHours(0, 0, 0, 0);

      if (selectedDate < today) {
        bookingDate.value = today.toISOString().split('T')[0];
      }
    }

    // Modal controls
    const openModalButton = document.getElementById('openBookingModal');
    const closeModalButton = document.getElementById('closeBookingModal');
    const modal = document.getElementById('bookingModal');

    // Open modal
    openModalButton.addEventListener('click', function() {
      modal.classList.add('show');
    });

    // Close modal
    closeModalButton.addEventListener('click', function() {
      modal.classList.remove('show');
    });

    // Close when clicking outside
    window.addEventListener('click', function(e) {
      if (e.target === modal) {
        modal.classList.remove('show');
      }
    });

    <?php if ($prefill_date): ?>
      modal.classList.add('show');
    <?php endif; ?>

  });
</script>
<?php
$content = ob_get_clean();
include "../layout/client_layout.php";
?>