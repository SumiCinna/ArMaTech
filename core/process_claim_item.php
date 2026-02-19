<?php
// core/process_claim_item.php
session_start();
require_once '../config/database.php';

// Security check: Must be Teller
if (!isset($_SESSION['account_id']) || $_SESSION['role'] !== 'teller') {
    header("Location: ../teller_login.php");
    exit();
}

if (isset($_POST['btn_complete_claim'])) {
    
    $teller_id = $_SESSION['account_id']; // The teller processing this
    
    $reservation_id = intval($_POST['reservation_id']);
    $shop_id = intval($_POST['shop_id']);
    $transaction_id = intval($_POST['transaction_id']);
    $customer_profile_id = intval($_POST['customer_profile_id']);
    $balance_amount = floatval($_POST['balance_amount']);

    // We will use a database transaction to ensure all updates happen safely together
    $conn->begin_transaction();

    try {
        // 1. Update Reservation to 'claimed'
        $sql1 = "UPDATE shop_reservations SET status = 'claimed' WHERE reservation_id = ?";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->bind_param("i", $reservation_id);
        $stmt1->execute();

        // 2. Update Shop Item to 'sold'
        $sql2 = "UPDATE shop_items SET shop_status = 'sold' WHERE shop_id = ?";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("i", $shop_id);
        $stmt2->execute();

        // 3. Close the Original Pawn Transaction (Using your 'auctioned' ENUM)
        $sql3 = "UPDATE transactions SET status = 'auctioned' WHERE transaction_id = ?";
        $stmt3 = $conn->prepare($sql3);
        $stmt3->bind_param("i", $transaction_id);
        $stmt3->execute();

        // 4. Record the final payment in the payments table so it shows in the Admin's Sales Report!
        // (Assuming you have a 'payment_type' column. If it's an ENUM, ensure 'redeem' or 'auction' is allowed)
        $sql4 = "INSERT INTO payments (transaction_id, teller_id, amount_paid, payment_type, date_paid) 
                 VALUES (?, ?, ?, 'full_redemption', NOW())"; // Using 'full_redemption' to match allowed ENUM values
        $stmt4 = $conn->prepare($sql4);
        $stmt4->bind_param("iid", $transaction_id, $teller_id, $balance_amount);
        $stmt4->execute();

        // If everything worked, commit the changes
        $conn->commit();

        header("Location: ../modules/teller/claim_reservation.php?msg=Item successfully released and payment recorded!");
        exit();

    } catch (Exception $e) {
        // If any error happens, rollback all changes to protect data integrity
        $conn->rollback();
        header("Location: ../modules/teller/claim_reservation.php?error=System Error: " . $e->getMessage());
        exit();
    }
} else {
    header("Location: ../modules/teller/claim_reservation.php");
    exit();
}
?>