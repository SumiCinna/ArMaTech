<?php
// modules/admin/dashboard.php
require_once '../../config/database.php';
include_once '../../includes/admin_header.php';

// 1. GET KEY METRICS
// Total Principal Out (Money Lent)
$sql_principal = "SELECT SUM(principal_amount) as total FROM transactions WHERE status='active'";
$res_principal = $conn->query($sql_principal)->fetch_assoc();

// Total Interest Earned (Money In)
$sql_interest = "SELECT SUM(amount_paid - IFNULL(new_principal, 0) + IFNULL(old_principal, 0)) as total_earnings 
                 FROM payments WHERE payment_type = 'interest_only'";
// Note: This is a simplified calculation. For robust accounting, you sum 'interest_amount' if you stored it.
// Let's assume you just want raw Cash In for today for now:
$sql_cash_today = "SELECT SUM(amount_paid) as today_sales FROM payments WHERE DATE(date_paid) = CURDATE()";
$res_cash = $conn->query($sql_cash_today)->fetch_assoc();

// Total Active Customers
$sql_cust = "SELECT COUNT(*) as count FROM accounts WHERE role='customer'";
$res_cust = $conn->query($sql_cust)->fetch_assoc();

// Items Expiring Soon
$sql_expiring = "SELECT COUNT(*) as count FROM transactions WHERE status='active' AND maturity_date < DATE_ADD(NOW(), INTERVAL 7 DAY)";
$res_expiring = $conn->query($sql_expiring)->fetch_assoc();
?>

<h2 class="fw-bold mb-4">Executive Dashboard</h2>

<div class="row g-4 mb-5">
    
    <div class="col-md-3">
        <div class="admin-card bg-success text-white p-4 h-100">
            <div class="d-flex justify-content-between">
                <div>
                    <h6 class="text-uppercase opacity-75">Cash In (Today)</h6>
                    <h2 class="fw-bold mb-0">₱<?php echo number_format($res_cash['today_sales'] ?? 0, 2); ?></h2>
                </div>
                <i class="fa-solid fa-cash-register fa-2x opacity-50"></i>
            </div>
            <small class="mt-3 d-block opacity-75">Recorded transactions today</small>
        </div>
    </div>

    <div class="col-md-3">
        <div class="admin-card bg-primary text-white p-4 h-100">
            <div class="d-flex justify-content-between">
                <div>
                    <h6 class="text-uppercase opacity-75">Active Lending</h6>
                    <h2 class="fw-bold mb-0">₱<?php echo number_format($res_principal['total'] ?? 0, 2); ?></h2>
                </div>
                <i class="fa-solid fa-hand-holding-dollar fa-2x opacity-50"></i>
            </div>
            <small class="mt-3 d-block opacity-75">Total principal currently out</small>
        </div>
    </div>

    <div class="col-md-3">
        <div class="admin-card bg-dark text-white p-4 h-100">
            <div class="d-flex justify-content-between">
                <div>
                    <h6 class="text-uppercase opacity-75">Total Customers</h6>
                    <h2 class="fw-bold mb-0"><?php echo $res_cust['count']; ?></h2>
                </div>
                <i class="fa-solid fa-users fa-2x opacity-50"></i>
            </div>
            <small class="mt-3 d-block opacity-75">Registered accounts</small>
        </div>
    </div>

    <div class="col-md-3">
        <div class="admin-card bg-warning text-dark p-4 h-100">
            <div class="d-flex justify-content-between">
                <div>
                    <h6 class="text-uppercase opacity-75 fw-bold">Expiring Soon</h6>
                    <h2 class="fw-bold mb-0"><?php echo $res_expiring['count']; ?></h2>
                </div>
                <i class="fa-solid fa-bell fa-2x opacity-50"></i>
            </div>
            <small class="mt-3 d-block opacity-75">Items due within 7 days</small>
        </div>
    </div>

</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold"><i class="fa-solid fa-list me-2"></i> Recent Transactions</h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Date</th>
                    <th>Teller</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>PT Number</th>
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
                    <tr>
                        <td class="ps-4"><?php echo date('M d, h:i A', strtotime($row['date_paid'])); ?></td>
                        <td><span class="badge bg-secondary"><?php echo strtoupper($row['username']); ?></span></td>
                        <td><?php echo ucwords(str_replace('_', ' ', $row['payment_type'])); ?></td>
                        <td class="fw-bold text-success">₱<?php echo number_format($row['amount_paid'], 2); ?></td>
                        <td class="text-muted small"><?php echo $row['pt_number']; ?></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">No recent activity.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once '../../includes/admin_footer.php'; ?>