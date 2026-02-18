<?php
// core/toggle_status.php
session_start();
require_once '../config/database.php';

// 1. SECURITY: Only Admins allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../admin_login.php");
    exit();
}

// 2. GET DATA
if (isset($_GET['id']) && isset($_GET['current'])) {
    $id = intval($_GET['id']);
    $current_status = $_GET['current'];

    // 3. FLIP THE STATUS
    // If active -> make inactive. If inactive -> make active.
    $new_status = ($current_status === 'active') ? 'inactive' : 'active';

    // 4. UPDATE DATABASE
    $stmt = $conn->prepare("UPDATE accounts SET status = ? WHERE account_id = ?");
    $stmt->bind_param("si", $new_status, $id);

    if ($stmt->execute()) {
        // Redirect back to where we came from
        $redirect = $_SERVER['HTTP_REFERER'] ?? '../modules/admin/dashboard.php';
        header("Location: $redirect");
        exit();
    } else {
        die("Error updating status: " . $conn->error);
    }
} else {
    header("Location: ../modules/admin/dashboard.php");
    exit();
}
?>