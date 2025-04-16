<?php
$title = "dashboard";
$activePage = "Dashboard";
ob_start();
?>

<h1>Admin Calendar - All Bookings</h1>
<div id='calendar'></div>

<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js'></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        events: 'load_events_admin.php'
    });
    calendar.render();
});
</script>

<?php 
$content = ob_get_clean();
include "../layout/admin_layout.php";
?>
