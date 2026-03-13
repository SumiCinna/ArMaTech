<?php
// modules/teller/view_history.php
session_start();
require_once '../../config/database.php';
include_once '../../includes/teller_header.php';

if (!isset($_GET['id'])) {
    header("Location: transactions.php");
    exit();
}
$trans_id = $_GET['id'];

// 1. Fetch Transaction Info
$sql_t = "SELECT t.*, i.device_type, i.brand, i.model FROM transactions t JOIN items i ON t.transaction_id = i.transaction_id WHERE t.transaction_id = ?";
$stmt = $conn->prepare($sql_t);
$stmt->bind_param("i", $trans_id);
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();

// 2. Fetch All Payments (Invoices) for this Transaction
$sql_p = "SELECT p.*, prof.public_id as teller_public_id 
          FROM payments p 
          JOIN accounts a ON p.teller_id = a.account_id 
          JOIN profiles prof ON a.profile_id = prof.profile_id
          WHERE p.transaction_id = ? 
          ORDER BY p.date_paid DESC";
$stmt = $conn->prepare($sql_p);
$stmt->bind_param("i", $trans_id);
$stmt->execute();
$payments = $stmt->get_result();
?>

<div class="container-fluid px-4 pb-5">

    <!-- Header & Back Button -->
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i> Transaction History</h3>
            <p class="text-muted mb-0 small">Payment logs and audit trail for <strong><?php echo $transaction['pt_number']; ?></strong>.</p>
        </div>
        <a href="transactions.php" class="btn btn-light border shadow-sm fw-bold text-secondary">
            <i class="fa-solid fa-arrow-left me-2"></i> Back to List
        </a>
    </div>

    <!-- Transaction Summary Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <small class="text-uppercase text-muted fw-bold letter-spacing-1" style="font-size: 0.7rem;">Item Details</small>
                    <h4 class="fw-bold text-dark mb-1"><?php echo $transaction['brand'] . ' ' . $transaction['model']; ?></h4>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 rounded-pill"><?php echo $transaction['device_type']; ?></span>
                </div>
                <div class="col-md-3 border-start">
                    <small class="text-muted d-block">Principal Amount</small>
                    <span class="fw-bold fs-5 text-dark">₱<?php echo number_format($transaction['principal_amount'], 2); ?></span>
                </div>
                <div class="col-md-3 border-start">
                    <small class="text-muted d-block">Current Status</small>
                    <?php 
                        $status = strtolower($transaction['status']);
                        $badge_class = 'bg-secondary text-secondary';
                        if ($status == 'active') $badge_class = 'bg-success text-success';
                        elseif ($status == 'redeemed') $badge_class = 'bg-primary text-primary';
                        elseif ($status == 'expired') $badge_class = 'bg-danger text-danger';
                        elseif ($status == 'auctioned') $badge_class = 'bg-dark text-dark';
                    ?>
                    <span class="badge <?php echo $badge_class; ?> bg-opacity-10 px-3 py-1 rounded-pill text-uppercase border"><?php echo $status; ?></span>
                </div>
            </div>
        </div>
    </div>

    <h5 class="fw-bold text-dark mb-3">Payment Timeline</h5>
    
    <?php if ($payments->num_rows > 0): ?>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="list-group list-group-flush">
                <?php while ($row = $payments->fetch_assoc()): ?>
                    <?php 
                        // Format Invoice ID: INV-0001
                        $inv_no = "INV-" . str_pad($row['payment_id'], 3, '0', STR_PAD_LEFT);
                        $type_label = ucwords(str_replace('_', ' ', $row['payment_type']));
                        $bg_icon = "bg-light text-muted";
                        $icon = "fa-receipt";
                        
                        if($row['payment_type'] == 'interest_only') { $bg_icon = "bg-info bg-opacity-10 text-info"; $icon = "fa-rotate"; }
                        elseif($row['payment_type'] == 'full_redemption') { $bg_icon = "bg-success bg-opacity-10 text-success"; $icon = "fa-check-circle"; }
                        elseif($row['payment_type'] == 'partial_payment') { $bg_icon = "bg-warning bg-opacity-10 text-warning"; $icon = "fa-chart-pie"; }
                    ?>
                    <div class="list-group-item p-4 d-flex align-items-center justify-content-between border-bottom-0" style="border-bottom: 1px solid #f8f9fa !important;">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle <?php echo $bg_icon; ?> d-flex align-items-center justify-content-center me-3 flex-shrink-0 shadow-sm" style="width: 48px; height: 48px;">
                                <i class="fa-solid <?php echo $icon; ?> fs-5 ps-2"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 fw-bold text-dark"><?php echo $type_label; ?></h6>
                                <small class="text-muted d-block mb-1">
                                    <i class="fa-regular fa-calendar me-1"></i> <?php echo date('M d, Y h:i A', strtotime($row['date_paid'])); ?> 
                                    <span class="mx-2">•</span> 
                                    <span class="font-monospace"><?php echo $inv_no; ?></span>
                                </small>
                                <span class="badge bg-light text-secondary border rounded-pill fw-normal">Teller: <?php echo $row['teller_public_id']; ?></span>
                            </div>
                        </div>
                        <div class="text-end">
                            <h5 class="fw-bold text-dark mb-1">₱<?php echo number_format($row['amount_paid'], 2); ?></h5>
                            <a href="print_receipt.php?payment_id=<?php echo $row['payment_id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">
                                View Receipt
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3 text-muted" style="width: 80px; height: 80px;">
                <i class="fa-solid fa-file-invoice-dollar fs-1 opacity-50"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">No Records Found</h5>
            <p class="text-muted small">No payments have been processed for this transaction yet.</p>
        </div>
    <?php endif; ?>
</div>

<style>
    .letter-spacing-1 { letter-spacing: 1px; }
    .list-group-item:hover { background-color: #fcfdfd; }
</style>

<?php include_once '../../includes/teller_footer.php'; ?>