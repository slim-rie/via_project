<?php
$title = "Generate Payroll";
$activePage = "payroll";
ob_start();
require '../dbcon.php';
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (strtolower($_SESSION['role']) !== "admin") {
    header("Location: ../unauthorized.php");
    exit();
}

// Initialize variables
$message = '';
$alert_class = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['month'], $_POST['year'])) {
    $month = $_POST['month'];
    $year = $_POST['year'];

    // Calculate exact date range for the selected month/year
    $start_date = date('Y-m-01', strtotime("$year-$month-01"));
    $end_date = date('Y-m-t', strtotime("$year-$month-01"));

    // Enable error reporting
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $con->begin_transaction();

        // 1. Check for existing payroll for this period (both driver and helper)
        $check_driver = $con->prepare("SELECT payroll_id FROM payroll 
                               WHERE pay_period_start = ? AND pay_period_end = ?");
        $check_driver->bind_param("ss", $start_date, $end_date);
        $check_driver->execute();
        $check_driver_result = $check_driver->get_result();
        $check_driver->close();

        $check_helper = $con->prepare("SELECT payroll_id FROM helper_payroll 
                               WHERE pay_period_start = ? AND pay_period_end = ?");
        $check_helper->bind_param("ss", $start_date, $end_date);
        $check_helper->execute();
        $check_helper_result = $check_helper->get_result();
        $check_helper->close();

        if ($check_driver_result->num_rows > 0 || $check_helper_result->num_rows > 0) {
            throw new Exception("Payroll already exists for " . date('F Y', strtotime($start_date)));
        }

        // 2. Get all drivers with their completed deliveries and truck information
        $driver_query = $con->prepare("
            SELECT 
                d.driver_id, 
                d.full_name,
                t.truck_type,
                COUNT(del.delivery_id) as completed_deliveries,
                COALESCE(SUM(s.distance_km), 0) as total_distance_km
            FROM drivers d
            LEFT JOIN schedules s ON s.driver_id = d.driver_id
            LEFT JOIN trucks t ON t.truck_id = s.truck_id
            LEFT JOIN deliveries del ON del.schedule_id = s.schedule_id
                AND del.delivery_status = 'Completed'
                AND del.delivery_datetime BETWEEN ? AND ?
            GROUP BY d.driver_id
        ");
        $driver_query->bind_param("ss", $start_date, $end_date);
        $driver_query->execute();
        $drivers = $driver_query->get_result();

         // 3. Get all helpers with their completed deliveries and total revenue
         $helper_query = $con->prepare("
         SELECT 
             h.helper_id, 
             h.full_name,
             COUNT(del.delivery_id) as completed_deliveries,
             COALESCE(SUM(py.total_amount), 0) as delivery_revenue
         FROM helpers h
         LEFT JOIN schedules s ON s.helper_id = h.helper_id
         LEFT JOIN deliveries del ON del.schedule_id = s.schedule_id
             AND del.delivery_status = 'Completed'
             AND del.delivery_datetime BETWEEN ? AND ?
         LEFT JOIN payments py ON py.schedule_id = s.schedule_id
         GROUP BY h.helper_id
        ");
        $helper_query->bind_param("ss", $start_date, $end_date);
        $helper_query->execute();
        $helpers = $helper_query->get_result();

        // 4. Salary configuration
        $driver_base_salary = 8000.00;
        $helper_base_salary = 5000.00;
        $helper_commission_rate = 0.10;
        // Rate per km based on wheeler count
        $wheeler_rates = [
            6 => 15.00,   // ₱15 per km for 6-wheeler
            8 => 20.00,   // ₱20 per km for 8-wheeler
            10 => 25.00,  // ₱25 per km for 10-wheeler
            12 => 30.00   // ₱30 per km for 12-wheeler
        ];

        // 5. Prepare payroll insert statements
        $insert_driver = $con->prepare("
            INSERT INTO payroll (
                driver_id, pay_period_start, pay_period_end, total_deliveries,
                base_salary, bonuses, sss_deduction, philhealth_deduction,
                pagibig_deduction, truck_maintenance, tax_deduction, deductions,
                net_pay, date_generated, payment_status, delivery_revenue
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Pending', ? )
        ");

        $insert_helper = $con->prepare("
            INSERT INTO helper_payroll (
                helper_id, pay_period_start, pay_period_end, total_deliveries,
                base_salary, bonuses, sss_deduction, philhealth_deduction,
                pagibig_deduction, deductions, net_pay, payment_status, date_generated
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
        ");
        
        $processed_drivers = 0;
        $processed_helpers = 0;

        // 6. Process each driver's payroll
        while ($driver = $drivers->fetch_assoc()) {
            $deliveries = $driver['completed_deliveries'];
            $distance = $driver['total_distance_km'];
            
            // Extract wheeler count from truck_type (e.g., "10 wheelers" -> 10)
            $wheeler_count = 6; // default if not found
            if (preg_match('/(\d+)\s*wheelers?/i', $driver['truck_type'], $matches)) {
                $wheeler_count = (int)$matches[1];
            }
            
            // Get the rate based on wheeler count
            $rate_per_km = $wheeler_rates[$wheeler_count] ?? $wheeler_rates[6];
            
            // Calculate distance earnings
            $distance_earnings = $distance * $rate_per_km;

            // Fixed deductions (Philippine rates)
            $sss = 581.30;        // Fixed SSS contribution
            $philhealth = 450.00; // Fixed PhilHealth
            $pagibig = 100.00;    // Fixed Pag-IBIG

            // Maintenance fee (₱500 per delivery)
            $truck_fee = $deliveries * 500;

            // Tax calculation (progressive)
            $taxable_income = $driver_base_salary + $distance_earnings;
            $tax = calculateTax($taxable_income);

            // Total deductions
            $total_deductions = $sss + $philhealth + $pagibig + $truck_fee + $tax;

            // Calculate net pay
            $net_pay = ($driver_base_salary + $distance_earnings) - $total_deductions;

            // Insert driver payroll record
            $insert_driver->bind_param(
                "issidddddddddd",
                $driver['driver_id'],
                $start_date,
                $end_date,
                $deliveries,
                $driver_base_salary,
                $distance_earnings, // Stored in bonuses field
                $sss,
                $philhealth,
                $pagibig,
                $truck_fee,
                $tax,
                $total_deductions,
                $net_pay,
                $distance_earnings// Stored in delivery_revenue field
            );
            $insert_driver->execute();
            $processed_drivers++;
        }
        $driver_query->close();
        $insert_driver->close();

        // 7. Process each helper's payroll
        while ($helper = $helpers->fetch_assoc()) {
            $deliveries = $helper['completed_deliveries'];
            $revenue = $helper['delivery_revenue'];

            // Calculate commission (10% of delivery revenue)
            $commission = $revenue * $helper_commission_rate;

            // Fixed deductions (Philippine rates)
            $sss = 581.30;        // Fixed SSS contribution
            $philhealth = 450.00; // Fixed PhilHealth
            $pagibig = 100.00;    // Fixed Pag-IBIG

            

            // Total deductions
            $total_deductions = $sss + $philhealth + $pagibig;

            // Calculate net pay
            $net_pay = ($helper_base_salary + $commission) - $total_deductions;

            // Insert helper payroll record
            $insert_helper->bind_param(
                "issiddddddd",
                $helper['helper_id'],
                $start_date,
                $end_date,
                $deliveries,
                $helper_base_salary,
                $commission,
                $sss,
                $philhealth,
                $pagibig,
                $total_deductions,
                $net_pay
            );
            $insert_helper->execute();
            $processed_helpers++;
        }
        $helper_query->close();
        $insert_helper->close();

        $con->commit();
        $message = "Successfully generated payroll for " . date('F Y', strtotime($start_date)) .
            " ($processed_drivers drivers and $processed_helpers helpers processed)";
        $alert_class = 'alert-success';
    } catch (Exception $e) {
        if (isset($con) && $con instanceof mysqli) {
            $con->rollback();
        }
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
            <h5 class="m-0 font-weight-bold">Select Payroll Period</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="monthSelect">Select Month:</label>
                            <select class="form-control" id="monthSelect" name="month" required>
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
                                foreach ($months as $num => $name) {
                                    $selected = (date('m') == $num) ? 'selected' : '';
                                    echo "<option value='$num' $selected>$name</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="yearSelect">Select Year:</label>
                            <select class="form-control" id="yearSelect" name="year" required>
                                <?php
                                $current_year = date('Y');
                                for ($year = $current_year - 2; $year <= $current_year + 2; $year++) {
                                    $selected = ($year == $current_year) ? 'selected' : '';
                                    echo "<option value='$year' $selected>$year</option>";
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
                        <i class="bi bi-list-ul"></i> View Driver Payrolls
                    </a>
                    <a href="helper_payroll_view.php" class="btn btn-secondary ml-2">
                        <i class="bi bi-list-ul"></i> View Helper Payrolls
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