    <?php
    session_start();
    require_once '../../config/database.php';

    if (!isset($_SESSION['account_id']) || !in_array($_SESSION['role'], ['teller', 'admin'])) {
        header("Location: ../../teller_login.php?error=Unauthorized Access");
        exit();
    }

    $teller_name = $_SESSION['full_name'];
    $teller_id = $_SESSION['account_id'];

    // 1. Current Day Summary (Teller Specific)
    $sql_pawn = "SELECT SUM(i.appraised_value) as total FROM transactions t 
                JOIN items i ON t.transaction_id = i.transaction_id 
                WHERE t.teller_id = ? AND DATE(t.date_pawned) = CURDATE()";
    $stmt = $conn->prepare($sql_pawn);
    $stmt->bind_param("i", $teller_id);
    $stmt->execute();
    $total_pawn = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $sql_pay = "SELECT SUM(amount_paid) as total FROM payments WHERE teller_id = ? AND DATE(date_paid) = CURDATE()";
    $stmt = $conn->prepare($sql_pay);
    $stmt->bind_param("i", $teller_id);
    $stmt->execute();
    $total_pay = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $sql_count = "SELECT COUNT(*) as total FROM transactions WHERE DATE(date_pawned) = CURDATE() AND teller_id = ?";
    $stmt = $conn->prepare($sql_count);
    $stmt->bind_param("i", $teller_id);
    $stmt->execute();
    $count_today = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // 2. Trend Data (Teller Specific - Dynamic Range)
    $range = isset($_GET['range']) && in_array($_GET['range'], [7, 30, 60]) ? (int)$_GET['range'] : 7;
    $range_label = "over the last $range days";
    if ($range == 30) $range_label = "over the last 1 month";
    if ($range == 60) $range_label = "over the last 2 months";

    $trend_out = [];
    $trend_in = [];
    $trend_dates = [];
    for ($i = $range - 1; $i >= 0; $i--) {
        $ts = strtotime("-$i days midnight");
        $date = date('Y-m-d', $ts);
        $trend_dates[] = $ts * 1000;
        
        $q_out = "SELECT SUM(i.appraised_value) as total FROM transactions t 
                  JOIN items i ON t.transaction_id = i.transaction_id 
                  WHERE DATE(t.date_pawned) = '$date' AND t.teller_id = $teller_id";
        $trend_out[] = (float)($conn->query($q_out)->fetch_assoc()['total'] ?? 0);

        $q_in = "SELECT SUM(amount_paid) as total FROM payments WHERE DATE(date_paid) = '$date' AND teller_id = $teller_id";
        $trend_in[] = (float)($conn->query($q_in)->fetch_assoc()['total'] ?? 0);
    }

    // 3. Category Distribution (Teller Specific)
    $cat_labels = [];
    $cat_counts = [];
    $sql_cat = "SELECT i.device_type as category, COUNT(*) as count 
                FROM items i 
                JOIN transactions t ON i.transaction_id = t.transaction_id 
                WHERE t.teller_id = ? 
                GROUP BY i.device_type ORDER BY count DESC";
    $stmt = $conn->prepare($sql_cat);
    $stmt->bind_param("i", $teller_id);
    $stmt->execute();
    $res_cat = $stmt->get_result();
    while($row = $res_cat->fetch_assoc()) {
        $cat_labels[] = $row['category'];
        $cat_counts[] = (int)$row['count'];
    }

    // 4. Recent Transactions (Teller Specific)
    $sql_recent = "
        (SELECT 
            t.pt_number, 
            CONCAT(p.first_name, ' ', p.last_name) AS customer_name,
            'New Pawn' AS type,
            t.principal_amount AS amount,
            t.date_pawned AS date_time
        FROM transactions t
        JOIN profiles p ON t.customer_id = p.profile_id
        WHERE t.teller_id = ?)
        
        UNION ALL
        
        (SELECT 
            t.pt_number,
            CONCAT(p.first_name, ' ', p.last_name) AS customer_name,
            pay.payment_type AS type,
            pay.amount_paid AS amount,
            pay.date_paid AS date_time
        FROM payments pay
        JOIN transactions t ON pay.transaction_id = t.transaction_id
        JOIN profiles p ON t.customer_id = p.profile_id
        WHERE pay.teller_id = ?)
        
        ORDER BY date_time DESC 
        LIMIT 8";
    $stmt = $conn->prepare($sql_recent);
    $stmt->bind_param("ii", $teller_id, $teller_id);
    $stmt->execute();
    $recent_txns = $stmt->get_result();

    include_once '../../includes/teller_header.php';
    ?>



        <div class="container-fluid px-0">
        
        <!-- Dashboard Header / Controls -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-0">Teller Performance Dashboard</h4>
                <p class="text-muted small mb-0">Overview of activity and cash flow for <?php echo date('F d, Y'); ?></p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-white border shadow-sm btn-sm fw-bold text-secondary px-3">
                    <i class="bi bi-calendar3 me-2"></i> Today
                </button>
                <button class="btn btn-primary btn-sm shadow-sm fw-bold px-3" onclick="window.location.reload()">
                    <i class="bi bi-arrow-clockwise me-2"></i> Refresh
                </button>
            </div>
        </div>

        <!-- Scorecards Row -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="bg-danger bg-opacity-10 text-danger rounded-3 p-2">
                                <i class="bi bi-arrow-up-right fs-4"></i>
                            </div>
                            <span class="badge bg-light text-muted fw-normal">Today</span>
                        </div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Total Pawned (Out)</h6>
                        <h3 class="fw-bold text-dark mb-1">₱<?php echo number_format($total_pawn, 2); ?></h3>
                        <div class="text-danger small fw-bold">
                            <i class="bi bi-caret-down-fill me-1"></i> Principal released
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="bg-success bg-opacity-10 text-success rounded-3 p-2">
                                <i class="bi bi-arrow-down-left fs-4"></i>
                            </div>
                            <span class="badge bg-light text-muted fw-normal">Today</span>
                        </div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Total Payments (In)</h6>
                        <h3 class="fw-bold text-dark mb-1">₱<?php echo number_format($total_pay, 2); ?></h3>
                        <div class="text-success small fw-bold">
                            <i class="bi bi-caret-up-fill me-1"></i> Collections processed
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2">
                                <i class="bi bi-receipt fs-4"></i>
                            </div>
                            <span class="badge bg-light text-muted fw-normal">Today</span>
                        </div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Transactions</h6>
                        <h3 class="fw-bold text-dark mb-1"><?php echo $count_today; ?></h3>
                        <div class="text-primary small fw-bold">
                            <i class="bi bi-activity me-1"></i> Total activity count
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-dark">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="bg-white bg-opacity-10 text-white rounded-3 p-2">
                                <i class="bi bi-wallet2 fs-4"></i>
                            </div>
                            <span class="badge bg-white bg-opacity-10 text-white fw-normal">Current</span>
                        </div>
                        <h6 class="text-white-50 text-uppercase small fw-bold mb-1">Net Cash Flow</h6>
                        <h3 class="fw-bold text-white mb-1">₱<?php echo number_format($total_pay - $total_pawn, 2); ?></h3>
                        <div class="text-info small fw-bold">
                            <i class="bi bi-info-circle me-1"></i> Total daily balance
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Main Analytics: Trend -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-bottom-0 py-4 px-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-0">Value Flow Trend</h5>
                            <p class="text-muted small mb-0">Money In vs Money Out <span class="text-primary fw-bold"><?php echo $range_label; ?></span></p>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm rounded-pill px-3 shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                <li><h6 class="dropdown-header">Select Date Range</h6></li>
                                <li><a class="dropdown-item py-2 <?php echo ($range == 7) ? 'active bg-primary' : ''; ?>" href="?range=7"><i class="bi bi-calendar-date me-2"></i> Last 7 Days</a></li>
                                <li><a class="dropdown-item py-2 <?php echo ($range == 30) ? 'active bg-primary' : ''; ?>" href="?range=30"><i class="bi bi-calendar-event me-2"></i> Last 1 Month</a></li>
                                <li><a class="dropdown-item py-2 <?php echo ($range == 60) ? 'active bg-primary' : ''; ?>" href="?range=60"><i class="bi bi-calendar-month me-2"></i> Last 2 Months</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item py-2 small text-muted" href="dashboard.php"><i class="bi bi-arrow-clockwise me-2"></i> Reset to Default</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body px-0 position-relative">
                        <?php if(array_sum($trend_in) == 0 && array_sum($trend_out) == 0): ?>
                            <div class="text-center py-5 my-4">
                                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <i class="bi bi-graph-up-arrow text-muted opacity-50 fs-2"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">No Activity Detected</h6>
                                <p class="text-muted small mb-0">No cash flow recorded for the selected range.</p>
                            </div>
                        <?php else: ?>
                            <div id="trendChart" style="min-height: 350px;"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Side Analytics: Categories -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                    <div class="card-header bg-white border-bottom-0 py-4 px-4">
                        <h5 class="fw-bold mb-0">Item Categories</h5>
                        <p class="text-muted small mb-0">Asset distribution by type</p>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center pt-0">
                        <?php if(!empty($cat_labels)): ?>
                            <div id="categoryChart" style="width: 100%;"></div>
                        <?php else: ?>
                            <div class="text-center py-5 my-2">
                                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                                    <i class="bi bi-tag text-muted opacity-50 fs-3"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">No Items</h6>
                                <p class="text-muted small mb-0 px-4">Categorized assets will appear here.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Recent Transactions -->
            <div class="col-lg-9">
                <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                    <div class="card-header bg-white border-bottom-0 py-4 px-4 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Recent Activity</h5>
                        <a href="transactions.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-4">PT Number</th>
                                    <th>Customer</th>
                                    <th>Operation</th>
                                    <th>Amount</th>
                                    <th>Time</th>
                                    <th class="pe-4 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_txns && $recent_txns->num_rows > 0): ?>
                                    <?php while($row = $recent_txns->fetch_assoc()): ?>
                                        <?php
                                            $badge_class = "bg-secondary";
                                            $type_label = $row['type'];
                                            $amount_class = "text-dark";
                                            $amount_prefix = "₱";

                                            switch($row['type']) {
                                                case 'New Pawn':
                                                    $badge_class = "bg-primary-subtle text-primary border border-primary border-opacity-10";
                                                    $type_label = "New Pawn";
                                                    $amount_class = "text-danger";
                                                    $amount_prefix = "- ₱";
                                                    break;
                                                case 'interest_only':
                                                    $badge_class = "bg-info-subtle text-info border border-info border-opacity-10";
                                                    $type_label = "Renewal";
                                                    $amount_class = "text-success";
                                                    $amount_prefix = "+ ₱";
                                                    break;
                                                case 'partial_payment':
                                                    $badge_class = "bg-warning-subtle text-warning border border-warning border-opacity-10";
                                                    $type_label = "Partial Pay";
                                                    $amount_class = "text-success";
                                                    $amount_prefix = "+ ₱";
                                                    break;
                                                case 'full_redemption':
                                                    $badge_class = "bg-success-subtle text-success border border-success border-opacity-10";
                                                    $type_label = "Redemption";
                                                    $amount_class = "text-success";
                                                    $amount_prefix = "+ ₱";
                                                    break;
                                            }
                                        ?>
                                        <tr>
                                            <td class="ps-4"><span class="font-monospace fw-bold text-primary"><?php echo htmlspecialchars($row['pt_number']); ?></span></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['customer_name']); ?></div>
                                            </td>
                                            <td><span class="badge rounded-pill <?php echo $badge_class; ?>"><?php echo $type_label; ?></span></td>
                                            <td class="<?php echo $amount_class; ?> fw-bold"><?php echo $amount_prefix . number_format($row['amount'], 2); ?></td>
                                            <td class="text-muted small"><?php echo date('h:i A', strtotime($row['date_time'])); ?></td>
                                            <td class="pe-4 text-center"><i class="bi bi-check-circle-fill text-success fs-5"></i></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">
                                            <img src="../../assets/img/empty.svg" alt="No data" style="height: 60px; opacity: 0.5;" class="mb-3 d-block mx-auto">
                                            No recent transactions found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden bg-primary text-white">
                    <div class="card-body p-4 d-flex flex-column">
                        <h5 class="fw-bold mb-4">Quick Operations</h5>
                        
                        <a href="new_pawn.php" class="btn btn-light text-primary w-100 mb-3 py-3 rounded-4 shadow-sm fw-bold border-0 transition-card">
                            <i class="bi bi-plus-circle me-2"></i> New Pawn
                        </a>
                        
                        <a href="redeem.php" class="btn btn-outline-light text-white w-100 mb-3 py-3 rounded-4 fw-bold border-2 transition-card">
                            <i class="bi bi-cash-stack me-2"></i> Redeem/Renew
                        </a>
                        
                        <a href="search_records.php" class="btn btn-outline-light text-white w-100 mb-4 py-3 rounded-4 fw-bold border-2 transition-card">
                            <i class="bi bi-search me-2"></i> Search Records
                        </a>

                        <div class="mt-auto p-3 bg-white bg-opacity-10 rounded-4">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-info-circle me-2"></i>
                                <span class="small fw-bold">Daily Protocol</span>
                            </div>
                            <p class="small mb-0 opacity-75">Ensure all ID photos are clear and expiration dates are verified before processing loans.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Scripts for Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Trend Chart
            var trendOptions = {
                series: [{
                    name: 'Money In (Collections)',
                    data: <?php echo json_encode($trend_in); ?>
                }, {
                    name: 'Money Out (Principal)',
                    data: <?php echo json_encode($trend_out); ?>
                }],
                chart: {
                    type: 'area',
                    height: 380,
                    toolbar: {
                        show: true,
                        autoSelected: 'pan',
                        tools: {
                            download: false,
                            selection: true,
                            zoom: true,
                            zoomin: true,
                            zoomout: true,
                            pan: true,
                            reset: true
                        }
                    },
                    zoom: {
                        enabled: true,
                        type: 'x',
                        autoScaleYaxis: false
                    },
                    fontFamily: 'Inter, sans-serif'
                },
                colors: ['#10b981', '#ef4444'],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 3 },
                markers: {
                    size: 0,
                    hover: { size: 6 }
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.45,
                        opacityTo: 0.05,
                        stops: [20, 100]
                    }
                },
                xaxis: {
                    type: 'datetime',
                    categories: <?php echo json_encode($trend_dates); ?>,
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: {
                        datetimeUTC: false,
                        style: { colors: '#64748b' }
                    }
                },
                yaxis: {
                    labels: {
                        style: { colors: '#64748b' },
                        formatter: function (val) {
                            return "₱" + val.toLocaleString();
                        }
                    }
                },
                grid: {
                    borderColor: '#f1f5f9',
                    padding: { left: 10, right: 10 }
                },
                tooltip: {
                    theme: 'light',
                    x: {
                        show: true,
                        format: 'dd MMM yyyy'
                    },
                    y: {
                        formatter: function (val) {
                            return "₱" + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                        }
                    }
                }
            };
            var trendChart = new ApexCharts(document.querySelector("#trendChart"), trendOptions);
            trendChart.render();

            // Category Chart
            var catOptions = {
                series: <?php echo json_encode($cat_counts); ?>,
                chart: {
                    type: 'donut',
                    height: 320,
                    fontFamily: 'Inter, sans-serif'
                },
                labels: <?php echo json_encode($cat_labels); ?>,
                colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'],
                legend: { position: 'bottom' },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total Items',
                                    formatter: function (w) {
                                        return w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                                    }
                                }
                            }
                        }
                    }
                },
                dataLabels: { enabled: false }
            };
            var categoryChart = new ApexCharts(document.querySelector("#categoryChart"), catOptions);
            categoryChart.render();
        });
    </script>

    <style>
        .btn-white { background-color: #fff; border: 1px solid #e2e8f0; color: #1e293b; }
        .btn-white:hover { background-color: #f8fafc; }
        .transition-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .transition-card:hover { transform: translateY(-3px); box-shadow: 0 12px 20px -10px rgba(0,0,0,0.15) !important; }
        .table > :not(caption) > * > * { padding: 1rem 0.5rem; border-bottom-color: #f1f5f9; }
        .bg-primary-subtle { background-color: rgba(13, 110, 253, 0.1); }
        .bg-info-subtle { background-color: rgba(13, 202, 240, 0.1); }
        .bg-warning-subtle { background-color: rgba(255, 193, 7, 0.1); }
        .bg-success-subtle { background-color: rgba(25, 135, 84, 0.1); }
    </style>

    <?php include_once '../../includes/teller_footer.php'; ?>
