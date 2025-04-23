<?php
include "../dbcon.php";
$title = "Employee Page";
$activePage = "employees";
ob_start();
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== "admin") {
    header("Location: ../login.php");
    exit();
}

// Fetch driver data with delivery counts
$driver_query = "SELECT d.*, 
                COUNT(del.delivery_id) as total_deliveries,
                SUM(CASE WHEN del.delivery_status = 'Completed' THEN 1 ELSE 0 END) as completed_deliveries
                FROM drivers d
                LEFT JOIN schedules s ON s.driver_id = d.driver_id
                LEFT JOIN deliveries del ON del.schedule_id = s.schedule_id
                GROUP BY d.driver_id";
$driver_result = mysqli_query($con, $driver_query);

// Fetch helper data with delivery counts
$helper_query = "SELECT h.*, 
                COUNT(del.delivery_id) as total_deliveries,
                SUM(CASE WHEN del.delivery_status = 'Completed' THEN 1 ELSE 0 END) as completed_deliveries
                FROM helpers h
                LEFT JOIN schedules s ON s.helper_id = h.helper_id
                LEFT JOIN deliveries del ON del.schedule_id = s.schedule_id
                GROUP BY h.helper_id";
$helper_result = mysqli_query($con, $helper_query);
?>

<h1 class="mb-4">Employees
    <a href="../register.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Add Employee
    </a>
</h1>

<!-- Drivers Section -->
<div class="card shadow p-3 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Driver List</h5>
        <!-- <a href="../register.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Driver
        </a> -->
    </div>

    <div class="table-responsive fixed-header">
        <table class="table table-hover table-bordered">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Total Deliveries</th>
                    <th>Completed Deliveries</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Loop through the driver results and display each row
                if (mysqli_num_rows($driver_result) > 0) {
                    while ($row = mysqli_fetch_assoc($driver_result)) {
                        echo "<tr>";
                        echo "<td>" . $row['driver_id'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                        echo "<td>" . $row['total_deliveries'] . "</td>";
                        echo "<td>" . $row['completed_deliveries'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                        echo "<td>
                                <a href='edit_employee.php?id=" . $row['driver_id'] . "&type=driver' class='btn btn-sm btn-warning'><i class='bi bi-pencil-square'></i></a>
                                <a href='delete_employee.php?id=" . $row['driver_id'] . "&type=driver' class='btn btn-sm btn-danger'><i class='bi bi-trash'></i></a>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' class='text-center'>No drivers found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Helpers Section -->
<div class="card shadow p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Helper List</h5>
        <!-- <a href="../register.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Helper
        </a> -->
    </div>

    <div class="table-responsive fixed-header">
        <table class="table table-hover table-bordered">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Total Deliveries</th>
                    <th>Completed Deliveries</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Loop through the helper results and display each row
                if (mysqli_num_rows($helper_result) > 0) {
                    while ($row = mysqli_fetch_assoc($helper_result)) {
                        echo "<tr>";
                        echo "<td>" . $row['helper_id'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                        echo "<td>" . $row['total_deliveries'] . "</td>";
                        echo "<td>" . $row['completed_deliveries'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                        echo "<td>
                                <a href='edit_employee.php?id=" . $row['helper_id'] . "&type=helper' class='btn btn-sm btn-warning'><i class='bi bi-pencil-square'></i></a>
                                <a href='delete_employee.php?id=" . $row['helper_id'] . "&type=helper' class='btn btn-sm btn-danger'><i class='bi bi-trash'></i></a>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' class='text-center'>No helpers found</td></tr>";
                }

                // Close the database connection
                mysqli_close($con);
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
include "../layout/admin_layout.php";
?>