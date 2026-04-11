<?php
// core/process_reservation.php
session_start();
require_once '../config/database.php';

// Security check
if (!isset($_SESSION['account_id'])) {
    header("Location: ../customer_login.php");
    exit();
}

if (!isset($_POST['btn_reserve'])) {
    header("Location: ../modules/customer/shop.php");
    exit();
}

// 1. Get Customer Profile ID
$stmt_prof = $conn->prepare("SELECT profile_id FROM accounts WHERE account_id = ?");
$stmt_prof->bind_param("i", $_SESSION['account_id']);
$stmt_prof->execute();
$result_prof = $stmt_prof->get_result()->fetch_assoc();

if (!$result_prof) {
    header("Location: ../modules/customer/shop.php?error=Account+profile+not+found.");
    exit();
}

$profile_id = $result_prof['profile_id'];

// 2. Gather and sanitize form data
$shop_id            = intval($_POST['shop_id']);
$reservation_amount = floatval($_POST['reservation_amount']);

if (!$shop_id || $reservation_amount <= 0) {
    header("Location: ../modules/customer/shop.php?error=Invalid+reservation+data.");
    exit();
}

// 3. Make sure the item is still available (re-check at this moment)
$stmt_check = $conn->prepare("SELECT shop_status FROM shop_items WHERE shop_id = ?");
$stmt_check->bind_param("i", $shop_id);
$stmt_check->execute();
$item = $stmt_check->get_result()->fetch_assoc();

if (!$item || $item['shop_status'] !== 'available') {
    header("Location: ../modules/customer/shop.php?error=Sorry,+this+item+is+no+longer+available.");
    exit();
}

// 4. Check if customer already has an active reservation for this item
$stmt_dup = $conn->prepare("
    SELECT reservation_id FROM shop_reservations
    WHERE shop_id = ? AND customer_profile_id = ?
    AND status IN ('pending_payment', 'pending_verification', 'pending', 'approved')
    LIMIT 1
");
$stmt_dup->bind_param("ii", $shop_id, $profile_id);
$stmt_dup->execute();
$dup = $stmt_dup->get_result()->fetch_assoc();

if ($dup) {
    // Already has a reservation — send straight to payment
    header("Location: ../modules/customer/pay.php?reservation_id=" . $dup['reservation_id']);
    exit();
}

// 5. Insert reservation — item stays 'available' until payment is confirmed
$stmt_res = $conn->prepare("
    INSERT INTO shop_reservations 
        (shop_id, customer_profile_id, reservation_amount, status, payment_status) 
    VALUES 
        (?, ?, ?, 'pending_payment', 'unpaid')
");

if (!$stmt_res) {
    header("Location: ../modules/customer/shop.php?error=DB+setup+error:+" . urlencode($conn->error));
    exit();
}

$stmt_res->bind_param("iid", $shop_id, $profile_id, $reservation_amount);

if (!$stmt_res->execute()) {
    header("Location: ../modules/customer/shop.php?error=Reservation+failed:+" . urlencode($stmt_res->error));
    exit();
}

$reservation_id = $conn->insert_id;



// 6. Redirect to PayMongo checkout
header("Location: ../modules/customer/pay.php?reservation_id=" . $reservation_id);
exit();
?>