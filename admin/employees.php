<?php
$title = "Employees";
$activePage = "employees";
ob_start();
?>

<h1>Welcome to the employee</h1>
<p>This is where your employee content goes.</p>

<?php
$content = ob_get_clean();
include "../layout/layout.php";
?>