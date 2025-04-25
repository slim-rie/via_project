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

        /* Table styles from style 2 */
        .filter-form {
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .form-select {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
            background-color: white;
        }

        .fixed-header {
            position: relative;
            width: 100%;
            overflow: auto;
            max-height: 75vh;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            background: white;
        }

        .fixed-header table {
            width: 100%;
            border-collapse: collapse;
        }

        .fixed-header th {
            background-color: #364C84;
            color: white;
            padding: 12px;
            text-align: left;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .fixed-header td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .fixed-header tr:hover {
            background-color: #f5f7ff;
        }

        /* Status badges from style 2 */
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-accepted {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-in-transit {
            background-color: #d4edda;
            color: #155724;
        }

        .status-delivered {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-completed {
            background-color: #e2e3e5;
            color: #383d41;
        }

        /* Action buttons from style 2 */
        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-accept {
            background-color: #17a2b8;
        }

        .btn-start {
            background-color: #28a745;
        }

        .btn-deliver {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-complete {
            background-color: #6c757d;
        }

        .no-action {
            color: #6c757d;
            font-style: italic;
        }

        .no-schedules {
            text-align: center;
            padding: 20px;
            color: #6c757d;
        }

        .action-form {
            margin: 0;
        }

        /* Calendar styles from style 1 */
        #calendar {
            font-family: 'Bruno Ace', sans-serif;
            background-color: white;
            border-radius: 8px;
        }

        .fc-header-toolbar {
            padding: 10px;
            margin-bottom: 10px !important;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        .fc-button {
            background-color: #364C84 !important;
            border-color: #364C84 !important;
            color: white !important;
            padding: 6px 12px;
            font-size: 14px;
            border-radius: 4px !important;
        }

        .fc-button:hover {
            background-color: #2F3E6E !important;
            border-color: #2F3E6E !important;
        }

        .fc-button-active {
            background-color: #1a2a57 !important;
            border-color: #1a2a57 !important;
        }

        .fc-event {
            border-radius: 4px;
            border: none;
            padding: 3px 6px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .fc-event:hover {
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transform: translateY(-1px);
        }

        .fc-event-completed {
            background-color: #28a745;
            border-color: #28a745;
        }

        .fc-event-pending {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #212529;
        }

        .fc-event-in-transit {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }

        .fc-day-today {
            background-color: rgba(54, 76, 132, 0.1) !important;
        }

        .fc-col-header-cell {
            background-color: #364C84;
            color: white;
            padding: 8px 0;
        }

        .fc-daygrid-day {
            transition: background-color 0.3s;
        }

        .fc-daygrid-day:hover {
            background-color: rgba(54, 76, 132, 0.05);
        }

        /* Additional utility styles from style 1 */
        .card {
            border: none;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #364C84;
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }

        .form-control {
            height: 45px;
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #364C84;
            box-shadow: 0 0 0 0.2rem rgba(54, 76, 132, 0.25);
        }

        .btn-primary {
            background-color: #364C84;
            border-color: #364C84;
        }

        .btn-primary:hover {
            background-color: #2F3E6E;
            border-color: #2F3E6E;
        }

        .chart-container {
            width: 100%;
            min-height: 300px;
        }

        #truckChart,
        #driverChart {
            min-height: 400px;
        }

        .table th {
            white-space: nowrap;
        }

        .text-success {
            color: #1cc88a !important;
        }

        .text-danger {
            color: #e74a3b !important;
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
                    <li class="nav-item"><a class="nav-link text-white" href="profile.php"><i class="bi bi-person-circle"></i> Profile</a></li>
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
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="schedules.php" class="nav-link <?= $activePage === 'schedules' ? 'active' : '' ?>">
                    <i class="bi bi-calendar-event"></i> <span>Schedules</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="reports.php" class="nav-link <?= $activePage === 'reports' ? 'active' : '' ?>">
                    <i class="bi bi-graph-up-arrow"></i> <span>Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="clients.php" class="nav-link <?= $activePage === 'clients' ? 'active' : '' ?>">
                    <i class="bi bi-person-lines-fill"></i> <span>Clients</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="employees.php" class="nav-link <?= $activePage === 'employees' ? 'active' : '' ?>">
                    <i class="bi bi-people"></i> <span>Employees</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="payroll_view.php" class="nav-link <?= $activePage === 'payroll' ? 'active' : '' ?>">
                    <i class="bi bi-wallet2"></i> <span>Payrolls</span>
                </a>
            </li>
            <!-- <li class="nav-item">
                <a href="pending_bookings.php" class="nav-link <?= $activePage === 'pending_bookings' ? 'active' : '' ?>">
                    <i class="bi bi-hourglass-split"></i> <span>Booking Request</span>
                </a>
            </li> -->
            <li class="nav-item">
                <a href="trucks.php" class="nav-link <?= $activePage === 'trucks' ? 'active' : '' ?>">
                    <i class="bi bi-truck-front"></i> <span>Trucks</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_request.php" class="nav-link <?= $activePage === 'request' ? 'active' : '' ?>">
                    <i class="bi bi-inbox"></i> <span>Requests</span>
                </a>
            </li>
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