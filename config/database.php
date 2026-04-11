<?php
// Set Timezone to South East Asia (Manila)
date_default_timezone_set('Asia/Manila');

$host = "localhost";
$user = "root";
$pass = "DREAMTEAM";
$dbname = "armatech";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}