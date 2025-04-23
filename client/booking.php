<?php
$title = "Book";
$activePage = "book";
session_start();
ob_start();
include "../dbcon.php";

if (isset($_SESSION['confirmation_message'])) {
  echo "<script>alert('" . addslashes($_SESSION['confirmation_message']) . "');</script>";
  unset($_SESSION['confirmation_message']);
}
if (isset($_SESSION['error_message'])) {
  echo "<script>alert('" . addslashes($_SESSION['error_message']) . "');</script>";
  unset($_SESSION['error_message']);
}
if (!isset($_SESSION['user_id'])) {
  echo "<script>alert('You must log in first.'); window.location.href = 'login.php';</script>";
  exit();
}

$userId = $_SESSION['user_id'];

// Handle delivery confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delivery'])) {
  $schedule_id = intval($_POST['schedule_id']);
  
  // Start transaction
  $con->begin_transaction();
  
  try {
      // 1. Update delivery status to "Received"
      $updateDelivery = $con->prepare("UPDATE deliveries SET delivery_status = 'Received' WHERE schedule_id = ?");
      $updateDelivery->bind_param("i", $schedule_id);
      $updateDelivery->execute();
      
      // 2. Get truck_id from the schedule
      $getTruck = $con->prepare("SELECT truck_id FROM schedules WHERE schedule_id = ?");
      $getTruck->bind_param("i", $schedule_id);
      $getTruck->execute();
      $truckResult = $getTruck->get_result()->fetch_assoc();
      $truck_id = $truckResult['truck_id'];
      
      // 3. Update truck status back to "Available"
      $updateTruck = $con->prepare("UPDATE trucks SET status = 'Available' WHERE truck_id = ?");
      $updateTruck->bind_param("i", $truck_id);
      $updateTruck->execute();
      
      // Commit transaction
      $con->commit();
      
      // Set session message and redirect
      $_SESSION['confirmation_message'] = 'Delivery confirmed as received and truck status updated!';
      header("Location: booking.php");
      exit();
  } catch (Exception $e) {
      // Rollback transaction if any error occurs
      $con->rollback();
      $_SESSION['error_message'] = 'Error confirming delivery: ' . $e->getMessage();
      header("Location: booking.php");
      exit();
  }
}

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
           t.truck_no, t.truck_id,
           (SELECT delivery_status FROM deliveries WHERE schedule_id = s.schedule_id ORDER BY delivery_id DESC LIMIT 1) AS delivery_status,
           (SELECT delivery_id FROM deliveries WHERE schedule_id = s.schedule_id ORDER BY delivery_id DESC LIMIT 1) AS delivery_id,
           (SELECT total_amount FROM payments WHERE schedule_id = s.schedule_id ORDER BY payment_id DESC LIMIT 1) AS total_amount,
           (SELECT status FROM payments WHERE schedule_id = s.schedule_id ORDER BY payment_id DESC LIMIT 1) AS payment_status
    FROM schedules s
    JOIN trucks t ON s.truck_id = t.truck_id
    WHERE s.customer_id = ?
    ORDER BY s.start_time DESC
");
$bookingsQuery->bind_param("i", $customerId);
$bookingsQuery->execute();
$bookingsResult = $bookingsQuery->get_result();

$trucks = $con->query("SELECT * FROM trucks WHERE status = 'Available'");
$prefill_date = $_GET['date'] ?? '';
?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Welcome, <?= htmlspecialchars($profileResult['full_name']) ?>!</h1>
  </div>

  <!-- Confirmation Modal -->
  <div class="modal fade" id="confirmationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Confirm Delivery Received</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST">
          <div class="modal-body">
            <p>Please confirm that you have received your delivery in good condition.</p>
            <p class="text-muted">This will also mark the truck as available for new bookings.</p>
            <input type="hidden" name="schedule_id" id="confirm_schedule_id">
            <input type="hidden" name="confirm_delivery" value="1">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Confirm Receipt</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="card shadow mb-4">
    <div class="card-header py-3">
      <h6 class="m-0 font-weight-bold text-primary">Your Profile</h6>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <p><strong>Username:</strong> <?= htmlspecialchars($profileResult['username']) ?></p>
        </div>
        <div class="col-md-6">
          <p><strong>Contact No:</strong> <?= htmlspecialchars($profileResult['contact_no']) ?></p>
        </div>
      </div>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bookingModal">
        <i class="bi bi-truck"></i> Book a Truck Delivery
      </button>
    </div>
  </div>

  <!-- Booking Modal -->
  <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="bookingModalLabel">Book a Truck Delivery</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="booking/process_booking.php">
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Pick-up Location</label>
              <input type="text" class="form-control" name="pick_up" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Destination</label>
              <input type="text" class="form-control" name="destination" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Distance (km)</label>
              <input type="number" class="form-control" name="distance_km" min="1" step="0.1" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Booking Date</label>
              <input type="date" class="form-control" name="booking_date" value="<?= $prefill_date ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Truck</label>
              <select class="form-select" name="truck_id" required>
                <?php while ($truck = $trucks->fetch_assoc()): ?>
                  <option value="<?= $truck['truck_id'] ?>"><?= $truck['truck_no'] ?> (<?= $truck['truck_type'] ?>)</option>
                <?php endwhile; ?>
              </select>
            </div>
            <input type="hidden" name="start_time" value="06:00">
            <input type="hidden" name="end_time" value="18:00">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Book Now</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="card shadow mb-4">
    <div class="card-header py-3">
      <h6 class="m-0 font-weight-bold text-primary">Your Bookings</h6>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover" id="bookingsTable" width="100%" cellspacing="0">
          <thead>
            <tr>
              <th>Schedule</th>
              <th>Pick-Up</th>
              <th>Destination</th>
              <th>Distance</th>
              <th>Truck</th>
              <th>Delivery Status</th>
              <th>Payment Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $bookingsResult->fetch_assoc()): ?>
              <tr>
                <td>
                  <?= date('M j, Y', strtotime($row['start_time'])) ?><br>
                  <?= date('h:i A', strtotime($row['start_time'])) ?>–<?= date('h:i A', strtotime($row['end_time'])) ?>
                </td>
                <td><?= htmlspecialchars($row['pick_up']) ?></td>
                <td><?= htmlspecialchars($row['destination']) ?></td>
                <td><?= htmlspecialchars($row['distance_km']) ?> km</td>
                <td><?= htmlspecialchars($row['truck_no']) ?></td>
                <td>
                  <span class="badge <?php
                                      $status = $row['delivery_status'] ?? 'Pending';
                                      echo ($status == 'Received') ? 'bg-success' : (($status == 'Delivered') ? 'bg-info' : (($status == 'In Transit') ? 'bg-primary' : 'bg-warning text-dark'));
                                      ?>">
                    <?= htmlspecialchars($status) ?>
                  </span>
                </td>
                <td>
                  <span class="badge <?= ($row['payment_status'] ?? 'Pending') == 'Paid' ? 'bg-success' : 'bg-warning text-dark' ?>">
                    <?= htmlspecialchars($row['payment_status'] ?? 'Pending') ?>
                  </span>
                </td>
                <td>
                  <div class="d-flex gap-2 flex-wrap">
                    <?php if ($row['payment_status'] === 'Paid'): ?>
                      <span class="text-success">Paid (₱<?= number_format($row['total_amount'], 2) ?>)</span>
                    <?php else: ?>
                      <form method="POST" action="booking/pay_now.php" class="mb-0">
                        <input type="hidden" name="schedule_id" value="<?= $row['schedule_id'] ?>">
                        <input type="hidden" name="amount" value="<?= $row['total_amount'] ?>">
                        <button type="submit" class="btn btn-sm btn-success">
                          <i class="bi bi-credit-card"></i> Pay ₱<?= number_format($row['total_amount'], 2) ?>
                        </button>
                      </form>
                    <?php endif; ?>

                    <?php if (new DateTime($row['start_time']) > new DateTime()): ?>
                      <form method="POST" action="booking/cancel_booking.php" class="mb-0" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                        <input type="hidden" name="schedule_id" value="<?= $row['schedule_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">
                          <i class="bi bi-x-circle"></i> Cancel
                        </button>
                      </form>
                    <?php else: ?>
                      <button class="btn btn-sm btn-secondary" disabled>
                        <i class="bi bi-x-circle"></i> Cannot Cancel
                      </button>
                    <?php endif; ?>

                    <?php if ($row['delivery_status'] === 'Delivered'): ?>
                      <button class="btn btn-sm btn-primary confirm-delivery"
                        data-schedule-id="<?= $row['schedule_id'] ?>">
                        <i class="bi bi-check-circle"></i> Confirm Receipt
                      </button>
                    <?php elseif ($row['delivery_status'] === 'Received'): ?>
                      <span class="text-success"><i class="bi bi-check2-circle"></i> Received</span>
                    <?php endif; ?>
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

<script>
  $(document).ready(function() {
    // Initialize DataTable
    $('#bookingsTable').DataTable({
      "order": [
        [0, "desc"]
      ],
      "responsive": true,
      "dom": '<"top"f>rt<"bottom"lip><"clear">',
      "language": {
        "search": "_INPUT_",
        "searchPlaceholder": "Search bookings...",
        "lengthMenu": "Show _MENU_ entries",
        "info": "Showing _START_ to _END_ of _TOTAL_ bookings",
        "paginate": {
          "previous": "<i class='bi bi-chevron-left'></i>",
          "next": "<i class='bi bi-chevron-right'></i>"
        }
      }
    });

    // Set minimum date for booking
    $('input[name="booking_date"]').attr('min', new Date().toISOString().split('T')[0]);

    // Handle delivery confirmation
    $('.confirm-delivery').click(function() {
      var scheduleId = $(this).data('schedule-id');
      $('#confirm_schedule_id').val(scheduleId);
      $('#confirmationModal').modal('show');
    });
  });
</script>

<?php
$content = ob_get_clean();
include "../layout/client_layout.php";
?>