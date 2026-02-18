<?php
// core/admin_reset_password.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../admin_login.php");
    exit();
}

if (isset($_POST['btn_reset'])) {
    $id = $_POST['account_id'];
    $default_pass = "Armatech123";
    // If using hash: $hash = password_hash($default_pass, PASSWORD_DEFAULT);

    // Reset Password AND force them to change it again
    $sql = "UPDATE accounts SET password = ?, force_change = 1 WHERE account_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $default_pass, $id);
    
    if ($stmt->execute()) {
        header("Location: ../modules/admin/manage_customers.php?msg=Password reset successfully");
    } else {
        header("Location: ../modules/admin/manage_customers.php?error=Failed to reset");
    }
}
?>