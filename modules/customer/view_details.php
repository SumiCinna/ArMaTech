<?php
// modules/customer/view_details.php
session_start();
require_once '../../config/database.php';
require_once '../../core/functions.php';
include_once '../../includes/customer_header.php';

// 1. SECURITY: Ensure Logged In
if (!isset($_SESSION['account_id'])) {
    header("Location: ../../customer_login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: transactions.php");
    exit();
}

$trans_id = $_GET['id'];
$account_id = $_SESSION['account_id'];

// 2. FETCH CUSTOMER PROFILE ID (To verify ownership)
$stmt = $conn->prepare("SELECT profile_id FROM accounts WHERE account_id = ?");
$stmt->bind_param("i", $account_id);
$stmt->execute();
$customer_id = $stmt->get_result()->fetch_assoc()['profile_id'];

// 3. FETCH TRANSACTION & ITEM
$sql = "SELECT t.*, i.device_type, i.brand, i.model, i.serial_number, i.condition_notes 
        FROM transactions t 
        JOIN items i ON t.transaction_id = i.transaction_id 
        WHERE t.transaction_id = ? AND t.customer_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $trans_id, $customer_id);
$stmt->execute();
$t = $stmt->get_result()->fetch_assoc();

// If no result, it means transaction doesn't exist OR belongs to someone else
if (!$t) {
    echo "<div class='container mt-5'><div class='alert alert-danger rounded-4 shadow-sm'>Transaction not found or access denied.</div></div>";
    include_once '../../includes/customer_footer.php';
    exit();
}

// 4. FETCH PAYMENT HISTORY
$sql_pay = "SELECT * FROM payments WHERE transaction_id = ? ORDER BY date_paid DESC";
$stmt = $conn->prepare($sql_pay);
$stmt->bind_param("i", $trans_id);
$stmt->execute();
$payments = $stmt->get_result();

// --- LOGIC: Dynamic Device Icon ---
$icon = 'fa-box-open';
$cat_lower = strtolower($t['device_type']);
if (strpos($cat_lower, 'smartphone') !== false || strpos($cat_lower, 'phone') !== false) $icon = 'fa-mobile-screen-button';
elseif (strpos($cat_lower, 'laptop') !== false) $icon = 'fa-laptop';
elseif (strpos($cat_lower, 'tablet') !== false) $icon = 'fa-tablet-screen-button';
elseif (strpos($cat_lower, 'watch') !== false) $icon = 'fa-stopwatch';

// --- LOGIC: Status Badge & Colors ---
$status_color = 'secondary';
$status_text = ucfirst($t['status']);
$status_icon = 'fa-circle-info';

if ($t['status'] == 'active') {
    $status_color = 'success';
    $status_icon = 'fa-shield-check';
} elseif ($t['status'] == 'redeemed') {
    $status_color = 'dark';
    $status_icon = 'fa-hand-holding-hand';
} elseif ($t['status'] == 'expired') {
    $status_color = 'danger';
    $status_icon = 'fa-gavel';
    $status_text = 'Foreclosed';
} elseif ($t['status'] == 'auctioned') {
    $status_color = 'primary';
    $status_icon = 'fa-store';
}

// --- LOGIC: Correct Interest Calculation using last_renewed_date ---
$start_date = isset($t['last_renewed_date']) ? $t['last_renewed_date'] : $t['date_pawned'];
$calc = calculatePawnInterest($t['principal_amount'], $start_date);
$current_interest = $calc['interest'];
$total_redemption = $calc['total'];

$show_financials = ($t['status'] == 'active' || $t['status'] == 'expired');
?>

<div class="container mt-4 pb-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0"><i class="fa-solid fa-file-invoice me-2 text-primary"></i> Loan Details</h3>
            <div class="d-flex align-items-center mt-1">
                <span class="badge bg-light text-dark border font-monospace me-2 px-2 py-1">PT# <?php echo $t['pt_number']; ?></span>
                <span class="badge bg-<?php echo $status_color; ?> bg-opacity-10 text-<?php echo $status_color; ?> border border-<?php echo $status_color; ?> rounded-pill px-3">
                    <i class="fa-solid <?php echo $status_icon; ?> me-1"></i> <?php echo $status_text; ?>
                </span>
            </div>
        </div>
        <a href="transactions.php" class="btn btn-light border shadow-sm fw-bold rounded-pill px-3">
            <i class="fa-solid fa-arrow-left me-md-2"></i> <span class="d-none d-md-inline">Back</span>
        </a>
    </div>

    <?php if ($show_financials): ?>
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden position-relative" style="background: linear-gradient(135deg, #198754 0%, #20c997 100%);">
                    <i class="fa-solid fa-money-bill-trend-up position-absolute opacity-10" style="font-size: 8rem; right: -10px; bottom: -20px;"></i>
                    <div class="card-body p-4 text-white position-relative z-index-1">
                        <small class="text-white-50 text-uppercase fw-bold ls-1" style="font-size: 0.75rem;">Total Amount to Pay</small>
                        <h1 class="display-6 fw-bold mb-1">₱<?php echo number_format($total_redemption, 2); ?></h1>
                        <div class="d-flex align-items-center mt-2 pt-2 border-top border-white border-opacity-25" style="font-size: 0.85rem;">
                            <span class="me-3"><i class="fa-solid fa-cube me-1 opacity-75"></i> Prin: ₱<?php echo number_format($t['principal_amount'], 2); ?></span>
                            <span><i class="fa-solid fa-arrow-trend-up me-1 text-warning"></i> Int: ₱<?php echo number_format($current_interest, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 rounded-4 bg-white border border-light">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center me-4" style="width: 70px; height: 70px;">
                            <i class="fa-regular fa-calendar-xmark fa-2x"></i>
                        </div>
                        <div>
                            <small class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 0.75rem; letter-spacing: 1px;">Maturity Date</small>
                            <h4 class="fw-bold text-dark mb-0"><?php echo date('F d, Y', strtotime($t['maturity_date'])); ?></h4>
                            <?php 
                                $days_left = (new DateTime())->diff(new DateTime($t['maturity_date']))->format("%r%a");
                                if ($t['status'] == 'expired') {
                                    echo '<small class="text-danger fw-bold mt-1 d-block"><i class="fa-solid fa-triangle-exclamation me-1"></i> Contract Expired</small>';
                                } elseif ($days_left < 0) {
                                    echo '<small class="text-danger fw-bold mt-1 d-block"><i class="fa-solid fa-clock me-1"></i> Overdue by ' . abs($days_left) . ' days</small>';
                                } else {
                                    echo '<small class="text-success fw-bold mt-1 d-block"><i class="fa-solid fa-clock me-1"></i> Due in ' . $days_left . ' days</small>';
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white fw-bold py-3 border-0 d-flex align-items-center">
                    <i class="fa-solid fa-box-open me-2 text-primary"></i> Collateral Details
                </div>
                <div class="card-body pt-0">
                    <div class="bg-light rounded-4 p-4 text-center mb-4 border">
                        <i class="fa-solid <?php echo $icon; ?> fa-4x text-secondary opacity-50 mb-3"></i>
                        <h5 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($t['brand'] . ' ' . $t['model']); ?></h5>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary mt-2 border"><?php echo htmlspecialchars($t['device_type']); ?></span>
                    </div>
                    
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between px-0 py-3">
                            <span class="text-muted fw-bold">Serial No.</span>
                            <span class="fw-bold font-monospace bg-light px-2 rounded border text-dark"><?php echo $t['serial_number'] ?: 'N/A'; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0 py-3">
                            <span class="text-muted fw-bold">Original Loan Date</span>
                            <span class="fw-bold text-dark"><?php echo date('M d, Y', strtotime($t['date_pawned'])); ?></span>
                        </li>
                        <?php if (isset($t['last_renewed_date']) && $t['last_renewed_date'] != $t['date_pawned']): ?>
                            <li class="list-group-item d-flex justify-content-between px-0 py-3">
                                <span class="text-muted fw-bold">Last Renewed</span>
                                <span class="fw-bold text-primary"><?php echo date('M d, Y', strtotime($t['last_renewed_date'])); ?></span>
                            </li>
                        <?php endif; ?>
                    </ul>

                    <?php if (!empty($t['condition_notes'])): ?>
                        <div class="mt-3 p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-3">
                            <small class="fw-bold text-warning text-uppercase d-block mb-1" style="font-size: 0.7rem;"><i class="fa-solid fa-triangle-exclamation me-1"></i> Condition Notes</small>
                            <p class="mb-0 small text-dark fst-italic"><?php echo htmlspecialchars($t['condition_notes']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center border-bottom-0">
                    <div><i class="fa-solid fa-list-check me-2 text-secondary"></i> Payment Ledger</div>
                    <span class="badge bg-light text-dark border rounded-pill"><?php echo $payments->num_rows; ?> Records</span>
                </div>
                <div class="card-body p-0">
                    <?php if ($payments->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($pay = $payments->fetch_assoc()): ?>
                                <?php
                                    // Ledger Icon & Color Logic
                                    $p_icon = 'fa-money-bill-wave';
                                    $p_color = 'success';
                                    
                                    if ($pay['payment_type'] == 'interest_only') {
                                        $p_icon = 'fa-rotate';
                                        $p_color = 'primary';
                                        $label = "Renewal (Interest Only)";
                                    } elseif ($pay['payment_type'] == 'partial_payment') {
                                        $p_icon = 'fa-chart-pie';
                                        $p_color = 'warning';
                                        $label = "Partial Payment";
                                    } else {
                                        $p_icon = 'fa-check-circle';
                                        $p_color = 'success';
                                        $label = "Full Redemption";
                                    }
                                ?>
                                <div class="list-group-item p-3 p-md-4 border-bottom">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="rounded-circle bg-<?php echo $p_color; ?> bg-opacity-10 text-<?php echo $p_color; ?> d-flex align-items-center justify-content-center border border-<?php echo $p_color; ?> border-opacity-25" style="width: 45px; height: 45px;">
                                                <i class="fa-solid <?php echo $p_icon; ?>"></i>
                                            </div>
                                        </div>
                                        
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1 fw-bold text-dark"><?php echo $label; ?></h6>
                                            <div class="text-muted d-flex align-items-center" style="font-size: 0.75rem;">
                                                <span class="me-3"><i class="fa-regular fa-clock me-1"></i> <?php echo date('M d, Y h:i A', strtotime($pay['date_paid'])); ?></span>
                                                <span class="font-monospace bg-light px-1 rounded">Inv #<?php echo str_pad($pay['payment_id'], 6, '0', STR_PAD_LEFT); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="text-end ps-2">
                                            <h5 class="mb-0 fw-bold text-<?php echo $p_color; ?>">- ₱<?php echo number_format($pay['amount_paid'], 2); ?></h5>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 my-3">
                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="fa-solid fa-receipt fs-2 text-muted opacity-50"></i>
                            </div>
                            <h6 class="fw-bold text-dark">No Payment History</h6>
                            <p class="text-muted small mb-0">You have not made any payments towards this loan yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($show_financials): ?>
                    <div class="card-footer bg-white border-top p-4 text-center rounded-bottom-4">
                        <small class="text-muted d-block"><i class="fa-solid fa-location-dot me-1 text-danger"></i> Please visit an ArMaTech branch to process your next payment.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .ls-1 { letter-spacing: 1px; }
    .list-group-item { transition: background-color 0.2s; }
    .list-group-item:hover { background-color: #fcfcfc; }
</style>

<?php include_once '../../includes/customer_footer.php'; ?>