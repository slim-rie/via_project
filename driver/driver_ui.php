<?php
session_start();
$title = "Calendar";
$activePage = "calendar";

// Check if this is an AJAX request for event data
if (isset($_GET['get_events'])) {
    header('Content-Type: application/json');
    
    require '../dbcon.php';
    
    $driver_id = isset($_GET['driver_id']) ? (int)$_GET['driver_id'] : 0;
    
    if ($driver_id === 0) {
        echo json_encode(['error' => 'Invalid driver ID']);
        exit();
    }

    try {
        $stmt = $con->prepare("
            SELECT s.start_time, s.end_time, t.truck_no, c.full_name
            FROM schedules s
            JOIN trucks t ON s.truck_id = t.truck_id
            JOIN customers c ON s.customer_id = c.customer_id
            WHERE s.driver_id = ?
        ");
        $stmt->bind_param("i", $driver_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = [
                'title' => "{$row['truck_no']} - {$row['full_name']}",
                'start' => $row['start_time'],
                'end' => $row['end_time'],
                'color' => '#28a745'
            ];
        }

        // If no events, show a placeholder
        if (empty($events)) {
            $events[] = [
                'title' => 'No bookings found',
                'start' => date('Y-m-d'),
                'color' => '#ffc107',
                'allDay' => true
            ];
        }

        echo json_encode($events);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// Normal page load
if (!isset($_SESSION['driver_id'])) {
    header("Location: ../login.php");
    exit();
}

$driver_id = $_SESSION['driver_id'];
ob_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Calendar</title>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.css' rel='stylesheet' />
</head>
<body>
    <h1>Driver Calendar - Your Bookings</h1>
    <div id='calendar'></div>

    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js'></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: {
                url: window.location.href,
                extraParams: {
                    get_events: true,
                    driver_id: <?php echo $driver_id; ?>
                },
                failure: function(error) {
                    console.error('Error loading events:', error);
                    alert('Error loading calendar events. Please refresh the page.');
                }
            },
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            },
            eventClick: function(info) {
                alert(
                    'Booking Details:\n\n' +
                    'Truck: ' + info.event.title.split(' - ')[0] + '\n' +
                    'Customer: ' + info.event.title.split(' - ')[1] + '\n' +
                    'Start: ' + info.event.start.toLocaleString() + '\n' +
                    'End: ' + (info.event.end ? info.event.end.toLocaleString() : 'N/A')
                );
            }
        });
        calendar.render();
    });
    </script>
</body>
</html>

<?php
$content = ob_get_clean();
include "../layout/driver_layout.php";
?>