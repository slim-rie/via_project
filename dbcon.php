<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$con = mysqli_connect("localhost", "root", "", "jordane_trucking_services");

if (!$con) {
    die("Connection error: " . mysqli_connect_error());
}
