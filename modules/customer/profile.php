<?php
// modules/customer/profile.php
require_once '../../config/database.php';
include_once '../../includes/customer_header.php';

// 1. FETCH FULL PROFILE DATA
$account_id = $_SESSION['account_id'];

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

// Calculate Age
$dob = new DateTime($user['date_of_birth']);
$now = new DateTime();
$age = $now->diff($dob)->y;
?>

<div class="container pb-5">

    <div class="row mt-4 mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-dark mb-0">My Profile</h2>
            <p class="text-muted">Manage your personal information and account security.</p>
        </div>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 text-center h-100">
                <div class="card-body p-4">
                    
                    <div class="mb-3 d-flex justify-content-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 120px; height: 120px; font-size: 3em;">
                            <?php echo substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1); ?>
                        </div>
                    </div>

                    <h4 class="fw-bold text-dark mb-1"><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h4>
                    <p class="text-muted mb-3">@<?php echo $user['username']; ?></p>
                    
                    <span class="badge <?php echo ($user['status'] == 'active') ? 'bg-success' : 'bg-danger'; ?> bg-opacity-10 text-<?php echo ($user['status'] == 'active') ? 'success' : 'danger'; ?> px-3 py-2 rounded-pill mb-4">
                        <i class="fa-solid fa-circle me-1" style="font-size: 0.6em;"></i> <?php echo strtoupper($user['status']); ?> MEMBER
                    </span>

                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary fw-bold rounded-pill" disabled>
                            <i class="fa-solid fa-pen-to-square me-2"></i> Edit Profile
                        </button>
                        <a href="change_password.php" class="btn btn-light text-muted fw-bold rounded-pill">
                            <i class="fa-solid fa-key me-2"></i> Change Password
                        </a>
                    </div>
                    
                    <div class="mt-4 pt-3 border-top text-start small">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Customer ID</span>
                            <span class="fw-bold font-monospace"><?php echo $user['public_id']; ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Member Since</span>
                            <span class="fw-bold"><?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold text-primary mb-0"><i class="fa-solid fa-id-card me-2"></i> Personal Details</h6>
                </div>
                <div class="card-body p-4 pt-0">
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold">Full Name</label>
                            <p class="fw-bold text-dark fs-5 mb-0">
                                <?php echo $user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold">Date of Birth</label>
                            <p class="fw-bold text-dark fs-5 mb-0">
                                <?php echo date('F d, Y', strtotime($user['date_of_birth'])); ?> 
                                <span class="text-muted fs-6 fw-normal">(<?php echo $age; ?> yrs old)</span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold">Gender</label>
                            <p class="fw-bold text-dark fs-5 mb-0"><?php echo $user['gender']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold">Civil Status</label>
                            <p class="fw-bold text-dark fs-5 mb-0"><?php echo $user['civil_status']; ?></p>
                        </div>
                    </div>

                    <hr class="opacity-10 my-4">

                    <h6 class="fw-bold text-primary mb-4"><i class="fa-solid fa-address-book me-2"></i> Contact Information</h6>
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold">Mobile Number</label>
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded p-2 me-2 text-primary"><i class="fa-solid fa-mobile-screen"></i></div>
                                <span class="fw-bold text-dark"><?php echo $user['contact_number']; ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold">Email Address</label>
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded p-2 me-2 text-primary"><i class="fa-solid fa-envelope"></i></div>
                                <span class="fw-bold text-dark"><?php echo $user['email']; ?></span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="small text-muted text-uppercase fw-bold">Home Address</label>
                            <div class="d-flex align-items-start">
                                <div class="bg-light rounded p-2 me-2 text-primary"><i class="fa-solid fa-map-location-dot"></i></div>
                                <span class="fw-bold text-dark mt-1"><?php echo $user['full_address'] ?? 'No address on file'; ?></span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

<?php include_once '../../includes/customer_footer.php'; ?>