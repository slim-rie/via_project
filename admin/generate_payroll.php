<?php
$title = "Generate Payroll";
$activePage = "payroll";
ob_start();
require '../dbcon.php';

// Initialize variables
$message = '';
$alert_class = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['month'], $_POST['year'])) {
    $month = $_POST['month'];
    $year = $_POST['year'];

    // Calculate exact date range for the selected month/year
    $start_date = date('Y-m-01', strtotime("$year-$month-01"));
    $end_date = date('Y-m-t', strtotime("$year-$month-01")); // 't' gives last day of month

    // Enable error reporting
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $con->begin_transaction();

        // 1. Check for existing payroll for this period
        $check = $con->prepare("SELECT payroll_id FROM payroll 
                               WHERE pay_period_start = ? AND pay_period_end = ?");
        $check->bind_param("ss", $start_date, $end_date);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            throw new Exception("Payroll already exists for " . date('F Y', strtotime($start_date)));
        }

        // 2. Get all drivers and their completed deliveries
        $driver_query = $con->prepare("
            SELECT 
                d.driver_id, 
                d.full_name,
                COUNT(del.delivery_id) as completed_deliveries
            FROM drivers d
            LEFT JOIN schedules s ON s.driver_id = d.driver_id
            LEFT JOIN deliveries del ON del.schedule_id = s.schedule_id
                AND del.delivery_status = 'Completed'
                AND del.delivery_datetime BETWEEN ? AND ?
            GROUP BY d.driver_id
        ");
        $driver_query->bind_param("ss", $start_date, $end_date);
        $driver_query->execute();
        $drivers = $driver_query->get_result();

        // 3. Base salary configuration
        $base_salary = 7000.00;

        // 4. Prepare payroll insert statement
        $insert = $con->prepare("
            INSERT INTO payroll (
                driver_id, pay_period_start, pay_period_end, total_deliveries,
                base_salary, bonuses, sss_deduction, philhealth_deduction,
                pagibig_deduction, truck_maintenance, tax_deduction, deductions,
                net_pay, date_generated, payment_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Pending')
        ");

        $processed_drivers = 0;
        // 5. Process each driver's payroll
        while ($driver = $drivers->fetch_assoc()) {
            $deliveries = $driver['completed_deliveries'];

            // Performance-based earnings (higher incentive per delivery)
            $bonuses = $deliveries * 750; // Increased from 500 to 750 per delivery

            // Reduced fixed deductions
            $sss = $base_salary * 0.03;         // Reduced from 4.5% to 3%
            $philhealth = $base_salary * 0.02;  // Reduced from 2.5% to 2%
            $pagibig = ($base_salary > 1500) ? $base_salary * 0.01 : 0; // Reduced from 2% to 1%

            // Variable truck maintenance fee based on revenue
            $truck_fee = $deliveries * 150;     // Reduced from 200 to 150 per delivery

            // Progressive tax calculation remains the same
            $tax = calculateTax($base_salary + $bonuses);

            // Total deductions (now significantly lower)
            $total_deductions = $sss + $philhealth + $pagibig + $truck_fee + $tax;

            // Calculate net pay (now more favorable)
            $net_pay = ($base_salary + $bonuses) - $total_deductions;

            // Insert payroll record
            $insert->bind_param(
                "issiddddddddd",
                $driver['driver_id'],
                $start_date,
                $end_date,
                $deliveries,
                $base_salary,
                $bonuses,
                $sss,
                $philhealth,
                $pagibig,
                $truck_fee,
                $tax,
                $total_deductions,
                $net_pay
            );
            $insert->execute();
            $processed_drivers++;
        }

        $con->commit();
        $message = "Successfully generated payroll for " . date('F Y', strtotime($start_date)) .
            " ($processed_drivers drivers processed)";
        $alert_class = 'alert-success';
    } catch (Exception $e) {
        $con->rollback();
        $message = "Error: " . $e->getMessage();
        $alert_class = 'alert-danger';
    }
}

// Philippine tax calculation function
function calculateTax($monthly_income)
{
    $annual = $monthly_income * 12;

    if ($annual <= 250000) return 0;
    elseif ($annual <= 400000) return ($annual - 250000) * 0.15 / 12;
    elseif ($annual <= 800000) return (22500 + ($annual - 400000) * 0.20) / 12;
    elseif ($annual <= 2000000) return (102500 + ($annual - 800000) * 0.25) / 12;
    elseif ($annual <= 8000000) return (402500 + ($annual - 2000000) * 0.30) / 12;
    else return (2202500 + ($annual - 8000000) * 0.35) / 12;
}
?>

<div class="container-fluid">
    <h1 class="mb-4">Generate Payroll</h1>

    <?php if ($message): ?>
        <div class="alert <?= $alert_class ?>"><?= $message ?></div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-header py-3">
            <h5 class="m-0 font-weight-bold text-primary">Select Payroll Period</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="monthSelect">Select Month:</label>
                            <select class="form-control" id="monthSelect" name="month" required>
                                <option value="">-- Select Month --</option>
                                <?php
                                $months = [
                                    '01' => 'January',
                                    '02' => 'February',
                                    '03' => 'March',
                                    '04' => 'April',
                                    '05' => 'May',
                                    '06' => 'June',
                                    '07' => 'July',
                                    '08' => 'August',
                                    '09' => 'September',
                                    '10' => 'October',
                                    '11' => 'November',
                                    '12' => 'December'
                                ];
                                $currentMonth = date('m');
                                foreach ($months as $num => $name) {
                                    $selected = ($num == $currentMonth) ? ' selected' : '';
                                    echo "<option value='$num'$selected>$name</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="yearSelect">Select Year:</label>
                            <select class="form-control" id="yearSelect" name="year" required>
                                <option value="">-- Select Year --</option>
                                <?php
                                $currentYear = date('Y');
                                for ($year = $currentYear - 1; $year <= $currentYear + 1; $year++) {
                                    $selected = ($year == $currentYear) ? ' selected' : '';
                                    echo "<option value='$year'$selected>$year</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-calculator"></i> Generate Payroll
                    </button>
                    <a href="payroll_view.php" class="btn btn-secondary ml-2">
                        <i class="bi bi-list-ul"></i> View Existing Payrolls
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include "../layout/admin_layout.php";
?>