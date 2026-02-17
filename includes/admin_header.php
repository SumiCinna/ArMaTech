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
    <style>
        body { background-color: #f4f6f9; overflow-x: hidden; }
        
        /* Sidebar Styling */
        #sidebar-wrapper {
            min-height: 100vh;
            width: 250px;
            margin-left: -250px;
            transition: margin 0.25s ease-out;
            background: #343a40;
            position: fixed;
            top: 0; bottom: 0; z-index: 1000;
        }
        #sidebar-wrapper.toggled { margin-left: 0; }
        #page-content-wrapper { width: 100%; transition: margin 0.25s ease-out; margin-left: 0; }
        @media (min-width: 768px) {
            #sidebar-wrapper { margin-left: 0; }
            #page-content-wrapper { margin-left: 250px; }
        }

        .sidebar-heading { padding: 1.5rem 1.25rem; font-size: 1.2rem; color: #fff; font-weight: bold; background: #212529; }
        .list-group-item { background: transparent; color: #adb5bd; border: none; padding: 15px 20px; }
        .list-group-item:hover { background: #495057; color: #fff; }
        .list-group-item.active { background: #0d6efd; color: #fff; font-weight: bold; }
        .list-group-item i { width: 25px; }

        /* Dashboard Cards */
        .admin-card { border: none; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.2s; }
        .admin-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body>

<div class="d-flex" id="wrapper">
    <div class="border-end" id="sidebar-wrapper">
        <div class="sidebar-heading"><i class="fa-solid fa-gem me-2"></i> ArMaTech <small class="d-block fs-6 fw-normal opacity-50">Admin Panel</small></div>
        <div class="list-group list-group-flush mt-3">
            <a href="dashboard.php" class="list-group-item <?php echo ($activePage == 'dashboard') ? 'active' : ''; ?>">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
            
            <small class="text-uppercase text-muted fw-bold px-3 mt-3 mb-1" style="font-size:0.75rem;">Management</small>
            
            <a href="manage_staff.php" class="list-group-item <?php echo ($activePage == 'manage_staff') ? 'active' : ''; ?>">
                <i class="fa-solid fa-users-gear"></i> Staff / Tellers
            </a>
            <a href="manage_customers.php" class="list-group-item <?php echo ($activePage == 'manage_customers') ? 'active' : ''; ?>">
                <i class="fa-solid fa-users"></i> Customers
            </a>
            
            <small class="text-uppercase text-muted fw-bold px-3 mt-3 mb-1" style="font-size:0.75rem;">Reports</small>
            
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
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm px-4">
            <button class="btn btn-outline-secondary d-md-none" id="menu-toggle"><i class="fa-solid fa-bars"></i></button>
            <span class="ms-auto fw-bold text-secondary">Hello, Administrator</span>
        </nav>
        
        <div class="container-fluid px-4 py-4">