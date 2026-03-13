<?php
// modules/admin/reports_sales.php
require_once '../../config/database.php';
include_once '../../includes/admin_header.php';

// 1. DATE LOGIC
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date'] ?? date('Y-m-t');

// 2. MAIN QUERY: List of Transactions (Detailed Table)
$sql = "SELECT p.*, t.pt_number, t.principal_amount as original_principal, a.username as teller_name 
        FROM payments p
        JOIN transactions t ON p.transaction_id = t.transaction_id
        LEFT JOIN accounts a ON p.teller_id = a.account_id
        WHERE DATE(p.date_paid) BETWEEN ? AND ?
        ORDER BY p.date_paid DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// 3. PROCESS DATA FOR TABLE & TOTALS
$total_collected = 0;
$total_interest  = 0;
$total_principal = 0;
$report_data = [];

// Data for TELLER PERFORMANCE
$teller_stats = [];

while ($row = $result->fetch_assoc()) {
    $report_data[] = $row;
    $total_collected += $row['amount_paid'];
    
    // --- BULLETPROOF INCOME SPLITTING ---
    $principal_paid_in_txn = 0;
    $interest_paid_in_txn = 0;

    if ($row['payment_type'] == 'interest_only') {
        // Renewal: 100% is interest
        $interest_paid_in_txn = $row['amount_paid'];
    } else {
        // Check if we have the new tracking data (Not NULL)
        if (!is_null($row['old_principal']) && !is_null($row['new_principal'])) {
            $principal_paid_in_txn = $row['old_principal'] - $row['new_principal'];
            $interest_paid_in_txn = $row['amount_paid'] - $principal_paid_in_txn;
        } else {
            // LEGACY RECORD FALLBACK (For older database entries)
            // If amount paid is more than the original loan, the extra is interest
            if ($row['amount_paid'] > $row['original_principal']) {
                $principal_paid_in_txn = $row['original_principal'];
                $interest_paid_in_txn = $row['amount_paid'] - $row['original_principal'];
            } else {
                $principal_paid_in_txn = $row['amount_paid'];
            }
        }
    }

    $total_interest += $interest_paid_in_txn;
    $total_principal += $principal_paid_in_txn;
    // ------------------------------------

    // Teller Stats Logic
    $teller = $row['teller_name'] ?? 'System';
    if (!isset($teller_stats[$teller])) {
        $teller_stats[$teller] = 0;
    }
    $teller_stats[$teller] += $row['amount_paid'];
}

// 3.5 PAGINATION LOGIC (Array based to preserve accurate totals)
$limit = 10;
$total_records = count($report_data);
$total_pages = ceil($total_records / $limit);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
$offset = ($page - 1) * $limit;

$paginated_data = array_slice($report_data, $offset, $limit);

// 4. SMART CHART QUERY (Daily vs Monthly Logic)
// Calculate the difference in days between start and end
$start_obj = new DateTime($start_date);
$end_obj   = new DateTime($end_date);
$days_diff = $start_obj->diff($end_obj)->days;

if ($days_diff > 31) {
    // > 31 Days: Group by MONTH (Format: Jan 2026)
    $sql_chart = "SELECT DATE_FORMAT(date_paid, '%b %Y') as label, SUM(amount_paid) as total 
                  FROM payments 
                  WHERE DATE(date_paid) BETWEEN ? AND ? 
                  GROUP BY DATE_FORMAT(date_paid, '%Y-%m'), label 
                  ORDER BY MIN(date_paid) ASC";
    $chart_title = "Monthly Revenue Trend";
} else {
    // <= 31 Days: Group by DAY (Format: Feb 19)
    $sql_chart = "SELECT DATE_FORMAT(date_paid, '%b %d') as label, SUM(amount_paid) as total 
                  FROM payments 
                  WHERE DATE(date_paid) BETWEEN ? AND ? 
                  GROUP BY DATE(date_paid), label 
                  ORDER BY MIN(date_paid) ASC";
    $chart_title = "Daily Revenue Trend";
}

$stmt_c = $conn->prepare($sql_chart);
$stmt_c->bind_param("ss", $start_date, $end_date);
$stmt_c->execute();
$chart_res = $stmt_c->get_result();

$chart_labels = [];
$chart_data   = [];

while($c = $chart_res->fetch_assoc()) {
    $chart_labels[] = $c['label']; 
    $chart_data[]   = $c['total'];
}
?>

<div class="container-fluid px-4 pb-5">
    
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0"><i class="fa-solid fa-chart-line me-2 text-primary"></i> Analytics & Sales</h3>
            <p class="text-muted small mb-0">Financial performance from <span class="fw-bold text-dark"><?php echo date('M d, Y', strtotime($start_date)); ?></span> to <span class="fw-bold text-dark"><?php echo date('M d, Y', strtotime($end_date)); ?></span></p>
        </div>
        <div class="no-print d-flex gap-2">
            <button onclick="exportTableToCSV('sales_report.csv')" class="btn btn-white border fw-bold shadow-sm text-success px-3">
                <i class="fa-solid fa-file-excel me-2"></i> Export
            </button>
            <button onclick="window.print()" class="btn btn-dark fw-bold shadow-sm px-3">
                <i class="fa-solid fa-print me-2"></i> Print
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4 bg-white rounded-4 overflow-hidden no-print">
        <div class="card-body p-4 pb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-filter me-2 text-muted"></i> Report Filters</h6>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold" style="font-size: 0.75rem;" onclick="setDateRange('today')">Today</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold" style="font-size: 0.75rem;" onclick="setDateRange('this_week')">This Week</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold" style="font-size: 0.75rem;" onclick="setDateRange('this_month')">This Month</button>
                </div>
            </div>
            <form method="GET" id="filterForm" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control bg-light border-0 shadow-none py-2" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.7rem;">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control bg-light border-0 shadow-none py-2" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm"><i class="fa-solid fa-magnifying-glass me-2"></i> Generate</button>
                        <a href="reports_sales.php" class="btn btn-light w-100 fw-bold py-2 shadow-sm border">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-8 mb-4 mb-lg-0">
            <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden">
                <div class="card-header bg-white fw-bold py-3 border-0">
                    <i class="fa-solid fa-arrow-trend-up me-2 text-primary"></i> <?php echo $chart_title; ?>
                </div>
                <div class="card-body" style="min-height: 350px;">
                    <canvas id="salesChart" style="max-height: 350px;"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm bg-gradient bg-primary text-white mb-3 rounded-4 overflow-hidden hover-lift">
                <div class="card-body p-4 text-center position-relative">
                    <i class="fa-solid fa-wallet fa-5x position-absolute opacity-10" style="bottom: -10px; right: -10px;"></i>
                    <small class="text-white-50 text-uppercase fw-bold letter-spacing-1" style="font-size: 0.75rem;">Total Revenue</small>
                    <h2 class="display-6 fw-bold mb-0 mt-2">₱<?php echo number_format($total_collected, 2); ?></h2>
                </div>
            </div>
            
            <div class="row g-2 mb-4">
                <div class="col-6">
                    <div class="card border-0 shadow-sm bg-light rounded-4 text-center p-3 h-100 hover-lift">
                        <small class="text-muted fw-bold text-uppercase letter-spacing-1" style="font-size: 0.65rem;">Principal In</small>
                        <h5 class="fw-bold text-dark mb-0 mt-1">₱<?php echo number_format($total_principal, 2); ?></h5>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card border-0 shadow-sm bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-4 text-center p-3 h-100 hover-lift">
                        <small class="text-warning fw-bold text-uppercase letter-spacing-1" style="font-size: 0.65rem;">Interest Earned</small>
                        <h5 class="fw-bold text-dark mb-0 mt-1">₱<?php echo number_format($total_interest, 2); ?></h5>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white fw-bold border-bottom-0 py-3">
                    <i class="fa-solid fa-users me-2 text-secondary"></i> Top Performing Tellers
                </div>
                <div class="card-body p-0 pb-3">
                    <ul class="list-group list-group-flush">
                        <?php if (empty($teller_stats)): ?>
                            <li class="list-group-item text-center text-muted border-0 py-4 small">No collections found.</li>
                        <?php endif; ?>
                        <?php $rank = 1; foreach($teller_stats as $name => $amount): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3 border-0 <?php echo $rank < count($teller_stats) ? 'border-bottom' : ''; ?>">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle <?php echo ($rank==1) ? 'bg-warning text-dark' : 'bg-light text-muted border'; ?> fw-bold d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px; font-size: 0.85rem;">
                                        <?php echo $rank++; ?>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-dark d-block"><?php echo $name; ?></span>
                                        <?php $percent = ($total_collected > 0) ? round(($amount / $total_collected) * 100) : 0; ?>
                                        <div class="progress mt-1 bg-light border" style="height: 4px; width: 100px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percent; ?>%;"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold text-success d-block">₱<?php echo number_format($amount, 2); ?></span>
                                    <small class="text-muted" style="font-size: 0.65rem;"><?php echo $percent; ?>% of total</small>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-5 rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0">
            <h6 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-book-open me-2 text-secondary"></i> Transaction Ledger</h6>
            <span class="badge bg-light text-dark border rounded-pill px-3 py-2">
                Total Records: <?php echo count($report_data); ?>
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 custom-table" id="dataTable">
                    <thead class="bg-light text-muted small text-uppercase" style="border-bottom: 2px solid #f1f2f4;">
                        <tr>
                            <th class="ps-4 py-3 fw-bold">Date</th>
                            <th class="py-3 fw-bold">PT Number</th>
                            <th class="py-3 fw-bold">Type</th>
                            <th class="py-3 fw-bold">Teller</th>
                            <th class="text-end pe-4 py-3 fw-bold">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_records > 0): ?>
                            <?php foreach($paginated_data as $row): ?>
                                <tr style="border-bottom: 1px solid #f8f9fa;">
                                    <td class="ps-4 py-3 text-nowrap">
                                        <span class="fw-bold text-dark d-block" style="font-size: 0.85rem;"><?php echo date('M d, Y', strtotime($row['date_paid'])); ?></span>
                                        <span class="text-muted" style="font-size: 0.75rem;"><?php echo date('h:i A', strtotime($row['date_paid'])); ?></span>
                                    </td>
                                    <td class="py-3">
                                        <span class="badge bg-light text-dark border font-monospace"><?php echo $row['pt_number']; ?></span>
                                    </td>
                                    <td class="py-3">
                                        <?php if($row['payment_type'] == 'interest_only'): ?>
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-3 py-1 text-uppercase" style="font-size: 0.7rem;"><i class="fa-solid fa-rotate-left me-1"></i> Interest</span>
                                        <?php else: ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1 text-uppercase" style="font-size: 0.7rem;"><i class="fa-solid fa-check-circle me-1"></i> Redeem/Partial</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-user-tie text-muted me-2"></i>
                                            <span class="small fw-bold text-secondary text-uppercase"><?php echo $row['teller_name'] ?? 'System'; ?></span>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4 py-3 fw-bold text-success fs-6">₱<?php echo number_format($row['amount_paid'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="empty-state d-flex flex-column align-items-center justify-content-center py-4">
                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 text-muted" style="width: 80px; height: 80px;">
                                            <i class="fa-solid fa-file-invoice-dollar fs-1 opacity-50"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-1">No Transactions</h5>
                                        <p class="text-muted small">No payment records found for the selected date range.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($total_pages > 1): ?>
        <nav class="mt-4 mb-4 no-print">
            <ul class="pagination justify-content-center custom-pagination">
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link shadow-sm" href="?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&page=<?php echo $page - 1; ?>"><i class="fa-solid fa-chevron-left"></i></a>
                </li>
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link shadow-sm fw-bold" href="?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link shadow-sm" href="?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&page=<?php echo $page + 1; ?>"><i class="fa-solid fa-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<table id="exportTable" style="display:none;">
    <thead>
        <tr>
            <th>Date</th>
            <th>PT Number</th>
            <th>Type</th>
            <th>Teller</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($report_data as $row): ?>
            <tr>
                <td><?php echo date('M d, Y h:i A', strtotime($row['date_paid'])); ?></td>
                <td><?php echo $row['pt_number']; ?></td>
                <td><?php echo $row['payment_type']; ?></td>
                <td><?php echo $row['teller_name'] ?? 'System'; ?></td>
                <td><?php echo $row['amount_paid']; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // 1. GENERATE CHART
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: '<?php echo $chart_title; ?> (₱)', // Dynamic Label (Daily vs Monthly)
                data: <?php echo json_encode($chart_data); ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4 
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // 2. EXPORT TO CSV FUNCTION
    function exportTableToCSV(filename) {
        var csv = [];
        var rows = document.querySelectorAll("table#exportTable tr");
        
        for (var i = 0; i < rows.length; i++) {
            var row = [], cols = rows[i].querySelectorAll("td, th");
            for (var j = 0; j < cols.length; j++) 
                row.push('"' + cols[j].innerText + '"'); 
            
            csv.push(row.join(","));        
        }

        var csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
        var downloadLink = document.createElement("a");
        downloadLink.download = filename;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
        downloadLink.click();
    }

    // 3. DATE PRESET SCRIPT
    function setDateRange(range) {
        let start = new Date();
        let end = new Date();
        
        if (range === 'today') {
            // start and end are already today
        } else if (range === 'this_week') {
            const first = start.getDate() - start.getDay() + (start.getDay() === 0 ? -6 : 1);
            start = new Date(start.setDate(first));
        } else if (range === 'this_month') {
            start = new Date(start.getFullYear(), start.getMonth(), 1);
            end = new Date(start.getFullYear(), start.getMonth() + 1, 0);
        }

        const fmt = (d) => {
            let m = '' + (d.getMonth() + 1), day = '' + d.getDate(), yr = d.getFullYear();
            if (m.length < 2) m = '0' + m;
            if (day.length < 2) day = '0' + day;
            return [yr, m, day].join('-');
        };

        document.getElementById('start_date').value = fmt(start);
        document.getElementById('end_date').value = fmt(end);
        document.getElementById('filterForm').submit();
    }

    // 4. MAINTAIN SCROLL POSITION
    document.addEventListener("DOMContentLoaded", function() {
        let scrollpos = sessionStorage.getItem('reports_scrollpos');
        if (scrollpos) { window.scrollTo(0, parseInt(scrollpos)); }
    });
    window.addEventListener("beforeunload", function () {
        sessionStorage.setItem('reports_scrollpos', window.scrollY);
    });
</script>

<style>
    .hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .hover-lift:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important; }
    .letter-spacing-1 { letter-spacing: 1px; }
    .custom-table tbody tr { transition: background-color 0.2s; }
    .custom-table tbody tr:hover { background-color: #fcfdfd !important; }

    /* Custom Pagination Pills */
    .custom-pagination .page-link {
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 4px;
        color: #495057;
        border: none;
    }
    .custom-pagination .page-item.active .page-link {
        background-color: #0d6efd;
        color: #fff;
    }
    .custom-pagination .page-item.disabled .page-link {
        background-color: transparent;
        color: #adb5bd;
        box-shadow: none !important;
    }

    @media print {
        .no-print, .btn, form { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        canvas { max-height: 200px !important; } 
    }
</style>

<?php include_once '../../includes/admin_footer.php'; ?>