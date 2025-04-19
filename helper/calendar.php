<?php
session_start();
$title = "Helper Calendar";
$activePage = "calendar";
require '../dbcon.php';

// Check if this is an AJAX request for event data
if (isset($_GET['get_events'])) {
    header('Content-Type: application/json');
    
    // Get the logged-in user's ID
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    
    if ($user_id === 0) {
        echo json_encode(['error' => 'Invalid user ID']);
        exit();
    }

    try {
        // First get the helper_id from the helpers table
        $helper_stmt = $con->prepare("SELECT helper_id FROM helpers WHERE user_id = ?");
        $helper_stmt->bind_param("i", $user_id);
        $helper_stmt->execute();
        $helper_result = $helper_stmt->get_result();
        
        if ($helper_result->num_rows === 0) {
            echo json_encode(['error' => 'Helper not found']);
            exit();
        }
        
        $helper_row = $helper_result->fetch_assoc();
        $helper_id = $helper_row['helper_id'];
        
        // Now get the schedules for this helper
        $stmt = $con->prepare("
            SELECT s.schedule_id, s.start_time, s.end_time, t.truck_no, 
                   c.full_name AS customer_name, d.full_name AS driver_name, 
                   dl.delivery_status, s.destination, s.pick_up
            FROM schedules s
            JOIN trucks t ON s.truck_id = t.truck_id
            JOIN customers c ON s.customer_id = c.customer_id
            JOIN drivers d ON s.driver_id = d.driver_id
            LEFT JOIN deliveries dl ON s.schedule_id = dl.schedule_id
            WHERE s.helper_id = ?
            ORDER BY s.start_time ASC
        ");
        $stmt->bind_param("i", $helper_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $events = [];
        while ($row = $result->fetch_assoc()) {
            // Set color based on delivery status
            $color = '#6c757d'; // Default gray
            $status = $row['delivery_status'] ?? 'Scheduled';
            
            if ($status === 'In Transit') {
                $color = '#17a2b8'; // Blue for in transit
            } elseif ($status === 'Completed' || $status === 'Delivered') {
                $color = '#28a745'; // Green for completed
            } elseif ($status === 'Pending') {
                $color = '#ffc107'; // Yellow for pending
            } elseif ($status === 'Cancelled') {
                $color = '#dc3545'; // Red for cancelled
            }

            $events[] = [
                'id' => $row['schedule_id'],
                'title' => "Truck: {$row['truck_no']} - {$row['customer_name']}",
                'start' => $row['start_time'],
                'end' => $row['end_time'],
                'color' => $color,
                'extendedProps' => [
                    'driver' => $row['driver_name'],
                    'status' => $status,
                    'destination' => $row['destination'],
                    'pick_up' => $row['pick_up']
                ]
            ];
        }

        // If no events, show a placeholder
        if (empty($events)) {
            $events[] = [
                'title' => 'No schedules found',
                'start' => date('Y-m-d'),
                'color' => '#6c757d',
                'allDay' => true,
                'display' => 'background'
            ];
        }

        echo json_encode($events);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// Normal page load
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'helper') {
    header("Location: ../login.php");
    exit();
}

ob_start();
?>

<!-- Calendar Content -->
<div class="container-fluid">
    <h1 class="mb-4">My Delivery Schedule</h1>
    
    <!-- Status Legend -->
    <div class="status-legend mb-4 d-flex flex-wrap">
        <div class="status-item d-flex align-items-center me-3 mb-2">
            <div class="status-color me-2" style="background-color: #6c757d; width: 20px; height: 20px;"></div>
            <span>Scheduled</span>
        </div>
        <div class="status-item d-flex align-items-center me-3 mb-2">
            <div class="status-color me-2" style="background-color: #ffc107; width: 20px; height: 20px;"></div>
            <span>Pending</span>
        </div>
        <div class="status-item d-flex align-items-center me-3 mb-2">
            <div class="status-color me-2" style="background-color: #17a2b8; width: 20px; height: 20px;"></div>
            <span>In Transit</span>
        </div>
        <div class="status-item d-flex align-items-center me-3 mb-2">
            <div class="status-color me-2" style="background-color: #28a745; width: 20px; height: 20px;"></div>
            <span>Completed</span>
        </div>
        <div class="status-item d-flex align-items-center mb-2">
            <div class="status-color me-2" style="background-color: #dc3545; width: 20px; height: 20px;"></div>
            <span>Cancelled</span>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div id='calendar'></div>
        </div>
    </div>
</div>

<!-- FullCalendar JS -->
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
                get_events: true
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
            let event = info.event;
            let details = `
                <div class="event-details">
                    <h5>Delivery Details</h5>
                    <p><strong>Truck:</strong> ${event.title.split(' - ')[0].replace('Truck: ', '')}</p>
                    <p><strong>Customer:</strong> ${event.title.split(' - ')[1]}</p>
                    <p><strong>Driver:</strong> ${event.extendedProps.driver}</p>
                    <p><strong>Status:</strong> ${event.extendedProps.status}</p>
                    <p><strong>Pick Up:</strong> ${event.extendedProps.pick_up}</p>
                    <p><strong>Destination:</strong> ${event.extendedProps.destination}</p>
                    <p><strong>Start:</strong> ${event.start ? event.start.toLocaleString() : 'N/A'}</p>
                    <p><strong>End:</strong> ${event.end ? event.end.toLocaleString() : 'N/A'}</p>
                </div>
            `;
            
            // Create a modal or use alert for better formatting
            Swal.fire({
                title: 'Delivery Details',
                html: details,
                confirmButtonText: 'Close'
            });
        }
    });
    calendar.render();
});
</script>

<?php
$content = ob_get_clean();
include "../layout/helper_layout.php";
?>