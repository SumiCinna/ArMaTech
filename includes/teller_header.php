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
    
    <style>
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; }
        
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
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm mb-4">
  <div class="container">
    <a class="navbar-brand fw-bold" href="dashboard.php">
        <i class="bi bi-shop-window me-2 text-info"></i> ArMaTech <small class="opacity-50 fw-normal" style="font-size: 0.6em; letter-spacing: 1px;">TELLER PORTAL</small>
    </a>
    
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#tellerNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="tellerNav">
      <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
        <li class="nav-item px-1">
            <a class="nav-link <?php echo ($activePage == 'dashboard') ? 'active' : ''; ?>" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
        </li>
        <li class="nav-item px-1">
            <a class="nav-link <?php echo ($activePage == 'new_pawn') ? 'active' : ''; ?>" href="new_pawn.php"><i class="bi bi-plus-circle me-1"></i> New Pawn</a>
        </li>
        <li class="nav-item px-1">
            <a class="nav-link <?php echo ($activePage == 'redeem') ? 'active' : ''; ?>" href="redeem.php"><i class="bi bi-cash-stack me-1"></i> Redeem/Renew</a>
        </li>
        <li class="nav-item px-1">
            <a class="nav-link <?php echo ($activePage == 'transactions') ? 'active' : ''; ?>" href="transactions.php"><i class="bi bi-file-earmark-text me-1"></i> Transactions</a>
        </li>
      </ul>
      
      <div class="d-flex align-items-center mt-3 mt-lg-0">
        <div class="dropdown user-dropdown">
            <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown">
                <div class="rounded-circle bg-info d-flex align-items-center justify-content-center text-dark fw-bold me-2" style="width: 32px; height: 32px; font-size: 0.8rem;">
                    <?php echo substr($fullname, 0, 1); ?>
                </div>
                <span class="small fw-bold me-1"><?php echo $fullname; ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 rounded-3 overflow-hidden">
                <li><h6 class="dropdown-header text-uppercase small fw-bold">Staff Account</h6></li>
                <li><a class="dropdown-item py-2" href="profile.php"><i class="bi bi-person-gear me-2 text-muted"></i> My Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item py-2 text-danger fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                        <i class="bi bi-power me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
      </div>
    </div>
  </div>
</nav>