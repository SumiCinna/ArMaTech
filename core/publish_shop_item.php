<?php
// core/publish_shop_item.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../admin_login.php");
    exit();
}

if (isset($_POST['btn_publish'])) {
    $transaction_id = intval($_POST['transaction_id']);
    $item_id = intval($_POST['item_id']);
    $selling_price = floatval($_POST['selling_price']);

    $sql = "INSERT INTO shop_items (transaction_id, item_id, selling_price, shop_status) 
            VALUES (?, ?, ?, 'available')";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iid", $transaction_id, $item_id, $selling_price);

    if ($stmt->execute()) {
        header("Location: ../modules/admin/ready_for_sale.php?msg=Item published to shop successfully!");
    } else {
        header("Location: ../modules/admin/ready_for_sale.php?error=Failed to publish item.");
    }
}
?>