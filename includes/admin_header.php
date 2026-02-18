<?php
// includes/admin_header.php
if (session_status() === PHP_SESSION_NONE) session_start();

// SECURITY CHECK
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../admin_login.php");
    exit();
}
$activePage = basename($_SERVER['PHP_SELF'], ".php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | ArMaTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        
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
        #page-content-wrapper { width: 100%; transition: margin 0.25s ease-out; margin-left: 0; }
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
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.15) 0%, transparent 100%); 
            color: #60a5fa; 
            border-left-color: #60a5fa;
            font-weight: 700;
        }
        .list-group-item i { width: 24px; text-align: center; margin-right: 10px; }
        .sidebar-label { color: rgba(255, 255, 255, 0.5); font-size: 0.75rem; letter-spacing: 1px; }

        /* Dashboard Cards */
        .admin-card { border: none; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.2s; }
        .admin-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body>

<div class="d-flex" id="wrapper">
    <div class="border-end" id="sidebar-wrapper">
        <div class="sidebar-heading"><i class="fa-solid fa-laptop me-2"></i> ArMaTech <small class="d-block fs-6 fw-normal opacity-50">Admin Panel</small></div>
        <div class="list-group list-group-flush mt-3">
            <a href="dashboard.php" class="list-group-item <?php echo ($activePage == 'dashboard') ? 'active' : ''; ?>">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
            
            <div class="sidebar-label text-uppercase fw-bold px-4 mt-3 mb-2">Management</div>
            
            <a href="manage_staff.php" class="list-group-item <?php echo ($activePage == 'manage_staff') ? 'active' : ''; ?>">
                <i class="fa-solid fa-users-gear"></i> Staff / Tellers
            </a>
            <a href="manage_customers.php" class="list-group-item <?php echo ($activePage == 'manage_customers') ? 'active' : ''; ?>">
                <i class="fa-solid fa-users"></i> Customers
            </a>
            
            <div class="sidebar-label text-uppercase fw-bold px-4 mt-3 mb-2">Reports</div>
            
            <a href="reports_sales.php" class="list-group-item <?php echo ($activePage == 'reports_sales') ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-line"></i> Sales Report
            </a>
            <a href="reports_inventory.php" class="list-group-item <?php echo ($activePage == 'reports_inventory') ? 'active' : ''; ?>">
                <i class="fa-solid fa-warehouse"></i> Inventory
            </a>
            
            <div class="mt-5 px-3">
                <button type="button" class="btn btn-danger w-100 fw-bold" data-bs-toggle="modal" data-bs-target="#adminLogoutModal">
                    <i class="fa-solid fa-power-off me-2"></i> Logout
                </button>
            </div>
        </div>
    </div>

    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm px-4 py-3">
            <button class="btn btn-outline-secondary d-md-none" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
            <span class="ms-auto fw-bold text-secondary">Hello, Administrator</span>
        </nav>
        
        <div class="container-fluid px-4 py-4">