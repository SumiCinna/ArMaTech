<?php
// includes/customer_header.php
if (session_status() === PHP_SESSION_NONE) session_start();

// SECURITY: Kick out if not a customer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../../customer_login.php");
    exit();
}

// 2. CHECK FORCE CHANGE STATUS (The Gatekeeper)
require_once '../../config/database.php';

$check_sql = "SELECT force_change FROM accounts WHERE account_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $_SESSION['account_id']);
$check_stmt->execute();
$check_res = $check_stmt->get_result()->fetch_assoc();

$current_page = basename($_SERVER['PHP_SELF']);
if ($check_res && $check_res['force_change'] == 1 && $current_page != 'force_password_change.php') {
    header("Location: force_password_change.php");
    exit();
}

$activePage = basename($_SERVER['PHP_SELF'], ".php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard | ArMaTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        
        /* Modern Navbar */
        .navbar { background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%); }
        .navbar-brand { font-weight: bold; letter-spacing: 1px; }
        
        /* Dashboard Cards */
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.2s;
            overflow: hidden;
        }
        .stat-card:hover { transform: translateY(-5px); }
        
        /* The "Credit Card" Look for Active Items */
        .loan-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-left: 5px solid #0d6efd; /* Blue Default */
            border-radius: 12px;
            margin-bottom: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .loan-card.due-soon { border-left-color: #ffc107; } /* Yellow */
        .loan-card.overdue { border-left-color: #dc3545; } /* Red */
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php"><i class="fa-solid fa-wallet me-2"></i> ArMaTech</a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($activePage == 'dashboard') ? 'active fw-bold' : ''; ?>" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($activePage == 'transactions') ? 'active fw-bold' : ''; ?>" href="transactions.php">Transactions</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">My Profile</a>
                </li>
                <li class="nav-item ms-3">
                    <a href="../../core/logout.php" class="btn btn-sm btn-light text-dark fw-bold px-3 rounded-pill">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>