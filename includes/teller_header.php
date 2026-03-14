<?php
require_once '../../config/database.php';
require_once '../../core/auto_expire.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Check Login
if (!isset($_SESSION['account_id']) || $_SESSION['role'] !== 'teller') {
    header("Location: ../../teller_login.php");
    exit();
}

// 2. GATEKEEPER: Check Force Change Status
require_once '../../config/database.php';

// We query the DB to be absolutely sure
$gate_sql = "SELECT force_change FROM accounts WHERE account_id = ?";
$gate_stmt = $conn->prepare($gate_sql);
$gate_stmt->bind_param("i", $_SESSION['account_id']);
$gate_stmt->execute();
$gate_res = $gate_stmt->get_result()->fetch_assoc();

$current_page = basename($_SERVER['PHP_SELF']);

// If force_change is 1 AND they are NOT on the change password page
if ($gate_res['force_change'] == 1 && $current_page != 'force_password_change.php') {
    // Force redirect back to security page
    header("Location: ../teller/force_password_change.php");
    exit();
}

$activePage = basename($_SERVER['PHP_SELF'], ".php");
$fullname = $_SESSION['full_name'] ?? 'Teller';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teller Portal | ArMaTech</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { background-color: #f4f7fa; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        
        /* Sidebar Styling */
        #sidebar-wrapper {
            min-height: 100vh;
            width: 260px;
            margin-left: -260px;
            transition: margin 0.25s ease-out;
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            position: fixed;
            top: 0; bottom: 0; z-index: 1000;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
        }
        #sidebar-wrapper.toggled { margin-left: 0; }
        #page-content-wrapper { width: 100%; transition: margin 0.25s ease-out; margin-left: 0; display: flex; flex-direction: column; min-height: 100vh; }
        @media (min-width: 768px) {
            #sidebar-wrapper { margin-left: 0; }
            #page-content-wrapper { margin-left: 260px; }
        }

        .sidebar-heading { 
            padding: 1.5rem 1.5rem; 
            font-size: 1.25rem; 
            color: #fff; 
            font-weight: 800; 
            background: rgba(255,255,255,0.03); 
            border-bottom: 1px solid rgba(255,255,255,0.05);
            letter-spacing: -0.5px;
        }
        
        .list-group-item { 
            background: transparent; 
            color: rgba(255, 255, 255, 0.8); 
            border: none; 
            padding: 16px 24px; 
            font-weight: 500;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }
        .list-group-item:hover { 
            background: rgba(255,255,255,0.05); 
            color: #f8fafc; 
            padding-left: 28px;
        }
        .list-group-item.active { 
            background: linear-gradient(90deg, rgba(13, 202, 240, 0.15) 0%, transparent 100%); 
            color: #0dcaf0; 
            border-left-color: #0dcaf0;
            font-weight: 700;
        }
        .list-group-item i { width: 24px; text-align: center; margin-right: 10px; }
        .sidebar-label { color: rgba(255, 255, 255, 0.5); font-size: 0.75rem; letter-spacing: 1px; }
    </style>
</head>
<body>

<div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <div class="border-end" id="sidebar-wrapper">
        <div class="sidebar-heading">
            <i class="bi bi-shop-window me-2 text-info"></i> ArMaTech 
            <small class="d-block fs-6 fw-normal opacity-50">Teller Portal</small>
        </div>
        <div class="list-group list-group-flush mt-3">
            <a href="dashboard.php" class="list-group-item <?php echo ($activePage == 'dashboard') ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            
            <div class="sidebar-label text-uppercase fw-bold px-4 mt-3 mb-2">Operations</div>
            
            <a href="new_pawn.php" class="list-group-item <?php echo ($activePage == 'new_pawn') ? 'active' : ''; ?>">
                <i class="bi bi-plus-circle"></i> New Pawn
            </a>
            <a href="redeem.php" class="list-group-item <?php echo ($activePage == 'redeem') ? 'active' : ''; ?>">
                <i class="bi bi-cash-stack"></i> Redeem/Renew
            </a>
            <a href="transactions.php" class="list-group-item <?php echo ($activePage == 'transactions') ? 'active' : ''; ?>">
                <i class="bi bi-file-earmark-text"></i> Transactions
            </a>
            <a href="claim_reservation.php" class="list-group-item <?php echo ($activePage == 'claim_reservation') ? 'active' : ''; ?>">
                <i class="bi bi-box-seam"></i> Claim Reservations
            </a>

            <div class="sidebar-label text-uppercase fw-bold px-4 mt-3 mb-2">Account</div>
            
            <a href="profile.php" class="list-group-item <?php echo ($activePage == 'profile') ? 'active' : ''; ?>">
                <i class="bi bi-person-gear"></i> My Profile
            </a>
            
            <div class="mt-5 px-3">
                <button type="button" class="btn btn-danger w-100 fw-bold" data-bs-toggle="modal" data-bs-target="#logoutModal">
                    <i class="bi bi-power me-2"></i> Logout
                </button>
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm px-4 py-3">
            <button class="btn btn-outline-secondary d-md-none" id="menu-toggle"><i class="bi bi-list"></i></button>
            <span class="ms-auto fw-bold text-secondary">Hello, <?php echo $fullname; ?></span>
        </nav>
        
        <div class="container-fluid px-4 py-4 flex-grow-1">