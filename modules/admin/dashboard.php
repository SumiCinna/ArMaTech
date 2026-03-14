<?php
// modules/admin/dashboard.php
require_once '../../config/database.php';
include_once '../../includes/admin_header.php';

// ==========================================
// 1. GET KEY METRICS
// ==========================================

// Total Principal Out (Money Lent)
$sql_principal = "SELECT SUM(principal_amount) as total FROM transactions WHERE status='active'";
$res_principal = $conn->query($sql_principal)->fetch_assoc();

// Cash In Today
$sql_cash_today = "SELECT SUM(amount_paid) as today_sales FROM payments WHERE DATE(date_paid) = CURDATE()";
$res_cash = $conn->query($sql_cash_today)->fetch_assoc();

// Total Active Customers
$sql_cust = "SELECT COUNT(*) as count FROM accounts WHERE role='customer'";
$res_cust = $conn->query($sql_cust)->fetch_assoc();

// DATA MINING: High-Risk Default Predictor
$sql_high_risk = "SELECT COUNT(*) as risk_count 
                  FROM transactions 
                  WHERE status = 'active' 
                  AND principal_amount >= 10000 
                  AND expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)";
$res_high_risk = $conn->query($sql_high_risk)->fetch_assoc();

// ==========================================
// 2. ANALYTICS DATA 
// ==========================================

// --- A. Revenue Trend Logic ---
$chart_range = $_GET['chart_range'] ?? '6_months';
$months = [];
$revenue_data = [];

$interval_sql = "6 MONTH";
$group_format = "%Y-%m";
$label_format = "%b %Y"; 
$chart_title_text = "Revenue Trend (Last 6 Months)";

if ($chart_range == '7_days') {
    $interval_sql = "7 DAY";
    $group_format = "%Y-%m-%d";
    $label_format = "%b %d"; 
    $chart_title_text = "Revenue Trend (Last 7 Days)";
} elseif ($chart_range == '30_days') {
    $interval_sql = "30 DAY";
    $group_format = "%Y-%m-%d";
    $label_format = "%b %d";
    $chart_title_text = "Revenue Trend (Last 30 Days)";
} elseif ($chart_range == '1_year') {
    $interval_sql = "1 YEAR";
    $group_format = "%Y-%m";
    $label_format = "%b %Y";
    $chart_title_text = "Revenue Trend (Last 1 Year)";
}

$sql_chart = "SELECT DATE_FORMAT(date_paid, '$label_format') as label, SUM(amount_paid) as total 
              FROM payments 
              WHERE date_paid >= DATE_SUB(NOW(), INTERVAL $interval_sql)
              GROUP BY DATE_FORMAT(date_paid, '$group_format'), label 
              ORDER BY MIN(date_paid) ASC";

$res_chart = $conn->query($sql_chart);
while($row = $res_chart->fetch_assoc()){
    $months[] = $row['label'];
    $revenue_data[] = $row['total'];
}

// --- B. DATA MINING: Time-Series Activity ---
$activity_range = $_GET['activity_range'] ?? '1_month'; 
$activity_interval_sql = "";

if ($activity_range == '7_days') {
    $activity_interval_sql = "WHERE date_paid >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($activity_range == '1_month') {
    $activity_interval_sql = "WHERE date_paid >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
} elseif ($activity_range == '2_months') {
    $activity_interval_sql = "WHERE date_paid >= DATE_SUB(NOW(), INTERVAL 2 MONTH)";
} elseif ($activity_range == '6_months') {
    $activity_interval_sql = "WHERE date_paid >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
}

$activity_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$activity_counts = [0,0,0,0,0,0,0]; 

$sql_activity = "SELECT DAYNAME(date_paid) as day_of_week, COUNT(*) as txn_count 
                 FROM payments 
                 $activity_interval_sql
                 GROUP BY DAYNAME(date_paid)";
$res_activity = $conn->query($sql_activity);

if ($res_activity) {
    while($row = $res_activity->fetch_assoc()){
        $index = array_search($row['day_of_week'], $activity_days);
        if($index !== false) {
            $activity_counts[$index] = $row['txn_count'];
        }
    }
}

// --- C. Loan Status Distribution ---
$status_labels = [];
$status_counts = [];
$sql_status = "SELECT status, COUNT(*) as count FROM transactions GROUP BY status";
$res_status = $conn->query($sql_status);
while($row = $res_status->fetch_assoc()){
    $status_labels[] = ucfirst($row['status']);
    $status_counts[] = $row['count'];
}

// --- D. Top Tellers by Collection ---
$teller_range = $_GET['teller_range'] ?? 'all_time';
$teller_where = "1=1";
if ($teller_range == '7_days') $teller_where = "p.date_paid >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
elseif ($teller_range == 'this_month') $teller_where = "p.date_paid >= DATE_FORMAT(NOW() ,'%Y-%m-01')";
elseif ($teller_range == 'last_3_months') $teller_where = "p.date_paid >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
elseif ($teller_range == '6_months') $teller_where = "p.date_paid >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
elseif ($teller_range == '1_year') $teller_where = "p.date_paid >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";

$sql_tellers = "SELECT a.username, SUM(p.amount_paid) as total_collected, COUNT(p.payment_id) as txn_count 
                FROM payments p 
                JOIN accounts a ON p.teller_id = a.account_id 
                WHERE $teller_where
                GROUP BY p.teller_id 
                ORDER BY total_collected DESC LIMIT 5";
$res_tellers = $conn->query($sql_tellers);

// --- E. Top Customers (Loyalty Program) ---
$client_range = $_GET['client_range'] ?? 'all_time';
$client_where = "1=1";
if ($client_range == '7_days') $client_where = "t.date_pawned >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
elseif ($client_range == 'this_month') $client_where = "t.date_pawned >= DATE_FORMAT(NOW() ,'%Y-%m-01')";
elseif ($client_range == 'last_3_months') $client_where = "t.date_pawned >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
elseif ($client_range == '6_months') $client_where = "t.date_pawned >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
elseif ($client_range == '1_year') $client_where = "t.date_pawned >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";

$sql_top_cust = "SELECT p.first_name, p.last_name, p.public_id, 
                        COUNT(t.transaction_id) as txn_count, 
                        SUM(t.principal_amount) as total_value
                 FROM profiles p
                 JOIN transactions t ON p.profile_id = t.customer_id
                 WHERE $client_where
                 GROUP BY p.profile_id
                 ORDER BY txn_count DESC, total_value DESC LIMIT 5";
$res_top_cust = $conn->query($sql_top_cust);
?>

<div class="container-fluid px-4 pb-5">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0"><i class="fa-solid fa-gauge-high me-2 text-primary"></i> Executive Dashboard</h3>
            <p class="text-muted small mb-0">Overview of operational metrics, revenue, and key relationships.</p>
        </div>
        <div class="d-none d-md-block">
            <span class="badge bg-white text-dark border shadow-sm px-3 py-2 rounded-pill fw-bold">
                <i class="fa-regular fa-calendar text-primary me-2"></i> <?php echo date('F d, Y'); ?>
            </span>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 stat-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                            <i class="fa-solid fa-cash-register fs-5"></i>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill">Today</span>
                    </div>
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;">Cash Collection</small>
                    <h3 class="fw-bold mb-0 text-dark">₱<?php echo number_format($res_cash['today_sales'] ?? 0, 2); ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 stat-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                            <i class="fa-solid fa-hand-holding-dollar fs-5"></i>
                        </div>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill">Active</span>
                    </div>
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;">Capital Lent</small>
                    <h3 class="fw-bold mb-0 text-dark">₱<?php echo number_format($res_principal['total'] ?? 0, 2); ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 stat-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-dark bg-opacity-10 text-dark rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                            <i class="fa-solid fa-users fs-5"></i>
                        </div>
                    </div>
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;">Total Customers</small>
                    <h3 class="fw-bold mb-0 text-dark"><?php echo number_format($res_cust['count']); ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 stat-card" style="background: linear-gradient(135deg, #fff5f5 0%, #fff 100%);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 45px; height: 45px;">
                            <i class="fa-solid fa-triangle-exclamation fs-5"></i>
                        </div>
                        <span class="badge bg-danger rounded-pill"><i class="fa-solid fa-brain me-1"></i> AI Alert</span>
                    </div>
                    <small class="text-danger text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;">Default Risk</small>
                    <h3 class="fw-bold mb-0 text-danger"><?php echo $res_high_risk['risk_count']; ?> <span class="fs-6 fw-normal text-muted">items</span></h3>
                    <small class="text-muted" style="font-size: 0.65rem;">High-value assets nearing expiry</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white p-4 pb-0 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-chart-line me-2 text-primary"></i> <?php echo $chart_title_text; ?></h6>
                    <form method="GET" class="d-inline-block">
                        <input type="hidden" name="activity_range" value="<?php echo htmlspecialchars($activity_range); ?>">
                        <input type="hidden" name="teller_range" value="<?php echo htmlspecialchars($teller_range); ?>">
                        <input type="hidden" name="client_range" value="<?php echo htmlspecialchars($client_range); ?>">
                        <select name="chart_range" class="form-select form-select-sm bg-light border-0 fw-bold text-secondary shadow-none rounded-pill px-3" onchange="this.form.submit()">
                            <option value="7_days" <?php echo ($chart_range == '7_days') ? 'selected' : ''; ?>>7 Days</option>
                            <option value="30_days" <?php echo ($chart_range == '30_days') ? 'selected' : ''; ?>>30 Days</option>
                            <option value="6_months" <?php echo ($chart_range == '6_months') ? 'selected' : ''; ?>>6 Months</option>
                            <option value="1_year" <?php echo ($chart_range == '1_year') ? 'selected' : ''; ?>>1 Year</option>
                        </select>
                    </form>
                </div>
                <div class="card-body p-4 pt-2">
                    <canvas id="revenueChart" style="height: 280px; width: 100%;"></canvas>
                 </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white p-4 pb-0 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-calendar-day me-2 text-warning"></i> Peak Activity</h6>
                    <form method="GET" class="d-inline-block">
                        <input type="hidden" name="chart_range" value="<?php echo htmlspecialchars($chart_range); ?>">
                        <input type="hidden" name="teller_range" value="<?php echo htmlspecialchars($teller_range); ?>">
                        <input type="hidden" name="client_range" value="<?php echo htmlspecialchars($client_range); ?>">
                        <select name="activity_range" class="form-select form-select-sm bg-light border-0 fw-bold text-secondary shadow-none rounded-pill px-3" onchange="this.form.submit()">
                            <option value="7_days" <?php echo ($activity_range == '7_days') ? 'selected' : ''; ?>>7 Days</option>
                            <option value="1_month" <?php echo ($activity_range == '1_month') ? 'selected' : ''; ?>>1 Month</option>
                            <option value="6_months" <?php echo ($activity_range == '6_months') ? 'selected' : ''; ?>>6 Months</option>
                        </select>
                    </form>
                </div>
                <div class="card-body p-4 pt-2">
                    <canvas id="activityChart" style="height: 250px; width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-header bg-white p-4 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-list-check me-2 text-secondary"></i> Recent System Activity</h6>
                    <a href="reports_sales.php" class="btn btn-sm btn-light border text-primary fw-bold rounded-pill px-3">View Full Log</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-4 py-3">Date & Time</th>
                                    <th class="py-3">Processed By</th>
                                    <th class="py-3">Type</th>
                                    <th class="py-3">Amount</th>
                                    <th class="py-3 pe-4">PT Number</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql_recent = "SELECT p.*, a.username, t.pt_number 
                                            FROM payments p 
                                            JOIN accounts a ON p.teller_id = a.account_id
                                            JOIN transactions t ON p.transaction_id = t.transaction_id
                                            ORDER BY p.date_paid DESC LIMIT 5";
                                $res_recent = $conn->query($sql_recent);
                                
                                if($res_recent->num_rows > 0):
                                    while($row = $res_recent->fetch_assoc()):
                                ?>
                                    <tr style="border-bottom: 1px solid #f8f9fa;">
                                        <td class="ps-4 text-nowrap"><span class="text-dark fw-bold"><?php echo date('M d, Y', strtotime($row['date_paid'])); ?></span> <span class="text-muted small ms-1"><?php echo date('h:i A', strtotime($row['date_paid'])); ?></span></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-secondary bg-opacity-10 text-secondary d-flex align-items-center justify-content-center me-2" style="width: 28px; height: 28px; font-size: 0.7rem; font-weight:bold;">
                                                    <?php echo substr($row['username'], 0, 2); ?>
                                                </div>
                                                <span class="small fw-bold"><?php echo ucfirst($row['username']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if($row['payment_type'] == 'interest_only'): ?>
                                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill">Renewal</span>
                                            <?php elseif($row['payment_type'] == 'partial_payment'): ?>
                                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill">Partial</span>
                                            <?php else: ?>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill">Redemption</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold text-success">₱<?php echo number_format($row['amount_paid'], 2); ?></td>
                                        <td class="text-muted small font-monospace pe-4"><?php echo $row['pt_number']; ?></td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted">No recent activity found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-header bg-white p-4 border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-chart-pie me-2 text-success"></i> Portfolio Health</h6>
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center pt-0 pb-4">
                    <div style="height: 220px; width: 220px; position: relative;">
                        <canvas id="statusChart"></canvas>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-4 h-100 border-top border-4 border-warning">
                <div class="card-header bg-white p-4 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-crown me-2 text-warning"></i> Top Valued Clients</h6>
                        <small class="text-muted" style="font-size: 0.7rem;">Highest total transaction volume & lifetime value</small>
                    </div>
                    <form method="GET" class="d-inline-block">
                        <input type="hidden" name="chart_range" value="<?php echo htmlspecialchars($chart_range); ?>">
                        <input type="hidden" name="activity_range" value="<?php echo htmlspecialchars($activity_range); ?>">
                        <input type="hidden" name="teller_range" value="<?php echo htmlspecialchars($teller_range); ?>">
                        <select name="client_range" class="form-select form-select-sm bg-warning bg-opacity-10 text-warning border-0 fw-bold shadow-none rounded-pill px-3" onchange="this.form.submit()">
                            <option value="all_time" <?php echo ($client_range == 'all_time') ? 'selected' : ''; ?>>All Time</option>
                            <option value="7_days" <?php echo ($client_range == '7_days') ? 'selected' : ''; ?>>7 Days</option>
                            <option value="this_month" <?php echo ($client_range == 'this_month') ? 'selected' : ''; ?>>This Month</option>
                            <option value="last_3_months" <?php echo ($client_range == 'last_3_months') ? 'selected' : ''; ?>>3 Months</option>
                            <option value="6_months" <?php echo ($client_range == '6_months') ? 'selected' : ''; ?>>6 Months</option>
                            <option value="1_year" <?php echo ($client_range == '1_year') ? 'selected' : ''; ?>>1 Year</option>
                        </select>
                    </form>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if($res_top_cust->num_rows > 0): ?>
                            <?php $rank = 1; while($cust = $res_top_cust->fetch_assoc()): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center p-4 border-0 border-bottom custom-hover">
                                    <div class="d-flex align-items-center">
                                        <div class="position-relative me-3">
                                            <div class="avatar-circle <?php echo ($rank==1) ? 'bg-warning text-dark shadow-sm' : 'bg-primary bg-opacity-10 text-primary'; ?> fw-bold d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; border-radius: 12px;">
                                                <?php echo substr($cust['first_name'], 0, 1) . substr($cust['last_name'], 0, 1); ?>
                                            </div>
                                            <?php if($rank <= 3): ?>
                                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.55rem;">
                                                    #<?php echo $rank; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold text-dark"><?php echo htmlspecialchars($cust['first_name'] . ' ' . $cust['last_name']); ?></h6>
                                            <div class="d-flex align-items-center mt-1 gap-2">
                                                <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.65rem;">ID: <?php echo $cust['public_id']; ?></span>
                                                <small class="text-muted fw-bold" style="font-size: 0.7rem;"><i class="fa-solid fa-handshake ms-1 me-1 text-secondary"></i> <?php echo $cust['txn_count']; ?> Loans</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.6rem;">Lifetime Value</small>
                                        <span class="fw-bold text-success fs-6">₱<?php echo number_format($cust['total_value'], 2); ?></span>
                                    </div>
                                </li>
                            <?php $rank++; endwhile; ?>
                        <?php else: ?>
                            <li class="list-group-item text-center text-muted py-5 border-0">No customer data available for this timeframe.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-4 h-100 border-top border-4 border-info">
                <div class="card-header bg-white p-4 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-medal me-2 text-info"></i> Top Performing Tellers</h6>
                        <small class="text-muted" style="font-size: 0.7rem;">Highest collection volume</small>
                    </div>
                    <form method="GET" class="d-inline-block">
                        <input type="hidden" name="chart_range" value="<?php echo htmlspecialchars($chart_range); ?>">
                        <input type="hidden" name="activity_range" value="<?php echo htmlspecialchars($activity_range); ?>">
                        <input type="hidden" name="client_range" value="<?php echo htmlspecialchars($client_range); ?>">
                        <select name="teller_range" class="form-select form-select-sm bg-info bg-opacity-10 text-info border-0 fw-bold shadow-none rounded-pill px-3" onchange="this.form.submit()">
                            <option value="all_time" <?php echo ($teller_range == 'all_time') ? 'selected' : ''; ?>>All Time</option>
                            <option value="7_days" <?php echo ($teller_range == '7_days') ? 'selected' : ''; ?>>7 Days</option>
                            <option value="this_month" <?php echo ($teller_range == 'this_month') ? 'selected' : ''; ?>>This Month</option>
                            <option value="last_3_months" <?php echo ($teller_range == 'last_3_months') ? 'selected' : ''; ?>>3 Months</option>
                            <option value="6_months" <?php echo ($teller_range == '6_months') ? 'selected' : ''; ?>>6 Months</option>
                            <option value="1_year" <?php echo ($teller_range == '1_year') ? 'selected' : ''; ?>>1 Year</option>
                        </select>
                    </form>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if($res_tellers->num_rows > 0): ?>
                            <?php $rank = 1; while($teller = $res_tellers->fetch_assoc()): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center p-4 border-0 border-bottom custom-hover">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle <?php echo ($rank==1) ? 'bg-info text-white shadow-sm' : 'bg-light text-muted border'; ?> fw-bold d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                                            <?php echo $rank; ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold text-dark"><?php echo ucfirst($teller['username']); ?></h6>
                                            <small class="text-muted"><i class="fa-solid fa-receipt me-1"></i> <?php echo $teller['txn_count']; ?> Payments</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.6rem;">Total Collected</small>
                                        <span class="fw-bold text-primary fs-6">₱<?php echo number_format($teller['total_collected'], 2); ?></span>
                                    </div>
                                </li>
                            <?php $rank++; endwhile; ?>
                        <?php else: ?>
                            <li class="list-group-item text-center text-muted py-5 border-0">No teller data available for this timeframe.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    Chart.defaults.font.family = "'Inter', 'Helvetica Neue', 'Arial', sans-serif";
    Chart.defaults.color = '#6c757d';

    // 1. Revenue Chart
    const ctxRev = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctxRev, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Revenue (₱)',
                data: <?php echo json_encode($revenue_data); ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.05)',
                borderWidth: 3, fill: true, tension: 0.4,
                pointRadius: 4, pointBackgroundColor: '#fff',
                pointBorderColor: '#0d6efd', pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { 
                y: { beginAtZero: true, grid: { borderDash: [4, 4], color: '#f1f2f4' }, border: {display: false} }, 
                x: { grid: { display: false }, border: {display: false} } 
            }
        }
    });

    // 2. Activity Chart
    const ctxAct = document.getElementById('activityChart').getContext('2d');
    new Chart(ctxAct, {
        type: 'bar',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], 
            datasets: [{
                label: 'Transactions',
                data: <?php echo json_encode($activity_counts); ?>,
                backgroundColor: '#ffc107', borderRadius: 6, barThickness: 12
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { 
                y: { beginAtZero: true, grid: { borderDash: [4, 4], color: '#f1f2f4' }, border: {display: false}, ticks: { stepSize: 1 } }, 
                x: { grid: { display: false }, border: {display: false} } 
            }
        }
    });

    // 3. Status Chart
    const ctxStat = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStat, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($status_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($status_counts); ?>,
                backgroundColor: ['#198754', '#dc3545', '#0d6efd', '#6c757d', '#ffc107'],
                borderWidth: 0, hoverOffset: 4
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20, boxWidth: 8, font: {size: 12, weight: 'bold'} } } },
            cutout: '80%'
        }
    });

    // Maintain Scroll Position on Refresh
    document.addEventListener("DOMContentLoaded", function() {
        let scrollpos = sessionStorage.getItem('dashboard_scrollpos');
        if (scrollpos) { window.scrollTo(0, parseInt(scrollpos)); }
    });
    window.addEventListener("beforeunload", function () {
        sessionStorage.setItem('dashboard_scrollpos', window.scrollY);
    });
</script>

<style>
    .stat-card { transition: transform 0.2s ease; }
    .stat-card:hover { transform: translateY(-5px); }
    .custom-hover:hover { background-color: #f8faff !important; }
</style>

<?php include_once '../../includes/admin_footer.php'; ?>