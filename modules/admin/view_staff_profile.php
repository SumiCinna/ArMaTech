<?php
// modules/admin/view_staff_profile.php
require_once '../../config/database.php';
include_once '../../includes/admin_header.php';

// 1. SECURITY: Get ID
if (!isset($_GET['id'])) {
    header("Location: manage_staff.php");
    exit();
}

$account_id = intval($_GET['id']);

// 2. FETCH STAFF PROFILE
$sql_staff = "SELECT p.*, a.username, a.role, a.status, 
              CONCAT(ad.house_no_street, ', ', ad.barangay, ', ', ad.city, ', ', ad.province) AS full_address 
              FROM accounts a
              JOIN profiles p ON a.profile_id = p.profile_id
              LEFT JOIN addresses ad ON p.profile_id = ad.profile_id
              WHERE a.account_id = ?";
$stmt = $conn->prepare($sql_staff);
$stmt->bind_param("i", $account_id);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();

if (!$staff) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Staff member not found.</div></div>";
    include_once '../../includes/admin_footer.php';
    exit();
}

// 3. FETCH PERFORMANCE STATS
// A. Total Pawns Processed (New Loans)
$sql_pawns = "SELECT COUNT(*) as count, SUM(principal_amount) as total_lent 
              FROM transactions WHERE teller_id = ?";
$stmt_p = $conn->prepare($sql_pawns);
$stmt_p->bind_param("i", $account_id);
$stmt_p->execute();
$pawns_stats = $stmt_p->get_result()->fetch_assoc();

// B. Total Payments Processed (Cash In)
$sql_pay = "SELECT COUNT(*) as count, SUM(amount_paid) as total_collected 
            FROM payments WHERE teller_id = ?";
$stmt_pay = $conn->prepare($sql_pay);
$stmt_pay->bind_param("i", $account_id);
$stmt_pay->execute();
$payment_stats = $stmt_pay->get_result()->fetch_assoc();

// C. Recent Activity (Last 5 Actions)
$sql_recent = "SELECT 'New Pawn' as type, date_pawned as date_action, principal_amount as amount, pt_number as ref 
               FROM transactions WHERE teller_id = ? 
               UNION 
               SELECT 'Payment' as type, date_paid as date_action, amount_paid as amount, payment_id as ref 
               FROM payments WHERE teller_id = ? 
               ORDER BY date_action DESC LIMIT 5";
$stmt_recent = $conn->prepare($sql_recent);
$stmt_recent->bind_param("ii", $account_id, $account_id);
$stmt_recent->execute();
$recent = $stmt_recent->get_result();
?>

<div class="container-fluid px-4">
    
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Staff Profile</h3>
            <small class="text-muted">Viewing details for <span class="fw-bold text-primary"><?php echo $staff['first_name'] . ' ' . $staff['last_name']; ?></span></small>
        </div>
        <a href="manage_staff.php" class="btn btn-outline-secondary btn-sm fw-bold">
            <i class="fa-solid fa-arrow-left me-2"></i> Back to List
        </a>
    </div>

    <div class="row">
        
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="card-header border-0 p-0" style="height: 100px; background: linear-gradient(135deg, #0f172a 0%, #334155 100%);"></div>
                
                <div class="card-body text-center p-4 position-relative">
                    
                    <div class="mb-3 position-relative" style="margin-top: -60px;">
                        <div class="rounded-circle bg-white p-1 d-inline-block shadow-sm">
                            <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; font-size: 2.5em; font-weight:bold;">
                                <?php echo substr($staff['first_name'], 0, 1) . substr($staff['last_name'], 0, 1); ?>
                            </div>
                        </div>
                    </div>
                    
                    <h4 class="fw-bold text-dark mb-1"><?php echo $staff['first_name'] . ' ' . $staff['last_name']; ?></h4>
                    <div class="mb-3">
                        <span class="badge bg-primary bg-opacity-10 text-primary text-uppercase px-3 py-2 rounded-pill me-1"><?php echo $staff['role']; ?></span>
                        <span class="badge <?php echo ($staff['status'] == 'active') ? 'bg-success' : 'bg-danger'; ?> bg-opacity-10 text-<?php echo ($staff['status'] == 'active') ? 'success' : 'danger'; ?> text-uppercase px-3 py-2 rounded-pill">
                            <?php echo strtoupper($staff['status']); ?>
                        </span>
                    </div>

                    <div class="text-start mt-4">
                        <h6 class="text-uppercase text-secondary small fw-bold border-bottom pb-2 mb-3">Contact & Account Info</h6>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <small class="text-uppercase text-secondary fw-bold" style="font-size: 0.7rem;">Employee ID</small>
                                <div class="fw-bold text-dark"><?php echo $staff['public_id']; ?></div>
                            </div>
                            <div class="col-12">
                                <small class="text-uppercase text-secondary fw-bold" style="font-size: 0.7rem;">Username</small>
                                <div class="fw-bold text-dark">@<?php echo $staff['username']; ?></div>
                            </div>
                            <div class="col-12">
                                <small class="text-uppercase text-secondary fw-bold" style="font-size: 0.7rem;">Date Hired</small>
                                <div class="fw-bold text-dark">
                                <?php echo ($staff['date_hired']) ? date('M d, Y', strtotime($staff['date_hired'])) : 'N/A'; ?>
                                </div>
                            </div>
                            <div class="col-12">
                                <small class="text-uppercase text-secondary fw-bold" style="font-size: 0.7rem;">Contact Details</small>
                                <div class="fw-bold text-dark"><i class="fa-solid fa-phone me-2 text-secondary"></i> <?php echo $staff['contact_number']; ?></div>
                                <div class="fw-bold text-dark"><i class="fa-solid fa-envelope me-2 text-secondary"></i> <?php echo $staff['email']; ?></div>
                            </div>
                            <div class="col-12">
                                <small class="text-uppercase text-secondary fw-bold" style="font-size: 0.7rem;">Address</small>
                                <div class="fw-bold text-dark small"><?php echo $staff['full_address'] ?? 'N/A'; ?></div>
                            </div>
                        </div>

                        <div class="p-3 bg-danger bg-opacity-10 rounded border border-danger border-opacity-25">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-white p-2 rounded-circle text-danger shadow-sm me-2"><i class="fa-solid fa-kit-medical"></i></div>
                                <h6 class="text-danger fw-bold text-uppercase small mb-0">Emergency Contact</h6>
                            </div>
                            <p class="mb-0 fw-bold text-dark small"><?php echo $staff['emergency_contact_name'] ?: 'Not set'; ?></p>
                            <p class="mb-0 small text-muted"><?php echo $staff['emergency_contact_phone'] ?: 'N/A'; ?></p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-lg-8">
            
            <h5 class="fw-bold text-dark mb-3">Performance Overview</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm p-4 h-100 position-relative overflow-hidden">
                        <div class="position-absolute top-0 end-0 p-3 opacity-10">
                            <i class="fa-solid fa-hand-holding-dollar fa-5x text-primary"></i>
                        </div>
                        <div class="position-relative z-1">
                            <small class="text-uppercase text-secondary fw-bold ls-1">Loans Processed</small>
                            <h2 class="display-5 fw-bold text-dark mt-2 mb-1"><?php echo $pawns_stats['count']; ?></h2>
                            <div class="text-primary fw-bold small">
                                <i class="fa-solid fa-arrow-up"></i> ₱<?php echo number_format($pawns_stats['total_lent'] ?? 0, 2); ?> Total Lent
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm p-4 h-100 position-relative overflow-hidden">
                        <div class="position-absolute top-0 end-0 p-3 opacity-10">
                            <i class="fa-solid fa-cash-register fa-5x text-success"></i>
                        </div>
                        <div class="position-relative z-1">
                            <small class="text-uppercase text-secondary fw-bold ls-1">Payments Collected</small>
                            <h2 class="display-5 fw-bold text-dark mt-2 mb-1"><?php echo $payment_stats['count']; ?></h2>
                            <div class="text-success fw-bold small">
                                <i class="fa-solid fa-arrow-down"></i> ₱<?php echo number_format($payment_stats['total_collected'] ?? 0, 2); ?> Total In
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="fa-solid fa-clock-rotate-left me-2"></i> Recent Activity Log</h6>
                    <span class="badge bg-light text-secondary border">Last 5 Actions</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-4 py-3">Date & Time</th>
                                    <th>Action Type</th>
                                    <th>Reference</th>
                                    <th class="text-end pe-4">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent->num_rows > 0): ?>
                                    <?php while($row = $recent->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 text-nowrap">
                                                <div class="fw-bold text-dark"><?php echo date('M d, Y', strtotime($row['date_action'])); ?></div>
                                                <div class="text-muted small" style="font-size: 0.75rem;"><?php echo date('h:i A', strtotime($row['date_action'])); ?></div>
                                            </td>
                                            <td>
                                                <?php if($row['type'] == 'New Pawn'): ?>
                                                    <span class="badge bg-primary bg-opacity-10 text-primary">New Pawn</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success">Payment Received</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="font-monospace text-muted small"><?php echo $row['ref']; ?></span>
                                            </td>
                                            <td class="text-end pe-4 fw-bold">
                                                ₱<?php echo number_format($row['amount'], 2); ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">No recent activity recorded.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    .ls-1 { letter-spacing: 1px; }
    .table-hover tbody tr { transition: background-color 0.2s; }
    .table-hover tbody tr:hover { background-color: #f8f9fa; }
</style>

<?php include_once '../../includes/admin_footer.php'; ?>