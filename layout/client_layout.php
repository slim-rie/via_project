<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= isset($title) ? $title : "Admin Panel" ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" />
    <!-- Add this in the head section of your layout file -->
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
            height: 100%;
            /* Set the height as needed */
        }

        .fixed-header table {
            width: 100%;
            border-collapse: collapse;
        }

        .fixed-header th {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            /* Same as body background */
            z-index: 10;
        }

        th,
        td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: left;
        }

        .booking-form {
            max-width: 600px;
            margin: 20px auto;
            background: #f8f8f8;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .booking-form label {
            display: block;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .booking-form input[type="text"],
        .booking-form input[type="datetime-local"],
        .booking-form select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .booking-form button {
            padding: 10px 20px;
            background-color: #007bff;
            border: none;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }

        .booking-form button:hover {
            background-color: #0056b3;
        }

        #bookingModal {
            display: none;
            /* Hidden by default */
            position: fixed;
            z-index: 999;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        #bookingModal.show {
            display: flex;
            /* Show and center content when active */
        }

        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            width: 400px;
            max-width: 90%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        #bookingModal form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        #bookingModal label {
            display: flex;
            flex-direction: column;
            font-weight: bold;
        }

        #bookingModal input,
        #bookingModal select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        #bookingModal button {
            padding: 10px;
            background-color: #28a745;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
        }

        #bookingModal button#closeBookingModal {
            background-color: #dc3545;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 15px;
        }

        .time-display {
            display: flex;
            gap: 20px;
        }

        .time-display>div {
            flex: 1;
        }

        .time-value {
            padding: 8px;
            background: #f5f5f5;
            border-radius: 4px;
            margin-top: 5px;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        /* Aesthetic Delivery Info Styling */
        .info-section {
            height: calc(100vh - 56px);
            padding-left: 250px;
            background: linear-gradient(-45deg, #e3f2fd, #e0f7fa, #fff3e0, #f3e5f5);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        @keyframes gradientBG {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .info-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 25px;
            padding: 40px;
            max-width: 720px;
            width: 100%;
            box-shadow: 0 20px 30px rgba(0, 0, 0, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.4);
            text-align: center;
            animation: fadeIn 1.2s ease-in-out;
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 40px rgba(0, 0, 0, 0.15);
        }

        .info-card h1 {
            color: #364C84;
            margin-bottom: 20px;
            font-size: 2.2rem;
        }

        .tagline {
            font-size: 1.1rem;
            margin-bottom: 30px;
            color: #5c5c5c;
            font-style: italic;
        }

        .info-item {
            font-size: 1.4rem;
            margin-bottom: 20px;
            color: #222;
        }

        .info-item i {
            color: #364C84;
            margin-right: 10px;
        }

        @media (max-width: 768px) {
            .info-section {
                padding-left: 70px;
            }

            .info-card {
                margin: 0 20px;
            }
        }

        .cancel-btn {
            padding: 5px 10px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .cancel-btn:hover {
            background-color: #c82333;
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
            <a class="navbar-brand" href="#">JOREDANE TRUCKING SERVICES | SCHEDULING </a>
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
            <li class="nav-item"><a href="home.php" class="nav-link <?= $activePage === 'home' ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i> <span>Home</span></a></li>
            <li class="nav-item"><a href="available.php" class="nav-link <?= $activePage === 'available' ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i> <span>Available Dates</span></a></li>
            <li class="nav-item"><a href="booking.php" class="nav-link <?= $activePage === 'book' ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i> <span>Start Booking</span></a></li>
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