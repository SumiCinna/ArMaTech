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

// 3. FETCH TRANSACTION & ITEM (Only if it belongs to this customer)
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
    echo "<div class='container mt-5'><div class='alert alert-danger'>Transaction not found or access denied.</div></div>";
    include_once '../../includes/customer_footer.php';
    exit();
}

// 4. FETCH PAYMENT HISTORY
$sql_pay = "SELECT * FROM payments WHERE transaction_id = ? ORDER BY date_paid DESC";
$stmt = $conn->prepare($sql_pay);
$stmt->bind_param("i", $trans_id);
$stmt->execute();
$payments = $stmt->get_result();

// Status Colors
$status_color = 'secondary';
if ($t['status'] == 'active') $status_color = 'success';
if ($t['status'] == 'redeemed') $status_color = 'primary';
if ($t['status'] == 'expired') $status_color = 'danger';

// Calculate Interest & Total for Active/Expired items
$calc = calculatePawnInterest($t['principal_amount'], $t['date_pawned']);
$current_interest = $calc['interest'];
$total_redemption = $calc['total'];
?>

<div class="container mt-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Transaction Details</h3>
            <span class="text-muted">PT Number: <span class="fw-bold text-dark"><?php echo $t['pt_number']; ?></span></span>
        </div>
        <a href="transactions.php" class="btn btn-light border shadow-sm fw-bold">
            <i class="fa-solid fa-arrow-left me-2"></i> Back
        </a>
    </div>

    <div class="row">
        
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm mb-3 bg-<?php echo $status_color; ?> bg-opacity-10 border-start border-5 border-<?php echo $status_color; ?>">
                <div class="card-body">
                    <small class="text-uppercase fw-bold text-<?php echo $status_color; ?>">Current Status</small>
                    <h2 class="fw-bold text-dark mb-0 text-capitalize"><?php echo $t['status']; ?></h2>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold py-3">
                    <i class="fa-solid fa-box-open me-2 text-primary"></i> Item Information
                </div>
                <div class="card-body">
                    <h5 class="fw-bold text-dark"><?php echo $t['brand'] . ' ' . $t['model']; ?></h5>
                    <p class="text-muted small mb-3"><?php echo $t['device_type']; ?></p>
                    
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Serial No.</span>
                            <span class="fw-bold"><?php echo $t['serial_number'] ?: 'N/A'; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Date Pawned</span>
                            <span class="fw-bold"><?php echo date('M d, Y', strtotime($t['date_pawned'])); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Maturity Date</span>
                            <span class="fw-bold text-danger"><?php echo date('M d, Y', strtotime($t['maturity_date'])); ?></span>
                        </li>
                    </ul>

                    <div class="mt-3 p-3 bg-light rounded">
                        <small class="fw-bold text-muted d-block mb-1">Condition Notes:</small>
                        <p class="mb-0 small fst-italic"><?php echo empty($t['condition_notes']) ? 'No notes.' : $t['condition_notes']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            
            <?php 
            $show_total_card = ($t['status'] == 'active' || $t['status'] == 'expired');
            $col_width = $show_total_card ? '6' : '12';
            ?>
            
            <div class="row g-3 mb-4">
                <div class="col-md-<?php echo $col_width; ?>">
                    <div class="card border-0 shadow-sm h-100 bg-dark text-white">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-white-50 text-uppercase fw-bold ls-1">Current Principal Balance</small>
                                <h2 class="fw-bold mb-0">₱<?php echo ($t['status'] == 'redeemed') ? '0.00' : number_format($t['principal_amount'], 2); ?></h2>
                            </div>
                            <i class="fa-solid fa-wallet fa-3x text-white-50"></i>
                        </div>
                    </div>
                </div>

                <?php if ($show_total_card): ?>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100 bg-success text-white">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-white-50 text-uppercase fw-bold ls-1">Total Amount to Pay</small>
                                <h2 class="fw-bold mb-0">₱<?php echo number_format($total_redemption, 2); ?></h2>
                                <small class="text-white-50 d-block mt-1" style="line-height: 1.2;">
                                    (Prin: ₱<?php echo number_format($t['principal_amount'], 2); ?> + Int: ₱<?php echo number_format($current_interest, 2); ?>)
                                </small>
                            </div>
                            <i class="fa-solid fa-money-bill-trend-up fa-3x text-white-50"></i>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-list-check me-2"></i> Payment History</h5>
            
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <?php if ($payments->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($pay = $payments->fetch_assoc()): ?>
                                <?php
                                    // Icon & Color Logic
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
                                <div class="list-group-item p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="rounded-circle bg-<?php echo $p_color; ?> bg-opacity-10 text-<?php echo $p_color; ?> p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                <i class="fa-solid <?php echo $p_icon; ?>"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-0 fw-bold"><?php echo $label; ?></h6>
                                            <small class="text-muted">
                                                <i class="fa-regular fa-clock me-1"></i> <?php echo date('M d, Y h:i A', strtotime($pay['date_paid'])); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <h5 class="mb-0 fw-bold text-dark">- ₱<?php echo number_format($pay['amount_paid'], 2); ?></h5>
                                            <small class="text-muted">Inv #<?php echo str_pad($pay['payment_id'], 6, '0', STR_PAD_LEFT); ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <p class="text-muted mb-0">No payments have been made yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include_once '../../includes/customer_footer.php'; ?>