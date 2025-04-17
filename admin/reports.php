<?php
require '../dbcon.php';
$title = "Reports";
$activePage = "reports";
ob_start();

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
    $date_label = "Overall Report (All Time)";
} else {
    $start_date = date('Y-m-01', strtotime($selected_month));
    $end_date = date('Y-m-t', strtotime($selected_month));
    $date_label = date('F Y', strtotime($selected_month));
}

// Get report data
$revenue_report = [];
$delivery_report = [];
$truck_utilization = [];
$driver_performance = [];

// 1. Enhanced Revenue Report with Deductions - FIXED FOR OVERALL REPORT
$revenue_query = mysqli_query($con, "SELECT 
    " . ($report_type === 'monthly' ? "DATE_FORMAT(p.date, '%Y-%m') AS month" : "'All Time' AS month") . ",
    SUM(p.total_amount) AS gross_revenue,
    " . ($report_type === 'monthly' ? 
        "SUM(pl.net_pay) AS salary_deductions" : 
        "(SELECT SUM(net_pay) FROM payroll WHERE pay_period_start BETWEEN '$start_date' AND '$end_date') AS salary_deductions") . ",
    SUM(p.total_amount) - " . ($report_type === 'monthly' ? 
        "SUM(pl.net_pay)" : 
        "(SELECT SUM(net_pay) FROM payroll WHERE pay_period_start BETWEEN '$start_date' AND '$end_date')") . " AS net_revenue,
    COUNT(p.payment_id) AS transaction_count
    FROM payments p
    " . ($report_type === 'monthly' ? 
        "LEFT JOIN payroll pl ON DATE_FORMAT(pl.pay_period_start, '%Y-%m') = DATE_FORMAT(p.date, '%Y-%m')" : "") . "
    WHERE p.date BETWEEN '$start_date' AND '$end_date'
    " . ($report_type === 'monthly' ? "GROUP BY month" : "") . "
    ORDER BY p.date DESC") or die(mysqli_error($con));

while ($row = mysqli_fetch_assoc($revenue_query)) {
    $revenue_report[] = $row;
}

// 2. Delivery Performance - WORKS FOR BOTH
$delivery_query = mysqli_query($con, "SELECT 
    d.delivery_status,
    COUNT(*) AS count,
    ROUND(COUNT(*)/(SELECT COUNT(*) FROM deliveries WHERE delivery_datetime BETWEEN '$start_date' AND '$end_date')*100,1) AS percentage
    FROM deliveries d
    WHERE d.delivery_datetime BETWEEN '$start_date' AND '$end_date'
    GROUP BY d.delivery_status") or die(mysqli_error($con));

while ($row = mysqli_fetch_assoc($delivery_query)) {
    $delivery_report[] = $row;
}

// 3. Truck Utilization with Cost Analysis - FIXED FOR OVERALL REPORT
$truck_query = mysqli_query($con, "SELECT 
    t.truck_no,
    COUNT(s.schedule_id) AS deliveries_count,
    SUM(TIMESTAMPDIFF(HOUR, s.start_time, s.end_time)) AS hours_utilized,
    SUM(p.total_amount) AS revenue_generated,
    " . ($report_type === 'monthly' ? 
        "SUM(pl.net_pay) AS driver_costs" : 
        "(SELECT SUM(net_pay) FROM payroll pl WHERE pl.driver_id = s.driver_id AND pl.pay_period_start BETWEEN '$start_date' AND '$end_date') AS driver_costs") . "
    FROM trucks t
    LEFT JOIN schedules s ON t.truck_id = s.truck_id AND s.start_time BETWEEN '$start_date' AND '$end_date'
    LEFT JOIN payments p ON s.schedule_id = p.schedule_id
    " . ($report_type === 'monthly' ? 
        "LEFT JOIN payroll pl ON s.driver_id = pl.driver_id AND DATE_FORMAT(pl.pay_period_start, '%Y-%m') = DATE_FORMAT(s.start_time, '%Y-%m')" : "") . "
    GROUP BY t.truck_id") or die(mysqli_error($con));

while ($row = mysqli_fetch_assoc($truck_query)) {
    $truck_utilization[] = $row;
}

// 4. Driver Performance with Cost Efficiency - FIXED FOR OVERALL REPORT
$driver_query = mysqli_query($con, "SELECT 
    d.full_name,
    COUNT(s.schedule_id) AS deliveries_completed,
    SUM(p.total_amount) AS revenue_generated,
    " . ($report_type === 'monthly' ? 
        "SUM(pl.net_pay) AS salary_cost" : 
        "(SELECT SUM(net_pay) FROM payroll pl WHERE pl.driver_id = d.driver_id AND pl.pay_period_start BETWEEN '$start_date' AND '$end_date') AS salary_cost") . ",
    ROUND(SUM(p.total_amount)/GREATEST(" . ($report_type === 'monthly' ? 
        "SUM(pl.net_pay)" : 
        "(SELECT SUM(net_pay) FROM payroll pl WHERE pl.driver_id = d.driver_id AND pl.pay_period_start BETWEEN '$start_date' AND '$end_date')") . ", 1), 2) AS revenue_per_cost
    FROM drivers d
    LEFT JOIN schedules s ON d.driver_id = s.driver_id AND s.start_time BETWEEN '$start_date' AND '$end_date'
    LEFT JOIN payments p ON s.schedule_id = p.schedule_id
    " . ($report_type === 'monthly' ? 
        "LEFT JOIN payroll pl ON d.driver_id = pl.driver_id AND DATE_FORMAT(pl.pay_period_start, '%Y-%m') = DATE_FORMAT(s.start_time, '%Y-%m')" : "") . "
    GROUP BY d.driver_id") or die(mysqli_error($con));

while ($row = mysqli_fetch_assoc($driver_query)) {
    $driver_performance[] = $row;
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
<!-- Revenue Report Card - Enhanced -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold">Profit & Loss Statement</h6>
        <button class="btn btn-sm btn-primary" onclick="exportToCSV('revenueTable', 'profit_loss.csv')">
            <i class="fas fa-download"></i> Export
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="revenueTable">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Gross Revenue</th>
                        <th>Salary Deductions</th>
                        <th>Net Revenue</th>
                        <th>Profit Margin</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($revenue_report as $row): ?>
                        <tr>
                            <td><?= date('F Y', strtotime($row['month'])) ?></td>
                            <td>₱<?= number_format($row['gross_revenue'], 2) ?></td>
                            <td class="text-danger">-₱<?= number_format($row['salary_deductions'], 2) ?></td>
                            <td class="font-weight-bold">₱<?= number_format($row['net_revenue'], 2) ?></td>
                            <td><?= $row['gross_revenue'] > 0 ? number_format(($row['net_revenue'] / $row['gross_revenue']) * 100, 2) : 0 ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <?php if (count($revenue_report) > 1): ?>
                    <tfoot>
                        <tr class="font-weight-bold">
                            <td>TOTAL</td>
                            <td>₱<?= number_format(array_sum(array_column($revenue_report, 'gross_revenue')), 2) ?></td>
                            <td class="text-danger">-₱<?= number_format(array_sum(array_column($revenue_report, 'salary_deductions')), 2) ?></td>
                            <td>₱<?= number_format(array_sum(array_column($revenue_report, 'net_revenue')), 2) ?></td>
                            <?php
                            $total_gross = array_sum(array_column($revenue_report, 'gross_revenue'));
                            $total_net = array_sum(array_column($revenue_report, 'net_revenue'));
                            $profit_margin = $total_gross > 0 ? ($total_net / $total_gross) * 100 : 0;
                            ?>
                            <td><?= number_format($profit_margin, 2) ?>%</td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
        
        <!-- Add Chart Canvas -->
        <div class="mt-4">
            <canvas id="profitChart" height="100"></canvas>
        </div>
    </div>
</div>

<!-- Truck Utilization Card - Enhanced -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold">Truck Profitability</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Truck No.</th>
                        <th>Deliveries</th>
                        <th>Revenue</th>
                        <th>Driver Costs</th>
                        <th>Net Profit</th>
                        <th>₱/Hour</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($truck_utilization as $row):
                        $net_profit = $row['revenue_generated'] - $row['driver_costs'];
                        $hourly_rate = $row['hours_utilized'] > 0 ? $net_profit / $row['hours_utilized'] : 0;
                    ?>
                        <tr>
                            <td><?= $row['truck_no'] ?></td>
                            <td><?= $row['deliveries_count'] ?></td>
                            <td>₱<?= number_format($row['revenue_generated'], 2) ?></td>
                            <td class="text-danger">-₱<?= number_format($row['driver_costs'], 2) ?></td>
                            <td class="<?= $net_profit >= 0 ? 'text-success' : 'text-danger' ?>">
                                ₱<?= number_format($net_profit, 2) ?>
                            </td>
                            <td>₱<?= number_format($hourly_rate, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Driver Performance Card - Enhanced -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold">Driver Cost Efficiency</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Driver</th>
                        <th>Deliveries</th>
                        <th>Revenue Generated</th>
                        <th>Salary Cost</th>
                        <th>Revenue ₱ per ₱ Salary</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($driver_performance as $row): ?>
                        <tr>
                            <td><?= $row['full_name'] ?></td>
                            <td><?= $row['deliveries_completed'] ?></td>
                            <td>₱<?= number_format($row['revenue_generated'], 2) ?></td>
                            <td>₱<?= number_format($row['salary_cost'], 2) ?></td>
                            <td class="<?= $row['revenue_per_cost'] >= 1 ? 'text-success' : 'text-danger' ?>">
                                ₱<?= number_format($row['revenue_per_cost'], 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Profit/Loss Bar Chart
    document.addEventListener('DOMContentLoaded', function() {
        const profitCtx = document.getElementById('profitChart').getContext('2d');
        new Chart(profitCtx, {
            type: 'bar',
            data: {
                labels: [
                    <?php foreach ($revenue_report as $row): ?> 
                        '<?= date('M Y', strtotime($row['month'])) ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                        label: 'Gross Revenue',
                        data: [
                            <?php foreach ($revenue_report as $row): ?>
                                <?= $row['gross_revenue'] ?>,
                            <?php endforeach; ?>
                        ],
                        backgroundColor: '#4e73df'
                    },
                    {
                        label: 'Salary Deductions',
                        data: [
                            <?php foreach ($revenue_report as $row): ?> 
                                -<?= $row['salary_deductions'] ?>,
                            <?php endforeach; ?>
                        ],
                        backgroundColor: '#e74a3b'
                    },
                    {
                        label: 'Net Revenue',
                        data: [
                            <?php foreach ($revenue_report as $row): ?>
                                <?= $row['net_revenue'] ?>,
                            <?php endforeach; ?>
                        ],
                        backgroundColor: '#1cc88a',
                        type: 'line',
                        borderColor: '#1cc88a',
                        borderWidth: 2,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    });
    
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
</script>
<?php
$content = ob_get_clean();
include "../layout/admin_layout.php";
?>