<?php
// Ensure session is started if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Check: If they are not logged in as Teller, kick them out
// (This is a backup check in case the main page forgot it)
if (!isset($_SESSION['account_id']) || $_SESSION['role'] !== 'teller') {
    header("Location: ../../teller_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teller Portal | ArMaTech</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; }
        .navbar-teller { background-color: #0f172a !important; }
        .nav-link { color: rgba(255,255,255,0.9) !important; }
        .nav-link:hover { color: #fff !important; text-decoration: underline; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-teller shadow-sm mb-4">
  <div class="container">
    <a class="navbar-brand fw-bold" href="dashboard.php">
        <i class="bi bi-shop-window"></i> ArMaTech <small class="opacity-75" style="font-size: 0.7em;">TELLER</small>
    </a>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#tellerNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="tellerNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
            <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="new_pawn.php"><i class="bi bi-plus-circle"></i> New Pawn</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="redeem.php"><i class="bi bi-cash-stack"></i> Redeem/Renew</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="search.php"><i class="bi bi-search"></i> Search</a>
        </li>
      </ul>
      
      <div class="d-flex align-items-center text-white">
        <div class="dropdown">
            <a class="text-white text-decoration-none dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                <i class="bi bi-person-circle"></i> <?php echo isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Teller'; ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="../../core/logout.php">Logout</a></li>
            </ul>
        </div>
      </div>
    </div>
  </div>
</nav>