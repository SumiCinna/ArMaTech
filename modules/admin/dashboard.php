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

// --- B. DATA MINING: Time-Series Activity (Dynamic) ---
$activity_range = $_GET['activity_range'] ?? '1_month'; // Default to 1 month
$activity_interval_sql = "";

if ($activity_range == '7_days') {
    $activity_interval_sql = "WHERE date_paid >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($activity_range == '1_month') {
    $activity_interval_sql = "WHERE date_paid >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
} elseif ($activity_range == '2_months') {
    $activity_interval_sql = "WHERE date_paid >= DATE_SUB(NOW(), INTERVAL 2 MONTH)";
} elseif ($activity_range == '6_months') {
    $activity_interval_sql = "WHERE date_paid >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
} else {
    $activity_interval_sql = ""; // All Time
}

$activity_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$activity_counts = [0,0,0,0,0,0,0]; // Default zeros

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
$sql_tellers = "SELECT a.username, SUM(p.amount_paid) as total_collected, COUNT(p.payment_id) as txn_count 
                FROM payments p 
                JOIN accounts a ON p.teller_id = a.account_id 
                GROUP BY p.teller_id 
                ORDER BY total_collected DESC LIMIT 4";
$res_tellers = $conn->query($sql_tellers);
?>

<div class="container-fluid px-4 pb-5">
    <h3 class="fw-bold text-dark mt-4 mb-4">Executive Dashboard</h3>

    <div class="row g-4 mb-4">
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 border-start border-4 border-success h-100">
                <div class="d-flex justify-content-between">
                    <div>
                        <small class="text-muted text-uppercase fw-bold">Cash In (Today)</small>
                        <h3 class="fw-bold mb-0 text-success">₱<?php echo number_format($res_cash['today_sales'] ?? 0, 2); ?></h3>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success rounded p-3">
                        <i class="fa-solid fa-cash-register fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 border-start border-4 border-primary h-100">
                <div class="d-flex justify-content-between">
                    <div>
                        <small class="text-muted text-uppercase fw-bold">Active Lending</small>
                        <h3 class="fw-bold mb-0 text-primary">₱<?php echo number_format($res_principal['total'] ?? 0, 2); ?></h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary rounded p-3">
                        <i class="fa-solid fa-hand-holding-dollar fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 border-start border-4 border-dark h-100">
                <div class="d-flex justify-content-between">
                    <div>
                        <small class="text-muted text-uppercase fw-bold">Total Customers</small>
                        <h3 class="fw-bold mb-0 text-dark"><?php echo $res_cust['count']; ?></h3>
                    </div>
                    <div class="bg-dark bg-opacity-10 text-dark rounded p-3">
                        <i class="fa-solid fa-users fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 border-start border-4 border-danger h-100">
                <div class="d-flex justify-content-between">
                    <div>
                        <small class="text-danger text-uppercase fw-bold"><i class="fa-solid fa-brain me-1"></i> Default Risk</small>
                        <h3 class="fw-bold mb-0 text-dark"><?php echo $res_high_risk['risk_count']; ?></h3>
                        <small class="text-muted" style="font-size: 0.65rem;">High-value items nearing expiry</small>
                    </div>
                    <div class="bg-danger bg-opacity-10 text-danger rounded p-3">
                        <i class="fa-solid fa-triangle-exclamation fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-4 mb-4">
        
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-chart-line me-2 text-primary"></i> <?php echo $chart_title_text; ?></h6>
                    <form method="GET" class="d-inline-block">
                        <input type="hidden" name="activity_range" value="<?php echo htmlspecialchars($activity_range); ?>" id="activityRange">
                        <select name="chart_range" class="form-select form-select-sm bg-light border-0 fw-bold text-secondary shadow-none" onchange="this.form.submit()">
                            <option value="7_days" <?php echo ($chart_range == '7_days') ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="30_days" <?php echo ($chart_range == '30_days') ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="6_months" <?php echo ($chart_range == '6_months') ? 'selected' : ''; ?>>Last 6 Months</option>
                            <option value="1_year" <?php echo ($chart_range == '1_year') ? 'selected' : ''; ?>>Last 1 Year</option>
                        </select>
                    </form>
                </div>
                <div class="card-body pt-0">
                    <canvas id="revenueChart" style="height: 280px; width: 100%;"></canvas>
                 </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0">
                    <div>
                        <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-calendar-day me-2 text-warning"></i> Peak Activity</h6>
                    </div>
                    <form method="GET" class="d-inline-block">
                        <input type="hidden" name="chart_range" value="<?php echo htmlspecialchars($chart_range); ?>" id = "chartRange">
                        <select name="activity_range" class="form-select form-select-sm bg-light border-0 fw-bold text-secondary shadow-none" onchange="this.form.submit()">
                            <option value="7_days" <?php echo ($activity_range == '7_days') ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="1_month" <?php echo ($activity_range == '1_month') ? 'selected' : ''; ?>>Last 1 Month</option>
                            <option value="2_months" <?php echo ($activity_range == '2_months') ? 'selected' : ''; ?>>Last 2 Months</option>
                            <option value="6_months" <?php echo ($activity_range == '6_months') ? 'selected' : ''; ?>>Last 6 Months</option>
                            <option value="all_time" <?php echo ($activity_range == 'all_time') ? 'selected' : ''; ?>>All Time</option>
                        </select>
                    </form>
                </div>
                <div class="card-body pt-0">
                    <canvas id="activityChart" style="height: 250px; width: 100%;"></canvas>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-4 mb-4">
        
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0">
                    <h6 class="mb-0 fw-bold"><i class="fa-solid fa-list-check me-2"></i> Recent Transactions</h6>
                    <a href="reports_sales.php" class="btn btn-sm btn-light border text-primary fw-bold">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase border-bottom">
                                <tr>
                                    <th class="ps-4 py-3">Date</th>
                                    <th class="py-3">Teller</th>
                                    <th class="py-3">Type</th>
                                    <th class="py-3">Amount</th>
                                    <th class="py-3">PT Number</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql_recent = "SELECT p.*, a.username, t.pt_number 
                                            FROM payments p 
                                            JOIN accounts a ON p.teller_id = a.account_id
                                            JOIN transactions t ON p.transaction_id = t.transaction_id
                                            ORDER BY p.date_paid DESC LIMIT 6";
                                $res_recent = $conn->query($sql_recent);
                                
                                if($res_recent->num_rows > 0):
                                    while($row = $res_recent->fetch_assoc()):
                                ?>
                                    <tr class="border-bottom">
                                        <td class="ps-4 text-nowrap"><?php echo date('M d, Y h:i A', strtotime($row['date_paid'])); ?></td>
                                        <td><span class="badge bg-secondary bg-opacity-10 text-secondary border"><?php echo strtoupper($row['username']); ?></span></td>
                                        <td>
                                            <?php if($row['payment_type'] == 'interest_only'): ?>
                                                <span class="badge bg-info bg-opacity-10 text-info">Renewal</span>
                                            <?php elseif($row['payment_type'] == 'partial_payment'): ?>
                                                <span class="badge bg-warning bg-opacity-10 text-warning">Partial</span>
                                            <?php else: ?>
                                                <span class="badge bg-success bg-opacity-10 text-success">Redeem</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold text-success">₱<?php echo number_format($row['amount_paid'], 2); ?></td>
                                        <td class="text-muted small font-monospace"><?php echo $row['pt_number']; ?></td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted">No recent activity.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 d-flex flex-column gap-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-chart-pie me-2 text-success"></i> Loan Portfolio Status</h6>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center pt-0 pb-4">
                    <div style="height: 180px; width: 180px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 flex-grow-1">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold"><i class="fa-solid fa-trophy me-2 text-warning"></i> Top Tellers</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if($res_tellers->num_rows > 0): ?>
                            <?php $rank = 1; while($teller = $res_tellers->fetch_assoc()): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-3 border-0 border-bottom">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle <?php echo ($rank==1) ? 'bg-warning text-dark' : 'bg-light text-muted border'; ?> fw-bold d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px;">
                                            <?php echo $rank++; ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold text-dark"><?php echo $teller['username']; ?></h6>
                                            <small class="text-muted"><?php echo $teller['txn_count']; ?> Transactions</small>
                                        </div>
                                    </div>
                                    <span class="fw-bold text-primary">₱<?php echo number_format($teller['total_collected'], 2); ?></span>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="list-group-item text-center text-muted py-4 border-0">No data available.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // 1. Revenue Chart (Line)
    const ctxRev = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctxRev, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Revenue (₱)',
                data: <?php echo json_encode($revenue_data); ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#0d6efd'
            }]
        },
       options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { 
                y: { beginAtZero: true, grid: { borderDash: [4, 4] } }, 
                x: { grid: { display: false } } 
            }
           
        }
    });

    // 2. DATA MINING: Activity Chart (Bar)
    const ctxAct = document.getElementById('activityChart').getContext('2d');
    new Chart(ctxAct, {
        type: 'bar',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], 
            datasets: [{
                label: 'Transactions',
                data: <?php echo json_encode($activity_counts); ?>,
                backgroundColor: 'rgba(245, 158, 11, 0.7)', // Warning Yellow
                borderColor: '#f59e0b',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { 
                y: { beginAtZero: true, grid: { borderDash: [4, 4] }, ticks: { stepSize: 1 } }, 
                x: { grid: { display: false } } 
            }
        }
    });

    // 3. Status Chart (Doughnut)
    const ctxStat = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStat, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($status_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($status_counts); ?>,
                backgroundColor: ['#198754', '#dc3545', '#0d6efd', '#6c757d', '#ffc107'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { position: 'right', labels: { usePointStyle: true, boxWidth: 8, font: {size: 11} } } 
            },
            cutout: '75%'
        }
    });

    // Maintain Scroll Position on Page Reload (when changing chart filters)
    document.addEventListener("DOMContentLoaded", function() {
        let scrollpos = sessionStorage.getItem('dashboard_scrollpos');
        if (scrollpos) { window.scrollTo(0, parseInt(scrollpos)); }
    });

    window.addEventListener("beforeunload", function () {
        sessionStorage.setItem('dashboard_scrollpos', window.scrollY);
    });
</script>

<?php include_once '../../includes/admin_footer.php'; ?>