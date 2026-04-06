<?php
session_start();
require_once 'config/database.php';
$res = $conn->query("SELECT a.account_id, a.username, p.first_name, p.last_name FROM accounts a JOIN profiles p ON a.profile_id = p.profile_id WHERE a.role='teller' LIMIT 1");
$row = $res->fetch_assoc();
$_SESSION['account_id'] = $row['account_id'];
$_SESSION['role'] = 'teller';
$_SESSION['full_name'] = $row['first_name'] . ' ' . $row['last_name'];
echo "Session set for " . $_SESSION['full_name'];
?>
