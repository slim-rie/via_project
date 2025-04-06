<?php
$title = "Schedules";
$activePage = "schedules";
ob_start();
?>

<h1>Welcome to the schedule</h1>
<p>This is where your schedule content goes.</p>

<?php
$content = ob_get_clean();
include "../layout/layout.php";
?>