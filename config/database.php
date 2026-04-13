<?php
// Set Timezone to South East Asia (Manila)
date_default_timezone_set('Asia/Manila');

$host = "localhost";
$user = "root";
$dbname = "armatech";

// lagay nyo na lang dito data base password nyo
$passwords_to_try = ["200427", "", "DREAMTEAM"]; 

$conn = null;
mysqli_report(MYSQLI_REPORT_OFF);

foreach ($passwords_to_try as $pass) {
    $conn = @new mysqli($host, $user, $pass, $dbname);
    if (!$conn->connect_error) {
        break;
    }
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($conn->connect_error) {
    die("Database connection failed with all attempted passwords. Did you start MySQL?");
}
?>