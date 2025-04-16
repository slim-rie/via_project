<?php
session_start();
$title = "Available Bookings";
$activePage = "available";

if (isset($_GET['get_availability'])) {
    header('Content-Type: application/json');
    require '../dbcon.php';

    // Date range (next 60 days)
    $start_date = date("Y-m-d");
    $end_date = date("Y-m-d", strtotime("+60 days"));

    // Get all booked trucks by date
    $sql = "SELECT 
                t.truck_id,
                t.truck_no,
                DATE(s.start_time) as booking_date
            FROM schedules s
            JOIN trucks t ON s.truck_id = t.truck_id
            WHERE s.start_time BETWEEN ? AND ?";

    $stmt = $con->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $booked_trucks = [];
    while ($row = $result->fetch_assoc()) {
        $booked_trucks[$row['booking_date']][] = $row['truck_id'];
    }
    $stmt->close();

    // Get all active trucks
    $trucks = [];
    $truck_result = $con->query("SELECT truck_id, truck_no FROM trucks WHERE status = 'Available'");
    $truck_id_map = [];
    while ($row = $truck_result->fetch_assoc()) {
        $trucks[] = $row['truck_id'];
        $truck_id_map[$row['truck_id']] = $row['truck_no'];
    }
    $total_trucks = count($trucks);

    // Generate calendar events
    $events = [];
    $current = strtotime($start_date);
    $end = strtotime($end_date);

    while ($current <= $end) {
        $date = date('Y-m-d', $current);
        $booked_ids = isset($booked_trucks[$date]) ? $booked_trucks[$date] : [];
        $available_ids = array_diff($trucks, $booked_ids);
        $available_count = count($available_ids);

        if ($available_count > 0) {
            $available_nos = array_map(function ($id) use ($truck_id_map) {
                return $truck_id_map[$id];
            }, $available_ids);

            $truck_display = implode(', ', $available_nos);
            $title = "Available Truck(s): $truck_display";

            $color = $available_count == $total_trucks ? '#a8ffa8' : ($available_count > 1 ? '#90EE90' : '#FFD700');

            $events[] = [
                'title' => $title,
                'start' => $date,
                'display' => 'background',
                'color' => $color,
                'textColor' => '#000',
                'extendedProps' => [
                    'available' => $available_count,
                    'trucks' => $available_nos,
                    'date' => $date
                ]
            ];
        }
        $current = strtotime('+1 day', $current);
    }

    // If no availability
    if (empty($events)) {
        $events[] = [
            'title' => 'No availability - please check back later',
            'start' => date('Y-m-d'),
            'color' => '#ffcccc',
            'allDay' => true
        ];
    }

    echo json_encode($events);
    exit();
}


// Normal page load
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

ob_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Truck Bookings</title>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.css' rel='stylesheet' />
    <style>
        #calendar {
            max-width: 1100px;
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            margin-top: 20px;
            color: #333;
        }

        .legend {
            display: flex;
            justify-content: center;
            margin: 20px 0;
            gap: 20px;
        }

        .legend-item {
            display: flex;
            align-items: center;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            margin-right: 8px;
            border-radius: 3px;
        }

        .fc-day:hover {
            cursor: pointer;
        }
    </style>
</head>

<body>
    <h1>Available Truck Booking Dates</h1>

    <div class="legend">
        <div class="legend-item">
            <div class="legend-color" style="background-color: #a8ffa8;"></div>
            <span>All Trucks Available</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background-color: #90EE90;"></div>
            <span>Multiple Trucks Available</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background-color: #FFD700;"></div>
            <span>1 Truck Available</span>
        </div>
    </div>

    <div id='calendar'></div>

    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },
                events: {
                    url: window.location.href,
                    extraParams: {
                        get_availability: true
                    },
                    failure: function(error) {
                        console.error('Error loading availability:', error);
                        alert('Error loading available dates. Please try again.');
                    }
                },
                dateClick: function(info) {
                    const events = calendar.getEvents();
                    const matched = events.find(event =>
                        event.startStr === info.dateStr &&
                        event.display === 'background' &&
                        event.extendedProps.available > 0
                    );

                    if (matched) {
                        const truckList = matched.extendedProps.trucks.join(', ');
                        if (confirm(`Available Truck(s) on ${info.dateStr}:\n${truckList}\n\nProceed to book?`)) {
                            window.location.href = 'booking.php?date=' + info.dateStr;
                        }
                    } else {
                        alert('No trucks available on ' + info.dateStr + '. Please select a highlighted date.');
                    }
                },

                eventDidMount: function(info) {
                    if (info.event.display === 'background') {
                        info.el.style.cursor = 'pointer';
                        info.el.title = info.event.title;
                    }
                }
            });
            calendar.render();
        });
    </script>
</body>

</html>

<?php
$content = ob_get_clean();
include "../layout/client_layout.php";
?>