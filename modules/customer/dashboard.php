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

// 3. CALCULATE PORTFOLIO TOTALS & HEALTH
$total_principal = 0;
$total_interest = 0;
$next_due_date = null;
$at_risk_count = 0;

foreach ($txns as $key => $t) {
    $total_principal += $t['principal_amount'];

    // ACCURACY FIX: Use last_renewed_date to prevent double-charging display
    $start_date_str = isset($t['last_renewed_date']) ? $t['last_renewed_date'] : $t['date_pawned'];
    $start_date = new DateTime($start_date_str);
    $today = new DateTime();
    $due_date = new DateTime($t['maturity_date']);
    
    $days_passed = $start_date->diff($today)->days;
    $days_until_due = $today->diff($due_date)->format("%r%a"); 

    // Risk Check
    if ($days_until_due <= 7) $at_risk_count++;
    
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
    $txns[$key]['days_until_due'] = $days_until_due;
    $txns[$key]['calc_start_date'] = $start_date_str;
}

$grand_total = $total_principal + $total_interest;
$health_score = (count($txns) > 0) ? round(((count($txns) - $at_risk_count) / count($txns)) * 100) : 100;

// ==========================================
// 4. DATA MINING FOR ANALYTICS (LOOCKER/POWER BI STYLE)
// ==========================================

// --- A. Asset Category Distribution ---
$cat_labels = []; $cat_counts = [];
$sql_cat = "SELECT i.device_type, COUNT(*) as count 
            FROM transactions t 
            JOIN items i ON t.transaction_id = i.transaction_id 
            WHERE t.customer_id = ? AND t.status IN ('active', 'expired') 
            GROUP BY i.device_type";
$stmt_cat = $conn->prepare($sql_cat);
$stmt_cat->bind_param("i", $profile_id);
$stmt_cat->execute();
$res_cat = $stmt_cat->get_result();
while($row = $res_cat->fetch_assoc()) {
    $cat_labels[] = $row['device_type'];
    $cat_counts[] = $row['count'];
}

// --- B. Interest Accrual Projection (90-Day Look-ahead) ---
// Projection logic: Total Principal * 0.03 * Month Index
$proj_labels = ['Current', '30 Days', '60 Days', '90 Days'];
$proj_data = [$total_interest];
if ($total_principal > 0) {
    $monthly_interest = $total_principal * 0.03;
    $proj_data[] = $total_interest + $monthly_interest;
    $proj_data[] = $total_interest + ($monthly_interest * 2);
    $proj_data[] = $total_interest + ($monthly_interest * 3);
}

// --- C. Redemption History Success Rate ---
$hist_labels = []; $hist_counts = [];
$sql_hist = "SELECT status, COUNT(*) as count FROM transactions WHERE customer_id = ? GROUP BY status";
$stmt_hist = $conn->prepare($sql_hist);
$stmt_hist->bind_param("i", $profile_id);
$stmt_hist->execute();
$res_hist = $stmt_hist->get_result();
while($row = $res_hist->fetch_assoc()) {
    $hist_labels[] = ucfirst($row['status']);
    $hist_counts[] = $row['count'];
}
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
        <!-- Main Asset Portfolio Card -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm text-white rounded-4 position-relative overflow-hidden bento-main-card" 
                 style="background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%); min-height: 250px;">
                <div class="card-body p-4 p-md-5 d-flex flex-column justify-content-between position-relative z-index-1">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small class="text-white-50 text-uppercase fw-bold ls-1" style="font-size: 0.65rem;">Total to Pay Back Today</small>
                            <h1 class="display-5 fw-bold mb-1 mt-1">₱<?php echo number_format($grand_total, 2); ?></h1>
                            <span class="badge bg-white bg-opacity-20 rounded-pill px-3 py-2 fw-bold" style="font-size: 0.7rem;">
                                <i class="fa-solid fa-chart-line me-1 text-warning"></i> 3% MONTHLY RATE
                            </span>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center p-3 shadow-lg" style="width: 80px; height: 80px;">
                            <i class="fa-solid fa-wallet fs-2 text-white shadow-soft"></i>
                        </div>
                    </div>

                    <div class="row mt-4 pt-3 border-top border-white border-opacity-10">
                        <div class="col-4 border-end border-white border-opacity-25">
                            <small class="text-white-50 d-block text-uppercase fw-bold ls-1 mb-1" style="font-size: 0.6rem;">Borrowed Amount</small>
                            <span class="fw-bold fs-5">₱<?php echo number_format($total_principal, 2); ?></span>
                        </div>
                        <div class="col-4 border-end border-white border-opacity-25 ps-4">
                            <small class="text-white-50 d-block text-uppercase fw-bold ls-1 mb-1" style="font-size: 0.6rem;">Interest Fees</small>
                            <span class="fw-bold fs-5 text-warning">+ ₱<?php echo number_format($total_interest, 2); ?></span>
                        </div>
                        <div class="col-4 ps-4">
                            <small class="text-white-50 d-block text-uppercase fw-bold ls-1 mb-1" style="font-size: 0.6rem;">My Current Items</small>
                            <span class="fw-bold fs-5"><?php echo count($txns); ?> <small class="fw-normal text-white-50">Units</small></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Portfolio Health & Quick Stats -->
        <div class="col-lg-4 d-flex flex-column gap-4">
            <div class="card border-0 shadow-sm rounded-4 flex-grow-1 overflow-hidden">
                <div class="card-body p-4 text-center d-flex flex-column justify-content-center align-items-center position-relative">
                    <div class="health-gauge mb-3 position-relative" style="width: 100px; height: 100px;">
                        <svg viewBox="0 0 36 36" class="circular-chart <?php echo ($health_score > 70) ? 'green' : (($health_score > 30) ? 'orange' : 'red'); ?>">
                            <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            <path class="circle" stroke-dasharray="<?php echo $health_score; ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            <text x="18" y="20.35" class="percentage fw-bold"><?php echo $health_score; ?>%</text>
                        </svg>
                    </div>
                    <small class="text-muted text-uppercase fw-bold ls-1 mb-1" style="font-size: 0.65rem;">Payment Safety Score</small>
                    <h6 class="fw-bold mb-0 text-dark"><?php echo ($health_score > 70) ? 'Items are Safe' : (($health_score > 30) ? 'Due Soon' : 'Expiring Very Soon'); ?></h6>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 flex-grow-1">
                <div class="card-body p-4 d-flex flex-column justify-content-center">
                    <h6 class="fw-bold text-muted text-uppercase ls-1 mb-3" style="font-size: 0.65rem;">Upcoming Deadlines</h6>
                    <?php if ($next_due_date): ?>
                        <div class="alert <?php echo ($at_risk_count > 0) ? 'alert-danger' : 'alert-warning'; ?> border-0 d-flex align-items-center mb-0 py-3 rounded-4 shadow-sm">
                            <i class="fa-solid <?php echo ($at_risk_count > 0) ? 'fa-triangle-exclamation' : 'fa-clock'; ?> fs-4 me-3"></i>
                            <div>
                                <strong class="d-block text-dark">Nearest Due Date</strong>
                                <span class="small fw-bold text-muted"><?php echo date('F d, Y', strtotime($next_due_date)); ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success border-0 d-flex align-items-center mb-0 py-3 rounded-4 shadow-sm">
                            <i class="fa-solid fa-check-circle fs-4 me-3"></i>
                            <div>
                                <strong class="d-block text-dark">No Due Items</strong>
                                <span class="small text-muted">You have no active loans.</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Power BI / Looker Style Core Analytics Section -->
    <div class="row g-4 mb-5">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 analytics-card">
                <div class="card-header bg-white p-4 border-0 pb-0">
                    <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-chart-pie me-2 text-primary"></i> My Item Categories</h6>
                    <small class="text-muted" style="font-size: 0.7rem;">What types of gadgets I pawned</small>
                </div>
                <div class="card-body p-4 d-flex align-items-center justify-content-center" style="min-height: 250px;">
                    <?php if (count($cat_counts) > 0): ?>
                        <canvas id="portfolioChart"></canvas>
                    <?php else: ?>
                        <div class="text-center text-muted opacity-50 py-5">
                            <i class="fa-solid fa-diagram-project fa-3x mb-2"></i>
                            <p class="small mb-0">No items yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100 analytics-card">
                <div class="card-header bg-white p-4 border-0 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-magnifying-glass-chart me-2 text-warning"></i> Interest Forecast</h6>
                        <small class="text-muted" style="font-size: 0.7rem;">How fees will grow over 3 months</small>
                    </div>
                    <span class="badge bg-light text-dark border rounded-pill px-3 py-2 small">INSIGHT</span>
                </div>
                <div class="card-body p-4" style="min-height: 250px;">
                    <?php if ($total_principal > 0): ?>
                        <canvas id="projectionChart"></canvas>
                    <?php else: ?>
                        <div class="text-center text-muted opacity-50 py-5">
                            <i class="fa-solid fa-arrow-trend-up fa-3x mb-2"></i>
                            <p class="small mb-0">Browse to see fee estimates.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 analytics-card bg-light bg-opacity-50">
                <div class="card-header bg-transparent p-4 border-0 pb-0">
                    <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-history me-2 text-info"></i> My History</h6>
                    <small class="text-muted" style="font-size: 0.7rem;">Redeemed vs Repossessed</small>
                </div>
                <div class="card-body p-4 d-flex flex-column" style="min-height: 250px;">
                    <?php if (count($hist_counts) > 0): ?>
                        <div class="flex-grow-1">
                            <canvas id="historyChart"></canvas>
                        </div>
                        <div class="mt-4 pt-3 border-top text-center">
                            <small class="text-muted d-block text-uppercase fw-bold ls-1 mb-1" style="font-size: 0.55rem;">Redemption Rate</small>
                            <h4 class="fw-bold text-primary mb-0">
                                <?php 
                                    $redeemed_count = 0; $total_hist = 0;
                                    foreach($hist_labels as $idx => $lbl) {
                                        $total_hist += $hist_counts[$idx];
                                        if (strtolower($lbl) == 'redeemed') $redeemed_count = $hist_counts[$idx];
                                    }
                                    echo ($total_hist > 0) ? round(($redeemed_count / $total_hist) * 100) : 0;
                                ?>%
                            </h4>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted opacity-50 py-5 my-auto">
                            <i class="fa-solid fa-clock-rotate-left fa-3x mb-2"></i>
                            <p class="small mb-0">Your history will show up here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    Chart.defaults.font.family = "'Inter', 'Helvetica Neue', 'Arial', sans-serif";
    Chart.defaults.color = '#718096';

    // 1. Portfolio Breakdown Chart
    const ctxPort = document.getElementById('portfolioChart')?.getContext('2d');
    if (ctxPort) {
        new Chart(ctxPort, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($cat_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($cat_counts); ?>,
                    backgroundColor: ['#4299e1', '#48bb78', '#f6ad55', '#38b2ac', '#9f7aea', '#ed64a1'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, padding: 15, font: {size: 11, weight: 'bold'}, usePointStyle: true }
                    }
                }
            }
        });
    }

    // 2. Interest Projection Chart
    const ctxProj = document.getElementById('projectionChart')?.getContext('2d');
    if (ctxProj) {
        new Chart(ctxProj, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($proj_labels); ?>,
                datasets: [{
                    label: 'Estimated Fees (₱)',
                    data: <?php echo json_encode($proj_data); ?>,
                    borderColor: '#f6ad55',
                    backgroundColor: 'rgba(246, 173, 85, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 6,
                    pointBackgroundColor: '#fff',
                    pointBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: false, grid: { borderDash: [4, 4], color: '#edf2f7' }, border: {display: false} },
                    x: { grid: { display: false }, border: {display: false} }
                }
            }
        });
    }

    // 3. Status History Chart
    const ctxHist = document.getElementById('historyChart')?.getContext('2d');
    if (ctxHist) {
        new Chart(ctxHist, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($hist_labels); ?>,
                datasets: [{
                    label: 'Items',
                    data: <?php echo json_encode($hist_counts); ?>,
                    backgroundColor: '#805ad5',
                    borderRadius: 8,
                    barThickness: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                indexAxis: 'y',
                scales: {
                    y: { grid: { display: false }, border: {display: false} },
                    x: { grid: { borderDash: [4, 4], color: '#edf2f7' }, border: {display: false}, ticks: {stepSize: 1} }
                }
            }
        });
    }
</script>

<style>
    .ls-1 { letter-spacing: 1.5px; }
    .analytics-card { transition: all 0.3s ease; border: 1px solid rgba(0,0,0,0.03) !important; }
    .analytics-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.08) !important; border-color: rgba(66, 153, 225, 0.1) !important; }
    .shadow-soft { text-shadow: 0 4px 10px rgba(0,0,0,0.2); }
    
    .product-card { transition: all 0.3s ease; }
    .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.08) !important; }

    /* Health Gauge Styles */
    .circular-chart { display: block; margin: 0 auto; max-width: 100%; max-height: 250px; }
    .circle-bg { fill: none; stroke: #edf2f7; stroke-width: 2.8; }
    .circle { fill: none; stroke-width: 2.8; stroke-linecap: round; animation: progress 1s ease-out forwards; }
    @keyframes progress { 0% { stroke-dasharray: 0 100; } }
    .circular-chart.green .circle { stroke: #48bb78; }
    .circular-chart.orange .circle { stroke: #f6ad55; }
    .circular-chart.red .circle { stroke: #e53e3e; }
    .percentage { fill: #2d3748; font-family: sans-serif; font-size: 0.5em; text-anchor: middle; }
    
    .text-truncate-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>

<?php include_once '../../includes/customer_footer.php'; ?>