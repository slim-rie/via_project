<?php
require '../dbcon.php';
$title = "Payroll Management";
$activePage = "payroll";
ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Payroll Management</h1>
        <div>
            <a href="generate_payroll.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Generate Payroll
            </a>
            <a href="payroll_report.php" class="btn btn-secondary">
                <i class="bi bi-file-earmark-text"></i> View Reports
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold">Payroll Records</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="payrollTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th>Driver</th>
                            <th>Pay Period</th>
                            <th>Base Salary</th>
                            <th>Deliveries</th>
                            <th>Bonuses</th>
                            <th>Deductions</th>
                            <th>Net Pay</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT p.*, d.full_name 
                                FROM payroll p
                                JOIN drivers d ON p.driver_id = d.driver_id
                                ORDER BY p.pay_period_end DESC, p.payment_status";
                        $result = mysqli_query($con, $sql);

                        while($row = mysqli_fetch_assoc($result)):
                            // Calculate deliveries for this payroll period
                            $deliveries_sql = "SELECT COUNT(*) AS delivery_count 
                                              FROM deliveries d
                                              JOIN schedules s ON d.schedule_id = s.schedule_id
                                              WHERE s.driver_id = {$row['driver_id']}
                                              AND d.delivery_status = 'Completed'
                                              AND d.delivery_datetime BETWEEN '{$row['pay_period_start']}' AND '{$row['pay_period_end']}'";
                            $deliveries_result = mysqli_query($con, $deliveries_sql);
                            $deliveries_data = mysqli_fetch_assoc($deliveries_result);
                            $delivery_count = $deliveries_data['delivery_count'] ?? 0;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td>
                                <?= date('M j, Y', strtotime($row['pay_period_start'])) ?><br>
                                <small>to <?= date('M j, Y', strtotime($row['pay_period_end'])) ?></small>
                            </td>
                            <td class="text-end">₱<?= number_format($row['base_salary'], 2) ?></td>
                            <td class="text-center"><?= $delivery_count ?></td>
                            <td class="text-end">₱<?= number_format($row['bonuses'], 2) ?></td>
                            <td class="text-end">₱<?= number_format($row['deductions'], 2) ?></td>
                            <td class="text-end font-weight-bold">₱<?= number_format($row['net_pay'], 2) ?></td>
                            <td>
                                <span class="badge <?= $row['payment_status'] == 'Paid' ? 'bg-success' : 'bg-warning' ?>">
                                    <?= $row['payment_status'] ?>
                                    <?php if($row['payment_status'] == 'Paid'): ?>
                                        <br><small><?= date('M j, Y', strtotime($row['payment_date'])) ?></small>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <?php if($row['payment_status'] == 'Pending'): ?>
                                        <a href="payroll/mark_paid.php?id=<?= $row['payroll_id'] ?>" 
                                           class="btn btn-sm btn-success" 
                                           title="Mark as Paid">
                                            <i class="bi bi-cash-coin"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="payroll_details.php?id=<?= $row['payroll_id'] ?>" 
                                       class="btn btn-sm btn-info" 
                                       title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="payroll/print_payslip.php?id=<?= $row['payroll_id'] ?>" 
                                       class="btn btn-sm btn-primary" 
                                       title="Print Payslip" 
                                       target="_blank">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2">Totals</th>
                            <th class="text-end">₱<?= 
                                number_format(array_sum(array_column(mysqli_fetch_all($result, MYSQLI_ASSOC), 'base_salary')), 2) 
                            ?></th>
                            <th class="text-center"><?= 
                                array_sum(array_column(mysqli_fetch_all($result, MYSQLI_ASSOC), 'total_deliveries'))
                            ?></th>
                            <th class="text-end">₱<?= 
                                number_format(array_sum(array_column(mysqli_fetch_all($result, MYSQLI_ASSOC), 'bonuses')), 2) 
                            ?></th>
                            <th class="text-end">₱<?= 
                                number_format(array_sum(array_column(mysqli_fetch_all($result, MYSQLI_ASSOC), 'deductions')), 2) 
                            ?></th>
                            <th class="text-end">₱<?= 
                                number_format(array_sum(array_column(mysqli_fetch_all($result, MYSQLI_ASSOC), 'net_pay')), 2) 
                            ?></th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#payrollTable').DataTable({
            "order": [[1, "desc"]],
            "responsive": true,
            "dom": '<"top"f>rt<"bottom"lip><"clear">',
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search payroll...",
                "lengthMenu": "Show _MENU_ entries",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "paginate": {
                    "previous": "<i class='bi bi-chevron-left'></i>",
                    "next": "<i class='bi bi-chevron-right'></i>"
                }
            }
        });
    });
</script>

<?php
$content = ob_get_clean();
include "../layout/admin_layout.php";
?>