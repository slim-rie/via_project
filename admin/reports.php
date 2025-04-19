<?php
require '../dbcon.php';
$title = "Sales Report";
$activePage = "reports";
ob_start();
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== "admin") {
    header("Location: ../login.php");
    exit();
}

// Get available months/years for dropdown
$months_query = mysqli_query($con, "SELECT DISTINCT DATE_FORMAT(date, '%Y-%m') as month_year FROM payments ORDER BY month_year DESC");
$available_months = [];
while ($row = mysqli_fetch_assoc($months_query)) {
    $available_months[] = $row['month_year'];
}

// Check if overall report is requested
$report_type = $_GET['report_type'] ?? 'monthly';
$selected_month = $_GET['month'] ?? end($available_months);

// Set date ranges based on report type
if ($report_type === 'overall') {
    $start_date = '1970-01-01'; // Very early date to get all records
    $end_date = date('Y-m-t');  // End of current month
    $date_label = "Overall Sales Report (All Time)";
} else {
    $start_date = date('Y-m-01', strtotime($selected_month));
    $end_date = date('Y-m-t', strtotime($selected_month));
    $date_label = "Sales Report for " . date('F Y', strtotime($selected_month));
}

// Get sales report data
$sales_report = [];
$customer_sales = [];
$truck_sales = [];
$driver_sales = [];

// 1. Main Sales Report
$sales_query = mysqli_query($con, "SELECT 
    " . ($report_type === 'monthly' ? "DATE_FORMAT(p.date, '%Y-%m') AS month" : "'All Time' AS month") . ",
    COUNT(p.payment_id) AS total_transactions,
    SUM(p.total_amount) AS total_sales,
    AVG(p.total_amount) AS average_sale,
    MIN(p.total_amount) AS min_sale,
    MAX(p.total_amount) AS max_sale
    FROM payments p
    WHERE p.date BETWEEN '$start_date' AND '$end_date'
    " . ($report_type === 'monthly' ? "GROUP BY month" : "") . "
    ORDER BY p.date DESC") or die(mysqli_error($con));

while ($row = mysqli_fetch_assoc($sales_query)) {
    $sales_report[] = $row;
}

// 2. Sales by Customer
$customer_query = mysqli_query($con, "SELECT 
    c.full_name AS customer_name,
    COUNT(p.payment_id) AS transaction_count,
    SUM(p.total_amount) AS total_spent,
    AVG(p.total_amount) AS avg_spent
    FROM payments p
    JOIN schedules s ON p.schedule_id = s.schedule_id
    JOIN customers c ON s.customer_id = c.customer_id
    WHERE p.date BETWEEN '$start_date' AND '$end_date'
    GROUP BY c.customer_id
    ORDER BY total_spent DESC") or die(mysqli_error($con));

while ($row = mysqli_fetch_assoc($customer_query)) {
    $customer_sales[] = $row;
}

// 3. Sales by Truck
$truck_query = mysqli_query($con, "SELECT 
    t.truck_no,
    COUNT(p.payment_id) AS delivery_count,
    SUM(p.total_amount) AS revenue_generated,
    AVG(p.total_amount) AS avg_revenue_per_delivery
    FROM trucks t
    JOIN schedules s ON t.truck_id = s.truck_id
    JOIN payments p ON s.schedule_id = p.schedule_id
    WHERE p.date BETWEEN '$start_date' AND '$end_date'
    GROUP BY t.truck_id
    ORDER BY revenue_generated DESC") or die(mysqli_error($con));

while ($row = mysqli_fetch_assoc($truck_query)) {
    $truck_sales[] = $row;
}

// 4. Sales by Driver
$driver_query = mysqli_query($con, "SELECT 
    d.full_name AS driver_name,
    COUNT(p.payment_id) AS delivery_count,
    SUM(p.total_amount) AS revenue_generated,
    AVG(p.total_amount) AS avg_revenue_per_delivery
    FROM drivers d
    JOIN schedules s ON d.driver_id = s.driver_id
    JOIN payments p ON s.schedule_id = p.schedule_id
    WHERE p.date BETWEEN '$start_date' AND '$end_date'
    GROUP BY d.driver_id
    ORDER BY revenue_generated DESC") or die(mysqli_error($con));

while ($row = mysqli_fetch_assoc($driver_query)) {
    $driver_sales[] = $row;
}

// 5. Daily Sales Trend (for monthly reports)
$daily_sales = [];
if ($report_type === 'monthly') {
    $daily_query = mysqli_query($con, "SELECT 
        DATE(date) AS sale_date,
        COUNT(payment_id) AS transaction_count,
        SUM(total_amount) AS daily_sales
        FROM payments
        WHERE date BETWEEN '$start_date' AND '$end_date'
        GROUP BY sale_date
        ORDER BY sale_date") or die(mysqli_error($con));
    
    while ($row = mysqli_fetch_assoc($daily_query)) {
        $daily_sales[] = $row;
    }
}
?>
<!-- Report Type Selector and Month Picker -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold">Report Period</h6>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="reportTypeToggle" 
                   <?= $report_type === 'overall' ? 'checked' : '' ?> 
                   onchange="toggleReportType()">
            <label class="form-check-label" for="reportTypeToggle">
                <?= $report_type === 'overall' ? 'Overall Report' : 'Monthly Report' ?>
            </label>
        </div>
    </div>
    <div class="card-body">
        <form method="get" class="form-inline" id="reportForm">
            <input type="hidden" name="report_type" id="reportTypeInput" value="<?= $report_type ?>">
            
            <div class="form-group mr-2" id="monthPickerGroup" 
                 style="<?= $report_type === 'overall' ? 'display:none;' : '' ?>">
                <label for="month" class="mr-2">Select Month:</label>
                <select name="month" id="month" class="form-control">
                    <?php foreach ($available_months as $month): ?>
                        <option value="<?= $month ?>" <?= $month == $selected_month ? 'selected' : '' ?>>
                            <?= date('F Y', strtotime($month)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Generate Report</button>
        </form>
    </div>
</div>

<!-- Main Sales Summary Card -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold">Sales Summary - <?= $date_label ?></h6>
        <button class="btn btn-sm btn-primary" onclick="exportToCSV('salesSummaryTable', 'sales_summary.csv')">
            <i class="fas fa-download"></i> Export
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="salesSummaryTable">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Transactions</th>
                        <th>Total Sales</th>
                        <th>Average Sale</th>
                        <th>Min Sale</th>
                        <th>Max Sale</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales_report as $row): ?>
                        <tr>
                            <td><?= $row['month'] === 'All Time' ? $row['month'] : date('F Y', strtotime($row['month'])) ?></td>
                            <td><?= number_format($row['total_transactions']) ?></td>
                            <td>₱<?= number_format($row['total_sales'], 2) ?></td>
                            <td>₱<?= number_format($row['average_sale'], 2) ?></td>
                            <td>₱<?= number_format($row['min_sale'], 2) ?></td>
                            <td>₱<?= number_format($row['max_sale'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Sales by Customer Card -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold">Sales by Customer</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Transactions</th>
                        <th>Total Spent</th>
                        <th>Avg. per Transaction</th>
                        <th>% of Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_sales = $sales_report[0]['total_sales'] ?? 0;
                    foreach ($customer_sales as $row): 
                        $percentage = $total_sales > 0 ? ($row['total_spent'] / $total_sales) * 100 : 0;
                    ?>
                        <tr>
                            <td><?= $row['customer_name'] ?></td>
                            <td><?= number_format($row['transaction_count']) ?></td>
                            <td>₱<?= number_format($row['total_spent'], 2) ?></td>
                            <td>₱<?= number_format($row['avg_spent'], 2) ?></td>
                            <td><?= number_format($percentage, 1) ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Sales by Truck Card -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold">Sales by Truck</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Truck No.</th>
                        <th>Deliveries</th>
                        <th>Revenue</th>
                        <th>Avg. per Delivery</th>
                        <th>% of Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($truck_sales as $row): 
                        $percentage = $total_sales > 0 ? ($row['revenue_generated'] / $total_sales) * 100 : 0;
                    ?>
                        <tr>
                            <td><?= $row['truck_no'] ?></td>
                            <td><?= number_format($row['delivery_count']) ?></td>
                            <td>₱<?= number_format($row['revenue_generated'], 2) ?></td>
                            <td>₱<?= number_format($row['avg_revenue_per_delivery'], 2) ?></td>
                            <td><?= number_format($percentage, 1) ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Sales by Driver Card -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold">Sales by Driver</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Driver</th>
                        <th>Deliveries</th>
                        <th>Revenue</th>
                        <th>Avg. per Delivery</th>
                        <th>% of Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($driver_sales as $row): 
                        $percentage = $total_sales > 0 ? ($row['revenue_generated'] / $total_sales) * 100 : 0;
                    ?>
                        <tr>
                            <td><?= $row['driver_name'] ?></td>
                            <td><?= number_format($row['delivery_count']) ?></td>
                            <td>₱<?= number_format($row['revenue_generated'], 2) ?></td>
                            <td>₱<?= number_format($row['avg_revenue_per_delivery'], 2) ?></td>
                            <td><?= number_format($percentage, 1) ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($report_type === 'monthly' && !empty($daily_sales)): ?>
<!-- Daily Sales Trend Card -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold">Daily Sales Trend - <?= date('F Y', strtotime($selected_month)) ?></h6>
    </div>
    <div class="card-body">
        <div class="chart-area">
            <canvas id="dailySalesChart"></canvas>
        </div>
        <div class="mt-4 table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Transactions</th>
                        <th>Daily Sales</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daily_sales as $row): ?>
                        <tr>
                            <td><?= date('M j, Y', strtotime($row['sale_date'])) ?></td>
                            <td><?= number_format($row['transaction_count']) ?></td>
                            <td>₱<?= number_format($row['daily_sales'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Export to CSV function
    function exportToCSV(tableId, filename) {
        const table = document.getElementById(tableId);
        const rows = table.querySelectorAll('tr');
        let csv = [];
        
        for (const row of rows) {
            const rowData = [];
            const cols = row.querySelectorAll('td, th');
            
            for (const col of cols) {
                // Remove any commas and format the text
                let text = col.innerText.replace(/,/g, '');
                // Handle currency symbols
                text = text.replace(/[^\d.-]/g, '');
                rowData.push(text);
            }
            
            csv.push(rowData.join(','));
        }
        
        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function toggleReportType() {
        const toggle = document.getElementById('reportTypeToggle');
        const monthPicker = document.getElementById('monthPickerGroup');
        const reportTypeInput = document.getElementById('reportTypeInput');
        
        if (toggle.checked) {
            monthPicker.style.display = 'none';
            reportTypeInput.value = 'overall';
            document.querySelector('label[for="reportTypeToggle"]').textContent = 'Overall Report';
        } else {
            monthPicker.style.display = 'block';
            reportTypeInput.value = 'monthly';
            document.querySelector('label[for="reportTypeToggle"]').textContent = 'Monthly Report';
        }
        
        // Auto-submit the form when toggling
        document.getElementById('reportForm').submit();
    }

    // Daily Sales Chart (for monthly reports)
    <?php if ($report_type === 'monthly' && !empty($daily_sales)): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('dailySalesChart').getContext('2d');
        const dailySalesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [
                    <?php foreach ($daily_sales as $row): ?>
                        '<?= date('j', strtotime($row['sale_date'])) ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    label: 'Daily Sales',
                    data: [
                        <?php foreach ($daily_sales as $row): ?>
                            <?= $row['daily_sales'] ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                    pointRadius: 3,
                    pointHoverRadius: 3,
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 10,
                        right: 25,
                        top: 25,
                        bottom: 0
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            maxTicksLimit: 7
                        }
                    },
                    y: {
                        ticks: {
                            maxTicksLimit: 5,
                            padding: 10,
                            callback: function(value, index, values) {
                                return '₱' + value.toLocaleString();
                            }
                        },
                        grid: {
                            color: "rgb(234, 236, 244)",
                            zeroLineColor: "rgb(234, 236, 244)",
                            drawBorder: false,
                            borderDash: [2],
                            zeroLineBorderDash: [2]
                        }
                    },
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyColor: "#858796",
                        titleMarginBottom: 10,
                        titleColor: '#6e707e',
                        titleFontSize: 14,
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false,
                        intersect: false,
                        mode: 'index',
                        caretPadding: 10,
                        callbacks: {
                            label: function(context) {
                                var label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += '₱' + context.parsed.y.toLocaleString();
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    });
    <?php endif; ?>
</script>
<?php
$content = ob_get_clean();
include "../layout/admin_layout.php";
?>