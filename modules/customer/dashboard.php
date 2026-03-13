<?php
// modules/customer/dashboard.php
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
    $total_principal += $t['principal_amount'];

    // ACCURACY FIX: Use last_renewed_date to prevent double-charging display
    $start_date_str = isset($t['last_renewed_date']) ? $t['last_renewed_date'] : $t['date_pawned'];
    $start_date = new DateTime($start_date_str);
    $today = new DateTime();
    
    $days_passed = $start_date->diff($today)->days;
    
    // Logic: Minimum 1 month, otherwise ceil(days/30)
    $months = ceil($days_passed / 30);
    if ($months < 1) $months = 1;
    
    $interest_amt = ($t['principal_amount'] * 0.03) * $months;
    $total_interest += $interest_amt;

    // Find Next Due Date (Earliest maturity date)
    if ($next_due_date === null || $t['maturity_date'] < $next_due_date) {
        $next_due_date = $t['maturity_date'];
    }

    // Save calculations for the UI loop
    $txns[$key]['current_interest'] = $interest_amt;
    $txns[$key]['days_passed'] = $days_passed;
    $txns[$key]['calc_start_date'] = $start_date_str;
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
            <span class="badge bg-white text-dark border shadow-sm px-3 py-2 rounded-pill fw-bold">
                <i class="fa-regular fa-calendar text-primary me-2"></i> <?php echo date('F d, Y'); ?>
            </span>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm text-white rounded-4 position-relative overflow-hidden" 
                 style="background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%); min-height: 220px;">
                

                <div class="card-body p-4 p-md-5 d-flex flex-column justify-content-between position-relative z-index-1">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-white-50 text-uppercase fw-bold letter-spacing-1">Total Redemption Value</small>
                            <h1 class="display-5 fw-bold mb-0">₱<?php echo number_format($grand_total, 2); ?></h1>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded-3 d-flex align-items-center justify-content-center p-3">
                            <i class="fa-solid fa-wallet fs-3"></i>
                        </div>
                    </div>

                    <div class="row mt-4 pt-3 border-top border-white border-opacity-10">
                        <div class="col-6 border-end border-white border-opacity-25">
                            <small class="text-white-50 d-block text-uppercase" style="font-size: 0.7rem;">Principal Debt</small>
                            <span class="fw-bold fs-5">₱<?php echo number_format($total_principal, 2); ?></span>
                        </div>
                        <div class="col-6 ps-4">
                            <small class="text-white-50 d-block text-uppercase" style="font-size: 0.7rem;">Accrued Interest</small>
                            <span class="fw-bold fs-5 text-warning">+ ₱<?php echo number_format($total_interest, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 d-flex flex-column gap-3">
            <div class="card border-0 shadow-sm rounded-4 flex-grow-1">
                <div class="card-body p-4 d-flex flex-column justify-content-center">
                    <h6 class="fw-bold text-muted text-uppercase small mb-3">Quick Actions</h6>
                    
                    <a href="interest_calculator.php" class="d-flex align-items-center p-3 bg-light rounded-3 text-decoration-none mb-3 border hover-lift">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                            <i class="fa-solid fa-calculator"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-0">Interest Simulator</h6>
                            <small class="text-muted" style="font-size: 0.75rem;">Calculate fees before visiting</small>
                        </div>
                        <i class="fa-solid fa-chevron-right ms-auto text-muted small"></i>
                    </a>

                    <?php if ($next_due_date): ?>
                        <div class="alert alert-warning border-0 d-flex align-items-center mb-0 py-3 rounded-3">
                            <i class="fa-regular fa-clock fs-4 me-3 text-warning"></i>
                            <div>
                                <strong class="d-block text-dark">Next Deadline</strong>
                                <span class="small text-muted"><?php echo date('F d, Y', strtotime($next_due_date)); ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success border-0 d-flex align-items-center mb-0 py-3 rounded-3">
                            <i class="fa-solid fa-check-circle fs-4 me-3 text-success"></i>
                            <div>
                                <strong class="d-block text-dark">All Clear</strong>
                                <span class="small text-muted">No upcoming deadlines.</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 mt-2">
        <h5 class="fw-bold text-dark mb-0">Active Loans</h5>
        <span class="badge bg-primary rounded-pill"><?php echo count($txns); ?> Items</span>
    </div>

    <div class="row g-4">
        <?php if (count($txns) > 0): ?>
            <?php foreach ($txns as $t): ?>
                <?php 
                    // Dynamic Status & Progress Logic
                    $start_d = new DateTime($t['calc_start_date']);
                    $due_date = new DateTime($t['maturity_date']);
                    $today = new DateTime();
                    
                    $days_until_due = $today->diff($due_date)->format("%r%a"); 
                    $total_cycle_days = $start_d->diff($due_date)->days;
                    if($total_cycle_days <= 0) $total_cycle_days = 30; // Fallback
                    
                    $progress_percent = min(100, max(0, ($t['days_passed'] / $total_cycle_days) * 100));

                    $status_badge = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill"><i class="fa-solid fa-shield-check me-1"></i> Active</span>';
                    $progress_color = 'bg-success';

                    if ($t['status'] == 'expired') {
                        $status_badge = '<span class="badge bg-danger px-3 py-2 rounded-pill"><i class="fa-solid fa-gavel me-1"></i> FORECLOSED</span>';
                        $progress_percent = 100;
                        $progress_color = 'bg-danger';
                    } elseif ($days_until_due <= 0) {
                        $status_badge = '<span class="badge bg-danger px-3 py-2 rounded-pill"><i class="fa-solid fa-triangle-exclamation me-1"></i> OVERDUE (' . abs($days_until_due) . ' days)</span>';
                        $progress_percent = 100;
                        $progress_color = 'bg-danger';
                    } elseif ($days_until_due <= 5) {
                        $status_badge = '<span class="badge bg-warning bg-opacity-25 text-dark border border-warning px-3 py-2 rounded-pill"><i class="fa-solid fa-clock me-1"></i> Due in ' . $days_until_due . ' days</span>';
                        $progress_color = 'bg-warning';
                    }
                    
                    $item_total = $t['principal_amount'] + $t['current_interest'];

                    // Icon Logic
                    $icon = 'fa-box-open';
                    $cat_lower = strtolower($t['device_type']);
                    if (strpos($cat_lower, 'smartphone') !== false || strpos($cat_lower, 'phone') !== false) $icon = 'fa-mobile-screen-button';
                    elseif (strpos($cat_lower, 'laptop') !== false) $icon = 'fa-laptop';
                    elseif (strpos($cat_lower, 'tablet') !== false) $icon = 'fa-tablet-screen-button';
                ?>

                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden product-card">
                        
                        <div class="bg-light p-3 d-flex justify-content-between align-items-center border-bottom">
                            <span class="font-monospace small text-muted fw-bold">PT# <?php echo $t['pt_number']; ?></span>
                            <?php echo $status_badge; ?>
                        </div>
                        
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-4">
                                <div class="bg-primary bg-opacity-10 text-primary rounded p-3 me-3 text-center" style="width: 60px; height: 60px;">
                                    <i class="fa-solid <?php echo $icon; ?> fs-3"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark"><?php echo $t['brand'] . ' ' . $t['model']; ?></h6>
                                    <small class="text-muted text-uppercase" style="font-size: 0.7rem;"><?php echo $t['device_type']; ?></small>
                                </div>
                            </div>

                            <div class="bg-light rounded-3 p-3 mb-4 border">
                                <div class="d-flex justify-content-between mb-1 small">
                                    <span class="text-muted">Principal</span>
                                    <span class="fw-bold">₱<?php echo number_format($t['principal_amount'], 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2 small">
                                    <span class="text-muted">Interest Due</span>
                                    <span class="fw-bold text-warning">+ ₱<?php echo number_format($t['current_interest'], 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between border-top pt-2 mt-2">
                                    <span class="fw-bold text-dark text-uppercase" style="font-size: 0.8rem;">Total to Pay</span>
                                    <h5 class="fw-bold text-primary mb-0">₱<?php echo number_format($item_total, 2); ?></h5>
                                </div>
                            </div>

                            <div>
                                <div class="d-flex justify-content-between small mb-2">
                                    <span class="text-muted" style="font-size: 0.7rem;">Started: <?php echo date('M d', strtotime($t['calc_start_date'])); ?></span>
                                    <span class="fw-bold <?php echo ($days_until_due < 0) ? 'text-danger' : 'text-dark'; ?>" style="font-size: 0.7rem;">
                                        Maturity: <?php echo date('M d, Y', strtotime($t['maturity_date'])); ?>
                                    </span>
                                </div>
                                <div class="progress bg-light border" style="height: 8px;">
                                    <div class="progress-bar <?php echo $progress_color; ?> progress-bar-striped <?php echo ($progress_percent < 100) ? 'progress-bar-animated' : ''; ?>" role="progressbar" style="width: <?php echo $progress_percent; ?>%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer bg-white border-top-0 p-3 text-center">
                            <small class="text-muted d-block mb-0" style="font-size: 0.7rem;">Visit any ArMaTech branch to pay or renew.</small>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="text-center py-5 bg-white rounded-4 shadow-sm border-0">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                        <i class="fa-solid fa-shield-halved text-success opacity-50 fa-3x"></i>
                    </div>
                    <h4 class="fw-bold text-dark">No Active Loans</h4>
                    <p class="text-muted mb-4">You are currently debt-free! Your portfolio is clear.</p>
                    <a href="shop.php" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">Browse Shop Deals</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .hover-lift:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.1) !important; }
    .product-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .product-card:hover { transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.1) !important; }
    .letter-spacing-1 { letter-spacing: 1px; }
</style>

<?php include_once '../../includes/customer_footer.php'; ?>