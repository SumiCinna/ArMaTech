<?php
// core/auth_customer.php
session_start();
require_once '../config/database.php';

if (isset($_POST['btn_login'])) {
    
    $username = trim($_POST['username']);
    $password = $_POST['password']; // In real app, this should be hashed

    // 1. Basic Validation
    if (empty($username) || empty($password)) {
        header("Location: ../customer_login.php?error=Credentials required");
        exit();
    }

    // 2. Check Database
    // We specifically check role = 'customer' so Tellers/Admins can't log in here
    $sql = "SELECT a.account_id, a.password, a.role, a.status, a.force_change, p.profile_id, p.first_name, p.last_name, p.public_id 
            FROM accounts a 
            JOIN profiles p ON a.profile_id = p.profile_id 
            WHERE a.username = ? AND a.role = 'customer' 
            LIMIT 1";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            // Check Status
            if ($row['status'] === 'inactive') {
                header("Location: ../customer_login.php?error=Your account is disabled. Contact the administrator.");
                exit();
            }

            // 3. Verify Password
            // Note: If you haven't hashed passwords yet, use: if ($password == $row['password']) 
            // Ideally: if (password_verify($password, $row['password']))
            if (password_verify($password, $row['password']) || $password === $row['password']) { 
                
                // Set Session
                $_SESSION['account_id'] = $row['account_id'];
                $_SESSION['profile_id'] = $row['profile_id'];
                $_SESSION['role']       = 'customer';
                $_SESSION['username']   = $row['username']; // or public_id
                $_SESSION['public_id']  = $row['public_id'];
                $_SESSION['fullname']   = $row['first_name'] . ' ' . $row['last_name'];

                // Check if they need to change password (force_change == 1)
                if ($row['force_change'] == 1) {
                    header("Location: ../modules/customer/force_password_change.php");
                    exit();
                }

                // Success Redirect
                header("Location: ../modules/customer/dashboard.php");
                exit();

            } else {
                header("Location: ../customer_login.php?error=Incorrect password");
                exit();
            }
        } else {
            header("Location: ../customer_login.php?error=Account not found");
            exit();
        }
    } else {
        header("Location: ../customer_login.php?error=Database error");
        exit();
    }
} else {
    header("Location: ../customer_login.php");
    exit();
}
?>