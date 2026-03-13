<?php
// modules/admin/view_transaction_details.php
require_once '../../config/database.php';
require_once '../../core/functions.php'; // ADD THIS LINE
include_once '../../includes/admin_header.php';

// 1. SECURITY: Get Transaction ID
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$trans_id = intval($_GET['id']);

// 2. FETCH TRANSACTION, ITEM & CUSTOMER DETAILS
$sql = "SELECT t.*, 
               i.device_type, i.brand, i.model, i.serial_number, i.inclusions, i.condition_notes,
               p.first_name, p.last_name, p.contact_number, p.email, p.public_id as cust_public_id,
               a.username as processed_by_user,
               b.first_name as buyer_fname, b.last_name as buyer_lname, b.contact_number as buyer_contact, b.public_id as buyer_public_id
        FROM transactions t
        JOIN items i ON t.transaction_id = i.transaction_id
        JOIN profiles p ON t.customer_id = p.profile_id
        LEFT JOIN accounts a ON t.teller_id = a.account_id
        LEFT JOIN shop_items si ON t.transaction_id = si.transaction_id
        LEFT JOIN shop_reservations sr ON si.shop_id = sr.shop_id AND sr.status = 'claimed'
        LEFT JOIN profiles b ON sr.customer_profile_id = b.profile_id
        WHERE t.transaction_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $trans_id);
$stmt->execute();
$t = $stmt->get_result()->fetch_assoc();

if (!$t) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Transaction not found.</div></div>";
    include_once '../../includes/admin_footer.php';
    exit();
}

// --- ADD THIS CALCULATION BLOCK ---
// Check if the new last_renewed_date exists, otherwise fallback to date_pawned
$start_date = isset($t['last_renewed_date']) ? $t['last_renewed_date'] : $t['date_pawned'];
$calc = calculatePawnInterest($t['principal_amount'], $start_date);

// 3. FETCH PAYMENT HISTORY (With Teller Name)
$sql_pay = "SELECT py.*, a.username as teller_name 
            FROM payments py
            LEFT JOIN accounts a ON py.teller_id = a.account_id
            WHERE py.transaction_id = ? 
            ORDER BY py.date_paid DESC";
$stmt_p = $conn->prepare($sql_pay);
$stmt_p->bind_param("i", $trans_id);
$stmt_p->execute();
$payments = $stmt_p->get_result();

// Device Icon Logic
$dt = $t['device_type'];
$device_icon = 'fa-box';
if ($dt == 'Smartphone' || $dt == 'Tablet') $device_icon = 'fa-mobile-screen';
elseif ($dt == 'Laptop') $device_icon = 'fa-laptop';
elseif ($dt == 'Camera') $device_icon = 'fa-camera';
elseif ($dt == 'Gaming Console') $device_icon = 'fa-gamepad';
elseif ($dt == 'Smartwatch') $device_icon = 'fa-clock';

// Status Badge Logic
$status_color = 'secondary';
if ($t['status'] == 'active') $status_color = 'success';
if ($t['status'] == 'redeemed') $status_color = 'secondary';
if ($t['status'] == 'expired') $status_color = 'danger';
if ($t['status'] == 'auctioned') $status_color = 'primary';
?>

<div class="container-fluid px-4 pb-5">
    
    <!-- Enhanced Header & Navigation -->
    <div class="d-flex justify-content-between align-items-end mt-4 mb-4">
        <div>
           
            <h3 class="fw-bold text-dark mb-0 d-flex align-items-center">
                Loan Details 
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 ms-3 rounded-pill" style="font-size: 0.5em; vertical-align: middle;">PT# <?php echo $t['pt_number']; ?></span>
            </h3>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-white border shadow-sm fw-bold text-secondary px-3" onclick="window.print()">
                <i class="fa-solid fa-print me-2 text-dark"></i> Print
            </button>
            <a href="javascript:history.back()" class="btn btn-dark fw-bold shadow-sm px-3">
                <i class="fa-solid fa-arrow-left me-2"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        
        <div class="col-lg-8">
            
            <!-- Enhanced Item Details Card -->
            <div class="card shadow-sm border-0 mb-4 rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="row g-0">
                        <div class="col-md-4 bg-light d-flex flex-column justify-content-center align-items-center p-4 border-end">
                            <div class="bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center mb-3 text-primary" style="width: 80px; height: 80px;">
                                <i class="fa-solid <?php echo $device_icon; ?> fa-2x"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-0"><?php echo $t['device_type']; ?></h5>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary mt-2 border border-secondary border-opacity-25 px-3 rounded-pill">Category</span>
                        </div>
                        <div class="col-md-8 p-4">
                            <h6 class="text-uppercase text-muted fw-bold small mb-3 ls-1 border-bottom pb-2">Item Specifications</h6>
                            <div class="row g-4">
                                <div class="col-sm-6">
                                    <small class="text-muted d-block mb-1">Brand & Model</small>
                                    <h6 class="fw-bold text-dark mb-0"><?php echo $t['brand'] . ' ' . $t['model']; ?></h6>
                                </div>
                                <div class="col-sm-6">
                                    <small class="text-muted d-block mb-1">Serial Number</small>
                                    <span class="font-monospace text-dark bg-light px-2 py-1 rounded border"><?php echo $t['serial_number'] ?: 'N/A'; ?></span>
                                </div>
                                <div class="col-12 border-top pt-3">
                                    <small class="text-muted d-block mb-1">Inclusions</small>
                                    <p class="mb-0 text-dark small"><?php echo nl2br($t['inclusions']); ?></p>
                                </div>
                                <div class="col-12">
                                    <div class="p-3 bg-warning bg-opacity-10 rounded-3 border border-warning border-opacity-25 d-flex align-items-start">
                                        <i class="fa-solid fa-triangle-exclamation text-warning mt-1 me-2"></i>
                                        <div>
                                            <small class="text-warning text-uppercase fw-bold d-block mb-1" style="font-size: 0.7rem;">Condition Notes</small>
                                            <p class="mb-0 small text-dark fst-italic"><?php echo nl2br($t['condition_notes']) ?: 'No specific issues noted.'; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-list-check me-2"></i> Transaction History</h6>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill">Audit Trail</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 custom-table border-top">
                            <thead class="bg-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-4">Date & Time</th>
                                    <th>Transaction Type</th>
                                    <th>Processed By</th>
                                    <th class="text-end pe-4">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($payments->num_rows > 0): ?>
                                    <?php while($pay = $payments->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 text-muted small">
                                                <div class="fw-bold text-dark"><?php echo date('M d, Y', strtotime($pay['date_paid'])); ?></div>
                                                <div class="text-muted" style="font-size: 0.75rem;"><?php echo date('h:i A', strtotime($pay['date_paid'])); ?></div>
                                            </td>
                                            <td>
                                                <?php if($pay['payment_type'] == 'interest_only'): ?>
                                                    <span class="badge bg-info bg-opacity-10 text-info border border-info rounded-pill px-3">Interest Payment</span>
                                                <?php elseif($pay['payment_type'] == 'redeem' || $pay['payment_type'] == 'full_redemption'): ?>
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill px-3">Full Redemption</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary rounded-pill px-3">Payment</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-uppercase fw-bold text-secondary">
                                                    <i class="fa-solid fa-user-tie me-1"></i> <?php echo $pay['teller_name'] ?? 'System'; ?>
                                                </small>
                                            </td>
                                            <td class="text-end pe-4 fw-bold text-dark">
                                                ₱<?php echo number_format($pay['amount_paid'], 2); ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No payment history found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-lg-4">
            
            <div class="card shadow-sm border-0 mb-4 rounded-4 bg-gradient bg-<?php echo $status_color; ?> text-white overflow-hidden">
                <div class="card-body p-4 text-center">
                    <small class="text-uppercase opacity-75 fw-bold ls-1">Current Status</small>
                    <h2 class="fw-bold mb-0 text-uppercase mt-2"><i class="fa-solid fa-circle-info me-2 opacity-50"></i><?php echo $t['status']; ?></h2>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4 rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-wallet me-2 text-primary"></i> Financial Details</h6>
                </div>
                <div class="card-body">
                    
                    <?php if ($t['status'] == 'active' || $t['status'] == 'expired'): ?>
                        <div class="bg-primary bg-opacity-10 p-3 rounded-3 mb-4 border border-primary border-opacity-25">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-primary fw-bold small text-uppercase"><i class="fa-solid fa-clock-rotate-left me-1"></i> Total Due Today</span>
                                <span class="badge bg-primary rounded-pill"><?php echo $calc['months']; ?> Month(s)</span>
                            </div>
                            <h2 class="fw-bold text-primary mb-0">₱<?php echo number_format($calc['total'], 2); ?></h2>
                        </div>
                    <?php else: ?>
                        <div class="bg-light p-3 rounded-3 mb-4 border text-center">
                            <span class="badge bg-secondary mb-2">Account Closed</span>
                            <p class="small text-muted mb-0">Interest is no longer accruing on this transaction.</p>
                        </div>
                    <?php endif; ?>

                    <ul class="list-group list-group-flush financial-list">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                            <span class="text-muted">Principal Amount</span>
                            <span class="fw-bold text-dark fs-6">₱<?php echo number_format($t['principal_amount'], 2); ?></span>
                        </li>
                        
                        <?php if ($t['status'] == 'active' || $t['status'] == 'expired'): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                            <span class="text-muted">Accrued Interest (3%)</span>
                            <span class="fw-bold text-warning">+ ₱<?php echo number_format($calc['interest'], 2); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-2 py-2 border-0 mt-1 bg-light rounded">
                            <span class="text-dark fw-bold small text-uppercase">Total Amount Due</span>
                            <span class="fw-bold text-success fs-6">₱<?php echo number_format($calc['total'], 2); ?></span>
                        </li>
                        <?php endif; ?>

                        <hr class="my-2 opacity-25">

                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                            <span class="text-muted">Origination Date</span>
                            <span class="fw-bold text-dark"><?php echo date('M d, Y', strtotime($t['date_pawned'])); ?></span>
                        </li>
                        
                        <?php if (isset($t['last_renewed_date']) && $t['last_renewed_date'] != $t['date_pawned']): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                            <span class="text-muted">Last Renewed</span>
                            <span class="fw-bold text-info"><?php echo date('M d, Y', strtotime($t['last_renewed_date'])); ?></span>
                        </li>
                        <?php endif; ?>

                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                            <span class="text-muted">Maturity Date</span>
                            <span class="fw-bold text-warning"><?php echo date('M d, Y', strtotime($t['maturity_date'])); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                            <span class="text-muted">Expiry Date</span>
                            <span class="fw-bold text-danger"><?php echo date('M d, Y', strtotime($t['expiry_date'])); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-primary bg-opacity-10 rounded-3 mt-3 px-3 py-3 border border-primary border-opacity-25 shadow-sm">
                            <div class="d-flex align-items-center">
                                <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 38px; height: 38px;">
                                    <i class="fa-solid fa-user-tie ps-2"></i>
                                </div>
                                <div>
                                    <small class="text-primary text-uppercase fw-bold d-block" style="font-size: 0.65rem; letter-spacing: 1px;">Processed By</small>
                                    <span class="fw-bold text-dark text-uppercase"><?php echo $t['processed_by_user'] ?? 'System'; ?></span>
                                </div>
                            </div>
                            <span class="badge bg-primary text-white rounded-pill px-3 py-2 shadow-sm" style="font-size: 0.7rem; letter-spacing: 0.5px;">TELLER</span>
                        </li>
                    </ul>
                </div>
            </div>

            <?php if ($t['status'] == 'auctioned' && !empty($t['buyer_fname'])): ?>
            <div class="card shadow-sm border-0 mb-4 border-start border-4 border-success rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-success"><i class="fa-solid fa-gavel me-2"></i> Auction Winner</h6>
                </div>
                <div class="card-body text-center pt-0 pb-4">
                    <div class="mb-3">
                         <div class="rounded-circle bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center shadow-sm border border-success border-opacity-25" style="width: 70px; height: 70px; font-weight:bold; font-size: 1.2rem;">
                            <?php echo substr($t['buyer_fname'], 0, 1) . substr($t['buyer_lname'], 0, 1); ?>
                        </div>
                    </div>
                    <h6 class="fw-bold mb-1 text-dark"><?php echo $t['buyer_fname'] . ' ' . $t['buyer_lname']; ?></h6>
                    <p class="text-muted small mb-2"><span class="badge bg-light text-dark border font-monospace">ID: <?php echo $t['buyer_public_id']; ?></span></p>
                    <small class="text-muted"><i class="fa-solid fa-phone me-1"></i> <?php echo $t['buyer_contact']; ?></small>
                </div>
            </div>
            <?php endif; ?>

            <!-- Enhanced Customer Card -->
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold"><i class="fa-solid fa-user me-2"></i> <?php echo ($t['status'] == 'auctioned') ? 'Previous Owner' : 'Customer Details'; ?></h6>
                </div>
                <div class="card-body text-center pt-0 pb-4">
                    <div class="mb-3">
                         <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center shadow-sm border border-primary border-opacity-25" style="width: 80px; height: 80px; font-weight:bold; font-size: 1.5rem;">
                            <?php echo substr($t['first_name'], 0, 1) . substr($t['last_name'], 0, 1); ?>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-1 text-dark"><?php echo $t['first_name'] . ' ' . $t['last_name']; ?></h5>
                    <p class="text-muted small mb-4"><span class="badge bg-light text-secondary border font-monospace">ID: <?php echo $t['cust_public_id']; ?></span></p>
                    
                    <div class="d-flex flex-column gap-2 px-2">
                        <a href="tel:<?php echo $t['contact_number']; ?>" class="btn btn-light border shadow-sm btn-sm fw-bold text-dark d-flex align-items-center justify-content-center py-2 btn-hover-lift">
                            <i class="fa-solid fa-phone me-2 text-primary"></i> <?php echo $t['contact_number']; ?>
                        </a>
                        <a href="mailto:<?php echo $t['email']; ?>" class="btn btn-light border shadow-sm btn-sm fw-bold text-dark d-flex align-items-center justify-content-center py-2 btn-hover-lift">
                            <i class="fa-solid fa-envelope me-2 text-danger"></i> Send Email
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    .ls-1 { letter-spacing: 1px; }
    /* Financial Details Hover Effect */
    .financial-list .list-group-item {
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }
    .financial-list .list-group-item:hover {
        background-color: #f0f7ff; /* Soft Blue */
        border-left-color: #0d6efd; /* Accent Color */
        padding-left: 1rem !important; /* Slide Effect */
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }
    /* Table Hover Effect */
    .custom-table tbody tr { transition: all 0.2s ease-in-out; }
    .custom-table tbody tr:hover {
        background-color: #f0f7ff !important;
        transform: scale(1.005);
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        position: relative;
        z-index: 1;
    }
    /* Buttons */
    .btn-hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .btn-hover-lift:hover { transform: translateY(-2px); box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important; }
</style>

<?php include_once '../../includes/admin_footer.php'; ?>