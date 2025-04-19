<?Php 
require '../../dbcon.php';
require '../../vendor/autoload.php'; // Path to autoload if using Composer
// OR if manual download: require '../../fpdf/fpdf.Php ';

session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== "admin") {
    header("Location: ../../login.php ");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: ../payroll_view.php ");
    exit();
}

$payroll_id = $_GET['id'];

// Get payroll details
$payroll_sql = "SELECT p.*, d.full_name, d.contact_no
                FROM payroll p
                JOIN drivers d ON p.driver_id = d.driver_id
                WHERE p.payroll_id = ?";
$stmt = $con->prepare($payroll_sql);
$stmt->bind_param("i", $payroll_id);
$stmt->execute();
$payroll_result = $stmt->get_result();

if ($payroll_result->num_rows === 0) {
    header("Location: ../payroll_view.php ");
    exit();
}

$payroll = $payroll_result->fetch_assoc();
$stmt->close();

// Create PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);

// Company Header
$pdf->Cell(0,10,'Jordan E-Truck Services',0,1,'C');
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10,'Driver Payslip',0,1,'C');
$pdf->Ln(10);

// Employee Information
$pdf->SetFont('Arial','B',12);
$pdf->Cell(40,10,'Driver Name:',0,0);
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10,$payroll['full_name'],0,1);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(40,10,'Contact No:',0,0);
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10,$payroll['contact_no'],0,1);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(40,10,'Pay Period:',0,0);
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10,date('F j, Y', strtotime($payroll['pay_period_start'])).' to '.date('F j, Y', strtotime($payroll['pay_period_end'])),0,1);
$pdf->Ln(5);

// Earnings
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,10,'Earnings',0,1);
$pdf->SetFont('Arial','',12);
$pdf->Cell(100,10,'Base Salary',0,0);
$pdf->Cell(0,10,'Php '.number_format($payroll['base_salary'], 2),0,1,'R');
$pdf->Cell(100,10,'Commission (15%)',0,0);
$pdf->Cell(0,10,'Php '.number_format($payroll['bonuses'], 2),0,1,'R');
$pdf->Ln(5);

// Deductions
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,10,'Deductions',0,1);
$pdf->SetFont('Arial','',12);
$pdf->Cell(100,10,'SSS',0,0);
$pdf->Cell(0,10,'Php '.number_format($payroll['sss_deduction'], 2),0,1,'R');
$pdf->Cell(100,10,'PhilHealth',0,0);
$pdf->Cell(0,10,'Php '.number_format($payroll['philhealth_deduction'], 2),0,1,'R');
$pdf->Cell(100,10,'Pag-IBIG',0,0);
$pdf->Cell(0,10,'Php '.number_format($payroll['pagibig_deduction'], 2),0,1,'R');
$pdf->Cell(100,10,'Truck Maintenance',0,0);
$pdf->Cell(0,10,'Php '.number_format($payroll['truck_maintenance'], 2),0,1,'R');
$pdf->Cell(100,10,'Tax Deduction',0,0);
$pdf->Cell(0,10,'Php '.number_format($payroll['tax_deduction'], 2),0,1,'R');
$pdf->Ln(5);

// Summary
$pdf->SetFont('Arial','B',12);
$pdf->Cell(100,10,'Total Earnings',0,0);
$pdf->Cell(0,10,'Php '.number_format($payroll['base_salary'] + $payroll['bonuses'], 2),0,1,'R');
$pdf->Cell(100,10,'Total Deductions',0,0);
$pdf->Cell(0,10,'Php '.number_format($payroll['deductions'], 2),0,1,'R');
$pdf->Cell(100,10,'Net Pay',0,0);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,10,'Php '.number_format($payroll['net_pay'], 2),0,1,'R');
$pdf->Ln(10);

// Footer
$pdf->SetFont('Arial','I',10);
$pdf->Cell(0,10,'Generated on: '.date('F j, Y, g:i a'),0,1,'C');
$pdf->Cell(0,10,'Jordan E-Truck Services - Payroll System',0,1,'C');

// Output PDF
$pdf->Output('I', 'driver_payslip_'.$payroll['full_name'].'_'.date('Y-m-d').'.pdf');
exit();
?>