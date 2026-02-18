<?php
require_once '../../config/database.php';
include_once '../../includes/customer_header.php';

// 1. GET PROFILE ID
$stmt = $conn->prepare("SELECT profile_id FROM accounts WHERE account_id = ?");
$stmt->bind_param("i", $_SESSION['account_id']);
$stmt->execute();
$profile_id = $stmt->get_result()->fetch_assoc()['profile_id'];

// 2. FETCH ACTIVE TRANSACTIONS
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

// 3. CALCULATE PORTFOLIO TOTALS
$total_principal = 0;
$total_interest = 0;
$next_due_date = null;

foreach ($txns as $key => $t) {
    // A. Principal
    $total_principal += $t['principal_amount'];

    // B. Calculate Dynamic Interest (Real-time)
    $date_pawned = new DateTime($t['date_pawned']);
    $today = new DateTime();
    $days_passed = $date_pawned->diff($today)->days;
    
    // Logic: Minimum 1 month, otherwise ceil(days/30)
    $months = ceil($days_passed / 30);
    if ($months < 1) $months = 1;
    
    $interest_amt = ($t['principal_amount'] * 0.03) * $months;
    $total_interest += $interest_amt;

    // C. Find Next Due Date (Earliest maturity date)
    if ($next_due_date === null || $t['maturity_date'] < $next_due_date) {
        $next_due_date = $t['maturity_date'];
    }

    // Add calculated interest to the array so we can use it in the loop later
    $txns[$key]['current_interest'] = $interest_amt;
    $txns[$key]['days_passed'] = $days_passed;
}

$grand_total = $total_principal + $total_interest;
?>

<div class="container pb-5">

    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">My Dashboard</h2>
            <p class="text-muted mb-0">Overview of your active assets and loans.</p>
        </div>
        <div class="d-none d-md-block">
            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
                <i class="fa-regular fa-calendar me-2"></i> <?php echo date('F d, Y'); ?>
            </span>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-lg text-white rounded-4 position-relative overflow-hidden" 
                 style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); min-height: 220px;">
                
                <div class="position-absolute top-0 end-0 bg-white opacity-10 rounded-circle" style="width: 200px; height: 200px; margin-right: -50px; margin-top: -50px;"></div>

                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-white-50 text-uppercase fw-bold letter-spacing-1">Total Redeem Value</small>
                            <h1 class="fw-bold mb-0">₱<?php echo number_format($grand_total, 2); ?></h1>
                        </div>
                        <div class="text-end">
                            <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                <i class="fa-solid fa-wallet fs-5"></i>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-6 border-end border-white border-opacity-25">
                            <small class="text-white-50 d-block">Principal Debt</small>
                            <span class="fw-bold fs-5">₱<?php echo number_format($total_principal, 2); ?></span>
                        </div>
                        <div class="col-6 ps-4">
                            <small class="text-white-50 d-block">Accrued Interest</small>
                            <span class="fw-bold fs-5 text-warning">+ ₱<?php echo number_format($total_interest, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100 rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-muted text-uppercase small mb-3">Quick Actions</h6>
                    
                    <a href="interest_calculator.php" class="d-flex align-items-center p-3 bg-light rounded-3 text-decoration-none mb-3 border hover-shadow transition">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            <i class="fa-solid fa-calculator"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-0">Interest Simulator</h6>
                            <small class="text-muted">Calculate fees before visiting</small>
                        </div>
                        <i class="fa-solid fa-chevron-right ms-auto text-muted small"></i>
                    </a>

                    <div class="alert alert-warning border-0 d-flex align-items-center mb-0 py-2">
                        <i class="fa-regular fa-clock me-2"></i>
                        <small><strong>Next Due:</strong> <?php echo $next_due_date ? date('M d', strtotime($next_due_date)) : 'None'; ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h5 class="fw-bold text-dark mb-3">Active Items <span class="badge bg-primary rounded-pill align-middle ms-2" style="font-size: 0.6em;"><?php echo count($txns); ?></span></h5>

    <div class="row g-3">
        <?php if (count($txns) > 0): ?>
            <?php foreach ($txns as $t): ?>
                <?php 
                    // Status & Color Logic
                    $due_date = new DateTime($t['maturity_date']);
                    $today = new DateTime();
                    $days_until_due = $today->diff($due_date)->format("%r%a"); // %r gives - sign if past
                    
                    $status_badge = '<span class="badge bg-success bg-opacity-10 text-success">Safe</span>';
                    $border_color = 'border-success';
                    $progress_val = 25;
                    $progress_color = 'bg-success';

                    if ($t['status'] == 'expired') {
                        $status_badge = '<span class="badge bg-danger">EXPIRED / FORECLOSED</span>';
                        $border_color = 'border-danger';
                        $progress_val = 100;
                        $progress_color = 'bg-danger';
                    } elseif ($days_until_due <= 0) {
                        $status_badge = '<span class="badge bg-danger text-white">MATURED / OVERDUE (' . abs($days_until_due) . ' days)</span>';
                        $border_color = 'border-danger';
                        $progress_val = 100;
                        $progress_color = 'bg-danger';
                    } elseif ($days_until_due <= 3) {
                        $status_badge = '<span class="badge bg-warning bg-opacity-25 text-dark">Due Soon (' . $days_until_due . ' days)</span>';
                        $border_color = 'border-warning';
                        $progress_val = 75;
                        $progress_color = 'bg-warning';
                    }
                    
                    // Total to pay for this specific item
                    $item_total = $t['principal_amount'] + $t['current_interest'];
                ?>

                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 position-relative">
                        <div class="position-absolute start-0 top-0 bottom-0 rounded-start-4 <?php echo $progress_color; ?>" style="width: 6px;"></div>
                        
                        <div class="card-body ps-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;"><?php echo $t['device_type']; ?></small>
                                    <h5 class="fw-bold mb-0 text-dark"><?php echo $t['brand'] . ' ' . $t['model']; ?></h5>
                                </div>
                                <div class="text-end">
                                    <small class="d-block text-muted" style="font-size: 0.7rem;">Est. Redemption</small>
                                    <span class="fw-bold text-dark fs-5">₱<?php echo number_format($item_total, 2); ?></span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
                                <span class="font-monospace bg-light px-2 rounded small text-muted">PT Number: <?php echo $t['pt_number']; ?></span>
                                <?php echo $status_badge; ?>
                            </div>

                            <div class="mt-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted">Loan Duration</span>
                                    <span class="<?php echo ($days_until_due < 0) ? 'text-danger fw-bold' : 'text-success'; ?>">
                                        Maturity: <?php echo date('M d', strtotime($t['maturity_date'])); ?>
                                    </span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar <?php echo $progress_color; ?>" role="progressbar" style="width: <?php echo $progress_val; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="text-center py-5">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fa-solid fa-check text-muted fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-secondary">No Active Loans</h5>
                    <p class="text-muted">You are debt-free! Your portfolio is clear.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .hover-shadow:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
    .transition { transition: all 0.3s ease; }
    .letter-spacing-1 { letter-spacing: 1px; }
</style>

<?php include_once '../../includes/customer_footer.php'; ?>