<?php
require '../dbcon.php';
$title = "Dashboard";
$activePage = "dashboard";
ob_start();
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== "admin") {
    header("Location: ../login.php");
    exit();
}
// Get all statistics in one query set for efficiency
$stats = [];

// Daily Revenue (today)
$result = mysqli_query($con, "SELECT SUM(total_amount) AS daily_revenue 
                            FROM payments 
                            WHERE DATE(date) = CURDATE()");
$stats['daily_revenue'] = mysqli_fetch_assoc($result)['daily_revenue'] ?? 0;

// Monthly Revenue (current month)
$result = mysqli_query($con, "SELECT SUM(total_amount) AS monthly_revenue 
                            FROM payments 
                            WHERE MONTH(date) = MONTH(CURRENT_DATE()) 
                            AND YEAR(date) = YEAR(CURRENT_DATE())");
$stats['monthly_revenue'] = mysqli_fetch_assoc($result)['monthly_revenue'] ?? 0;

// Yearly Revenue (current year)
$result = mysqli_query($con, "SELECT SUM(total_amount) AS yearly_revenue 
                            FROM payments 
                            WHERE YEAR(date) = YEAR(CURRENT_DATE())");
$stats['yearly_revenue'] = mysqli_fetch_assoc($result)['yearly_revenue'] ?? 0;

// Basic counts
$result = mysqli_query($con, "SELECT COUNT(*) AS total FROM customers");
$stats['total_customers'] = mysqli_fetch_assoc($result)['total'];

$result = mysqli_query($con, "SELECT COUNT(*) AS total FROM drivers");
$stats['total_drivers'] = mysqli_fetch_assoc($result)['total'];

$result = mysqli_query($con, "SELECT COUNT(*) AS total FROM trucks");
$stats['total_trucks'] = mysqli_fetch_assoc($result)['total'];

// Booking statistics - using deliveries table for status
$result = mysqli_query($con, "SELECT 
    COUNT(*) AS total_bookings,
    SUM(CASE WHEN d.delivery_status = 'Received' THEN 1 ELSE 0 END) AS completed_bookings,
    SUM(CASE WHEN d.delivery_status = 'Pending' THEN 1 ELSE 0 END) AS pending_bookings,
    SUM(CASE WHEN d.delivery_status = 'In Transit' THEN 1 ELSE 0 END) AS in_progress_bookings
    FROM schedules s
    JOIN deliveries d ON s.schedule_id = d.schedule_id");
$booking_stats = mysqli_fetch_assoc($result);
$stats = array_merge($stats, $booking_stats);

// Financial statistics
$result = mysqli_query($con, "SELECT 
    SUM(total_amount) AS total_revenue,
    SUM(CASE WHEN status = 'Paid' THEN total_amount ELSE 0 END) AS paid_amount,
    SUM(CASE WHEN status = 'Pending' THEN total_amount ELSE 0 END) AS pending_amount
    FROM payments");
$financial_stats = mysqli_fetch_assoc($result);
$stats = array_merge($stats, $financial_stats);

// Payroll statistics
$result = mysqli_query($con, "SELECT 
    SUM(net_pay) AS total_payouts,
    SUM(CASE WHEN payment_status = 'Paid' THEN net_pay ELSE 0 END) AS paid_payouts,
    SUM(CASE WHEN payment_status = 'Pending' THEN net_pay ELSE 0 END) AS pending_payouts
    FROM payroll");
$payroll_stats = mysqli_fetch_assoc($result);
$stats = array_merge($stats, $payroll_stats);

// Recent activity
$stats['recent_bookings'] = [];
$result = mysqli_query($con, "SELECT s.*, c.full_name AS customer_name, t.truck_no, d.full_name AS driver_name, dl.delivery_status
    FROM schedules s
    JOIN customers c ON s.customer_id = c.customer_id
    JOIN trucks t ON s.truck_id = t.truck_id
    JOIN drivers d ON s.driver_id = d.driver_id
    JOIN deliveries dl ON s.schedule_id = dl.schedule_id
    ORDER BY s.start_time DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($result)) {
    $stats['recent_bookings'][] = $row;
}

// Upcoming deliveries
$stats['upcoming_deliveries'] = [];
$result = mysqli_query($con, "SELECT s.*, c.full_name AS customer_name, t.truck_no, d.full_name AS driver_name
    FROM schedules s
    JOIN customers c ON s.customer_id = c.customer_id
    JOIN trucks t ON s.truck_id = t.truck_id
    JOIN drivers d ON s.driver_id = d.driver_id
    WHERE s.start_time > NOW()
    ORDER BY s.start_time ASC LIMIT 5");
while ($row = mysqli_fetch_assoc($result)) {
    $stats['upcoming_deliveries'][] = $row;
}

// Recent payments
$stats['recent_payments'] = [];
$result = mysqli_query($con, "SELECT p.*, c.full_name AS customer_name
    FROM payments p
    JOIN schedules s ON p.schedule_id = s.schedule_id
    JOIN customers c ON s.customer_id = c.customer_id
    ORDER BY p.date DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($result)) {
    $stats['recent_payments'][] = $row;
}

// Truck status
$stats['truck_status'] = [];
$result = mysqli_query($con, "SELECT status, COUNT(*) AS count FROM trucks GROUP BY status");
while ($row = mysqli_fetch_assoc($result)) {
    $stats['truck_status'][$row['status']] = $row['count'];
}
?>


<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard Overview</h1>
        <div>
            <a href="reports.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="bi bi-download text-white-50"></i> Generate Report
            </a>
            <button class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm ml-2" id="refreshDashboard">
                <i class="bi bi-arrow-clockwise text-white-50"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Customers Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold  text-uppercase mb-1">
                                Total Customers</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_customers'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Revenue</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?= number_format($stats['total_revenue'], 2) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-cash-stack fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Drivers Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Active Drivers</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_drivers'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-person-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Payments Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pending Payments</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?= number_format($stats['pending_amount'], 2) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-hourglass-split fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Revenue Statistics Cards -->
    <div class="row">
        <!-- Daily Revenue Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Today's Revenue</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?= number_format($stats['daily_revenue'], 2) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-calendar-day fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Revenue Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Monthly Revenue (<?= date('F') ?>)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?= number_format($stats['monthly_revenue'], 2) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-calendar-month fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Yearly Revenue Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Yearly Revenue (<?= date('Y') ?>)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?= number_format($stats['yearly_revenue'], 2) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Bookings Overview -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold ">Bookings Overview</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="border-left-primary pl-3 py-2">
                                <div class="text-xs font-weight-bold  text-uppercase mb-1">Total</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_bookings'] ?></div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="border-left-success pl-3 py-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Completed</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['completed_bookings'] ?></div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="border-left-warning pl-3 py-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['pending_bookings'] ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Truck Status -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold ">Truck Status</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="border-left-success pl-3 py-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Available</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['truck_status']['Available'] ?? 0 ?></div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="border-left-primary pl-3 py-2">
                                <div class="text-xs font-weight-bold  text-uppercase mb-1">In Use</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['truck_status']['Booked'] ?? 0 ?></div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="border-left-danger pl-3 py-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Maintenance</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['truck_status']['Maintenance'] ?? 0 ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Recent Bookings -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold ">Recent Bookings</h6>
                    <a href="schedules.php" class="btn btn-sm btn-outline-light">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Truck</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['recent_bookings'] as $booking): ?>
                                    <tr>
                                        <td><?= date('M j', strtotime($booking['start_time'])) ?></td>
                                        <td><?= htmlspecialchars($booking['customer_name']) ?></td>
                                        <td><?= htmlspecialchars($booking['truck_no']) ?></td>
                                        <td>
                                            <span class="badge bg-<?=
                                                                    $booking['delivery_status'] == 'Completed' ? 'success' : ($booking['delivery_status'] == 'Pending' ? 'warning' : ($booking['delivery_status'] == 'In Transit' ? 'info' : ($booking['delivery_status'] == 'Received' ? 'success' : 'primary' ))) ?>">
                                                <?= htmlspecialchars($booking['delivery_status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Deliveries -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold ">Upcoming Deliveries</h6>
                    <a href="schedules.php" class="btn btn-sm btn-outline-light">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Driver</th>
                                    <th>Truck</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['upcoming_deliveries'] as $delivery): ?>
                                    <tr>
                                        <td><?= date('M j', strtotime($delivery['start_time'])) ?></td>
                                        <td><?= htmlspecialchars($delivery['customer_name']) ?></td>
                                        <td><?= htmlspecialchars($delivery['driver_name']) ?></td>
                                        <td><?= htmlspecialchars($delivery['truck_no']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Recent Payments -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold ">Recent Payments</h6>
                    <a href="payments.php" class="btn btn-sm btn-outline-light">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['recent_payments'] as $payment): ?>
                                    <tr>
                                        <td><?= date('M j', strtotime($payment['date'])) ?></td>
                                        <td><?= htmlspecialchars($payment['customer_name']) ?></td>
                                        <td>₱<?= number_format($payment['total_amount'], 2) ?></td>
                                        <td><span class="badge bg-<?= $payment['status'] == 'Paid' ? 'success' : 'warning' ?>">
                                                <?= $payment['status'] ?>
                                            </span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employee Overview Card -->
        <div class="col-lg-6 mb-4">
    <div class="card shadow">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold">Employee Overview</h6>
            <a href="employees.php" class="btn btn-sm btn-outline-light">View All</a>
        </div>
        <div class="card-body">
            <div class="row text-center mb-3">
                <!-- Total Drivers -->
                <div class="col-md-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Drivers</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_drivers'] ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-people fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Drivers -->
                <div class="col-md-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Active Drivers</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= $stats['truck_status']['In Use'] ?? 0 ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-person-check fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Payroll -->
                <div class="col-md-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Pending Payroll</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= $payroll_stats['pending_payroll_count'] ?? 0 ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-cash-stack fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                    <h6 class="mb-3">Driver Assignments</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Driver</th>
                                    <th>Assigned Truck</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Get driver assignments
                                $driverQuery = "SELECT d.driver_id, d.full_name, t.truck_no, t.status 
                                          FROM drivers d
                                          LEFT JOIN trucks t ON d.driver_id = t.driver_id
                                          ORDER BY d.driver_id DESC LIMIT 3";
                                $driverResult = mysqli_query($con, $driverQuery);

                                while ($driver = mysqli_fetch_assoc($driverResult)):
                                    $truckNo = $driver['truck_no'] ? $driver['truck_no'] : 'Not assigned';
                                    $statusClass = $driver['status'] == 'In Use' ? 'success' : ($driver['status'] == 'Available' ? 'primary' : 'warning');
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($driver['full_name']) ?></td>
                                        <td><?= htmlspecialchars($truckNo) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $statusClass ?>">
                                                <?= $driver['status'] ?? 'Available' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between" style="background-color: #364C84; color: white;">
                    <h6 class="m-0 font-weight-bold">Delivery Calendar</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle text-white" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="bi bi-three-dots-vertical"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                            <a class="dropdown-item calendar-view-change" data-view="dayGridMonth" href="#"><i class="bi bi-calendar-month mr-2"></i>Month View</a>
                            <a class="dropdown-item calendar-view-change" data-view="timeGridWeek" href="#"><i class="bi bi-calendar-week mr-2"></i>Week View</a>
                            <a class="dropdown-item calendar-view-change" data-view="timeGridDay" href="#"><i class="bi bi-calendar-day mr-2"></i>Day View</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="schedules.php"><i class="bi bi-plus-circle mr-2"></i>Add New Schedule</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="calendar" style="min-height: 500px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Calendar Scripts -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js'></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
            initialView: 'dayGridMonth',
            height: 400,
            events: 'load_events_admin.php',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek'
            }
        });
        calendar.render();

        // Refresh button functionality
        document.getElementById('refreshDashboard').addEventListener('click', function() {
            location.reload();
        });
    });
</script>

<?php
$content = ob_get_clean();
include "../layout/admin_layout.php";
?>