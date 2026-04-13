<?php
// modules/teller/profile.php
require_once '../../config/database.php';
include_once '../../includes/teller_header.php';

$account_id = $_SESSION['account_id'];
$msg = '';
$err = '';

// 1. GET PROFILE ID & CURRENT AVATAR FOR PROCESSING
$stmt_init = $conn->prepare("SELECT p.profile_id, p.avatar FROM accounts a JOIN profiles p ON a.profile_id = p.profile_id WHERE a.account_id = ?");
$stmt_init->bind_param("i", $account_id);
$stmt_init->execute();
$current_data = $stmt_init->get_result()->fetch_assoc();
$profile_id = $current_data['profile_id'];
$current_avatar = $current_data['avatar'];

$upload_dir = '../../uploads/avatars/';

// ==========================================
// 2. HANDLE AVATAR UPLOAD (Now requires Save button)
// ==========================================
if (isset($_POST['save_avatar']) && isset($_FILES['avatar_upload']) && $_FILES['avatar_upload']['error'] === UPLOAD_ERR_OK) {
    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
    $file_ext = strtolower(pathinfo($_FILES['avatar_upload']['name'], PATHINFO_EXTENSION));

    if (in_array($file_ext, $allowed_exts)) {
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Delete the old avatar from the server
        if (!empty($current_avatar) && file_exists($upload_dir . $current_avatar)) {
            unlink($upload_dir . $current_avatar);
        }

        // Generate a secure, unique filename
        $new_filename = 'avatar_user_' . $profile_id . '_' . time() . '.' . $file_ext;
        $destination = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['avatar_upload']['tmp_name'], $destination)) {
            $update_stmt = $conn->prepare("UPDATE profiles SET avatar = ? WHERE profile_id = ?");
            $update_stmt->bind_param("si", $new_filename, $profile_id);
            $update_stmt->execute();
            
            // Refresh the current avatar variable so the UI updates instantly
            $current_avatar = $new_filename; 
            $msg = "Profile picture updated successfully!";
        } else {
            $err = "Failed to save the uploaded file.";
        }
    } else {
        $err = "Invalid file type. Only JPG, PNG, and WEBP are allowed.";
    }
}

// ==========================================
// 3. HANDLE AVATAR DELETION (Via Modal)
// ==========================================
if (isset($_POST['confirm_delete_avatar'])) {
    if (!empty($current_avatar) && file_exists($upload_dir . $current_avatar)) {
        unlink($upload_dir . $current_avatar); 
    }
    $conn->query("UPDATE profiles SET avatar = NULL WHERE profile_id = $profile_id");
    
    $current_avatar = null; // Refresh for UI
    $msg = "Profile picture removed.";
}

// ==========================================
// 4. FETCH FRESH TELLER DATA FOR DISPLAY
// ==========================================
$sql = "SELECT p.*, a.username, a.status, 
        CONCAT(ad.house_no_street, ', ', ad.barangay, ', ', ad.city, ', ', ad.province) AS full_address 
        FROM accounts a
        JOIN profiles p ON a.profile_id = p.profile_id
        LEFT JOIN addresses ad ON p.profile_id = ad.profile_id
        WHERE a.account_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $account_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Calculate Tenure
$hired = new DateTime($user['date_hired']);
$now = new DateTime();
$tenure = $now->diff($hired);
$tenure_str = "";
if($tenure->y > 0) $tenure_str .= $tenure->y . " yrs ";
$tenure_str .= $tenure->m . " mos";
?>

<div class="container pb-5">

    <div class="row mt-4 mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-dark mb-0">My Employee Profile</h2>
            <p class="text-muted">Manage your personal details and account settings.</p>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 rounded-4 p-3 d-flex align-items-center" role="alert">
            <i class="fa-solid fa-circle-check fs-4 me-3 text-success"></i>
            <div><strong>Success!</strong> <?php echo $msg; ?></div>
            <button type="button" class="btn-close mt-1 shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($err): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-4 p-3 d-flex align-items-center" role="alert">
            <i class="fa-solid fa-triangle-exclamation fs-4 me-3 text-danger"></i>
            <div><strong>Error!</strong> <?php echo $err; ?></div>
            <button type="button" class="btn-close mt-1 shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 text-center h-100">
                <div class="card-body p-4">
                    
                    <form action="" method="POST" enctype="multipart/form-data" id="avatarForm">
                        <div class="mb-4 d-flex flex-column align-items-center position-relative">
                            
                            <div class="position-relative d-inline-block mb-3">
                                <img id="avatarPreview" 
                                     src="<?php echo (!empty($current_avatar) && file_exists($upload_dir . $current_avatar)) ? $upload_dir . $current_avatar : '../../assets/images/default-avatar.png'; ?>" 
                                     class="rounded-circle shadow-sm object-fit-cover border border-4 border-white <?php echo empty($current_avatar) ? 'd-none' : ''; ?>" 
                                     style="width: 140px; height: 140px; background-color: #f8f9fa;">
                                
                                <div id="avatarInitials" class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center fw-bold shadow-sm border border-4 border-white <?php echo !empty($current_avatar) ? 'd-none' : ''; ?>" style="width: 140px; height: 140px; font-size: 3.5em;">
                                    <?php echo substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1); ?>
                                </div>

                                <label for="avatar_upload" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 35px; height: 35px; cursor: pointer; transform: translate(-10%, -10%); border: 3px solid white; transition: all 0.2s;">
                                    <i class="fa-solid fa-pen" style="font-size: 0.8rem;"></i>
                                </label>
                            </div>

                            <input type="file" name="avatar_upload" id="avatar_upload" class="d-none" accept="image/jpeg, image/png, image/webp" onchange="previewAvatar(this);">

                            <div id="avatarActionButtons" class="d-none mt-2 w-100 px-4">
                                <button type="submit" name="save_avatar" class="btn btn-success rounded-pill fw-bold w-100 shadow-sm mb-2">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
                                </button>
                                <button type="button" class="btn btn-light border rounded-pill fw-bold w-100 text-muted" onclick="cancelPreview()">
                                    Cancel
                                </button>
                            </div>
                            
                            <?php if (!empty($current_avatar)): ?>
                                <button type="button" id="btnDeleteAvatar" class="btn btn-sm btn-link text-danger text-decoration-none mt-2 fw-bold" data-bs-toggle="modal" data-bs-target="#deleteAvatarModal">
                                    <i class="fa-solid fa-trash me-1"></i> Remove Photo
                                </button>
                            <?php endif; ?>

                        </div>
                    </form>

                    <h4 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                    <p class="text-muted mb-3">@<?php echo htmlspecialchars($user['username']); ?></p>
                    
                    <span class="badge bg-info bg-opacity-10 text-dark px-3 py-2 rounded-pill mb-4 border border-info border-opacity-25">
                        <i class="fa-solid fa-id-badge me-1"></i> TELLER
                    </span>

                    <div class="d-grid gap-2">
                        <a href="change_password.php" class="btn btn-outline-dark fw-bold rounded-pill shadow-sm">
                            <i class="fa-solid fa-key me-2"></i> Update Password
                        </a>
                    </div>
                    
                    <div class="mt-4 pt-3 border-top text-start small">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Employee ID</span>
                            <span class="fw-bold font-monospace"><?php echo $user['public_id']; ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Date Hired</span>
                            <span class="fw-bold"><?php echo date('M d, Y', strtotime($user['date_hired'])); ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Tenure</span>
                            <span class="fw-bold text-success"><?php echo $tenure_str; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-4 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-user-check me-2 text-primary"></i> Employee Information</h5>
                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill"><i class="fa-solid fa-circle me-1" style="font-size: 0.5em;"></i> Active Status</span>
                </div>
                <div class="card-body p-4 p-lg-5">
                    
                    <h6 class="text-uppercase text-muted fw-bold small mb-3 border-bottom pb-2">Personal Details</h6>
                    <div class="row g-4 mb-5">
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold mb-1">Full Name</label>
                            <p class="fw-bold text-dark mb-0 fs-6">
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']); ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold mb-1">Birth Date</label>
                            <p class="fw-bold text-dark mb-0 fs-6">
                                <?php echo date('F d, Y', strtotime($user['date_of_birth'])); ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold mb-1">Gender</label>
                            <p class="fw-bold text-dark mb-0 fs-6"><?php echo htmlspecialchars($user['gender']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold mb-1">Civil Status</label>
                            <p class="fw-bold text-dark mb-0 fs-6"><?php echo htmlspecialchars($user['civil_status']); ?></p>
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted fw-bold small mb-3 border-bottom pb-2">Contact Information</h6>
                    <div class="row g-4 mb-5">
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold mb-1">Mobile Number</label>
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded p-2 me-2 text-secondary"><i class="fa-solid fa-mobile-screen"></i></div>
                                <span class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($user['contact_number']); ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold mb-1">Email Address</label>
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded p-2 me-2 text-secondary"><i class="fa-solid fa-envelope"></i></div>
                                <span class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="small text-muted fw-bold mb-1">Home Address</label>
                            <div class="d-flex align-items-start">
                                <div class="bg-light rounded p-2 me-2 text-secondary"><i class="fa-solid fa-map-location-dot"></i></div>
                                <span class="fw-bold text-dark fs-6 mt-1"><?php echo htmlspecialchars($user['full_address']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded-4 p-4">
                        <h6 class="text-uppercase text-danger fw-bold small mb-3 border-bottom border-danger border-opacity-25 pb-2">
                            <i class="fa-solid fa-kit-medical me-2"></i> In Case of Emergency
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small text-danger fw-bold opacity-75 mb-1">Contact Person</label>
                                <p class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($user['emergency_contact_name'] ?? 'Not set'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-danger fw-bold opacity-75 mb-1">Contact Number</label>
                                <p class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($user['emergency_contact_phone'] ?? 'Not set'); ?></p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="deleteAvatarModal" tabindex="-1" aria-labelledby="deleteAvatarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-danger border-0 p-4">
                <h5 class="modal-title text-white fw-bold" id="deleteAvatarModalLabel"><i class="fa-solid fa-trash-can me-2"></i> Remove Photo</h5>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="mb-3 mt-2">
                    <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                        <i class="fa-solid fa-triangle-exclamation fa-2x"></i>
                    </div>
                </div>
                <h5 class="fw-bold text-dark mb-2">Are you sure?</h5>
                <p class="text-muted mb-0">This will permanently delete your profile picture and restore the default initials.</p>
            </div>
            <div class="modal-footer border-0 p-3 bg-light d-flex">
                <button type="button" class="btn btn-white border fw-bold flex-grow-1 rounded-pill py-2 shadow-sm" data-bs-dismiss="modal">Cancel</button>
                <form action="" method="POST" class="flex-grow-1 m-0">
                    <button type="submit" name="confirm_delete_avatar" class="btn btn-danger fw-bold w-100 rounded-pill py-2 shadow-sm">
                        Yes, Remove It
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Store the original image source so we can revert if they cancel
const originalImageSrc = document.getElementById('avatarPreview').src;
const hasOriginalImage = <?php echo !empty($current_avatar) ? 'true' : 'false'; ?>;

function previewAvatar(input) {
    const preview = document.getElementById('avatarPreview');
    const initials = document.getElementById('avatarInitials');
    const actionButtons = document.getElementById('avatarActionButtons');
    const deleteButton = document.getElementById('btnDeleteAvatar');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            // Show the preview image, hide initials
            preview.src = e.target.result;
            preview.classList.remove('d-none');
            initials.classList.add('d-none');
            
            // Show Save/Cancel buttons, hide Delete button
            actionButtons.classList.remove('d-none');
            if(deleteButton) deleteButton.classList.add('d-none');
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

function cancelPreview() {
    const input = document.getElementById('avatar_upload');
    const preview = document.getElementById('avatarPreview');
    const initials = document.getElementById('avatarInitials');
    const actionButtons = document.getElementById('avatarActionButtons');
    const deleteButton = document.getElementById('btnDeleteAvatar');

    // Clear the file input
    input.value = '';

    // Revert UI to original state
    actionButtons.classList.add('d-none');
    if(deleteButton) deleteButton.classList.remove('d-none');

    if (hasOriginalImage) {
        preview.src = originalImageSrc;
        preview.classList.remove('d-none');
        initials.classList.add('d-none');
    } else {
        preview.src = '';
        preview.classList.add('d-none');
        initials.classList.remove('d-none');
    }
}
</script>

<?php include_once '../../includes/teller_footer.php'; ?>