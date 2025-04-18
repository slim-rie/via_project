<?php
// payroll_report.php
require '../dbcon.php';
$title = "Payroll Reports";
$activePage = "payroll";
ob_start();
session_start();
// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== "admin") {
    header("Location: ../login.php");
    exit();
}
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
            <h6 class="m-0 font-weight-bold">Monthly Payroll Summary</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="reportTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Month</th>
                            <th>Drivers</th>
                            <th>Total Base</th>
                            <th>Total Commission</th>
                            <th>Total Deliveries</th>
                            <th>Total Revenue</th>
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
                                    SUM(bonuses) AS total_commission,
                                    SUM(total_deliveries) AS total_deliveries,
                                    SUM(delivery_revenue) AS total_revenue,
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
                            <td>₱<?= number_format($row['total_commission'], 2) ?></td>
                            <td><?= $row['total_deliveries'] ?></td>
                            <td>₱<?= number_format($row['total_revenue'], 2) ?></td>
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
                    <h6 class="m-0 font-weight-bold">Top Performing Drivers (Commission)</h6>
                </div>
                <div class="card-body">
                    <canvas id="driverChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">Revenue vs Commission Trends</h6>
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

    // Top Performing Drivers Chart
    const driverCtx = document.getElementById('driverChart').getContext('2d');
    new Chart(driverCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php
                $sql = "SELECT d.full_name, SUM(p.bonuses) as total_commission
                        FROM payroll p
                        JOIN drivers d ON p.driver_id = d.driver_id
                        GROUP BY d.driver_id
                        ORDER BY total_commission DESC
                        LIMIT 5";
                $result = mysqli_query($con, $sql);
                while($row = mysqli_fetch_assoc($result)) {
                    echo "'".addslashes($row['full_name'])."',";
                }
                ?>
            ],
            datasets: [{
                label: 'Total Commission Earned',
                data: [
                    <?php
                    mysqli_data_seek($result, 0);
                    while($row = mysqli_fetch_assoc($result)) {
                        echo $row['total_commission'].",";
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
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Commission (₱)'
                    }
                }
            }
        }
    });

    // Revenue vs Commission Trends Chart
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
            datasets: [
                {
                    label: 'Total Revenue',
                    data: [
                        <?php
                        $sql = "SELECT DATE_FORMAT(pay_period_end, '%Y-%m') AS month, SUM(delivery_revenue) as total_revenue
                                FROM payroll
                                GROUP BY month
                                ORDER BY month DESC
                                LIMIT 6";
                        $result = mysqli_query($con, $sql);
                        while($row = mysqli_fetch_assoc($result)) {
                            echo $row['total_revenue'].",";
                        }
                        ?>
                    ],
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    yAxisID: 'y'
                },
                {
                    label: 'Total Commission',
                    data: [
                        <?php
                        $sql = "SELECT DATE_FORMAT(pay_period_end, '%Y-%m') AS month, SUM(bonuses) as total_commission
                                FROM payroll
                                GROUP BY month
                                ORDER BY month DESC
                                LIMIT 6";
                        $result = mysqli_query($con, $sql);
                        while($row = mysqli_fetch_assoc($result)) {
                            echo $row['total_commission'].",";
                        }
                        ?>
                    ],
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 2,
                    yAxisID: 'y'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Amount (₱)'
                    }
                }
            }
        }
    });
</script>

<?php
$content = ob_get_clean();
include "../layout/admin_layout.php";
?>