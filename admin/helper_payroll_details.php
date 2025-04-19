<?php
require '../dbcon.php';
$title = "Helper Payroll Details";
$activePage = "payroll";
ob_start();
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== "admin") {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: helper_payroll_view.php");
    exit();
}

$payroll_id = $_GET['id'];

// Get payroll details
$payroll_sql = "SELECT p.*, h.full_name, h.contact_no
                FROM helper_payroll p
                JOIN helpers h ON p.helper_id = h.helper_id
                WHERE p.payroll_id = ?";
$stmt = $con->prepare($payroll_sql);
$stmt->bind_param("i", $payroll_id);
$stmt->execute();
$payroll_result = $stmt->get_result();

if ($payroll_result->num_rows === 0) {
    header("Location: helper_payroll_view.php");
    exit();
}

$payroll = $payroll_result->fetch_assoc();
$stmt->close();

// Get deliveries for this payroll period
$deliveries_sql = "SELECT s.schedule_id, s.start_time, s.end_time, s.destination, 
                          c.full_name as customer_name, py.total_amount
                   FROM schedules s
                   JOIN deliveries d ON d.schedule_id = s.schedule_id
                   JOIN customers c ON s.customer_id = c.customer_id
                   JOIN payments py ON py.schedule_id = s.schedule_id
                   WHERE s.helper_id = ? 
                   AND d.delivery_status = 'Completed'
                   AND d.delivery_datetime BETWEEN ? AND ?";
$stmt = $con->prepare($deliveries_sql);
$stmt->bind_param("iss", $payroll['helper_id'], $payroll['pay_period_start'], $payroll['pay_period_end']);
$stmt->execute();
$deliveries = $stmt->get_result();
$stmt->close();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Helper Payroll Details</h1>
        <div>
            <a href="payroll_view.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Payroll
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">Payroll Information</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4 font-weight-bold">Helper Name:</div>
                        <div class="col-sm-8"><?= htmlspecialchars($payroll['full_name']) ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 font-weight-bold">Contact Number:</div>
                        <div class="col-sm-8"><?= htmlspecialchars($payroll['contact_no']) ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 font-weight-bold">Pay Period:</div>
                        <div class="col-sm-8">
                            <?= date('F j, Y', strtotime($payroll['pay_period_start'])) ?> to 
                            <?= date('F j, Y', strtotime($payroll['pay_period_end'])) ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 font-weight-bold">Status:</div>
                        <div class="col-sm-8">
                            <span class="badge <?= $payroll['payment_status'] == 'Paid' ? 'bg-success' : 'bg-warning' ?>">
                                <?= $payroll['payment_status'] ?>
                            </span>
                            <?php if($payroll['payment_status'] == 'Paid'): ?>
                                <small class="text-muted">(<?= date('M j, Y', strtotime($payroll['payment_date'])) ?>)</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">Payroll Summary</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-6 font-weight-bold">Base Salary:</div>
                        <div class="col-sm-6 text-end">₱<?= number_format($payroll['base_salary'], 2) ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-6 font-weight-bold">Commission (10%):</div>
                        <div class="col-sm-6 text-end">₱<?= number_format($payroll['bonuses'], 2) ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-6 font-weight-bold">SSS Deduction:</div>
                        <div class="col-sm-6 text-end">₱<?= number_format($payroll['sss_deduction'], 2) ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-6 font-weight-bold">PhilHealth Deduction:</div>
                        <div class="col-sm-6 text-end">₱<?= number_format($payroll['philhealth_deduction'], 2) ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-6 font-weight-bold">Pag-IBIG Deduction:</div>
                        <div class="col-sm-6 text-end">₱<?= number_format($payroll['pagibig_deduction'], 2) ?></div>
                    </div>
                    <hr>
                    <div class="row mb-2">
                        <div class="col-sm-6 font-weight-bold">Total Deductions:</div>
                        <div class="col-sm-6 text-end">₱<?= number_format($payroll['deductions'], 2) ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-6 font-weight-bold">Net Pay:</div>
                        <div class="col-sm-6 text-end font-weight-bold">₱<?= number_format($payroll['net_pay'], 2) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold">Completed Deliveries (<?= $deliveries->num_rows ?>)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Schedule Date</th>
                            <th>Destination</th>
                            <th>Customer</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($delivery = $deliveries->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('M j, Y', strtotime($delivery['start_time'])) ?></td>
                            <td><?= htmlspecialchars($delivery['destination']) ?></td>
                            <td><?= htmlspecialchars($delivery['customer_name']) ?></td>
                            <td class="text-end">₱<?= number_format($delivery['total_amount'], 2) ?></td>
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
include "../layout/admin_layout.php";
?>