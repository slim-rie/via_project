<?php
require '../dbcon.php';
require '../vendor/autoload.php';

// Check if schedule ID is provided
if (!isset($_GET['schedule_id']) || !is_numeric($_GET['schedule_id'])) {
    die('Invalid schedule ID');
}

$schedule_id = intval($_GET['schedule_id']);

// Get schedule details
$stmt = $con->prepare("
    SELECT s.*, t.truck_no, c.full_name AS customer_name, 
           d.full_name AS driver_name, h.full_name AS helper_name,
           p.total_amount, p.status AS payment_status, p.date AS payment_date
    FROM schedules s
    JOIN trucks t ON s.truck_id = t.truck_id
    JOIN customers c ON s.customer_id = c.customer_id
    JOIN drivers d ON s.driver_id = d.driver_id
    JOIN helpers h ON s.helper_id = h.helper_id
    LEFT JOIN payments p ON s.schedule_id = p.schedule_id
    WHERE s.schedule_id = ?
");
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$schedule = $stmt->get_result()->fetch_assoc();

if (!$schedule) {
    die('Schedule not found');
}

// Create PDF receipt
$pdf = new FPDF();
$pdf->AddPage();

// Header
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Delivery Receipt',0,1,'C');
$pdf->Ln(10);

// Company Info
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10,'Trucking Services Company',0,1,'C');
$pdf->Cell(0,10,'123 Logistics Street, City',0,1,'C');
$pdf->Cell(0,10,'Phone: (123) 456-7890',0,1,'C');
$pdf->Ln(10);

// Delivery Details
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,10,'Delivery Details',0,1);
$pdf->SetFont('Arial','',10);

$pdf->Cell(50,10,'Receipt #:',0,0);
$pdf->Cell(0,10,$schedule['schedule_id'],0,1);

$pdf->Cell(50,10,'Date:',0,0);
$pdf->Cell(0,10,date('M j, Y', strtotime($schedule['start_time'])),0,1);

$pdf->Cell(50,10,'Customer:',0,0);
$pdf->Cell(0,10,$schedule['customer_name'],0,1);

$pdf->Cell(50,10,'Pick-up Location:',0,0);
$pdf->Cell(0,10,$schedule['pick_up'],0,1);

$pdf->Cell(50,10,'Destination:',0,0);
$pdf->Cell(0,10,$schedule['destination'],0,1);

$pdf->Cell(50,10,'Distance (km):',0,0);
$pdf->Cell(0,10,$schedule['distance_km'],0,1);

$pdf->Cell(50,10,'Driver:',0,0);
$pdf->Cell(0,10,$schedule['driver_name'],0,1);

$pdf->Cell(50,10,'Helper:',0,0);
$pdf->Cell(0,10,$schedule['helper_name'],0,1);

$pdf->Cell(50,10,'Truck No:',0,0);
$pdf->Cell(0,10,$schedule['truck_no'],0,1);

$pdf->Ln(10);

// Payment Information
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,10,'Payment Information',0,1);
$pdf->SetFont('Arial','',10);

$pdf->Cell(50,10,'Amount:',0,0);
$pdf->Cell(0,10,'Php ' . number_format($schedule['total_amount'], 2),0,1);

$pdf->Cell(50,10,'Status:',0,0);
$pdf->Cell(0,10,$schedule['payment_status'],0,1);

if ($schedule['payment_date']) {
    $pdf->Cell(50,10,'Payment Date:',0,0);
    $pdf->Cell(0,10,date('M j, Y', strtotime($schedule['payment_date'])),0,1);
}

$pdf->Ln(15);

// Footer
$pdf->SetFont('Arial','I',8);
$pdf->Cell(0,10,'Thank you for choosing our services!',0,1,'C');

// Output the PDF
$pdf->Output('D', 'Delivery_Receipt_'.$schedule_id.'.pdf');
exit();