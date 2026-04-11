<?php
// modules/teller/change_password.php
session_start();
require_once '../../config/database.php';

// SECURITY: Ensure the teller is logged in
if (!isset($_SESSION['account_id']) || $_SESSION['role'] !== 'teller') {
    header("Location: ../../teller_login.php");
    exit();
}

$account_id = $_SESSION['account_id'];
$success_msg = '';
$error_msg = '';
$current_pass_error = '';

// ==========================================
// 1. PROCESS PASSWORD CHANGE
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_msg = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error_msg = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error_msg = "New password must be at least 8 characters long.";
    } else {
        // Fetch the account's current hashed password from the database
        $stmt = $conn->prepare("SELECT password FROM accounts WHERE account_id = ?");
        $stmt->bind_param("i", $account_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $hashed_password_db = $row['password'];

            // Verify the current password using PHP's built-in secure verifier
            if (password_verify($current_password, $hashed_password_db)) {
                
                // Hash the new password securely
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update the database
                $update_stmt = $conn->prepare("UPDATE accounts SET password = ? WHERE account_id = ?");
                $update_stmt->bind_param("si", $new_hashed_password, $account_id);
                
                if ($update_stmt->execute()) {
                    $success_msg = "Your password has been successfully updated!";
                } else {
                    $error_msg = "Failed to update password in the database. Please try again.";
                }
            } else {
                $current_pass_error = "The current password you entered is incorrect.";
            }
        } else {
            $error_msg = "Account not found.";
        }
    }
}

// Include the teller header AFTER processing so alerts render correctly
include_once '../../includes/teller_header.php'; 
?>

<style>
    /* Premium FinTech UI Integration */
    :root {
        --fintech-primary: #0dcaf0; /* Using Teller's Info/Cyan color theme */
        --fintech-border: #e2e8f0;
    }

    /* Custom Form Elements */
    .form-floating > .form-control { 
        height: 4rem; font-weight: 600; color: #1e293b; background-color: #f8fafc; 
        border: 1px solid var(--fintech-border); border-radius: 0.75rem; 
        padding-right: 3rem; 
    }
    .form-floating > label { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 700; padding: 1.2rem 1rem; }
    .form-control:focus { box-shadow: 0 0 0 4px rgba(13, 202, 240, 0.1) !important; border-color: var(--fintech-primary) !important; background-color: #fff !important; }
    
    /* Password Eye Icon Toggle */
    .password-toggle {
        position: absolute;
        top: 50%;
        right: 15px;
        transform: translateY(-50%);
        cursor: pointer;
        color: #94a3b8;
        z-index: 10;
        transition: color 0.2s ease;
    }
    .password-toggle:hover { color: var(--fintech-primary); }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-xl-7 col-lg-9">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-0">Account Security</h2>
                    <p class="text-muted">Manage your credentials to keep your employee portal safe.</p>
                </div>
                <a href="profile.php" class="btn btn-outline-secondary rounded-pill fw-bold">
                    <i class="fa-solid fa-arrow-left me-2"></i> Back to Profile
                </a>
            </div>

            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 rounded-4 d-flex align-items-center p-4 mb-4">
                    <i class="fa-solid fa-circle-check fa-2x me-3 text-success"></i>
                    <div class="fw-bold"><?php echo $success_msg; ?></div>
                    <button type="button" class="btn-close mt-1" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-4 d-flex align-items-center p-4 mb-4">
                    <i class="fa-solid fa-triangle-exclamation fa-2x me-3 text-danger"></i>
                    <div class="fw-bold"><?php echo $error_msg; ?></div>
                    <button type="button" class="btn-close mt-1" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-4 px-4 border-bottom-0 pb-2">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-key me-2 text-info"></i> Update Password</h5>
                </div>
                
                <div class="card-body p-4 pt-0">
                    <form action="change_password.php" method="POST" class="needs-validation" novalidate>
                        
                        <!-- CURRENT PASSWORD -->
                        <div class="mb-4">
                            <div class="form-floating position-relative">
                                <input type="password" name="current_password" id="current_password" class="form-control <?php echo !empty($current_pass_error) ? 'is-invalid' : ''; ?>" placeholder="Current Password" required>
                                <label>Current Password</label>
                                <i class="fa-solid fa-eye password-toggle" onclick="togglePassword('current_password', this)"></i>
                            </div>
                            <?php if (!empty($current_pass_error)): ?>
                                <div class="text-danger small fw-bold mt-2">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> <?php echo $current_pass_error; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <hr class="my-4 opacity-10">

                        <!-- NEW PASSWORD -->
                        <div class="mb-3">
                            <div class="form-floating position-relative">
                                <input type="password" name="new_password" id="new_password" class="form-control" placeholder="New Password" required minlength="8">
                                <label>New Password</label>
                                <i class="fa-solid fa-eye password-toggle" onclick="togglePassword('new_password', this)"></i>
                            </div>
                            <div class="text-danger small fw-bold mt-2" id="length_error" style="display: none;">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> Password must be at least 8 characters long.
                            </div>
                        </div>

                        <!-- CONFIRM PASSWORD -->
                        <div class="mb-4">
                            <div class="form-floating position-relative">
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm New Password" required minlength="8">
                                <label>Confirm New Password</label>
                                <i class="fa-solid fa-eye password-toggle" onclick="togglePassword('confirm_password', this)"></i>
                            </div>
                            <div class="text-danger small fw-bold mt-2" id="match_error" style="display: none;">
                                <i class="fa-solid fa-circle-xmark me-1"></i> Passwords do not match!
                            </div>
                            <div class="text-success small fw-bold mt-2" id="match_success" style="display: none;">
                                <i class="fa-solid fa-circle-check me-1"></i> Passwords match!
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="btn_update_password" class="btn btn-info text-white w-100 btn-lg rounded-pill shadow fw-bold py-3 transition-all">
                                <i class="fa-solid fa-floppy-disk me-2"></i> Save New Password
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// Toggle Password Visibility
function togglePassword(inputId, iconElement) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        iconElement.classList.remove('fa-eye');
        iconElement.classList.add('fa-eye-slash');
        iconElement.classList.add('text-primary');
    } else {
        input.type = 'password';
        iconElement.classList.remove('fa-eye-slash');
        iconElement.classList.add('fa-eye');
        iconElement.classList.remove('text-primary');
    }
}

// JSON-based validation configuration
const validationConfig = {
    rules: {
        confirm_password: {
            matchField: 'new_password',
            errorElement: 'match_error',
            successElement: 'match_success',
            errorMsg: 'Passwords do not match!',
            successMsg: 'Passwords match!'
        },
        new_password: {
            minLength: 8,
            errorElement: 'length_error',
            errorMsg: 'Password must be at least 8 characters long.'
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('.needs-validation');
    const newPass = document.getElementById('new_password');
    const confirmPass = document.getElementById('confirm_password');
    const matchError = document.getElementById('match_error');
    const matchSuccess = document.getElementById('match_success');
    const lengthError = document.getElementById('length_error');

    const validateLength = () => {
        const config = validationConfig.rules.new_password;
        let isValid = true;

        if (newPass.value.length > 0 && newPass.value.length < config.minLength) {
            newPass.classList.add('is-invalid');
            lengthError.style.display = 'block';
            isValid = false;
        } else {
            newPass.classList.remove('is-invalid');
            lengthError.style.display = 'none';
        }

        return isValid;
    };

    const validatePasswords = () => {
        const config = validationConfig.rules.confirm_password;
        const matchField = document.getElementById(config.matchField);
        
        let isValid = true;

        if (confirmPass.value !== "" && confirmPass.value !== matchField.value) {
            confirmPass.classList.add('is-invalid');
            matchError.style.display = 'block';
            matchSuccess.style.display = 'none';
            isValid = false;
        } else if (confirmPass.value !== "" && confirmPass.value === matchField.value) {
            confirmPass.classList.remove('is-invalid');
            matchError.style.display = 'none';
            matchSuccess.style.display = 'block';
            isValid = true;
        } else {
            confirmPass.classList.remove('is-invalid');
            matchError.style.display = 'none';
            matchSuccess.style.display = 'none';
        }

        return isValid;
    };

    // Real-time validation
    newPass.addEventListener('input', () => {
        validateLength();
        validatePasswords();
    });
    confirmPass.addEventListener('input', validatePasswords);

    // Form submission validation
    form.addEventListener('submit', function (event) {
        const isLengthValid = validateLength();
        const isPasswordsMatch = validatePasswords();
        
        if (!form.checkValidity() || !isLengthValid || !isPasswordsMatch) {
            event.preventDefault();
            event.stopPropagation();
            
            // Show all validation errors
            form.classList.add('was-validated');
            
            // Premium feedback: Shake the card if invalid
            const card = document.querySelector('.card');
            card.style.animation = 'shake 0.5s';
            setTimeout(() => card.style.animation = '', 500);
        }
    }, false);
});

// Add shake animation and premium checkmark styles
const style = document.createElement('style');
style.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-10px); }
        50% { transform: translateX(10px); }
        75% { transform: translateX(-10px); }
    }
    
    /* Corrected selectors to move validation icons to the left of the eye toggle for ALL fields */
    .form-control.is-invalid, 
    .form-control.is-valid,
    .was-validated .form-control:invalid, 
    .was-validated .form-control:valid {
        background-position: right 45px center !important;
        padding-right: 4.5rem !important;
    }
    
    /* Completely remove validation visuals (borders and icons) for current password field */
    #current_password.form-control,
    .was-validated #current_password.form-control:invalid,
    .was-validated #current_password.form-control:valid,
    #current_password.form-control.is-invalid,
    #current_password.form-control.is-valid {
        border-color: var(--fintech-border) !important;
        background-image: none !important;
        box-shadow: none !important;
    }
    #current_password.form-control:focus {
        border-color: var(--fintech-primary) !important;
        box-shadow: 0 0 0 4px rgba(13, 202, 240, 0.1) !important;
    }

    button.btn-info:hover {
        opacity: 0.9;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(13, 202, 240, 0.2) !important;
    }
`;
document.head.appendChild(style);
</script>

<?php include_once '../../includes/teller_footer.php'; ?>
