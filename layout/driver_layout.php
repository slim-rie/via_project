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

        /* Enhanced Table Styles */
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
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.95rem;
        }

        .fixed-header th {
            position: sticky;
            top: 0;
            background-color: #364C84;
            color: white;
            padding: 12px 15px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border: none;
            z-index: 10;
        }

        .fixed-header td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: middle;
            color: #444;
        }

        .fixed-header tr:not(:last-child) td {
            border-bottom: 1px solid #e0e0e0;
        }

        .fixed-header tr:hover td {
            background-color: #f5f7ff;
        }

        /* Status Badges */
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
            display: inline-block;
            min-width: 90px;
        }

        .status-scheduled {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .status-pending {
            background-color: #fff8e1;
            color: #ff8f00;
        }

        .status-in-transit {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .status-delivered {
            background-color: #f1f8e9;
            color: #689f38;
        }

        .status-completed {
            background-color: #e8eaf6;
            color: #3949ab;
        }

        /* Action Buttons */
        .btn-action {
            border: none;
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .btn-start {
            background-color: #4caf50;
            color: white;
        }

        .btn-start:hover {
            background-color: #3d8b40;
        }

        .btn-complete {
            background-color: #2196f3;
            color: white;
        }

        .btn-complete:hover {
            background-color: #0b7dda;
        }

        .btn-action i {
            font-size: 0.9rem;
        }

        /* No Schedules Message */
        .no-schedules {
            text-align: center;
            padding: 30px;
            color: #666;
            font-size: 1.1rem;
        }

        /* Responsive Table */
        @media (max-width: 992px) {
            .fixed-header {
                overflow-x: auto;
            }

            .fixed-header table {
                min-width: 900px;
            }
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }

        .page-title {
            color: #364C84;
            font-weight: 600;
            margin: 0;
        }

        .current-date {
            color: #666;
            font-size: 0.95rem;
        }
        /* Profile-specific styles (taking precedence where duplicates exist) */
        .profile-card {
            border-radius: 15px;
            overflow: hidden;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .profile-info-item {
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .profile-info-item:hover {
            background-color: #e9ecef;
            transform: translateY(-2px);
        }

        .info-label {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 250;
        }

        .info-value {
            font-size: 1.1rem;
            color: #364C84;
            font-weight: 300;
            margin-top: 5px;
        }

        .address-value {
            white-space: pre-line;
        }

        .modal-content-profile {
            border-radius: 15px;
            overflow: hidden;
            border: none;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
        }

        .form-control:focus {
            border-color: #364C84;
            box-shadow: 0 0 0 0.25rem rgba(54, 76, 132, 0.25);
        }

        .btn-primary {
            background-color: #364C84;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background-color: #2F3E6E;
        }

        .btn-secondary {
            border-radius: 8px;
            padding: 10px 20px;
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
            <li class="nav-item"><a href="driver_ui.php" class="nav-link <?= $activePage === 'calendar' ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i> <span>Calendar</span></a></li>
            <li class="nav-item"><a href="schedules.php" class="nav-link <?= $activePage === 'schedules' ? 'active' : '' ?>"><i class="bi bi-box-seam"></i> <span>Schedules</span></a></li>
            <li class="nav-item"><a href="payslip.php" class="nav-link <?= $activePage === 'payslip' ? 'active' : '' ?>"><i class="bi bi-receipt"></i> <span>Payslip</span></a></li>
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