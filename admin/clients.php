<?php
$title = "Clients";
$activePage = "clients";
ob_start();
?>

<h1>Welcome to the clients</h1>
<p>This is where your clients content goes.</p>

<?php
$content = ob_get_clean();
include "../layout/admin_layout.php";
?>