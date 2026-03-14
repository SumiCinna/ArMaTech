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

// --- STATUS UI LOGIC ---
$status = strtolower($transaction['status']);
$badge_class = 'bg-secondary text-secondary';
$border_class = 'border-secondary';
$status_icon = 'fa-circle-info';

if ($status == 'active') {
    $badge_class = 'bg-success text-success';
    $border_class = 'border-success';
    $status_icon = 'fa-shield-check';
} elseif ($status == 'redeemed') {
    $badge_class = 'bg-dark text-dark';
    $border_class = 'border-dark';
    $status_icon = 'fa-hand-holding-hand';
} elseif ($status == 'expired') {
    $badge_class = 'bg-danger text-danger';
    $border_class = 'border-danger';
    $status_icon = 'fa-gavel';
    $status = 'Foreclosed';
} elseif ($status == 'auctioned') {
    $badge_class = 'bg-primary text-primary';
    $border_class = 'border-primary';
    $status_icon = 'fa-store';
}
?>

<div class="container-fluid px-4 pb-5">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mt-4 mb-4 gap-3 no-print">
        <div>
            <h3 class="fw-bold text-dark mb-0"><i class="fa-solid fa-list-check me-2 text-primary"></i> Transaction Ledger</h3>
            <p class="text-muted mb-0 small">Complete payment audit trail for this pawn contract.</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-light border shadow-sm fw-bold text-dark rounded-pill px-4">
                <i class="fa-solid fa-print me-2"></i> Print Ledger
            </button>
            <a href="transactions.php" class="btn btn-dark shadow-sm fw-bold rounded-pill px-4">
                <i class="fa-solid fa-arrow-left me-2"></i> Back
            </a>
        </div>
    </div>

    <div class="d-none d-print-block mb-4 text-center">
        <h3 class="fw-bold mb-1">ArMaTech Pawnshop</h3>
        <p class="text-muted mb-0">Official Customer Ledger</p>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden border-start border-5 <?php echo $border_class; ?>">
        <div class="card-body p-4">
            <div class="row align-items-center g-4">
                
                <div class="col-md-5">
                    <div class="d-flex align-items-center mb-1">
                        <span class="badge bg-light text-dark border font-monospace me-2 px-2 py-1">PT# <?php echo $transaction['pt_number']; ?></span>
                        <span class="badge <?php echo $badge_class; ?> bg-opacity-10 border border-<?php echo str_replace('bg-', '', $badge_class); ?> border-opacity-25 rounded-pill px-3 text-uppercase" style="font-size: 0.65rem;">
                            <i class="fa-solid <?php echo $status_icon; ?> me-1"></i> <?php echo $status; ?>
                        </span>
                    </div>
                    <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($transaction['brand'] . ' ' . $transaction['model']); ?></h5>
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;"><?php echo $transaction['device_type']; ?></small>
                </div>
                
                <div class="col-md-3 border-md-start ps-md-4">
                    <small class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 1px;">Current Balance</small>
                    <h3 class="fw-bold text-dark mb-0">₱<?php echo ($status == 'redeemed' || $status == 'auctioned') ? '0.00' : number_format($transaction['principal_amount'], 2); ?></h3>
                </div>
                
                <div class="col-md-4 border-md-start ps-md-4">
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Date Pawned</span>
                            <span class="fw-bold text-dark"><?php echo date('M d, Y', strtotime($transaction['date_pawned'])); ?></span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Maturity Date</span>
                            <span class="fw-bold <?php echo ($status == 'active') ? 'text-primary' : 'text-muted'; ?>"><?php echo date('M d, Y', strtotime($transaction['maturity_date'])); ?></span>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center mb-3">
        <h5 class="fw-bold text-dark mb-0"><i class="fa-regular fa-clock me-2 text-secondary"></i> Payment Timeline</h5>
        <div class="flex-grow-1 ms-3 border-bottom opacity-25"></div>
        <span class="badge bg-light text-dark border ms-3 rounded-pill"><?php echo $payments->num_rows; ?> Records</span>
    </div>
    
    <?php if ($payments->num_rows > 0): ?>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
            <div class="list-group list-group-flush">
                <?php while ($row = $payments->fetch_assoc()): ?>
                    <?php 
                        // Math Logic for Transparency
                        $prin_paid = 0;
                        $int_paid = 0;
                        
                        if ($row['payment_type'] == 'interest_only') {
                            $int_paid = $row['amount_paid'];
                        } else {
                            if (!is_null($row['old_principal']) && !is_null($row['new_principal'])) {
                                $prin_paid = $row['old_principal'] - $row['new_principal'];
                                $int_paid = $row['amount_paid'] - $prin_paid;
                            } else {
                                $prin_paid = $row['amount_paid']; // Legacy fallback
                            }
                        }

                        // Format Invoice ID: INV-0001
                        $inv_no = "INV-" . str_pad($row['payment_id'], 5, '0', STR_PAD_LEFT);
                        $type_label = ucwords(str_replace('_', ' ', $row['payment_type']));
                        
                        $bg_icon = "bg-light text-muted";
                        $icon = "fa-receipt";
                        $text_color = "text-dark";
                        
                        if($row['payment_type'] == 'interest_only') { 
                            $bg_icon = "bg-info bg-opacity-10 text-info"; 
                            $icon = "fa-rotate"; 
                            $type_label = "Renewal (Interest Only)";
                        } elseif($row['payment_type'] == 'full_redemption') { 
                            $bg_icon = "bg-success bg-opacity-10 text-success"; 
                            $icon = "fa-check-circle"; 
                            $text_color = "text-success";
                        } elseif($row['payment_type'] == 'partial_payment') { 
                            $bg_icon = "bg-warning bg-opacity-10 text-warning"; 
                            $icon = "fa-chart-pie"; 
                            $type_label = "Partial Payment";
                        }
                    ?>
                    
                    <div class="list-group-item p-4 border-bottom-0" style="border-bottom: 1px solid #f1f2f4 !important;">
                        <div class="row align-items-center">
                            
                            <div class="col-md-5 d-flex align-items-center mb-3 mb-md-0">
                                <div class="rounded-circle <?php echo $bg_icon; ?> d-flex align-items-center justify-content-center me-3 flex-shrink-0 border border-secondary border-opacity-10" style="width: 55px; height: 55px;">
                                    <i class="fa-solid <?php echo $icon; ?> fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-bold text-dark d-flex align-items-center">
                                        <?php echo $type_label; ?>
                                        <a href="print_receipt.php?payment_id=<?php echo $row['payment_id']; ?>" target="_blank" class="ms-2 text-primary no-print" title="Print Receipt"><i class="fa-solid fa-print"></i></a>
                                    </h6>
                                    <small class="text-muted d-block font-monospace mb-1" style="font-size: 0.7rem;">ID: <?php echo $inv_no; ?></small>
                                    <span class="badge bg-light text-muted border fw-normal"><i class="fa-regular fa-clock me-1"></i> <?php echo date('M d, Y h:i A', strtotime($row['date_paid'])); ?></span>
                                </div>
                            </div>
                            
                            <div class="col-md-4 border-md-start border-md-end px-md-4 mb-3 mb-md-0">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted">To Principal:</span>
                                    <span class="fw-bold">₱<?php echo number_format($prin_paid, 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between small">
                                    <span class="text-muted">To Interest:</span>
                                    <span class="fw-bold text-warning">₱<?php echo number_format($int_paid, 2); ?></span>
                                </div>
                            </div>
                            
                            <div class="col-md-3 text-md-end text-start">
                                <small class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.65rem; letter-spacing: 1px;">Total Received</small>
                                <h4 class="fw-bold <?php echo $text_color; ?> mb-1">₱<?php echo number_format($row['amount_paid'], 2); ?></h4>
                                <small class="text-muted d-block" style="font-size: 0.7rem;">Teller: <span class="fw-bold"><?php echo $row['teller_public_id']; ?></span></small>
                            </div>
                            
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm border border-light">
            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3 text-muted border shadow-sm" style="width: 80px; height: 80px;">
                <i class="fa-solid fa-file-invoice-dollar fs-2 opacity-50"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">No Records Found</h5>
            <p class="text-muted small mb-0">No payments have been processed for this transaction yet.</p>
        </div>
    <?php endif; ?>
</div>

<style>
    .list-group-item:hover { background-color: #fcfdfd; }
    @media print {
        .no-print, .navbar, footer { display: none !important; }
        body { background-color: white !important; }
        .card { box-shadow: none !important; border: 1px solid #ccc !important; }
        .list-group-item { border-bottom: 1px solid #ddd !important; break-inside: avoid; }
    }
</style>

<?php include_once '../../includes/teller_footer.php'; ?>