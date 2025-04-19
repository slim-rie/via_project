<?php
$title = "My Payroll";
$activePage = "payroll";
ob_start();
require '../dbcon.php';

// Start session and check if user is logged in as helper
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'helper') {
    header("Location: ../login.php");
    exit();
}

// Get helper ID from session
$helper_id = $_SESSION['user_id'];

// Query to get helper details
$helper_query = $con->prepare("SELECT h.helper_id, h.full_name 
                              FROM helpers h 
                              JOIN users u ON h.user_id = u.user_id 
                              WHERE u.user_id = ?");
$helper_query->bind_param("i", $helper_id);
$helper_query->execute();
$helper_result = $helper_query->get_result();

if ($helper_result->num_rows === 0) {
    die("Helper not found");
}

$helper = $helper_result->fetch_assoc();
$helper_id = $helper['helper_id'];

// Handle month/year filter if submitted
$filter_month = date('m');
$filter_year = date('Y');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['month'], $_POST['year'])) {
    $filter_month = $_POST['month'];
    $filter_year = $_POST['year'];
}

// Calculate date range for the selected month/year
$start_date = date('Y-m-01', strtotime("$filter_year-$filter_month-01"));
$end_date = date('Y-m-t', strtotime("$filter_year-$filter_month-01"));

// Get payroll data for this helper
$payroll_query = $con->prepare("
    SELECT * FROM helper_payroll 
    WHERE helper_id = ? 
    AND pay_period_start = ? 
    AND pay_period_end = ?
    ORDER BY date_generated DESC
");
$payroll_query->bind_param("iss", $helper_id, $start_date, $end_date);
$payroll_query->execute();
$payroll_result = $payroll_query->get_result();

// Get all payroll periods for this helper (for history)
$history_query = $con->prepare("
    SELECT DISTINCT pay_period_start, pay_period_end 
    FROM helper_payroll 
    WHERE helper_id = ?
    ORDER BY pay_period_start DESC
");
$history_query->bind_param("i", $helper_id);
$history_query->execute();
$history_result = $history_query->get_result();
?>

<div class="container-fluid">
    <h1 class="mb-4">My Payroll</h1>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h5 class="m-0 font-weight-bold text-primary">Payroll Details</h5>
        </div>
        <div class="card-body">
            <form method="POST" class="mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="monthSelect">Month:</label>
                            <select class="form-control" id="monthSelect" name="month" required>
                                <?php
                                $months = [
                                    '01' => 'January', '02' => 'February', '03' => 'March',
                                    '04' => 'April', '05' => 'May', '06' => 'June',
                                    '07' => 'July', '08' => 'August', '09' => 'September',
                                    '10' => 'October', '11' => 'November', '12' => 'December'
                                ];
                                foreach ($months as $num => $name) {
                                    $selected = ($num == $filter_month) ? ' selected' : '';
                                    echo "<option value='$num'$selected>$name</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="yearSelect">Year:</label>
                            <select class="form-control" id="yearSelect" name="year" required>
                                <?php
                                $currentYear = date('Y');
                                for ($year = $currentYear - 1; $year <= $currentYear + 1; $year++) {
                                    $selected = ($year == $filter_year) ? ' selected' : '';
                                    echo "<option value='$year'$selected>$year</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-2">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </form>

            <?php if ($payroll_result->num_rows > 0): ?>
                <?php while ($payroll = $payroll_result->fetch_assoc()): ?>
                    <div class="payroll-details mb-4">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h5>Pay Period: 
                                    <?= date('F j, Y', strtotime($payroll['pay_period_start'])) ?> - 
                                    <?= date('F j, Y', strtotime($payroll['pay_period_end'])) ?>
                                </h5>
                                <p>Generated on: <?= date('F j, Y H:i', strtotime($payroll['date_generated'])) ?></p>
                                <p>Status: 
                                    <span class="badge <?= $payroll['payment_status'] === 'Paid' ? 'bg-success' : 'bg-warning' ?>">
                                        <?= $payroll['payment_status'] ?>
                                    </span>
                                </p>
                                <?php if ($payroll['payment_status'] === 'Paid' && $payroll['payment_date']): ?>
                                    <p>Paid on: <?= date('F j, Y', strtotime($payroll['payment_date'])) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 text-end">
                                <h3>Net Pay: ₱<?= number_format($payroll['net_pay'], 2) ?></h3>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">Earnings</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Base Salary:</span>
                                            <span>₱<?= number_format($payroll['base_salary'], 2) ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Delivery Bonuses (<?= $payroll['total_deliveries'] ?> deliveries):</span>
                                            <span>₱<?= number_format($payroll['bonuses'], 2) ?></span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between fw-bold">
                                            <span>Total Earnings:</span>
                                            <span>₱<?= number_format($payroll['base_salary'] + $payroll['bonuses'], 2) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0">Deductions</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>SSS:</span>
                                            <span>₱<?= number_format($payroll['sss_deduction'], 2) ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>PhilHealth:</span>
                                            <span>₱<?= number_format($payroll['philhealth_deduction'], 2) ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Pag-IBIG:</span>
                                            <span>₱<?= number_format($payroll['pagibig_deduction'], 2) ?></span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between fw-bold">
                                            <span>Total Deductions:</span>
                                            <span>₱<?= number_format($payroll['deductions'], 2) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    No payroll records found for <?= date('F Y', strtotime($start_date)) ?>.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header py-3">
            <h5 class="m-0 font-weight-bold text-primary">Payroll History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Pay Period</th>
                            <th>Deliveries</th>
                            <th>Earnings</th>
                            <th>Deductions</th>
                            <th>Net Pay</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($history = $history_result->fetch_assoc()): 
                            // Get summary for each period
                            $summary_query = $con->prepare("
                                SELECT 
                                    SUM(total_deliveries) as deliveries,
                                    SUM(base_salary + bonuses) as earnings,
                                    SUM(deductions) as deductions,
                                    SUM(net_pay) as net_pay,
                                    GROUP_CONCAT(DISTINCT payment_status) as statuses
                                FROM helper_payroll
                                WHERE helper_id = ? 
                                AND pay_period_start = ? 
                                AND pay_period_end = ?
                            ");
                            $summary_query->bind_param("iss", $helper_id, $history['pay_period_start'], $history['pay_period_end']);
                            $summary_query->execute();
                            $summary = $summary_query->get_result()->fetch_assoc();
                            
                            // Determine status (if all are Paid, show Paid, else Pending)
                            $status = (strpos($summary['statuses'], 'Pending') !== false) ? 'Pending' : 'Paid';
                        ?>
                            <tr>
                                <td><?= date('M Y', strtotime($history['pay_period_start'])) ?></td>
                                <td><?= $summary['deliveries'] ?></td>
                                <td>₱<?= number_format($summary['earnings'], 2) ?></td>
                                <td>₱<?= number_format($summary['deductions'], 2) ?></td>
                                <td>₱<?= number_format($summary['net_pay'], 2) ?></td>
                                <td>
                                    <span class="badge <?= $status === 'Paid' ? 'bg-success' : 'bg-warning' ?>">
                                        <?= $status ?>
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

<?php
$content = ob_get_clean();
include "../layout/helper_layout.php";
?>