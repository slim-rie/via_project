<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= isset($title) ? $title : "Admin Panel" ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" />
    <style>
        body {
            font-family: 'Bruno Ace', sans-serif !important;
            background-color: #f8f9fa;
            color: #364C84;
            transition: margin-left 0.3s ease-in-out;
            padding-top: 56px;
            /* navbar height */
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background-color: #364C84;
            z-index: 1040;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background: linear-gradient(180deg, #364C84, #4A5C9B);
            position: fixed;
            top: 56px;
            /* below navbar */
            left: 0;
            padding: 20px;
            color: white;
            transition: width 0.3s ease-in-out;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            z-index: 1030;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .toggle-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: transform 0.1s;
        }

        .sidebar.collapsed .toggle-btn {
            transform: rotate(180deg);
        }

        .sidebar-header span {
            transition: opacity 0.3s ease-in-out, margin-left 0.3s ease-in-out;
        }

        .sidebar.collapsed .sidebar-header span {
            opacity: 0;
            margin-left: -20px;
        }

        .nav-link {
            color: white;
            font-weight: 500;
            padding: 12px 15px;
            transition: 0.3s;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 15px;
            white-space: nowrap;
        }

        .nav-link i {
            font-size: 1.4rem;
            min-width: 10px;
            text-align: center;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
            color: #FFD700 !important;
        }

        .nav-link.active {
            background-color: #2F3E6E;
        }

        .sidebar.collapsed .nav-link span {
            opacity: 0;
            width: 0;
        }

        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding: 12px 0;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s;
        }

        .sidebar.collapsed+.main-content {
            margin-left: 70px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                padding: 10px;
            }

            .sidebar-header span {
                display: none;
            }

            .main-content {
                margin-left: 70px;
            }

            .nav-link {
                justify-content: center;
                padding: 10px;
            }

            .nav-link span {
                display: none;
            }
        }
        /* Fixed header table styles */
        .fixed-header {
            position: relative;
            width: 100%;
            overflow: auto;
            height: 100%; /* Set the height as needed */
        }

        .fixed-header table {
            width: 100%;
            border-collapse: collapse;
        }

        .fixed-header th {
            position: sticky;
            top: 0;
            background-color: #f8f9fa; /* Same as body background */
            z-index: 10;
        }

        th,
        td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: left;
        }
    </style>
</head>

<body>
    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">JOREDANE TRUCKING SERVICES</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTop">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarTop">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link text-white" href="#"><i class="bi bi-bell"></i> Notifications</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="../admin/profile.php"><i class="bi bi-person-circle"></i> Profile</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <button class="toggle-btn" onclick="toggleSidebar()"><i class="bi bi-chevron-left"></i></button>
            <span>Menu</span>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item"><a href="dashboard.php" class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i> <span>Dashboard</span></a></li>
            <li class="nav-item"><a href="schedules.php" class="nav-link <?= $activePage === 'schedules' ? 'active' : '' ?>"><i class="bi bi-box-seam"></i> <span>Schedules</span></a></li>
            <li class="nav-item"><a href="reports.php" class="nav-link <?= $activePage === 'reports' ? 'active' : '' ?>"><i class="bi bi-receipt"></i> <span>Reports</span></a></li>
            <li class="nav-item"><a href="clients.php" class="nav-link <?= $activePage === 'clients' ? 'active' : '' ?>"><i class="bi bi-people"></i> <span>Clients</span></a></li>
            <li class="nav-item"><a href="employees.php" class="nav-link <?= $activePage === 'employees' ? 'active' : '' ?>"><i class="bi bi-person-badge"></i> <span>Employees</span></a></li>
            <li class="nav-item"><a href="pending_bookings.php" class="nav-link <?= $activePage === 'pending_bookings' ? 'active' : '' ?>"><i class="bi bi-person-badge"></i> <span>Booking Request</span></a></li>

        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php if (isset($content)) echo $content; ?>
    </div>

    <!-- Scripts -->
    <script>
        function toggleSidebar() {
            document.getElementById("sidebar").classList.toggle("collapsed");
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>