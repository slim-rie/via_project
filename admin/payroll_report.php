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

// Get current year and month for default filter
$currentYear = date('Y');
$currentMonth = date('m');
$selectedYear = $_GET['year'] ?? $currentYear;
$selectedMonth = $_GET['month'] ?? $currentMonth;

// Convert month number to name
$monthName = date('F', mktime(0, 0, 0, $selectedMonth, 10));
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Payroll Reports</h1>
        <div>
            <button class="btn btn-primary" onclick="printReport()">
                <i class="bi bi-printer"></i> Print Report
            </button>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold">Filter Options</h6>
        </div>
        <div class="card-body">
            <form method="GET" class="form-inline">
                <div class="form-group mr-3">
                    <label for="year" class="mr-2">Year:</label>
                    <select name="year" id="year" class="form-control">
                        <?php 
                        // Generate year options (current year and previous 5 years)
                        for ($i = $currentYear; $i >= $currentYear - 5; $i--) {
                            $selected = ($i == $selectedYear) ? 'selected' : '';
                            echo "<option value='$i' $selected>$i</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group mr-3">
                    <label for="month" class="mr-2">Month:</label>
                    <select name="month" id="month" class="form-control">
                        <?php 
                        // Generate month options
                        for ($i = 1; $i <= 12; $i++) {
                            $month = str_pad($i, 2, '0', STR_PAD_LEFT);
                            $monthText = date('F', mktime(0, 0, 0, $i, 10));
                            $selected = ($i == $selectedMonth) ? 'selected' : '';
                            echo "<option value='$month' $selected>$monthText</option>";
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel"></i> Filter
                </button>
            </form>
        </div>
    </div>

    <!-- Monthly Summary -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold">Payroll Summary for <?= "$monthName $selectedYear" ?></h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="reportTable">
                    <thead>
                        <tr>
                            <th>Driver</th>
                            <th>Base Salary</th>
                            <th>Commission</th>
                            <th>Deliveries</th>
                            <th>Revenue</th>
                            <th>Deductions</th>
                            <th>Net Pay</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT 
                                    p.*,
                                    d.full_name AS driver_name,
                                    p.base_salary + p.bonuses AS gross_pay
                                FROM payroll p
                                JOIN drivers d ON p.driver_id = d.driver_id
                                WHERE YEAR(p.pay_period_end) = ?
                                AND MONTH(p.pay_period_end) = ?
                                ORDER BY p.pay_period_end DESC";
                        
                        $stmt = $con->prepare($sql);
                        $stmt->bind_param("ii", $selectedYear, $selectedMonth);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['driver_name']) ?></td>
                            <td>₱<?= number_format($row['base_salary'], 2) ?></td>
                            <td>₱<?= number_format($row['bonuses'], 2) ?></td>
                            <td><?= $row['total_deliveries'] ?></td>
                            <td>₱<?= number_format($row['delivery_revenue'], 2) ?></td>
                            <td>₱<?= number_format($row['deductions'], 2) ?></td>
                            <td>₱<?= number_format($row['net_pay'], 2) ?></td>
                            <td>
                                <span class="badge <?= $row['payment_status'] === 'Paid' ? 'badge-success' : 'badge-warning' ?>">
                                    <?= $row['payment_status'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        } else {
                            echo '<tr><td colspan="8" class="text-center">No payroll records found for selected period</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
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
                    <h6 class="m-0 font-weight-bold">Monthly Trends</h6>
                </div>
                <div class="card-body">
                    <canvas id="monthlyChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">Total Payroll</h6>
                </div>
                <div class="card-body text-center">
                    <?php
                    $sql = "SELECT 
                                SUM(base_salary + bonuses) AS total_payroll,
                                SUM(deductions) AS total_deductions,
                                SUM(net_pay) AS total_net
                            FROM payroll
                            WHERE YEAR(pay_period_end) = ?
                            AND MONTH(pay_period_end) = ?";
                    
                    $stmt = $con->prepare($sql);
                    $stmt->bind_param("ii", $selectedYear, $selectedMonth);
                    $stmt->execute();
                    $sumResult = $stmt->get_result()->fetch_assoc();
                    ?>
                    <h4>₱<?= number_format($sumResult['total_payroll'] ?? 0, 2) ?></h4>
                    <p class="text-muted">Gross Payroll</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">Total Deductions</h6>
                </div>
                <div class="card-body text-center">
                    <h4>₱<?= number_format($sumResult['total_deductions'] ?? 0, 2) ?></h4>
                    <p class="text-muted">All Deductions</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">Net Pay</h6>
                </div>
                <div class="card-body text-center">
                    <h4>₱<?= number_format($sumResult['total_net'] ?? 0, 2) ?></h4>
                    <p class="text-muted">After Deductions</p>
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
                        WHERE YEAR(p.pay_period_end) = ?
                        AND MONTH(p.pay_period_end) = ?
                        GROUP BY d.driver_id
                        ORDER BY total_commission DESC
                        LIMIT 5";
                $stmt = $con->prepare($sql);
                $stmt->bind_param("ii", $selectedYear, $selectedMonth);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while($row = $result->fetch_assoc()) {
                    echo "'".addslashes($row['full_name'])."',";
                }
                ?>
            ],
            datasets: [{
                label: 'Commission Earned (₱)',
                data: [
                    <?php
                    mysqli_data_seek($result, 0);
                    while($row = $result->fetch_assoc()) {
                        echo $row['total_commission'].",";
                    }
                    ?>
                ],
                backgroundColor: '#364C84',
                borderColor: '#2F3E6E',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '₱' + context.raw.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Monthly Trends Chart
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: ['Base Salary', 'Commission', 'Deductions', 'Net Pay'],
            datasets: [{
                label: 'Amount (₱)',
                data: [
                    <?php
                    $sql = "SELECT 
                                SUM(base_salary) as total_base,
                                SUM(bonuses) as total_commission,
                                SUM(deductions) as total_deductions,
                                SUM(net_pay) as total_net
                            FROM payroll
                            WHERE YEAR(pay_period_end) = ?
                            AND MONTH(pay_period_end) = ?";
                    $stmt = $con->prepare($sql);
                    $stmt->bind_param("ii", $selectedYear, $selectedMonth);
                    $stmt->execute();
                    $sumResult = $stmt->get_result()->fetch_assoc();
                    
                    echo ($sumResult['total_base'] ?? 0) . ",";
                    echo ($sumResult['total_commission'] ?? 0) . ",";
                    echo ($sumResult['total_deductions'] ?? 0) . ",";
                    echo ($sumResult['total_net'] ?? 0);
                    ?>
                ],
                backgroundColor: 'rgba(54, 76, 132, 0.2)',
                borderColor: 'rgba(54, 76, 132, 1)',
                borderWidth: 2,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '₱' + context.raw.toLocaleString();
                        }
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