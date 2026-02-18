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
$fullname = $_SESSION['fullname'] ?? 'Customer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard | ArMaTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        
        /* Modern Navbar */
        .navbar { 
            background-color: #0f172a; 
            padding-top: 1rem; 
            padding-bottom: 1rem;
        }
        .navbar-brand { 
            font-weight: 800; 
            letter-spacing: -0.5px; 
            font-size: 1.5rem;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.7) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: all 0.2s;
            border-radius: 50px;
        }
        .nav-link:hover {
            color: #fff !important;
            background: rgba(255,255,255,0.1);
        }
        .nav-link.active {
            color: #fff !important;
            background: rgba(255,255,255,0.15);
            font-weight: 600;
        }

        /* User Dropdown */
        .user-dropdown .dropdown-toggle {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 5px 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 50px;
            transition: background 0.2s;
        }
        .user-dropdown .dropdown-toggle:hover {
            background: rgba(255,255,255,0.2);
        }
        
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
        <a class="navbar-brand" href="dashboard.php">
            <i class="fa-solid fa-shield-halved me-2 text-primary"></i> ArMaTech
        </a>
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto align-items-center">
                <li class="nav-item px-1">
                    <a class="nav-link <?php echo ($activePage == 'dashboard') ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="fa-solid fa-grip me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item px-1">
                    <a class="nav-link <?php echo ($activePage == 'transactions') ? 'active' : ''; ?>" href="transactions.php">
                        <i class="fa-solid fa-clock-rotate-left me-1"></i> History
                    </a>
                </li>
                <li class="nav-item px-1">
                    <a class="nav-link <?php echo ($activePage == 'interest_calculator') ? 'active' : ''; ?>" href="interest_calculator.php">
                        <i class="fa-solid fa-calculator me-1"></i> Calculator
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center mt-3 mt-lg-0">
                <div class="dropdown user-dropdown">
                    <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown">
                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold me-2" style="width: 32px; height: 32px; font-size: 0.8rem;">
                            <?php echo substr($fullname, 0, 1); ?>
                        </div>
                        <span class="small fw-bold me-1"><?php echo $fullname; ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 rounded-3 overflow-hidden">
                        <li><h6 class="dropdown-header text-uppercase small fw-bold">My Account</h6></li>
                        <li><a class="dropdown-item py-2" href="profile.php"><i class="fa-solid fa-user-gear me-2 text-muted"></i> Profile Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item py-2 text-danger fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                                <i class="fa-solid fa-power-off me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>