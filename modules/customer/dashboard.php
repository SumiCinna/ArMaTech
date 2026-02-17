<?php
require_once '../../config/database.php';
include_once '../../includes/customer_header.php';

// Fetch Customer Profile ID
$stmt = $conn->prepare("SELECT profile_id FROM accounts WHERE account_id = ?");
$stmt->bind_param("i", $_SESSION['account_id']);
$stmt->execute();
$profile_id = $stmt->get_result()->fetch_assoc()['profile_id'];

// Fetch Active Transactions
$sql = "SELECT t.*, i.brand, i.model, i.device_type 
        FROM transactions t 
        JOIN items i ON t.transaction_id = i.transaction_id 
        WHERE t.customer_id = ? AND t.status IN ('active', 'expired')
        ORDER BY t.maturity_date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$result = $stmt->get_result();
$txns = $result->fetch_all(MYSQLI_ASSOC);

$total_principal = array_sum(array_column($txns, 'principal_amount'));
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-dark">Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?>!</h2>
            <p class="text-muted">Here is an overview of your active pawn transactions.</p>
        </div>
    </div>

    <div class="col-md-12 mb-4">
        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold">Plan your next payment</h4>
                    <p class="mb-0 text-white-50">Use our interest simulator to calculate fees ahead of time.</p>
                </div>
                <a href="interest_calculator.php" class="btn btn-light fw-bold text-primary px-4 py-2 rounded-pill">
                    Open Calculator
                </a>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="stat-card bg-white p-4 mb-3">
                <h6 class="text-muted text-uppercase small fw-bold">Active Pawns</h6>
                <h2 class="fw-bold text-primary mb-0"><?php echo count($txns); ?></h2>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-card bg-white p-4 mb-3">
                <h6 class="text-muted text-uppercase small fw-bold">Total Principal</h6>
                <h2 class="fw-bold text-danger mb-0">₱<?php echo number_format($total_principal, 2); ?></h2>
            </div>
        </div>
    </div>

    <h5 class="mb-3 fw-bold text-secondary">Your Active Items</h5>
    
    <?php if (count($txns) > 0): ?>
        <?php foreach ($txns as $t): ?>
            <?php 
                // Status Logic
                $status_style = "border-left-color: #0d6efd;"; // Blue Default
                $status_text = "Active";
                $today = date('Y-m-d');
                
                if ($t['status'] == 'expired') {
                    $status_style = "border-left-color: #dc3545;"; // Red
                    $status_text = "Expired";
                } elseif ($today > $t['maturity_date']) {
                    $status_style = "border-left-color: #ffc107;"; // Yellow
                    $status_text = "Overdue";
                }
            ?>
            <div class="card loan-card p-3" style="<?php echo $status_style; ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-1"><?php echo $t['brand'] . ' ' . $t['model']; ?></h5>
                        <small class="text-muted text-uppercase"><?php echo $t['pt_number']; ?> • <?php echo $t['device_type']; ?></small>
                    </div>
                    <div class="text-end">
                        <h4 class="fw-bold text-dark mb-0">₱<?php echo number_format($t['principal_amount'], 2); ?></h4>
                        <small class="text-danger fw-bold">Due: <?php echo date('M d, Y', strtotime($t['maturity_date'])); ?></small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-light text-center py-5 shadow-sm">
            <i class="fa-solid fa-box-open fa-3x text-muted mb-3"></i>
            <p class="text-muted">You have no active pawn transactions at the moment.</p>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../../includes/customer_footer.php'; ?>