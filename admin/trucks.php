<?php
include "../dbcon.php";
$title = "Truck Management";
$activePage = "trucks";
ob_start();
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== "admin") {
    header("Location: ../login.php");
    exit();
}

// Handle add/update truck
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == "add") {
        $truck_no = $_POST['truck_no'];
        $status = $_POST['status'];
        $driver_id = !empty($_POST['driver_id']) ? $_POST['driver_id'] : null;
        
        $stmt = $con->prepare("INSERT INTO trucks (truck_no, status, driver_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $truck_no, $status, $driver_id);
        $stmt->execute();
        echo "<div style='color: green;'>Truck added successfully!</div>";
    } elseif ($_POST['action'] == "update") {
        $truck_id = $_POST['truck_id'];
        $truck_no = $_POST['truck_no'];
        $status = $_POST['status'];
        $driver_id = !empty($_POST['driver_id']) ? $_POST['driver_id'] : null;
        
        $stmt = $con->prepare("UPDATE trucks SET truck_no = ?, status = ?, driver_id = ? WHERE truck_id = ?");
        $stmt->bind_param("ssii", $truck_no, $status, $driver_id, $truck_id);
        $stmt->execute();
        echo "<div style='color: green;'>Truck updated successfully!</div>";
    }
}

// Handle delete truck
if (isset($_GET['delete'])) {
    $truck_id = $_GET['delete'];
    $stmt = $con->prepare("DELETE FROM trucks WHERE truck_id = ?");
    $stmt->bind_param("i", $truck_id);
    $stmt->execute();
    echo "<div style='color: red;'>Truck deleted.</div>";
}

// Fetch trucks with their assigned drivers
$trucks = $con->query("
    SELECT t.truck_id, t.truck_no, t.status, t.driver_id, d.full_name AS driver_name , h.full_name AS helper_name
    FROM trucks t
    LEFT JOIN drivers d ON t.driver_id = d.driver_id
    LEFT JOIN helpers h ON t.helper_id = h.helper_id
") or die($con->error);

// Fetch all drivers for dropdown
$drivers = $con->query("SELECT driver_id, full_name FROM drivers") or die($con->error);
?>

<h1>Truck Management</h1>

<!-- Add/Update Truck Form -->
<form method="POST" action="">
    <h2>Add New Truck</h2>
    <label for="truck_no">Truck Number:</label>
    <input type="text" id="truck_no" name="truck_no" required>
    
    <label for="status">Status:</label>
    <select name="status" required>
        <option value="Available">Available</option>
        <option value="Booked">Booked</option>
        <option value="Maintenance">Maintenance</option>
    </select>
    
    <label for="driver_id">Assign Driver:</label>
    <select name="driver_id">
        <option value="">No Driver</option>
        <?php while($driver = $drivers->fetch_assoc()): ?>
            <option value="<?= $driver['driver_id'] ?>">
                <?= htmlspecialchars($driver['full_name']) ?>
            </option>
        <?php endwhile; ?>
    </select>
    
    <input type="hidden" name="action" value="add">
    <button type="submit">Add Truck</button>
</form>

<h2>Existing Trucks</h2>
<div class="fixed_header">
    <table border="1" cellpadding="5">
        <tr>
            <th>Truck ID</th>
            <th>Truck Number</th>
            <th>Status</th>
            <th>Driver</th>
            <th>Helper</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $trucks->fetch_assoc()): ?>
            <tr>
                <td><?= $row['truck_id'] ?></td>
                <td><?= htmlspecialchars($row['truck_no']) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td><?= !empty($row['driver_name']) ? htmlspecialchars($row['driver_name']) : 'No Driver Assigned' ?></td>
                <td><?= !empty($row['helper_name']) ? htmlspecialchars($row['helper_name']) : 'No Helper Assigned' ?></td>
                <td>
                    <!-- Update Truck Form -->
                    <form method="POST" action="" style="display:inline;">
                        <input type="hidden" name="truck_id" value="<?= $row['truck_id'] ?>">
                        <input type="text" name="truck_no" value="<?= $row['truck_no'] ?>" required>
                        
                        <select name="status" required>
                            <option value="Available" <?= $row['status'] == 'Available' ? 'selected' : '' ?>>Available</option>
                            <option value="Booked" <?= $row['status'] == 'Booked' ? 'selected' : '' ?>>Booked</option>
                            <option value="Maintenance" <?= $row['status'] == 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                        </select>
                        
                        <select name="driver_id">
                            <option value="">No Driver</option>
                            <?php 
                            $drivers->data_seek(0); // Reset pointer to beginning
                            while($driver = $drivers->fetch_assoc()): ?>
                                <option value="<?= $driver['driver_id'] ?>" <?= $row['driver_id']==$driver['driver_id']?'selected':'' ?>>
                                    <?= htmlspecialchars($driver['full_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        
                        <input type="hidden" name="action" value="update">
                        <button type="submit">Update</button>
                    </form>

                    <!-- Delete Truck -->
                    <a href="?delete=<?= $row['truck_id'] ?>" style="color: red;">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php
$content = ob_get_clean();
include "../layout/admin_layout.php";
?>