<?php
// payroll_report.php
require '../dbcon.php';

$title = "Payroll Reports";
$activePage = "payroll";
ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Payroll Reports</h1>
        <div>
            <button class="btn btn-secondary" onclick="printReport()">
                <i class="bi bi-printer"></i> Print Report
            </button>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Monthly Payroll Summary</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="reportTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Month</th>
                            <th>Drivers</th>
                            <th>Total Base</th>
                            <th>Total Bonuses</th>
                            <th>Total Deductions</th>
                            <th>Total Net Pay</th>
                            <th>Avg. Per Driver</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT 
                                    DATE_FORMAT(pay_period_end, '%Y-%m') AS month,
                                    COUNT(*) AS driver_count,
                                    SUM(base_salary) AS total_base,
                                    SUM(bonuses) AS total_bonuses,
                                    SUM(deductions) AS total_deductions,
                                    SUM(net_pay) AS total_net
                                FROM payroll
                                GROUP BY DATE_FORMAT(pay_period_end, '%Y-%m')
                                ORDER BY month DESC";
                        $result = mysqli_query($con, $sql);

                        while($row = mysqli_fetch_assoc($result)):
                        ?>
                        <tr>
                            <td><?= date('F Y', strtotime($row['month'].'-01')) ?></td>
                            <td><?= $row['driver_count'] ?></td>
                            <td>₱<?= number_format($row['total_base'], 2) ?></td>
                            <td>₱<?= number_format($row['total_bonuses'], 2) ?></td>
                            <td>₱<?= number_format($row['total_deductions'], 2) ?></td>
                            <td>₱<?= number_format($row['total_net'], 2) ?></td>
                            <td>₱<?= number_format($row['total_net']/$row['driver_count'], 2) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Driver Earnings Comparison</h6>
                </div>
                <div class="card-body">
                    <canvas id="driverChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Monthly Trends</h6>
                </div>
                <div class="card-body">
                    <canvas id="monthlyChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function printReport() {
        window.print();
    }

    // Driver Earnings Chart
    const driverCtx = document.getElementById('driverChart').getContext('2d');
    new Chart(driverCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php
                $sql = "SELECT d.full_name, SUM(p.net_pay) as total_earnings
                        FROM payroll p
                        JOIN drivers d ON p.driver_id = d.driver_id
                        GROUP BY d.driver_id
                        ORDER BY total_earnings DESC
                        LIMIT 5";
                $result = mysqli_query($con, $sql);
                while($row = mysqli_fetch_assoc($result)) {
                    echo "'".addslashes($row['full_name'])."',";
                }
                ?>
            ],
            datasets: [{
                label: 'Total Earnings',
                data: [
                    <?php
                    mysqli_data_seek($result, 0);
                    while($row = mysqli_fetch_assoc($result)) {
                        echo $row['total_earnings'].",";
                    }
                    ?>
                ],
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Monthly Trends Chart
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: [
                <?php
                $sql = "SELECT DATE_FORMAT(pay_period_end, '%Y-%m') AS month
                        FROM payroll
                        GROUP BY month
                        ORDER BY month DESC
                        LIMIT 6";
                $result = mysqli_query($con, $sql);
                while($row = mysqli_fetch_assoc($result)) {
                    echo "'".date('M Y', strtotime($row['month'].'-01'))."',";
                }
                ?>
            ],
            datasets: [{
                label: 'Total Net Pay',
                data: [
                    <?php
                    $sql = "SELECT DATE_FORMAT(pay_period_end, '%Y-%m') AS month, SUM(net_pay) as total_net
                            FROM payroll
                            GROUP BY month
                            ORDER BY month DESC
                            LIMIT 6";
                    $result = mysqli_query($con, $sql);
                    while($row = mysqli_fetch_assoc($result)) {
                        echo $row['total_net'].",";
                    }
                    ?>
                ],
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                fill: true
            }]
        },
        options: {
            responsive: true
        }
    });
</script>

<?php
$content = ob_get_clean();
include "../layout/admin_layout.php";
?>