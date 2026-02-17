<?php
// customer_login.php
session_start();
// If already logged in, go to dashboard
if (isset($_SESSION['role']) && $_SESSION['role'] == 'customer') {
    header("Location: modules/customer/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Access | ArMaTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            /* Modern Banking Gradient */
            background: radial-gradient(circle at center, #2c5364 0%, #203a43 50%, #0f2027 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .login-header {
            background: linear-gradient(135deg, #0061f2 0%, #00c6f9 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
            position: relative;
        }
        /* Decorative Circle */
        .login-header::after {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        .login-body {
            padding: 40px 30px;
        }
        .form-control {
            border-radius: 8px;
            border: 2px solid #eef2f7;
            background: #f8f9fa;
        }
        .form-control:focus {
            border-color: #0061f2;
            background: #fff;
            box-shadow: none;
        }
        .btn-login {
            background: #0061f2;
            border: none;
            border-radius: 50px;
            padding: 15px;
            font-weight: 700;
            letter-spacing: 0.5px;
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            background: #0050c8;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 97, 242, 0.4);
        }
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #eef2f7;
            border-left: none;
            border-radius: 0 8px 8px 0;
            cursor: pointer;
        }
        /* Fix for floating label inside input group */
        .input-group > .form-floating > .form-control {
            border-radius: 8px 0 0 8px;
            border-right: none;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="mb-3">
            <i class="fa-solid fa-wallet fa-3x"></i>
        </div>
        <h4 class="fw-bold mb-0">ArMaTech</h4>
        <p class="mb-0 opacity-75 small text-uppercase fw-bold ls-1">Customer Secure Portal</p>
    </div>
    
    <div class="login-body">
        
        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger fw-bold small text-center mb-4">
                <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form action="core/auth_customer.php" method="POST">
            
            <div class="form-floating mb-3">
                <input type="text" name="username" class="form-control" id="floatingInput" placeholder="Username" required autofocus>
                <label for="floatingInput">Customer ID / Username</label>
            </div>

            <div class="input-group mb-4">
                <div class="form-floating">
                    <input type="password" name="password" class="form-control" id="floatingPassword" placeholder="Password" required>
                    <label for="floatingPassword">Password</label>
                </div>
                <span class="input-group-text" id="togglePassword"><i class="fa-solid fa-eye text-muted" id="eyeIcon"></i></span>
            </div>

            <div class="d-grid mb-4">
                <button type="submit" name="btn_login" class="btn btn-primary btn-login">
                    LOGIN TO MY ACCOUNT
                </button>
            </div>

            <div class="text-center d-flex justify-content-between">
                <a href="index.php" class="text-decoration-none small text-muted"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>
                <a href="#" class="text-decoration-none small text-primary fw-bold">Forgot Password?</a>
            </div>
        </form>
    </div>
</div>

<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#floatingPassword');
    const eyeIcon = document.querySelector('#eyeIcon');

    togglePassword.addEventListener('click', function (e) {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        
        if(type === 'text'){
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        } else {
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        }
    });
</script>

</body>
</html>