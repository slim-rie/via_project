<?php
// payroll_details.php
require '../dbcon.php';
$title = "Payroll Details";
$activePage = "payroll";
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== "admin") {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: payroll_view.php");
    exit();
}

$payroll_id = (int)$_GET['id'];
$sql = "SELECT p.*, d.full_name, d.contact_no, d.email 
        FROM payroll p
        JOIN drivers d ON p.driver_id = d.driver_id
        WHERE p.payroll_id = ?";
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "i", $payroll_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$payroll = mysqli_fetch_assoc($result);

if (!$payroll) {
    header("Location: payroll_view.php");
    exit();
}

// Fetch distance and delivery count
$distance_sql = "SELECT COUNT(*) AS delivery_count, COALESCE(SUM(s.distance_km), 0) AS total_distance
                 FROM deliveries d
                 JOIN schedules s ON d.schedule_id = s.schedule_id
                 WHERE s.driver_id = ?
                 AND d.delivery_status = 'Completed'
                 AND d.delivery_datetime BETWEEN ? AND ?";
$distance_stmt = mysqli_prepare($con, $distance_sql);
mysqli_stmt_bind_param($distance_stmt, "iss", $payroll['driver_id'], $payroll['pay_period_start'], $payroll['pay_period_end']);
mysqli_stmt_execute($distance_stmt);
$distance_result = mysqli_stmt_get_result($distance_stmt);
$distance_data = mysqli_fetch_assoc($distance_result);
$total_distance = $distance_data['total_distance'];
$delivery_count = $distance_data['delivery_count'];

ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Payroll Details</h1>
        <a href="payroll_view.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Payroll
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold">
                <?= htmlspecialchars($payroll['full_name']) ?> - Payroll #<?= $payroll['payroll_id'] ?>
            </h6>
            <span class="badge <?= $payroll['payment_status'] == 'Paid' ? 'bg-success' : 'bg-warning' ?>">
                <?= $payroll['payment_status'] ?>
                <?php if ($payroll['payment_status'] == 'Paid'): ?>
                    (<?= date('M j, Y', strtotime($payroll['payment_date'])) ?>)
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Pay Period</h5>
                    <p><?= date('F j, Y', strtotime($payroll['pay_period_start'])) ?> to <?= date('F j, Y', strtotime($payroll['pay_period_end'])) ?></p>
                </div>
                <div class="col-md-6">
                    <h5>Generated On</h5>
                    <p><?= date('F j, Y, h:i A', strtotime($payroll['date_generated'])) ?></p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header text-white">
                            <h6 class="m-0 font-weight-bold">Earnings</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <td>Base Salary:</td>
                                    <td class="text-end">₱<?= number_format($payroll['base_salary'], 2) ?></td>
                                </tr>
                                <tr>
                                    <td>Distance Earnings (Bonuses):</td>
                                    <td class="text-end">₱<?= number_format($payroll['bonuses'], 2) ?></td>
                                </tr>
                                <tr>
                                    <td>Total Distance:</td>
                                    <td class="text-end"><?= number_format($total_distance, 2) ?> km</td>
                                </tr>
                                <tr>
                                    <td>Total Deliveries:</td>
                                    <td class="text-end"><?= $delivery_count ?></td>
                                </tr>
                                <tr class="table-active">
                                    <th>Total Earnings:</th>
                                    <th class="text-end">₱<?= number_format($payroll['base_salary'] + $payroll['bonuses'], 2) ?></th>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header text-white">
                            <h6 class="m-0 font-weight-bold">Deductions Breakdown</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <td>SSS Contribution:</td>
                                    <td class="text-end">₱<?= number_format($payroll['sss_deduction'], 2) ?></td>
                                </tr>
                                <tr>
                                    <td>PhilHealth Contribution:</td>
                                    <td class="text-end">₱<?= number_format($payroll['philhealth_deduction'], 2) ?></td>
                                </tr>
                                <tr>
                                    <td>Pag-IBIG Fund:</td>
                                    <td class="text-end">₱<?= number_format($payroll['pagibig_deduction'], 2) ?></td>
                                </tr>
                                <tr>
                                    <td>Withholding Tax:</td>
                                    <td class="text-end">₱<?= number_format($payroll['tax_deduction'], 2) ?></td>
                                </tr>
                                <tr>
                                    <td>Truck Maintenance (₱500/delivery):</td>
                                    <td class="text-end">₱<?= number_format($payroll['truck_maintenance'], 2) ?></td>
                                </tr>
                                <tr class="table-active">
                                    <th>Total Deductions:</th>
                                    <th class="text-end">₱<?= number_format($payroll['deductions'], 2) ?></th>
                                </tr>
                            </table>

                            <div class="mt-4">
                                <h5>Deductions Summary</h5>
                                <div class="progress" style="height: 30px;">
                                    <?php
                                    $total = $payroll['deductions'];
                                    $items = [
                                        ['sss_deduction', 'SSS', 'bg-info'],
                                        ['philhealth_deduction', 'PhilHealth', 'bg-primary'],
                                        ['pagibig_deduction', 'Pag-IBIG', 'bg-warning'],
                                        ['tax_deduction', 'Tax', 'bg-danger'],
                                        ['truck_maintenance', 'Truck', 'bg-secondary']
                                    ];

                                    foreach ($items as $item) {
                                        if ($payroll[$item[0]] > 0) {
                                            $width = ($payroll[$item[0]] / $total) * 100;
                                            echo '<div class="progress-bar ' . $item[2] . '" role="progressbar" style="width: ' . $width . '%" 
                    data-bs-toggle="tooltip" data-bs-placement="top" 
                    title="' . $item[1] . ': ₱' . number_format($payroll[$item[0]], 2) . '"></div>';
                                        }
                                    }
                                    ?>
                                </div>
                                <small class="text-muted">Hover over each section to see details</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-success">
                <h4 class="alert-heading text-center">Net Pay: ₱<?= number_format($payroll['net_pay'], 2) ?></h4>
                <p class="text-center mb-0">
                    <small>Calculated as: (Base + Distance Earnings) - Deductions</small>
                </p>
            </div>
        </div>
        <div class="card-footer text-center">
            <?php if ($payroll['payment_status'] == 'Pending'): ?>
                <a href="payroll/mark_paid.php?id=<?= $payroll['payroll_id'] ?>" class="btn btn-success mr-2">
                    <i class="bi bi-cash-coin"></i> Mark as Paid
                </a>
            <?php endif; ?>
            <a href="payroll/print_payslip.php?id=<?= $payroll['payroll_id'] ?>" class="btn btn-primary" target="_blank">
                <i class="bi bi-printer"></i> Print Payslip
            </a>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>

<?php
$content = ob_get_clean();
include "../layout/admin_layout.php";
?>
