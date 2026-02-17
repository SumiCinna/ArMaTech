<?php
session_start();
require_once '../../config/database.php';

// SECURITY: Must be logged in
if (!isset($_SESSION['account_id'])) {
    header("Location: ../../customer_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Your Account | ArMaTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: radial-gradient(circle at center, #2c5364 0%, #203a43 50%, #0f2027 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .secure-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .header-strip {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            color: #212529;
            padding: 30px 20px;
            text-align: center;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .form-control {
            border-right: none;
        }
        .input-group-text {
            background-color: #fff;
            border-left: none;
            cursor: pointer;
        }
        .btn-update {
            background: #2c5364;
            border: none;
            transition: all 0.3s;
        }
        .btn-update:hover {
            background: #203a43;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>

<div class="secure-card">
    <div class="header-strip">
        <i class="fa-solid fa-shield-halved fa-3x mb-3"></i><br>
        SECURITY ACTION REQUIRED
    </div>
    
    <div class="p-4">
        <p class="text-center text-muted mb-4">
            Hello <strong><?php echo $_SESSION['username']; ?></strong>,<br>
            For your security, please update your temporary password before continuing.
        </p>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger small text-center"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <form action="../../core/process_force_change.php" method="POST">
            
            <div class="mb-3">
                <label class="form-label fw-bold small text-uppercase text-muted">New Password</label>
                <div class="input-group">
                    <input type="password" name="new_password" id="new_password" class="form-control form-control-lg" placeholder="Enter new password" required minlength="6">
                    <span class="input-group-text" onclick="togglePass('new_password', 'icon1')"><i class="fa-solid fa-eye text-muted" id="icon1"></i></span>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold small text-uppercase text-muted">Confirm Password</label>
                <div class="input-group">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control form-control-lg" placeholder="Repeat password" required>
                    <span class="input-group-text" onclick="togglePass('confirm_password', 'icon2')"><i class="fa-solid fa-eye text-muted" id="icon2"></i></span>
                </div>
            </div>

            <button type="submit" name="btn_update" class="btn btn-primary btn-update w-100 btn-lg fw-bold rounded-pill">
                Update Password & Continue <i class="fa-solid fa-arrow-right ms-2"></i>
            </button>
        </form>
        
        <div class="text-center mt-3">
            <a href="../../core/logout.php" class="text-muted small text-decoration-none"><i class="fa-solid fa-power-off me-1"></i> Cancel & Logout</a>
        </div>
    </div>
</div>

<script>
    function togglePass(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            input.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }
</script>

</body>
</html>