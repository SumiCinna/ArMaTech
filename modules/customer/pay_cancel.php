<?php
date_default_timezone_set('Asia/Manila');
require_once '../../config/database.php';
session_start();

// pay_cancel.php — PayMongo redirects here when payment is cancelled or fails

$reservation_id = (int)($_GET['reservation_id'] ?? 0);
$_SESSION['toast_error'] = "Payment was cancelled or failed. Your reservation is still held — you can try paying again.";
header('Location: my_reservations.php' . ($reservation_id ? '#res-' . $reservation_id : ''));
exit;