<?php
$title = "Dashboard";
$activePage = "dashboard";
ob_start();
?>

<h1>Welcome to the Dashboard</h1>
<p>This is where your dashboard content goes.</p>

<?php
$content = ob_get_clean();
include "../layout/admin_layout.php";
?>