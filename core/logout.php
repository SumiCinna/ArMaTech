<?php
session_start();

$role = $_SESSION['role'] ?? '';

session_unset();
session_destroy();

if ($role === 'teller') {
    header("Location: ../teller_login.php");
} elseif ($role === 'admin') {
    header("Location: ../admin_login.php");
} else {
    header("Location: ../index.php");
}
exit();
?>