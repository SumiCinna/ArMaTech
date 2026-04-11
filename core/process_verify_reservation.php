<?php
// core/process_verify_reservation.php
session_start();
require_once '../config/database.php';

// Security check: Only Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../admin_login.php");
    exit();
}

if (isset($_POST['action'])) {
    
    $action         = $_POST['action'];
    $reservation_id = intval($_POST['reservation_id']);
    $shop_id        = intval($_POST['shop_id']);

    if ($action === 'approve') {
        $sql  = "UPDATE shop_reservations SET status = 'approved' WHERE reservation_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $reservation_id);

        if ($stmt->execute()) {
            header("Location: ../modules/admin/verify_reservations.php?filter=approved&msg=Reservation+successfully+APPROVED.");
            exit();
        }

    } elseif ($action === 'reject') {
        $sql_reject = "UPDATE shop_reservations SET status = 'rejected' WHERE reservation_id = ?";
        $stmt_reject = $conn->prepare($sql_reject);
        $stmt_reject->bind_param("i", $reservation_id);
        $stmt_reject->execute();

        // Put item back on the market
        $sql_shop  = "UPDATE shop_items SET shop_status = 'available' WHERE shop_id = ?";
        $stmt_shop = $conn->prepare($sql_shop);
        $stmt_shop->bind_param("i", $shop_id);
        $stmt_shop->execute();

        header("Location: ../modules/admin/verify_reservations.php?filter=rejected&msg=Reservation+REJECTED.+Item+is+back+on+the+market.");
        exit();
    }

} else {
    header("Location: ../modules/admin/verify_reservations.php");
    exit();
}
?>