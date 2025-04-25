<?php
require '../dbcon.php';
require '../vendor/autoload.php';

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

// Create PDF receipt with smaller margins
$pdf = new FPDF('P','mm','A4');
$pdf->AddPage();
$pdf->SetMargins(15, 10, 15); // Reduced margins

// Header - more compact
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,6,'DELIVERY RECEIPT',0,1,'C');
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,5,'JOREDANE TRUCKING SERVICES',0,1,'C');
$pdf->Cell(0,5,'123 Logistics Street, City',0,1,'C');
$pdf->Cell(0,5,'Phone: (123) 456-7890',0,1,'C');
$pdf->Ln(5);

// Delivery Details - two columns
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,'DELIVERY INFORMATION',0,1);
$pdf->SetFont('Arial','',9);

// Left column
$pdf->Cell(40,5,'Receipt #:',0,0);
$pdf->Cell(50,5,$schedule['schedule_id'],0,0);
$pdf->Cell(40,5,'Date:',0,0);
$pdf->Cell(0,5,date('M j, Y', strtotime($schedule['start_time'])),0,1);

$pdf->Cell(40,5,'Customer:',0,0);
$pdf->Cell(50,5,$schedule['customer_name'],0,0);
$pdf->Cell(40,5,'Truck No:',0,0);
$pdf->Cell(0,5,$schedule['truck_no'],0,1);

$pdf->Cell(40,5,'Pick-up:',0,0);
$pdf->Cell(50,5,$schedule['pick_up'],0,0);
$pdf->Cell(40,5,'Distance:',0,0);
$pdf->Cell(0,5,$schedule['distance_km'].' km',0,1);

$pdf->Cell(40,5,'Destination:',0,0);
$pdf->Cell(50,5,$schedule['destination'],0,0);
$pdf->Cell(40,5,'Driver:',0,0);
$pdf->Cell(0,5,$schedule['driver_name'],0,1);

$pdf->Cell(40,5,'',0,0);
$pdf->Cell(50,5,'',0,0);
$pdf->Cell(40,5,'Helper:',0,0);
$pdf->Cell(0,5,$schedule['helper_name'],0,1);

$pdf->Ln(5);

// Payment Information - single line
$pdf->SetFont('Arial','B',10);
$pdf->Cell(30,6,'PAYMENT:',0,0);
$pdf->SetFont('Arial','',9);
$pdf->Cell(40,6,'Php '.number_format($schedule['total_amount'], 2),0,0);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(25,6,'STATUS:',0,0);
$pdf->SetFont('Arial','',9);
$pdf->Cell(0,6,$schedule['payment_status'],0,1);

if ($schedule['payment_date']) {
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(30,6,'PAID ON:',0,0);
    $pdf->SetFont('Arial','',9);
    $pdf->Cell(0,6,date('M j, Y', strtotime($schedule['payment_date'])),0,1);
}

$pdf->Ln(8);

// Signature Section - compact
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,5,'ACKNOWLEDGEMENT OF RECEIPT',0,1,'C');
$pdf->Ln(3);

// Signature lines with names
$pdf->Cell(95,15,'',0,0,'C'); // Customer space
$pdf->Cell(95,15,'',0,1,'C'); // Driver space

$pdf->Cell(95,5,'_________________________',0,0,'C');
$pdf->Cell(95,5,'_________________________',0,1,'C');

$pdf->SetFont('Arial','I',8);
$pdf->Cell(95,5,$schedule['customer_name'],0,0,'C');
$pdf->Cell(95,5,$schedule['driver_name'],0,1,'C');

$pdf->SetFont('Arial','',8);
$pdf->Cell(95,5,'Customer Signature',0,0,'C');
$pdf->Cell(95,5,'Driver Signature',0,1,'C');

$pdf->Ln(5);

// Footer - very compact
$pdf->SetFont('Arial','I',7);
$pdf->Cell(0,3,'Thank you for choosing our services!',0,1,'C');
$pdf->Cell(0,3,'This document serves as official receipt for delivery #'.$schedule['schedule_id'],0,1,'C');

// Output the PDF
$pdf->Output('D', 'Delivery_Receipt_'.$schedule_id.'.pdf');
exit();