<?php
session_start();
require_once '../config/database.php';

if (isset($_POST['btn_teller_login'])) {
    
    $username = trim($_POST['username']);
    $password = $_POST['password']; // Removed trim() to ensure exact match with hash
    $sql = "SELECT 
                a.account_id, 
                a.username, 
                a.password, 
                a.role, 
                a.force_change,
                p.profile_id, 
                p.first_name, 
                p.last_name 
            FROM accounts a
            INNER JOIN profiles p ON a.profile_id = p.profile_id
            WHERE a.username = ? LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Allow both Hashed password AND Plain text password (for testing)
    if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
        
        if ($user['role'] === 'customer') {
            header("Location: ../teller_login.php?error=Access Denied: You do not have staff privileges.");
            exit();
        }

        $_SESSION['account_id'] = $user['account_id'];
        $_SESSION['profile_id'] = $user['profile_id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['full_name']  = $user['first_name'] . " " . $user['last_name'];
        $_SESSION['role']       = $user['role'];

        // Check Force Change
        if ($user['force_change'] == 1) {
            header("Location: ../modules/teller/force_password_change.php");
            exit();
        }
        // ------------------------------------

        header("Location: ../modules/teller/dashboard.php");
        exit();

    } else {
        header("Location: ../teller_login.php?error=Invalid Username or Password");
        exit();
    }

} else {
    header("Location: ../teller_login.php");
    exit();
}
?>