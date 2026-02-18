<?php
// core/auth_admin.php
session_start();
require_once '../config/database.php';

if (isset($_POST['btn_login'])) {
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // 1. Check for Admin Account
    $sql = "SELECT account_id, password, role, status FROM accounts WHERE username = ? AND role = 'admin' LIMIT 1";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            if ($row['status'] === 'inactive') {
                header("Location: ../admin_login.php?error=Your account is disabled. Contact the administrator.");
                exit();
            }

            // 2. Verify Password (Simple check for now, use password_verify in production)
            if ($password == $row['password']) {
                
                $_SESSION['account_id'] = $row['account_id'];
                $_SESSION['role']       = 'admin';
                $_SESSION['username']   = $username;

                header("Location: ../modules/admin/dashboard.php");
                exit();
            } else {
                header("Location: ../admin_login.php?error=Access Denied: Invalid Credentials");
                exit();
            }
        } else {
            header("Location: ../admin_login.php?error=Access Denied: Not an Admin Account");
            exit();
        }
    }
}
header("Location: ../admin_login.php");
?>