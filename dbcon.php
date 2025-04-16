<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$con = mysqli_connect("localhost", "root", "", "trucking_service");

if (!$con) {
    die("Connection error: " . mysqli_connect_error());
}
