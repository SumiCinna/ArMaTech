<?php
// core/process_force_change_teller.php
session_start();
require_once '../config/database.php';

// Security Check
if (!isset($_SESSION['account_id']) || $_SESSION['role'] !== 'teller') {
    header("Location: ../teller_login.php");
    exit();
}

if (isset($_POST['btn_update'])) {
    
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    $account_id = $_SESSION['account_id'];

    // 1. Validation
    if (strlen($new_pass) < 6) {
        header("Location: ../modules/teller/force_password_change.php?error=Password must be at least 6 characters");
        exit();
    }
    if ($new_pass !== $confirm_pass) {
        header("Location: ../modules/teller/force_password_change.php?error=Passwords do not match");
        exit();
    }

    // 2. Update Database
    // Hash the password before saving
    $password_to_save = password_hash($new_pass, PASSWORD_DEFAULT); 

    // Update password AND set force_change to 0
    $sql = "UPDATE accounts SET password = ?, force_change = 0 WHERE account_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $password_to_save, $account_id); 
    
    if ($stmt->execute()) {
        // Success! Redirect to Teller Dashboard
        header("Location: ../modules/teller/dashboard.php?msg=Password updated successfully");
        exit();
    } else {
        header("Location: ../modules/teller/force_password_change.php?error=Database error");
        exit();
    }
}
?>