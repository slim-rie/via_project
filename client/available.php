<?php
$title = "available";
$activePage = "available";
ob_start();
?>

<h1>Welcome to Home client</h1>

<?php 
$content = ob_get_clean();
include "../layout/client_layout.php";
?>