<?php
// modules/admin/view_transaction_details.php
require_once '../../config/database.php';
require_once '../../core/functions.php'; 
include_once '../../includes/admin_header.php';

// 1. SECURITY: Get Transaction ID
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$trans_id = intval($_GET['id']);

// 2. FETCH TRANSACTION, ITEM & CUSTOMER DETAILS
// We now select extra_specs to display the dynamic Python API data
$sql = "SELECT t.*, 
               i.device_type, i.brand, i.model, i.serial_number, i.inclusions, i.condition_notes, 
               i.img_front, i.img_back, i.img_serial, i.ram, i.storage_capacity, i.extra_specs,
               p.first_name, p.last_name, p.contact_number, p.email, p.public_id as cust_public_id,
               a.username as processed_by_user, tp.first_name as teller_fname, tp.last_name as teller_lname,
               b.first_name as buyer_fname, b.last_name as buyer_lname, b.contact_number as buyer_contact, b.public_id as buyer_public_id
        FROM transactions t
        JOIN items i ON t.transaction_id = i.transaction_id
        JOIN profiles p ON t.customer_id = p.profile_id
        LEFT JOIN accounts a ON t.teller_id = a.account_id
        LEFT JOIN profiles tp ON a.profile_id = tp.profile_id
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

// Calculate Interest
$start_date = isset($t['last_renewed_date']) ? $t['last_renewed_date'] : $t['date_pawned'];
$calc = calculatePawnInterest($t['principal_amount'], $start_date);

// 3. FETCH PAYMENT HISTORY 
$sql_pay = "SELECT py.*, a.username as teller_name, tp.first_name as t_fname, tp.last_name as t_lname 
            FROM payments py
            LEFT JOIN accounts a ON py.teller_id = a.account_id
            LEFT JOIN profiles tp ON a.profile_id = tp.profile_id
            WHERE py.transaction_id = ? 
            ORDER BY py.date_paid DESC";
$stmt_p = $conn->prepare($sql_pay);
$stmt_p->bind_param("i", $trans_id);
$stmt_p->execute();
$payments = $stmt_p->get_result();

// ==========================================
// Device Icon Logic (Updated for New Categories)
// ==========================================
$dt = $t['device_type'];
$device_icon = 'fa-box';
if ($dt == 'Smartphone' || $dt == 'Smartphones' || $dt == 'Tablet' || $dt == 'Tablets') {
    $device_icon = 'fa-mobile-screen';
} elseif ($dt == 'Laptop' || $dt == 'Laptops & Computers') {
    $device_icon = 'fa-laptop';
} elseif ($dt == 'Camera' || $dt == 'Cameras & Lenses') {
    $device_icon = 'fa-camera';
} elseif ($dt == 'Gaming Console' || $dt == 'Gaming Consoles') {
    $device_icon = 'fa-gamepad';
} elseif ($dt == 'Smartwatch' || $dt == 'Wearables') {
    $device_icon = 'fa-clock';
} elseif ($dt == 'Audio Equipment') {
    $device_icon = 'fa-headphones';
}

// Status & Color Logic
$status = $t['status'];
$status_label = strtoupper($status);
$bg_status = 'secondary';
$text_status = 'text-secondary';
$icon_status = 'fa-circle-question';

if ($status == 'active') {
    $bg_status = 'success';
    $text_status = 'text-success';
    $icon_status = 'fa-circle-check';
} elseif ($status == 'expired' || $status == 'overdue') {
    $bg_status = 'danger';
    $text_status = 'text-danger';
    $icon_status = 'fa-clock';
} elseif ($status == 'redeemed') {
    $bg_status = 'dark';
    $text_status = 'text-dark';
    $icon_status = 'fa-lock';
} elseif ($status == 'auctioned') {
    $bg_status = 'primary';
    $text_status = 'text-primary';
    $icon_status = 'fa-gavel';
}

// Lifecycle Percent Calculate
$pawn_time = strtotime($t['date_pawned']);
$mature_time = strtotime($t['maturity_date']);
$expire_time = strtotime($t['expiry_date']);
$now = time();

$progress_val = 0;
$current_stage = 1; 

if ($now < $mature_time) {
    $progress_val = (($now - $pawn_time) / ($mature_time - $pawn_time)) * 50; 
    $current_stage = 1;
} elseif ($now < $expire_time) {
    $progress_val = 50 + ((($now - $mature_time) / ($expire_time - $mature_time)) * 50);
    $current_stage = 2;
} else {
    $progress_val = 100;
    $current_stage = 3;
}
if ($status == 'redeemed' || $status == 'auctioned') $progress_val = 100;

// Parse Dynamic JSON Specs (From Python API)
$dynamic_specs = [];
if (!empty($t['extra_specs'])) {
    $dynamic_specs = json_decode($t['extra_specs'], true) ?: [];
}
?>

<div class="container-fluid px-4 pb-5">
    
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item small"><a href="dashboard.php" class="text-decoration-none text-muted">Management</a></li>
                    <li class="breadcrumb-item small active">Loan Details</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-dark mb-0 d-flex align-items-center">
                <i class="fa-solid fa-file-invoice-dollar me-2 text-primary opacity-50"></i>
                Transaction ID 
                <span class="ms-2 font-monospace text-primary bg-primary bg-opacity-10 px-3 py-1 rounded-pill border border-primary border-opacity-25" style="font-size: 0.6em;"><?php echo $t['pt_number']; ?></span>
            </h3>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-white border shadow-sm fw-bold text-secondary px-4 rounded-pill btn-hover-lift" onclick="window.print()">
                <i class="fa-solid fa-print me-2"></i> Print Ticket
            </button>
            <a href="javascript:history.back()" class="btn btn-dark fw-bold shadow-sm px-4 rounded-pill btn-hover-lift">
                <i class="fa-solid fa-arrow-left me-2 text-white-50"></i> Return
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 position-relative overflow-hidden kpi-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-muted text-uppercase fw-bold ls-1" style="font-size: 0.65rem;">Principal Amount</small>
                            <h2 class="fw-bold text-dark mb-1 mt-1">₱<?php echo number_format($t['principal_amount'], 2); ?></h2>
                            <span class="badge bg-secondary-subtle text-secondary fw-normal">Initial Disbursement</span>
                        </div>
                        <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2">
                            <i class="fa-solid fa-hand-holding-dollar fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 position-relative overflow-hidden kpi-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-muted text-uppercase fw-bold ls-1" style="font-size: 0.65rem;">Accumulated Interest</small>
                            <h2 class="fw-bold <?php echo ($calc['interest'] > 0) ? 'text-warning' : 'text-dark'; ?> mb-1 mt-1">
                                + ₱<?php echo number_format($calc['interest'], 2); ?>
                            </h2>
                            <span class="badge bg-warning-subtle text-warning fw-normal">Accrued 3% Monthly</span>
                        </div>
                        <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-2">
                            <i class="fa-solid fa-chart-line fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 position-relative overflow-hidden kpi-card border-2 border-primary border-opacity-10" style="background-color: #f0f7ff;">
                <div class="card-body p-4 text-center">
                    <small class="text-primary text-uppercase fw-bold ls-1" style="font-size: 0.65rem;">Total Redemption Value</small>
                    <h1 class="fw-bold text-primary mb-1 mt-1" style="font-size: 2.5rem; letter-spacing: -1px;">
                        ₱<?php echo number_format($calc['total'], 2); ?>
                    </h1>
                    <p class="text-muted small mb-0"><i class="fa-solid fa-calendar-check me-1"></i> As of <?php echo date('M d, Y h:i A'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-timeline me-2 text-primary"></i> Loan Lifecycle Phases</h6>
                <span class="badge bg-<?php echo $bg_status; ?> text-white px-3 py-2 rounded-pill fw-bold ls-1">
                    <i class="fa-solid <?php echo $icon_status; ?> me-1"></i> STATUS: <?php echo $status_label; ?>
                </span>
            </div>
            
            <div class="position-relative px-4 py-2">
                <div class="progress rounded-pill bg-light shadow-inner mb-4" style="height: 12px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-<?php echo $bg_status; ?>" role="progressbar" style="width: <?php echo $progress_val; ?>%"></div>
                </div>
                
                <div class="d-flex justify-content-between lifecycle-nodes">
                    <div class="text-center node active">
                        <div class="node-icon bg-white text-dark shadow-sm border border-2 border-success"><i class="fa-solid fa-p"></i></div>
                        <small class="d-block fw-bold mt-2">Pawned</small>
                        <span class="text-muted" style="font-size: 0.65rem;"><?php echo date('M d', strtotime($t['date_pawned'])); ?></span>
                    </div>
                    <div class="text-center node <?php echo ($current_stage >= 2) ? 'active' : 'upcoming'; ?>">
                        <div class="node-icon bg-white text-dark shadow-sm border border-2 <?php echo ($current_stage >= 2) ? 'border-primary' : 'border-light-subtle'; ?>"><i class="fa-solid fa-m"></i></div>
                        <small class="d-block fw-bold mt-2">Maturity</small>
                        <span class="text-muted" style="font-size: 0.65rem;"><?php echo date('M d', strtotime($t['maturity_date'])); ?></span>
                    </div>
                    <div class="text-center node <?php echo ($current_stage >= 3) ? 'active' : 'upcoming'; ?>">
                        <div class="node-icon bg-white text-dark shadow-sm border border-2 <?php echo ($current_stage >= 3) ? 'border-danger' : 'border-light-subtle'; ?>"><i class="fa-solid fa-e"></i></div>
                        <small class="d-block fw-bold mt-2">Default</small>
                        <span class="text-muted" style="font-size: 0.65rem;"><?php echo date('M d', strtotime($t['expiry_date'])); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        
        <div class="col-lg-8">
            
            <div class="card shadow-sm border-0 mb-4 rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="row g-0">
                        <div class="col-md-4 bg-light d-flex flex-column justify-content-center align-items-center p-4 border-end">
                            <div class="bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center mb-3 text-primary border border-primary border-opacity-25" style="width: 80px; height: 80px;">
                                <i class="fa-solid <?php echo $device_icon; ?> fa-2x"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-0 text-center"><?php echo htmlspecialchars($t['device_type']); ?></h5>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary mt-2 border border-secondary border-opacity-25 px-3 rounded-pill">Category</span>
                        </div>
                        
                        <div class="col-md-8 p-4 p-lg-5">
                            <h6 class="text-uppercase text-muted fw-bold small mb-4 ls-1 border-bottom pb-2"><i class="fa-solid fa-list-ul me-2 text-primary"></i> Item Specifications</h6>
                            
                            <div class="row g-4 mb-3">
                                <div class="col-sm-6">
                                    <small class="text-muted d-block mb-1 text-uppercase fw-bold" style="font-size: 0.65rem;">Brand & Model</small>
                                    <h6 class="fw-bold text-dark mb-0 fs-5"><?php echo htmlspecialchars($t['brand'] . ' ' . $t['model']); ?></h6>
                                </div>
                                <div class="col-sm-6">
                                    <small class="text-muted d-block mb-1 text-uppercase fw-bold" style="font-size: 0.65rem;">Serial Number</small>
                                    <span class="font-monospace fw-bold text-primary bg-primary bg-opacity-10 px-2 py-1 rounded border border-primary border-opacity-25"><?php echo htmlspecialchars($t['serial_number']) ?: 'N/A'; ?></span>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2 mb-4">
                                <?php 
                                // Show legacy data if it exists
                                if (!empty($t['ram']) && $t['ram'] != 'N/A') {
                                    echo '<span class="badge bg-light text-dark border px-3 py-2 fw-bold shadow-sm">RAM: ' . htmlspecialchars($t['ram']) . '</span>';
                                }
                                if (!empty($t['storage_capacity']) && $t['storage_capacity'] != 'N/A') {
                                    echo '<span class="badge bg-light text-dark border px-3 py-2 fw-bold shadow-sm">Storage: ' . htmlspecialchars($t['storage_capacity']) . '</span>';
                                }
                                
                                // Show Dynamic Python API Data
                                if (!empty($dynamic_specs)) {
                                    foreach ($dynamic_specs as $key => $value) {
                                        // Filter out N/A, empty strings, and 'color' (since we render it specially below)
                                        if (trim($value) === '' || $value === 'N/A' || strtolower($key) === 'color') continue; 
                                        
                                        $clean_label = ucwords(str_replace('_', ' ', $key));
                                        echo '<span class="badge bg-light text-dark border px-3 py-2 fw-bold shadow-sm">' . htmlspecialchars($clean_label) . ': ' . htmlspecialchars($value) . '</span>';
                                    }
                                }

                                // Show Color specifically
                                $color_val = !empty($dynamic_specs['color']) ? $dynamic_specs['color'] : (!empty($t['color']) ? $t['color'] : '');
                                if (!empty($color_val) && $color_val != 'N/A') {
                                    echo '<span class="badge bg-light text-dark border px-3 py-2 fw-bold shadow-sm">Color: ' . htmlspecialchars($color_val) . '</span>';
                                }
                                ?>
                            </div>

                            <h6 class="text-uppercase text-muted fw-bold small mb-3 mt-4 ls-1 border-bottom pb-2"><i class="fa-solid fa-camera me-2 text-primary"></i> Photographic Evidence</h6>
                            
                            <?php 
                            $upload_dir = '../../uploads/pawn_items/';
                            $has_images = !empty($t['img_front']) || !empty($t['img_back']) || !empty($t['img_serial']);
                            ?>

                            <?php if ($has_images): ?>
                                <div class="row g-3 mb-4">
                                    <?php 
                                    $images = [
                                        ['file' => $t['img_front'], 'label' => 'Front View'],
                                        ['file' => $t['img_back'], 'label' => 'Back/Sides'],
                                        ['file' => $t['img_serial'], 'label' => 'Serial/IMEI']
                                    ];
                                    foreach ($images as $img): 
                                    ?>
                                        <div class="col-4">
                                            <div class="card border border-secondary border-opacity-25 shadow-sm rounded-3 overflow-hidden h-100 photo-card">
                                                <?php if (!empty($img['file']) && file_exists($upload_dir . $img['file'])): ?>
                                                    <a href="<?php echo $upload_dir . $img['file']; ?>" target="_blank" class="d-block bg-light" style="height: 120px; overflow: hidden;">
                                                        <img src="<?php echo $upload_dir . htmlspecialchars($img['file']); ?>" alt="<?php echo $img['label']; ?>" class="w-100 h-100 object-fit-cover photo-zoom">
                                                    </a>
                                                <?php else: ?>
                                                    <div class="d-flex align-items-center justify-content-center bg-light text-muted" style="height: 120px;">
                                                        <i class="fa-regular fa-image fa-2x opacity-25"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="card-footer bg-white p-2 text-center border-top-0">
                                                    <small class="fw-bold text-muted text-uppercase" style="font-size: 0.65rem;"><?php echo $img['label']; ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-light border border-secondary border-opacity-25 text-center text-muted mb-4 rounded-3 p-3">
                                    <i class="fa-solid fa-camera-slash fs-4 mb-2 d-block opacity-50"></i>
                                    <small>No photographic evidence was uploaded for this transaction.</small>
                                </div>
                            <?php endif; ?>

                            <div class="row g-4 mt-2">
                                <div class="col-12 border-top pt-3">
                                    <small class="text-muted d-block mb-1 text-uppercase fw-bold" style="font-size: 0.65rem;">Inclusions</small>
                                    <p class="mb-0 text-dark small fw-bold"><?php echo nl2br(htmlspecialchars($t['inclusions'])); ?></p>
                                </div>
                                <div class="col-12">
                                    <div class="p-3 bg-warning bg-opacity-10 rounded-3 border border-warning border-opacity-50 d-flex align-items-start shadow-sm">
                                        <i class="fa-solid fa-triangle-exclamation text-warning mt-1 me-3 fs-5"></i>
                                        <div>
                                            <small class="text-warning text-uppercase fw-bold d-block mb-1 ls-1" style="font-size: 0.7rem;">Condition Notes & Defects</small>
                                            <p class="mb-0 small text-dark fst-italic fw-bold"><?php echo nl2br(htmlspecialchars($t['condition_notes'])) ?: 'No specific issues noted.'; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-4 px-4 d-flex justify-content-between align-items-center border-0">
                    <h5 class="mb-0 fw-bold text-dark">Transaction Feed</h5>
                    <span class="badge bg-light text-dark fw-bold px-3 py-2 border rounded-pill">Activity Log</span>
                </div>
                <div class="card-body p-4 pt-0">
                    <div class="activity-timeline">
                        <?php if ($payments->num_rows > 0): ?>
                            <?php while($pay = $payments->fetch_assoc()): ?>
                                <?php
                                    $p_icon = 'fa-cash-register';
                                    $p_color = 'primary';
                                    $p_label = $pay['payment_type'];
                                    
                                    if ($pay['payment_type'] == 'interest_only') {
                                        $p_icon = 'fa-rotate';
                                        $p_color = 'info';
                                        $p_label = 'Monthly Renewal';
                                    } elseif ($pay['payment_type'] == 'redeem' || $pay['payment_type'] == 'full_redemption') {
                                        $p_icon = 'fa-unlock-keyhole';
                                        $p_color = 'success';
                                        $p_label = 'Full Redemption';
                                    }
                                ?>
                                <div class="timeline-item d-flex gap-4 mb-4">
                                    <div class="timeline-point bg-<?php echo $p_color; ?> shadow-sm">
                                        <i class="fa-solid <?php echo $p_icon; ?> text-white fa-xs"></i>
                                    </div>
                                    <div class="timeline-card p-3 rounded-4 border bg-light bg-opacity-25 flex-grow-1 shadow-xs transition-hover">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($p_label); ?></h6>
                                                <small class="text-muted"><?php echo date('F d, Y \a\t h:i A', strtotime($pay['date_paid'])); ?></small>
                                            </div>
                                            <div class="text-end">
                                                <span class="fw-bold text-<?php echo $p_color; ?> fs-5">₱<?php echo number_format($pay['amount_paid'], 2); ?></span>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center mt-3 pt-2 border-top">
                                            <div class="rounded-circle bg-white text-muted d-flex align-items-center justify-content-center me-3 border shadow-sm" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                <i class="fa-solid fa-user-tie"></i>
                                            </div>
                                            <div>
                                                <small class="text-dark fw-bold d-block mb-0" style="font-size: 0.75rem;">
                                                    <?php echo htmlspecialchars(($pay['t_fname'] ?? '') . ' ' . ($pay['t_lname'] ?? 'System')); ?>
                                                </small>
                                                <small class="text-muted fw-bold text-uppercase" style="font-size: 0.6rem; letter-spacing: 0.5px;">
                                                    <?php echo htmlspecialchars($pay['teller_name'] ?? 'System'); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <div class="opacity-25 mb-3"><i class="fa-solid fa-receipt display-4"></i></div>
                                <h6 class="fw-bold text-muted">No Payment History</h6>
                                <p class="small text-muted mb-0">Initial pawn transaction confirmed on <?php echo date('M d, Y', strtotime($t['date_pawned'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-lg-4">
            
            <div class="card shadow-sm border-0 mb-4 rounded-4 bg-gradient bg-<?php echo $status_color; ?> text-white overflow-hidden position-relative">
                <i class="fa-solid fa-shield-halved position-absolute opacity-10" style="font-size: 8rem; right: -20px; top: -10px;"></i>
                <div class="card-body p-4 text-center position-relative z-index-1">
                    <small class="text-uppercase text-white-50 fw-bold ls-1" style="font-size: 0.7rem;">Current Loan Status</small>
                    <h2 class="fw-bold mb-0 text-uppercase mt-2 d-flex justify-content-center align-items-center">
                        <i class="fa-solid fa-circle-info me-2 fs-4 opacity-75"></i><?php echo $t['status']; ?>
                    </h2>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4 rounded-4 overflow-hidden position-sticky" style="top: 20px;">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-wallet me-2 text-primary"></i> Financial Details</h6>
                </div>
                <div class="card-body p-4 pt-0">
                    
                    <?php if ($t['status'] == 'active' || $t['status'] == 'expired'): ?>
                        <div class="bg-primary bg-opacity-10 p-4 rounded-4 mb-4 border border-primary border-opacity-25 text-center shadow-sm">
                            <span class="text-primary fw-bold small text-uppercase ls-1 d-block mb-2">Total Due Today</span>
                            <h1 class="fw-bold text-primary mb-2" style="letter-spacing: -1px;">₱<?php echo number_format($calc['total'], 2); ?></h1>
                            <span class="badge bg-white text-primary border border-primary border-opacity-25 rounded-pill px-3 shadow-sm"><i class="fa-solid fa-clock-rotate-left me-1"></i> <?php echo $calc['months']; ?> Month(s) Interest</span>
                        </div>
                    <?php else: ?>
                        <div class="bg-light p-4 rounded-4 mb-4 border text-center shadow-sm">
                            <i class="fa-solid fa-lock fs-3 text-secondary opacity-50 mb-2 d-block"></i>
                            <span class="badge bg-secondary mb-2 rounded-pill px-3">Account Closed</span>
                            <p class="small text-muted mb-0 fw-bold">Interest is no longer accruing on this transaction.</p>
                        </div>
                    <?php endif; ?>

                    <ul class="list-group list-group-flush financial-list">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 border-bottom border-light">
                            <span class="text-muted fw-bold small text-uppercase">Principal Amount</span>
                            <span class="fw-bold text-dark fs-6">₱<?php echo number_format($t['principal_amount'], 2); ?></span>
                        </li>
                        
                        <?php if ($t['status'] == 'active' || $t['status'] == 'expired'): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 border-bottom border-light">
                            <span class="text-muted fw-bold small text-uppercase">Accrued Interest (3%)</span>
                            <span class="fw-bold text-warning fs-6">+ ₱<?php echo number_format($calc['interest'], 2); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-3 border-0 mt-2 bg-light rounded-3 shadow-sm border border-secondary border-opacity-25">
                            <span class="text-dark fw-bold small text-uppercase ls-1">Total Amount Due</span>
                            <span class="fw-bold text-success fs-5">₱<?php echo number_format($calc['total'], 2); ?></span>
                        </li>
                        <?php endif; ?>

                        <div class="my-4 border-top border-dashed"></div>

                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                            <span class="text-muted small fw-bold"><i class="fa-solid fa-calendar-check w-15px text-center me-2 text-secondary opacity-50"></i> Origination Date</span>
                            <span class="fw-bold text-dark small"><?php echo date('M d, Y', strtotime($t['date_pawned'])); ?></span>
                        </li>
                        
                        <?php if (isset($t['last_renewed_date']) && $t['last_renewed_date'] != $t['date_pawned']): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                            <span class="text-muted small fw-bold"><i class="fa-solid fa-clock-rotate-left w-15px text-center me-2 text-info opacity-75"></i> Last Renewed</span>
                            <span class="fw-bold text-info small"><?php echo date('M d, Y', strtotime($t['last_renewed_date'])); ?></span>
                        </li>
                        <?php endif; ?>

                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                            <span class="text-muted small fw-bold"><i class="fa-solid fa-hourglass-end w-15px text-center me-2 text-warning opacity-75"></i> Maturity Date</span>
                            <span class="fw-bold text-warning small"><?php echo date('M d, Y', strtotime($t['maturity_date'])); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                            <span class="text-muted small fw-bold"><i class="fa-solid fa-triangle-exclamation w-15px text-center me-2 text-danger opacity-75"></i> Expiry Date</span>
                            <span class="fw-bold text-danger small"><?php echo date('M d, Y', strtotime($t['expiry_date'])); ?></span>
                        </li>
                    </ul>

                    <div class="bg-primary bg-opacity-10 rounded-4 mt-4 p-3 border border-primary border-opacity-25 d-flex align-items-center justify-content-between shadow-sm">
                        <div class="d-flex align-items-center">
                            <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm border border-primary border-opacity-25" style="width: 45px; height: 45px;">
                                <i class="fa-solid fa-user-tie"></i>
                            </div>
                            <div>
                                <small class="text-primary text-uppercase fw-bold d-block mb-0" style="font-size: 0.65rem; letter-spacing: 1px;">Processed By</small>
                                <span class="fw-bold text-dark text-uppercase d-block" style="font-size: 0.9rem;"><?php echo htmlspecialchars(($t['teller_fname'] ?? '') . ' ' . ($t['teller_lname'] ?? 'System')); ?></span>
                                <small class="text-muted fw-bold font-monospace" style="font-size: 0.7rem;"><?php echo htmlspecialchars($t['processed_by_user'] ?? 'System'); ?></small>
                            </div>
                        </div>
                        <span class="badge bg-primary text-white rounded-pill px-2 py-1 shadow-sm" style="font-size: 0.65rem; letter-spacing: 1px;">TELLER</span>
                    </div>

                </div>
            </div>

            <?php if ($t['status'] == 'auctioned' && !empty($t['buyer_fname'])): ?>
            <div class="card shadow-sm border-0 mb-4 border-top border-4 border-success rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-success"><i class="fa-solid fa-gavel me-2"></i> Auction Winner</h6>
                </div>
                <div class="card-body text-center pt-0 pb-4">
                    <div class="mb-3">
                         <div class="rounded-circle bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center shadow-sm border border-success border-opacity-25" style="width: 60px; height: 60px; font-weight:bold; font-size: 1.2rem;">
                            <?php echo substr($t['buyer_fname'], 0, 1) . substr($t['buyer_lname'], 0, 1); ?>
                        </div>
                    </div>
                    <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($t['buyer_fname'] . ' ' . $t['buyer_lname']); ?></h6>
                    <p class="text-muted small mb-2"><span class="badge bg-light text-dark border font-monospace">ID: <?php echo htmlspecialchars($t['buyer_public_id']); ?></span></p>
                    <small class="text-muted fw-bold"><i class="fa-solid fa-phone me-1 opacity-50"></i> <?php echo htmlspecialchars($t['buyer_contact']); ?></small>
                </div>
            </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4 border-top border-4 border-dark">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-user me-2 opacity-75"></i> <?php echo ($t['status'] == 'auctioned') ? 'Previous Owner' : 'Customer Profile'; ?></h6>
                </div>
                <div class="card-body text-center pt-0 pb-4">
                    <div class="mb-3">
                         <div class="rounded-circle bg-dark text-white d-inline-flex align-items-center justify-content-center shadow" style="width: 70px; height: 70px; font-weight:bold; font-size: 1.5rem;">
                            <?php echo substr($t['first_name'], 0, 1) . substr($t['last_name'], 0, 1); ?>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?></h5>
                    <p class="text-muted small mb-4"><span class="badge bg-light text-secondary border font-monospace px-2 py-1 shadow-sm">ID: <?php echo htmlspecialchars($t['cust_public_id']); ?></span></p>
                    
                    <div class="d-flex flex-column gap-2 px-3">
                        <a href="tel:<?php echo htmlspecialchars($t['contact_number']); ?>" class="btn btn-light border shadow-sm fw-bold text-dark d-flex align-items-center justify-content-center py-2 btn-hover-lift rounded-pill">
                            <i class="fa-solid fa-phone me-2 text-primary"></i> <?php echo htmlspecialchars($t['contact_number']); ?>
                        </a>
                        <a href="mailto:<?php echo htmlspecialchars($t['email']); ?>" class="btn btn-light border shadow-sm fw-bold text-dark d-flex align-items-center justify-content-center py-2 btn-hover-lift rounded-pill">
                            <i class="fa-solid fa-envelope me-2 text-danger"></i> Send Email
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    .ls-1 { letter-spacing: 1.5px; }
    .w-15px { width: 15px; display: inline-block; }
    .border-dashed { border-top-style: dashed !important; border-top-width: 2px !important; border-color: #dee2e6 !important;}
    
    /* Hero Scorecards */
    .kpi-card { transition: all 0.2s ease; border: 1px solid transparent; }
    .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.06) !important; border-color: rgba(13, 110, 253, 0.15); }
    
    /* Lifecycle Nodes */
    .node { width: 60px; position: relative; z-index: 2; }
    .node-icon { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 0.75rem; font-weight: bold; transition: all 0.3s ease; }
    .node.active .node-icon { transform: scale(1.1); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .node.upcoming { opacity: 0.5; }
    .progress-bar-animated { transition: width 1s ease-in-out; }

    /* Activity Timeline */
    .activity-timeline { position: relative; padding-left: 15px; }
    .activity-timeline::before { content: ''; position: absolute; left: 24px; top: 0; bottom: 0; width: 2px; background: #eaedf0; z-index: 1; }
    .timeline-item { position: relative; z-index: 2; }
    .timeline-point { width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 5px; border: 3px solid #fff; }
    .timeline-card { transition: all 0.2s ease; border: 1px solid #eef0f2; cursor: default; }
    .timeline-card:hover { transform: translateX(5px); background-color: #fff !important; box-shadow: 0 5px 15px rgba(0,0,0,0.04); border-color: #dee2e6; }
    
    /* General Styles */
    .btn-hover-lift:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important; }
    .photo-card { transition: all 0.3s ease; border-color: #eef1f5 !important; }
    .photo-card:hover { transform: scale(1.02); box-shadow: 0 8px 20px rgba(0,0,0,0.1) !important; z-index: 10; }
    .shadow-xs { box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .digital-readout { font-family: 'Inter', sans-serif; }
</style>

<?php include_once '../../includes/admin_footer.php'; ?>