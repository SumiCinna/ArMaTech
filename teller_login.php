<?php 
session_start();

if(isset($_SESSION['role']) && ($_SESSION['role'] == 'teller' || $_SESSION['role'] == 'admin')){
    header("Location: modules/teller/dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Access | ArMaTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #0f172a; 
            font-family: 'Inter', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: radial-gradient(#1e293b 1px, transparent 1px);
            background-size: 20px 20px;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .brand-logo {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            text-align: center;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="brand-logo"><i class="bi bi-shield-lock-fill text-primary"></i> ArMaTech</div>
    <p class="text-center text-muted mb-4">Secure Staff Portal</p>
    
    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger d-flex align-items-center p-2 small mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div><?php echo htmlspecialchars($_GET['error']); ?></div>
        </div>
    <?php endif; ?>

    <form action="core/auth_teller.php" method="POST">
        <div class="mb-3">
            <label class="form-label small fw-bold text-secondary">Username / ID</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                <input type="text" name="username" class="form-control form-control-lg" placeholder="Enter ID" required autofocus>
            </div>
        </div>
        
        <div class="mb-4">
            <label class="form-label small fw-bold text-secondary">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                <input type="password" name="password" id="password" class="form-control form-control-lg" placeholder="••••••••" required>
                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                    <i class="bi bi-eye-slash" id="toggleIcon"></i>
                </button>
            </div>
        </div>

        <div class="d-grid">
            <button type="submit" name="btn_teller_login" class="btn btn-primary btn-lg rounded-pill fw-bold">
                Sign In <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </div>
    </form>
    
    <div class="text-center mt-4 pt-3 border-top">
        <small class="text-muted d-block mb-1">Restricted Access</small>
        <a href="index.php" class="text-decoration-none small text-primary"><i class="bi bi-arrow-left"></i> Back to Home</a>
    </div>
</div>

<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');
    const toggleIcon = document.querySelector('#toggleIcon');

    togglePassword.addEventListener('click', function (e) {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        
        if(type === 'password'){
            toggleIcon.classList.remove('bi-eye');
            toggleIcon.classList.add('bi-eye-slash');
        } else {
            toggleIcon.classList.remove('bi-eye-slash');
            toggleIcon.classList.add('bi-eye');
        }
    });
</script>

</body>
</html>