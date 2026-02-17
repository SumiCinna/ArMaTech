<?php
session_start();
require_once '../../config/database.php';

// SECURITY: Must be logged in as Teller
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
    <title>Security Update | Teller Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { 
            background-color: #0f172a; 
            font-family: 'Inter', sans-serif; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh;
            background-image: radial-gradient(#1e293b 1px, transparent 1px);
            background-size: 20px 20px;
        }
        .security-card { 
            width: 100%; 
            max-width: 450px; 
            border: none; 
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); 
            border-radius: 16px; 
            overflow: hidden; 
        }
        .card-header { 
            background-color: #fff; 
            color: #0f172a; 
            padding: 30px 20px 10px; 
            text-align: center; 
            border-bottom: none;
        }
        .btn-primary {
            background-color: #0f172a;
            border-color: #0f172a;
        }
        .btn-primary:hover {
            background-color: #1e293b;
            border-color: #1e293b;
        }
    </style>
</head>
<body>

<div class="card security-card">
    <div class="card-header bg-white">
        <i class="bi bi-shield-lock-fill display-4 mb-2 text-primary"></i>
        <h4 class="fw-bold mb-0 text-dark">Security Update</h4>
        <small class="text-muted">Action Required</small>
    </div>
    
    <div class="card-body p-4">
        <div class="alert alert-light border text-center mb-4 small">
            Hello <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong>,<br>
            Please set a new secure password to continue.
        </div>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger small text-center">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form action="../../core/process_force_change_teller.php" method="POST">
            
            <div class="mb-3">
                <label class="form-label fw-bold small text-secondary">New Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                    <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Enter secure password" required minlength="6">
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePass('new_password', 'icon1')">
                        <i class="bi bi-eye-slash" id="icon1"></i>
                    </button>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold small text-secondary">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-check-circle"></i></span>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Repeat password" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePass('confirm_password', 'icon2')">
                        <i class="bi bi-eye-slash" id="icon2"></i>
                    </button>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" name="btn_update" class="btn btn-primary fw-bold py-2 rounded-pill">
                    Update Password & Continue
                </button>
                <a href="../../core/logout.php" class="btn btn-link text-muted text-decoration-none small">Cancel & Logout</a>
            </div>

        </form>
    </div>
</div>

<script>
    function togglePass(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("bi-eye-slash");
            icon.classList.add("bi-eye");
        } else {
            input.type = "password";
            icon.classList.remove("bi-eye");
            icon.classList.add("bi-eye-slash");
        }
    }
</script>

</body>
</html>