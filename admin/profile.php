<?php
$title = "Profile";
$activePage = "profile";
ob_start();
?>

<h1>Welcome to the profile</h1>
<a href="employee/add_employee.php">Add new employee</a>
<a href="clients/add_client.php">Add new customer</a>
<a href="../register.php">register</a>
<?php
$content = ob_get_clean();
include "../layout/admin_layout.php";
?>