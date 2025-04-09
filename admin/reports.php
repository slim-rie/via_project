<?php
$title = "Reports";
$activePage = "reports";
ob_start();
?>

<h1>Welcome to the reports</h1>
<p>This is where your reports content goes.</p>

<?php
$content = ob_get_clean();
include "../layout/admin_layout.php";
?>