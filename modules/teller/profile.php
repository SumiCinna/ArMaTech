<?php
// modules/teller/profile.php
require_once '../../config/database.php';
include_once '../../includes/teller_header.php';

// 1. FETCH TELLER DATA
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

// Calculate Tenure (How long they've worked)
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

    <div class="row g-4">
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 text-center h-100">
                <div class="card-body p-4">
                    
                    <div class="mb-3 d-flex justify-content-center">
                        <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 120px; height: 120px; font-size: 3em;">
                            <?php echo substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1); ?>
                        </div>
                    </div>

                    <h4 class="fw-bold text-dark mb-1"><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h4>
                    <p class="text-muted mb-3">@<?php echo $user['username']; ?></p>
                    
                    <span class="badge bg-info bg-opacity-10 text-dark px-3 py-2 rounded-pill mb-4 border border-info border-opacity-25">
                        <i class="fa-solid fa-id-badge me-1"></i> TELLER
                    </span>

                    <div class="d-grid gap-2">
                        <a href="change_password.php" class="btn btn-outline-dark fw-bold rounded-pill">
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
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-user-check me-2"></i> Employee Information</h6>
                    <span class="badge bg-success bg-opacity-10 text-success"><i class="fa-solid fa-circle me-1" style="font-size: 0.5em;"></i> Active Status</span>
                </div>
                <div class="card-body p-4 pt-0">
                    
                    <h6 class="text-uppercase text-muted fw-bold small mb-3 border-bottom pb-2">Personal Details</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold">Full Name</label>
                            <p class="fw-bold text-dark mb-0">
                                <?php echo $user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold">Birth Date</label>
                            <p class="fw-bold text-dark mb-0">
                                <?php echo date('F d, Y', strtotime($user['date_of_birth'])); ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold">Gender</label>
                            <p class="fw-bold text-dark mb-0"><?php echo $user['gender']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold">Civil Status</label>
                            <p class="fw-bold text-dark mb-0"><?php echo $user['civil_status']; ?></p>
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted fw-bold small mb-3 border-bottom pb-2">Contact Information</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold">Mobile Number</label>
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-mobile-screen me-2 text-secondary"></i>
                                <span class="fw-bold text-dark"><?php echo $user['contact_number']; ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold">Email Address</label>
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-envelope me-2 text-secondary"></i>
                                <span class="fw-bold text-dark"><?php echo $user['email']; ?></span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="small text-muted fw-bold">Home Address</label>
                            <div class="d-flex align-items-start">
                                <i class="fa-solid fa-map-location-dot me-2 mt-1 text-secondary"></i>
                                <span class="fw-bold text-dark"><?php echo $user['full_address']; ?></span>
                            </div>
                        </div>
                    </div>

                    <h6 class="text-uppercase text-danger fw-bold small mb-3 border-bottom pb-2">
                        <i class="fa-solid fa-kit-medical me-2"></i> In Case of Emergency
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold">Contact Person</label>
                            <p class="fw-bold text-dark mb-0"><?php echo $user['emergency_contact_name'] ?? 'Not set'; ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold">Contact Number</label>
                            <p class="fw-bold text-dark mb-0"><?php echo $user['emergency_contact_phone'] ?? 'Not set'; ?></p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

<?php include_once '../../includes/teller_footer.php'; ?>