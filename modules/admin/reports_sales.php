<?php
// modules/admin/reports_sales.php
require_once '../../config/database.php';
include_once '../../includes/admin_header.php';

// 1. DATE LOGIC
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date'] ?? date('Y-m-t');

// 2. MAIN QUERY: List of Transactions (Detailed Table)
$sql = "SELECT p.*, t.pt_number, a.username as teller_name 
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
    
    // Split Income
    if ($row['payment_type'] == 'interest_only') {
        $total_interest += $row['amount_paid'];
    } else {
        $total_principal += $row['amount_paid'];
    }

    // Teller Stats Logic
    $teller = $row['teller_name'] ?? 'System';
    if (!isset($teller_stats[$teller])) {
        $teller_stats[$teller] = 0;
    }
    $teller_stats[$teller] += $row['amount_paid'];
}

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

<div class="container-fluid px-4">
    
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0"><i class="fa-solid fa-chart-line me-2 text-primary"></i> Analytics & Sales</h3>
            <small class="text-muted">Financial performance from <span class="fw-bold"><?php echo date('M d', strtotime($start_date)); ?></span> to <span class="fw-bold"><?php echo date('M d', strtotime($end_date)); ?></span></small>
        </div>
        <div class="no-print">
            <button onclick="exportTableToCSV('sales_report.csv')" class="btn btn-success fw-bold shadow-sm me-2">
                <i class="fa-solid fa-file-excel me-2"></i> Export Excel
            </button>
            <button onclick="window.print()" class="btn btn-dark fw-bold shadow-sm">
                <i class="fa-solid fa-print me-2"></i> Print
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4 bg-light no-print">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="small fw-bold text-muted text-uppercase">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold text-muted text-uppercase">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="fa-solid fa-filter me-2"></i> Apply</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-8 mb-4 mb-lg-0">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">
                    <i class="fa-solid fa-arrow-trend-up me-2 text-success"></i> <?php echo $chart_title; ?>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm bg-primary text-white mb-3">
                <div class="card-body p-4 text-center">
                    <small class="text-white-50 text-uppercase fw-bold">Total Revenue</small>
                    <h1 class="fw-bold mb-0">₱<?php echo number_format($total_collected, 2); ?></h1>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold border-bottom-0">
                    <i class="fa-solid fa-users me-2 text-secondary"></i> Top Performing Tellers
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr><th>Name</th><th class="text-end">Collected</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($teller_stats as $name => $amount): ?>
                                <tr>
                                    <td class="ps-3 py-2 fw-bold text-secondary"><?php echo $name; ?></td>
                                    <td class="text-end pe-3 py-2 fw-bold text-dark">₱<?php echo number_format($amount, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow mb-5">
        <div class="card-header bg-white py-3 d-flex justify-content-between">
            <h6 class="mb-0 fw-bold">Transaction Ledger</h6>
            <span class="badge bg-light text-dark border">
                Total Records: <?php echo count($report_data); ?>
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="dataTable">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>PT Number</th>
                            <th>Type</th>
                            <th>Teller</th>
                            <th class="text-end pe-4">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($report_data) > 0): ?>
                            <?php foreach($report_data as $row): ?>
                                <tr>
                                    <td class="ps-4 text-nowrap">
                                        <?php echo date('M d, Y', strtotime($row['date_paid'])); ?>
                                    </td>
                                    <td class="font-monospace"><?php echo $row['pt_number']; ?></td>
                                    <td>
                                        <?php if($row['payment_type'] == 'interest_only'): ?>
                                            <span class="badge bg-info bg-opacity-10 text-info">Interest</span>
                                        <?php else: ?>
                                            <span class="badge bg-success bg-opacity-10 text-success">Redeem/Partial</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-uppercase text-muted"><?php echo $row['teller_name']; ?></td>
                                    <td class="text-end pe-4 fw-bold">₱<?php echo number_format($row['amount_paid'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-4">No data available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

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
        var rows = document.querySelectorAll("table#dataTable tr");
        
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
</script>

<style>
    @media print {
        .no-print, .btn, form { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        canvas { max-height: 200px !important; } 
    }
</style>

<?php include_once '../../includes/admin_footer.php'; ?>