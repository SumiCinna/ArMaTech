<?php
// core/process_force_change.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['account_id'])) {
    header("Location: ../customer_login.php");
    exit();
}

if (isset($_POST['btn_update'])) {
    
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    $account_id = $_SESSION['account_id'];

    // 1. Validation
    if (strlen($new_pass) < 6) {
        header("Location: ../modules/customer/force_password_change.php?error=Password must be at least 6 characters");
        exit();
    }
    if ($new_pass !== $confirm_pass) {
        header("Location: ../modules/customer/force_password_change.php?error=Passwords do not match");
        exit();
    }

    // 2. Update Database
    $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
    
    // Updating password AND turning off the 'force_change' flag
    $sql = "UPDATE accounts SET password = ?, force_change = 0 WHERE account_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $hashed_password, $account_id); 
    
    if ($stmt->execute()) {
        // Success! Go to Dashboard
        header("Location: ../modules/customer/dashboard.php?msg=Password updated successfully");
        exit();
    } else {
        header("Location: ../modules/customer/force_password_change.php?error=Database error");
        exit();
    }
}
?>