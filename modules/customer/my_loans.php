<?php
// modules/customer/my_loans.php
require_once '../../config/database.php';
include_once '../../includes/customer_header.php';

// 1. GET PROFILE ID
$stmt = $conn->prepare("SELECT profile_id FROM accounts WHERE account_id = ?");
$stmt->bind_param("i", $_SESSION['account_id']);
$stmt->execute();
$profile_id = $stmt->get_result()->fetch_assoc()['profile_id'];

// 2. Handle Filtering (Active vs All History)
$filter = $_GET['filter'] ?? 'active';
$status_condition = "t.status IN ('active', 'expired')"; // Default: Only active/overdue

if ($filter === 'history') {
    $status_condition = "1=1"; // Show everything
}

// 3. FETCH TRANSACTIONS
$sql = "SELECT t.*, i.brand, i.model, i.device_type, i.img_front 
        FROM transactions t 
        JOIN items i ON t.transaction_id = i.transaction_id 
        WHERE t.customer_id = ? AND $status_condition
        ORDER BY CASE WHEN t.status IN ('active', 'expired') THEN 0 ELSE 1 END, t.maturity_date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$result = $stmt->get_result();
$txns = $result->fetch_all(MYSQLI_ASSOC);

// 4. AGGREGATE STATS (Only for Active Portfolio)
$all_borrowed = 0;
$risk_count = 0;
$total_units = 0;

foreach ($txns as $t) {
    if ($t['status'] == 'active' || $t['status'] == 'expired') {
        $all_borrowed += $t['principal_amount'];
        $total_units++;
        
        $due = new DateTime($t['maturity_date']);
        $today = new DateTime();
        $days = $today->diff($due)->format("%r%a");
        if ($days <= 7) $risk_count++;
    }
}
?>

<style>
    /* Premium FinTech UI */
    :root {
        --fintech-bg: #f4f7f6;
        --fintech-primary: #0d6efd;
    }
    body { background-color: var(--fintech-bg); }
    
    .loan-card { transition: transform 0.2s ease, box-shadow 0.2s ease; border: 1px solid transparent; }
    .loan-card:hover { transform: translateY(-4px); box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important; border-color: rgba(13, 110, 253, 0.15); }
    
    .nav-pills .nav-link { color: #64748b; font-weight: 600; border-radius: 2rem; padding: 0.5rem 1.5rem; transition: all 0.2s ease; border: 1px solid transparent; }
    .nav-pills .nav-link:not(.active):hover { background-color: rgba(0,0,0,0.05); color: var(--fintech-primary); }
    .nav-pills .nav-link.active { background-color: var(--fintech-primary); color: white !important; box-shadow: 0 4px 10px rgba(13,110,253,0.2); }
    
    .status-indicator { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 6px; }
</style>

<div class="container py-5">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold text-dark mb-0"><i class="fa-solid fa-layer-group me-2 text-primary"></i> My Portfolio</h2>
            <p class="text-muted mb-0 mt-1">Manage and track your active assets.</p>
        </div>
        
        <ul class="nav nav-pills bg-light p-1 rounded-pill shadow-sm border">
            <li class="nav-item">
                <a class="nav-link <?php echo ($filter === 'active') ? 'active' : ''; ?>" href="?filter=active">Active Loans</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($filter === 'history') ? 'active' : ''; ?>" href="?filter=history">Full History</a>
            </li>
        </ul>
    </div>

    <!-- Statistical Header Summary -->
    <?php if ($filter === 'active' && count($txns) > 0): ?>
    <div class="row g-3 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bento-mini bg-white h-100">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-3 me-3">
                        <i class="fa-solid fa-hand-holding-dollar fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-bold ls-1" style="font-size: 0.65rem;">Total Borrowed</small>
                        <h4 class="fw-bold mb-0 text-dark">₱<?php echo number_format($all_borrowed, 2); ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bento-mini bg-white h-100">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="<?php echo ($risk_count > 0) ? 'bg-danger text-white shadow-sm' : 'bg-primary bg-opacity-10 text-primary'; ?> rounded-circle p-3 me-3">
                        <i class="fa-solid fa-clock-rotate-left fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-bold ls-1" style="font-size: 0.65rem;">Due This Week</small>
                        <h4 class="fw-bold mb-0 <?php echo ($risk_count > 0) ? 'text-danger' : 'text-dark'; ?>"><?php echo $risk_count; ?> Items</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bento-mini bg-white h-100">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="bg-dark bg-opacity-10 text-dark rounded-circle p-3 me-3">
                        <i class="fa-solid fa-box-open fs-5"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-bold ls-1" style="font-size: 0.65rem;">Active Units</small>
                        <h4 class="fw-bold mb-0 text-dark"><?php echo $total_units; ?> Items</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php if (count($txns) > 0): ?>
            <?php foreach ($txns as $t): ?>
                <?php 
                    // Calculations
                    $start_date_str = isset($t['last_renewed_date']) ? $t['last_renewed_date'] : $t['date_pawned'];
                    $start_d = new DateTime($start_date_str);
                    $due_date = new DateTime($t['maturity_date']);
                    $today = new DateTime();
                    
                    $days_passed = $start_d->diff($today)->days;
                    $months = ceil($days_passed / 30);
                    if ($months < 1) $months = 1;
                    
                    $interest_amt = 0;
                    $days_until_due = $today->diff($due_date)->format("%r%a"); 
                    
                    // Status & Colors
                    $status_bg = 'bg-secondary';
                    $status_text = 'text-secondary';
                    $status_label = ucfirst($t['status']);
                    $progress_val = 100;
                    
                    if ($t['status'] == 'active') {
                        $interest_amt = ($t['principal_amount'] * 0.03) * $months;
                        
                        if ($days_until_due < 0) {
                            // Overdue but not yet foreclosed (in grace period)
                            $status_bg = 'bg-danger';
                            $status_text = 'text-danger';
                            $status_label = 'Overdue by ' . abs($days_until_due) . ' Days';
                        } elseif ($days_until_due <= 5) {
                            // Warning: Due Soon
                            $status_bg = 'bg-warning';
                            $status_text = 'text-dark';
                            $status_label = 'Due in ' . $days_until_due . ' Days';
                            
                            $total_cycle = max(1, $start_d->diff($due_date)->days);
                            $progress_val = min(100, max(0, ($days_passed / $total_cycle) * 100));
                        } else {
                            // Normal Active
                            $status_bg = 'bg-success';
                            $status_text = 'text-success';
                            $status_label = 'Active';
                            
                            $total_cycle = max(1, $start_d->diff($due_date)->days);
                            $progress_val = min(100, max(0, ($days_passed / $total_cycle) * 100));
                        }
                    } elseif ($t['status'] == 'expired') {
                        $status_bg = 'bg-danger';
                        $status_text = 'text-danger';
                        $status_label = 'Foreclosed';
                        $interest_amt = ($t['principal_amount'] * 0.03) * $months; 
                    } elseif ($t['status'] == 'redeemed') {
                        $status_bg = 'bg-dark';
                        $status_text = 'text-dark';
                    } elseif ($t['status'] == 'auctioned') {
                        $status_bg = 'bg-primary';
                        $status_text = 'text-primary';
                    }
                    
                    $total_due = $t['principal_amount'] + $interest_amt;

                    // Icon Logic
                    $icon = 'fa-box-open';
                    $cat_lower = strtolower($t['device_type']);
                    if (strpos($cat_lower, 'smartphone') !== false || strpos($cat_lower, 'phone') !== false) $icon = 'fa-mobile-screen-button';
                    elseif (strpos($cat_lower, 'laptop') !== false) $icon = 'fa-laptop';
                    elseif (strpos($cat_lower, 'tablet') !== false) $icon = 'fa-tablet-screen-button';
                ?>
                
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden loan-card">
                        
                        <div class="bg-light p-3 d-flex justify-content-between align-items-center border-bottom">
                            <span class="font-monospace small text-muted fw-bold"><i class="fa-solid fa-receipt me-1 opacity-50"></i> <?php echo $t['pt_number']; ?></span>
                            <span class="badge <?php echo $status_bg . (($status_bg == 'bg-warning') ? '' : ' text-white'); ?> border border-opacity-25 px-3 py-2 rounded-pill shadow-sm" style="font-size: 0.7rem;">
                                <?php if($status_label == 'Active'): ?>
                                    <i class="fa-solid fa-shield-check me-1"></i>
                                <?php elseif(strpos($status_label, 'Due in') !== false): ?>
                                    <i class="fa-solid fa-clock me-1"></i>
                                <?php elseif(strpos($status_label, 'Overdue') !== false || $status_label == 'Foreclosed'): ?>
                                    <i class="fa-solid fa-triangle-exclamation me-1 text-white"></i>
                                <?php endif; ?>
                                <?php echo $status_label; ?>
                            </span>
                        </div>
                        
                        <div class="card-body p-4">
                            <div class="d-flex align-items-start mb-4">
                                <?php 
                                    $upload_dir = '../../uploads/pawn_items/';
                                    $has_photo = !empty($t['img_front']) && file_exists($upload_dir . $t['img_front']);
                                ?>
                                <?php if ($has_photo): ?>
                                    <div class="rounded-4 overflow-hidden me-3 border shadow-sm" style="width: 70px; height: 70px; flex-shrink: 0;">
                                        <img src="<?php echo $upload_dir . $t['img_front']; ?>" class="w-100 h-100 object-fit-cover" alt="Item">
                                    </div>
                                <?php else: ?>
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-4 p-3 me-3 text-center d-flex align-items-center justify-content-center border border-primary border-opacity-25" style="width: 70px; height: 70px; flex-shrink: 0;">
                                        <i class="fa-solid <?php echo $icon; ?> fs-3"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="flex-grow-1 min-w-0">
                                    <h6 class="fw-bold mb-1 text-dark fs-6 text-truncate" title="<?php echo htmlspecialchars($t['brand'] . ' ' . $t['model']); ?>" style="line-height: 1.2;">
                                        <?php echo htmlspecialchars($t['brand'] . ' ' . $t['model']); ?>
                                    </h6>
                                    <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.6rem; letter-spacing: 0.5px;"><?php echo htmlspecialchars($t['device_type']); ?></small>
                                </div>
                            </div>

                            <div class="bg-light rounded-4 p-3 mb-4 border shadow-sm" style="background-color: #fbfbfc !important;">
                                <div class="d-flex justify-content-between mb-2 small">
                                    <span class="text-muted fw-bold">Borrowed Amount</span>
                                    <span class="fw-bold text-dark">₱<?php echo number_format($t['principal_amount'], 2); ?></span>
                                </div>
                                <?php if ($t['status'] == 'active' || $t['status'] == 'expired'): ?>
                                <div class="d-flex justify-content-between mb-3 small">
                                    <span class="text-muted fw-bold">Interest Fees</span>
                                    <span class="fw-bold text-warning">+ ₱<?php echo number_format($interest_amt, 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between border-top border-secondary border-opacity-10 pt-2 mt-2">
                                    <span class="fw-bold text-dark text-uppercase ls-1" style="font-size: 0.7rem;">Total to PayBack</span>
                                    <h5 class="fw-bold text-primary mb-0">₱<?php echo number_format($total_due, 2); ?></h5>
                                </div>
                                <?php else: ?>
                                <div class="d-flex justify-content-between border-top border-secondary border-opacity-10 pt-2 mt-2">
                                    <span class="fw-bold text-muted text-uppercase ls-1" style="font-size: 0.7rem;">Final Status</span>
                                    <h6 class="fw-bold text-muted mb-0">Loan Settled</h6>
                                </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($t['status'] == 'active'): ?>
                                <div class="d-flex justify-content-between small mb-2">
                                    <span class="text-muted fw-bold" style="font-size: 0.7rem;">Issued on <?php echo date('M d', strtotime($start_date_str)); ?></span>
                                    <span class="fw-bold <?php echo $status_text; ?>" style="font-size: 0.7rem;">
                                        Due on <?php echo date('M d, Y', strtotime($t['maturity_date'])); ?>
                                    </span>
                                </div>
                                <div class="progress bg-secondary bg-opacity-10 border-0" style="height: 6px; border-radius: 10px;">
                                    <div class="progress-bar <?php echo $status_bg; ?> <?php echo ($progress_val < 100) ? 'progress-bar-striped progress-bar-animated' : ''; ?>" role="progressbar" style="width: <?php echo $progress_val; ?>%; border-radius: 10px;"></div>
                                </div>
                            <?php elseif ($t['status'] == 'redeemed'): ?>
                                <div class="text-center py-1">
                                    <span class="badge bg-dark bg-opacity-10 text-dark border-0 px-3 py-2 rounded-pill w-100"><i class="fa-solid fa-lock me-1"></i> Item Released</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-footer bg-white border-top-0 p-3 pt-0">
                            <a href="view_details.php?id=<?php echo $t['transaction_id']; ?>" class="btn btn-outline-primary rounded-pill fw-bold w-100 shadow-sm py-2 btn-sm transition-all">
                                <i class="fa-solid fa-eye me-1"></i> View Full Ticket
                            </a>
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
                    <h4 class="fw-bold text-dark">No Loans Found</h4>
                    <p class="text-muted mb-4">You do not have any records matching this filter.</p>
                    <a href="shop.php" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">Browse ArMaTech Deals</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../../includes/customer_footer.php'; ?>