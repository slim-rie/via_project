<?php
$title = "home";
$activePage = "home";
ob_start();
?>

<div class="info-section">
    <div class="info-card glass">
        <h1><i class="bi bi-truck-front-fill"></i> Delivery Details</h1>
        <p class="tagline">We deliver safely, on time, every time.</p>
        <div class="info-item"><i class="bi bi-cash-coin"></i> <strong>Rate:</strong> ₱5,000 per delivery</div>
        <div class="info-item"><i class="bi bi-alarm"></i> <strong>Pickup Starts:</strong> 6:00 AM onwards</div>
        <div class="info-item"><i class="bi bi-clock-history"></i> <strong>Delivery Complete Before:</strong> 6:00 PM</div>
    </div>
</div>

<?php 
$content = ob_get_clean();
include "../layout/client_layout.php";
?>
