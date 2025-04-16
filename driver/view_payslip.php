<?php
require '../dbcon.php';

// Get payroll ID from URL
$payroll_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch payroll data
$query = "SELECT p.*, d.full_name, d.email 
          FROM payroll p
          JOIN drivers d ON p.driver_id = d.driver_id
          WHERE p.payroll_id = ?";
$stmt = $con->prepare($query);
$stmt->bind_param("i", $payroll_id);
$stmt->execute();
$payroll = $stmt->get_result()->fetch_assoc();

if (!$payroll) {
    die("Payslip not found");
}

// Calculate earnings
$gross_pay = $payroll['base_salary'] + $payroll['bonuses'];

// Start output
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - <?= $payroll['full_name'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .payslip-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .company-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #364C84;
            padding-bottom: 20px;
        }
        .company-name {
            color: #364C84;
            font-weight: bold;
            font-size: 24px;
        }
        .payslip-title {
            color: #364C84;
            text-align: center;
            margin-bottom: 30px;
        }
        .section-title {
            background-color: #364C84;
            color: white;
            padding: 8px 15px;
            margin-top: 20px;
            font-size: 16px;
        }
        .detail-row {
            margin-bottom: 8px;
        }
        .detail-label {
            font-weight: bold;
        }
        .total-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                background: none;
            }
            .payslip-container {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="payslip-container">
        <!-- Company Header -->
        <div class="company-header">
            <div class="company-name">JOREDANE TRUCKING SERVICES</div>
            <div>123 Main Street, City, Country</div>
            <div>Phone: (123) 456-7890 | Email: info@jordanetrucking.com</div>
        </div>

        <!-- Payslip Title -->
        <h3 class="payslip-title">EMPLOYEE PAYSLIP</h3>

        <!-- Employee and Pay Period Info -->
        <div class="row">
            <div class="col-md-6">
                <div class="detail-row">
                    <span class="detail-label">Employee ID:</span> DRV-<?= str_pad($payroll['driver_id'], 4, '0', STR_PAD_LEFT) ?>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Employee Name:</span> <?= htmlspecialchars($payroll['full_name']) ?>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span> <?= $payroll['email'] ? htmlspecialchars($payroll['email']) : 'N/A' ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detail-row">
                    <span class="detail-label">Pay Period:</span> 
                    <?= date('M j, Y', strtotime($payroll['pay_period_start'])) ?> - <?= date('M j, Y', strtotime($payroll['pay_period_end'])) ?>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Pay Date:</span> 
                    <?= $payroll['payment_date'] ? date('M j, Y', strtotime($payroll['payment_date'])) : 'Pending' ?>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="badge <?= $payroll['payment_status'] == 'Paid' ? 'bg-success' : 'bg-warning' ?>">
                        <?= $payroll['payment_status'] ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Earnings Section -->
        <div class="section-title mt-4">EARNINGS</div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Base Salary</td>
                    <td>₱<?= number_format($payroll['base_salary'], 2) ?></td>
                </tr>
                <tr>
                    <td>Delivery Bonuses (<?= $payroll['total_deliveries'] ?> deliveries)</td>
                    <td>₱<?= number_format($payroll['bonuses'], 2) ?></td>
                </tr>
                <tr class="table-active">
                    <td><strong>Total Earnings</strong></td>
                    <td><strong>₱<?= number_format($gross_pay, 2) ?></strong></td>
                </tr>
            </tbody>
        </table>

        <!-- Deductions Section -->
        <div class="section-title">DEDUCTIONS</div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>SSS Contribution</td>
                    <td>₱<?= number_format($payroll['sss_deduction'], 2) ?></td>
                </tr>
                <tr>
                    <td>PhilHealth Contribution</td>
                    <td>₱<?= number_format($payroll['philhealth_deduction'], 2) ?></td>
                </tr>
                <tr>
                    <td>Pag-IBIG Contribution</td>
                    <td>₱<?= number_format($payroll['pagibig_deduction'], 2) ?></td>
                </tr>
                <tr>
                    <td>Tax Withholding</td>
                    <td>₱<?= number_format($payroll['tax_deduction'], 2) ?></td>
                </tr>
                <tr>
                    <td>Truck Maintenance Fees</td>
                    <td>₱<?= number_format($payroll['truck_maintenance'], 2) ?></td>
                </tr>
                <tr class="table-active">
                    <td><strong>Total Deductions</strong></td>
                    <td><strong>₱<?= number_format($payroll['deductions'], 2) ?></strong></td>
                </tr>
            </tbody>
        </table>

        <!-- Net Pay Section -->
        <div class="total-box text-center">
            <h4>NET PAY</h4>
            <h2 class="text-primary">₱<?= number_format($payroll['net_pay'], 2) ?></h2>
            <p class="text-muted"><?= strtoupper(numberToWords($payroll['net_pay'])) ?> PESOS ONLY</p>
        </div>

        <!-- Footer -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="border-top pt-3">
                    <p class="mb-1">Employee Signature</p>
                    <div style="height: 50px;"></div>
                    <p class="mb-0"><?= htmlspecialchars($payroll['full_name']) ?></p>
                    <p class="text-muted">Date: ________________</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border-top pt-3">
                    <p class="mb-1">Authorized Signature</p>
                    <div style="height: 50px;"></div>
                    <p class="mb-0">JOREDANE TRUCKING SERVICES</p>
                    <p class="text-muted">Date: <?= date('M j, Y') ?></p>
                </div>
            </div>
        </div>

        <!-- Print Button -->
        <div class="mt-4 no-print">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Print Payslip
            </button>
            <a href="payslip.php" class="btn btn-secondary ms-2">
                <i class="bi bi-arrow-left"></i> Back to Payroll
            </a>
        </div>
    </div>

    <!-- Number to Words Function -->
    <?php
    function numberToWords($num) {
        $ones = array("", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine");
        $tens = array("", "Ten", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety");
        $teens = array("Ten", "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen", "Nineteen");
        
        $num = (int)$num;
        if ($num == 0) return "Zero";
        
        $words = "";
        
        if ($num >= 1000) {
            $thousands = (int)($num / 1000);
            $words .= numberToWords($thousands) . " Thousand ";
            $num %= 1000;
        }
        
        if ($num >= 100) {
            $hundreds = (int)($num / 100);
            $words .= $ones[$hundreds] . " Hundred ";
            $num %= 100;
        }
        
        if ($num >= 20) {
            $tensDigit = (int)($num / 10);
            $words .= $tens[$tensDigit] . " ";
            $num %= 10;
        } elseif ($num >= 10) {
            $words .= $teens[$num - 10] . " ";
            $num = 0;
        }
        
        if ($num > 0) {
            $words .= $ones[$num] . " ";
        }
        
        return trim($words);
    }
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Automatically trigger print dialog when page loads (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
<?php
$content = ob_get_clean();
echo $content;
?>