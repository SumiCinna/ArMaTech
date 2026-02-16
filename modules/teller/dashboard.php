<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['account_id']) || !in_array($_SESSION['role'], ['teller', 'admin'])) {
    header("Location: ../../teller_login.php?error=Unauthorized Access");
    exit();
}

$teller_name = $_SESSION['full_name'];

// 1. Get Transactions Today Count
$sql_count = "SELECT COUNT(*) as total FROM transactions WHERE DATE(date_pawned) = CURDATE()";
$count_today = $conn->query($sql_count)->fetch_assoc()['total'] ?? 0;

// 2. Get Recent Transactions
$sql_recent = "SELECT t.pt_number, t.principal_amount, t.date_pawned, p.first_name, p.last_name 
               FROM transactions t
               JOIN profiles p ON t.customer_id = p.profile_id
               ORDER BY t.date_pawned DESC LIMIT 5";
$recent_txns = $conn->query($sql_recent);

$cash_on_hand = 0.00; 

include_once '../../includes/teller_header.php';
?>



<div class="container">
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="card-title text-muted text-uppercase small fw-bold">Transactions Today</h6>
                    <div class="d-flex align-items-center">
                        <div class="display-6 fw-bold text-dark me-3"><?php echo $count_today; ?></div>
                        <div class="text-primary bg-primary-subtle rounded p-2"><i class="bi bi-receipt fs-4"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="card-title text-muted text-uppercase small fw-bold">Cash Drawer</h6>
                    <div class="d-flex align-items-center">
                        <div class="display-6 fw-bold text-dark me-3">₱<?php echo number_format($cash_on_hand, 2); ?></div>
                        <div class="text-success bg-success-subtle rounded p-2"><i class="bi bi-wallet2 fs-4"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="alert alert-light border-0 shadow-sm d-flex align-items-center h-100">
                <i class="bi bi-info-circle-fill text-info fs-4 me-3"></i>
                <div>
                    <strong class="text-dark">System Notice:</strong> 
                    Reminder to check ID expiration dates for all high-value transactions.
                </div>
            </div>
        </div>
    </div>

    <h5 class="mb-3 text-secondary fw-bold">Quick Operations</h5>
    <div class="row g-4 mb-5">
        
        <div class="col-md-4">
            <a href="new_pawn.php" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm transition-card">
                    <div class="card-body text-center py-4">
                        <div class="mb-3 text-primary"><i class="bi bi-gem display-4"></i></div>
                        <h4 class="fw-bold text-dark">New Pawn</h4>
                        <p class="text-muted small">Create a new loan transaction for gadgets.</p>
                        <span class="btn btn-outline-primary btn-sm rounded-pill px-4">Start <i class="bi bi-arrow-right"></i></span>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="redeem.php" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm transition-card">
                    <div class="card-body text-center py-4">
                        <div class="mb-3 text-success"><i class="bi bi-cash-coin display-4"></i></div>
                        <h4 class="fw-bold text-dark">Redeem / Renew</h4>
                        <p class="text-muted small">Process payments, renewals, and item claims.</p>
                        <span class="btn btn-outline-success btn-sm rounded-pill px-4">Process <i class="bi bi-arrow-right"></i></span>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="search.php" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm transition-card">
                    <div class="card-body text-center py-4">
                        <div class="mb-3 text-secondary"><i class="bi bi-search display-4"></i></div>
                        <h4 class="fw-bold text-dark">Search Records</h4>
                        <p class="text-muted small">Find customers, items, or transaction history.</p>
                        <span class="btn btn-outline-secondary btn-sm rounded-pill px-4">Search <i class="bi bi-arrow-right"></i></span>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-0">
            <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Transactions</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary small text-uppercase">
                    <tr>
                        <th>PT Number</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_txns && $recent_txns->num_rows > 0): ?>
                        <?php while($row = $recent_txns->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['pt_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td><span class="badge bg-primary">New Pawn</span></td>
                                <td>₱<?php echo number_format($row['principal_amount'], 2); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['date_pawned'])); ?></td>
                                <td><span class="badge bg-success-subtle text-success border border-success">Active</span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No recent transactions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<style>
    /* Specific CSS for Dashboard Hover Effects */
    .transition-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .transition-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important; }
</style>

<?php include_once '../../includes/teller_footer.php'; ?>