<?php
// admin_login.php
session_start();
// If already logged in as Admin, redirect
if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
    header("Location: modules/admin/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Portal | ArMaTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #1a1d21; /* Dark Corporate Theme */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-box {
            background: #212529;
            width: 100%;
            max-width: 400px;
            border-radius: 15px;
            box-shadow: 0 0 50px rgba(0,0,0,0.5);
            border: 1px solid #343a40;
            overflow: hidden;
        }
        .login-header {
            background: #0d6efd;
            padding: 30px;
            text-align: center;
            color: white;
        }
        .form-control {
            background: #2c3034;
            border: 1px solid #495057;
            color: #fff;
            padding: 12px;
        }
        .form-control:focus {
            background: #2c3034;
            border-color: #0d6efd;
            color: #fff;
            box-shadow: none;
        }
        .btn-login {
            background: #0d6efd;
            border: none;
            padding: 12px;
            font-weight: bold;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>

<div class="login-box">
    <div class="login-header">
        <i class="fa-solid fa-user-shield fa-3x mb-2"></i>
        <h4 class="fw-bold">ADMINISTRATOR</h4>
        <small class="opacity-75">System Management Console</small>
    </div>
    
    <div class="p-4">
        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger bg-danger bg-opacity-10 border-0 text-danger small text-center">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form action="core/auth_admin.php" method="POST">
            <div class="mb-3">
                <label class="text-secondary small fw-bold text-uppercase">Username</label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="mb-4">
                <label class="text-secondary small fw-bold text-uppercase">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" name="btn_login" class="btn btn-primary w-100 btn-login">
                ACCESS DASHBOARD
            </button>
        </form>
    </div>
    <div class="p-3 bg-black bg-opacity-25 text-center">
        <a href="index.php" class="text-secondary small text-decoration-none">Return to Homepage</a>
    </div>
</div>

</body>
</html>